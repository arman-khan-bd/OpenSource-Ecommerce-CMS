<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\OptionRepository;
use App\Repository\OrderRepository;
use App\Service\AuthService;
use App\Service\CacheFallbackService;
use App\Service\HookManager;
use App\Service\InertiaService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends BaseAdminController
{
    private OrderRepository $orderRepo;
    private HookManager $hookManager;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService,
        OrderRepository $orderRepo,
        HookManager $hookManager
    ) {
        parent::__construct($auth, $inertia, $optionRepo, $cacheService);
        $this->orderRepo = $orderRepo;
        $this->hookManager = $hookManager;
    }

    #[Route('/admin/orders', name: 'admin_orders', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $status = $request->query->get('status', 'all');
        $search = $request->query->get('search', '');

        $ordersData = $this->orderRepo->paginate($page, 10, $status, $search);
        $stats = $this->orderRepo->getStats();

        return $this->renderAdmin($request, 'Admin/Orders/Index', [
            'orders' => $ordersData,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    #[Route('/admin/orders/{id}', name: 'admin_orders_show', methods: ['GET'])]
    public function show(Request $request, int $id): Response
    {
        $order = $this->orderRepo->findById($id);
        if (!$order) {
            $request->getSession()->getFlashBag()->add('error', 'Order not found.');
            return new RedirectResponse('/admin/orders');
        }

        return $this->renderAdmin($request, 'Admin/Orders/Show', [
            'order' => $order->toArray(),
        ]);
    }

    #[Route('/admin/orders/{id}/status', name: 'admin_orders_status', methods: ['POST'])]
    public function updateStatus(Request $request, int $id): Response
    {
        $order = $this->orderRepo->findById($id);
        if (!$order) {
            return new JsonResponse(['success' => false, 'message' => 'Order not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $oldStatus = $order->getStatus();
        $newStatus = $data['status'] ?? $oldStatus;

        $order->setStatus($newStatus);
        if (isset($data['notes'])) {
            $order->setNotes($data['notes']);
        }

        $this->orderRepo->save($order);

        // Fire order status change hook
        $this->hookManager->doAction('order_status_changed', $id, $newStatus, $oldStatus);

        $request->getSession()->getFlashBag()->add('success', "Order status updated to {$newStatus}.");

        if ($request->headers->get('X-Inertia') === 'true') {
            return new RedirectResponse('/admin/orders/' . $id);
        }

        return new JsonResponse(['success' => true, 'order' => $order->toArray()]);
    }
}
