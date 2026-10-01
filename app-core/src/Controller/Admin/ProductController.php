<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Repository\OptionRepository;
use App\Repository\ProductRepository;
use App\Service\AuthService;
use App\Service\CacheFallbackService;
use App\Service\HookManager;
use App\Service\InertiaService;
use App\Service\MediaService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends BaseAdminController
{
    private ProductRepository $productRepo;
    private HookManager $hookManager;
    private MediaService $mediaService;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        ProductRepository $productRepo,
        HookManager $hookManager,
        MediaService $mediaService
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->productRepo = $productRepo;
        $this->hookManager = $hookManager;
        $this->mediaService = $mediaService;
    }

    #[Route('/admin/products', name: 'admin_products', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $status = $request->query->get('status', 'all');
        $search = $request->query->get('search', '');

        $productsData = $this->productRepo->paginate($page, 10, $status, $search);

        return $this->renderAdmin($request, 'Admin/Products/Index', [
            'products' => $productsData,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    #[Route('/admin/products/new', name: 'admin_products_new', methods: ['GET'])]
    public function create(Request $request): Response
    {
        $categories = $this->getProductCategoriesList();

        return $this->renderAdmin($request, 'Admin/Products/Edit', [
            'product' => [
                'id' => null,
                'title' => '',
                'slug' => '',
                'description' => '',
                'price' => 0.0,
                'sale_price' => null,
                'sku' => '',
                'stock' => 15,
                'status' => 'active',
                'images' => [],
                'meta' => [
                    'categories' => [$categories[0] ?? 'Headphones & Audio'],
                    'tags' => ['featured'],
                    'cost_price' => null,
                    'barcode' => '',
                    'stock_status' => 'instock',
                    'track_stock' => true,
                    'low_stock_threshold' => 3,
                    'weight' => '',
                    'dimensions' => ['length' => '', 'width' => '', 'height' => ''],
                    'is_virtual' => false,
                    'free_shipping' => false,
                    'attributes' => [
                        ['name' => 'Brand', 'value' => 'OpenSource Gear'],
                        ['name' => 'Warranty', 'value' => '1 Year Official'],
                    ],
                    'focus_keyword' => '',
                    'seo_title' => '',
                    'seo_description' => '',
                    'canonical_url' => '',
                    'og_title' => '',
                    'og_description' => '',
                    'og_image' => '',
                    'robots_index' => 'index',
                    'robots_follow' => 'follow',
                ],
            ],
            'categories' => $categories,
            'isNew' => true,
        ]);
    }

    #[Route('/admin/products/{id}/edit', name: 'admin_products_edit', methods: ['GET'])]
    public function edit(Request $request, int $id): Response
    {
        $product = $this->productRepo->findById($id);
        if (!$product) {
            $request->getSession()->getFlashBag()->add('error', 'Product not found.');
            return new RedirectResponse('/admin/products');
        }

        $categories = $this->getProductCategoriesList();
        $productData = $product->toArray();

        $defaultMeta = [
            'categories' => [$categories[0] ?? 'Headphones & Audio'],
            'tags' => [],
            'cost_price' => null,
            'barcode' => '',
            'stock_status' => 'instock',
            'track_stock' => true,
            'low_stock_threshold' => 3,
            'weight' => '',
            'dimensions' => ['length' => '', 'width' => '', 'height' => ''],
            'is_virtual' => false,
            'free_shipping' => false,
            'attributes' => [],
            'focus_keyword' => '',
            'seo_title' => '',
            'seo_description' => '',
            'canonical_url' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'robots_index' => 'index',
            'robots_follow' => 'follow',
        ];

        $productData['meta'] = array_merge($defaultMeta, is_array($productData['meta']) ? $productData['meta'] : []);

        // Compatibility: if legacy single category was stored, migrate into categories array
        if (!empty($productData['meta']['category']) && empty($productData['meta']['categories'])) {
            $productData['meta']['categories'] = [$productData['meta']['category']];
        }

        return $this->renderAdmin($request, 'Admin/Products/Edit', [
            'product' => $productData,
            'categories' => $categories,
            'isNew' => false,
        ]);
    }

    #[Route('/admin/products/check-slug', name: 'admin_products_check_slug', methods: ['GET', 'POST'])]
    public function checkSlug(Request $request): JsonResponse
    {
        $slug = (string)($request->get('slug') ?: $request->get('title', ''));
        $excludeId = $request->get('id') ? (int)$request->get('id') : null;

        $uniqueSlug = $this->productRepo->generateUniqueSlug($slug, $excludeId);

        return new JsonResponse([
            'success' => true,
            'slug' => $uniqueSlug,
            'is_collision' => (strtolower(trim($slug)) !== $uniqueSlug),
        ]);
    }

    #[Route('/admin/products/categories', name: 'admin_products_categories', methods: ['GET', 'POST'])]
    public function categories(Request $request): JsonResponse
    {
        $categories = $this->getProductCategoriesList();

        if ($request->isMethod('POST')) {
            $data = [];
            $content = $request->getContent();
            if (!empty($content)) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
            if (empty($data)) {
                $data = $request->request->all();
            }

            $name = trim((string)($data['name'] ?? $request->get('name', '')));

            if ($name === '') {
                return new JsonResponse(['success' => false, 'message' => 'Category name cannot be empty.'], 400);
            }

            $exists = false;
            foreach ($categories as $cat) {
                if (strcasecmp($cat, $name) === 0) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $categories[] = $name;
                $this->optionRepo->set('product_categories', $categories);
            }

            return new JsonResponse([
                'success' => true,
                'categories' => $categories,
                'created' => $name,
            ]);
        }

        return new JsonResponse(['success' => true, 'categories' => $categories]);
    }

    #[Route('/admin/products/upload-image', name: 'admin_products_upload_image', methods: ['POST'])]
    public function uploadImage(Request $request): JsonResponse
    {
        $file = $request->files->get('image') ?: $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['success' => false, 'message' => 'No image file uploaded.'], 400);
        }

        try {
            $altText = $request->request->get('alt_text', '');
            $media = $this->mediaService->upload($file, $altText);

            return new JsonResponse([
                'success' => true,
                'url' => $media->getUrl(),
                'filename' => $media->getFilename(),
                'media' => $media->toArray(),
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/admin/products/save', name: 'admin_products_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();

        // Apply pre-save filter hook
        $data = $this->hookManager->applyFilter('pre_save_product', $data);

        $rawSlug = !empty($data['slug']) ? $data['slug'] : ($data['title'] ?? 'item');
        $productId = !empty($data['id']) ? (int)$data['id'] : null;

        // Auto generate collision-free slug (e.g. item_1, item_2)
        $data['slug'] = $this->productRepo->generateUniqueSlug($rawSlug, $productId);

        $product = Product::fromArray($data);
        $saved = $this->productRepo->save($product);

        // Fire post-save action hook
        $this->hookManager->doAction('save_product', $saved->getId(), $saved);

        $request->getSession()->getFlashBag()->add('success', 'Product saved successfully!');

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/products');
        }

        return new JsonResponse(['success' => true, 'product' => $saved->toArray()]);
    }

    #[Route('/admin/products/{id}/delete', name: 'admin_products_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, int $id): Response
    {
        $deleted = $this->productRepo->delete($id);

        if ($deleted) {
            $request->getSession()->getFlashBag()->add('success', 'Product deleted.');
        } else {
            $request->getSession()->getFlashBag()->add('error', 'Failed to delete product.');
        }

        return new RedirectResponse('/admin/products');
    }

    private function getProductCategoriesList(): array
    {
        $categories = $this->optionRepo->get('product_categories', [
            'Headphones & Audio',
            'Keyboards & Peripherals',
            'Laptops & Computers',
            'Accessories',
            'Gaming Gear',
            'Smart Devices',
        ]);

        if (!is_array($categories)) {
            $categories = ['Headphones & Audio', 'Keyboards & Peripherals', 'Laptops & Computers', 'Accessories', 'Gaming Gear', 'Smart Devices'];
        }

        return array_values(array_unique(array_filter(array_map('trim', $categories))));
    }
}
