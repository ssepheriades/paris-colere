<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

final class ImageResizer
{
    private const QUALITY = 80;

    public function resize(UploadedFile $file, int $maxEdge): string
    {
        $extension = $this->extension($file);
        $filename = Uuid::v4()->toRfc4122().'.'.$extension;

        if (!\extension_loaded('imagick') || !class_exists(\Imagick::class)) {
            // FrankenPHP / PHP sans Imagick : garder le fichier tel quel.
            return $filename;
        }

        $mime = (string) $file->getMimeType();
        $image = new \Imagick($file->getPathname());

        try {
            $image->autoOrient();

            $needsResize = $image->getImageWidth() > $maxEdge || $image->getImageHeight() > $maxEdge;
            if ('image/png' === $mime && !$needsResize) {
                return $filename;
            }

            if ($needsResize) {
                $image->thumbnailImage($maxEdge, $maxEdge, true);
            }

            if ('image/jpeg' === $mime) {
                $image->setImageFormat('jpeg');
                $image->setImageCompressionQuality(self::QUALITY);
            } elseif ('image/webp' === $mime) {
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality(self::QUALITY);
            } else {
                $image->setImageFormat('png');
            }

            $image->stripImage();
            $image->writeImage($file->getPathname());
        } finally {
            $image->clear();
        }

        return $filename;
    }

    private function extension(UploadedFile $file): string
    {
        return match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new \InvalidArgumentException(sprintf('Unsupported image type "%s".', $file->getMimeType())),
        };
    }
}
