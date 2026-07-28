<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\BetaTest\Entity\BetaTesterProfile;
use App\Module\BetaTest\Repository\BetaTesterProfileRepository;
use App\Module\BetaTest\Service\BetaProfileChoices;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-testers', name: 'api_admin_beta_testers', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListBetaTestersController extends AbstractController
{
    public function __construct(private readonly BetaTesterProfileRepository $profiles)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $search = mb_strtolower(trim((string) $request->query->get('search', '')));
        $status = (string) $request->query->get('status', '');
        $accessibility = (string) $request->query->get('accessibility', '');
        $items = array_filter($this->profiles->findBy([], ['createdAt' => 'DESC']), static function (BetaTesterProfile $profile) use ($search, $status, $accessibility): bool {
            $user = $profile->getUser();
            $hay = mb_strtolower($user->getFirstName().' '.$user->getLastName().' '.$user->getEmail());

            return ('' === $search || str_contains($hay, $search)) && ('' === $status || $profile->getStatus() === $status) && ('' === $accessibility || $profile->getAccessibilityNeed() === $accessibility);
        });
        $pagination = Pagination::fromRequest($request);
        $all = array_values($items);
        $pageItems = array_slice($all, $pagination->offset(), $pagination->perPage);

        return ApiResponse::paginated(array_map([$this, 'format'], $pageItems), $pagination->metadata(count($all)));
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
