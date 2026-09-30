<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OptionRepository;
use App\Repository\OrderRepository;
use App\Repository\PostRepository;
use App\Repository\ProductRepository;
use App\Service\HookManager;
use App\Service\ThemeManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment as TwigEnvironment;

/**
 * Server-rendered Twig Storefront with dynamic theme switcher and hook integration.
 */
class StorefrontController extends AbstractController
{
    private string $projectDir;
    private ThemeManager $themeManager;
    private OptionRepository $optionRepo;
    private PostRepository $postRepo;
    private ProductRepository $productRepo;
    private OrderRepository $orderRepo;
    private HookManager $hookManager;
    private TwigEnvironment $twig;

    public function __construct(
        string $projectDir,
        ThemeManager $themeManager,
        OptionRepository $optionRepo,
        PostRepository $postRepo,
        ProductRepository $productRepo,
        OrderRepository $orderRepo,
        HookManager $hookManager,
        TwigEnvironment $twig
    ) {
        $this->projectDir = $projectDir;
        $this->themeManager = $themeManager;
        $this->optionRepo = $optionRepo;
        $this->postRepo = $postRepo;
        $this->productRepo = $productRepo;
        $this->orderRepo = $orderRepo;
        $this->hookManager = $hookManager;
        $this->twig = $twig;
    }

    #[Route('/', name: 'storefront_home', methods: ['GET'])]
    public function home(Request $request): Response
    {
        if (!file_exists($this->projectDir . '/var/installed.lock')) {
            return new RedirectResponse('/install');
        }

        $activeTheme = $this->themeManager->getActiveTheme();
        $customizer = $this->themeManager->getCustomizerOptions($activeTheme);

        $postsData = $this->postRepo->paginate('post', 1, 6, 'published');
        $productsData = $this->productRepo->paginate(1, 8, 'active');

        // Let plugins add hooks to the header/footer
        ob_start();
        $this->hookManager->doAction('storefront_head');
        $headInjected = ob_get_clean();

        ob_start();
        $this->hookManager->doAction('storefront_footer');
        $footerInjected = ob_get_clean();

        $context = [
            'site' => [
                'title' => $this->optionRepo->get('site_title', 'OpenSource eCommerce CMS'),
                'tagline' => $this->optionRepo->get('site_tagline', 'Alternative to WordPress'),
                'description' => $this->optionRepo->get('site_description', 'The modern, lightweight, monolithic & headless alternative to WordPress.'),
                'url' => $this->optionRepo->get('site_url', $request->getSchemeAndHttpHost()),
            ],
            'theme' => [
                'name' => $activeTheme,
                'customizer' => $customizer,
            ],
            'posts' => $postsData['items'],
            'products' => $productsData['items'],
            'headInjected' => $headInjected,
            'footerInjected' => $footerInjected,
        ];

        return $this->renderThemeTemplate('index.html.twig', $context);
    }

    #[Route('/blog/{slug}', name: 'storefront_post', methods: ['GET'])]
    public function post(Request $request, string $slug): Response
    {
        $post = $this->postRepo->findBySlug($slug, 'post');
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        // Apply content filters (e.g. SEO, shortcodes, formatting)
        $filteredContent = $this->hookManager->applyFilter('the_content', $post->getContent());
        $filteredTitle = $this->hookManager->applyFilter('the_title', $post->getTitle());

        $postArray = $post->toArray();
        $postArray['content'] = $filteredContent;
        $postArray['title'] = $filteredTitle;

        $context = [
            'site' => [
                'title' => $this->optionRepo->get('site_title', 'OpenSource eCommerce CMS'),
                'tagline' => $this->optionRepo->get('site_tagline', 'Alternative to WordPress'),
                'description' => $this->optionRepo->get('site_description', 'The modern, lightweight, monolithic & headless alternative to WordPress.'),
            ],
            'post' => $postArray,
            'theme' => [
                'name' => $this->themeManager->getActiveTheme(),
                'customizer' => $this->themeManager->getCustomizerOptions(),
            ],
        ];

        return $this->renderThemeTemplate('post.html.twig', $context);
    }

    #[Route('/product/{slug}', name: 'storefront_product', methods: ['GET'])]
    public function product(Request $request, string $slug): Response
    {
        $product = $this->productRepo->findBySlug($slug);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $context = [
            'site' => [
                'title' => $this->optionRepo->get('site_title', 'OpenSource eCommerce CMS'),
                'tagline' => $this->optionRepo->get('site_tagline', 'Alternative to WordPress'),
                'description' => $this->optionRepo->get('site_description', 'The modern, lightweight, monolithic & headless alternative to WordPress.'),
            ],
            'product' => $product->toArray(),
            'theme' => [
                'name' => $this->themeManager->getActiveTheme(),
                'customizer' => $this->themeManager->getCustomizerOptions(),
            ],
        ];

        return $this->renderThemeTemplate('product.html.twig', $context);
    }

    #[Route('/shop', name: 'storefront_shop', methods: ['GET'])]
    public function shop(Request $request): Response
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $productsData = $this->productRepo->paginate($page, 12, 'active');

        $context = [
            'site' => [
                'title' => $this->optionRepo->get('site_title', 'OpenSource eCommerce CMS'),
                'tagline' => $this->optionRepo->get('site_tagline', 'Alternative to WordPress'),
                'description' => $this->optionRepo->get('site_description', 'The modern, lightweight, monolithic & headless alternative to WordPress.'),
            ],
            'products' => $productsData['items'],
            'pagination' => $productsData,
            'theme' => [
                'name' => $this->themeManager->getActiveTheme(),
                'customizer' => $this->themeManager->getCustomizerOptions(),
            ],
        ];

