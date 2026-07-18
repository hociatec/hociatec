<?php

declare(strict_types=1);

namespace App\Module\Marketing\Service;

use App\Module\Marketing\Entity\EmailCampaign;
use App\Module\Marketing\Entity\EmailTemplate;
use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderItem;
use App\Module\Rating\Entity\ProductRating;
use App\Module\User\Entity\User;
use App\Shared\Http\OvhRoundcubeMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class MarketingCampaignService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly OvhRoundcubeMailer $ovhRoundcubeMailer,
        private readonly EmailTemplateScenarioProvider $scenarioProvider,
    ) {
    }

    /**
     * @return array<string, array{label: string, description: string, defaults: array<string, int|string|bool>}>
     */
    public function getSegmentDefinitions(): array
    {
        return $this->scenarioProvider->getCampaignScenarioDefinitions();
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array{count: int, recipients: list<array{id: int, email: string, fullName: string}>, description: string}
     */
    public function previewAudience(string $segmentKey, array $criteria): array
    {
        $users = $this->resolveRecipients($segmentKey, $criteria, 10);

        return [
            'count' => $this->countRecipients($segmentKey, $criteria),
            'recipients' => array_map(
                static fn (User $user) => [
                    'id' => (int) $user->getId(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getFullName(),
                ],
                $users,
            ),
            'description' => $this->buildAudienceDescription($segmentKey, $criteria),
        ];
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function sendCampaign(
        string $name,
        string $segmentKey,
        array $criteria,
        string $subject,
        string $htmlBody,
        ?string $textBody,
        ?EmailTemplate $template,
        ?string $createdByEmail,
    ): EmailCampaign {
        $users = $this->resolveRecipients($segmentKey, $criteria);
        $from = $_ENV['MAILER_FROM'] ?? 'no-reply@localhost';
        $frontendUrl = rtrim((string) ($_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:5173'), '/');

        foreach ($users as $user) {
            $context = $this->buildContext($user, $frontendUrl);
            $renderedSubject = $this->renderTemplate($subject, $context, false);
            $renderedHtml = $this->renderTemplate($htmlBody, $context, true);
            $renderedText = $this->renderTemplate($textBody ?: strip_tags($htmlBody), $context, false);

            try {
                $this->ovhRoundcubeMailer->send(
                    $user->getEmail(),
                    $renderedSubject,
                    $renderedText,
                );
            } catch (\Throwable) {
                $email = (new Email())
                    ->from(new Address($from, 'Hociatec'))
                    ->to(new Address($user->getEmail(), $user->getFullName()))
                    ->subject($renderedSubject)
                    ->html($renderedHtml)
                    ->text($renderedText);

                $this->mailer->send($email);
            }
        }

        $campaign = new EmailCampaign(
            $name,
            $segmentKey,
            $criteria,
            $subject,
            $htmlBody,
            $textBody,
            count($users),
            $createdByEmail,
            $template,
        );

        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        return $campaign;
    }

    /**
     * @param array<string, mixed> $criteria
     * @return list<User>
     */
    public function resolveRecipients(string $segmentKey, array $criteria, ?int $limit = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT u')
            ->from(User::class, 'u')
            ->andWhere('u.isVerified = :verified')
            ->setParameter('verified', true)
            ->orderBy('u.createdAt', 'DESC');

        switch ($segmentKey) {
            case 'all_verified_users':
                break;

            case 'recent_verified_users':
                $registeredDays = max(7, (int) ($criteria['registeredDays'] ?? 30));
                $threshold = new \DateTimeImmutable(sprintf('-%d days', $registeredDays));
                $qb
                    ->andWhere('u.createdAt >= :registeredThreshold')
                    ->setParameter('registeredThreshold', $threshold);
                break;

            case 'customers_with_orders':
                $minimumOrders = max(1, (int) ($criteria['minimumOrders'] ?? 1));
                $qb
                    ->join(Order::class, 'o', 'WITH', 'o.user = u')
                    ->groupBy('u.id')
                    ->having('COUNT(o.id) >= :minimumOrders')
                    ->setParameter('minimumOrders', $minimumOrders);
                break;

            case 'loyal_customers':
                $minimumOrders = max(2, (int) ($criteria['minimumOrders'] ?? 3));
                $qb
                    ->join(Order::class, 'o', 'WITH', 'o.user = u')
                    ->groupBy('u.id')
                    ->having('COUNT(o.id) >= :minimumOrders')
                    ->setParameter('minimumOrders', $minimumOrders);
                break;

            case 'single_order_customers':
                $qb
                    ->join(Order::class, 'o', 'WITH', 'o.user = u')
                    ->groupBy('u.id')
                    ->having('COUNT(o.id) = 1');
                break;

            case 'recent_customers':
                $recentDays = max(7, (int) ($criteria['recentDays'] ?? 30));
                $threshold = new \DateTimeImmutable(sprintf('-%d days', $recentDays));
                $qb
                    ->join(Order::class, 'o', 'WITH', 'o.user = u')
                    ->andWhere('o.createdAt >= :recentThreshold')
                    ->setParameter('recentThreshold', $threshold);
                break;

            case 'high_value_customers':
                $minimumTotalCents = max(1000, (int) ($criteria['minimumTotalCents'] ?? 50000));
                $qb
                    ->join(Order::class, 'o', 'WITH', 'o.user = u')
                    ->groupBy('u.id')
                    ->having('SUM(o.totalPriceCents) >= :minimumTotalCents')
                    ->setParameter('minimumTotalCents', $minimumTotalCents);
                break;

            case 'customers_without_review':
                $qb
                    ->join(Order::class, 'o', 'WITH', 'o.user = u')
                    ->join(OrderItem::class, 'oi', 'WITH', 'oi.order = o')
                    ->leftJoin(ProductRating::class, 'r', 'WITH', 'r.orderItem = oi')
                    ->andWhere('r.id IS NULL');
                break;

            case 'customers_with_pending_reviews':
                $minimumPendingReviews = max(1, (int) ($criteria['minimumPendingReviews'] ?? 2));
                $qb
                    ->join(Order::class, 'o', 'WITH', 'o.user = u')
                    ->join(OrderItem::class, 'oi', 'WITH', 'oi.order = o')
                    ->leftJoin(ProductRating::class, 'r', 'WITH', 'r.orderItem = oi')
                    ->andWhere('r.id IS NULL')
                    ->groupBy('u.id')
                    ->having('COUNT(DISTINCT oi.id) >= :minimumPendingReviews')
                    ->setParameter('minimumPendingReviews', $minimumPendingReviews);
                break;

            case 'inactive_customers':
                $inactiveDays = max(30, (int) ($criteria['inactiveDays'] ?? 90));
                $threshold = new \DateTimeImmutable(sprintf('-%d days', $inactiveDays));
                $qb
                    ->join(Order::class, 'o', 'WITH', 'o.user = u')
                    ->groupBy('u.id')
                    ->having('MAX(o.createdAt) < :inactiveThreshold')
                    ->setParameter('inactiveThreshold', $threshold);
                break;

            case 'verified_without_orders':
                $qb
                    ->leftJoin(Order::class, 'o', 'WITH', 'o.user = u')
                    ->andWhere('o.id IS NULL');
                break;

            case 'verified_without_orders_recent':
                $registeredDays = max(7, (int) ($criteria['registeredDays'] ?? 30));
                $threshold = new \DateTimeImmutable(sprintf('-%d days', $registeredDays));
                $qb
                    ->leftJoin(Order::class, 'o', 'WITH', 'o.user = u')
                    ->andWhere('o.id IS NULL')
                    ->andWhere('u.createdAt >= :registeredThreshold')
                    ->setParameter('registeredThreshold', $threshold);
                break;

            default:
                throw new \InvalidArgumentException('Segment marketing inconnu.');
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        /** @var list<User> $users */
        $users = $qb->getQuery()->getResult();

        return $users;
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function countRecipients(string $segmentKey, array $criteria): int
    {
        return count($this->resolveRecipients($segmentKey, $criteria));
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function buildAudienceDescription(string $segmentKey, array $criteria): string
    {
        return match ($segmentKey) {
            'all_verified_users' => 'Tous les comptes vérifiés.',
            'recent_verified_users' => sprintf('Comptes vérifiés créés depuis moins de %d jours.', max(7, (int) ($criteria['registeredDays'] ?? 30))),
            'customers_with_orders' => sprintf('Clients avec au moins %d commande(s).', max(1, (int) ($criteria['minimumOrders'] ?? 1))),
            'loyal_customers' => sprintf('Clients avec au moins %d commandes.', max(2, (int) ($criteria['minimumOrders'] ?? 3))),
            'single_order_customers' => 'Clients ayant exactement une commande.',
            'recent_customers' => sprintf('Clients ayant commandé au cours des %d derniers jours.', max(7, (int) ($criteria['recentDays'] ?? 30))),
            'high_value_customers' => sprintf('Clients avec au moins %.2f EUR de commandes cumulées.', max(1000, (int) ($criteria['minimumTotalCents'] ?? 50000)) / 100),
            'customers_without_review' => 'Clients ayant commandé mais sans avis publié sur au moins un article.',
            'customers_with_pending_reviews' => sprintf('Clients avec au moins %d avis en attente.', max(1, (int) ($criteria['minimumPendingReviews'] ?? 2))),
            'inactive_customers' => sprintf('Clients inactifs depuis plus de %d jours.', max(30, (int) ($criteria['inactiveDays'] ?? 90))),
            'verified_without_orders' => 'Comptes vérifiés sans aucune commande.',
            'verified_without_orders_recent' => sprintf('Comptes vérifiés créés depuis moins de %d jours et sans commande.', max(7, (int) ($criteria['registeredDays'] ?? 30))),
            default => 'Audience marketing.',
        };
    }

    /**
     * @return array<string, string>
     */
    private function buildContext(User $user, string $frontendUrl): array
    {
        $orderStats = $this->entityManager->createQueryBuilder()
            ->select('COUNT(o.id) AS ordersCount', 'MAX(o.createdAt) AS lastOrderAt', 'COALESCE(SUM(o.totalPriceCents), 0) AS totalSpentCents')
            ->from(Order::class, 'o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleResult();

        $lastOrder = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $pendingReviewsCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT oi.id)')
            ->from(Order::class, 'o')
            ->join(OrderItem::class, 'oi', 'WITH', 'oi.order = o')
            ->leftJoin(ProductRating::class, 'r', 'WITH', 'r.orderItem = oi')
            ->andWhere('o.user = :user')
            ->andWhere('r.id IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'full_name' => $user->getFullName(),
            'email' => $user->getEmail(),
            'order_count' => (string) ((int) ($orderStats['ordersCount'] ?? 0)),
            'total_spent_eur' => number_format(((int) ($orderStats['totalSpentCents'] ?? 0)) / 100, 2, ',', ' '),
            'last_order_date' => $orderStats['lastOrderAt'] instanceof \DateTimeInterface
                ? $orderStats['lastOrderAt']->format('d/m/Y')
                : '',
            'last_order_number' => $lastOrder instanceof Order ? $lastOrder->getNumber() : '',
            'days_since_last_order' => $orderStats['lastOrderAt'] instanceof \DateTimeInterface
                ? (string) (new \DateTimeImmutable())->diff(\DateTimeImmutable::createFromInterface($orderStats['lastOrderAt']))->days
                : '',
            'pending_reviews_count' => (string) $pendingReviewsCount,
            'app_frontend_url' => $frontendUrl,
        ];
    }

    /**
     * @param array<string, string> $context
     */
    public function renderTemplate(?string $content, array $context, bool $preserveHtml): string
    {
        $content ??= '';
        $pairs = [];
        foreach ($context as $key => $value) {
            $pairs['{{' . $key . '}}'] = $value;
        }

        $rendered = strtr($content, $pairs);

        if ($preserveHtml) {
            return $rendered;
        }

        return trim(html_entity_decode(strip_tags($rendered)));
    }
}
