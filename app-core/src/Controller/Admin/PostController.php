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

class PostController extends BaseAdminController
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

    #[Route('/admin/posts', name: 'admin_posts', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $status = $request->query->get('status', 'all');
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', 'post');

        $postsData = $this->postRepo->paginate($type, $page, 10, $status, $search);

        return $this->renderAdmin($request, 'Admin/Posts/Index', [
            'posts' => $postsData,
            'filters' => [
                'type' => $type,
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    #[Route('/admin/posts/new', name: 'admin_posts_new', methods: ['GET'])]
    public function create(Request $request): Response
    {
        $type = $request->query->get('type', 'post');
        return $this->renderAdmin($request, 'Admin/Posts/Edit', [
            'post' => [
                'id' => null,
                'title' => '',
                'slug' => '',
                'content' => '',
                'excerpt' => '',
                'type' => $type,
                'status' => 'draft',
                'featured_image' => null,
                'meta' => ['tags' => [], 'seo_title' => '', 'seo_description' => ''],
            ],
            'isNew' => true,
        ]);
    }

    #[Route('/admin/posts/{id}/edit', name: 'admin_posts_edit', methods: ['GET'])]
    public function edit(Request $request, int $id): Response
    {
        $post = $this->postRepo->findById($id);
        if (!$post) {
            $request->getSession()->getFlashBag()->add('error', 'Post not found.');
            return new RedirectResponse('/admin/posts');
        }

        return $this->renderAdmin($request, 'Admin/Posts/Edit', [
            'post' => $post->toArray(),
            'isNew' => false,
        ]);
    }

    #[Route('/admin/posts/save', name: 'admin_posts_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();

        // Apply pre-save filter hook
        $data = $this->hookManager->applyFilter('pre_save_post', $data);

        $slug = !empty($data['slug']) ? $data['slug'] : preg_replace('/[^a-z0-9]+/i', '-', strtolower($data['title'] ?? 'untitled'));
        $data['slug'] = trim($slug, '-');

        $currentUser = $this->auth->getCurrentUser($request);
        if ($currentUser) {
            $data['author_id'] = $currentUser->getId();
        }

        $post = Post::fromArray($data);
        $saved = $this->postRepo->save($post);

        // Fire post-save action hook
        $this->hookManager->doAction('save_post', $saved->getId(), $saved);

        $request->getSession()->getFlashBag()->add('success', 'Post saved successfully!');

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/posts');
        }

        return new JsonResponse(['success' => true, 'post' => $saved->toArray()]);
    }

    #[Route('/admin/posts/{id}/delete', name: 'admin_posts_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, int $id): Response
    {
        $this->hookManager->doAction('before_delete_post', $id);
        $deleted = $this->postRepo->delete($id);

        if ($deleted) {
            $this->hookManager->doAction('deleted_post', $id);
            $request->getSession()->getFlashBag()->add('success', 'Post moved to trash.');
        } else {
            $request->getSession()->getFlashBag()->add('error', 'Failed to delete post.');
        }

        return new RedirectResponse('/admin/posts');
    }
}
