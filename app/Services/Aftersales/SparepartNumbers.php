<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\RefSparepart;
use Illuminate\Validation\ValidationException;

class SparepartNumbers
{
    public static function normalize(?string $value): ?string
    {
        $key = mb_strtoupper(trim($value ?? ''));

        return $key === '' ? null : $key;
    }

    public static function acquire(array $numbers): void
    {
        $keys = [];
        foreach ($numbers as $number) {
            if (($key = self::normalize($number)) !== null) {
                $keys[] = 'sparepart:'.$key;
            }
        }
        CustomerMutationLocks::acquire($keys);
    }

    public static function assertAvailable(?string $number, ?int $except = null): void
    {
        $key = self::normalize($number);
        if ($key === null) {
            return;
        }
        $parts = RefSparepart::withTrashed()->when($except !== null, fn ($query) => $query->whereKeyNot($except))->get(['part_number']);
        foreach ($parts as $part) {
            if (self::normalize($part->part_number) === $key) {
                throw ValidationException::withMessages(['part_number' => 'This Part Number already exists, including archived spareparts.']);
            }
        }
    }
}
