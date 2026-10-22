<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\ServiceCustomerProduct;

class MachineSerials
{
    public static function existing(array $serials): array
    {
        $serials = array_values(array_unique(array_filter(array_map([CustomerMatching::class, 'serial'], $serials))));
        $existing = [];
        foreach (array_chunk($serials, 500) as $chunk) {
            foreach (ServiceCustomerProduct::withTrashed()->whereIn('normalized_serial', $chunk)->pluck('normalized_serial') as $serial) {
                $existing[$serial] = true;
            }
        }

        return $existing;
    }

    public static function exists(string $serial, ?int $except = null): bool
    {
        return ServiceCustomerProduct::withTrashed()->where('normalized_serial', $serial)
            ->when($except !== null, fn ($q) => $q->whereKeyNot($except))->exists();
    }
}
