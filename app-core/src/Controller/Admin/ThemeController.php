<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\OptionRepository;
use App\Service\AuthService;
use App\Service\CacheFallbackService;
use App\Service\HookManager;
use App\Service\InertiaService;
use App\Service\ThemeManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ThemeController extends BaseAdminController
{
    private ThemeManager $themeManager;
    private HookManager $hookManager;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        ThemeManager $themeManager,
        HookManager $hookManager
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->themeManager = $themeManager;
        $this->hookManager = $hookManager;
    }

    #[Route('/admin/themes', name: 'admin_themes', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $themes = $this->themeManager->getInstalledThemes();
        $activeTheme = $this->themeManager->getActiveTheme();
        $customizerSettings = $this->themeManager->getCustomizerOptions($activeTheme);

        return $this->renderAdmin($request, 'Admin/Themes/Index', [
            'themes' => $themes,
            'activeTheme' => $activeTheme,
            'customizerSettings' => $customizerSettings,
        ]);
    }

    #[Route('/admin/themes/activate', name: 'admin_themes_activate', methods: ['POST'])]
    public function activate(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $slug = $data['slug'] ?? 'default';

        $success = $this->themeManager->setActiveTheme($slug);
        if ($success) {
            $this->hookManager->doAction('after_switch_theme', $slug);
            $this->cacheService->clear(); // Clear template cache
            $request->getSession()->getFlashBag()->add('success', "Theme '{$slug}' is now live on your storefront!");
        } else {
            $request->getSession()->getFlashBag()->add('error', "Could not activate theme '{$slug}'.");
        }

        return new RedirectResponse('/admin/themes');
    }

    #[Route('/admin/themes/customize', name: 'admin_themes_customize', methods: ['POST'])]
    public function customize(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $themeSlug = $data['theme'] ?? $this->themeManager->getActiveTheme();
        $settings = $data['settings'] ?? [];

        $this->themeManager->saveCustomizerOptions($settings, $themeSlug);
        $this->cacheService->clear();

        $request->getSession()->getFlashBag()->add('success', 'Theme customizer options saved successfully.');

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/themes');
        }

        return new JsonResponse(['success' => true]);
    }
}
