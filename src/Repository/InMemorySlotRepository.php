<?php

namespace App\Repository;

use App\Entity\Doctor;
use App\Entity\Slot;

class InMemorySlotRepository implements SlotRepositoryInterface
{
    public array $modifiedSlots = [];

    public function __construct(private array $slots)
    {
    }

    public function save(Slot $slot): void
    {
        // TODO: Implement save() method.
    }

}