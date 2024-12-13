<?php

namespace App\Service\Processor;

use App\DTO\DoctorSlotDataDTO;
use App\Entity\Slot;

interface SlotServiceInterface
{
    /**
     * @param DoctorSlotDataDTO[] $doctorSlots
     *
     * @return iterable<Slot|null>
     */
    public function parseSlots(array $doctorSlots, int $doctorId): iterable;

    public function persist(Slot $slot): void;
}
