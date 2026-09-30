<?php

declare(strict_types=1);

namespace App\Entity;

class User
{
    private ?int $id = null;
    private string $username = '';
    private string $email = '';
    private string $passwordHash = '';
    private string $role = 'editor'; // admin, editor, author, customer
    private ?string $fullName = null;
    private ?string $avatar = null;
    private string $status = 'active'; // active, suspended
    private array $meta = [];
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public static function fromArray(array $data): self
    {
        $user = new self();
        $user->id = isset($data['id']) ? (int)$data['id'] : null;
        $user->username = $data['username'] ?? '';
        $user->email = $data['email'] ?? '';
        $user->passwordHash = $data['password_hash'] ?? '';
        $user->role = $data['role'] ?? 'editor';
        $user->fullName = $data['full_name'] ?? null;
        $user->avatar = $data['avatar'] ?? null;
        $user->status = $data['status'] ?? 'active';
        $user->meta = is_string($data['meta'] ?? null) ? (json_decode($data['meta'], true) ?: []) : ($data['meta'] ?? []);
        $user->createdAt = $data['created_at'] ?? null;
        $user->updatedAt = $data['updated_at'] ?? null;
        return $user;
    }

    public function toArray(bool $includeHash = false): array
    {
        $data = [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'full_name' => $this->fullName,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'meta' => $this->meta,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
        if ($includeHash) {
            $data['password_hash'] = $this->passwordHash;
        }
        return $data;
    }

    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function setUsername(string $val): self { $this->username = $val; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $val): self { $this->email = $val; return $this; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function setPasswordHash(string $val): self { $this->passwordHash = $val; return $this; }
    public function getRole(): string { return $this->role; }
    public function setRole(string $val): self { $this->role = $val; return $this; }
    public function getFullName(): ?string { return $this->fullName; }
    public function setFullName(?string $val): self { $this->fullName = $val; return $this; }
    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $val): self { $this->avatar = $val; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $val): self { $this->status = $val; return $this; }
    public function getMeta(): array { return $this->meta; }
    public function setMeta(array $val): self { $this->meta = $val; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
}
