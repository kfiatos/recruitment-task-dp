<?php

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
     * @var DoctorSlotDataDTO[]
     */
    public array $slots = [];

    public function getDoctors(): array
    {
        return $this->doctors;
    }

    public function getDoctorSlots(DoctorId $doctorId): array
    {
        return $this->slots;
    }

}