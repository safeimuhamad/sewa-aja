<?php

namespace Shared\Validation;

class Validator
{
    public static function make(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && self::isBlank($value)) {
                    $errors[$field][] = 'Field wajib diisi.';
                }

                if ($rule === 'email' && !self::isBlank($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = 'Format email tidak valid.';
                }

                if (str_starts_with($rule, 'min:') && !self::isBlank($value)) {
                    $min = (int) substr($rule, 4);
                    if (mb_strlen((string) $value) < $min) {
                        $errors[$field][] = "Minimal {$min} karakter.";
                    }
                }

                if (str_starts_with($rule, 'max:') && !self::isBlank($value)) {
                    $max = (int) substr($rule, 4);
                    if (mb_strlen((string) $value) > $max) {
                        $errors[$field][] = "Maksimal {$max} karakter.";
                    }
                }

                if (str_starts_with($rule, 'in:') && !self::isBlank($value)) {
                    $allowed = explode(',', substr($rule, 3));
                    if (!in_array($value, $allowed, true)) {
                        $errors[$field][] = 'Nilai tidak tersedia.';
                    }
                }
            }
        }

        return $errors;
    }

    private static function isBlank(mixed $value): bool
    {
        return $value === null || trim((string) $value) === '';
    }
}
