<?php

declare(strict_types=1);

namespace App\Service\DoctorsApi;

use App\DoctorId;
use App\DTO\DoctorSlotDataDTO;

interface SlotApiClientInterface
{
    /**
     * @return DoctorSlotDataDTO[]
     */
    public function getDoctorSlots(DoctorId $doctorId): array;
}
