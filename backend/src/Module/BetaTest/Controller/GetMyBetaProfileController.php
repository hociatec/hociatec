<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Controller;

use App\Module\BetaTest\Repository\BetaTesterProfileRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/profile', methods: ['GET'])] #[IsGranted('ROLE_USER')]
final class GetMyBetaProfileController extends AbstractController
{
    public function __construct(private readonly BetaTesterProfileRepository $profiles)
    {
    }

    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        } $profile = $this->profiles->findOneByUser($user);
        if (null === $profile) {
            return ApiResponse::error('Profil bêta introuvable.', 404);
        }

return ApiResponse::success(['profile' => ['id' => $profile->getId(), 'status' => $profile->getStatus(), 'availability' => $profile->getAvailability(), 'motivation' => $profile->getMotivation(), 'testingExperience' => $profile->getTestingExperience(), 'bugDescriptionAbility' => $profile->getBugDescriptionAbility(), 'technicalKnowledge' => $profile->getTechnicalKnowledge(), 'accessibilityNeed' => $profile->getAccessibilityNeed(), 'assistiveTools' => $profile->getAssistiveTools(), 'devices' => $profile->getDevices(), 'browsers' => $profile->getBrowsers(), 'testingTypes' => $profile->getTestingTypes()]]);
    }
}
