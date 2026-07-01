<?php

namespace App\Support\Validation;

final class NestedFileValidationErrors
{
    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, list<string>>
     */
    public static function collapse(array $errors, string $field): array
    {
        foreach (array_keys($errors) as $key) {
            if (! str_starts_with($key, $field.'.')) {
                continue;
            }

            $errors[$field] = $errors[$key];
            unset($errors[$key]);
        }

        return $errors;
    }
}
