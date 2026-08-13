<?php

declare(strict_types=1);

namespace App\Module\System\UI\Controller;

use App\Module\System\Application\Provider\LatestIosAppDownloadProvider;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/ios/latest-download', name: 'api_public_ios_latest_download', methods: ['GET'])]
final readonly class DownloadLatestIosAppController
{
    public function __construct(
        private LatestIosAppDownloadProvider $downloads,
        private AttachmentResponseFactory $attachments,
    ) {
    }

    public function __invoke(): Response
    {
        $download = $this->downloads->fetchLatestDownload();
        if (null === $download) {
            return ApiResponse::error('Téléchargement iPhone indisponible.', Response::HTTP_NOT_FOUND);
        }

        return $this->attachments->create($download['content'], $download['filename'], $download['contentType']);
    }
}
