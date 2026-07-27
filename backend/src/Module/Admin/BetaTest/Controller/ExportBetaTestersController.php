<?php
declare(strict_types=1);
namespace App\Module\Admin\BetaTest\Controller;
use App\Module\BetaTest\Repository\BetaTesterProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/admin/beta-testers/export', methods: ['GET'])] #[IsGranted('ROLE_ADMIN')]
final class ExportBetaTestersController { public function __construct(private readonly BetaTesterProfileRepository $profiles) {} public function __invoke(): Response { $stream=fopen('php://temp','w+'); fputcsv($stream,['Prénom','Nom','E-mail','Statut','Accessibilité','Disponibilités','Appareils','Navigateurs','Types de tests','Créé le'],';'); foreach($this->profiles->findBy([],['createdAt'=>'DESC']) as $p) { $u=$p->getUser(); fputcsv($stream,[$u->getFirstName(),$u->getLastName(),$u->getEmail(),$p->getStatus(),$p->getAccessibilityNeed(),implode(', ',$p->getAvailability()),implode(', ',$p->getDevices()),implode(', ',$p->getBrowsers()),implode(', ',$p->getTestingTypes()),$p->getCreatedAt()->format(DATE_ATOM)],';'); } rewind($stream); $content=stream_get_contents($stream); fclose($stream); return new Response($content,200,['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="beta-testeurs.csv"']); } }
