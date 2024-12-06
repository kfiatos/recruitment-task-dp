<?php

namespace App\Service\DoctorsApi;

use App\DoctorId;
use App\DTO\DoctorDataDTO;

readonly class DoctorsApiGateway
{
    public function __construct(private DoctorApiClientInterface $doctorApiClient, private SlotApiClientInterface $slotApiClient)
    {
    }

    /**
     * @return DoctorDataDTO[]
     */
    public function fetchDoctors(): array
    {
        return $this->doctorApiClient->getDoctors();
    }

    /**
     * @return DoctorDataDTO[]
     */
    public function fetchDoctorSlots(DoctorId $doctorId): array
    {
        return $this->slotApiClient->getDoctorSlots($doctorId);
    }

}