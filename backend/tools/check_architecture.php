<?php

declare(strict_types=1);

$sourceDir = dirname(__DIR__).'/src';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir));
$violations = [];
$lineCountExceptions = [
    'src/Module/Catalog/Domain/Entity/Product.php',
    'src/Module/Order/Domain/Entity/OrderCheckoutSession.php',
    'src/Module/TradeIn/Domain/Entity/TradeInRequest.php',
];
$serviceLineCountExceptions = [
    'src/Module/Catalog/Application/Provider/ProductCatalogSearchProvider.php',
    'src/Module/Catalog/Application/Provider/ProductCatalogAggregationFacets.php',
    'src/Module/Catalog/Application/Provider/ProductCatalogAggregationVariants.php',
];
$workflowLineCountExceptions = [
    'src/Module/Catalog/Application/Workflow/CategoryCatalogWorkflow.php',
];

function stripAttributesFromParameterList(string $parameters): string
{
    return preg_replace('/#\s*\[(?:[^\[\]]|\[[^\[\]]*\])*\]/s', '', $parameters) ?? $parameters;
}

function countConstructorParameters(string $parameters): int
{
    $parameters = trim(preg_replace('/,\s*$/', '', stripAttributesFromParameterList($parameters)));

    return '' === $parameters ? 0 : substr_count($parameters, ',') + 1;
}

/** @return list<array{name: string, lines: int, start: int}> */
function methodLineCounts(string $content): array
{
    $tokens = token_get_all($content);
    $methods = [];
    $count = count($tokens);

    for ($index = 0; $index < $count; ++$index) {
        if (!is_array($tokens[$index]) || T_FUNCTION !== $tokens[$index][0]) {
            continue;
        }

        $start = $tokens[$index][2];
        $name = 'closure';
        $bodyStart = null;
        $depth = 0;
        for ($cursor = $index + 1; $cursor < $count; ++$cursor) {
            $token = $tokens[$cursor];
            $value = is_array($token) ? $token[1] : $token;
            if (is_array($token) && T_STRING === $token[0]) {
                $name = $token[1];
            }
            if ('{' === $value) {
                $bodyStart = is_array($token) ? $token[2] : $start;
                $depth = 1;
                ++$cursor;
                break;
            }
            if (';' === $value) {
                break;
            }
        }

        if (null === $bodyStart) {
            continue;
        }

        for (; $cursor < $count; ++$cursor) {
            $token = $tokens[$cursor];
            $value = is_array($token) ? $token[1] : $token;
            if ('{' === $value) {
                ++$depth;
            } elseif ('}' === $value && 0 === --$depth) {
                $end = is_array($token) ? $token[2] : $bodyStart;
                $methods[] = ['name' => $name, 'lines' => $end - $bodyStart + 1, 'start' => $start];
                break;
            }
        }
    }

    return $methods;
}

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
    if ($lineCount > 500 && !in_array($relativePath, $lineCountExceptions, true)) {
        $violations[] = sprintf('%s: %d lignes (maximum global: 500)', $relativePath, $lineCount);
    }

    if (str_contains($relativePath, '/Domain/Entity/') && str_ends_with($relativePath, 'Trait.php') && $lineCount > 250) {
        $violations[] = sprintf('%s: %d lignes (maximum trait entité: 250)', $relativePath, $lineCount);
    }

    foreach (methodLineCounts($content) as $method) {
        if ($method['lines'] > 70) {
            $violations[] = sprintf('%s:%d %s: %d lignes (maximum méthode: 70)', $relativePath, $method['start'], $method['name'], $method['lines']);
        }
    }

    if (
        str_contains($relativePath, '/Application/Workflow/')
        && $lineCount > 250
        && !in_array($relativePath, $workflowLineCountExceptions, true)
    ) {
        $violations[] = sprintf('%s: %d lignes (maximum workflow: 250)', $relativePath, $lineCount);
    }

    if (str_ends_with($relativePath, 'Formatter.php') && $lineCount > 200) {
        $violations[] = sprintf('%s: %d lignes (maximum formatter: 200)', $relativePath, $lineCount);
    }

    if (str_contains($relativePath, '/Application/') && str_contains($content, 'Symfony\\Component\\HttpFoundation')) {
        $violations[] = $relativePath.': HttpFoundation interdit dans Application';
    }

    if (
        str_contains($relativePath, '/Application/')
        && (
            1 === preg_match('/^use App\\\\Module\\\\[^;]+\\\\Infrastructure\\\\/m', $content)
            || 1 === preg_match('/^use App\\\\Shared\\\\Infrastructure\\\\/m', $content)
        )
    ) {
        $violations[] = $relativePath.': Application ne doit pas dépendre d’Infrastructure';
    }

    if (str_contains($relativePath, '/Application/') && str_ends_with($relativePath, 'RequestMapper.php')) {
        $violations[] = $relativePath.': les mappers de Request HTTP doivent vivre dans UI';
    }

    if (
        (str_contains($relativePath, '/Application/') || str_contains($relativePath, '/UI/'))
        && (
            str_contains($content, 'Doctrine\\ORM\\EntityManagerInterface')
            || str_contains($content, 'Doctrine\\ORM\\QueryBuilder')
            || str_contains($content, 'createQueryBuilder(')
        )
    ) {
        $violations[] = $relativePath.': Doctrine direct interdit hors Infrastructure/Repository';
    }

    if (
        str_starts_with($relativePath, 'src/Module/')
        && (
            str_ends_with($relativePath, 'Manager.php')
            || str_ends_with($relativePath, 'Helper.php')
            || str_ends_with($relativePath, 'Utils.php')
        )
    ) {
        $violations[] = $relativePath.': suffixe trop générique interdit dans les modules';
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

    if (1 === preg_match('/function\s+__construct\s*\((.*?)\)\s*\{/s', $content, $constructor)) {
        $parameterCount = countConstructorParameters($constructor[1]);
        if ($parameterCount >= 8) {
            $violations[] = sprintf('%s: %d paramètres de constructeur (maximum global: 7)', $relativePath, $parameterCount);
        }
    }

    if (str_contains($relativePath, '/Domain/Entity/') && str_contains($content, '#[ORM\\Pre')) {
        foreach (methodLineCounts($content) as $method) {
            $pattern = sprintf('/#\[ORM\\\\Pre(?:Persist|Update)\][\s\S]*?function\s+%s\s*\([^)]*\)\s*:\s*void\s*\{(?P<body>[\s\S]*?)\n    \}/', preg_quote($method['name'], '/'));
            if (1 !== preg_match($pattern, $content, $lifecycleMethod)) {
                continue;
            }

            if (1 === preg_match('/\$this->(?!createdAt|updatedAt)[A-Za-z_][A-Za-z0-9_]*/', $lifecycleMethod['body'], $property)) {
                $violations[] = sprintf('%s:%d %s: callback Doctrine réservé aux dates techniques, accès interdit à %s', $relativePath, $method['start'], $method['name'], $property[0]);
            }
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
    if ($isService && !$isPdfTemplate && $lineCount > 250 && !in_array($relativePath, $serviceLineCountExceptions, true)) {
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
