<?php

declare(strict_types=1);

namespace App\Tests\helpers;

use App\Entity\Doctor;
use App\Repository\DoctorRepositoryInterface;

class InMemoryDoctorRepository implements DoctorRepositoryInterface
{
    /**
     * @param Doctor[] $doctors
     */
    public function __construct(public array $doctors = [])
    {
    }

    public function find(int $id): ?Doctor
    {
        return $this->doctors[$id] ?? null;
    }

    public function save(Doctor $doctor): void
    {
        $this->doctors[$doctor->getId()] = $doctor;
    }
}
