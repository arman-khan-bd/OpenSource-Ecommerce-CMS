<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\MediaRepository;
use App\Repository\OptionRepository;
use App\Repository\OrderRepository;
use App\Repository\PostRepository;
use App\Repository\ProductRepository;
use App\Service\AuthService;
use App\Service\CacheFallbackService;
use App\Service\DatabaseService;
use App\Service\InertiaService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends BaseAdminController
{
    private PostRepository $postRepo;
    private ProductRepository $productRepo;
    private OrderRepository $orderRepo;
    private MediaRepository $mediaRepo;
    private DatabaseService $db;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        PostRepository $postRepo,
        ProductRepository $productRepo,
        OrderRepository $orderRepo,
        MediaRepository $mediaRepo,
        DatabaseService $db
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->postRepo = $postRepo;
        $this->productRepo = $productRepo;
        $this->orderRepo = $orderRepo;
        $this->mediaRepo = $mediaRepo;
        $this->db = $db;
    }

    #[Route('/admin', name: 'admin_root', methods: ['GET'])]
    #[Route('/admin/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $postsData = $this->postRepo->paginate('post', 1, 5);
        $productsData = $this->productRepo->paginate(1, 5);
        $ordersData = $this->orderRepo->paginate(1, 5);
        $orderStats = $this->orderRepo->getStats();
        $mediaData = $this->mediaRepo->paginate(1, 1);

        $driver = 'none';
        if ($this->db->getConnection()) {
            $class = get_class($this->db->getConnection()->getDatabasePlatform());
            $driver = str_contains(strtolower($class), 'sqlite') ? 'sqlite' : 'mysql';
        }

        $systemInfo = [
            'php_version' => PHP_VERSION,
            'db_driver' => $driver,
            'cache_driver' => $this->cacheService->getDriverInfo()['active_driver'],
            'opcache_enabled' => function_exists('opcache_get_status') && !empty(opcache_get_status()),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Apache / LiteSpeed (cPanel)',
            'memory_limit' => ini_get('memory_limit'),
        ];

        return $this->renderAdmin($request, 'Admin/Dashboard', [
            'stats' => [
                'total_posts' => $postsData['total'],
                'total_products' => $productsData['total'],
                'total_orders' => $ordersData['total'],
                'total_media' => $mediaData['total'],
                'total_sales' => $orderStats['total_sales'],
                'pending_orders' => $orderStats['pending'],
            ],
            'recentPosts' => $postsData['items'],
            'recentOrders' => $ordersData['items'],
            'recentProducts' => $productsData['items'],
            'systemInfo' => $systemInfo,
        ]);
    }
}
