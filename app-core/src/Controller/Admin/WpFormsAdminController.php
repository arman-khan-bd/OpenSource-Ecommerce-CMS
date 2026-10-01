<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\OptionRepository;
use App\Repository\PluginRepository;
use App\Service\AuthService;
use App\Service\CacheFallbackService;
use App\Service\HookManager;
use App\Service\InertiaService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WpFormsAdminController extends BaseAdminController
{
    private PluginRepository $pluginRepo;
    private HookManager $hookManager;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        PluginRepository $pluginRepo,
        HookManager $hookManager
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->pluginRepo = $pluginRepo;
        $this->hookManager = $hookManager;
    }

    #[Route('/admin/wpforms', name: 'admin_wpforms', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $plugin = $this->pluginRepo->findBySlug('wpforms-builder');
        if (!$plugin || !$plugin->isActive()) {
            $request->getSession()->getFlashBag()->add('error', 'WPForms Lead Builder is not currently active.');
            return new RedirectResponse('/admin/plugins');
        }

        $settings = $plugin->getSettings();

        $forms = [
            [
                'id' => 'contact',
                'name' => 'General Inquiry & Support Form',
                'tag' => '[wpforms id="contact"]',
                'fields_count' => 4,
                'entries_count' => 8,
                'status' => 'active',
            ],
            [
                'id' => 'newsletter',
                'name' => 'VIP Newsletter Subscription Box',
                'tag' => '[wpforms id="newsletter"]',
                'fields_count' => 1,
                'entries_count' => 24,
                'status' => 'active',
            ],
        ];

        $recentEntries = [
            [
                'id' => 101,
                'form' => 'General Inquiry',
                'name' => 'Marcus Vance',
                'email' => 'marcus.v@apextech.io',
                'subject' => 'Enterprise Hardware Consultation',
                'date' => date('Y-m-d H:i', strtotime('-2 hours')),
            ],
            [
                'id' => 102,
                'form' => 'VIP Newsletter',
                'name' => 'Elena Rostova',
                'email' => 'elena@cyberdyne.org',
                'subject' => 'VIP Coupon Drop Opt-in',
                'date' => date('Y-m-d H:i', strtotime('-5 hours')),
            ],
            [
                'id' => 103,
                'form' => 'General Inquiry',
                'name' => 'David Chen',
                'email' => 'david.c@quantum.dev',
                'subject' => 'Bulk Shipping Discount Inquiry',
                'date' => date('Y-m-d H:i', strtotime('-1 day')),
            ],
        ];

        return $this->renderAdmin($request, 'Admin/WpForms/Index', [
            'settings' => $settings,
            'forms' => $forms,
            'recentEntries' => $recentEntries,
            'stats' => [
                'totalForms' => count($forms),
                'totalEntries' => 32,
                'notificationEmail' => $settings['notification_email'] ?? 'admin@example.com',
            ],
        ]);
    }

    #[Route('/admin/wpforms/settings', name: 'admin_wpforms_settings_save', methods: ['POST'])]
    public function saveSettings(Request $request): Response
    {
        $plugin = $this->pluginRepo->findBySlug('wpforms-builder');
        if (!$plugin) {
            return new JsonResponse(['success' => false, 'message' => 'Plugin not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $settings = $plugin->getSettings();

        if (isset($data['notification_email'])) $settings['notification_email'] = (string)$data['notification_email'];
        if (isset($data['enable_honeypot'])) $settings['enable_honeypot'] = (bool)$data['enable_honeypot'];
        if (isset($data['success_message'])) $settings['success_message'] = (string)$data['success_message'];

        $plugin->setSettings($settings);
        $this->pluginRepo->save($plugin);

        $request->getSession()->getFlashBag()->add('success', 'WPForms configuration settings saved!');

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/wpforms');
        }

        return new JsonResponse(['success' => true, 'settings' => $settings]);
    }
}
