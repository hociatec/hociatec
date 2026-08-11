<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final readonly class AttachmentResponseFactory
{
    public function create(string $content, string $filename, string $contentType, int $status = Response::HTTP_OK): Response
    {
        $response = new Response($content, $status);
        $this->applyHeaders($response, $filename, $contentType);

        return $response;
    }

    public function applyHeaders(Response $response, string $filename, string $contentType): void
    {
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->safeFilename($filename),
        ));
    }

    public function createBinaryFile(string $path, string $filename, string $contentType = 'application/octet-stream', int $status = Response::HTTP_OK): BinaryFileResponse
    {
        $response = new BinaryFileResponse($path, $status);
        $this->applyHeaders($response, $filename, $contentType);

        return $response;
    }

    private function safeFilename(string $filename): string
    {
        $filename = trim(basename($filename));
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?? '';
        $filename = trim($filename, '.-');

        return '' !== $filename ? $filename : 'download';
    }
}
