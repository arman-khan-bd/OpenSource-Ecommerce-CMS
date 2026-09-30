<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Option;
use App\Entity\Post;
use App\Entity\Product;
use App\Entity\SduiScreen;
use App\Entity\User;
use App\Repository\OptionRepository;
use App\Repository\PostRepository;
use App\Repository\ProductRepository;
use App\Repository\SduiScreenRepository;
use App\Repository\UserRepository;
use App\Service\DatabaseService;
use App\Service\InertiaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InstallerController extends AbstractController
{
    private string $projectDir;
    private DatabaseService $dbService;
    private InertiaService $inertia;
    private UserRepository $userRepo;
    private OptionRepository $optionRepo;
    private PostRepository $postRepo;
    private ProductRepository $productRepo;
    private SduiScreenRepository $sduiRepo;

    public function __construct(
        string $projectDir,
        DatabaseService $dbService,
        InertiaService $inertia,
        UserRepository $userRepo,
        OptionRepository $optionRepo,
        PostRepository $postRepo,
        ProductRepository $productRepo,
        SduiScreenRepository $sduiRepo
    ) {
        $this->projectDir = $projectDir;
        $this->dbService = $dbService;
        $this->inertia = $inertia;
        $this->userRepo = $userRepo;
        $this->optionRepo = $optionRepo;
        $this->postRepo = $postRepo;
        $this->productRepo = $productRepo;
        $this->sduiRepo = $sduiRepo;
    }

    #[Route('/install', name: 'installer_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $lockFile = $this->projectDir . '/var/installed.lock';
        if (file_exists($lockFile)) {
            return $this->redirect('/admin');
        }

        $requirements = $this->checkRequirements();

        return $this->inertia->render($request, 'Installer/Wizard', [
            'requirements' => $requirements,
            'defaultDbPath' => $this->projectDir . '/var/database.sqlite',
            'canProceed' => $requirements['all_passed'],
        ]);
    }

    #[Route('/install/test-db', name: 'installer_test_db', methods: ['POST'])]
    public function testDb(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?: $request->request->all();
        $result = $this->dbService->testConnection($payload);
        return new JsonResponse($result);
    }

    #[Route('/install/execute', name: 'installer_execute', methods: ['POST'])]
    public function execute(Request $request): JsonResponse
    {
        $lockFile = $this->projectDir . '/var/installed.lock';
        if (file_exists($lockFile)) {
            return new JsonResponse(['success' => false, 'message' => 'CMS is already installed.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true) ?: $request->request->all();

        try {
            // 1. Save database configuration
            $driver = $data['db_driver'] ?? 'pdo_mysql';
            $prefix = !empty($data['db_prefix']) ? $data['db_prefix'] : 'cms_';

            $dbConfig = [
                'prefix' => $prefix,
                'connection' => ($driver === 'sqlite' || $driver === 'pdo_sqlite') ? [
                    'driver' => 'pdo_sqlite',
                    'path' => $this->projectDir . '/var/database.sqlite',
                ] : [
                    'driver' => 'pdo_mysql',
                    'host' => $data['db_host'] ?? '127.0.0.1',
                    'port' => (int)($data['db_port'] ?? 3306),
                    'dbname' => $data['db_name'] ?? 'cms_database',
                    'user' => $data['db_user'] ?? 'root',
                    'password' => $data['db_pass'] ?? '',
                    'charset' => 'utf8mb4',
                ],
            ];

            $this->dbService->saveConfiguration($dbConfig);

            // 2. Execute schema migration
            $this->dbService->createTables($prefix);

            // 3. Seed site settings
            $siteTitle = $data['site_title'] ?? 'OpenSource eCommerce CMS';
            $siteTagline = $data['site_tagline'] ?? 'Alternative to WordPress';
            $adminEmail = $data['admin_email'] ?? 'admin@example.com';
            $adminUser = $data['admin_username'] ?? 'admin';
            $adminPassword = $data['admin_password'] ?? 'Password123!';

            $this->optionRepo->set('site_title', $siteTitle);
            $this->optionRepo->set('site_tagline', $siteTagline);
            $this->optionRepo->set('site_description', 'The modern, lightweight, monolithic & headless alternative to WordPress.');
            $this->optionRepo->set('admin_email', $adminEmail);
            $this->optionRepo->set('active_theme', 'default');
            $this->optionRepo->set('site_url', $request->getSchemeAndHttpHost());
            $this->optionRepo->set('redis_host', $data['redis_host'] ?? 'none');
            $this->optionRepo->set('redis_port', $data['redis_port'] ?? 6379);

            // 4. Create Super Admin User
            $user = User::fromArray([
                'username' => $adminUser,
                'email' => $adminEmail,
                'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
                'role' => 'admin',
                'full_name' => 'System Administrator',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&h=200&q=80',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $this->userRepo->save($user);

            // 5. Seed sample blog post
            $samplePost = Post::fromArray([
                'title' => 'Welcome to Modern Standalone CMS',
                'slug' => 'welcome-to-modern-cms',
                'content' => "<h2>A Fresh Era for Monolithic and Headless Architecture</h2>\n<p>Modern CMS combines the instant, zero-configuration deployment convenience of traditional cPanel hosting with the ultra-fast reactivity of Vue 3 and Symfony 7.</p>\n<h3>Key Architectural Highlights</h3>\n<ul>\n<li><strong>Monolithic Bridge:</strong> Single-page application feel with server-driven routing via Inertia.js.</li>\n<li><strong>Extensible Hook & Plugin Engine:</strong> Action and filter hooks compatible with the WordPress developer paradigm.</li>\n<li><strong>Server-Driven UI (SDUI) for Android:</strong> Visually compose native Android mobile screens directly from your browser.</li>\n<li><strong>Redis with File Fallback:</strong> Lightning-fast in-memory caching where available, falling back smoothly to file storage on standard hosting.</li>\n</ul>",
                'excerpt' => 'Discover how this production-ready CMS delivers monolithic speed with headless Android SDUI flexibility.',
                'type' => 'post',
                'status' => 'published',
                'author_id' => 1,
                'featured_image' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1200&q=80',
                'meta' => ['tags' => ['Announcements', 'Architecture', 'Open Source']],
                'published_at' => date('Y-m-d H:i:s'),
            ]);
            $this->postRepo->save($samplePost);

            // 6. Seed sample e-commerce products
            $products = [
                [
                    'title' => 'Quantum ANC Wireless Earbuds',
                    'slug' => 'quantum-anc-wireless-earbuds',
                    'description' => 'Flagship active noise cancellation earbuds with custom spatial audio drivers and 36-hour battery endurance.',
                    'price' => 179.99,
                    'sale_price' => 149.99,
                    'sku' => 'QTM-EAR-01',
                    'stock' => 45,
                    'status' => 'active',
                    'images' => [
                        'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?auto=format&fit=crop&w=600&q=80',
                    ],
                    'meta' => ['category' => 'Audio', 'color' => 'Graphite Black'],
                ],
                [
                    'title' => 'Mechanical Tactile Pro Keyboard',
                    'slug' => 'mechanical-tactile-pro-keyboard',
                    'description' => 'Precision CNC aluminum chassis with hot-swappable lubricated switches and PBT keycaps.',
                    'price' => 220.00,
                    'sale_price' => null,
                    'sku' => 'KBD-TAC-PRO',
                    'stock' => 18,
                    'status' => 'active',
                    'images' => [
                        'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=600&q=80',
                    ],
                    'meta' => ['category' => 'Peripherals', 'layout' => '75% Compact'],
                ],
            ];

            foreach ($products as $p) {
                $this->productRepo->save(Product::fromArray($p));
            }

            // 7. Seed default Android SDUI Screen
            $defaultSdui = [
                'title' => 'Mobile Storefront Home',
                'slug' => 'home',
                'description' => 'Primary Android app home screen rendered dynamically via SDUI.',
                'is_active' => true,
                'layout' => [
                    'status_bar_color' => '#0f172a',
                    'background_color' => '#020617',
                    'is_dark_mode' => true,
                    'primary_color' => '#6366f1',
                    'app_bar' => [
                        'type' => 'TopAppBar',
                        'title' => 'Apex Mobile Store',
                        'show_back_button' => false,
                        'actions' => [
                            ['icon' => 'search', 'action' => 'open_search'],
                            ['icon' => 'shopping-cart', 'action' => 'open_cart', 'badge' => 2],
                        ],
                    ],
                    'components' => [
                        [
                            'type' => 'BannerSlider',
                            'aspect_ratio' => '16:9',
                            'auto_scroll' => true,
                            'banners' => [
                                [
                                    'title' => 'Exclusive Mobile Launch',
                                    'subtitle' => 'Get 25% off your first mobile order',
                                    'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1000&q=80',
                                    'badge' => 'SPECIAL',
                                ],
                            ],
                        ],
                        [
                            'type' => 'QuickActions',
                            'columns' => 4,
                            'actions' => [
                                ['label' => 'Orders', 'icon' => 'package', 'target' => 'orders', 'color' => '#6366f1'],
                                ['label' => 'Favorites', 'icon' => 'heart', 'target' => 'favorites', 'color' => '#ec4899'],
                                ['label' => 'Deals', 'icon' => 'zap', 'target' => 'deals', 'color' => '#f59e0b'],
                                ['label' => 'Support', 'icon' => 'message-circle', 'target' => 'support', 'color' => '#10b981'],
                            ],
                        ],
                        [
                            'type' => 'CategoryRow',
                            'title' => 'Trending Collections',
                        ],
                        [
                            'type' => 'ProductGrid',
                            'title' => 'Featured Hardware',
                            'subtitle' => 'Top rated items this week',
                            'data_source' => 'database',
                            'columns' => 2,
                        ],
                        [
                            'type' => 'AnnouncementCard',
                            'icon' => 'sparkles',
                            'title' => 'Join Apex VIP Rewards',
                            'message' => 'Earn double points on every app purchase this week.',
                            'button_text' => 'Unlock VIP',
                        ],
                        [
                            'type' => 'FeaturedArticle',
                            'title' => 'From the Journal',
                        ],
                    ],
                ],
            ];
            $this->sduiRepo->save(SduiScreen::fromArray($defaultSdui));

            // 8. Create installed.lock
            $lockPayload = [
                'installed_at' => date('c'),
                'version' => '1.0.0',
                'php_version' => PHP_VERSION,
                'db_driver' => $driver,
            ];
            file_put_contents($lockFile, json_encode($lockPayload, JSON_PRETTY_PRINT));

            return new JsonResponse([
                'success' => true,
                'message' => 'Installation completed successfully! Welcome to Modern CMS.',
                'redirect' => '/admin/login',
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function checkRequirements(): array
    {
        $uploadsDir = dirname($this->projectDir) . '/public_html/uploads';
        $varDir = $this->projectDir . '/var';

        $checks = [
            'php_version' => [
                'name' => 'PHP >= 8.2',
                'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'current' => PHP_VERSION,
            ],
            'pdo' => [
                'name' => 'PDO Extension',
                'passed' => extension_loaded('pdo'),
                'current' => extension_loaded('pdo') ? 'Enabled' : 'Missing',
            ],
            'pdo_mysql' => [
                'name' => 'PDO MySQL Driver',
                'passed' => extension_loaded('pdo_mysql'),
                'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Missing',
            ],
            'pdo_sqlite' => [
                'name' => 'PDO SQLite Driver',
                'passed' => extension_loaded('pdo_sqlite'),
                'current' => extension_loaded('pdo_sqlite') ? 'Enabled' : 'Optional',
            ],
            'json' => [
                'name' => 'Native JSON Support',
                'passed' => extension_loaded('json'),
                'current' => extension_loaded('json') ? 'Enabled' : 'Missing',
            ],
            'curl' => [
                'name' => 'cURL Extension',
                'passed' => extension_loaded('curl'),
                'current' => extension_loaded('curl') ? 'Enabled' : 'Missing',
            ],
            'var_writable' => [
                'name' => 'Storage Directory (app-core/var)',
                'passed' => is_writable($varDir) || (!file_exists($varDir) && is_writable(dirname($varDir))),
                'current' => is_writable($varDir) ? 'Writable (0775)' : 'Check Permissions',
            ],
            'uploads_writable' => [
                'name' => 'Media Storage (public_html/uploads)',
                'passed' => is_writable($uploadsDir) || (!file_exists($uploadsDir) && is_writable(dirname($uploadsDir))),
                'current' => is_writable($uploadsDir) ? 'Writable (0775)' : 'Check Permissions',
            ],
        ];

        $allPassed = true;
        foreach ($checks as $k => $c) {
            if (!$c['passed'] && $k !== 'pdo_sqlite') {
                $allPassed = false;
            }
        }

        return [
            'checks' => $checks,
            'all_passed' => $allPassed,
        ];
    }
}
