<?php

namespace App\Service\Processor;

use App\DTO\DoctorDataDTO;
use App\Entity\Doctor;

interface DoctorServiceInterface
{
    public function prepareAndSave(DoctorDataDTO $doctorDto): Doctor;

    public function persist(Doctor $doctor): void;

    public function markError(Doctor $doctor): void;
}
