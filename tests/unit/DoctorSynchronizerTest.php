<?php

declare(strict_types=1);

namespace App\Tests\unit;

use App\DoctorSlotsSynchronizer;
use App\DTO\DoctorDataDTO;
use App\DTO\DoctorSlotDataDTO;
use App\Entity\Doctor;
use App\Repository\DoctorRepositoryInterface;
use App\Repository\InMemoryDoctorRepository;
use App\Repository\InMemorySlotRepository;
use App\Repository\SlotRepositoryInterface;
use App\Service\DoctorsApi\DoctorApiClientInterface;
use App\Service\DoctorsApi\DoctorsApiGateway;
use App\Service\DoctorsApi\InMemoryDoctorApiClient;
use App\Service\DoctorsApi\SlotApiClientInterface;
use App\Service\Strategy\DoNotReportErrorsOnSundayStrategy;
use App\Service\Strategy\ErrorReportingStrategyInterface;
use PHPUnit\Framework\TestCase;

class DoctorSynchronizerTest extends TestCase
{
    private DoctorSlotsSynchronizer $synchronizerUnderTest;
    private DoctorRepositoryInterface $doctorRepository;
    private SlotRepositoryInterface $slotRepository;
    private DoctorsApiGateway $apiGateway;
    private DoctorApiClientInterface|InMemoryDoctorApiClient $inMemoryDoctorApiClient;
    private SlotApiClientInterface|InMemoryDoctorApiClient $inMemorySlotsApiClient;

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
        $this->doctorRepository = new InMemoryDoctorRepository([]);
        $this->slotRepository = new InMemorySlotRepository([]);
        $this->inMemoryDoctorApiClient = new InMemoryDoctorApiClient();
        $this->inMemorySlotsApiClient = $this->createMock(SlotApiClientInterface::class);
        $this->inMemorySlotsApiClient->method('getDoctorSlots')->willThrowException(new \JsonException('json decode error'));

        $this->apiGateway = new DoctorsApiGateway($this->inMemoryDoctorApiClient, $this->inMemorySlotsApiClient);
        $this->synchronizerUnderTest = new DoctorSlotsSynchronizer(
            doctorRepository: $this->doctorRepository,
            slotRepository: $this->slotRepository,
            apiGateway: $this->apiGateway,
            errorReportingStrategy: new DoNotReportErrorsOnSundayStrategy(),
            logFile: '/dev/null',
        );

        $this->inMemoryDoctorApiClient->doctors = [
            new DoctorDataDTO(1, 'Happy Honey'),
        ];

        $this->synchronizerUnderTest->synchronizeDoctorSlots();

        $entity = $this->doctorRepository->find(1);
        $this->assertTrue($entity->hasError());
    }

    public function testLogsErrorOnFalseDoctorSlotWhenConditionsMet(): void
    {
        $this->doctorRepository = new InMemoryDoctorRepository([]);
        $this->slotRepository = new InMemorySlotRepository([]);
        $this->inMemoryDoctorApiClient = new InMemoryDoctorApiClient();
        $this->inMemorySlotsApiClient = $this->createMock(SlotApiClientInterface::class);
        $this->inMemorySlotsApiClient->method('getDoctorSlots')->willThrowException(new \JsonException('json dencode error'));

        $this->apiGateway = new DoctorsApiGateway($this->inMemoryDoctorApiClient, $this->inMemorySlotsApiClient);
        $this->synchronizerUnderTest = new DoctorSlotsSynchronizer(
            doctorRepository: $this->doctorRepository,
            slotRepository: $this->slotRepository,
            apiGateway: $this->apiGateway,
            errorReportingStrategy: new DoNotReportErrorsOnSundayStrategy(),
            logFile: 'error.log'
        );

        $doctorId = 1;
        $this->inMemoryDoctorApiClient->doctors = [
            new DoctorDataDTO($doctorId, 'Happy Honey'),
        ];
        $this->synchronizerUnderTest->synchronizeDoctorSlots();

        $this->assertStringContainsString(sprintf('Error fetching slots for doctor {"doctorId":%s}', $doctorId), file_get_contents('error.log'));
        unlink('error.log');
    }

    public function testLogsErrorOnFalseDoctorSlotWhenConditionsNotMet(): void
    {
        $logfile = 'error.log';
        $this->doctorRepository = new InMemoryDoctorRepository([]);
        $this->slotRepository = new InMemorySlotRepository([]);
        $this->inMemoryDoctorApiClient = new InMemoryDoctorApiClient();
        $this->inMemorySlotsApiClient = $this->createMock(SlotApiClientInterface::class);
        $this->inMemorySlotsApiClient->method('getDoctorSlots')->willThrowException(new \JsonException('json decode error'));

        $this->apiGateway = new DoctorsApiGateway($this->inMemoryDoctorApiClient, $this->inMemorySlotsApiClient);

        $errorReportingStrategyMock = $this->createMock(ErrorReportingStrategyInterface::class);
        $errorReportingStrategyMock->method('shouldReport')->willReturn(false);
        $this->synchronizerUnderTest = new DoctorSlotsSynchronizer(
            doctorRepository: $this->doctorRepository,
            slotRepository: $this->slotRepository,
            apiGateway: $this->apiGateway,
            errorReportingStrategy: $errorReportingStrategyMock,
            logFile: $logfile
        );

        $doctorId = 1;
        $this->inMemoryDoctorApiClient->doctors = [
            new DoctorDataDTO($doctorId, 'Happy Honey'),
        ];
        $this->synchronizerUnderTest->synchronizeDoctorSlots();

        $this->assertFalse(file_exists('error.log'));
    }

    protected function setUp(): void
    {
        $this->doctorRepository = new InMemoryDoctorRepository([]);
        $this->slotRepository = new InMemorySlotRepository([]);
        $this->inMemoryDoctorApiClient = new InMemoryDoctorApiClient();
        $this->inMemorySlotsApiClient = new InMemoryDoctorApiClient();
        $this->apiGateway = new DoctorsApiGateway($this->inMemoryDoctorApiClient, $this->inMemorySlotsApiClient);
        $this->synchronizerUnderTest = new DoctorSlotsSynchronizer(
            doctorRepository: $this->doctorRepository,
            slotRepository: $this->slotRepository,
            apiGateway: $this->apiGateway,
            errorReportingStrategy: new DoNotReportErrorsOnSundayStrategy(),
        );
    }
}
