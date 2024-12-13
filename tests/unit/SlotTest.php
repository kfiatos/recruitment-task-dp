<?php

namespace App\Tests\unit;

use App\Entity\Slot;
use PHPUnit\Framework\TestCase;

class SlotTest extends TestCase
{
    public function testSlotIsStale(): void
    {
        $slot = new Slot(
            1,
            new \DateTime('2024-01-01 12:00:00'),
            new \DateTime('2025-01-01 12:30:00'),
            new \DateTimeImmutable('6 minutes ago')
        );
        $this->assertTrue($slot->isStale());
    }
}
