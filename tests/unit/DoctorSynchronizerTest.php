<?php

namespace unit;

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

use PHPUnit\Framework\TestCase;

class DoctorSynchronizerTest extends TestCase
{
    private DoctorSlotsSynchronizer $synchronizerUnderTest;
    private DoctorRepositoryInterface $doctorRepository;
    private SlotRepositoryInterface $slotRepository;
    private DoctorsApiGateway $apiGateway;

    private DoctorApiClientInterface $inMemoryDoctorApiClient;
    private SlotApiClientInterface $inMemorySlotsApiClient;


//    public function testSynchronise(): void
//    {
////        $repository = new EntityRepository()
//        $emMock = $this->createMock(EntityManager::class);
//        $doctorRepositoryMock = $this->createMock(EntityRepository::class);
//        $slotsRepositoryMock = $this->createMock(EntityRepository::class);
//        $qbMock = $this->createMock(QueryBuilder::class);
//        $qbMock->method('getEntityManager')->willReturn($emMock);
//        $doctorRepositoryMock->method('createQueryBuilder')->willReturn($qbMock);
//
//        $emMock->method('getRepository')->willReturnOnConsecutiveCalls($doctorRepositoryMock, $slotsRepositoryMock);
////        $emMock->method('getRepository')->with($this->equalTo(Slot::class))->willReturn($slotsRepositoryMock);
//        $synchroniser = new DoctorSlotsSynchronizer($emMock);
//        $synchroniser->synchronizeDoctorSlots();
//    }


    public function testSynchronize(): void
    {
        $this->inMemoryDoctorApiClient->doctors = [
            new DoctorDataDTO(1, 'Happy Honey'),
            new DoctorDataDTO(2, 'Batty Badger'),
        ];

        $this->inMemorySlotsApiClient->slots = [
            new DoctorSlotDataDTO(1, '2020-02-01T14:00:00+00:00', '2020-02-01T14:30:00+00:00'),
            new DoctorSlotDataDTO(2, '2020-02-01T14:30:00+00:00', '2020-02-01T15:00:00+00:00')
        ];
        $this->synchronizerUnderTest->synchronizeDoctorSlots();

        $this->assertNotEmpty(array_filter($this->doctorRepository->modifiedDoctors, function (Doctor $doctor) {
            return $doctor->getId() == 1 && $doctor->getName() == 'Happy Honey';
        }));
        $this->assertNotEmpty(array_filter($this->doctorRepository->modifiedDoctors, function (Doctor $doctor) {
            return $doctor->getId() == 2 && $doctor->getName() == 'Batty Badger';
        }));
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
            apiGateway: $this->apiGateway
        );
    }
}