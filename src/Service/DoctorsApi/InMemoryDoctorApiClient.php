<?php

declare(strict_types=1);

namespace App\Service\DoctorsApi;

use App\DoctorId;
use App\DTO\DoctorDataDTO;
use App\DTO\DoctorSlotDataDTO;

class InMemoryDoctorApiClient implements DoctorApiClientInterface, SlotApiClientInterface
{
    /**
     * @var DoctorDataDTO[]
     */
    public array $doctors = [];

    /**
     * @var array<array<DoctorSlotDataDTO>>
     */
    public array $slots = [];

    public function getDoctors(): array
    {
        return $this->doctors;
    }

    public function getDoctorSlots(DoctorId $doctorId): array
    {
        return $this->slots[$doctorId->id];
    }
}
