<?php

namespace App\Service\Processor;

use App\DTO\DoctorSlotDataDTO;
use App\Entity\Slot;
use App\Repository\SlotRepositoryInterface;
use Psr\Log\LoggerInterface;

readonly class SlotService implements SlotServiceInterface
{
    public function __construct(private SlotRepositoryInterface $slotRepository, private LoggerInterface $logger)
    {
    }

    /**
     * @param DoctorSlotDataDTO[] $doctorSlots
     *
     * @return iterable<Slot|null>
     *
     * @throws \DateMalformedStringException
     */
    public function parseSlots(array $doctorSlots, int $doctorId): iterable
    {
        if (empty($doctorSlots)) {
            return [];
        }
        foreach ($doctorSlots as $slot) {
            $start = new \DateTime($slot->startDate);
            $end = new \DateTime($slot->endDate);

            $entity = $this->slotRepository->findOneByDoctorIdAndStartTime($doctorId, $start)
                ?: new Slot($doctorId, $start, $end);

            if ($entity->isStale()) {
                $entity->setEnd($end);
            }

            yield $entity;
        }
    }

    public function persist(Slot $slot): void
    {
        $this->slotRepository->save($slot);
        $this->logger->info('Doctor Slot stored properly', ['doctorId' => $slot->getDoctorId()]);
    }
}
