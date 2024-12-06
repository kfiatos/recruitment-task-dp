<?php

namespace App\Service\DoctorsApi;

use App\DoctorId;
use App\DTO\DoctorDataDTO;
use App\Entity\Doctor;
use App\Entity\Slot;

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
                        'header' => 'Authorization: Basic ' . $this->getAuth(),
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
        foreach ($data as $doctor) {
            $result[] = new DoctorDataDTO($doctor['id'], $doctor['name'] ?? '');
        }
        return $result;
    }

    public function getDoctorSlots(DoctorId $doctorId): array
    {
        $result = [];
        $content = $this->sendGetRequest(sprintf('/%s/slots', $doctorId->id));
        $data = $this->getJsonDecode($content);
        foreach ($data as $slot) {
            $result[] = new Slot($doctorId->id, $slot['start'], $slot['end']);
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