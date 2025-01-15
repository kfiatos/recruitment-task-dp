<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="slot")
 */
final class Slot
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @ORM\Column(type="integer")
     */
    private string $id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $doctorId;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTimeInterface $start;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTimeInterface $end;

    /**
     * @ORM\Column(type="datetime")
     */
    private readonly \DateTimeImmutable $createdAt;

    public function __construct(int $doctorId, \DateTimeInterface $start, \DateTimeInterface $end, \DateTimeImmutable $createdAt = new \DateTimeImmutable())
    {
        $this->doctorId = $doctorId;
        $this->start = $start;
        $this->end = $end;
        $this->createdAt = $createdAt;
    }

    public function getStart(): \DateTimeInterface
    {
        return $this->start;
    }

    public function setEnd(\DateTime $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getEnd(): \DateTimeInterface
    {
        return $this->end;
    }

    public function getDoctorId(): int
    {
        return $this->doctorId;
    }

    public function isStale(): bool
    {
        return $this->createdAt < new \DateTime('5 minutes ago');
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
