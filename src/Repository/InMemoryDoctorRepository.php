<?php

namespace App\Repository;

use App\Entity\Doctor;

class InMemoryDoctorRepository implements DoctorRepositoryInterface
{
    public array $modifiedDoctors = [];

    public function __construct(private array $doctors)
    {
    }

    public function find($id): ?Doctor
    {
        return $this->doctors[$id] ?? null;
    }

    public function save(Doctor $doctor): void
    {
        $this->modifiedDoctors[] = $doctor;
    }

}