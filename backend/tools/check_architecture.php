<?php

declare(strict_types=1);

$sourceDir = dirname(__DIR__).'/src';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir));
$violations = [];

/** @var SplFileInfo $file */
foreach ($iterator as $file) {
    if (!$file->isFile() || 'php' !== $file->getExtension()) {
        continue;
    }

    $path = $file->getPathname();
    $relativePath = substr($path, strlen(dirname(__DIR__)) + 1);
    $content = file_get_contents($path);
    if (false === $content) {
        $violations[] = $relativePath.': lecture impossible';
        continue;
    }

    $lineCount = substr_count($content, "\n") + 1;
    if ($lineCount > 500) {
        $violations[] = sprintf('%s: %d lignes (maximum global: 500)', $relativePath, $lineCount);
    }

    if (str_contains($relativePath, '/Controller/') && $lineCount > 120) {
        $violations[] = sprintf('%s: %d lignes (maximum contrôleur: 120)', $relativePath, $lineCount);
    }

    if (
        str_contains($relativePath, '/Controller/')
        && 1 === preg_match('/function\s+__construct\s*\((.*?)\)\s*\{/s', $content, $constructor)
    ) {
        preg_match_all('/\b(?:private|protected|public)\b/', $constructor[1], $dependencies);
        if (count($dependencies[0]) > 5) {
            $violations[] = sprintf(
                '%s: %d dépendances injectées (maximum contrôleur: 5)',
                $relativePath,
                count($dependencies[0]),
            );
        }
    }

    if (str_contains($relativePath, '/Controller/') && str_contains($content, 'json_decode(')) {
        $violations[] = $relativePath.': décodage JSON direct interdit dans un contrôleur';
    }

    if (str_contains($relativePath, '/Controller/') && str_contains($content, 'new JsonResponse')) {
        $violations[] = $relativePath.': utiliser ApiResponse pour garantir le contrat JSON';
    }

    if (str_contains($relativePath, '/Controller/') && str_contains($content, 'ValidatorInterface')) {
        $violations[] = $relativePath.': utiliser DtoValidator pour centraliser la validation';
    }

    $isService = str_contains($relativePath, '/Service/') || str_contains($relativePath, '/Provider/');
    $isPdfTemplate = str_ends_with($relativePath, 'PdfService.php');
    if ($isService && !$isPdfTemplate && $lineCount > 250) {
        $violations[] = sprintf('%s: %d lignes (maximum service: 250)', $relativePath, $lineCount);
    }

    if (str_contains($content, '$_ENV') || str_contains($content, '$_SERVER')) {
        $violations[] = $relativePath.': accès direct à $_ENV/$_SERVER interdit';
    }
}

if ([] !== $violations) {
    fwrite(STDERR, "Architecture backend invalide:\n- ".implode("\n- ", $violations)."\n");
    exit(1);
}

fwrite(STDOUT, "Architecture backend valide.\n");
