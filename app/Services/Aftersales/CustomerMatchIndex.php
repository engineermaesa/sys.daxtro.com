<?php

namespace App\Services\Aftersales;

class CustomerMatchIndex
{
    public array $customers = [];

    private array $entries = [];

    private array $names = [];

    private array $phones = [];

    public function linkLead(int $id, int $customerId): void
    {
        if (isset($this->entries['lead:'.$id])) {
            $this->entries['lead:'.$id]['candidate']['customer_id'] = $customerId;
        }
    }

    public function add(string $type, int $id, array $customer, array $extra = []): void
    {
        $key = $type.':'.$id;
        $normalized = CustomerMatching::prepare($customer);
        $this->entries[$key] = ['customer' => $normalized, 'candidate' => ['type' => $type, 'id' => $id] + $extra + ['name' => $customer['name'] ?? null, 'phone' => $customer['phone'] ?? null, 'ref_region_id' => $customer['ref_region_id'] ?? null]];
        if ($normalized['name'] !== '') {
            $this->names[$normalized['name']][$key] = true;
        }
        foreach (CustomerMatching::phoneSignatures($normalized['phone']) as $signature) {
            $this->phones[$signature][$key] = true;
        }
    }

    public function candidates(array $customer): array
    {
        $normalized = CustomerMatching::prepare($customer);
        $keys = $this->names[$normalized['name']] ?? [];
        foreach (CustomerMatching::phoneSignatures($normalized['phone']) as $signature) {
            $keys += $this->phones[$signature] ?? [];
        }

        uksort($keys, static function ($a, $b) {
            [$at, $ai] = explode(':', $a);
            [$bt, $bi] = explode(':', $b);
            $order = ['customer' => 0, 'lead' => 1, 'row' => 2];

            return ($order[$at] <=> $order[$bt]) ?: ((int) $ai <=> (int) $bi);
        });
        $matches = [];
        foreach ($keys as $key => $_) {
            $entry = $this->entries[$key];
            if ($status = CustomerMatching::classifyPrepared($normalized, $entry['customer'])) {
                $candidate = $entry['candidate'];
                
                if ($candidate['type'] === 'row') {
                    unset($candidate['phone'], $candidate['ref_region_id']);
                }
                if ($candidate['type'] === 'lead') {
                    unset($candidate['ref_region_id']);
                }
                $matches[] = $candidate + ['status' => $status];
            }
        }

        return $matches;
    }
}
