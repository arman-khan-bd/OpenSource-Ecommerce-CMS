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
use App\Service\MediaService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostController extends BaseAdminController
{
    private PostRepository $postRepo;
    private HookManager $hookManager;
    private MediaService $mediaService;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        PostRepository $postRepo,
        HookManager $hookManager,
        MediaService $mediaService
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->postRepo = $postRepo;
        $this->hookManager = $hookManager;
        $this->mediaService = $mediaService;
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
        $categories = $this->getCategoriesList();

        return $this->renderAdmin($request, 'Admin/Posts/Edit', [
            'post' => [
                'id' => null,
                'title' => '',
                'slug' => '',
                'content' => '',
                'excerpt' => '',
                'type' => $type,
                'status' => 'published',
                'featured_image' => null,
                'meta' => [
                    'categories' => [$categories[0] ?? 'Technology'],
                    'tags' => [],
                    'focus_keyword' => '',
                    'seo_title' => '',
                    'seo_description' => '',
                    'canonical_url' => '',
                    'og_title' => '',
                    'og_description' => '',
                    'og_image' => '',
                    'robots_index' => 'index',
                    'robots_follow' => 'follow',
                    'reading_time' => 1,
                    'word_count' => 0,
                    'is_featured' => false,
                ],
            ],
            'categories' => $categories,
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

        $categories = $this->getCategoriesList();
        $postData = $post->toArray();

        $defaultMeta = [
            'categories' => [$categories[0] ?? 'Technology'],
            'tags' => [],
            'focus_keyword' => '',
            'seo_title' => '',
            'seo_description' => '',
            'canonical_url' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'robots_index' => 'index',
            'robots_follow' => 'follow',
            'reading_time' => 1,
            'word_count' => 0,
            'is_featured' => false,
        ];
        $postData['meta'] = array_merge($defaultMeta, is_array($postData['meta']) ? $postData['meta'] : []);

        return $this->renderAdmin($request, 'Admin/Posts/Edit', [
            'post' => $postData,
            'categories' => $categories,
            'isNew' => false,
        ]);
    }

    #[Route('/admin/posts/check-slug', name: 'admin_posts_check_slug', methods: ['GET', 'POST'])]
    public function checkSlug(Request $request): JsonResponse
    {
        $slug = (string)($request->get('slug') ?: $request->get('title', ''));
        $excludeId = $request->get('id') ? (int)$request->get('id') : null;
        $type = (string)($request->get('type', 'post'));

        $uniqueSlug = $this->postRepo->generateUniqueSlug($slug, $excludeId, $type);

        return new JsonResponse([
            'success' => true,
            'slug' => $uniqueSlug,
            'is_collision' => (strtolower(trim($slug)) !== $uniqueSlug),
        ]);
    }

    #[Route('/admin/posts/categories', name: 'admin_posts_categories', methods: ['GET', 'POST'])]
    public function categories(Request $request): JsonResponse
    {
        $categories = $this->getCategoriesList();

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
                $this->optionRepo->set('post_categories', $categories);
            }

            return new JsonResponse([
                'success' => true,
                'categories' => $categories,
                'created' => $name,
            ]);
        }

        return new JsonResponse(['success' => true, 'categories' => $categories]);
    }

    #[Route('/admin/posts/upload-image', name: 'admin_posts_upload_image', methods: ['POST'])]
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

    #[Route('/admin/posts/save', name: 'admin_posts_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();

        // Apply pre-save filter hook
        $data = $this->hookManager->applyFilter('pre_save_post', $data);

        $rawSlug = !empty($data['slug']) ? $data['slug'] : ($data['title'] ?? 'untitled');
        $postId = !empty($data['id']) ? (int)$data['id'] : null;
        $postType = $data['type'] ?? 'post';

        // Auto generate unique slug with sequential suffix (e.g. old_1, old_2) if duplicate in DB
        $data['slug'] = $this->postRepo->generateUniqueSlug($rawSlug, $postId, $postType);

        // Auto calculate reading time and word count from content
        if (!empty($data['content'])) {
            $plain = trim(strip_tags((string)$data['content']));
            $wordCount = $plain === '' ? 0 : str_word_count($plain);
            $readingTime = max(1, (int)ceil($wordCount / 200));

            if (!isset($data['meta']) || !is_array($data['meta'])) {
                $data['meta'] = [];
            }
            $data['meta']['reading_time'] = $readingTime;
            $data['meta']['word_count'] = $wordCount;
        }

        $currentUser = $this->auth->getCurrentUser($request);
        if ($currentUser && empty($data['author_id'])) {
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

    private function getCategoriesList(): array
    {
        $categories = $this->optionRepo->get('post_categories', [
            'Technology',
            'E-Commerce',
            'Guides',
            'Updates',
            'Announcements',
            'Business',
        ]);

        if (!is_array($categories)) {
            $categories = ['Technology', 'E-Commerce', 'Guides', 'Updates', 'Announcements', 'Business'];
        }

        return array_values(array_unique(array_filter(array_map('trim', $categories))));
    }
}
