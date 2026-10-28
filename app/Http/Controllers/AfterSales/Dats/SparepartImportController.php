<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\CustomerImport;
use App\Services\Aftersales\ImportAccess;
use App\Services\Aftersales\SparepartImports;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SparepartImportController extends Controller
{
    public function template(Request $request)
    {
        ImportAccess::authorize($request->user(), CustomerImport::TYPE_SPAREPART);

        return response()->download(resource_path('imports/Template_Import_Sparepart.xlsx'));
    }

    public function upload(Request $request, SparepartImports $service)
    {
        ImportAccess::authorize($request->user(), CustomerImport::TYPE_SPAREPART);
        $request->validate(['file' => 'required|file|mimes:xlsx|max:20480']);

        return response()->json(['data' => $service->payload($service->upload($request->file('file'), (int) $request->user()->id))], 201);
    }

    public function show(Request $request, CustomerImport $import, SparepartImports $service)
    {
        ImportAccess::authorize($request->user(), CustomerImport::TYPE_SPAREPART, $import);

        return response()->json(['data' => $service->payload($import)]);
    }

    public function review(Request $request, CustomerImport $import, SparepartImports $service)
    {
        ImportAccess::authorize($request->user(), CustomerImport::TYPE_SPAREPART, $import);
        $input = $request->validate([
            'lock_version' => 'required|integer|min:0', 'rows' => 'required|array|max:5000',
            'rows.*.id' => 'required|integer|distinct', 'rows.*.input' => 'required|array',
            'rows.*.input.*' => ['nullable', function ($attribute, $value, $fail) {
                if (! is_scalar($value)) {
                    $fail('Import cells must contain scalar values.');
                }
            }],
        ]);

        foreach ($input['rows'] as $index => $row) {
            if (array_diff(array_keys($row['input']), SparepartImports::HEADERS)) {
                throw ValidationException::withMessages(['rows.'.$index.'.input' => 'Import columns must match the Sparepart template.']);
            }
        }

        return response()->json(['data' => $service->updateReview($import, $input['lock_version'], $input['rows'], (int) $request->user()->id)]);
    }

    public function export(Request $request, CustomerImport $import)
    {
        ImportAccess::authorize($request->user(), CustomerImport::TYPE_SPAREPART, $import);
        $request->validate(['errors_only' => 'sometimes|boolean']);
        $rows = $import->rows()->orderBy('row_number')->get();
        if ($request->boolean('errors_only', true)) {
            $rows = $rows->filter(fn ($row) => $row->review['status'] === 'error');
        }
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Import Review');
        $sheet->fromArray(array_merge(SparepartImports::HEADERS, ['Row', 'Status', 'Errors', 'Warnings']), null, 'A1');
        foreach ($rows->values() as $offset => $row) {
            $values = array_map(fn ($header) => $row->input[$header] ?? '', SparepartImports::HEADERS);
            $values = array_merge($values, [$row->row_number, $row->review['status'], implode('; ', $row->review['errors']), implode('; ', $row->review['warnings'])]);
            foreach ($values as $column => $value) {
                $sheet->setCellValueExplicit([$column + 1, $offset + 2], (string) $value, DataType::TYPE_STRING);
            }
        }

        return response()->streamDownload(function () use ($book) {
            try {
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'sparepart-import-'.$import->id.'-review.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
