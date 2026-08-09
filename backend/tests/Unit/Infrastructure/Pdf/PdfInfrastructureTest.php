<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Pdf;

use App\Shared\Infrastructure\Pdf\AccessiblePdfRenderer;
use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

final class PdfInfrastructureTest extends TestCase
{
    public function testAccessiblePdfRendererRendersWithFakePythonRuntime(): void
    {
        $projectDir = sys_get_temp_dir().'/hociatec-pdf-'.bin2hex(random_bytes(4));
        mkdir($projectDir.'/bin', 0777, true);
        file_put_contents($projectDir.'/bin/render_accessible_pdf.py', "# fake\n");

        $python = $projectDir.'/fake-python'.('Windows' === PHP_OS_FAMILY ? '.bat' : '.sh');
        file_put_contents($python, 'Windows' === PHP_OS_FAMILY ? <<<'BAT'
@echo off
if "%~1"=="-c" exit /B 0
<nul set /p dummy="PDF-CONTENT" > "%~3"
exit /B 0
BAT
            : <<<'SH'
#!/bin/sh
if [ "$1" = "-c" ]; then
  exit 0
fi
printf 'PDF-CONTENT' > "$3"
exit 0
SH);
        chmod($python, 0755);

        $renderer = new AccessiblePdfRenderer($projectDir, $python, '/opt/python-packages');

        self::assertSame('PDF-CONTENT', $renderer->render('<h1>Invoice</h1>', 'invoice', 'read error'));
    }