        return $this->renderThemeTemplate('shop.html.twig', $context);
    }

    #[Route('/checkout', name: 'storefront_checkout', methods: ['GET'])]
    public function checkout(Request $request): Response
    {
        $context = [
            'site' => [
                'title' => $this->optionRepo->get('site_title', 'OpenSource eCommerce CMS'),
                'tagline' => $this->optionRepo->get('site_tagline', 'Alternative to WordPress'),
                'description' => $this->optionRepo->get('site_description', 'The modern, lightweight, monolithic & headless alternative to WordPress.'),
                'url' => $this->optionRepo->get('site_url', $request->getSchemeAndHttpHost()),
            ],
            'theme' => [
                'name' => $this->themeManager->getActiveTheme(),
                'customizer' => $this->themeManager->getCustomizerOptions(),
            ],
        ];

        return $this->renderThemeTemplate('checkout.html.twig', $context);
    }

    #[Route('/checkout/confirmation/{orderNumber}', name: 'storefront_confirmation', methods: ['GET'])]
    public function confirmation(Request $request, string $orderNumber): Response
    {
        $order = $this->orderRepo->findByOrderNumber($orderNumber);
        if (!$order) {
            return new RedirectResponse('/');
        }

        $context = [
            'site' => [
                'title' => $this->optionRepo->get('site_title', 'OpenSource eCommerce CMS'),
                'tagline' => $this->optionRepo->get('site_tagline', 'Alternative to WordPress'),
                'description' => $this->optionRepo->get('site_description', 'The modern, lightweight, monolithic & headless alternative to WordPress.'),
                'url' => $this->optionRepo->get('site_url', $request->getSchemeAndHttpHost()),
            ],
            'order' => $order->toArray(),
            'theme' => [
                'name' => $this->themeManager->getActiveTheme(),
                'customizer' => $this->themeManager->getCustomizerOptions(),
            ],
        ];

        return $this->renderThemeTemplate('confirmation.html.twig', $context);
    }

    #[Route('/api/v1/checkout', name: 'api_checkout', methods: ['POST'])]
    public function processCheckout(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();

        $items = $data['items'] ?? [];
        if (empty($items)) {
            return new JsonResponse(['success' => false, 'message' => 'Your shopping bag is empty.'], Response::HTTP_BAD_REQUEST);
        }

        $customerName = trim($data['customer_name'] ?? '');
        $customerEmail = trim($data['customer_email'] ?? '');

        if (empty($customerName) || empty($customerEmail)) {
            return new JsonResponse(['success' => false, 'message' => 'Customer name and valid email are required.'], Response::HTTP_BAD_REQUEST);
        }

        // Calculate total amount
        $totalAmount = 0.0;
        foreach ($items as $item) {
            $price = (float)($item['price'] ?? 0.0);
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $totalAmount += ($price * $qty);
        }

        // Standard shipping fee (free over $100)
        $shipping = $totalAmount >= 100.0 ? 0.0 : 15.0;
        $totalAmount += $shipping;

        $shippingAddress = [
            'street' => $data['street'] ?? '',
            'city' => $data['city'] ?? '',
            'state' => $data['state'] ?? '',
            'postal_code' => $data['postal_code'] ?? '',
            'country' => $data['country'] ?? 'United States',
            'phone' => $data['phone'] ?? '',
            'payment_method' => $data['payment_method'] ?? 'card',
            'shipping_fee' => $shipping,
        ];

        $orderNumber = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));

        $order = Order::fromArray([
            'order_number' => $orderNumber,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'total_amount' => round($totalAmount, 2),
            'status' => 'processing',
            'items' => $items,
            'shipping_address' => $shippingAddress,
            'notes' => $data['notes'] ?? 'Order placed via Online Storefront',
        ]);

        $savedOrder = $this->orderRepo->save($order);

        // Dispatch Hook
        $this->hookManager->doAction('order_created', $savedOrder);

        return new JsonResponse([
            'success' => true,
            'order_number' => $savedOrder->getOrderNumber(),
            'total_amount' => $savedOrder->getTotalAmount(),
            'redirect' => '/checkout/confirmation/' . $savedOrder->getOrderNumber(),
        ]);
    }

    private function renderThemeTemplate(string $template, array $context): Response
    {
        $theme = $this->themeManager->getActiveTheme();
        $templatePath = "@theme/{$template}";

        try {
            $html = $this->twig->render($templatePath, $context);
            return new Response($html);
        } catch (\Twig\Error\LoaderError $e) {
            // Fallback to default theme or system template
            $fallbackPath = "@themes/default/{$template}";
            try {
                $html = $this->twig->render($fallbackPath, $context);
                return new Response($html);
            } catch (\Throwable $ex) {
                // Return clean responsive fallback
                return new Response($this->renderMinimalFallback($template, $context));
            }
        }
    }

    private function renderMinimalFallback(string $template, array $context): string
    {
        $title = htmlspecialchars($context['site']['title'] ?? 'OpenSource eCommerce CMS');
        return "<!DOCTYPE html><html><head><title>{$title}</title><meta name='viewport' content='width=device-width, initial-scale=1'><style>body{font-family:sans-serif;background:#090d16;color:#f8fafc;padding:3rem;text-align:center;}a{color:#818cf8;}</style></head><body><h1>{$title}</h1><p>OpenSource eCommerce CMS — Alternative to WordPress</p><p><a href='/admin'>Go to Admin Control Panel</a></p></body></html>";
    }
}
