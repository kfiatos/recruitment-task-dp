<?php

declare(strict_types=1);

namespace App;

use App\DTO\DoctorDataDTO;
use App\Entity\Slot;
use App\Exception\CannotGetDoctorsException;
use App\Exception\CannotGetDoctorSlotsException;
use App\Service\DoctorsApi\DoctorsApiGateway;
use App\Service\Processor\DoctorServiceInterface;
use App\Service\Processor\SlotServiceInterface;
use App\Service\Strategy\ErrorReportingStrategyInterface;
use App\ValueObject\DoctorId;
use Psr\Log\LoggerInterface;

class DoctorSlotsSynchronizer
{
    public function __construct(
        protected DoctorsApiGateway $apiGateway,
        protected DoctorServiceInterface $doctorProcessor,
        protected SlotServiceInterface $slotProcessor,
        protected ErrorReportingStrategyInterface $errorReportingStrategy,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function synchronizeDoctorSlots(): void
    {
        $doctors = $this->getDoctors();

        foreach ($doctors as $doctor) {
            $doctorEntity = $this->doctorProcessor->prepareAndSave($doctor);
            foreach ($this->fetchDoctorSlots((int) $doctorEntity->getId()) as $slot) {
                if ($slot) {
                    $this->slotProcessor->persist($slot);
                    continue;
                }
                $this->doctorProcessor->markError($doctorEntity);
            }
        }
    }

    /**
     * @return DoctorDataDTO[]
     */
    protected function getDoctors(): array
    {
        try {
            return $this->apiGateway->getDoctors();
        } catch (CannotGetDoctorsException $e) {
            $this->logger->error('Cannot get doctors from source ', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return array<Slot|null>
     *
     * @throws \DateMalformedStringException
     */
    protected function fetchDoctorSlots(int $doctorId): iterable
    {
        try {
            $doctorSlots = $this->apiGateway->getDoctorSlots(DoctorId::fromInt($doctorId));
            yield from $this->slotProcessor->parseSlots($doctorSlots, $doctorId);
        } catch (CannotGetDoctorSlotsException $e) {
            if ($this->shouldReportErrors()) {
                $this->logger->info('Error fetching slots for doctor:', ['doctorId' => $doctorId]);
                $this->logger->warning('Cannot fetch slots because of:', ['error' => $e->getMessage()]);
            }
            yield null;
        }
    }

    protected function shouldReportErrors(): bool
    {
        return $this->errorReportingStrategy->shouldReport();
    }
}
