<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\CustomerImport;
use App\Models\Aftersales\RefSparepart;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SparepartImports
{
    public const HEADERS = ['Part Number*', 'Name*', 'Brand', 'Supplier', 'Price', 'Stock', 'Min Stock', 'Rack Location'];

    public const TEMPLATE_VERSION = '2.2';

    public function upload(UploadedFile $file, int $userId): CustomerImport
    {
        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly(['Import Sparepart']);
            $book = $reader->load($file->getRealPath());
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['file' => 'The file must be a readable .xlsx template.']);
        }
        try {
            $sheet = $book->getSheetByName('Import Sparepart');
            if (! $sheet || $sheet->rangeToArray('A1:H1', null, false, false)[0] !== self::HEADERS || $sheet->getHighestDataColumn() !== 'H') {
                throw ValidationException::withMessages(['file' => 'Headers must exactly match the Import Sparepart template.']);
            }
            $last = $sheet->getHighestDataRow();
            if ($last > 5001) {
                throw ValidationException::withMessages(['file' => 'A maximum of 5000 rows is allowed per import.']);
            }
            $inputs = [];
            for ($number = 2; $number <= $last; $number++) {
                $values = $sheet->rangeToArray('A'.$number.':H'.$number, null, false, false)[0];
                if (count(array_filter($values, fn ($value) => $value !== null && $value !== ''))) {
                    $inputs[$number] = array_combine(self::HEADERS, $values);
                }
            }
        } finally {
            $book->disconnectWorksheets();
        }
        if (! $inputs) {
            throw ValidationException::withMessages(['file' => 'The template contains no data rows.']);
        }

        return DB::transaction(function () use ($file, $userId, $inputs) {
            $batch = CustomerImport::create(['type' => CustomerImport::TYPE_SPAREPART, 'file_name' => $file->getClientOriginalName(), 'status' => 'review', 'created_by_user_id' => $userId, 'lock_version' => 0]);
            $now = now();
            $records = [];
            foreach ($inputs as $number => $input) {
                $records[] = ['customer_import_id' => $batch->id, 'row_number' => $number, 'input' => json_encode($input, JSON_THROW_ON_ERROR), 'review' => '{}', 'created_at' => $now, 'updated_at' => $now];
            }
            foreach (array_chunk($records, 100) as $chunk) {
                DB::table('customer_import_rows')->insert($chunk);
            }
            $rows = $batch->rows()->orderBy('row_number')->get();
            $this->reviewRows($rows);
            $this->persistRows($rows);

            return $batch;
        });
    }

    public function payload(CustomerImport $batch): array
    {
        $batch->assertType(CustomerImport::TYPE_SPAREPART);
        $rows = $batch->rows()->orderBy('row_number')->get();
        $summary = array_fill_keys(['new', 'already_exists', 'warning', 'error', 'reorder', 'applicable'], 0);
        foreach ($rows as $row) {
            $summary[$row->review['status']]++;
            $summary['reorder'] += ($row->review['stock_status'] ?? null) === 'REORDER' ? 1 : 0;
            $summary['applicable'] += ! empty($row->review['can_apply']) ? 1 : 0;
        }

        return ['import' => $batch, 'summary' => $summary, 'rows' => $rows];
    }

    public function updateReview(CustomerImport $batch, int $version, array $changes, int $userId): array
    {
        return DB::transaction(function () use ($batch, $version, $changes, $userId) {
            $batch = CustomerImport::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $batch->assertType(CustomerImport::TYPE_SPAREPART);
            ImportAccess::assertOwner($batch, $userId);
            abort_unless($batch->status === 'review' && $batch->lock_version === $version, 409, 'Review changed or already applied. Reload the import.');
            $rows = $batch->rows()->orderBy('row_number')->get();
            $byId = $rows->keyBy('id');
            foreach ($changes as $change) {
                $row = $byId->get($change['id']);
                abort_unless($row, 404);
                $row->input = array_replace($row->input, array_intersect_key($change['input'], array_flip(self::HEADERS)));
            }
            $this->reviewRows($rows);
            $this->persistRows($rows);
            $batch->increment('lock_version');

            return $this->payload($batch);
        });
    }

    private function reviewRows(Collection $rows): void
    {
        $catalogue = [];
        foreach (RefSparepart::withTrashed()->get(['id', 'part_number', 'deleted_at', 'is_deleted']) as $part) {
            $key = mb_strtoupper(trim($part->part_number ?? ''));
            if ($key !== '') {
                $state = $part->deleted_at || $part->is_deleted ? 'deleted' : 'active';
                $catalogue[$key][$state] ??= $part->id;
            }
        }
        $seen = [];
        foreach ($rows as $row) {
            $row->review = $this->reviewRow($row->input, $row->row_number, $catalogue, $seen);
        }
    }

    private function reviewRow(array $input, int $number, array $catalogue, array &$seen): array
    {
        $get = fn ($key) => trim((string) ($input[$key] ?? ''));
        $nullable = fn ($key) => $get($key) === '' ? null : $get($key);
        $key = mb_strtoupper($get('Part Number*'));
        $fields = ['part_number' => $key, 'name' => $get('Name*'), 'brand' => $nullable('Brand'), 'supplier' => $nullable('Supplier'), 'price' => $get('Price') === '' ? '0' : $get('Price'), 'stock' => $get('Stock') === '' ? '0' : $get('Stock'), 'min_stock' => $get('Min Stock') === '' ? '0' : $get('Min Stock'), 'rack_location' => $nullable('Rack Location')];
        $validator = Validator::make($fields, [
            'part_number' => 'required|string|max:255', 'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255', 'supplier' => 'nullable|string|max:255', 'rack_location' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0|max:9999999999999.99|decimal:0,2',
            'stock' => 'required|integer|min:0|max:2147483647', 'min_stock' => 'required|integer|min:0|max:2147483647',
        ], [], ['part_number' => 'Part Number', 'name' => 'Name', 'min_stock' => 'Min Stock', 'rack_location' => 'Rack Location']);
        $errors = $validator->errors()->all();
        foreach (self::HEADERS as $header) {
            if (str_starts_with($get($header), '=')) {
                $errors[] = $header.' must contain a literal value, not an Excel formula.';
            }
        }
        $warnings = $get('Price') === '' ? ['Price is empty; 0.00 will be used if applied.'] : [];
        $duplicate = $key !== '' ? ($seen[$key] ?? null) : null;
        if ($duplicate !== null) {
            $errors[] = 'Duplicate Part Number in file; first occurrence is row '.$duplicate.'.';
        }
        if ($key !== '') {
            $seen[$key] ??= $number;
        }
        $active = $catalogue[$key]['active'] ?? null;
        $deleted = $catalogue[$key]['deleted'] ?? null;
        if (! $active && $deleted) {
            $warnings[] = 'Part Number belongs to a soft-deleted sparepart; this row will be skipped.';
        }
        $fields['price'] = $validator->errors()->has('price') ? null : number_format((float) $fields['price'], 2, '.', '');
        foreach (['stock', 'min_stock'] as $field) {
            $fields[$field] = $validator->errors()->has($field) ? null : (int) $fields[$field];
        }
        $status = $errors ? 'error' : ($active ? 'already_exists' : ($warnings ? 'warning' : 'new'));
        $stockStatus = $fields['stock'] === null || $fields['min_stock'] === null ? null : ($fields['stock'] <= $fields['min_stock'] ? 'REORDER' : 'SAFE');

        return [
            'template_version' => self::TEMPLATE_VERSION, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'part' => $fields, 'existing_sparepart_id' => $active ?? $deleted, 'duplicate_of_row' => $duplicate,
            'stock_status' => $stockStatus, 'can_apply' => ! $errors && ! $active && ! $deleted,
            'skip_reason' => $errors ? ($duplicate !== null ? 'duplicate_part_number' : 'validation_error') : ($active ? 'already_exists' : ($deleted ? 'soft_deleted' : null)),
        ];
    }

    private function persistRows(Collection $rows): void
    {
        $changes = [];
        foreach ($rows as $row) {
            if ($row->isDirty()) {
                $row->updated_at = now();
                $changes[] = $row->getAttributes();
            }
        }
        foreach (array_chunk($changes, 100) as $chunk) {
            DB::table('customer_import_rows')->upsert($chunk, ['id'], ['input', 'review', 'decision', 'result', 'updated_at']);
        }
    }
}
