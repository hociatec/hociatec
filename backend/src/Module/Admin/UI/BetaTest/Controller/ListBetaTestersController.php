<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\BetaTest\Application\Mapper\BetaProfileChoices;
use App\Module\BetaTest\Application\Port\BetaTesterProfileRepositoryPort;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-testers', name: 'api_admin_beta_testers', methods: ['GET'])]
#[IsGranted('ROLE_BETA_MANAGER')]
final class ListBetaTestersController extends AbstractController
{
    public function __construct(private readonly BetaTesterProfileRepositoryPort $profiles)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $search = RequestQueryMapper::lowerString($request, 'q');
        $status = RequestQueryMapper::string($request, 'status');
        $accessibility = RequestQueryMapper::string($request, 'accessibility');
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $pageItems = $this->profiles->findForAdminList($search, $status, $accessibility, $pagination->perPage, $pagination->offset());
        $total = $this->profiles->countForAdminList($search, $status, $accessibility);

        return ApiResponse::paginated(array_map([$this, 'format'], $pageItems), $pagination->metadata($total));
    }

    /**
     * @return array<string, mixed>
     */
    private function format(BetaTesterProfile $profile): array
    {
        $user = $profile->getUser();

        return ['id' => $profile->getId(), 'userId' => $user->getId(), 'firstName' => $user->getFirstName(), 'lastName' => $user->getLastName(), 'email' => $user->getEmail(), 'status' => $profile->getStatus(), 'availability' => $profile->getAvailability(), 'motivation' => $profile->getMotivation(), 'testingExperience' => BetaProfileChoices::parseStoredList($profile->getTestingExperience(), 'testingExperience'), 'bugDescriptionAbility' => BetaProfileChoices::parseStoredList($profile->getBugDescriptionAbility(), 'bugDescriptionAbility'), 'technicalKnowledge' => BetaProfileChoices::parseStoredList($profile->getTechnicalKnowledge(), 'technicalKnowledge'), 'accessibilityNeed' => $profile->getAccessibilityNeed(), 'assistiveTools' => $profile->getAssistiveTools(), 'devices' => $profile->getDevices(), 'browsers' => $profile->getBrowsers(), 'testingTypes' => $profile->getTestingTypes(), 'consentAt' => $profile->getConsentAt()->format(DATE_ATOM), 'createdAt' => $profile->getCreatedAt()->format(DATE_ATOM)];
    }
}
