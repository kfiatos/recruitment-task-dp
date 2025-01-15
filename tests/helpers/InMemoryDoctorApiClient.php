<?php

declare(strict_types=1);

namespace App\Tests\helpers;

use App\DTO\DoctorDataDTO;
use App\DTO\DoctorSlotDataDTO;
use App\Service\DoctorsApi\Contract\DoctorApiClientInterface;
use App\Service\DoctorsApi\Contract\SlotApiClientInterface;
use App\ValueObject\DoctorId;

class InMemoryDoctorApiClient implements DoctorApiClientInterface, SlotApiClientInterface
{
    /**
     * @var DoctorDataDTO[]
     */
    public array $doctors = [];

    /**
     * @var array<DoctorSlotDataDTO[]>
     */
    public array $slots = [];

    public function getDoctors(): array
    {
        return $this->doctors;
    }

    public function getDoctorSlots(DoctorId $doctorId): array
    {
        return $this->slots[$doctorId->id] ?? [];
    }
}
