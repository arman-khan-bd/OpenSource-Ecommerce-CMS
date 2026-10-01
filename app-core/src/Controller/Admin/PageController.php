<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Repository\OptionRepository;
use App\Repository\PostRepository;
use App\Service\AuthService;
use App\Service\CacheFallbackService;
use App\Service\HookManager;
use App\Service\InertiaService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends BaseAdminController
{
    private PostRepository $postRepo;
    private HookManager $hookManager;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        PostRepository $postRepo,
        HookManager $hookManager
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->postRepo = $postRepo;
        $this->hookManager = $hookManager;
    }

    #[Route('/admin/pages', name: 'admin_pages', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $status = $request->query->get('status', 'all');
        $search = $request->query->get('search', '');

        $pagesData = $this->postRepo->paginate('page', $page, 10, $status, $search);

        return $this->renderAdmin($request, 'Admin/Pages/Index', [
            'pages' => $pagesData,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    #[Route('/admin/pages/new', name: 'admin_pages_new', methods: ['GET'])]
    public function create(Request $request): Response
    {
        $pm = \App\Service\PluginManager::getInstance();
        $isElementorActive = $pm && $pm->isPluginActive('elementor-pro');

        return $this->renderAdmin($request, 'Admin/Pages/Edit', [
            'page' => [
                'id' => null,
                'title' => '',
                'slug' => '',
                'content' => '',
                'excerpt' => '',
                'type' => 'page',
                'status' => 'published',
                'featured_image' => null,
                'meta' => [
                    'builder' => $isElementorActive ? 'elementor' : 'classic',
                    'builder_data' => [],
                    'seo_title' => '',
                    'seo_description' => '',
                    'robots_index' => 'index',
                    'robots_follow' => 'follow',
                ],
            ],
            'isNew' => true,
        ]);
    }

    #[Route('/admin/pages/{id}/edit', name: 'admin_pages_edit', methods: ['GET'])]
    public function edit(Request $request, int $id): Response
    {
        $page = $this->postRepo->findById($id);
        if (!$page || $page->getType() !== 'page') {
            $request->getSession()->getFlashBag()->add('error', 'Page not found.');
            return new RedirectResponse('/admin/pages');
        }

        $pm = \App\Service\PluginManager::getInstance();
        $isElementorActive = $pm && $pm->isPluginActive('elementor-pro');

        $pageData = $page->toArray();
        if (!isset($pageData['meta']) || !is_array($pageData['meta'])) {
            $pageData['meta'] = [];
        }

        // If Elementor is inactive, always default to classic WordPress editor
        if (!$isElementorActive) {
            $pageData['meta']['builder'] = 'classic';
        } elseif (!isset($pageData['meta']['builder'])) {
            $pageData['meta']['builder'] = str_contains($pageData['content'] ?? '', '[elementor') ? 'elementor' : 'classic';
        }

        return $this->renderAdmin($request, 'Admin/Pages/Edit', [
            'page' => $pageData,
            'isNew' => false,
        ]);
    }
}
