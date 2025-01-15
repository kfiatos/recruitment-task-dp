<?php

declare(strict_types=1);

namespace App\Tests\unit;

use App\DTO\DoctorSlotDataDTO;
use App\Entity\Doctor;
use App\Service\DoctorsApi\DoctorsApiGateway;
use App\Service\Processor\DoctorService;
use App\Service\Processor\SlotService;
use App\Service\Strategy\DoNotReportErrorsOnSundayStrategy;
use App\StaticDoctorSlotsSynchronizer;
use App\Tests\helpers\InMemoryDoctorApiClient;
use App\Tests\helpers\InMemoryDoctorRepository;
use App\Tests\helpers\InMemorySlotRepository;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StaticDoctorSynchronizerTest extends TestCase
{
    private StaticDoctorSlotsSynchronizer $synchronizerUnderTest;
    private InMemoryDoctorRepository $doctorRepository;
    private InMemorySlotRepository $slotRepository;
    private DoctorsApiGateway $apiGateway;
    private InMemoryDoctorApiClient $inMemoryDoctorApiClient;
    private InMemoryDoctorApiClient $inMemorySlotsApiClient;

    private LoggerInterface $logger;

    public function testSynchronize(): void
    {
        $this->inMemorySlotsApiClient->slots[1] = [
            new DoctorSlotDataDTO(1, '2020-02-01T14:00:00+00:00', '2020-02-01T14:30:00+00:00'),
        ];
        $this->inMemorySlotsApiClient->slots[2] = [
            new DoctorSlotDataDTO(2, '2020-02-01T14:30:00+00:00', '2020-02-01T15:00:00+00:00'),
        ];
        $this->synchronizerUnderTest->synchronizeDoctorSlots();

        $this->assertNotEmpty(array_filter($this->doctorRepository->doctors, function (Doctor $doctor) {
            return 1 == $doctor->getId() && 'Brave Ramanujan' == $doctor->getName();
        }));
        $this->assertNotEmpty(array_filter($this->doctorRepository->doctors, function (Doctor $doctor) {
            return 2 == $doctor->getId() && 'Tender Rosalind' == $doctor->getName();
        }));
    }

    private function createLogger(string $logfile = 'php://stderr'): LoggerInterface
    {
        return new Logger('logger', [new StreamHandler($logfile)]);
    }

    protected function setUp(): void
    {
        $this->logger = $this->createLogger();
        $this->doctorRepository = new InMemoryDoctorRepository([]);
        $this->slotRepository = new InMemorySlotRepository([]);
        $this->inMemoryDoctorApiClient = new InMemoryDoctorApiClient();
        $this->inMemorySlotsApiClient = new InMemoryDoctorApiClient();
        $this->apiGateway = new DoctorsApiGateway($this->inMemoryDoctorApiClient, $this->inMemorySlotsApiClient);
        $this->synchronizerUnderTest = new StaticDoctorSlotsSynchronizer(
            apiGateway: $this->apiGateway,
            doctorProcessor: new DoctorService($this->doctorRepository, $this->logger),
            slotProcessor: new SlotService($this->slotRepository, $this->logger),
            errorReportingStrategy: new DoNotReportErrorsOnSundayStrategy(),
            logger: $this->logger,
        );
    }
}
