<?php

declare(strict_types=1);

namespace App\Service\DoctorsApi\Contract;

use App\DTO\DoctorDataDTO;

interface DoctorApiClientInterface
{
    /**
     * @return DoctorDataDTO[]
     */
    public function getDoctors(): array;
}
