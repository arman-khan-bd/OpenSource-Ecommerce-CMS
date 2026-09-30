<?php

declare(strict_types=1);

namespace App\Entity;

class Product
{
    private ?int $id = null;
    private string $title = '';
    private string $slug = '';
    private ?string $description = null;
    private float $price = 0.0;
    private ?float $salePrice = null;
    private ?string $sku = null;
    private int $stock = 0;
    private string $status = 'active'; // active, draft
    private array $images = [];
    private array $meta = [];
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public static function fromArray(array $data): self
    {
        $prod = new self();
        $prod->id = isset($data['id']) ? (int)$data['id'] : null;
        $prod->title = $data['title'] ?? '';
        $prod->slug = $data['slug'] ?? '';
        $prod->description = $data['description'] ?? null;
        $prod->price = (float)($data['price'] ?? 0.0);
        $prod->salePrice = isset($data['sale_price']) && $data['sale_price'] !== '' ? (float)$data['sale_price'] : null;
        $prod->sku = $data['sku'] ?? null;
        $prod->stock = (int)($data['stock'] ?? 0);
        $prod->status = $data['status'] ?? 'active';
        $prod->images = is_string($data['images'] ?? null) ? (json_decode($data['images'], true) ?: []) : ($data['images'] ?? []);
        $prod->meta = is_string($data['meta'] ?? null) ? (json_decode($data['meta'], true) ?: []) : ($data['meta'] ?? []);
        $prod->createdAt = $data['created_at'] ?? null;
        $prod->updatedAt = $data['updated_at'] ?? null;
        return $prod;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'sale_price' => $this->salePrice,
            'sku' => $this->sku,
            'stock' => $this->stock,
            'status' => $this->status,
            'images' => $this->images,
            'meta' => $this->meta,
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
    public function getPrice(): float { return $this->price; }
    public function setPrice(float $val): self { $this->price = $val; return $this; }
    public function getSalePrice(): ?float { return $this->salePrice; }
    public function setSalePrice(?float $val): self { $this->salePrice = $val; return $this; }
    public function getSku(): ?string { return $this->sku; }
    public function setSku(?string $val): self { $this->sku = $val; return $this; }
    public function getStock(): int { return $this->stock; }
    public function setStock(int $val): self { $this->stock = $val; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $val): self { $this->status = $val; return $this; }
    public function getImages(): array { return $this->images; }
    public function setImages(array $val): self { $this->images = $val; return $this; }
    public function getMeta(): array { return $this->meta; }
    public function setMeta(array $val): self { $this->meta = $val; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
}
