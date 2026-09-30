<?php

declare(strict_types=1);

namespace App\Entity;

class SduiScreen
{
    private ?int $id = null;
    private string $title = '';
    private string $slug = '';
    private ?string $description = null;
    private array $layout = [];
    private bool $isActive = true;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public static function fromArray(array $data): self
    {
        $screen = new self();
        $screen->id = isset($data['id']) ? (int)$data['id'] : null;
        $screen->title = $data['title'] ?? '';
        $screen->slug = $data['slug'] ?? '';
        $screen->description = $data['description'] ?? null;
        $screen->layout = is_string($data['layout'] ?? null) ? (json_decode($data['layout'], true) ?: []) : ($data['layout'] ?? []);
        $screen->isActive = (bool)($data['is_active'] ?? true);
        $screen->createdAt = $data['created_at'] ?? null;
        $screen->updatedAt = $data['updated_at'] ?? null;
        return $screen;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'layout' => $this->layout,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $val): self { $this->title = $val; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $val): self { $this->slug = $val; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $val): self { $this->description = $val; return $this; }
    public function getLayout(): array { return $this->layout; }
    public function setLayout(array $val): self { $this->layout = $val; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $val): self { $this->isActive = $val; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
}
