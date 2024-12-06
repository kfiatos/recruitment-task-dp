<?php

namespace App\Service\DoctorsApi;

use App\DoctorId;
use App\DTO\DoctorDataDTO;
use App\DTO\DoctorSlotDataDTO;

interface DoctorApiClientInterface
{
    /**
     * @return DoctorDataDTO[]
     */
    public function getDoctors(): array;
}