<?php

declare(strict_types=1);

namespace App\Entity;

class Plugin
{
    private ?int $id = null;
    private string $name = '';
    private string $slug = '';
    private string $version = '1.0.0';
    private ?string $author = null;
    private ?string $description = null;
    private bool $isActive = false;
    private array $settings = [];
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public static function fromArray(array $data): self
    {
        $plugin = new self();
        $plugin->id = isset($data['id']) ? (int)$data['id'] : null;
        $plugin->name = $data['name'] ?? '';
        $plugin->slug = $data['slug'] ?? '';
        $plugin->version = $data['version'] ?? '1.0.0';
        $plugin->author = $data['author'] ?? null;
        $plugin->description = $data['description'] ?? null;
        $plugin->isActive = (bool)($data['is_active'] ?? false);
        $plugin->settings = is_string($data['settings'] ?? null) ? (json_decode($data['settings'], true) ?: []) : ($data['settings'] ?? []);
        $plugin->createdAt = $data['created_at'] ?? null;
        $plugin->updatedAt = $data['updated_at'] ?? null;
        return $plugin;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'version' => $this->version,
            'author' => $this->author,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'settings' => $this->settings,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $val): self { $this->name = $val; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $val): self { $this->slug = $val; return $this; }
    public function getVersion(): string { return $this->version; }
    public function setVersion(string $val): self { $this->version = $val; return $this; }
    public function getAuthor(): ?string { return $this->author; }
    public function setAuthor(?string $val): self { $this->author = $val; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $val): self { $this->description = $val; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $val): self { $this->isActive = $val; return $this; }
    public function getSettings(): array { return $this->settings; }
    public function setSettings(array $val): self { $this->settings = $val; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
}
