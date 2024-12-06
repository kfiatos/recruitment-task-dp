<?php

namespace App\Normalizer;

class DoctorNameNormalizer
{
    public static function normalize(string $name): string
    {
        [, $surname] = explode(' ', $name);

        /** @see https://www.youtube.com/watch?v=PUhU3qCf0Nk */
        if (0 === stripos($surname, "o'")) {
            return ucwords($name, ' \'');
        }

        return ucwords($name);
    }
}