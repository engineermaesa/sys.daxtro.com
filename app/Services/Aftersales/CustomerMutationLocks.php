<?php

namespace App\Services\Aftersales;

use Illuminate\Support\Facades\DB;

class CustomerMutationLocks
{
    public static function customerKeys(array $customer): array
    {
        $data = CustomerMatching::prepare($customer);
        $keys = $data['name'] !== '' ? ['name:'.$data['name']] : [];
        foreach (CustomerMatching::phoneSignatures($data['phone']) as $signature) {
            $keys[] = 'phone:'.$signature;
        }

        return $keys;
    }

    public static function serialKey(?string $serial): array
    {
        $serial = CustomerMatching::serial($serial);

        return $serial === null ? [] : ['serial:'.$serial];
    }

    public static function acquire(array $keys): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Customer mutation locks require a transaction.');
        }
        $keys = array_values(array_unique(array_map(fn ($key) => hash('sha256', $key), $keys)));
        sort($keys, SORT_STRING);
        foreach (array_chunk($keys, 500) as $chunk) {
            DB::table('aftersales_mutation_locks')->upsert(array_map(fn ($key) => ['lock_key' => $key], $chunk), ['lock_key'], ['lock_key']);
        }
    }
}
