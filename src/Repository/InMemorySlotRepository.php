<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Slot;

class InMemorySlotRepository implements SlotRepositoryInterface
{
    /**
     * @param array<array<Slot>> $slots
     */
    public function __construct(public array $slots = [])
    {
    }

    public function save(Slot $slot): void
    {
        $this->slots[$slot->getDoctorId()][] = $slot;
    }

    public function findOneByDoctorIdAndStartTime(int $doctorId, \DateTimeInterface $startTime): ?Slot
    {
        if (!isset($this->slots[$doctorId])) {
            return null;
        }

        $result = array_filter($this->slots[$doctorId], function (Slot $slot) use ($startTime) {
            return $slot->getStart() == $startTime;
        });

        return 0 === count($result) ? null : $result[0];
    }
}
