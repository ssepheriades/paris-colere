<?php

namespace App\Service;

use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Image;

final class UploadedImageField
{
    public function __construct(
        private readonly ImageResizer $resizer,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function photo(): ImageField
    {
        return $this->field('photo', 'Photo', 'photos', 800);
    }

    public function logo(): ImageField
    {
        return $this->field('logo', 'Logo', 'logos', 512);
    }

    private function field(string $property, string $label, string $directory, int $maxEdge): ImageField
    {
        $uploadDir = $this->projectDir.'/public/uploads/'.$directory;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException(sprintf('Unable to create the upload directory "%s".', $uploadDir));
        }

        return ImageField::new($property, $label)
            ->setBasePath('uploads/'.$directory)
            ->setUploadDir('public/uploads/'.$directory)
            ->setUploadedFileNamePattern(fn (UploadedFile $file): string => $this->resizer->resize($file, $maxEdge))
            ->setFileConstraints(new Image(
                maxSize: '8M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
            ))
            ->mimeTypes('image/jpeg,image/png,image/webp')
            ->maxSize('8M');
    }
}
