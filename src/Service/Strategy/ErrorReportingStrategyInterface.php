<?php

declare(strict_types=1);

namespace App\Service\Strategy;

interface ErrorReportingStrategyInterface
{
    public function shouldReport(): bool;
}
