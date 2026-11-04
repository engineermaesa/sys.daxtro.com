<?php

namespace Tests\Unit\Aftersales;

use App\Services\Aftersales\MachineNames;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MachineNamesTest extends TestCase
{
    public static function names(): array
    {
        return [
            ['TD', 15, 'Bitzer', 'R404A', 'TD150 | Bitzer R404A'],
            ['TD', 5, null, null, 'TD50'],
            ['TD', 7, null, null, 'TD70'],
            ['TD', '7.25', null, null, 'TD72.5'],
            ['TD', 0, null, null, 'TD0'],
            ['RD', 6, 'Copeland', 'R22', 'RD20 | Copeland R22'],
            ['RD', 1, null, null, 'RD3'],
            ['RD', 1.05, null, null, 'RD4'],
            ['VCD', 6, null, null, 'RD20'],
            ['CD', 5, null, null, 'CD50'],
            ['BD', 5, null, null, 'BD50'],
            ['FD', 5, null, null, 'FD50'],
            ['SD', 5, null, null, 'SD50'],
            ['RO', 5, null, null, 'RO50'],
            [' td ', 15, ' Bitzer ', ' ', 'TD150 | Bitzer'],
            ['TD', 15, null, ' R404A ', 'TD150 | R404A'],
        ];
    }

    #[DataProvider('names')]
    public function test_name_format_and_series_formula(string $prefix, mixed $capacity, ?string $compressor, ?string $refrigerant, string $expected): void
    {
        $this->assertSame($expected, MachineNames::generate($prefix, $capacity, $compressor, $refrigerant));
    }

    public static function invalidInputs(): array
    {
        return [
            [null, 15, null, null, 'ref_product_type_id'],
            [' ', 15, null, null, 'ref_product_type_id'],
            ['TD', null, null, null, 'capacity'],
            ['TD', '', null, null, 'capacity'],
            ['TD', 'not a number', null, null, 'capacity'],
            ['TD', -1, null, null, 'capacity'],
            ['TD', INF, null, null, 'capacity'],
            ['TD', '1e309', null, null, 'capacity'],
            ['TD', '1e308', null, null, 'capacity'],
            ['TD', 15, str_repeat('X', 255), 'R404A', 'machine_name'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_generator_input_has_a_field_error(?string $prefix, mixed $capacity, ?string $compressor, ?string $refrigerant, string $field): void
    {
        try {
            MachineNames::generate($prefix, $capacity, $compressor, $refrigerant);
            $this->fail('Invalid generator input must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}
