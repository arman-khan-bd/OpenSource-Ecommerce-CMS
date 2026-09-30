<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SduiScreen;
use App\Service\DatabaseService;

class SduiScreenRepository
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?SduiScreen
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return null;
        }

        $table = $this->db->tableName('sdui_screens');
        $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE id = ?", [$id]);
        return $row ? SduiScreen::fromArray($row) : null;
    }

    public function findBySlug(string $slug): ?SduiScreen
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return null;
        }

        $table = $this->db->tableName('sdui_screens');
        $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE slug = ?", [$slug]);
        return $row ? SduiScreen::fromArray($row) : null;
    }

    public function all(): array
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return [];
        }

        try {
            $table = $this->db->tableName('sdui_screens');
            $rows = $conn->fetchAllAssociative("SELECT * FROM {$table} ORDER BY id ASC");
            return array_map(fn($r) => SduiScreen::fromArray($r), $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function save(SduiScreen $screen): SduiScreen
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            throw new \RuntimeException('Database connection not available.');
        }

        $table = $this->db->tableName('sdui_screens');
        $existing = $this->findBySlug($screen->getSlug());

        if ($existing || $screen->getId() !== null) {
            $targetId = $screen->getId() ?? $existing->getId();
            $conn->executeStatement(
                "UPDATE {$table} SET 
                    title = ?, 
                    slug = ?, 
                    description = ?, 
                    layout = ?, 
                    is_active = ?, 
                    updated_at = ? 
                WHERE id = ?",
                [
                    $screen->getTitle(),
                    $screen->getSlug(),
                    $screen->getDescription(),
                    json_encode($screen->getLayout()),
                    $screen->isActive() ? 1 : 0,
                    date('Y-m-d H:i:s'),
                    $targetId,
                ]
            );
            return $this->findById($targetId);
        }

        $conn->executeStatement(
            "INSERT INTO {$table} (title, slug, description, layout, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $screen->getTitle(),
                $screen->getSlug(),
                $screen->getDescription(),
                json_encode($screen->getLayout()),
                $screen->isActive() ? 1 : 0,
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

        $table = $this->db->tableName('sdui_screens');
        $affected = $conn->executeStatement("DELETE FROM {$table} WHERE id = ?", [$id]);
        return $affected > 0;
    }
}
