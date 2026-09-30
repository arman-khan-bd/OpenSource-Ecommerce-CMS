<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\OptionRepository;
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

class PluginController extends BaseAdminController
{
    private PluginManager $pluginManager;
    private HookManager $hookManager;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        PluginManager $pluginManager,
        HookManager $hookManager
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->pluginManager = $pluginManager;
        $this->hookManager = $hookManager;
    }

    #[Route('/admin/plugins', name: 'admin_plugins', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $plugins = $this->pluginManager->discoverPlugins();
        $hookInspector = $this->hookManager->getInspectorSummary();

        return $this->renderAdmin($request, 'Admin/Plugins/Index', [
            'plugins' => $plugins,
            'hookInspector' => $hookInspector,
        ]);
    }

    #[Route('/admin/plugins/toggle', name: 'admin_plugins_toggle', methods: ['POST'])]
    public function toggle(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $slug = $data['slug'] ?? '';

        $isActive = $this->pluginManager->togglePlugin($slug);
        $statusText = $isActive ? 'activated' : 'deactivated';

        if ($isActive) {
            $this->hookManager->doAction('plugin_activated_' . $slug);
        } else {
            $this->hookManager->doAction('plugin_deactivated_' . $slug);
        }

        $request->getSession()->getFlashBag()->add('success', "Plugin '{$slug}' {$statusText}.");

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/plugins');
        }

        return new JsonResponse(['success' => true, 'is_active' => $isActive]);
    }
}
