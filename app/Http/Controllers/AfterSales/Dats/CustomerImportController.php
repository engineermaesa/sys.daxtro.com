<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\CustomerImport;
use App\Services\Aftersales\CustomerImports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CustomerImportController extends Controller
{
    private function authorizeImport(Request $request, ?CustomerImport $import = null): void
    {
        abort_unless($request->user()?->hasPermission('masters.service-customers'), 403);
        if ($import) {
            abort_unless((int) $import->created_by_user_id === $request->user()->id, 403, 'Only the uploader can access this import.');
        }
    }

    public function template(Request $request)
    {
        $this->authorizeImport($request);

        return response()->download(resource_path('imports/DAXX_Import_Customer_Template.xlsx'));
    }

    public function upload(Request $request, CustomerImports $service)
    {
        $this->authorizeImport($request);
        $request->validate(['file' => 'required|file|mimes:xlsx|max:20480']);

        return response()->json(['data' => $service->payload($service->upload($request->file('file'), $request->user()->id))], 201);
    }

    public function show(Request $request, CustomerImport $import, CustomerImports $service)
    {
        $this->authorizeImport($request, $import);

        return response()->json(['data' => $service->payload($import)]);
    }

    public function review(Request $request, CustomerImport $import, CustomerImports $service)
    {
        $this->authorizeImport($request, $import);
        $input = $request->validate([
            'lock_version' => 'required|integer|min:0', 'rows' => 'required|array|max:5000',
            'rows.*.id' => 'required|integer|distinct', 'rows.*.input' => 'sometimes|array',
            'rows.*.decision' => 'nullable|array:action,customer_id,lead_id,row_id',
            'rows.*.decision.action' => 'required_with:rows.*.decision|in:link,create_new,skip',
            'rows.*.decision.customer_id' => 'nullable|integer|exists:service_customers,id',
            'rows.*.decision.lead_id' => 'nullable|integer|exists:leads,id',
            'rows.*.decision.row_id' => 'nullable|integer',
        ]);
        DB::transaction(function () use ($input, $import, $service) {
            $batch = CustomerImport::whereKey($import->id)->lockForUpdate()->firstOrFail();
            abort_unless($batch->status === 'review' && $batch->lock_version === (int) $input['lock_version'], 409, 'Review changed or already applied. Reload the import.');
            foreach ($input['rows'] as $item) {
                $row = $batch->rows()->findOrFail($item['id']);
                $raw = array_replace($row->input, array_intersect_key($item['input'] ?? [], array_flip(CustomerImports::HEADERS)));
                $row->update(['input' => $raw]);
            }
            // Rebuild in file order after corrections; downstream matches cannot remain stale.
            foreach ($batch->rows()->orderBy('row_number')->get() as $row) {
                $review = $service->review($row->input, $batch, $row->row_number);
                $item = collect($input['rows'])->firstWhere('id', $row->id);
                $decision = array_key_exists('decision', $item ?? []) ? $item['decision'] : ($review === $row->review ? $row->decision : null);
                if (($decision['action'] ?? '') === 'link') {
                    $targets = array_filter([$decision['customer_id'] ?? null, $decision['lead_id'] ?? null, $decision['row_id'] ?? null]);
                    abort_unless(count($targets) === 1, 422, 'Select exactly one link target.');
                    if (! empty($decision['row_id'])) {
                        abort_unless($batch->rows()->where('row_number', '<', $row->row_number)->whereKey($decision['row_id'])->exists(), 422, 'Only an earlier row in this import can be linked.');
                    }
                }
                $row->update(['review' => $review, 'decision' => $decision]);
            }
            $batch->increment('lock_version');
        });

        return response()->json(['data' => $service->payload($import->fresh())]);
    }

    public function apply(Request $request, CustomerImport $import, CustomerImports $service)
    {
        $this->authorizeImport($request, $import);
        $input = $request->validate(['lock_version' => 'required|integer|min:0']);

        return response()->json(['data' => $service->apply($import, $input['lock_version'], $request->user()->id)]);
    }

    public function export(Request $request, CustomerImport $import)
    {
        $this->authorizeImport($request, $import);
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Import Review');
        $sheet->fromArray(array_merge(CustomerImports::HEADERS, ['Row', 'Status', 'Errors', 'Warnings', 'Decision', 'Result']), null, 'A1');
        $line = 2;
        foreach ($import->rows()->orderBy('row_number')->get() as $row) {
            $values = array_map(fn ($key) => $row->input[$key] ?? '', CustomerImports::HEADERS);
            $values = array_merge($values, [$row->row_number, $row->review['status'], implode('; ', $row->review['errors']), implode('; ', $row->review['warnings']), json_encode($row->decision), json_encode($row->result)]);
            foreach ($values as $column => $value) {
                $sheet->setCellValueExplicit([$column + 1, $line], (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $line++;
        }

        return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'), 'customer-import-'.$import->id.'-review.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
