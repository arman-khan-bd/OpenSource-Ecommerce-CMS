<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Media;
use App\Repository\MediaRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaService
{
    private string $uploadDir;
    private MediaRepository $mediaRepo;

    public function __construct(string $projectDir, MediaRepository $mediaRepo)
    {
        // Uploads are placed directly in public_html/uploads for instant Apache static delivery
        $this->uploadDir = dirname($projectDir) . '/public_html/uploads';
        $this->mediaRepo = $mediaRepo;

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }
    }

    public function upload(UploadedFile $file, ?string $altText = null): Media
    {
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $size = $file->getSize() ?: 0;
        $extension = $file->getClientOriginalExtension() ?: 'bin';

        $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $uniqueName = $safeName . '-' . bin2hex(random_bytes(4)) . '.' . strtolower($extension);

        // Subdirectory by Year/Month
        $subDir = date('Y/m');
        $targetDir = $this->uploadDir . '/' . $subDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $file->move($targetDir, $uniqueName);
        $fullPath = $targetDir . '/' . $uniqueName;
        $publicUrl = '/uploads/' . $subDir . '/' . $uniqueName;

        $width = null;
        $height = null;
        if (str_starts_with($mimeType, 'image/')) {
            $dimensions = @getimagesize($fullPath);
            if ($dimensions) {
                $width = $dimensions[0];
                $height = $dimensions[1];
            }
        }

        $media = Media::fromArray([
            'filename' => $uniqueName,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $size,
            'width' => $width,
            'height' => $height,
            'path' => $fullPath,
            'url' => $publicUrl,
            'alt_text' => $altText ?: $safeName,
            'meta' => [
                'sub_dir' => $subDir,
                'extension' => $extension,
            ],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->mediaRepo->save($media);
    }
}
