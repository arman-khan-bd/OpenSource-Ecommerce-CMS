<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use App\Service\DatabaseService;

class ProductRepository
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?Product
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return null;
        }

        $table = $this->db->tableName('products');
        $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE id = ?", [$id]);
        return $row ? Product::fromArray($row) : null;
    }

    public function findBySlug(string $slug): ?Product
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return null;
        }

        $table = $this->db->tableName('products');
        $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE slug = ?", [$slug]);
        return $row ? Product::fromArray($row) : null;
    }

    public function paginate(int $page = 1, int $perPage = 10, ?string $status = null, ?string $search = null): array
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage, 'pages' => 1];
        }

        $table = $this->db->tableName('products');
        $where = [];
        $params = [];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $where[] = "status = ?";
            $params[] = $status;
        }

        if ($search !== null && $search !== '') {
            $where[] = "(title LIKE ? OR sku LIKE ? OR description LIKE ?)";
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
            'items' => array_map(fn($r) => Product::fromArray($r)->toArray(), $rows),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => ceil($total / max(1, $perPage)),
        ];
    }

    public function save(Product $product): Product
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            throw new \RuntimeException('Database connection not available.');
        }

        $table = $this->db->tableName('products');

        if ($product->getId() !== null) {
            $conn->executeStatement(
                "UPDATE {$table} SET 
                    title = ?, 
                    slug = ?, 
                    description = ?, 
                    price = ?, 
                    sale_price = ?, 
                    sku = ?, 
                    stock = ?, 
                    status = ?, 
                    images = ?, 
                    meta = ?, 
                    updated_at = ? 
                WHERE id = ?",
                [
                    $product->getTitle(),
                    $product->getSlug(),
                    $product->getDescription(),
                    $product->getPrice(),
                    $product->getSalePrice(),
                    $product->getSku(),
                    $product->getStock(),
                    $product->getStatus(),
                    json_encode($product->getImages()),
                    json_encode($product->getMeta()),
                    date('Y-m-d H:i:s'),
                    $product->getId(),
                ]
            );
            return $product;
        }

        $conn->executeStatement(
            "INSERT INTO {$table} (title, slug, description, price, sale_price, sku, stock, status, images, meta, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $product->getTitle(),
                $product->getSlug(),
                $product->getDescription(),
                $product->getPrice(),
                $product->getSalePrice(),
                $product->getSku(),
                $product->getStock(),
                $product->getStatus(),
                json_encode($product->getImages()),
                json_encode($product->getMeta()),
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
            ]
        );

        $id = (int)$conn->lastInsertId();
        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return false;
        }

        $table = $this->db->tableName('products');
        $affected = $conn->executeStatement("DELETE FROM {$table} WHERE id = ?", [$id]);
        return $affected > 0;
    }

    /**
     * Generates a collision-free slug for products.
     * If the slug exists in DB, appends a sequential number (e.g. product_1, product_2).
     */
    public function generateUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return $slug;
        }

        $table = $this->db->tableName('products');

        // Clean and normalize slug
        $cleanSlug = preg_replace('/[^a-z0-9_\-]+/i', '-', strtolower($slug));
        $cleanSlug = trim($cleanSlug, '-_');
        if ($cleanSlug === '') {
            $cleanSlug = 'product';
        }

        // Check if exact cleanSlug is already free
        $params = [$cleanSlug];
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $existing = $conn->fetchOne($sql, $params);
        if (!$existing) {
            return $cleanSlug;
        }

        // Slug is taken. Extract base name without existing _<number> suffix if present
        $baseSlug = $cleanSlug;
        if (preg_match('/^(.*?)_([0-9]+)$/', $cleanSlug, $matches)) {
            $baseSlug = $matches[1];
        }

        // Find all matching slugs starting with baseSlug
        $likeParams = [$baseSlug, $baseSlug . '_%'];
        $likeSql = "SELECT slug FROM {$table} WHERE (slug = ? OR slug LIKE ?)";
        if ($excludeId !== null) {
            $likeSql .= " AND id != ?";
            $likeParams[] = $excludeId;
        }

        $takenSlugs = $conn->fetchFirstColumn($likeSql, $likeParams);
        $takenMap = array_flip($takenSlugs);

        $counter = 1;
        while (isset($takenMap["{$baseSlug}_{$counter}"])) {
            $counter++;
        }

        return "{$baseSlug}_{$counter}";
    }
}
