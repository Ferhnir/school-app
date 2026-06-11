<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsoDateTime implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $value)) {
            $fail('The :attribute must be a valid ISO 8601 datetime (YYYY-MM-DDTHH:MM:SS).');
        }
    }
}
