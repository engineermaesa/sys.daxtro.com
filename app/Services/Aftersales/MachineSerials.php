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
            foreach (ServiceCustomerProduct::withTrashed()->where('is_daxtro', true)->whereIn('normalized_serial', $chunk)->pluck('normalized_serial') as $serial) {
                $existing[$serial] = true;
            }
        }

        return $existing;
    }

    public static function exists(string $serial, ?int $except = null): bool
    {
        return ServiceCustomerProduct::withTrashed()->where('is_daxtro', true)->where('normalized_serial', $serial)
            ->when($except !== null, fn ($q) => $q->whereKeyNot($except))->exists();
    }

    public static function importSnapshot(array $serials): array
    {
        $serials = array_values(array_unique(array_filter(array_map([CustomerMatching::class, 'serial'], $serials))));
        $existing = $archived = [];
        foreach (array_chunk($serials, 500) as $chunk) {
            $units = ServiceCustomerProduct::withTrashed()
                ->leftJoin('service_customers', 'service_customers.id', '=', 'service_customer_products.customer_id')
                ->where('service_customer_products.is_daxtro', true)
                ->whereIn('service_customer_products.normalized_serial', $chunk)
                ->get(['service_customer_products.normalized_serial', 'service_customers.deleted_at as customer_deleted_at']);
            foreach ($units as $unit) {
                $serial = $unit->normalized_serial;
                $existing[$serial] = true;
                $archived[$serial] = ($archived[$serial] ?? true) && $unit->customer_deleted_at !== null;
            }
        }

        return ['existing' => $existing, 'archived' => array_filter($archived)];
    }
}
