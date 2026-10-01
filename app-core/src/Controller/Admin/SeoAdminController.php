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

class SeoAdminController extends BaseAdminController
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

    #[Route('/admin/seo', name: 'admin_seo', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $plugin = $this->pluginRepo->findBySlug('yoast-seo-pro');
        if (!$plugin || !$plugin->isActive()) {
            $plugin = $this->pluginRepo->findBySlug('seo-toolkit');
        }

        if (!$plugin || !$plugin->isActive()) {
            $request->getSession()->getFlashBag()->add('error', 'SEO Suite plugin is not currently active.');
            return new RedirectResponse('/admin/plugins');
        }

        $settings = $plugin->getSettings();

        return $this->renderAdmin($request, 'Admin/Seo/Index', [
            'settings' => $settings,
            'pluginSlug' => $plugin->getSlug(),
            'siteDefaults' => [
                'title' => $this->optionRepo->get('site_title', 'Apex Modern CMS Store'),
                'description' => $this->optionRepo->get('site_description', 'The modern, lightweight, monolithic & headless alternative to WordPress.'),
                'url' => $this->optionRepo->get('site_url', $request->getSchemeAndHttpHost()),
            ],
        ]);
    }

    #[Route('/admin/seo/settings', name: 'admin_seo_settings_save', methods: ['POST'])]
    public function saveSettings(Request $request): Response
    {
        $pluginSlug = $request->get('plugin_slug', 'yoast-seo-pro');
        $plugin = $this->pluginRepo->findBySlug($pluginSlug) ?: $this->pluginRepo->findBySlug('seo-toolkit');

        if (!$plugin) {
            return new JsonResponse(['success' => false, 'message' => 'Plugin not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $settings = $plugin->getSettings();

        if (isset($data['site_name'])) $settings['site_name'] = (string)$data['site_name'];
        if (isset($data['separator'])) $settings['separator'] = (string)$data['separator'];
        if (isset($data['title_template'])) $settings['title_template'] = (string)$data['title_template'];
        if (isset($data['twitter_handle'])) $settings['twitter_handle'] = (string)$data['twitter_handle'];
        if (isset($data['social_share_image'])) $settings['social_share_image'] = (string)$data['social_share_image'];
        if (isset($data['enable_json_ld_schema'])) $settings['enable_json_ld_schema'] = (bool)$data['enable_json_ld_schema'];
        if (isset($data['enable_breadcrumbs'])) $settings['enable_breadcrumbs'] = (bool)$data['enable_breadcrumbs'];
        if (isset($data['enable_sitemap_xml'])) $settings['enable_sitemap_xml'] = (bool)$data['enable_sitemap_xml'];
        if (isset($data['robots_default'])) $settings['robots_default'] = (string)$data['robots_default'];

        $plugin->setSettings($settings);
        $this->pluginRepo->save($plugin);

        $request->getSession()->getFlashBag()->add('success', 'Yoast SEO settings updated successfully!');

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/seo');
        }

        return new JsonResponse(['success' => true, 'settings' => $settings]);
    }
}
