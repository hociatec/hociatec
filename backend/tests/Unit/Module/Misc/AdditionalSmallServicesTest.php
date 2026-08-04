<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Marketing\Application\Notification\EmailTemplateRenderer;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderEvent;
use App\Module\Order\Application\Workflow\OrderEventLogger;
use App\Module\Order\Application\Persistence\OrderEventPersistence;
use App\Module\Rating\Infrastructure\Repository\ProductRatingRepository;
use App\Module\Rating\Application\Writer\ProductReviewStatsUpdater;
use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Module\Training\Application\Mapper\TrainingSlotValidator;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;

final class AdditionalSmallServicesTest extends TestCase
{
    public function testProductReviewStatsUpdaterAndEmailTemplateRenderer(): void
    {
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 5, new Category('Phones', 'phones'));
        $ratings = $this->createMock(ProductRatingRepository::class);
        $ratings->expects(self::once())->method('getStatsForProduct')->with($product)->willReturn(['count' => 3, 'average' => 4.5]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with($product);
        $entityManager->expects(self::once())->method('flush');
        (new ProductReviewStatsUpdater($ratings, new DoctrineUnitOfWork($entityManager)))->refresh($product);
        self::assertSame(3, $product->getReviewsCount());
        self::assertSame(4.5, $product->getReviewsAverage());

        $template = new EmailTemplate('Order', 'order-created', 'order_created', 'Sujet {{name}}', '<p>{{name}}</p>', 'Bonjour {{name}}');
        $templates = $this->createMock(EmailTemplateRepository::class);
        $templates->expects(self::exactly(2))->method('findActiveOneByScenarioKey')->with('order_created')->willReturnOnConsecutiveCalls($template, null);
        $renderer = new EmailTemplateRenderer($templates);
        $rendered = $renderer->renderScenario('order_created', ['name' => 'Ada'], ['subject' => 'Fallback', 'html' => 'F', 'text' => 'T']);
        self::assertSame('Sujet Ada', $rendered['subject']);
        self::assertSame('<p>Ada</p>', $rendered['html']);
        self::assertSame('Bonjour Ada', $rendered['text']);
        self::assertSame('Fallback', $renderer->renderScenario('order_created', ['name' => 'Ada'], ['subject' => 'Fallback', 'html' => 'F', 'text' => 'T'])['subject']);
    }

    public function testTrainingSlotValidatorAndOrderEventLogger(): void
    {
        $training = new Training('Formation', 'formation', 60, 1000);
        $session = new TrainingSession($training, 'onsite', new \DateTimeImmutable('2026-08-03T08:00:00+00:00'), new \DateTimeImmutable('2026-08-03T18:00:00+00:00'), 10);
        $session->setDailyStartTime(new \DateTimeImmutable('09:00'));
        $session->setDailyEndTime(new \DateTimeImmutable('17:00'));
        $session->setIncludeWeekends(false);
        $validator = new TrainingSlotValidator();
        $validator->validate($session, new \DateTimeImmutable('2026-08-03T10:00:00+00:00'), new \DateTimeImmutable('2026-08-03T12:00:00+00:00'));

        try {
            $validator->validate($session, new \DateTimeImmutable('2026-08-02T10:00:00+00:00'), new \DateTimeImmutable('2026-08-02T12:00:00+00:00'));
            self::fail('Expected slot bounds exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('période de réservation', $exception->getMessage());
        }

        $user = $this->user();
        $this->setId($user, 9);
        $order = new Order('ORD-1', $user);
        $logEntityManager = $this->createMock(EntityManagerInterface::class);
        $logEntityManager->expects(self::once())->method('persist')->with(self::callback(static fn (object $event): bool => $event instanceof OrderEvent && 'Ada Lovelace' === $event->getActorName()));
        $logEntityManager->expects(self::once())->method('flush');
        $logger = new OrderEventLogger(new OrderEventPersistence($logEntityManager));
        $logger->log($order, $user, 'created', 'ok');
    }

    private function user(): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
