<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Training\Service;

use App\Module\Training\Application\Exception\TrainingSessionUnavailableException;
use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Shared\Application\Exception\ExternalServiceException;
use App\Tests\Unit\Module\Training\TrainingIntegrationTestCase;

final class TrainingEnrollmentCheckoutCompletionTest extends TrainingIntegrationTestCase
{
    public function testTrainingEnrollmentCheckoutServiceCoversMainBranches(): void
    {
        $em = $this->entityManager();
        [, $session, $user] = $this->persistTrainingGraph($em);
        $service = $this->checkoutService($em);

        $created = $service->enroll($user, (int) $session->getId(), self::ENROLLMENT_START);
        self::assertTrue($created->created);
        self::assertSame(TrainingEnrollment::STATUS_CONFIRMED, $created->enrollment->getStatus());

        $created->enrollment->setStatus(TrainingEnrollment::STATUS_CONFIRMED);
        $em->flush();
        $existing = $service->enroll($user, (int) $session->getId(), self::ENROLLMENT_START);
        self::assertFalse($existing->created);

        $session->setCapacity(1);
        $created->enrollment->setStatus(TrainingEnrollment::STATUS_CANCELLED);
        $busy = $this->user('busy@example.com');
        $em->persist($busy);
        $blocking = (new TrainingEnrollment($session, $busy, 0))
            ->setStatus(TrainingEnrollment::STATUS_CONFIRMED)
            ->setScheduledStartsAt(new \DateTimeImmutable(self::ENROLLMENT_START))
            ->setScheduledEndsAt(new \DateTimeImmutable(self::ENROLLMENT_END));
        $em->persist($blocking);
        $em->flush();
        try {
            $service->enroll($user, (int) $session->getId(), self::ENROLLMENT_START);
            self::fail('Expected full session exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Cette session est complète.', $exception->getMessage());
        }

        $failureEm = $this->entityManager();
        [, $failureSession, $failureUser] = $this->persistTrainingGraph($failureEm);
        $failureService = $this->checkoutService($failureEm);

        try {
            $failureService->enroll($failureUser, (int) $failureSession->getId(), 'not a date');
            self::fail('Expected invalid start exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Créneau invalide.', $exception->getMessage());
        }

        $failureEm = $this->entityManager();
        [, $failureSession, $failureUser] = $this->persistTrainingGraph($failureEm);
        $failureService = $this->checkoutService($failureEm);

        try {
            $failureService->enroll($failureUser, (int) $failureSession->getId(), '');
            self::fail('Expected blank start exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Choisissez une date et une heure de début.', $exception->getMessage());
        }

        $failureEm = $this->entityManager();
        [, , $failureUser] = $this->persistTrainingGraph($failureEm);
        try {
            $this->checkoutService($failureEm)->enroll($failureUser, 0, self::ENROLLMENT_START);
            self::fail('Expected missing session exception.');
        } catch (TrainingSessionUnavailableException) {
            self::assertTrue(true);
        }

        $paidEm = $this->entityManager();
        [, , $paidUser] = $this->persistTrainingGraph($paidEm);
        $paidService = $this->checkoutService($paidEm);
        $paidTraining = (new Training('Paid', 'paid', 60, 5000))->setIsActive(true);
        $paidSession = new TrainingSession($paidTraining, 'remote', new \DateTimeImmutable(self::PAID_SESSION_START), new \DateTimeImmutable(self::PAID_SESSION_END), 2);
        $paidEm->persist($paidTraining);
        $paidEm->persist($paidSession);
        $paidEm->flush();

        try {
            $paidService->enroll($paidUser, (int) $paidSession->getId(), self::PAID_ENROLLMENT_START);
            self::fail('Expected unavailable Stripe exception.');
        } catch (ExternalServiceException) {
            self::assertTrue(true);
        }
    }
}
