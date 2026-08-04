<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Appointment\Service;

use App\Module\Appointment\Application\Service\WorkingDayPayloadMapper;
use PHPUnit\Framework\TestCase;

final class WorkingDayPayloadMapperTest extends TestCase
{
    public function testMapReturnsStructuredWorkingDays(): void
    {
        $mapper = new WorkingDayPayloadMapper();

        $days = $mapper->map([
            'days' => [[
                'dayOfWeek' => '0',
                'isWorkingDay' => true,
                'startTime' => '09:00',
                'endTime' => '18:00',
                'breaks' => [['start' => '12:00', 'end' => '13:00']],
            ]],
        ]);

        self::assertCount(1, $days);
        self::assertSame(0, $days[0]->dayOfWeek);
        self::assertTrue($days[0]->isWorkingDay);
        self::assertSame('09:00', $days[0]->startTime);
        self::assertSame('18:00', $days[0]->endTime);
        self::assertSame([['start' => '12:00', 'end' => '13:00']], $days[0]->breaks);
    }

    public function testMapRejectsInvalidPayloadShapes(): void
    {
        $mapper = new WorkingDayPayloadMapper();

        $cases = [
            [[], 'La configuration doit contenir un tableau "days".'],
            [['days' => [[]]], 'Chaque jour doit définir dayOfWeek et isWorkingDay.'],
            [['days' => [['dayOfWeek' => 0, 'isWorkingDay' => true, 'startTime' => 1200]]], 'Le champ startTime doit être une heure valide.'],
            [['days' => [['dayOfWeek' => 0, 'isWorkingDay' => true, 'endTime' => 1200]]], 'Le champ endTime doit être une heure valide.'],
            [['days' => [['dayOfWeek' => 'x', 'isWorkingDay' => true]]], 'Le jour doit être un entier compris entre 0 et 6.'],
            [['days' => [['dayOfWeek' => 7, 'isWorkingDay' => true]]], 'Chaque jour doit être unique et compris entre 0 et 6.'],
            [['days' => [['dayOfWeek' => 0, 'isWorkingDay' => true], ['dayOfWeek' => 0, 'isWorkingDay' => false]]], 'Chaque jour doit être unique et compris entre 0 et 6.'],
            [['days' => [['dayOfWeek' => 0, 'isWorkingDay' => 'yes']]], 'isWorkingDay doit être un booléen.'],
            [['days' => [['dayOfWeek' => 0, 'isWorkingDay' => true, 'breaks' => 'pause']]], 'Les pauses doivent être un tableau.'],
            [['days' => [['dayOfWeek' => 0, 'isWorkingDay' => true, 'breaks' => [['start' => '12:00']]]]], 'Chaque pause doit définir une heure de début et de fin.'],
        ];

        foreach ($cases as [$payload, $message]) {
            try {
                $mapper->map($payload);
                self::fail('Expected invalid payload to throw.');
            } catch (\InvalidArgumentException $exception) {
                self::assertSame($message, $exception->getMessage());
            }
        }
    }
}
