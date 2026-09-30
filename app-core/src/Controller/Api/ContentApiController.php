<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\OptionRepository;
use App\Repository\PostRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Headless content REST API for mobile apps and external consumers.
 */
class ContentApiController extends AbstractController
{
    private PostRepository $postRepo;
    private ProductRepository $productRepo;
    private OptionRepository $optionRepo;

    public function __construct(PostRepository $postRepo, ProductRepository $productRepo, OptionRepository $optionRepo)
    {
        $this->postRepo = $postRepo;
        $this->productRepo = $productRepo;
        $this->optionRepo = $optionRepo;
    }

    #[Route('/api/v1/posts', name: 'api_posts', methods: ['GET'])]
    public function getPosts(Request $request): JsonResponse
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(50, (int)$request->query->get('limit', 10)));
        $data = $this->postRepo->paginate('post', $page, $limit, 'published');
        return new JsonResponse($data);
    }

    #[Route('/api/v1/products', name: 'api_products', methods: ['GET'])]
    public function getProducts(Request $request): JsonResponse
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(50, (int)$request->query->get('limit', 10)));
        $data = $this->productRepo->paginate($page, $limit, 'active');
        return new JsonResponse($data);
    }

    #[Route('/api/v1/site', name: 'api_site', methods: ['GET'])]
    public function getSiteInfo(): JsonResponse
    {
        return new JsonResponse([
            'title' => $this->optionRepo->get('site_title', 'Modern CMS'),
            'tagline' => $this->optionRepo->get('site_tagline', 'Next-Gen Standalone Platform'),
            'active_theme' => $this->optionRepo->get('active_theme', 'default'),
            'api_version' => '1.0.0',
        ]);
    }
}
