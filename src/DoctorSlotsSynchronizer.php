<?php

declare(strict_types=1);

namespace App;

use App\DTO\DoctorDataDTO;
use App\DTO\DoctorSlotDataDTO;
use App\Entity\Doctor;
use App\Entity\Slot;
use App\Normalizer\DoctorNameNormalizer;
use App\Repository\DoctorRepositoryInterface;
use App\Repository\SlotRepositoryInterface;
use App\Service\DoctorsApi\DoctorsApiGateway;
use App\Service\Strategy\ErrorReportingStrategyInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class DoctorSlotsSynchronizer
{
    protected Logger $logger;

    public function __construct(
        protected DoctorRepositoryInterface $doctorRepository,
        protected SlotRepositoryInterface $slotRepository,
        protected DoctorsApiGateway $apiGateway,
        protected ErrorReportingStrategyInterface $errorReportingStrategy,
        string $logFile = 'php://stderr',
    ) {
        $this->logger = new Logger('logger', [new StreamHandler($logFile)]);
    }

    /**
     * @throws \JsonException
     */
    public function synchronizeDoctorSlots(): void
    {
        $doctors = $this->getDoctors();

        foreach ($doctors as $doctor) {
            $name = DoctorNameNormalizer::normalize($doctor->doctorName ?? '');
            $entity = $this->doctorRepository->find($doctor->doctorId)
                ??
                new Doctor((string) $doctor->doctorId, $name)
            ;
            $entity->setName($name);
            $entity->clearError();
            $this->doctorRepository->save($entity);

            foreach ($this->fetchDoctorSlots($doctor->doctorId) as $slot) {
                if (false === $slot) {
                    $entity->markError();
                    $this->doctorRepository->save($entity);
                } else {
                    $this->slotRepository->save($slot);
                }
            }
        }
    }

    /**
     * @return DoctorDataDTO[]
     */
    protected function getDoctors(): array
    {
        return $this->apiGateway->fetchDoctors();
    }

    /**
     * @return array<Slot|false>
     *
     * @throws \DateMalformedStringException
     */
    protected function fetchDoctorSlots(int $id): iterable
    {
        try {
            $doctorSlots = $this->getSlots($id);
            yield from $this->parseSlots($doctorSlots, $id);
        } catch (\JsonException) {
            if ($this->shouldReportErrors()) {
                $this->logger->info('Error fetching slots for doctor', ['doctorId' => $id]);
            }
            yield false;
        }
    }

    /**
     * @return DoctorSlotDataDTO[]
     */
    protected function getSlots(int $id): array
    {
        return $this->apiGateway->fetchDoctorSlots(DoctorId::fromInt($id));
    }

    /**
     * @param DoctorSlotDataDTO[] $doctorSlots
     *
     * @return Slot[]
     *
     * @throws \DateMalformedStringException
     */
    protected function parseSlots(array $doctorSlots, int $id): iterable
    {
        /** @var DoctorSlotDataDTO $slot */
        foreach ($doctorSlots as $slot) {
            $start = new \DateTime($slot->startDate);
            $end = new \DateTime($slot->endDate);

            $entity = $this->slotRepository->findOneByDoctorIdAndStartTime($id, $start)
                ?: new Slot($id, $start, $end);

            if ($entity->isStale()) {
                $entity->setEnd($end);
            }

            yield $entity;
        }
    }

    protected function shouldReportErrors(): bool
    {
        return $this->errorReportingStrategy->shouldReport();
    }
}
