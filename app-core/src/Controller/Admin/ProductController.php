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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends BaseAdminController
{
    private ProductRepository $productRepo;
    private HookManager $hookManager;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        ProductRepository $productRepo,
        HookManager $hookManager
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->productRepo = $productRepo;
        $this->hookManager = $hookManager;
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
        return $this->renderAdmin($request, 'Admin/Products/Edit', [
            'product' => [
                'id' => null,
                'title' => '',
                'slug' => '',
                'description' => '',
                'price' => 0.0,
                'sale_price' => null,
                'sku' => '',
                'stock' => 10,
                'status' => 'active',
                'images' => [],
                'meta' => ['category' => 'Default', 'attributes' => []],
            ],
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

        return $this->renderAdmin($request, 'Admin/Products/Edit', [
            'product' => $product->toArray(),
            'isNew' => false,
        ]);
    }

    #[Route('/admin/products/save', name: 'admin_products_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();

        // Apply pre-save filter hook
        $data = $this->hookManager->applyFilter('pre_save_product', $data);

        $slug = !empty($data['slug']) ? $data['slug'] : preg_replace('/[^a-z0-9]+/i', '-', strtolower($data['title'] ?? 'item'));
        $data['slug'] = trim($slug, '-');

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
}
