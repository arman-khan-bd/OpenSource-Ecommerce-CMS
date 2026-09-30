<?php

declare(strict_types=1);

namespace App\Entity;

class Media
{
    private ?int $id = null;
    private string $filename = '';
    private string $originalName = '';
    private string $mimeType = '';
    private int $fileSize = 0;
    private ?int $width = null;
    private ?int $height = null;
    private string $path = '';
    private string $url = '';
    private ?string $altText = null;
    private array $meta = [];
    private ?string $createdAt = null;

    public static function fromArray(array $data): self
    {
        $media = new self();
        $media->id = isset($data['id']) ? (int)$data['id'] : null;
        $media->filename = $data['filename'] ?? '';
        $media->originalName = $data['original_name'] ?? '';
        $media->mimeType = $data['mime_type'] ?? '';
        $media->fileSize = (int)($data['file_size'] ?? 0);
        $media->width = isset($data['width']) ? (int)$data['width'] : null;
        $media->height = isset($data['height']) ? (int)$data['height'] : null;
        $media->path = $data['path'] ?? '';
        $media->url = $data['url'] ?? '';
        $media->altText = $data['alt_text'] ?? null;
        $media->meta = is_string($data['meta'] ?? null) ? (json_decode($data['meta'], true) ?: []) : ($data['meta'] ?? []);
        $media->createdAt = $data['created_at'] ?? null;
        return $media;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'width' => $this->width,
            'height' => $this->height,
            'path' => $this->path,
            'url' => $this->url,
            'alt_text' => $this->altText,
            'meta' => $this->meta,
            'created_at' => $this->createdAt,
        ];
    }

    public function getId(): ?int { return $this->id; }
    public function getFilename(): string { return $this->filename; }
    public function setFilename(string $val): self { $this->filename = $val; return $this; }
    public function getOriginalName(): string { return $this->originalName; }
    public function setOriginalName(string $val): self { $this->originalName = $val; return $this; }
    public function getMimeType(): string { return $this->mimeType; }
    public function setMimeType(string $val): self { $this->mimeType = $val; return $this; }
    public function getFileSize(): int { return $this->fileSize; }
    public function setFileSize(int $val): self { $this->fileSize = $val; return $this; }
    public function getWidth(): ?int { return $this->width; }
    public function setWidth(?int $val): self { $this->width = $val; return $this; }
    public function getHeight(): ?int { return $this->height; }
    public function setHeight(?int $val): self { $this->height = $val; return $this; }
    public function getPath(): string { return $this->path; }
    public function setPath(string $val): self { $this->path = $val; return $this; }
    public function getUrl(): string { return $this->url; }
    public function setUrl(string $val): self { $this->url = $val; return $this; }
    public function getAltText(): ?string { return $this->altText; }
    public function setAltText(?string $val): self { $this->altText = $val; return $this; }
    public function getMeta(): array { return $this->meta; }
    public function setMeta(array $val): self { $this->meta = $val; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
}
