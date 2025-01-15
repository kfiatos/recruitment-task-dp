<?php

declare(strict_types=1);

namespace App\Service\DoctorsApi\Contract;

use App\DTO\DoctorSlotDataDTO;
use App\ValueObject\DoctorId;

interface SlotApiClientInterface
{
    /**
     * @return DoctorSlotDataDTO[]
     */
    public function getDoctorSlots(DoctorId $doctorId): array;
}
