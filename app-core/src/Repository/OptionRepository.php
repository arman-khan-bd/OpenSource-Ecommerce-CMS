<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Option;
use App\Service\DatabaseService;

class OptionRepository
{
    private DatabaseService $db;
    private array $cache = [];

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        if (array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        $conn = $this->db->getConnection();
        if (!$conn) {
            return $default;
        }

        try {
            $table = $this->db->tableName('options');
            $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE option_name = ?", [$name]);
            if ($row) {
                $option = Option::fromArray($row);
                $this->cache[$name] = $option->getOptionValue();
                return $this->cache[$name];
            }
        } catch (\Throwable $e) {
            // Table might not exist yet
        }

        return $default;
    }

    public function set(string $name, mixed $value, bool $autoload = true): void
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return;
        }

        $table = $this->db->tableName('options');
        $valToStore = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;

        $existing = $conn->fetchOne("SELECT id FROM {$table} WHERE option_name = ?", [$name]);
        if ($existing) {
            $conn->executeStatement(
                "UPDATE {$table} SET option_value = ?, autoload = ? WHERE option_name = ?",
                [$valToStore, $autoload ? 1 : 0, $name]
            );
        } else {
            $conn->executeStatement(
                "INSERT INTO {$table} (option_name, option_value, autoload) VALUES (?, ?, ?)",
                [$name, $valToStore, $autoload ? 1 : 0]
            );
        }

        $this->cache[$name] = $value;
    }

    public function delete(string $name): void
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return;
        }

        $table = $this->db->tableName('options');
        $conn->executeStatement("DELETE FROM {$table} WHERE option_name = ?", [$name]);
        unset($this->cache[$name]);
    }

    public function getAll(): array
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return [];
        }

        try {
            $table = $this->db->tableName('options');
            $rows = $conn->fetchAllAssociative("SELECT * FROM {$table}");
            $result = [];
            foreach ($rows as $row) {
                $opt = Option::fromArray($row);
                $result[$opt->getOptionName()] = $opt->getOptionValue();
                $this->cache[$opt->getOptionName()] = $opt->getOptionValue();
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
