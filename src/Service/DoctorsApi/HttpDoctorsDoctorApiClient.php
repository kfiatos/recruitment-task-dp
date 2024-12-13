<?php

declare(strict_types=1);

namespace App\Service\DoctorsApi;

use App\DTO\DoctorDataDTO;
use App\DTO\DoctorSlotDataDTO;
use App\Service\DoctorsApi\Contract\DoctorApiClientInterface;
use App\Service\DoctorsApi\Contract\SlotApiClientInterface;
use App\ValueObject\DoctorId;

class HttpDoctorsDoctorApiClient implements DoctorApiClientInterface, SlotApiClientInterface
{
    protected const ENDPOINT = 'http://unit-testing-api:2137/api/doctors';
    protected const USERNAME = 'docplanner';
    protected const PASSWORD = 'docplanner';

    private function sendGetRequest(string $url): string|false
    {
        return @file_get_contents(
            filename: $url,
            context: stream_context_create(
                [
                    'http' => [
                        'header' => 'Authorization: Basic '.$this->getAuth(),
                    ],
                ],
            ),
        );
    }

    private function getAuth(): string
    {
        return base64_encode(
            sprintf(
                '%s:%s',
                self::USERNAME,
                self::PASSWORD,
            ),
        );
    }

    public function getDoctors(): array
    {
        $result = [];
        $content = $this->sendGetRequest(self::ENDPOINT);
        $data = $this->getJsonDecode($content);

        /** @var array<array{id: string, name: string}> $data */
        foreach ($data as $doctor) {
            $result[] = new DoctorDataDTO((int) $doctor['id'], $doctor['name']);
        }

        return $result;
    }

    public function getDoctorSlots(DoctorId $doctorId): array
    {
        $result = [];
        $content = $this->sendGetRequest(sprintf('/%s/slots', $doctorId->id));
        /** @var array<array{id: string, start: string, end:string}> $data */
        $data = $this->getJsonDecode($content);
        foreach ($data as $slot) {
            $result[] = new DoctorSlotDataDTO((int) $doctorId->id, $slot['start'], $slot['end']);
        }

        return $result;
    }

    /**
     * @throws \JsonException
     */
    protected function getJsonDecode(string|bool $json): mixed
    {
        return json_decode(
            json: false === $json ? '' : $json,
            associative: true,
            depth: 16,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
