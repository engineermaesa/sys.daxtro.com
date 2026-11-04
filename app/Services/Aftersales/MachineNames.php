<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\ServiceCustomerProduct;
use App\Models\Masters\ProductType;
use Illuminate\Validation\ValidationException;

class MachineNames
{
    private const NAMING_FIELDS = ['ref_product_type_id', 'capacity', 'compressor', 'refrigerant'];

    public static function generate(?string $prefix, mixed $capacity, ?string $compressor = null, ?string $refrigerant = null): string
    {
        $prefix = strtoupper(trim($prefix ?? ''));
        if ($prefix === '') {
            throw ValidationException::withMessages(['ref_product_type_id' => 'Select a machine category with a code prefix to generate the machine name.']);
        }
        if ($capacity === null || $capacity === '' || ! is_numeric($capacity) || ! is_finite((float) $capacity) || (float) $capacity < 0) {
            throw ValidationException::withMessages(['capacity' => 'A non-negative capacity in tons is required to generate the machine name.']);
        }

        $prefix = $prefix === 'VCD' ? 'RD' : $prefix;
        $series = $prefix === 'RD' ? round((float) $capacity / 3 * 10, 0, PHP_ROUND_HALF_UP) : (float) $capacity * 10;
        if (! is_finite($series)) {
            throw ValidationException::withMessages(['capacity' => 'Capacity is too large to generate the machine name.']);
        }
        $number = rtrim(rtrim(number_format($series, 10, '.', ''), '0'), '.');
        $suffix = implode(' ', array_filter([trim($compressor ?? ''), trim($refrigerant ?? '')], fn ($value) => $value !== ''));
        $name = $prefix.$number.($suffix !== '' ? ' | '.$suffix : '');
        if (mb_strlen($name) > 255) {
            throw ValidationException::withMessages(['machine_name' => 'The generated machine name must not exceed 255 characters.']);
        }

        return $name;
    }

    public static function apply(ServiceCustomerProduct $machine, array $input): void
    {
        $currentName = $machine->exists ? $machine->machine_name : null;
        $suppliedName = array_key_exists('machine_name', $input);
        $name = $suppliedName ? trim($input['machine_name'] ?? '') : $currentName;
        $requestedMode = $input['machine_name_mode'] ?? null;
        $mode = $requestedMode;
        if (! ($input['is_daxtro'] ?? $machine->is_daxtro)) {
            $mode = 'manual';
        } elseif ($mode === null) {
            $edited = $suppliedName && filled($name) && (! $machine->exists || $name !== $currentName);
            $mode = $edited ? 'manual' : ($machine->machine_name_mode ?? 'auto');
        }

        unset($input['machine_name'], $input['machine_name_mode']);
        $machine->fill($input);
        if ($machine->isDirty('product_id')) {
            $machine->unsetRelation('product');
        }
        if ($machine->isDirty('ref_product_type_id')) {
            $machine->unsetRelation('productType');
        }
        $machine->machine_name_mode = $mode;

        if ($mode === 'manual') {
            if (blank($name)) {
                throw ValidationException::withMessages(['machine_name' => 'Machine Name is required for manual names and non-Daxtro machines.']);
            }
            $machine->machine_name = $name;
        } elseif (! $machine->exists || $requestedMode === 'auto' || $machine->isDirty(self::NAMING_FIELDS)) {
            $machine->machine_name = self::generate(
                ProductType::find($machine->ref_product_type_id)?->code_prefix,
                $machine->capacity,
                $machine->compressor,
                $machine->refrigerant,
            );
        }
    }
}
