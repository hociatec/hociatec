<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\Client;

use App\Module\Training\Exception\TrainingSessionUnavailableException;
use App\Module\Training\Service\TrainingEnrollmentCheckoutService;
use App\Module\Training\Service\TrainingFormatter;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trainings/enrollments', name: 'api_training_enrollments_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class CreateTrainingEnrollmentController extends AbstractController
{
    public function __construct(
        private readonly TrainingEnrollmentCheckoutService $checkout,
        private readonly TrainingFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
            $result = $this->checkout->enroll(
                $this->currentUser(),
                (int) ($payload['sessionId'] ?? 0),
                is_string($payload['startsAt'] ?? null) ? $payload['startsAt'] : '',
            );
        } catch (TrainingSessionUnavailableException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $exception) {
            return ApiResponse::error('Impossible de finaliser l’inscription à la formation.', Response::HTTP_BAD_REQUEST, [$exception->getMessage()]);
        }

        $data = $this->formatter->formatEnrollment($result->enrollment);
        if (null !== $result->checkoutUrl) {
            $data['checkoutUrl'] = $result->checkoutUrl;
        }

        return ApiResponse::success($data, $result->created ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