    public function testAccessiblePdfRendererRejectsMissingRuntimeOrScript(): void
    {
        $renderer = new AccessiblePdfRenderer('/tmp/does-not-exist', '/tmp/missing-python', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('WeasyPrint n\'est pas installé pour la génération PDF accessible.');

        $renderer->render('<p>x</p>', 'invoice', 'read error');
    }

    public function testAccessiblePdfRendererRunReturnsGenericFailureMessage(): void
    {
        $projectDir = sys_get_temp_dir().'/hociatec-pdf-fail-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0777, true);

        $python = $projectDir.'/fail-python'.('Windows' === PHP_OS_FAMILY ? '.bat' : '.sh');
        file_put_contents($python, 'Windows' === PHP_OS_FAMILY ? <<<'BAT'
@echo off
echo boom 1>&2
exit /B 1
BAT
            : <<<'SH'
#!/bin/sh
echo boom 1>&2
exit 1
SH);
        chmod($python, 0755);

        $renderer = new AccessiblePdfRenderer($projectDir, $python, '');
        $reflection = new \ReflectionObject($renderer);
        $method = $reflection->getMethod('run');
        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('La génération PDF accessible a échoué.');

        $method->invoke($renderer, $python, $projectDir.'/script.py', $projectDir.'/in.html', $projectDir.'/out.pdf');
    }

    public function testAccessiblePdfRendererPrivateHelpersCoverEnvironmentAndBinaryResolutionBranches(): void
    {
        $projectDir = sys_get_temp_dir().'/hociatec-pdf-helper-'.bin2hex(random_bytes(4));
        mkdir($projectDir.'/bin', 0777, true);
        file_put_contents($projectDir.'/bin/render_accessible_pdf.py', "# fake\n");

        $python = $projectDir.'/fake-python'.('Windows' === PHP_OS_FAMILY ? '.bat' : '.sh');
        file_put_contents(
            $python,
            'Windows' === PHP_OS_FAMILY
                ? "@echo off\r\nif \"%~1\"==\"-c\" exit /B 0\r\nexit /B 0\r\n"
                : "#!/bin/sh\nif [ \"$1\" = \"-c\" ]; then exit 0; fi\nexit 0\n"
        );
        chmod($python, 0755);

        $renderer = new AccessiblePdfRenderer($projectDir, $python, '/opt/site-packages');
        $reflection = new \ReflectionObject($renderer);

        $resolve = $reflection->getMethod('resolvePythonBinary');
        $resolve->setAccessible(true);
        self::assertSame($python, $resolve->invoke($renderer));

        $env = $reflection->getMethod('environment');
        $env->setAccessible(true);
        $environment = $env->invoke($renderer);
        self::assertArrayNotHasKey('HOME', $environment);
        self::assertStringContainsString('/opt/site-packages', $environment['PYTHONPATH']);

        $canImport = $reflection->getMethod('canImportWeasyPrint');
        $canImport->setAccessible(true);
        self::assertFalse($canImport->invoke($renderer, '/path/does/not/exist'));

        $withoutPythonPath = new AccessiblePdfRenderer($projectDir, '/missing-python', '');
        $env2 = (new \ReflectionObject($withoutPythonPath))->getMethod('environment');
        $env2->setAccessible(true);
        self::assertArrayNotHasKey('PYTHONPATH', $env2->invoke($withoutPythonPath));

        $withHome = new AccessiblePdfRenderer($projectDir, $python, '/opt/site-packages', '/tmp/hociatec-home');
        $env3 = (new \ReflectionObject($withHome))->getMethod('environment');
        $env3->setAccessible(true);
        self::assertSame('/tmp/hociatec-home', $env3->invoke($withHome)['HOME']);
    }

    public function testPdfHtmlFormatterCoversMoneyDateEscapeAndParagraphVariants(): void
    {
        $formatter = new PdfHtmlFormatter();

        self::assertSame('12,34 EUR', $formatter->money(1234));
        self::assertSame('-', $formatter->date(null));
        self::assertSame('29/07/2026', $formatter->date('2026-07-29'));
        self::assertSame('-', $formatter->date('bad-date'));
        self::assertSame('bad-date', $formatter->date('bad-date', true));
        self::assertSame('&lt;b&gt;x&lt;/b&gt;', $formatter->escape('<b>x</b>'));
        self::assertSame('', $formatter->paragraphsFromLines(" \n "));
        self::assertSame('<p>-</p>', $formatter->paragraphsFromLines(" \n ", true));
        self::assertSame('<p>Line 1</p><p>&lt;Line 2&gt;</p>', $formatter->paragraphsFromLines(" Line 1 \n <Line 2> "));
    }

    public function testAccessiblePdfRendererReturnsGenericFailureWhenPdfWasNotGenerated(): void
    {
        $projectDir = sys_get_temp_dir().'/hociatec-pdf-read-'.bin2hex(random_bytes(4));
        mkdir($projectDir.'/bin', 0777, true);
        file_put_contents($projectDir.'/bin/render_accessible_pdf.py', "# fake\n");

        $python = $projectDir.'/read-python'.('Windows' === PHP_OS_FAMILY ? '.bat' : '.sh');
        file_put_contents($python, 'Windows' === PHP_OS_FAMILY ? <<<'BAT'
@echo off
if "%~1"=="-c" exit /B 0
exit /B 0
BAT
            : <<<'SH'
#!/bin/sh
if [ "$1" = "-c" ]; then
  exit 0
fi
exit 0
SH);
        chmod($python, 0755);

        $renderer = new AccessiblePdfRenderer($projectDir, $python, '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('La génération PDF accessible a échoué.');
        $renderer->render('<h1>x</h1>', 'invoice', 'lecture impossible');
    }

    public function testAccessiblePdfRendererIgnoresCleanupWhenPathIsNotAFile(): void
    {
        $path = sys_get_temp_dir().'/hociatec-pdf-dir-'.bin2hex(random_bytes(4));
        mkdir($path);

        $logger = new PdfCollectingLogger();
        $renderer = new AccessiblePdfRenderer('/tmp', '/missing-python', '', '', $logger);
        $reflection = new \ReflectionObject($renderer);
        $method = $reflection->getMethod('removeTemporaryFile');
        $method->setAccessible(true);
        $method->invoke($renderer, $path);

        self::assertSame([], $logger->warnings);
    }

    public function testAccessiblePdfRendererLogsDebugWhenImportCheckCannotRunProcess(): void
    {
        $projectDir = sys_get_temp_dir().'/hociatec-pdf-debug-'.bin2hex(random_bytes(4));
        mkdir($projectDir.'/bin', 0777, true);
        file_put_contents($projectDir.'/bin/render_accessible_pdf.py', "# fake\n");

        $python = $projectDir.'/non-executable-python.sh';
        file_put_contents($python, "#!/bin/sh\nexit 0\n");
        chmod($python, 0644);

        $logger = new PdfCollectingLogger();
        $renderer = new AccessiblePdfRenderer($projectDir, $python, '', '', $logger);
        $method = (new \ReflectionObject($renderer))->getMethod('canImportWeasyPrint');
        $method->setAccessible(true);

        self::assertFalse($method->invoke($renderer, $python));
        self::assertCount(1, $logger->debugs);
        self::assertSame('WeasyPrint import check failed.', $logger->debugs[0]['message']);
    }
}

final class PdfCollectingLogger extends AbstractLogger
{
    /** @var list<array{level:string,message:string,context:array<string,mixed>}> */
    public array $warnings = [];
    /** @var list<array{level:string,message:string,context:array<string,mixed>}> */
    public array $debugs = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        if ('warning' === $level) {
            $this->warnings[] = [
                'level' => (string) $level,
                'message' => (string) $message,
                'context' => $context,
            ];

            return;
        }

        if ('debug' === $level) {
            $this->debugs[] = [
                'level' => (string) $level,
                'message' => (string) $message,
                'context' => $context,
            ];
        }
    }
}
