<?php
declare(strict_types=1);
namespace App\Module\Admin\BetaTest\Controller;
use App\Module\BetaTest\Repository\BugReportRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/admin/beta-reports', methods: ['GET'])] #[IsGranted('ROLE_ADMIN')]
final class ListBugReportsController extends AbstractController { public function __construct(private readonly BugReportRepository $reports) {} public function __invoke(): JsonResponse { return ApiResponse::success(['items'=>array_map(static fn($r)=>['id'=>$r->getId(),'title'=>$r->getTitle(),'description'=>$r->getDescription(),'severity'=>$r->getSeverity(),'status'=>$r->getStatus(),'reporter'=>$r->getReporter()->getEmail(),'campaign'=>$r->getCampaign()?->getName(),'attachments'=>$r->getAttachments(),'createdAt'=>$r->getCreatedAt()->format(DATE_ATOM)],$this->reports->findBy([],['createdAt'=>'DESC']))]); } }
