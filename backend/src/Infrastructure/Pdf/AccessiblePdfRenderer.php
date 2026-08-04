<?php

declare(strict_types=1);

namespace App\Infrastructure\Pdf;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

final readonly class AccessiblePdfRenderer
{
    public function __construct(
        private string $projectDir,
        private string $configuredPython,
        private string $configuredPythonPath,
        private string $configuredHome = '',
        private ?LoggerInterface $logger = null,
        private int $timeoutSeconds = 60,
    ) {
    }

    public function render(string $html, string $filePrefix, string $readError): string
    {
        $python = $this->resolvePythonBinary();
        $script = $this->projectDir.'/bin/render_accessible_pdf.py';
        if (null === $python || !is_file($script)) {
            throw new \RuntimeException('WeasyPrint n\'est pas installé pour la génération PDF accessible.');
        }

        $htmlFile = tempnam(sys_get_temp_dir(), $filePrefix.'-html-');
        $pdfFile = tempnam(sys_get_temp_dir(), $filePrefix.'-pdf-');
        if (false === $htmlFile || false === $pdfFile) {
            throw new \RuntimeException('Impossible de préparer les fichiers temporaires du document PDF.');
        }

        $htmlPath = $htmlFile.'.html';
        $pdfPath = $pdfFile.'.pdf';
        $this->removeTemporaryFile($htmlFile);
        $this->removeTemporaryFile($pdfFile);

        try {
            if (false === file_put_contents($htmlPath, $html)) {
                throw new \RuntimeException('Impossible d\'écrire le fichier temporaire du document PDF.');
            }
            $this->run($python, $script, $htmlPath, $pdfPath);
            $pdf = file_get_contents($pdfPath);
            if (false === $pdf) {
                throw new \RuntimeException($readError);
            }

            return $pdf;
        } finally {
            $this->removeTemporaryFile($htmlPath);
            $this->removeTemporaryFile($pdfPath);
        }
    }

    private function resolvePythonBinary(): ?string
    {
        $workspaceRoot = dirname($this->projectDir);
        $candidates = array_filter([
            '' !== trim($this->configuredPython) ? trim($this->configuredPython) : null,
            $workspaceRoot.'/.venv-weasy/bin/python',
            $this->projectDir.'/.venv-weasy/bin/python',
            '/usr/bin/python3',
            '/usr/local/bin/python3',
            'python3',
        ]);

        foreach ($candidates as $candidate) {
            if ($this->canImportWeasyPrint($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function canImportWeasyPrint(string $python): bool
    {
        if (str_contains($python, '/') && !is_file($python)) {
            return false;
        }

        $process = new Process([$python, '-c', 'import weasyprint'], null, $this->environment());
        $process->setTimeout(5);

        try {
            $process->run();
        } catch (\RuntimeException $exception) {
            $this->logger?->debug('WeasyPrint import check failed.', [
                'python' => $python,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        return $process->isSuccessful();
    }

    private function run(string $python, string $script, string $htmlPath, string $pdfPath): void
    {
        $process = new Process([$python, $script, $htmlPath, $pdfPath], null, $this->environment());
        $process->setTimeout($this->timeoutSeconds);

        try {
            $process->run();
        } catch (\RuntimeException $exception) {
            $this->logger?->error('PDF generation process failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('La génération PDF accessible a échoué.', 0, $exception);
        }

        if (!$process->isSuccessful() || !is_file($pdfPath)) {
            $this->logger?->error('PDF generation failed.', [
                'exitCode' => $process->getExitCode(),
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput(),
            ]);

            throw new \RuntimeException('La génération PDF accessible a échoué.');
        }
    }

    /** @return array<string, string> */
    private function environment(): array
    {
        $pythonPaths = [];
        if ('' !== trim($this->configuredPythonPath)) {
            $pythonPaths[] = trim($this->configuredPythonPath);
        }

        $environment = ['PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'];
        if ('' !== trim($this->configuredHome)) {
            $environment['HOME'] = trim($this->configuredHome);
        }
        if ([] !== $pythonPaths) {
            $environment['PYTHONPATH'] = implode(
                PATH_SEPARATOR,
                array_values(array_unique($pythonPaths)),
            );
        }

        return $environment;
    }

    private function removeTemporaryFile(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        if (!unlink($path)) {
            $this->logger?->warning('Temporary PDF file cleanup failed.', ['path' => $path]);
        }
    }
}
