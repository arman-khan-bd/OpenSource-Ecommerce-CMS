<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Media;
use App\Service\DatabaseService;

class MediaRepository
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?Media
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return null;
        }

        $table = $this->db->tableName('media');
        $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE id = ?", [$id]);
        return $row ? Media::fromArray($row) : null;
    }

    public function paginate(int $page = 1, int $perPage = 24, ?string $mimeType = null, ?string $search = null): array
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage, 'pages' => 1];
        }

        $table = $this->db->tableName('media');
        $where = [];
        $params = [];

        if ($mimeType !== null && $mimeType !== '' && $mimeType !== 'all') {
            $where[] = "mime_type LIKE ?";
            $params[] = "{$mimeType}%";
        }

        if ($search !== null && $search !== '') {
            $where[] = "(original_name LIKE ? OR alt_text LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
        $total = (int)$conn->fetchOne("SELECT COUNT(*) FROM {$table} {$whereClause}", $params);

        $offset = ($page - 1) * $perPage;
        $order = "ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $rows = $conn->fetchAllAssociative("SELECT * FROM {$table} {$whereClause} {$order}", $params);

        return [
            'items' => array_map(fn($r) => Media::fromArray($r)->toArray(), $rows),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => ceil($total / max(1, $perPage)),
        ];
    }

    public function save(Media $media): Media
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            throw new \RuntimeException('Database connection not available.');
        }

        $table = $this->db->tableName('media');

        if ($media->getId() !== null) {
            $conn->executeStatement(
                "UPDATE {$table} SET alt_text = ?, meta = ? WHERE id = ?",
                [
                    $media->getAltText(),
                    json_encode($media->getMeta()),
                    $media->getId(),
                ]
            );
            return $media;
        }

        $conn->executeStatement(
            "INSERT INTO {$table} (filename, original_name, mime_type, file_size, width, height, path, url, alt_text, meta, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $media->getFilename(),
                $media->getOriginalName(),
                $media->getMimeType(),
                $media->getFileSize(),
                $media->getWidth(),
                $media->getHeight(),
                $media->getPath(),
                $media->getUrl(),
                $media->getAltText(),
                json_encode($media->getMeta()),
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

        $media = $this->findById($id);
        if ($media && file_exists($media->getPath())) {
            @unlink($media->getPath());
        }

        $table = $this->db->tableName('media');
        $affected = $conn->executeStatement("DELETE FROM {$table} WHERE id = ?", [$id]);
        return $affected > 0;
    }
}
