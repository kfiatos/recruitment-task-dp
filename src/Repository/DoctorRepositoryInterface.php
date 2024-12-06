<?php

namespace App\Repository;

use App\Entity\Doctor;

interface DoctorRepositoryInterface
{

    public function find($id);

    public function save(Doctor $doctor): void;
}