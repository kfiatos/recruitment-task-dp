<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Slot;

interface SlotRepositoryInterface
{
    public function save(Slot $slot): void;

    public function findOneByDoctorIdAndStartTime(int $doctorId, \DateTimeInterface $startTime): ?Slot;
}
