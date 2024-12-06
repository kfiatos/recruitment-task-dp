<?php

namespace App\Repository;

use App\Entity\Slot;

interface SlotRepositoryInterface
{
    public function save(Slot $slot): void;

}