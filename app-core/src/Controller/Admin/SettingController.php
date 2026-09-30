<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\OptionRepository;
use App\Service\AuthService;
use App\Service\CacheFallbackService;
use App\Service\HookManager;
use App\Service\InertiaService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SettingController extends BaseAdminController
{
    private HookManager $hookManager;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        HookManager $hookManager
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->hookManager = $hookManager;
    }

    #[Route('/admin/settings', name: 'admin_settings', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true) ?: $request->request->all();

            if (isset($data['site_title'])) $this->optionRepo->set('site_title', $data['site_title']);
            if (isset($data['site_tagline'])) $this->optionRepo->set('site_tagline', $data['site_tagline']);
            if (isset($data['site_description'])) $this->optionRepo->set('site_description', $data['site_description']);
            if (isset($data['site_url'])) $this->optionRepo->set('site_url', $data['site_url']);
            if (isset($data['admin_email'])) $this->optionRepo->set('admin_email', $data['admin_email']);
            if (isset($data['redis_host'])) $this->optionRepo->set('redis_host', $data['redis_host']);
            if (isset($data['redis_port'])) $this->optionRepo->set('redis_port', (int)$data['redis_port']);
            if (isset($data['currency'])) $this->optionRepo->set('currency', $data['currency']);

            $this->hookManager->doAction('saved_settings', $data);

            $request->getSession()->getFlashBag()->add('success', 'Site settings saved successfully.');

            if ($request->headers->get('X-Inertia') === 'true') {
                return new RedirectResponse('/admin/settings');
            }

            return new JsonResponse(['success' => true]);
        }

        $allSettings = [
            'site_title' => $this->optionRepo->get('site_title', 'OpenSource eCommerce CMS'),
            'site_tagline' => $this->optionRepo->get('site_tagline', 'Alternative to WordPress'),
            'site_description' => $this->optionRepo->get('site_description', 'The modern, lightweight, monolithic & headless alternative to WordPress.'),
            'site_url' => $this->optionRepo->get('site_url', $request->getSchemeAndHttpHost()),
            'admin_email' => $this->optionRepo->get('admin_email', 'admin@example.com'),
            'redis_host' => $this->optionRepo->get('redis_host', 'none'),
            'redis_port' => $this->optionRepo->get('redis_port', 6379),
            'currency' => $this->optionRepo->get('currency', 'USD'),
        ];

        $cacheInfo = $this->cacheService->getDriverInfo();

        $diagnostics = [
            'php_version' => PHP_VERSION,
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'extensions' => [
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'pdo_sqlite' => extension_loaded('pdo_sqlite'),
                'curl' => extension_loaded('curl'),
                'fileinfo' => extension_loaded('fileinfo'),
                'gd' => extension_loaded('gd'),
                'redis' => extension_loaded('redis'),
                'opcache' => extension_loaded('Zend OPcache'),
            ],
        ];

        return $this->renderAdmin($request, 'Admin/Settings/Index', [
            'settings' => $allSettings,
            'cacheInfo' => $cacheInfo,
            'diagnostics' => $diagnostics,
        ]);
    }

    #[Route('/admin/settings/cache-flush', name: 'admin_cache_flush', methods: ['POST'])]
    public function flushCache(Request $request): Response
    {
        $this->cacheService->clear();
        $this->hookManager->doAction('after_flush_cache');

        $request->getSession()->getFlashBag()->add('success', 'Cache purged cleanly across all layers.');

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/settings');
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/admin/settings/test-redis', name: 'admin_test_redis', methods: ['POST'])]
    public function testRedis(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $host = $data['host'] ?? '127.0.0.1';
        $port = (int)($data['port'] ?? 6379);
        $pass = $data['password'] ?? null;

        $result = $this->cacheService->testRedisConnection($host, $port, $pass);
        return new JsonResponse($result);
    }
}
