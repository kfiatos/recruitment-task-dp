<?php
namespace App;
readonly class DoctorId
{
    private function __construct(public string $id)
    {
    }

    public static function fromInt(int $id): self
    {
        return new self((string) $id);
    }

}