<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GoogleMapsUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $host = strtolower(parse_url((string) $value, PHP_URL_HOST) ?? '');
        $path = parse_url((string) $value, PHP_URL_PATH) ?? '';
        $valid = filter_var($value, FILTER_VALIDATE_URL)
            && in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)
            && (in_array($host, ['maps.app.goo.gl', 'maps.google.com'], true)
                || ($host === 'goo.gl' && str_starts_with($path, '/maps'))
                || (in_array($host, ['google.com', 'www.google.com', 'google.co.id', 'www.google.co.id'], true) && preg_match('#^/maps(?:/|$)#', $path)));
        if (! $valid) {
            $fail('The :attribute must be a valid Google Maps URL.');
        }
    }
}
