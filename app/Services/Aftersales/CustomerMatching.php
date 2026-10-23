<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\ServiceCustomer;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadStatus;

class CustomerMatching
{
    public static function name(?string $value): string
    {
        $value = mb_strtolower(trim($value ?? ''));
        $value = preg_replace('/^(?:(?:mr|mrs|pak|bapak|ibu|bu)\.?\s+)+/u', '', $value);

        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    public static function phone(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('/^-+$/', $value)) {
            return null;
        }
        if (preg_match('/[eE]|[^\d\s+().\-]/', $value)) {
            throw new \InvalidArgumentException('Phone is invalid or uses scientific notation.');
        }
        $digits = preg_replace('/\D/', '', $value);
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }
        if (strlen($digits) < 7 || strlen($digits) > 16) {
            throw new \InvalidArgumentException('Phone must contain a valid telephone number.');
        }

        return $digits;
    }

    public static function serial(?string $value): ?string
    {
        $value = strtoupper(preg_replace('/\s+/', '', trim($value ?? '')));

        return in_array($value, ['', '-', 'NOMACHINEID'], true) ? null : $value;
    }

    public function classify(array $a, array $b): ?string
    {
        return self::classifyPrepared(self::prepare($a), self::prepare($b));
    }

    public static function prepare(array $data): array
    {
        try {
            $phone = self::phone($data['phone'] ?? null);
            $valid = true;
        } catch (\InvalidArgumentException) {
            $phone = null;
            $valid = false;
        }

        return ['name' => self::name($data['name'] ?? null), 'phone' => $phone, 'phone_valid' => $valid, 'ref_region_id' => $data['ref_region_id'] ?? null];
    }

    public static function phoneSignatures(?string $phone): array
    {
        if (! $phone) {
            return [];
        }
        $keys = [$phone];
        for ($i = 0; $i < strlen($phone); $i++) {
            $keys[] = substr($phone, 0, $i).substr($phone, $i + 1);
        }

        return array_values(array_unique($keys));
    }

    public static function classifyPrepared(array $a, array $b): ?string
    {
        $an = $a['name'];
        $bn = $b['name'];
        $ap = $a['phone_valid'] && $b['phone_valid'] ? $a['phone'] : null;
        $bp = $a['phone_valid'] && $b['phone_valid'] ? $b['phone'] : null;
        $sameName = $an !== '' && $an === $bn;
        $samePhone = $ap && $bp && $ap === $bp;
        $sameCity = ! empty($a['ref_region_id']) && (int) $a['ref_region_id'] === (int) ($b['ref_region_id'] ?? 0);
        if ($sameName && $samePhone) {
            return 'exact';
        }
        if (($sameName && $sameCity) || $samePhone || ($ap && $bp && levenshtein($ap, $bp) === 1)) {
            return 'possible';
        }
        // Missing phones are never an automatic merge key.
        if ($sameName && (! $ap || ! $bp)) {
            return 'possible';
        }

        return null;
    }

    public function candidates(array $data, bool $includeLeads = true): array
    {
        return $this->index($includeLeads)->candidates($data);
    }

    public function index(bool $includeLeads = true): CustomerMatchIndex
    {
        $index = new CustomerMatchIndex;
        foreach (ServiceCustomer::get(['id', 'name', 'phone', 'ref_region_id']) as $customer) {
            $index->customers[$customer->id] = $customer;
            $index->add('customer', $customer->id, $customer->getAttributes());
        }
        if ($includeLeads) {
            foreach (Lead::where('status_id', LeadStatus::DEAL)->whereNotNull('deal_at')->with('customerDeal:id,lead_id,service_customer_id')->get(['id', 'name', 'phone', 'factory_city_id']) as $lead) {
                if ($lead->customerDeal?->service_customer_id && ! isset($index->customers[$lead->customerDeal->service_customer_id])) {
                    continue;
                }
                $index->add('lead', $lead->id, ['name' => $lead->name, 'phone' => $lead->phone, 'ref_region_id' => $lead->factory_city_id], ['customer_id' => $lead->customerDeal?->service_customer_id]);
            }
        }

        return $index;
    }
}
