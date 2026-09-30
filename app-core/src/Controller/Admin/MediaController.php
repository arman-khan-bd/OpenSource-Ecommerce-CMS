<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\MediaRepository;
use App\Repository\OptionRepository;
use App\Service\AuthService;
use App\Service\CacheFallbackService;
use App\Service\InertiaService;
use App\Service\MediaService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MediaController extends BaseAdminController
{
    private MediaRepository $mediaRepo;
    private MediaService $mediaService;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        MediaRepository $mediaRepo,
        MediaService $mediaService
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->mediaRepo = $mediaRepo;
        $this->mediaService = $mediaService;
    }

    #[Route('/admin/media', name: 'admin_media', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $type = $request->query->get('type', 'all');
        $search = $request->query->get('search', '');

        $mediaData = $this->mediaRepo->paginate($page, 24, $type, $search);

        return $this->renderAdmin($request, 'Admin/Media/Index', [
            'media' => $mediaData,
            'filters' => [
                'type' => $type,
                'search' => $search,
            ],
        ]);
    }

    #[Route('/admin/media/upload', name: 'admin_media_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['success' => false, 'message' => 'No file uploaded'], 400);
        }

        try {
            $altText = $request->request->get('alt_text');
            $media = $this->mediaService->upload($file, $altText);

            return new JsonResponse([
                'success' => true,
                'media' => $media->toArray(),
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/admin/media/{id}/update', name: 'admin_media_update', methods: ['POST'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $media = $this->mediaRepo->findById($id);
        if (!$media) {
            return new JsonResponse(['success' => false, 'message' => 'Media not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        if (isset($data['alt_text'])) {
            $media->setAltText($data['alt_text']);
        }
        $this->mediaRepo->save($media);

        return new JsonResponse(['success' => true, 'media' => $media->toArray()]);
    }

    #[Route('/admin/media/{id}/delete', name: 'admin_media_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, int $id): Response
    {
        $this->mediaRepo->delete($id);
        $request->getSession()->getFlashBag()->add('success', 'Media file permanently deleted.');
        return new RedirectResponse('/admin/media');
    }
}
