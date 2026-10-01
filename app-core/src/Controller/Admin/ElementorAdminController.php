<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\OptionRepository;
use App\Repository\PluginRepository;
use App\Repository\PostRepository;
use App\Service\AuthService;
use App\Service\CacheFallbackService;
use App\Service\HookManager;
use App\Service\InertiaService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ElementorAdminController extends BaseAdminController
{
    private PluginRepository $pluginRepo;
    private PostRepository $postRepo;
    private HookManager $hookManager;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        PluginRepository $pluginRepo,
        PostRepository $postRepo,
        HookManager $hookManager
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->pluginRepo = $pluginRepo;
        $this->postRepo = $postRepo;
        $this->hookManager = $hookManager;
    }

    #[Route('/admin/elementor', name: 'admin_elementor', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $plugin = $this->pluginRepo->findBySlug('elementor-pro');
        if (!$plugin || !$plugin->isActive()) {
            $request->getSession()->getFlashBag()->add('error', 'Elementor Pro Visual Builder is not currently active.');
            return new RedirectResponse('/admin/plugins');
        }

        $settings = $plugin->getSettings();
        $pages = $this->postRepo->paginate('page', 1, 20);

        return $this->renderAdmin($request, 'Admin/Elementor/Index', [
            'settings' => $settings,
            'pages' => $pages['items'],
            'stats' => [
                'totalPages' => $pages['total'],
                'primaryAccent' => $settings['primary_accent'] ?? '#6366f1',
                'supportWidgetActive' => !empty($settings['enable_floating_support']),
            ],
        ]);
    }

    #[Route('/admin/elementor/settings', name: 'admin_elementor_settings_save', methods: ['POST'])]
    public function saveSettings(Request $request): Response
    {
        $plugin = $this->pluginRepo->findBySlug('elementor-pro');
        if (!$plugin) {
            return new JsonResponse(['success' => false, 'message' => 'Plugin not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $settings = $plugin->getSettings();

        if (isset($data['primary_accent'])) $settings['primary_accent'] = (string)$data['primary_accent'];
        if (isset($data['enable_floating_support'])) $settings['enable_floating_support'] = (bool)$data['enable_floating_support'];
        if (isset($data['support_phone'])) $settings['support_phone'] = (string)$data['support_phone'];
        if (isset($data['support_email'])) $settings['support_email'] = (string)$data['support_email'];
        if (isset($data['whatsapp_number'])) $settings['whatsapp_number'] = (string)$data['whatsapp_number'];
        if (isset($data['enable_micro_animations'])) $settings['enable_micro_animations'] = (bool)$data['enable_micro_animations'];

        $plugin->setSettings($settings);
        $this->pluginRepo->save($plugin);

        $request->getSession()->getFlashBag()->add('success', 'Elementor Pro visual builder settings saved!');

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/elementor');
        }

        return new JsonResponse(['success' => true, 'settings' => $settings]);
    }
}
