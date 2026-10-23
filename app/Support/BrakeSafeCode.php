<?php

namespace App\Support;

class BrakeSafeCode
{
    public static function generate(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $characters = '';

        for ($index = 0; $index < 16; $index++) {
            $characters .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return 'BS-'.implode('-', str_split($characters, 4));
    }
}
