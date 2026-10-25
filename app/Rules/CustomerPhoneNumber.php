<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CustomerPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('رقم الهاتف غير صالح. أدخل رقماً محلياً أو دولياً.');

            return;
        }

        $phone = trim($value);

        if (preg_match('/\A(?:09[0-9]{8}|\+?[1-9][0-9]{7,14})\z/', $phone) !== 1) {
            $fail('أدخل رقم هاتف صالحاً بصيغة محلية أو دولية.');
        }
    }
}
