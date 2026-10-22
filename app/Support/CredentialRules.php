<?php

namespace App\Support;

use Closure;
use Illuminate\Validation\Rules\Password;

final class CredentialRules
{
    public static function email(): array
    {
        return ['required', 'string', 'email', 'max:255', 'not_regex:/[\\x00-\\x20\\x7f]/'];
    }

    public static function password(): array
    {
        return ['required', 'string', 'confirmed', Password::min(12), 'max:72', function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && (strlen($value) > 72 || str_contains($value, "\0"))) {
                $fail('The password must be at most 72 bytes and cannot contain null characters.');
            }
        }];
    }
}
