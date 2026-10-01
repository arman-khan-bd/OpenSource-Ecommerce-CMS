<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\OptionRepository;
use App\Repository\OrderRepository;
use App\Repository\PluginRepository;
use App\Service\AuthService;
use App\Service\CacheFallbackService;
use App\Service\HookManager;
use App\Service\InertiaService;
use App\Service\PluginManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WooCommerceAdminController extends BaseAdminController
{
    private PluginRepository $pluginRepo;
    private OrderRepository $orderRepo;
    private HookManager $hookManager;
    private PluginManager $pluginManager;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        PluginRepository $pluginRepo,
        OrderRepository $orderRepo,
        HookManager $hookManager,
        PluginManager $pluginManager
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->pluginRepo = $pluginRepo;
        $this->orderRepo = $orderRepo;
        $this->hookManager = $hookManager;
        $this->pluginManager = $pluginManager;
    }

    #[Route('/admin/woocommerce', name: 'admin_woocommerce', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $plugin = $this->pluginRepo->findBySlug('woocommerce-core');
        if (!$plugin || !$plugin->isActive()) {
            $request->getSession()->getFlashBag()->add('error', 'WooCommerce Commerce Pro is not currently active.');
            return new RedirectResponse('/admin/plugins');
        }

        $settings = $plugin->getSettings();
        $ordersData = $this->orderRepo->paginate(1, 10);

        // Calculate store totals
        $totalRevenue = 0.0;
        foreach ($ordersData['items'] as $ord) {
            $totalRevenue += (float)($ord['total_amount'] ?? 0);
        }

        return $this->renderAdmin($request, 'Admin/WooCommerce/Index', [
            'settings' => $settings,
            'recentOrders' => $ordersData['items'],
            'stats' => [
                'totalOrders' => $ordersData['total'],
                'totalRevenue' => round($totalRevenue, 2),
                'activeCouponsCount' => count($settings['coupons'] ?? []),
                'freeShippingThreshold' => $settings['free_shipping_threshold'] ?? 100,
            ],
        ]);
    }

    #[Route('/admin/woocommerce/coupons', name: 'admin_woocommerce_coupons_save', methods: ['POST'])]
    public function saveCoupon(Request $request): Response
    {
        $plugin = $this->pluginRepo->findBySlug('woocommerce-core');
        if (!$plugin) {
            return new JsonResponse(['success' => false, 'message' => 'Plugin not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $code = strtoupper(trim((string)($data['code'] ?? '')));
        $type = $data['type'] ?? 'percent';
        $amount = (float)($data['amount'] ?? 10);
        $minSpend = !empty($data['min_spend']) ? (float)$data['min_spend'] : 0;
        $description = trim((string)($data['description'] ?? ''));

        if (empty($code)) {
            return new JsonResponse(['success' => false, 'message' => 'Coupon code is required.'], 400);
        }

        $settings = $plugin->getSettings();
        $coupons = $settings['coupons'] ?? [];

        // Update if existing or append
        $updated = false;
        foreach ($coupons as &$c) {
            if (strtoupper($c['code']) === $code) {
                $c['type'] = $type;
                $c['amount'] = $amount;
                $c['min_spend'] = $minSpend;
                $c['description'] = $description;
                $updated = true;
                break;
            }
        }
        unset($c);

        if (!$updated) {
            $coupons[] = [
                'code' => $code,
                'type' => $type,
                'amount' => $amount,
                'min_spend' => $minSpend,
                'description' => $description ?: "{$amount}" . ($type === 'percent' ? '% OFF' : '$ OFF'),
            ];
        }

        $settings['coupons'] = $coupons;
        $plugin->setSettings($settings);
        $this->pluginRepo->save($plugin);

        $request->getSession()->getFlashBag()->add('success', "Coupon '{$code}' saved successfully!");

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/woocommerce');
        }

        return new JsonResponse(['success' => true, 'coupons' => $coupons]);
    }

    #[Route('/admin/woocommerce/coupons/{code}', name: 'admin_woocommerce_coupons_delete', methods: ['DELETE', 'POST'])]
    public function deleteCoupon(Request $request, string $code): Response
    {
        $plugin = $this->pluginRepo->findBySlug('woocommerce-core');
        if (!$plugin) {
            return new JsonResponse(['success' => false, 'message' => 'Plugin not found'], 404);
        }

        $settings = $plugin->getSettings();
        $coupons = $settings['coupons'] ?? [];

        $filtered = array_values(array_filter($coupons, fn($c) => strtoupper($c['code']) !== strtoupper($code)));
        $settings['coupons'] = $filtered;

        $plugin->setSettings($settings);
        $this->pluginRepo->save($plugin);

        $request->getSession()->getFlashBag()->add('success', "Coupon '{$code}' deleted.");

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/woocommerce');
        }

        return new JsonResponse(['success' => true, 'coupons' => $filtered]);
    }

    #[Route('/admin/woocommerce/settings', name: 'admin_woocommerce_settings_save', methods: ['POST'])]
    public function updateStoreSettings(Request $request): Response
    {
        $plugin = $this->pluginRepo->findBySlug('woocommerce-core');
        if (!$plugin) {
            return new JsonResponse(['success' => false, 'message' => 'Plugin not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $settings = $plugin->getSettings();

        if (isset($data['currency_symbol'])) $settings['currency_symbol'] = (string)$data['currency_symbol'];
        if (isset($data['currency_code'])) $settings['currency_code'] = (string)$data['currency_code'];
        if (isset($data['free_shipping_threshold'])) $settings['free_shipping_threshold'] = (float)$data['free_shipping_threshold'];
        if (isset($data['stock_urgency_threshold'])) $settings['stock_urgency_threshold'] = (int)$data['stock_urgency_threshold'];
        if (isset($data['enable_coupons'])) $settings['enable_coupons'] = (bool)$data['enable_coupons'];
        if (isset($data['enable_free_shipping_meter'])) $settings['enable_free_shipping_meter'] = (bool)$data['enable_free_shipping_meter'];
        if (isset($data['enable_trust_badges'])) $settings['enable_trust_badges'] = (bool)$data['enable_trust_badges'];

        $plugin->setSettings($settings);
        $this->pluginRepo->save($plugin);

        $request->getSession()->getFlashBag()->add('success', 'WooCommerce store settings updated successfully!');

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/woocommerce');
        }

        return new JsonResponse(['success' => true, 'settings' => $settings]);
    }
}
