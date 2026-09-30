<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\SduiScreenRepository;
use App\Service\SDUIParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * REST API delivering native Server-Driven UI models to Android / mobile clients.
 */
class SduiApiController extends AbstractController
{
    private SduiScreenRepository $sduiRepo;
    private SDUIParser $parser;

    public function __construct(SduiScreenRepository $sduiRepo, SDUIParser $parser)
    {
        $this->sduiRepo = $sduiRepo;
        $this->parser = $parser;
    }

    #[Route('/api/v1/sdui/screens', name: 'api_sdui_screens', methods: ['GET'])]
    public function getScreens(): JsonResponse
    {
        $screens = $this->sduiRepo->all();
        $list = array_map(function($s) {
            return [
                'id' => $s->getId(),
                'title' => $s->getTitle(),
                'slug' => $s->getSlug(),
                'description' => $s->getDescription(),
                'is_active' => $s->isActive(),
                'endpoint' => '/api/v1/sdui/screen/' . $s->getSlug(),
            ];
        }, $screens);

        return new JsonResponse(['screens' => $list]);
    }

    #[Route('/api/v1/sdui/screen/{slug}', name: 'api_sdui_screen', methods: ['GET'])]
    public function getScreen(string $slug): JsonResponse
    {
        $screen = $this->sduiRepo->findBySlug($slug);
        if (!$screen) {
            return new JsonResponse([
                'error' => 'Screen not found',
                'slug' => $slug,
            ], Response::HTTP_NOT_FOUND);
        }

        $screenSpec = $this->parser->parseScreen($screen->toArray());

        $response = new JsonResponse($screenSpec);
        $response->headers->set('Cache-Control', 'public, max-age=60, s-maxage=120');
        $response->headers->set('X-SDUI-Engine', 'Apex-Android-SDUI-1.0');
        return $response;
    }
}
