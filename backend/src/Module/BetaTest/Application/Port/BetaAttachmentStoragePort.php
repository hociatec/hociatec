<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Port;

interface BetaAttachmentStoragePort
{
    /**
     * @param list<object> $files
     *
     * @return list<string>
     */
    public function store(array $files): array;

    public function path(string $name): ?string;

    /**
     * @param list<mixed> $names
     */
    public function deleteMany(array $names): void;
}
