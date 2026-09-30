<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Service\DatabaseService;

class UserRepository
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?User
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return null;
        }

        $table = $this->db->tableName('users');
        $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE id = ?", [$id]);
        return $row ? User::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return null;
        }

        $table = $this->db->tableName('users');
        $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE email = ?", [strtolower(trim($email))]);
        return $row ? User::fromArray($row) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return null;
        }

        $table = $this->db->tableName('users');
        $row = $conn->fetchAssociative("SELECT * FROM {$table} WHERE username = ?", [trim($username)]);
        return $row ? User::fromArray($row) : null;
    }

    public function all(): array
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            return [];
        }

        $table = $this->db->tableName('users');
        $rows = $conn->fetchAllAssociative("SELECT * FROM {$table} ORDER BY id ASC");
        return array_map(fn($row) => User::fromArray($row), $rows);
    }

    public function save(User $user): User
    {
        $conn = $this->db->getConnection();
        if (!$conn) {
            throw new \RuntimeException('Database connection not available.');
        }

        $table = $this->db->tableName('users');
        $data = $user->toArray(true);

        if ($user->getId() !== null) {
            $conn->executeStatement(
                "UPDATE {$table} SET 
                    username = ?, 
                    email = ?, 
                    password_hash = ?, 
                    role = ?, 
                    full_name = ?, 
                    avatar = ?, 
                    status = ?, 
                    meta = ?, 
                    updated_at = ? 
                WHERE id = ?",
                [
                    $user->getUsername(),
                    strtolower(trim($user->getEmail())),
                    $user->getPasswordHash(),
                    $user->getRole(),
                    $user->getFullName(),
                    $user->getAvatar(),
                    $user->getStatus(),
                    json_encode($user->getMeta()),
                    date('Y-m-d H:i:s'),
                    $user->getId(),
                ]
            );
            return $user;
        }

        $conn->executeStatement(
            "INSERT INTO {$table} (username, email, password_hash, role, full_name, avatar, status, meta, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $user->getUsername(),
                strtolower(trim($user->getEmail())),
                $user->getPasswordHash(),
                $user->getRole(),
                $user->getFullName(),
                $user->getAvatar(),
                $user->getStatus(),
                json_encode($user->getMeta()),
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

        $table = $this->db->tableName('users');
        $affected = $conn->executeStatement("DELETE FROM {$table} WHERE id = ?", [$id]);
        return $affected > 0;
    }
}
