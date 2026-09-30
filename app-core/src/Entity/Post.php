<?php

declare(strict_types=1);

namespace App\Entity;

class Post
{
    private ?int $id = null;
    private string $title = '';
    private string $slug = '';
    private ?string $content = null;
    private ?string $excerpt = null;
    private string $type = 'post'; // post, page
    private string $status = 'published'; // published, draft, archived
    private ?int $authorId = null;
    private ?string $featuredImage = null;
    private array $meta = [];
    private ?string $publishedAt = null;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public static function fromArray(array $data): self
    {
        $post = new self();
        $post->id = isset($data['id']) ? (int)$data['id'] : null;
        $post->title = $data['title'] ?? '';
        $post->slug = $data['slug'] ?? '';
        $post->content = $data['content'] ?? null;
        $post->excerpt = $data['excerpt'] ?? null;
        $post->type = $data['type'] ?? 'post';
        $post->status = $data['status'] ?? 'published';
        $post->authorId = isset($data['author_id']) ? (int)$data['author_id'] : null;
        $post->featuredImage = $data['featured_image'] ?? null;
        $post->meta = is_string($data['meta'] ?? null) ? (json_decode($data['meta'], true) ?: []) : ($data['meta'] ?? []);
        $post->publishedAt = $data['published_at'] ?? null;
        $post->createdAt = $data['created_at'] ?? null;
        $post->updatedAt = $data['updated_at'] ?? null;
        return $post;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'type' => $this->type,
            'status' => $this->status,
            'author_id' => $this->authorId,
            'featured_image' => $this->featuredImage,
            'meta' => $this->meta,
            'published_at' => $this->publishedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $val): self { $this->title = $val; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $val): self { $this->slug = $val; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(?string $val): self { $this->content = $val; return $this; }
    public function getExcerpt(): ?string { return $this->excerpt; }
    public function setExcerpt(?string $val): self { $this->excerpt = $val; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $val): self { $this->type = $val; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $val): self { $this->status = $val; return $this; }
    public function getAuthorId(): ?int { return $this->authorId; }
    public function setAuthorId(?int $val): self { $this->authorId = $val; return $this; }
    public function getFeaturedImage(): ?string { return $this->featuredImage; }
    public function setFeaturedImage(?string $val): self { $this->featuredImage = $val; return $this; }
    public function getMeta(): array { return $this->meta; }
    public function setMeta(array $val): self { $this->meta = $val; return $this; }
    public function getPublishedAt(): ?string { return $this->publishedAt; }
    public function setPublishedAt(?string $val): self { $this->publishedAt = $val; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
}
