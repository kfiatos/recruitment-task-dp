<?php

declare(strict_types=1);

namespace App\Service\Strategy;

final class DoNotReportErrorsOnSundayStrategy implements ErrorReportingStrategyInterface
{
    public function __construct(private \DateTimeImmutable $now = new \DateTimeImmutable())
    {
    }

    public function shouldReport(): bool
    {
        return 'Sun' !== $this->now->format('D');
    }
}
