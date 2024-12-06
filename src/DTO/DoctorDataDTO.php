<?php

namespace App\DTO;

final readonly class DoctorDataDTO
{
    public function __construct(public int $doctorId, public string $doctorName)
    {
    }
}