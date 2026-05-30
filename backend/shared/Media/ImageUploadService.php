<?php

namespace Shared\Media;

use RuntimeException;
use Shared\Support\Uuid;

class ImageUploadService
{
    private array $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        private string $publicRoot,
        private int $maxBytes = 5242880
    ) {
    }

    public function upload(array $file, string $folder = 'products'): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload gambar gagal.');
        }

        if (($file['size'] ?? 0) > $this->maxBytes) {
            throw new RuntimeException('Ukuran gambar melebihi batas.');
        }

        $tmp = $file['tmp_name'] ?? '';
        $mimeType = mime_content_type($tmp) ?: '';

        if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new RuntimeException('Format gambar tidak didukung.');
        }

        [$width, $height] = getimagesize($tmp) ?: [null, null];
        $targetDir = rtrim($this->publicRoot, '/') . '/uploads/' . trim($folder, '/');

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $baseName = Uuid::v4();
        $webpPath = "{$targetDir}/{$baseName}.webp";
        $thumbPath = "{$targetDir}/{$baseName}-thumb.webp";

        $image = $this->createImage($tmp, $mimeType);
        imagepalettetotruecolor($image);
        imagewebp($image, $webpPath, 82);

        $thumb = imagescale($image, 480);
        imagewebp($thumb, $thumbPath, 78);
        imagedestroy($image);
        imagedestroy($thumb);

        return [
            'image_url' => '/uploads/' . trim($folder, '/') . '/' . basename($webpPath),
            'thumbnail_url' => '/uploads/' . trim($folder, '/') . '/' . basename($thumbPath),
            'mime_type' => 'image/webp',
            'file_size' => filesize($webpPath) ?: null,
            'width' => $width,
            'height' => $height,
        ];
    }

    private function createImage(string $path, string $mimeType): \GdImage
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default => throw new RuntimeException('Format gambar tidak didukung.'),
        };
    }
}
