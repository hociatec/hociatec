<?php

declare(strict_types=1);

namespace App\Shared\Pdf;

final readonly class AccessiblePdfRenderer
{
    public function __construct(
        private string $projectDir,
        private string $configuredPython,
        private string $configuredPythonPath,
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
        @unlink($htmlFile);
        @unlink($pdfFile);

        try {
            file_put_contents($htmlPath, $html);
            $this->run($python, $script, $htmlPath, $pdfPath);
            $pdf = file_get_contents($pdfPath);
            if (false === $pdf) {
                throw new \RuntimeException($readError);
            }

            return $pdf;
        } finally {
            @unlink($htmlPath);
            @unlink($pdfPath);
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

        $process = proc_open(
            sprintf('%s -c %s', escapeshellarg($python), escapeshellarg('import weasyprint')),
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            $this->environment(),
        );
        if (!is_resource($process)) {
            return false;
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        return 0 === proc_close($process);
    }

    private function run(string $python, string $script, string $htmlPath, string $pdfPath): void
    {
        $process = proc_open(
            sprintf(
                '%s %s %s %s',
                escapeshellarg($python),
                escapeshellarg($script),
                escapeshellarg($htmlPath),
                escapeshellarg($pdfPath),
            ),
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            $this->environment(),
        );
        if (!is_resource($process)) {
            throw new \RuntimeException('Impossible de démarrer WeasyPrint.');
        }

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if (0 !== $exitCode || !is_file($pdfPath)) {
            $message = trim($stderr ?: $stdout);
            throw new \RuntimeException('' !== $message ? $message : 'La génération PDF accessible a échoué.');
        }
    }

    /** @return array<string, string> */
    private function environment(): array
    {
        $pythonPaths = [];
        if ('' !== trim($this->configuredPythonPath)) {
            $pythonPaths[] = trim($this->configuredPythonPath);
        }

        $deploymentPackages = '/home/hocine/.local/lib/python3.10/site-packages';
        if (is_dir($deploymentPackages)) {
            $pythonPaths[] = $deploymentPackages;
        }

        $environment = [
            'HOME' => '/home/hocine',
            'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        ];
        if ([] !== $pythonPaths) {
            $environment['PYTHONPATH'] = implode(
                PATH_SEPARATOR,
                array_values(array_unique($pythonPaths)),
            );
        }

        return $environment;
    }
}
