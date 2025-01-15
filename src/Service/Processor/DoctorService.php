<?php

namespace App\Service\Processor;

use App\DTO\DoctorDataDTO;
use App\Entity\Doctor;
use App\Normalizer\DoctorNameNormalizer;
use App\Repository\DoctorRepositoryInterface;
use Psr\Log\LoggerInterface;

readonly class DoctorService implements DoctorServiceInterface
{
    public function __construct(private DoctorRepositoryInterface $doctorRepository, private LoggerInterface $logger)
    {
    }

    public function prepareAndSave(DoctorDataDTO $doctorDto): Doctor
    {
        $name = DoctorNameNormalizer::normalize($doctorDto->doctorName ?? '');
        $entity = $this->doctorRepository->find($doctorDto->doctorId)
            ??
            new Doctor((string) $doctorDto->doctorId, $name)
        ;
        $entity->setName($name);
        $entity->clearError();
        $this->persist($entity);

        return $entity;
    }

    public function persist(Doctor $doctor): void
    {
        $this->doctorRepository->save($doctor);
        $this->logger->info('Doctor stored properly', ['doctorId' => $doctor->getId()]);
    }

    public function markError(Doctor $doctor): void
    {
        $doctor->markError();
        $this->persist($doctor);
    }
}
