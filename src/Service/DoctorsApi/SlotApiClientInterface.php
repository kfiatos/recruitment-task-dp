<?php

namespace App\Service\DoctorsApi;

use App\DoctorId;
use App\DTO\DoctorDataDTO;
use App\DTO\DoctorSlotDataDTO;

interface SlotApiClientInterface
{
    /**
     * @return DoctorSlotDataDTO[]
     */
    public function getDoctorSlots(DoctorId $doctorId): array;
}