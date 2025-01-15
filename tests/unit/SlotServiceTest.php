<?php

namespace App\Tests\unit;

use App\DTO\DoctorSlotDataDTO;
use App\Entity\Slot;
use App\Repository\SlotRepositoryInterface;
use App\Service\Processor\SlotService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SlotServiceTest extends TestCase
{
    public function testParsingSlotSetsEndDateFromExternalSourceWhenSlotIsStale(): void
    {
        $slotRepository = $this->createMock(SlotRepositoryInterface::class);
        $doctorId = 1;
        $startDate = new \DateTime();
        $endDate = new \DateTime('+12 hours');
        $slotDto = new DoctorSlotDataDTO($doctorId, $startDate->format(DATE_ATOM), $endDate->format(DATE_ATOM));

        $slotEntity = new Slot($doctorId, new \DateTime(), new \DateTime('+ 30 minutes'), new \DateTimeImmutable('10 minutes ago'));
        $slotRepository->method('findOneByDoctorIdAndStartTime')->willReturn($slotEntity);

        $slotService = new SlotService($slotRepository, $this->createMock(LoggerInterface::class));
        $data = $slotService->parseSlots([$slotDto], $doctorId);
        foreach ($data as $slot) {
            $this->assertEquals($endDate->format(DATE_ATOM), $slot?->getEnd()->format(DATE_ATOM));
        }
    }
}
