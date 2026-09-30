<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use App\Service\DatabaseService;

class OrderRepository
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?Order
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return null;
        }

        $table = $this->db->tableName('orders');
        $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE id = ?", [$id]);
        return $row ? Order::fromArray($row) : null;
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return null;
        }

        $table = $this->db->tableName('orders');
        $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE order_number = ?", [$orderNumber]);
        return $row ? Order::fromArray($row) : null;
    }

    public function paginate(int $page = 1, int $perPage = 10, ?string $status = null, ?string $search = null): array
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage, 'pages' => 1];
        }

        $table = $this->db->tableName('orders');
        $where = [];
        $params = [];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $where[] = "status = ?";
            $params[] = $status;
        }

        if ($search !== null && $search !== '') {
            $where[] = "(order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
        $total = (int)$conn->fetchOne("SELECT COUNT(*) FROM {$table} {$whereClause}", $params);

        $offset = ($page - 1) * $perPage;
        $order = "ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $rows = $conn->fetchAllAssociative("SELECT * FROM {$table} {$whereClause} {$order}", $params);

        return [
            'items' => array_map(fn($r) => Order::fromArray($r)->toArray(), $rows),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => ceil($total / max(1, $perPage)),
        ];
    }

    public function save(Order $order): Order
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            throw new \RuntimeException('Database connection not available.');
        }

        $table = $this->db->tableName('orders');

        if ($order->getId() !== null) {
            $conn->executeStatement(
                "UPDATE {$table} SET 
                    status = ?, 
                    notes = ?, 
                    updated_at = ? 
                WHERE id = ?",
                [
                    $order->getStatus(),
                    $order->getNotes(),
                    date('Y-m-d H:i:s'),
                    $order->getId(),
                ]
            );
            return $order;
        }

        $conn->executeStatement(
            "INSERT INTO {$table} (order_number, customer_name, customer_email, total_amount, status, items, shipping_address, notes, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $order->getOrderNumber(),
                $order->getCustomerName(),
                $order->getCustomerEmail(),
                $order->getTotalAmount(),
                $order->getStatus(),
                json_encode($order->getItems()),
                json_encode($order->getShippingAddress()),
                $order->getNotes(),
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
            ]
        );

        $id = (int)$conn->lastInsertId();
        return $this->findById($id);
    }

    public function getStats(): array
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return ['total_sales' => 0, 'total_orders' => 0, 'pending' => 0, 'completed' => 0];
        }

        $table = $this->db->tableName('orders');
        try {
            $totalSales = (float)$conn->fetchOne("SELECT SUM(total_amount) FROM {$table} WHERE status != 'cancelled'");
            $totalOrders = (int)$conn->fetchOne("SELECT COUNT(*) FROM {$table}");
            $pending = (int)$conn->fetchOne("SELECT COUNT(*) FROM {$table} WHERE status = 'pending'");
            $completed = (int)$conn->fetchOne("SELECT COUNT(*) FROM {$table} WHERE status = 'completed'");

            return [
                'total_sales' => round($totalSales, 2),
                'total_orders' => $totalOrders,
                'pending' => $pending,
                'completed' => $completed,
            ];
        } catch (\Throwable $e) {
            return ['total_sales' => 0, 'total_orders' => 0, 'pending' => 0, 'completed' => 0];
        }
    }
}
