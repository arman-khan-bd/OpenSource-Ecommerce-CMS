<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Plugin;
use App\Service\DatabaseService;

class PluginRepository
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function findBySlug(string $slug): ?Plugin
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return null;
        }

        $table = $this->db->tableName('plugins');
        $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE slug = ?", [$slug]);
        return $row ? Plugin::fromArray($row) : null;
    }

    public function getActivePlugins(): array
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return [];
        }

        try {
            $table = $this->db->tableName('plugins');
            $rows = $conn->fetchAllAssociative("SELECT * FROM {$table} WHERE is_active = 1");
            return array_map(fn($r) => Plugin::fromArray($r), $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function all(): array
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return [];
        }

        try {
            $table = $this->db->tableName('plugins');
            $rows = $conn->fetchAllAssociative("SELECT * FROM {$table} ORDER BY name ASC");
            return array_map(fn($r) => Plugin::fromArray($r), $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function toggleActive(string $slug): bool
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return false;
        }

        $table = $this->db->tableName('plugins');
        $plugin = $this->findBySlug($slug);
        if (!$plugin) {
            return false;
        }

        $newStatus = $plugin->isActive() ? 0 : 1;
        $conn->executeStatement("UPDATE {$table} SET is_active = ?, updated_at = ? WHERE slug = ?", [
            $newStatus,
            date('Y-m-d H:i:s'),
            $slug,
        ]);
        return (bool)$newStatus;
    }

    public function save(Plugin $plugin): Plugin
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            throw new \RuntimeException('Database connection not available.');
        }

        $table = $this->db->tableName('plugins');
        $existing = $this->findBySlug($plugin->getSlug());

        if ($existing) {
            $conn->executeStatement(
                "UPDATE {$table} SET 
                    name = ?, 
                    version = ?, 
                    author = ?, 
                    description = ?, 
                    is_active = ?, 
                    settings = ?, 
                    updated_at = ? 
                WHERE slug = ?",
                [
                    $plugin->getName(),
                    $plugin->getVersion(),
                    $plugin->getAuthor(),
                    $plugin->getDescription(),
                    $plugin->isActive() ? 1 : 0,
                    json_encode($plugin->getSettings()),
                    date('Y-m-d H:i:s'),
                    $plugin->getSlug(),
                ]
            );
            return $this->findBySlug($plugin->getSlug());
        }

        $conn->executeStatement(
            "INSERT INTO {$table} (name, slug, version, author, description, is_active, settings, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $plugin->getName(),
                $plugin->getSlug(),
                $plugin->getVersion(),
                $plugin->getAuthor(),
                $plugin->getDescription(),
                $plugin->isActive() ? 1 : 0,
                json_encode($plugin->getSettings()),
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
            ]
        );

        return $this->findBySlug($plugin->getSlug());
    }
}
