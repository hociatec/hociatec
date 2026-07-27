<?php
declare(strict_types=1);
namespace App\Module\BetaTest\Controller;
use App\Module\BetaTest\DTO\BetaProfileInput;
use App\Module\BetaTest\Repository\BetaTesterProfileRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use App\Shared\Persistence\DoctrinePersistence;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/beta/profile', methods: ['PUT'])] #[IsGranted('ROLE_USER')]
final class UpdateMyBetaProfileController extends AbstractController { public function __construct(private readonly BetaTesterProfileRepository $profiles, private readonly DoctrinePersistence $persistence, private readonly DtoValidator $validator) {} public function __invoke(Request $request): JsonResponse { $user=$this->getUser(); if (!$user instanceof User) return ApiResponse::error('Authentification requise.',401); $profile=$this->profiles->findOneByUser($user); if(null===$profile)return ApiResponse::error('Profil bêta introuvable.',404); $input=BetaProfileInput::fromArray(JsonPayload::decode($request)); $this->validator->validate($input); $profile->updateFromInput($input); $this->persistence->flush(); return ApiResponse::success([],200,'Profil bêta mis à jour.'); } }
