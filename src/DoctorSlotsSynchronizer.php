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
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use JsonException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class DoctorSlotsSynchronizer
{
    protected const ENDPOINT = 'http://unit-testing-api:2137/api/doctors';
    protected const USERNAME = 'docplanner';
    protected const PASSWORD = 'docplanner';

    protected EntityRepository $repository;
    protected EntityRepository $slots;
    protected DoctorsApiGateway $apiGateway;

    protected Logger $logger;

    protected DoctorRepositoryInterface $doctorRepository;
    protected SlotRepositoryInterface $slotRepository;

    public function __construct(
        DoctorRepositoryInterface $doctorRepository,
        SlotRepositoryInterface $slotRepository,
        DoctorsApiGateway $apiGateway,
        string $logFile = 'php://stderr'
    )
    {
        $this->apiGateway = $apiGateway;
        $this->doctorRepository = $doctorRepository;
        $this->slotRepository = $slotRepository;
        $this->logger = new Logger('logger', [new StreamHandler($logFile)]);
    }

    /**
     * @throws JsonException
     */
    public function synchronizeDoctorSlots(): void
    {
        $doctors = $this->getDoctors();

        foreach ($doctors as $doctor) {
            $name = DoctorNameNormalizer::normalize($doctor->doctorName ?? '');
            /** @var Doctor $entity */
            $entity = $this->doctorRepository->find($doctor->doctorId)
                ??
                new Doctor((string)$doctor->doctorId, $name)
            ;
            $entity->setName($name);
            $entity->clearError();
            $this->doctorRepository->save($entity);

            foreach ($this->fetchDoctorSlots($doctor['id']) as $slot) {
                if (false === $slot) {
                    $entity->markError();
                    $this->doctorRepository->save($entity);
                } else {
                    $this->slotRepository->save(new Slot($slot->doctorId, new DateTime($slot->startDate), new DateTime($slot->endDate)));
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

    protected function fetchData(string $url): string|false
    {
        $auth = base64_encode(
            sprintf(
                '%s:%s',
                self::USERNAME,
                self::PASSWORD,
            ),
        );

        return @file_get_contents(
            filename: $url,
            context: stream_context_create(
                [
                    'http' => [
                        'header' => 'Authorization: Basic ' . $auth,
                    ],
                ],
            ),
        );
    }

    protected function normalizeName(string $fullName): string
    {
        [, $surname] = explode(' ', $fullName);

        /** @see https://www.youtube.com/watch?v=PUhU3qCf0Nk */
        if (0 === stripos($surname, "o'")) {
            return ucwords($fullName, ' \'');
        }

        return ucwords($fullName);
    }

    protected function save(Doctor|Slot $entity): void
    {
        $em = $this->repository->createQueryBuilder('alias')->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    protected function fetchDoctorSlots(int $id): iterable
    {
        try {
            $slots = $this->getSlots($id);
            exit();
            yield from $this->parseSlots($slots, $id);
        } catch (JsonException) {
            if ($this->shouldReportErrors()) {
                $this->logger->info('Error fetching slots for doctor', ['doctorId' => $id]);
            }
            yield false;
        }
    }

    protected function getSlots(int $id): array
    {
        return $this->apiGateway->fetchDoctorSlots(DoctorId::fromInt($id));
    }

    /**
     * @param DoctorSlotDataDTO[] $slots
     */
    protected function parseSlots(array $slots, int $id): iterable
    {
        /** @var DoctorSlotDataDTO $slot */
        var_dump($slots);

        foreach ($slots as $slot) {
            $start = new DateTime($slot->startDate);
            $end = new DateTime($slot->endDate);

            /** @var Slot $entity */
            $entity = $this->slots->findOneBy(['doctorId' => $id, 'start' => $start])
                ?: new Slot($id, $start, $end);

            if ($entity->isStale()) {
                $entity->setEnd($end);
            }

            yield $entity;
        }
    }

    protected function shouldReportErrors(): bool
    {
        return (new DateTime())->format('D') !== 'Sun';
    }
}
