<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cnpj implements ValidationRule
{
    public static function normalize(string $cnpj): string
    {
        return preg_replace('/\D+/', '', $cnpj) ?? '';
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) ||
            ! preg_match('/^(?:\d{14}|\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})$/D', $value)) {
            $fail('O campo :attribute deve ser um CNPJ válido.');

            return;
        }

        $digits = self::normalize($value);

        if (preg_match('/^(\d)\1{13}$/', $digits) ||
            self::checkDigit(substr($digits, 0, 12)) !== (int) $digits[12] ||
            self::checkDigit(substr($digits, 0, 13)) !== (int) $digits[13]) {
            $fail('O campo :attribute deve ser um CNPJ válido.');
        }
    }

    private static function checkDigit(string $base): int
    {
        $weight = strlen($base) - 7;
        $sum = 0;

        for ($index = 0; $index < strlen($base); $index++) {
            $sum += (int) $base[$index] * $weight;
            $weight = $weight === 2 ? 9 : $weight - 1;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
