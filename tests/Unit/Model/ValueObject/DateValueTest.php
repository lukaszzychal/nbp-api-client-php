<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Tests\Unit\Model\ValueObject;

use LukaszZychal\NbpApiClient\Model\ValueObject\DateValue;
use PHPUnit\Framework\TestCase;

class DateValueTest extends TestCase
{
    public function testAcceptsValidDate(): void
    {
        $date = new DateValue('2026-03-08');
        $this->assertSame('2026-03-08', $date->getValue());
        $this->assertInstanceOf(\DateTimeImmutable::class, $date->getDateTime());
    }

    public function testValidatesLeapYearCorrectly(): void
    {
        $date = new DateValue('2024-02-29'); // 2024 to przestępny
        $this->assertSame('2024-02-29', $date->getValue());
    }

    public function testThrowsExceptionForInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format');
        new DateValue('08-03-2026');
    }

    public function testThrowsExceptionForNonExistentDay(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format');
        new DateValue('2026-02-31'); // Niepoprawna data (nieistniejący)
    }

    public function testToStringReturnsFormattedDate(): void
    {
        $date = new DateValue('2026-01-01');
        $this->assertSame('2026-01-01', (string) $date);
    }
}
