<?php
declare(strict_types=1);
namespace App\Module\BetaTest\Controller;
use App\Module\BetaTest\Entity\BugReport;
use App\Module\BetaTest\Repository\BetaCampaignRepository;
use App\Module\BetaTest\Repository\BugReportRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use App\Shared\Persistence\DoctrinePersistence;
use App\Module\BetaTest\Service\BetaAttachmentStorage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/beta/reports', methods: ['POST'])] #[IsGranted('ROLE_USER')]
final class CreateBugReportController extends AbstractController { public function __construct(private readonly BugReportRepository $reports, private readonly BetaCampaignRepository $campaigns, private readonly DoctrinePersistence $persistence, private readonly BetaAttachmentStorage $attachments) {} public function __invoke(Request $request): JsonResponse { $user=$this->getUser(); if (!$user instanceof User) return ApiResponse::error('Authentification requise.',401); $payload=$request->isMethod('POST') && str_contains((string)$request->headers->get('Content-Type'),'multipart/form-data') ? $request->request->all() : JsonPayload::decode($request); $title=trim((string)($payload['title']??'')); $description=trim((string)($payload['description']??'')); if (''===$title || ''===$description) return ApiResponse::error('Le titre et la description sont obligatoires.',422); $campaign=isset($payload['campaignId']) ? $this->campaigns->find((int)$payload['campaignId']) : null; $files=array_values(array_filter($request->files->all('screenshots'),static fn($file)=>$file instanceof UploadedFile)); $report=new BugReport($user,$campaign,$title,$description,isset($payload['expectedBehavior'])?(string)$payload['expectedBehavior']:null,isset($payload['actualBehavior'])?(string)$payload['actualBehavior']:null,in_array($payload['severity']??'normal',['low','normal','high','critical'],true)?(string)$payload['severity']:'normal',isset($payload['pageUrl'])?(string)$payload['pageUrl']:null,$this->attachments->store($files)); $this->persistence->persist($report); $this->persistence->flush(); return ApiResponse::created(['id'=>$report->getId()],'Votre signalement a bien été envoyé.'); } }
