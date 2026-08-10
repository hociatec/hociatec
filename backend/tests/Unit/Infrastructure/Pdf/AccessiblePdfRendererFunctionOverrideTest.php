<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Pdf {
    final class AccessiblePdfRendererTestState
    {
        public static bool $forceTempnamFailure = false;
        public static bool $forceHtmlWriteFailure = false;
        public static bool $forcePdfReadFailure = false;

        public static function reset(): void
        {
            self::$forceTempnamFailure = false;
            self::$forceHtmlWriteFailure = false;
            self::$forcePdfReadFailure = false;
        }
    }

    function tempnam(string $directory, string $prefix): string|false
    {
        if (AccessiblePdfRendererTestState::$forceTempnamFailure) {
            return false;
        }

        return \tempnam($directory, $prefix);
    }

    function file_put_contents(string $filename, mixed $data, int $flags = 0, $context = null): int|false
    {
        if (AccessiblePdfRendererTestState::$forceHtmlWriteFailure && str_ends_with($filename, '.html')) {
            return false;
        }

        return null === $context
            ? \file_put_contents($filename, $data, $flags)
            : \file_put_contents($filename, $data, $flags, $context);
    }

    function file_get_contents(string $filename, bool $use_include_path = false, $context = null, int $offset = 0, ?int $length = null): string|false
    {
        if (AccessiblePdfRendererTestState::$forcePdfReadFailure && str_ends_with($filename, '.pdf')) {
            return false;
        }

        if (null === $context) {
            return null === $length
                ? \file_get_contents($filename, $use_include_path, null, $offset)
                : \file_get_contents($filename, $use_include_path, null, $offset, $length);
        }

        return null === $length
            ? \file_get_contents($filename, $use_include_path, $context, $offset)
            : \file_get_contents($filename, $use_include_path, $context, $offset, $length);
    }
}

namespace App\Tests\Unit\Infrastructure\Pdf {

    use App\Shared\Infrastructure\Pdf\AccessiblePdfRenderer;
    use App\Shared\Infrastructure\Pdf\AccessiblePdfRendererTestState;
    use PHPUnit\Framework\TestCase;

    final class AccessiblePdfRendererFunctionOverrideTest extends TestCase
    {
        protected function tearDown(): void
        {
            AccessiblePdfRendererTestState::reset();
        }

        public function testAccessiblePdfRendererReportsTemporaryFilePreparationAndHtmlWriteFailures(): void
        {
            $projectDir = $this->projectDir();
            $python = $this->fakePython($projectDir, true);

            AccessiblePdfRendererTestState::$forceTempnamFailure = true;
            try {
                (new AccessiblePdfRenderer($projectDir, $python, ''))->render('<h1>x</h1>', 'invoice', 'read error');
                self::fail('Expected tempnam failure.');
            } catch (\RuntimeException $exception) {
                self::assertSame('Impossible de préparer les fichiers temporaires du document PDF.', $exception->getMessage());
            }

            AccessiblePdfRendererTestState::reset();
            AccessiblePdfRendererTestState::$forceHtmlWriteFailure = true;
            try {
                (new AccessiblePdfRenderer($projectDir, $python, ''))->render('<h1>x</h1>', 'invoice', 'read error');
                self::fail('Expected HTML write failure.');
            } catch (\RuntimeException $exception) {
                self::assertSame('Impossible d\'écrire le fichier temporaire du document PDF.', $exception->getMessage());
            }
        }

        public function testAccessiblePdfRendererReportsCustomReadErrorWhenPdfCannotBeRead(): void
        {
            $projectDir = $this->projectDir();
            $python = $this->fakePython($projectDir, true);
            AccessiblePdfRendererTestState::$forcePdfReadFailure = true;

            try {
                (new AccessiblePdfRenderer($projectDir, $python, ''))->render('<h1>x</h1>', 'invoice', 'lecture impossible');
                self::fail('Expected PDF read failure.');
            } catch (\RuntimeException $exception) {
                self::assertSame('lecture impossible', $exception->getMessage());
            }
        }

        private function projectDir(): string
        {
            $projectDir = sys_get_temp_dir().'/hociatec-pdf-override-'.bin2hex(random_bytes(4));
            mkdir($projectDir.'/bin', 0777, true);
            file_put_contents($projectDir.'/bin/render_accessible_pdf.py', "# fake\n");

            return $projectDir;
        }

        private function fakePython(string $projectDir, bool $writePdf): string
        {
            $python = $projectDir.'/fake-python'.('Windows' === PHP_OS_FAMILY ? '.bat' : '.sh');
            file_put_contents($python, 'Windows' === PHP_OS_FAMILY ? ($writePdf ? <<<'BAT'
@echo off
if "%~1"=="-c" exit /B 0
<nul set /p dummy="PDF-CONTENT" > "%~3"
exit /B 0
BAT
                : <<<'BAT'
@echo off
if "%~1"=="-c" exit /B 0
exit /B 0
BAT) : ($writePdf ? <<<'SH'
#!/bin/sh
if [ "$1" = "-c" ]; then
  exit 0
fi
printf 'PDF-CONTENT' > "$3"
exit 0
SH
                : <<<'SH'
#!/bin/sh
if [ "$1" = "-c" ]; then
  exit 0
fi
exit 0
SH));
            chmod($python, 0755);

            return $python;
        }
    }
}
