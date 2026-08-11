<?php

declare(strict_types=1);

namespace App\Module\User\UI\Controller;

use App\Module\User\Application\Provider\PersonalDataExportProvider;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth/profile/export', name: 'api_auth_profile_export', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ExportMyPersonalDataController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly PersonalDataExportProvider $exports,
        private readonly AttachmentResponseFactory $attachments,
    ) {
    }

    public function __invoke(): Response
    {
        $user = $this->currentUser();
        $content = json_encode($this->exports->export($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $response = $this->attachments->create(
            $content,
            sprintf('personal-data-export-%d.json', $user->getId() ?? 0),
            'application/json; charset=utf-8',
        );
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
