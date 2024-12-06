<?php

namespace App\DTO;

final readonly class DoctorSlotDataDTO
{
    public function __construct(public int $doctorId,public string $startDate, public string $endDate)
    {
    }
}