<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SduiScreen;
use App\Repository\OptionRepository;
use App\Repository\SduiScreenRepository;
use App\Service\AuthService;
use App\Service\CacheFallbackService;
use App\Service\InertiaService;
use App\Service\SDUIParser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SduiBuilderController extends BaseAdminController
{
    private SduiScreenRepository $sduiRepo;
    private SDUIParser $sduiParser;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        SduiScreenRepository $sduiRepo,
        SDUIParser $sduiParser
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->sduiRepo = $sduiRepo;
        $this->sduiParser = $sduiParser;
    }

    #[Route('/admin/sdui', name: 'admin_sdui', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $screens = $this->sduiRepo->all();
        $selectedSlug = $request->query->get('screen', 'home');

        $activeScreen = $this->sduiRepo->findBySlug($selectedSlug);
        if (!$activeScreen && !empty($screens)) {
            $activeScreen = $screens[0];
            $selectedSlug = $activeScreen->getSlug();
        }

        $parsedPreview = null;
        if ($activeScreen) {
            $parsedPreview = $this->sduiParser->parseScreen($activeScreen->toArray());
        }

        // Palette of available Android UI components
        $componentPalette = [
            [
                'type' => 'BannerSlider',
                'name' => 'Banner Carousel',
                'description' => 'Auto-sliding high-res hero promotional banners',
                'icon' => 'image',
                'default' => [
                    'type' => 'BannerSlider',
                    'aspect_ratio' => '16:9',
                    'auto_scroll' => true,
                    'scroll_interval_ms' => 4000,
                    'banners' => [
                        [
                            'title' => 'Flash Sale Today',
                            'subtitle' => 'Save up to 40% on hardware',
                            'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=800&q=80',
                            'badge' => 'LIMITED',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'QuickActions',
                'name' => 'Quick Action Grid',
                'description' => 'Circular or square quick action buttons',
                'icon' => 'grid',
                'default' => [
                    'type' => 'QuickActions',
                    'columns' => 4,
                    'actions' => [
                        ['label' => 'Orders', 'icon' => 'package', 'target' => 'orders', 'color' => '#6366f1'],
                        ['label' => 'Favorites', 'icon' => 'heart', 'target' => 'favorites', 'color' => '#ec4899'],
                        ['label' => 'Deals', 'icon' => 'zap', 'target' => 'deals', 'color' => '#f59e0b'],
                        ['label' => 'Support', 'icon' => 'message-circle', 'target' => 'support', 'color' => '#10b981'],
                    ],
                ],
            ],
            [
                'type' => 'CategoryRow',
                'name' => 'Category Chips',
                'description' => 'Horizontal scrolling category pills',
                'icon' => 'list',
                'default' => [
                    'type' => 'CategoryRow',
                    'title' => 'Top Categories',
                    'items' => [
                        ['id' => 'c1', 'label' => 'Audio', 'icon' => 'headphones', 'badge' => 'New', 'color' => '#818cf8'],
                        ['id' => 'c2', 'label' => 'Gaming', 'icon' => 'gamepad-2', 'badge' => null, 'color' => '#38bdf8'],
                        ['id' => 'c3', 'label' => 'Keyboards', 'icon' => 'keyboard', 'badge' => 'Hot', 'color' => '#f472b6'],
                    ],
                ],
            ],
            [
                'type' => 'ProductGrid',
                'name' => 'Product Showcase',
                'description' => 'Dynamic grid pulling live inventory from CMS',
                'icon' => 'shopping-bag',
                'default' => [
                    'type' => 'ProductGrid',
                    'title' => 'New Arrivals',
                    'subtitle' => 'Handpicked for your setup',
                    'data_source' => 'database',
                    'columns' => 2,
                    'card_style' => 'elevated',
                ],
            ],
            [
                'type' => 'AnnouncementCard',
                'name' => 'Announcement Card',
                'description' => 'Gradient-filled notice with call to action',
                'icon' => 'bell',
                'default' => [
                    'type' => 'AnnouncementCard',
                    'icon' => 'gift',
                    'title' => 'Special Weekend Drop',
                    'message' => 'Free express shipping on all orders over $75.',
                    'button_text' => 'Shop Collection',
                ],
            ],
            [
                'type' => 'FeaturedArticle',
                'name' => 'Editorial Article',
                'description' => 'Card featuring latest blog post or guide',
                'icon' => 'book-open',
                'default' => [
                    'type' => 'FeaturedArticle',
                    'title' => 'Read Our Latest Journal',
                ],
            ],
            [
                'type' => 'Spacer',
                'name' => 'Vertical Spacer',
                'description' => 'Adds breathing room between components',
                'icon' => 'move-vertical',
                'default' => [
                    'type' => 'Spacer',
                    'height' => 16,
                ],
            ],
        ];

        return $this->renderAdmin($request, 'Admin/Sdui/Builder', [
            'screens' => array_map(fn($s) => $s->toArray(), $screens),
            'activeScreen' => $activeScreen ? $activeScreen->toArray() : null,
            'parsedPreview' => $parsedPreview,
            'palette' => $componentPalette,
            'apiEndpoint' => '/api/v1/sdui/screen/' . ($activeScreen ? $activeScreen->getSlug() : 'home'),
        ]);
    }

    #[Route('/admin/sdui/save', name: 'admin_sdui_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $slug = $data['slug'] ?? 'home';

        $screen = $this->sduiRepo->findBySlug($slug);
        if (!$screen) {
            $screen = new SduiScreen();
            $screen->setSlug($slug);
        }

        $screen->setTitle($data['title'] ?? 'Mobile Screen');
        $screen->setDescription($data['description'] ?? '');
        $screen->setLayout($data['layout'] ?? []);
        $screen->setIsActive((bool)($data['is_active'] ?? true));

        $saved = $this->sduiRepo->save($screen);
        $request->getSession()->getFlashBag()->add('success', 'Mobile screen layout published to Android SDUI API!');

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/sdui?screen=' . $saved->getSlug());
        }

        return new JsonResponse(['success' => true, 'screen' => $saved->toArray()]);
    }

    #[Route('/admin/sdui/preview/{slug}', name: 'admin_sdui_preview', methods: ['GET'])]
    public function preview(string $slug): JsonResponse
    {
        $screen = $this->sduiRepo->findBySlug($slug);
        if (!$screen) {
            return new JsonResponse(['error' => 'Screen not found'], 404);
        }

        $parsed = $this->sduiParser->parseScreen($screen->toArray());
        return new JsonResponse($parsed);
    }
}
