<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\CustomerImport;
use App\Models\Aftersales\ServiceCustomer;
use App\Models\Leads\Lead;
use App\Models\Masters\Industry;
use App\Models\Masters\Province;
use App\Models\Masters\Region;
use App\Rules\GoogleMapsUrl;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CustomerImports
{
    public const TEMPLATE_VERSION = '2.2';

    public const HEADERS = ['Customer Name*', 'PIC Name', 'Phone', 'Email', 'Address', 'Google Maps Link', 'Province', 'City / Region', 'Industry', 'Daxtro Machine', 'Machine Category*', 'Machine Name', 'Brand', 'Capacity', 'Compressor', 'Refrigerant Type', 'Serial Number', 'Warranty Start', 'Warranty End', 'PM Contract', 'Note'];

    public function upload(UploadedFile $file, int $userId): CustomerImport
    {
        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setLoadSheetsOnly(['Import Customer']);
            $book = $reader->load($file->getRealPath());
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['file' => 'The file must be a readable .xlsx template.']);
        }
        $sheet = $book->getSheetByName('Import Customer');
        $lastColumn = Coordinate::stringFromColumnIndex(count(self::HEADERS));
        if (! $sheet || $sheet->rangeToArray('A1:'.$lastColumn.'1', null, false, false)[0] !== self::HEADERS || $sheet->getHighestDataColumn() !== $lastColumn) {
            throw ValidationException::withMessages(['file' => 'Headers must exactly match the Import Customer template.']);
        }
        $last = $sheet->getHighestDataRow();
        if ($last > 5001) {
            throw ValidationException::withMessages(['file' => 'A maximum of 5000 rows is allowed per import.']);
        }

        $inputs = [];
        for ($row = 2; $row <= $last; $row++) {
            $values = $sheet->rangeToArray('A'.$row.':'.$lastColumn.$row, null, false, false)[0];
            if (count(array_filter($values, fn ($v) => $v !== null && $v !== ''))) {
                $inputs[$row] = array_combine(self::HEADERS, $values);
            }
        }
        $book->disconnectWorksheets();
        abort_unless($inputs, 422, 'The template contains no data rows.');
        $context = $this->context($inputs);

        return DB::transaction(function () use ($file, $userId, $inputs, $context) {
            $batch = CustomerImport::create(['type' => CustomerImport::TYPE_CUSTOMER, 'file_name' => $file->getClientOriginalName(), 'created_by_user_id' => $userId, 'lock_version' => 0]);
            $now = now();
            $pending = [];
            foreach ($inputs as $number => $input) {
                $pending[] = ['customer_import_id' => $batch->id, 'row_number' => $number, 'input' => json_encode($input, JSON_THROW_ON_ERROR), 'review' => '{}', 'created_at' => $now, 'updated_at' => $now];
            }
            foreach (array_chunk($pending, 100) as $chunk) {
                DB::table('customer_import_rows')->insert($chunk);
            }
            $rows = $batch->rows()->orderBy('row_number')->get();
            foreach ($rows as $row) {
                $review = $this->review($row->input, $batch, $row->row_number, $context);
                $row->review = $review;
                $context->addPrior($row->id, $row->row_number, $review);
            }
            $this->persistRows($rows);

            return $batch;
        });
    }

    public function context(iterable $inputs): CustomerImportContext
    {
        $serials = [];
        foreach ($inputs as $input) {
            if (strtolower(trim((string) ($input['Daxtro Machine'] ?? ''))) !== 'no') {
                $serials[] = (string) ($input['Serial Number'] ?? '');
            }
        }

        $snapshot = MachineSerials::importSnapshot($serials);

        return new CustomerImportContext(app(CustomerMatching::class)->index(), $snapshot['existing'], $snapshot['archived']);
    }

    public function persistRows(iterable $rows): void
    {
        $changes = [];
        foreach ($rows as $row) {
            if (! $row->isDirty()) {
                continue;
            }
            $row->updated_at = now();
            $changes[] = $row->getAttributes();
        }
        foreach (array_chunk($changes, 100) as $chunk) {
            DB::table('customer_import_rows')->upsert($chunk, ['id'], ['input', 'review', 'decision', 'result', 'updated_at']);
        }
    }

    public function review(array $input, CustomerImport $batch, ?int $beforeRow = null, ?CustomerImportContext $context = null): array
    {
        $batch->assertType(CustomerImport::TYPE_CUSTOMER);
        if ($context === null) {
            $context = $this->context([$input]);
            foreach ($batch->rows()->when($beforeRow, fn ($q) => $q->where('row_number', '<', $beforeRow))->orderBy('row_number')->get() as $prior) {
                $context->addPrior($prior->id, $prior->row_number, $prior->review);
            }
        }
        $errors = $warnings = [];
        $get = fn ($key) => trim((string) ($input[$key] ?? ''));
        $kind = strtolower($get('Daxtro Machine'));
        $isDaxtro = $kind !== 'no';
        if (! in_array($kind, ['', 'yes', 'no'], true)) {
            $errors[] = 'Daxtro Machine must be Yes, No, or empty (Yes).';
        }
        $required = $isDaxtro ? ['Customer Name*', 'Machine Category*', 'Capacity'] : ['Customer Name*', 'Machine Name', 'Brand'];
        $ignored = $isDaxtro ? ['Brand'] : ['Serial Number', 'Warranty Start', 'Warranty End', 'PM Contract'];
        foreach ($required as $key) {
            if ($get($key) === '') {
                $errors[] = $key.' is required.';
            }
        }
        foreach (self::HEADERS as $key) {
            if (in_array($key, $ignored, true)) {
                if ($get($key) !== '') {
                    $warnings[] = $key.' is ignored for '.($isDaxtro ? 'Daxtro' : 'non-Daxtro').' machines.';
                }
            } elseif ($get($key) === '' && ! in_array($key, array_merge($required, ['Daxtro Machine', 'Machine Name', 'Compressor', 'Refrigerant Type']), true)) {
                $warnings[] = $key.' is empty.'.(in_array($key, ['Phone', 'Email'], true) ? ' Complete the customer contact before creating a new ticket.' : '');
            }
        }
        foreach (['Customer Name*', 'PIC Name', 'Machine Name', 'Brand', 'Serial Number', 'Compressor', 'Refrigerant Type'] as $key) {
            if (! in_array($key, $ignored, true) && mb_strlen($get($key)) > 255) {
                $errors[] = $key.' exceeds 255 characters.';
            }
        }
        try {
            $phone = CustomerMatching::phone($get('Phone'));
        } catch (\InvalidArgumentException $e) {
            $phone = null;
            $errors[] = $e->getMessage();
        }
        if ($phone === null && $get('Phone') !== '') {
            $warnings[] = 'Phone is empty after normalization. Complete the customer contact before creating a new ticket.';
        }
        $validator = Validator::make(['email' => $get('Email') ?: null, 'maps' => $get('Google Maps Link') ?: null], ['email' => 'nullable|email|max:255', 'maps' => ['nullable', 'max:2048', new GoogleMapsUrl]]);
        $errors = array_merge($errors, $validator->errors()->all());
        $province = $context->lookup(Province::class, $get('Province'));
        $region = $context->lookup(Region::class, $get('City / Region'));
        if (! $province && $get('Province')) {
            $warnings[] = 'Province not found. Select it during review.';
        }
        if (! $region && $get('City / Region')) {
            $warnings[] = 'City / Region not found. Select it during review.';
        }
        if ($region) {
            if ($province && (int) $province->id !== (int) $region->province_id) {
                $warnings[] = 'Province does not match City / Region; Province follows the selected region.';
            }
            $province = $context->province($region->province_id);
        }
        $industry = $context->lookup(Industry::class, $get('Industry'));
        if (! $industry && $get('Industry')) {
            $warnings[] = 'Industry not found; it will be left empty.';
        }
        $category = $context->categoryFor($get('Machine Category*'));
        if (! $category && $get('Machine Category*') !== '') {
            $errors[] = 'Machine Category was not found.';
        }
        $capacity = $get('Capacity');
        if ($capacity !== '' && (! is_numeric($capacity) || ! is_finite((float) $capacity) || (float) $capacity < 0)) {
            $errors[] = 'Capacity must be a non-negative number in tons.';
        }
        $start = $end = null;
        foreach ($isDaxtro ? ['Warranty Start' => 'start', 'Warranty End' => 'end'] : [] as $key => $target) {
            try {
                $$target = $this->date($input[$key] ?? null);
            } catch (\InvalidArgumentException $e) {
                $errors[] = $key.': '.$e->getMessage();
            }
        }
        if ($start && Carbon::parse($start)->isFuture()) {
            $warnings[] = 'Warranty Start is in the future; verify the year.';
        }
        if ($start && ! $end) {
            $end = Carbon::parse($start)->addMonthsNoOverflow(12)->toDateString();
        }
        if ($start && $end && $end < $start) {
            $errors[] = 'Warranty End must be on or after Warranty Start.';
        }
        $pm = $isDaxtro ? (strtolower($get('PM Contract')) ?: 'none') : 'none';
        if (! in_array($pm, ['none', 'basic', 'premium'], true)) {
            $errors[] = 'PM Contract must be Basic, Premium, or empty.';
        }
        $serial = $isDaxtro ? CustomerMatching::serial($get('Serial Number')) : null;
        $name = $get('Machine Name');
        $nameMode = $isDaxtro && $name === '' ? 'auto' : 'manual';
        if ($nameMode === 'auto' && $category && $capacity !== '') {
            try {
                $name = MachineNames::generate($category->code_prefix, $capacity, $get('Compressor'), $get('Refrigerant Type'));
            } catch (ValidationException $e) {
                $errors = array_merge($errors, $e->validator->errors()->all());
            }
        }
        $data = ['name' => $get('Customer Name*'), 'pic_name' => $get('PIC Name') ?: null, 'phone' => $phone, 'email' => $get('Email') ?: null, 'address' => $get('Address') ?: null, 'address_maps_url' => $get('Google Maps Link') ?: null, 'ref_province_id' => $province?->id, 'ref_region_id' => $region?->id];
        $machine = ['product_id' => null, 'is_daxtro' => $isDaxtro, 'machine_name' => $name, 'machine_name_mode' => $nameMode, 'ref_product_type_id' => $category?->id, 'industry_id' => $industry?->id, 'brand' => $isDaxtro ? null : ($get('Brand') ?: null), 'capacity' => $capacity === '' ? null : $capacity, 'compressor' => $get('Compressor') ?: null, 'refrigerant' => $get('Refrigerant Type') ?: null, 'serial_number' => $serial, 'warranty_start' => $start, 'warranty_end' => $end, 'pm_contract_status' => $pm, 'note' => $get('Note') ?: null];
        $candidates = array_merge($context->matches->candidates($data), $context->prior->candidates($data));
        for ($i = 0; $i < ($context->priorSerialCounts[$serial] ?? 0); $i++) {
            $warnings[] = 'Serial repeated in this file: one machine, last applied row owns it; earliest warranty is preserved.';
        }
        $existingSerial = $serial && isset($context->existingSerials[$serial]);
        $keepMachineArchived = $serial && isset($context->archivedSerials[$serial]);
        if ($keepMachineArchived) {
            $warnings[] = 'This serial belongs to a deleted customer. The customer will be matched or created as new; the existing machine stays archived and will not be copied or moved.';
        }
        $exact = collect($candidates)->where('status', 'exact');
        $targets = $exact->where('type', '!=', 'row')->map(fn ($c) => $c['type'] === 'lead' && ! empty($c['customer_id']) ? 'customer:'.$c['customer_id'] : $c['type'].':'.$c['id'])->unique();
        if ($targets->isEmpty() && $exact->contains('type', 'row')) {
            $targets = collect(['file_customer']);
        }
        $status = $existingSerial && ! $keepMachineArchived ? 'already_exists' : ($targets->count() === 1 ? 'exact' : ($candidates ? 'possible' : 'new'));

        return ['template_version' => self::TEMPLATE_VERSION, 'status' => $errors ? 'error' : $status, 'errors' => array_values(array_unique($errors)), 'warnings' => $warnings, 'customer' => $data, 'machine' => $machine, 'candidates' => $candidates, 'keep_machine_archived' => (bool) $keepMachineArchived];
    }

    public function date(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '' || trim((string) $value) === '-') {
            return null;
        }
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 100000) {
            $date = Date::excelToDateTimeObject($value)->format('Y-m-d');

            return $date === '2000-12-30' ? null : $date;
        }
        $value = mb_strtolower(trim((string) $value));
        if (preg_match('/^30[-\/]dec[-\/]0{2,4}$/i', $value)) {
            return null;
        }
        $value = strtr($value, ['januari' => 'jan', 'februari' => 'feb', 'maret' => 'mar', 'april' => 'apr', 'mei' => 'may', 'juni' => 'jun', 'juli' => 'jul', 'agustus' => 'aug', 'agt' => 'aug', 'agu' => 'aug', 'september' => 'sep', 'sept' => 'sep', 'oktober' => 'oct', 'okt' => 'oct', 'november' => 'nov', 'desember' => 'dec', 'des' => 'dec']);
        foreach (['!d/m/Y', '!d-m-Y', '!d-M-y', '!d-M-Y', '!Y-m-d', '!d/m/y', '!d-m-y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            $issues = \DateTimeImmutable::getLastErrors();
            if ($date && (! $issues || (! $issues['warning_count'] && ! $issues['error_count']))) {
                return $date->format('Y-m-d');
            }
        }
        throw new \InvalidArgumentException('Unrecognized date; use dd/mm/yyyy.');
    }

    public function payload(CustomerImport $batch): array
    {
        $batch->assertType(CustomerImport::TYPE_CUSTOMER);
        $rows = $batch->rows()->orderBy('row_number')->get();
        $counts = array_fill_keys(['exact', 'possible', 'new', 'already_exists', 'error', 'warning'], 0);
        foreach ($rows as $row) {
            $counts[$row->review['status']]++;
            if ($row->review['warnings']) {
                $counts['warning']++;
            }
        }

        return ['import' => $batch, 'summary' => $counts, 'rows' => $rows];
    }

    public function apply(CustomerImport $batch, int $version, int $userId): array
    {
        return DB::transaction(function () use ($batch, $version, $userId) {
            $batch = CustomerImport::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $batch->assertType(CustomerImport::TYPE_CUSTOMER);
            ImportAccess::assertOwner($batch, $userId);
            if ($batch->status === 'applied') {
                return $this->payload($batch);
            }
            abort_unless($batch->lock_version === $version, 409, 'Review changed. Reload before Apply.');
            $rows = $batch->rows()->orderBy('row_number')->lockForUpdate()->get();
            abort_if($rows->contains(fn ($row) => ($row->review['template_version'] ?? null) !== self::TEMPLATE_VERSION), 409, 'The import template changed. Upload the latest Customer template and review again.');
            foreach ($rows as $row) {
                if ($row->review['status'] === 'possible' && ! $row->decision) {
                    throw ValidationException::withMessages(['rows' => 'Resolve all Possible matches before Apply (row '.$row->row_number.').']);
                }
            }
            $lockKeys = [];
            $incomingSerials = [];
            foreach ($rows as $row) {
                $lockKeys = array_merge($lockKeys, CustomerMutationLocks::customerKeys($row->review['customer']), CustomerMutationLocks::serialKey($row->review['machine']['serial_number']));
                $incomingSerials[] = $row->review['machine']['serial_number'];
            }
            CustomerMutationLocks::acquire($lockKeys);
            // Build the snapshot only after acquiring identity/serial locks.
            $index = app(CustomerMatching::class)->index();
            $serialSnapshot = MachineSerials::importSnapshot($incomingSerials);
            $existingSerials = $serialSnapshot['existing'];
            $archivedSerials = $serialSnapshot['archived'];
            $customers = $index->customers;
            $findCustomer = function (int $id) use (&$customers) {
                return $customers[$id] ??= ServiceCustomer::findOrFail($id);
            };
            $leads = [];
            $linkedLeads = [];
            $resolved = [];
            $serials = [];
            $created = $matched = $errorCount = 0;
            foreach ($rows as $row) {
                $review = $row->review;
                $decision = $row->decision ?? [];
                if (in_array($review['status'], ['error', 'already_exists'], true) || ($decision['action'] ?? '') === 'skip') {
                    $row->result = ['status' => 'skipped'];
                    $errorCount += $review['status'] === 'error' ? 1 : 0;

                    continue;
                }
                $machine = $review['machine'];
                $serial = $machine['serial_number'];
                $keepMachineArchived = ! empty($review['keep_machine_archived']);
                abort_if($keepMachineArchived && ! isset($archivedSerials[$serial]), 409, 'The archived machine changed. Review this import again.');
                // Recheck at Apply, including another batch applied since upload.
                if ($serial && ! $keepMachineArchived && ! isset($serials[$serial]) && isset($existingSerials[$serial])) {
                    $row->result = ['status' => 'already_exists'];

                    continue;
                }
                $customer = null;
                $lead = null;
                if (($decision['action'] ?? '') === 'link') {
                    if (! empty($decision['customer_id'])) {
                        $customer = $findCustomer((int) $decision['customer_id']);
                    } elseif (! empty($decision['row_id'])) {
                        $customer = isset($resolved[$decision['row_id']]) ? $findCustomer($resolved[$decision['row_id']]) : null;
                    } elseif (! empty($decision['lead_id'])) {
                        $lead = $leads[$decision['lead_id']] ??= Lead::with('customerDeal')->findOrFail($decision['lead_id']);
                        $customerId = $linkedLeads[$lead->id] ?? $lead->customerDeal?->service_customer_id;
                        $customer = $customerId ? $findCustomer((int) $customerId) : null;
                    }
                    abort_if(! $customer && ! $lead, 422, 'The selected row must resolve to a customer before it can be linked.');
                } elseif (($decision['action'] ?? '') !== 'create_new') {
                    // Refresh DB matches instead of trusting a stale preview.
                    $matches = $index->candidates($review['customer']);
                    $exactCustomers = collect($matches)->where('status', 'exact')->map(fn ($m) => $m['type'] === 'customer' ? $m['id'] : ($m['customer_id'] ?? null))->filter()->unique();
                    abort_if($exactCustomers->count() > 1, 409, 'Customer matches changed. Review this import again.');
                    if ($exactCustomers->count() === 1) {
                        $customer = $findCustomer((int) $exactCustomers->first());
                    }
                    if (! $customer) {
                        $prior = collect($review['candidates'])->where('type', 'row')->where('status', 'exact')->first();
                        if ($prior && isset($resolved[$prior['id']])) {
                            $customer = $findCustomer($resolved[$prior['id']]);
                        }
                    }
                    $leadMatches = collect($matches)->where('type', 'lead')->where('status', 'exact')->values();
                    if ($leadMatches->count() === 1) {
                        $lead = $leads[$leadMatches[0]['id']] ??= Lead::findOrFail($leadMatches[0]['id']);
                    }
                    if (! $customer && ! $lead && collect($matches)->contains('status', 'possible')) {
                        abort(409, 'New possible matches appeared. Review this import again.');
                    }
                }
                if (! $customer) {
                    $customer = ServiceCustomer::create($review['customer'] + ['source' => 'import', 'created_by' => $userId]);
                    $customers[$customer->id] = $customer;
                    $index->add('customer', $customer->id, $customer->getAttributes());
                    $created++;
                } else {
                    $matched++;
                }
                abort_if($lead && isset($linkedLeads[$lead->id]) && $linkedLeads[$lead->id] !== $customer->id, 409, 'This Deal is already linked to another customer.');
                if ($lead && ! isset($linkedLeads[$lead->id])) {
                    app(DealConversion::class)->link($lead, $customer);
                    $linkedLeads[$lead->id] = $customer->id;
                    $index->linkLead($lead->id, $customer->id);
                }
                $resolved[$row->id] = $customer->id;
                if ($keepMachineArchived) {
                    $row->result = ['status' => 'applied', 'customer_id' => $customer->id, 'machine_id' => null, 'machine_status' => 'kept_archived'];

                    continue;
                }
                if ($serial && isset($serials[$serial])) {
                    $unit = $serials[$serial];
                    $previous = $findCustomer($unit->customer_id);
                    if ($unit->warranty_start && (! $machine['warranty_start'] || $unit->warranty_start->toDateString() < $machine['warranty_start'])) {
                        $machine['warranty_start'] = $unit->warranty_start->toDateString();
                        $machine['warranty_end'] = $unit->warranty_end?->toDateString();
                    }
                    $machine['note'] = trim(implode("\n", array_filter([$unit->note, $machine['note'], $previous->id !== $customer->id ? 'Previously owned by '.$previous->name : null])));
                    $unit->fill($machine + ['customer_id' => $customer->id])->save();
                } else {
                    $unit = $customer->products()->create($machine + ['created_by' => $userId]);
                }
                if ($serial) {
                    $serials[$serial] = $unit;
                }
                $row->result = ['status' => 'applied', 'customer_id' => $customer->id, 'machine_id' => $unit->id];
            }
            $this->persistRows($rows);
            $batch->update(['status' => 'applied', 'applied_at' => now(), 'created_count' => $created, 'matched_count' => $matched, 'error_count' => $errorCount, 'lock_version' => $version + 1]);

            return $this->payload($batch);
        }, 3);
    }
}
