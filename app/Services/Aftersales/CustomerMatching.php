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
        $an = self::name($a['name'] ?? null);
        $bn = self::name($b['name'] ?? null);
        try {
            $ap = self::phone($a['phone'] ?? null);
            $bp = self::phone($b['phone'] ?? null);
        } catch (\InvalidArgumentException) {
            $ap = $bp = null;
        }
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
        $matches = [];
        foreach (ServiceCustomer::query()->get() as $customer) {
            if ($status = $this->classify($data, $customer->getAttributes())) {
                $matches[] = ['type' => 'customer', 'id' => $customer->id, 'status' => $status, 'name' => $customer->name, 'phone' => $customer->phone, 'ref_region_id' => $customer->ref_region_id];
            }
        }
        if ($includeLeads) {
            foreach (Lead::where('status_id', LeadStatus::DEAL)->whereNotNull('deal_at')->with('customerDeal')->get() as $lead) {
                if ($status = $this->classify($data, ['name' => $lead->name, 'phone' => $lead->phone, 'ref_region_id' => $lead->factory_city_id])) {
                    $matches[] = ['type' => 'lead', 'id' => $lead->id, 'status' => $status, 'customer_id' => $lead->customerDeal?->service_customer_id, 'name' => $lead->name, 'phone' => $lead->phone];
                }
            }
        }

        return $matches;
    }
}
