<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Post;
use App\Service\DatabaseService;

class PostRepository
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?Post
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return null;
        }

        $table = $this->db->tableName('posts');
        $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE id = ?", [$id]);
        return $row ? Post::fromArray($row) : null;
    }

    public function findBySlug(string $slug, string $type = 'post'): ?Post
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return null;
        }

        $table = $this->db->tableName('posts');
        $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE slug = ? AND type = ?", [$slug, $type]);
        return $row ? Post::fromArray($row) : null;
    }

    public function paginate(string $type = 'post', int $page = 1, int $perPage = 10, ?string $status = null, ?string $search = null): array
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage, 'pages' => 1];
        }

        $table = $this->db->tableName('posts');
        $where = ["type = ?"];
        $params = [$type];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $where[] = "status = ?";
            $params[] = $status;
        }

        if ($search !== null && $search !== '') {
            $where[] = "(title LIKE ? OR content LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = implode(' AND ', $where);
        $total = (int)$conn->fetchOne("SELECT COUNT(*) FROM {$table} WHERE {$whereClause}", $params);

        $offset = ($page - 1) * $perPage;
        $order = "ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $rows = $conn->fetchAllAssociative("SELECT * FROM {$table} WHERE {$whereClause} {$order}", $params);

        return [
            'items' => array_map(fn($r) => Post::fromArray($r)->toArray(), $rows),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => ceil($total / max(1, $perPage)),
        ];
    }

    public function save(Post $post): Post
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            throw new \RuntimeException('Database connection not available.');
        }

        $table = $this->db->tableName('posts');

        if ($post->getId() !== null) {
            $conn->executeStatement(
                "UPDATE {$table} SET 
                    title = ?, 
                    slug = ?, 
                    content = ?, 
                    excerpt = ?, 
                    type = ?, 
                    status = ?, 
                    author_id = ?, 
                    featured_image = ?, 
                    meta = ?, 
                    published_at = ?, 
                    updated_at = ? 
                WHERE id = ?",
                [
                    $post->getTitle(),
                    $post->getSlug(),
                    $post->getContent(),
                    $post->getExcerpt(),
                    $post->getType(),
                    $post->getStatus(),
                    $post->getAuthorId(),
                    $post->getFeaturedImage(),
                    json_encode($post->getMeta()),
                    $post->getPublishedAt(),
                    date('Y-m-d H:i:s'),
                    $post->getId(),
                ]
            );
            return $post;
        }

        $conn->executeStatement(
            "INSERT INTO {$table} (title, slug, content, excerpt, type, status, author_id, featured_image, meta, published_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $post->getTitle(),
                $post->getSlug(),
                $post->getContent(),
                $post->getExcerpt(),
                $post->getType(),
                $post->getStatus(),
                $post->getAuthorId(),
                $post->getFeaturedImage(),
                json_encode($post->getMeta()),
                $post->getPublishedAt() ?? ($post->getStatus() === 'published' ? date('Y-m-d H:i:s') : null),
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

        $table = $this->db->tableName('posts');
        $affected = $conn->executeStatement("DELETE FROM {$table} WHERE id = ?", [$id]);
        return $affected > 0;
    }
}
