<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\PostRepository;
use App\Repository\ProductRepository;

/**
 * Server-Driven UI (SDUI) Parser for Android Mobile Client.
 * Transforms schema component trees into rich, native Android Compose / Kotlin-ready JSON models,
 * dynamically binding live CMS data (products, articles, banners).
 */
class SDUIParser
{
    private ProductRepository $productRepo;
    private PostRepository $postRepo;
    private HookManager $hookManager;

    public function __construct(ProductRepository $productRepo, PostRepository $postRepo, HookManager $hookManager)
    {
        $this->productRepo = $productRepo;
        $this->postRepo = $postRepo;
        $this->hookManager = $hookManager;
    }

    /**
     * Parses raw SDUI layout tree, resolves dynamic queries, applies filter hooks,
     * and returns structured native Android screen spec.
     */
    public function parseScreen(array $screenData): array
    {
        $layout = $screenData['layout'] ?? [];
        $components = $layout['components'] ?? [];

        $resolvedComponents = [];
        foreach ($components as $component) {
            $resolved = $this->resolveComponent($component);
            if ($resolved !== null) {
                $resolvedComponents[] = $resolved;
            }
        }

        $screenSpec = [
            'screen_id' => $screenData['slug'] ?? 'screen',
            'title' => $screenData['title'] ?? 'Mobile Screen',
            'theme' => [
                'status_bar_color' => $layout['status_bar_color'] ?? '#0f172a',
                'background_color' => $layout['background_color'] ?? '#020617',
                'is_dark_mode' => (bool)($layout['is_dark_mode'] ?? true),
                'primary_color' => $layout['primary_color'] ?? '#6366f1',
            ],
            'app_bar' => $layout['app_bar'] ?? [
                'type' => 'TopAppBar',
                'title' => $screenData['title'] ?? 'CMS Mobile',
                'show_back_button' => false,
                'actions' => [
                    ['icon' => 'search', 'action' => 'open_search'],
                    ['icon' => 'shopping-cart', 'action' => 'open_cart', 'badge' => 2],
                ],
            ],
            'components' => $resolvedComponents,
            'bottom_nav' => $layout['bottom_nav'] ?? [
                'type' => 'BottomNavigation',
                'items' => [
                    ['id' => 'home', 'label' => 'Home', 'icon' => 'home', 'screen' => 'home', 'selected' => true],
                    ['id' => 'shop', 'label' => 'Shop', 'icon' => 'shopping-bag', 'screen' => 'shop'],
                    ['id' => 'blog', 'label' => 'Blog', 'icon' => 'book-open', 'screen' => 'blog'],
                    ['id' => 'profile', 'label' => 'Account', 'icon' => 'user', 'screen' => 'profile'],
                ],
            ],
            'version' => '1.0.0',
            'generated_at' => date('c'),
        ];

        // Allow plugins to filter the final mobile SDUI response
        return $this->hookManager->applyFilter('sdui_screen_response', $screenSpec, $screenData);
    }

    private function resolveComponent(array $component): ?array
    {
        $type = $component['type'] ?? 'Unknown';

        switch ($type) {
            case 'BannerSlider':
                return $this->resolveBannerSlider($component);

            case 'CategoryRow':
                return $this->resolveCategoryRow($component);

            case 'ProductGrid':
                return $this->resolveProductGrid($component);

            case 'AnnouncementCard':
                return $this->resolveAnnouncementCard($component);

            case 'QuickActions':
                return $this->resolveQuickActions($component);

            case 'FeaturedArticle':
                return $this->resolveFeaturedArticle($component);

            case 'SearchBar':
            case 'Spacer':
            case 'Divider':
                return $component;

            default:
                // Custom plugin-defined component
                return $this->hookManager->applyFilter('sdui_resolve_component_' . $type, $component);
        }
    }

    private function resolveBannerSlider(array $component): array
    {
        return [
            'id' => $component['id'] ?? ('banner_' . uniqid()),
            'type' => 'BannerSlider',
            'aspect_ratio' => $component['aspect_ratio'] ?? '16:9',
            'auto_scroll' => (bool)($component['auto_scroll'] ?? true),
            'scroll_interval_ms' => (int)($component['scroll_interval_ms'] ?? 4000),
            'banners' => $component['banners'] ?? [
                [
                    'id' => 'b1',
                    'title' => 'New Season Drops',
                    'subtitle' => 'Exclusive Mobile Discount: Use code APP20',
                    'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1000&q=80',
                    'badge' => '20% OFF',
                    'click_action' => [
                        'type' => 'navigate',
                        'target' => 'screen_shop',
                    ],
                ],
            ],
        ];
    }

