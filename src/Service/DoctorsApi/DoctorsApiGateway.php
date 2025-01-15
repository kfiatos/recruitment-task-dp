<?php

declare(strict_types=1);

namespace App\Service\DoctorsApi;

use App\DTO\DoctorDataDTO;
use App\DTO\DoctorSlotDataDTO;
use App\Exception\CannotGetDoctorsException;
use App\Exception\CannotGetDoctorSlotsException;
use App\Service\DoctorsApi\Contract\DoctorApiClientInterface;
use App\Service\DoctorsApi\Contract\DoctorsGatewayInterface;
use App\Service\DoctorsApi\Contract\SlotApiClientInterface;
use App\ValueObject\DoctorId;

readonly class DoctorsApiGateway implements DoctorsGatewayInterface
{
    public function __construct(private DoctorApiClientInterface $doctorApiClient, private SlotApiClientInterface $slotApiClient)
    {
    }

    /**
     * @return DoctorDataDTO[]
     *
     * @throws CannotGetDoctorsException
     */
    public function getDoctors(): array
    {
        try {
            return $this->doctorApiClient->getDoctors();
        } catch (\Exception $e) {
            throw new CannotGetDoctorsException($e->getMessage());
        }
    }

    /**
     * @return DoctorSlotDataDTO[]
     *
     * @throws CannotGetDoctorSlotsException
     */
    public function getDoctorSlots(DoctorId $doctorId): array
    {
        try {
            return $this->slotApiClient->getDoctorSlots($doctorId);
        } catch (\Exception $e) {
            throw new CannotGetDoctorSlotsException($e->getMessage());
        }
    }
}
