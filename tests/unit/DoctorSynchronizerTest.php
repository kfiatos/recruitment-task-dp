<?php

declare(strict_types=1);

namespace App\Tests\unit;

use App\DoctorSlotsSynchronizer;
use App\DTO\DoctorDataDTO;
use App\DTO\DoctorSlotDataDTO;
use App\Entity\Doctor;
use App\Exception\CannotGetDoctorsException;
use App\Service\DoctorsApi\DoctorsApiGateway;
use App\Service\Processor\DoctorService;
use App\Service\Processor\SlotService;
use App\Service\Strategy\DoNotReportErrorsOnSundayStrategy;
use App\Service\Strategy\ErrorReportingStrategyInterface;
use App\Tests\helpers\InMemoryDoctorApiClient;
use App\Tests\helpers\InMemoryDoctorRepository;
use App\Tests\helpers\InMemorySlotRepository;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DoctorSynchronizerTest extends TestCase
{
    private DoctorSlotsSynchronizer $synchronizerUnderTest;
    private InMemoryDoctorRepository $doctorRepository;
    private InMemorySlotRepository $slotRepository;
    private DoctorsApiGateway $apiGateway;
    private InMemoryDoctorApiClient $inMemoryDoctorApiClient;
    private InMemoryDoctorApiClient $inMemorySlotsApiClient;

    private LoggerInterface $logger;

    public function testSynchronize(): void
    {
        $this->inMemoryDoctorApiClient->doctors = [
            new DoctorDataDTO(1, 'Happy Honey'),
            new DoctorDataDTO(2, 'Batty Badger'),
        ];

        $this->inMemorySlotsApiClient->slots[1] = [
            new DoctorSlotDataDTO(1, '2020-02-01T14:00:00+00:00', '2020-02-01T14:30:00+00:00'),
        ];
        $this->inMemorySlotsApiClient->slots[2] = [
            new DoctorSlotDataDTO(2, '2020-02-01T14:30:00+00:00', '2020-02-01T15:00:00+00:00'),
        ];
        $this->synchronizerUnderTest->synchronizeDoctorSlots();

        $this->assertNotEmpty(array_filter($this->doctorRepository->doctors, function (Doctor $doctor) {
            return 1 == $doctor->getId() && 'Happy Honey' == $doctor->getName();
        }));
        $this->assertNotEmpty(array_filter($this->doctorRepository->doctors, function (Doctor $doctor) {
            return 2 == $doctor->getId() && 'Batty Badger' == $doctor->getName();
        }));
    }

    public function testSetsDoctorErrorOnFalseSlot(): void
    {
        $this->inMemorySlotsApiClient = $this->createMock(InMemoryDoctorApiClient::class);
        $this->inMemorySlotsApiClient->method('getDoctorSlots')->willThrowException(new \JsonException('json decode error'));

        $this->apiGateway = new DoctorsApiGateway($this->inMemoryDoctorApiClient, $this->inMemorySlotsApiClient);
        $this->synchronizerUnderTest = new DoctorSlotsSynchronizer(
            apiGateway: $this->apiGateway,
            doctorProcessor: new DoctorService($this->doctorRepository, $this->logger),
            slotProcessor: new SlotService($this->slotRepository, $this->logger),
            errorReportingStrategy: new DoNotReportErrorsOnSundayStrategy(),
            logger: $this->createLogger('/dev/null'),
        );

        $this->inMemoryDoctorApiClient->doctors = [
            new DoctorDataDTO(1, 'Happy Honey'),
        ];

        $this->synchronizerUnderTest->synchronizeDoctorSlots();

        $entity = $this->doctorRepository->find(1);
        $this->assertTrue($entity?->hasError());
    }

    /**
     * @not void
     *
     * @throws \DateMalformedStringException
     * @throws Exception
     */
    public function testLogsErrorOnFalseDoctorSlotWhenConditionsMet(): void
    {
        $logfile = 'error.log';
        $this->inMemorySlotsApiClient = $this->createMock(InMemoryDoctorApiClient::class);
        $this->inMemorySlotsApiClient->method('getDoctorSlots')->willThrowException(new \JsonException('json decode error'));
        $errorReportingStrategyMock = $this->createMock(ErrorReportingStrategyInterface::class);

        $errorReportingStrategyMock->expects($this->once())->method('shouldReport');
        $this->apiGateway = new DoctorsApiGateway($this->inMemoryDoctorApiClient, $this->inMemorySlotsApiClient);
        $this->synchronizerUnderTest = new DoctorSlotsSynchronizer(
            apiGateway: $this->apiGateway,
            doctorProcessor: new DoctorService($this->doctorRepository, $this->logger),
            slotProcessor: new SlotService($this->slotRepository, $this->logger),
            errorReportingStrategy: $errorReportingStrategyMock,
            logger: $this->createLogger($logfile),
        );

        $doctorId = 1;
        $this->inMemoryDoctorApiClient->doctors = [
            new DoctorDataDTO($doctorId, 'Happy Honey'),
        ];
        $this->synchronizerUnderTest->synchronizeDoctorSlots();
    }

    public function testLogsErrorOnFalseDoctorSlotWhenConditionsNotMet(): void
    {
        $logfile = 'error.log';
        $this->inMemorySlotsApiClient = $this->createMock(InMemoryDoctorApiClient::class);
        $this->inMemorySlotsApiClient->method('getDoctorSlots')->willThrowException(new CannotGetDoctorsException('api error'));

        $this->apiGateway = new DoctorsApiGateway($this->inMemoryDoctorApiClient, $this->inMemorySlotsApiClient);

        $errorReportingStrategyMock = $this->createMock(ErrorReportingStrategyInterface::class);
        $errorReportingStrategyMock->method('shouldReport')->willReturn(false);

        $this->synchronizerUnderTest = new DoctorSlotsSynchronizer(
            apiGateway: $this->apiGateway,
            doctorProcessor: new DoctorService($this->doctorRepository, $this->logger),
            slotProcessor: new SlotService($this->slotRepository, $this->logger),
            errorReportingStrategy: $errorReportingStrategyMock,
            logger: $this->createLogger($logfile),
        );

        $doctorId = 1;
        $this->inMemoryDoctorApiClient->doctors = [
            new DoctorDataDTO($doctorId, 'Happy Honey'),
        ];
        $this->synchronizerUnderTest->synchronizeDoctorSlots();

        $this->assertFalse(file_exists($logfile));
    }

    public function testMarksErrorOnDoctorEntityWhenNoSlot(): void
    {
        $this->inMemorySlotsApiClient = $this->createMock(InMemoryDoctorApiClient::class);
        $this->inMemorySlotsApiClient->method('getDoctorSlots')->willThrowException(new CannotGetDoctorsException('api error'));

        $this->apiGateway = new DoctorsApiGateway($this->inMemoryDoctorApiClient, $this->inMemorySlotsApiClient);

        $errorReportingStrategyMock = $this->createMock(ErrorReportingStrategyInterface::class);

        $this->synchronizerUnderTest = new DoctorSlotsSynchronizer(
            apiGateway: $this->apiGateway,
            doctorProcessor: new DoctorService($this->doctorRepository, $this->logger),
            slotProcessor: new SlotService($this->slotRepository, $this->logger),
            errorReportingStrategy: $errorReportingStrategyMock,
            logger: $this->logger,
        );

        $doctorId = 1;
        $this->inMemoryDoctorApiClient->doctors = [
            new DoctorDataDTO($doctorId, 'Happy Honey'),
        ];
        $this->synchronizerUnderTest->synchronizeDoctorSlots();
        $doctor = $this->doctorRepository->find($doctorId);
        $this->assertTrue($doctor?->hasError());
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
        $this->synchronizerUnderTest = new DoctorSlotsSynchronizer(
            apiGateway: $this->apiGateway,
            doctorProcessor: new DoctorService($this->doctorRepository, $this->logger),
            slotProcessor: new SlotService($this->slotRepository, $this->logger),
            errorReportingStrategy: new DoNotReportErrorsOnSundayStrategy(),
            logger: $this->logger,
        );
    }
}
