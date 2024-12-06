<?php

namespace unit;

use App\Service\Strategy\DoNotReportErrorsOnSundayStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DoNotReportErrorsOnSundayStrategyTest extends TestCase
{
    public function testDoesNotReportForSunday(): void
    {
        $strategy = new DoNotReportErrorsOnSundayStrategy(new \DateTimeImmutable('Sunday'));
        $this->assertFalse($strategy->shouldReport());
    }

    #[DataProvider('provideDays')]
    public function testReportsForDaysOtherThanSunday(string $day): void
    {
        $strategy = new DoNotReportErrorsOnSundayStrategy(new \DateTimeImmutable($day));
        $this->assertTrue($strategy->shouldReport());
    }


    public static function provideDays(): \Generator
    {
        yield ['Monday'];
        yield ['Tuesday'];
        yield ['Wednesday'];
        yield ['Thursday'];
        yield ['Friday'];
        yield ['Saturday'];
    }
}
