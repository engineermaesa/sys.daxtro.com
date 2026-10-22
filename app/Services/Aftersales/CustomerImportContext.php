<?php

namespace App\Services\Aftersales;

use App\Models\Masters\Industry;
use App\Models\Masters\ProductType;
use App\Models\Masters\Province;
use App\Models\Masters\Region;

class CustomerImportContext
{
    public CustomerMatchIndex $prior;

    public array $priorSerialCounts = [];

    public array $priorRowNumbers = [];

    private array $masters = [];

    private array $provinces;

    private array $categories;

    public function __construct(public CustomerMatchIndex $matches, public array $existingSerials)
    {
        $this->prior = new CustomerMatchIndex;
        foreach ([Province::class, Region::class, Industry::class] as $class) {
            $columns = $class === Region::class ? ['id', 'name', 'province_id'] : ['id', 'name'];
            $rows = $class::get($columns);
            $this->masters[$class] = $rows->groupBy(fn ($m) => self::location($m->name))->all();
            if ($class === Province::class) {
                $this->provinces = $rows->keyBy('id')->all();
            }
        }
        $this->categories = [];
        foreach (ProductType::get(['id', 'name']) as $type) {
            $this->categories[self::category($type->name)] ??= $type;
        }
    }

    public static function location(string $name): string
    {
        return trim(preg_replace('/^(kabupaten|kab\.?|kota)\s+/i', '', mb_strtolower(trim($name))));
    }

    public static function category(string $name): string
    {
        return trim(str_replace(['ice machine', 'machine'], '', strtolower($name)));
    }

    public function lookup(string $class, string $name): mixed
    {
        if (trim($name) === '') {
            return null;
        }
        $matches = $this->masters[$class][self::location($name)] ?? collect();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function province(int $id): mixed
    {
        return $this->provinces[$id] ?? null;
    }

    public function categoryFor(string $name): mixed
    {
        return $this->categories[self::category($name)] ?? null;
    }

    public function addPrior(int $id, int $rowNumber, array $review): void
    {
        $this->priorRowNumbers[$id] = $rowNumber;
        if ($review['status'] === 'error') {
            return;
        }
        $this->prior->add('row', $id, $review['customer'], ['row_number' => $rowNumber]);
        $serial = $review['machine']['serial_number'];
        if ($serial) {
            $this->priorSerialCounts[$serial] = ($this->priorSerialCounts[$serial] ?? 0) + 1;
        }
    }
}
