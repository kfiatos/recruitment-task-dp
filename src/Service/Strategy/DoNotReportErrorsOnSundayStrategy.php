<?php

namespace App\Service\Strategy;

final class DoNotReportErrorsOnSundayStrategy implements ErrorReportingStrategyInterface
{
    public function shouldReport(): bool
    {
        return (new \DateTime())->format('D') !== 'Sun';
    }
}