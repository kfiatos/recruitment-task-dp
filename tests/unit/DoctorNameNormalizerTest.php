<?php

namespace unit;

use App\Normalizer\DoctorNameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DoctorNameNormalizerTest extends TestCase
{
    #[DataProvider('providerDoctorsNames')]
    public function testNormalize(array $data): void
    {
        $name  = DoctorNameNormalizer::normalize($data['input_name']);
        $this->assertEquals($data['expected_name'], $name);
    }

    public static function providerDoctorsNames(): \Generator
    {
        yield [['input_name' => 'Sam o\'neil', 'expected_name' => 'Sam O\'Neil']];
        yield [['input_name' => 'Sam o\'Neil', 'expected_name' => 'Sam O\'Neil']];
        yield [['input_name' => 'Happy gilmore', 'expected_name' => 'Happy Gilmore']];
        yield [['input_name' => 'happy Gilmore', 'expected_name' => 'Happy Gilmore']];
        yield [['input_name' => 'happy gilmore', 'expected_name' => 'Happy Gilmore']];
        yield [['input_name' => '', 'expected_name' => '']];
    }
}