    private function resolveCategoryRow(array $component): array
    {
        return [
            'id' => $component['id'] ?? ('cat_' . uniqid()),
            'type' => 'CategoryRow',
            'title' => $component['title'] ?? 'Popular Categories',
            'items' => $component['items'] ?? [
                ['id' => 'c1', 'label' => 'Fashion', 'icon' => 'shirt', 'badge' => 'New', 'color' => '#818cf8'],
                ['id' => 'c2', 'label' => 'Electronics', 'icon' => 'laptop', 'badge' => null, 'color' => '#38bdf8'],
                ['id' => 'c3', 'label' => 'Footwear', 'icon' => 'footprints', 'badge' => 'Hot', 'color' => '#f472b6'],
                ['id' => 'c4', 'label' => 'Watches', 'icon' => 'watch', 'badge' => null, 'color' => '#fbbf24'],
                ['id' => 'c5', 'label' => 'Beauty', 'icon' => 'sparkles', 'badge' => null, 'color' => '#34d399'],
            ],
        ];
    }

    private function resolveProductGrid(array $component): array
    {
        $dataSource = $component['data_source'] ?? 'database';
        $items = [];

        if ($dataSource === 'database' || $dataSource === 'recent') {
            $limit = (int)($component['limit'] ?? 6);
            $paginated = $this->productRepo->paginate(1, $limit, 'active');
            foreach ($paginated['items'] as $prod) {
                $items[] = [
                    'id' => $prod['id'],
                    'title' => $prod['title'],
                    'slug' => $prod['slug'],
                    'price' => $prod['price'],
                    'sale_price' => $prod['sale_price'],
                    'sku' => $prod['sku'],
                    'stock' => $prod['stock'],
                    'image_url' => !empty($prod['images'][0]) ? $prod['images'][0] : 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=600&q=80',
                    'action' => [
                        'type' => 'product_detail',
                        'product_id' => $prod['id'],
                        'slug' => $prod['slug'],
                    ],
                ];
            }
        } else {
            $items = $component['items'] ?? [];
        }

        return [
            'id' => $component['id'] ?? ('prod_grid_' . uniqid()),
            'type' => 'ProductGrid',
            'title' => $component['title'] ?? 'Featured Products',
            'subtitle' => $component['subtitle'] ?? 'Hand-picked for you',
            'columns' => (int)($component['columns'] ?? 2),
            'card_style' => $component['card_style'] ?? 'elevated', // elevated, outlined, minimal
            'items' => $items,
        ];
    }

    private function resolveAnnouncementCard(array $component): array
    {
        return [
            'id' => $component['id'] ?? ('announcement_' . uniqid()),
            'type' => 'AnnouncementCard',
            'icon' => $component['icon'] ?? 'gift',
            'title' => $component['title'] ?? 'Flash Weekend Promo',
            'message' => $component['message'] ?? 'Get free nationwide express delivery on all orders above $50.',
            'gradient' => $component['gradient'] ?? ['from' => '#4f46e5', 'to' => '#9333ea'],
            'dismissable' => (bool)($component['dismissable'] ?? true),
            'button_text' => $component['button_text'] ?? 'Claim Offer',
            'button_action' => $component['button_action'] ?? ['type' => 'url', 'target' => '/offers'],
        ];
    }

    private function resolveQuickActions(array $component): array
    {
        return [
            'id' => $component['id'] ?? ('quick_' . uniqid()),
            'type' => 'QuickActions',
            'columns' => (int)($component['columns'] ?? 4),
            'actions' => $component['actions'] ?? [
                ['id' => 'q1', 'label' => 'My Orders', 'icon' => 'package', 'target' => 'screen_orders', 'color' => '#6366f1'],
                ['id' => 'q2', 'label' => 'Wishlist', 'icon' => 'heart', 'target' => 'screen_wishlist', 'color' => '#ec4899'],
                ['id' => 'q3', 'label' => 'Flash Deals', 'icon' => 'zap', 'target' => 'screen_deals', 'color' => '#f59e0b'],
                ['id' => 'q4', 'label' => '24/7 Chat', 'icon' => 'message-circle', 'target' => 'screen_support', 'color' => '#10b981'],
            ],
        ];
    }

    private function resolveFeaturedArticle(array $component): array
    {
        $article = null;
        if (!empty($component['article_id'])) {
            $post = $this->postRepo->findById((int)$component['article_id']);
            if ($post) {
                $article = $post->toArray();
            }
        }

        if (!$article) {
            $recent = $this->postRepo->paginate('post', 1, 1, 'published');
            if (!empty($recent['items'][0])) {
                $article = $recent['items'][0];
            }
        }

        return [
            'id' => $component['id'] ?? ('article_' . uniqid()),
            'type' => 'FeaturedArticle',
            'title' => $component['title'] ?? 'From the Journal',
            'article' => [
                'title' => $article['title'] ?? 'Behind the Craft: Building Next-Gen Experiences',
                'excerpt' => $article['excerpt'] ?? 'Explore how our design engineers create cutting-edge architectures...',
                'image_url' => $article['featured_image'] ?? 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=800&q=80',
                'reading_time' => '4 min read',
                'action' => [
                    'type' => 'article_detail',
                    'slug' => $article['slug'] ?? 'story',
                ],
            ],
        ];
    }
}
