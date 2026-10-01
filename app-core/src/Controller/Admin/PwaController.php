<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\OptionRepository;
use App\Service\AuthService;
use App\Service\CacheFallbackService;
use App\Service\InertiaService;
use App\Service\PwaService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PwaController extends BaseAdminController
{
    private PwaService $pwaService;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        PwaService $pwaService
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->pwaService = $pwaService;
    }

    #[Route('/admin/pwa', name: 'admin_pwa', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $manifest = $this->pwaService->getManifest();
        $vapid = $this->pwaService->getVapidKeys();
        $subscriptions = $this->pwaService->getSubscriptions();

        return $this->renderAdmin($request, 'Admin/Pwa/Studio', [
            'manifest' => $manifest,
            'vapid' => [
                'publicKey' => $vapid['publicKey'],
                'subject' => $vapid['subject'],
            ],
            'subscribersCount' => count($subscriptions),
            'latestAlert' => $this->optionRepo->get('pwa_latest_notification', null),
        ]);
    }

    #[Route('/admin/pwa/save', name: 'admin_pwa_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();

        if (isset($data['manifest']) && is_array($data['manifest'])) {
            $this->pwaService->saveManifest($data['manifest']);
        }

        if (isset($data['vapid_subject'])) {
            $this->optionRepo->set('vapid_subject', trim((string)$data['vapid_subject']));
        }

        $request->getSession()->getFlashBag()->add('success', 'PWA Manifest and Settings updated successfully!');

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/pwa');
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/admin/pwa/vapid/generate', name: 'admin_pwa_vapid_generate', methods: ['POST'])]
    public function generateVapid(Request $request): JsonResponse
    {
        $keys = $this->pwaService->generateVapidKeys();
        return new JsonResponse([
            'success' => true,
            'publicKey' => $keys['publicKey'],
            'subject' => $keys['subject'],
            'message' => 'New RFC 8292 compliant VAPID keypair successfully generated.',
        ]);
    }

    #[Route('/admin/pwa/icons/generate', name: 'admin_pwa_icons_generate', methods: ['POST'])]
    public function generateIcons(Request $request): JsonResponse
    {
        $this->pwaService->generateDefaultIcons();
        return new JsonResponse([
            'success' => true,
            'message' => 'PWA icons (icon-192.png, icon-512.png, badge-72.png) regenerated successfully in public_html/.',
        ]);
    }

    #[Route('/api/v1/pwa/subscribe', name: 'api_pwa_subscribe', methods: ['POST'])]
    public function subscribe(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        if (empty($data['endpoint'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid push subscription payload.'], 400);
        }

        $this->pwaService->savePushSubscription($data);

        return new JsonResponse([
            'success' => true,
            'message' => 'Device subscribed to Web Push notifications successfully.',
        ]);
    }

    #[Route('/api/v1/pwa/broadcast', name: 'api_pwa_broadcast', methods: ['POST'])]
    public function broadcast(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $title = trim((string)($data['title'] ?? 'Store Alert'));
        $body = trim((string)($data['body'] ?? 'A new notification from OpenSource eCommerce CMS'));
        $url = trim((string)($data['url'] ?? '/admin'));
        $icon = !empty($data['icon']) ? (string)$data['icon'] : '/icon-192.png';
        $badge = !empty($data['badge']) ? (string)$data['badge'] : '/badge-72.png';

        $result = $this->pwaService->broadcastNotification($title, $body, $url, $icon, $badge);

        return new JsonResponse($result);
    }

    #[Route('/api/v1/pwa/vapid-public-key', name: 'api_pwa_vapid_key', methods: ['GET'])]
    public function vapidPublicKey(): JsonResponse
    {
        $vapid = $this->pwaService->getVapidKeys();
        return new JsonResponse([
            'publicKey' => $vapid['publicKey'],
        ]);
    }

    #[Route('/api/v1/pwa/latest-alert', name: 'api_pwa_latest_alert', methods: ['GET'])]
    public function latestAlert(): JsonResponse
    {
        $alert = $this->optionRepo->get('pwa_latest_notification', [
            'title' => 'OpenSource eCommerce CMS',
            'body' => 'Welcome to your progressive web application.',
            'url' => '/admin',
        ]);

        return new JsonResponse($alert);
    }

    #[Route('/manifest.json', name: 'pwa_manifest_json', methods: ['GET'])]
    public function manifest(): JsonResponse
    {
        $manifest = $this->pwaService->getManifest();
        $response = new JsonResponse($manifest);
        $response->headers->set('Content-Type', 'application/manifest+json');
        return $response;
    }
}
