<?php

namespace App\Service\Strategy;

interface ErrorReportingStrategyInterface
{
    public function shouldReport(): bool;
}