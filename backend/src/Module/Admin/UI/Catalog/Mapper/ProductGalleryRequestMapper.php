<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Mapper;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class ProductGalleryRequestMapper
{
    /**
     * @return array<int, UploadedFile|null>
     */
    public function files(Request $request): array
    {
        $payload = $request->files->all('gallery');

        $files = [];
        foreach (range(0, 3) as $index) {
            $file = $payload[$index] ?? null;
            if (0 === $index && null === $file) {
                $file = $request->files->get('image');
            }
            if (null !== $file && !$file instanceof UploadedFile) {
                throw new \InvalidArgumentException('Fichier d’image invalide.');
            }
            $files[$index] = $file;
        }

        return $files;
    }

    /**
     * @return list<int>
     */
    public function removals(Request $request): array
    {
        try {
            $values = $request->request->all('removeGallery');
        } catch (BadRequestException) {
            $values = [];
        }
        if ([] === $values) {
            $single = $request->request->get('removeGallery');
            $values = null === $single ? [] : [$single];
        }

        $removals = [];
        foreach ($values as $value) {
            $value = is_array($value) ? reset($value) : $value;
            if (is_numeric($value)) {
                $removals[] = (int) $value;
            }
        }

        return $removals;
    }
}
