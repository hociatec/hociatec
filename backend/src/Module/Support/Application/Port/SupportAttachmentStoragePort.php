<?php

declare(strict_types=1);

namespace App\Module\Support\Application\Port;

interface SupportAttachmentStoragePort
{
    /** @param list<object> $files
     * @return list<array{name:string,originalName:string,contentType:string,size:int,uploadedAt:string}>
     */
    public function store(array $files): array;

    public function path(string $name): ?string;
}
