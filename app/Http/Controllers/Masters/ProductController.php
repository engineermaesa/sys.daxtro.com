<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Masters\Product;
use App\Models\Masters\ProductCategory;
use App\Models\Masters\Part;
use App\Http\Classes\ActivityLogger;
use App\Models\Masters\ProductType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Products';
        return $this->render('pages.masters.products.index');
    }

    public function list(Request $request)
    {
        $query = Product::with(['categories', 'type']);

        if (($request->is('api/*') || $request->wantsJson()) && !$request->has('draw')) {
            $products = $query->get();
            return response()->json([
                'status' => true,
                'data' => $products,
            ]);
        }

        return DataTables::of($query)
            ->addColumn('product_type_name', function ($row) {
                return $row->type->name ?? '-';
            })
            ->addColumn('category_name', function ($row) {
                return $row->categories->pluck('name')->implode(', ');
            })
            ->addColumn('actions', function ($row) {
                $edit = route('masters.products.form', $row->id);
                $del  = route('masters.products.delete', $row->id);

                return '
                    <a href="' . $edit . '" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="' . $del . '" data-id="' . $row->id . '" data-table="productsTable" class="btn btn-sm btn-danger delete-data"><i class="bi bi-trash"></i> Delete</a>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function form(Request $request, $id = null)
    {
        $types = ProductType::all();
        $form_data = $id ? Product::with(['categories', 'parts'])->findOrFail($id) : new Product();
        $categories = ProductCategory::all();
        $parts = Part::all();
        $selectedCategories = $form_data->categories->pluck('id')->toArray();
        $selectedParts = $form_data->parts->pluck('id')->toArray();
        $selectedType = $form_data->product_type_id;

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'data' => [
                    'form_data' => $form_data,
                    'categories' => $categories,
                    'parts' => $parts,
                    'selectedCategories' => $selectedCategories,
                    'selectedParts' => $selectedParts,
                    'selectedType' => $selectedType,
                    'types' => $types,
                ],
            ]);
        }

        return $this->render('pages.masters.products.form', compact('form_data', 'categories', 'parts', 'selectedCategories', 'selectedParts', 'selectedType', 'types'));
    }



    public function save(Request $request, $id = null)
    {
        $request->validate([
            'product_type_id'     => 'required|exists:ref_product_types,id',
            'category_ids'        => 'nullable|array',
            'category_ids.*'      => 'exists:ref_product_categories,id',
            'part_ids'            => 'nullable|array',
            'part_ids.*'          => 'exists:ref_parts,id',
            'sku'                 => 'required',
            'name'                => 'required',
            'description'         => 'nullable',
            'vat'                 => 'nullable|numeric',
            'corporate_price'     => 'nullable|string',
            'government_price'    => 'nullable|string',
            'personal_price'      => 'nullable|string',
            'fob_price'           => 'nullable|string',
            'bdi_price'           => 'nullable|string',
            'warranty_available'  => 'nullable|boolean',
            'warranty_time_month' => 'nullable|integer',
        ]);

        $sanitizeCurrency = fn($value) => $value !== null ? (int) str_replace(',', '', $value) : null;

        $product = $id ? Product::findOrFail($id) : new Product();
        $before = $id ? $product->toArray() : null;

        $product->product_type_id = $request->product_type_id;
        $product->sku = $request->sku;
        $product->name = $request->name;
        $product->description = $request->description;
        $product->vat = $request->vat;
        $product->corporate_price = $sanitizeCurrency($request->corporate_price);
        $product->government_price = $sanitizeCurrency($request->government_price);
        $product->personal_price = $sanitizeCurrency($request->personal_price);
        $product->fob_price = $sanitizeCurrency($request->fob_price);
        $product->bdi_price = $sanitizeCurrency($request->bdi_price);
        $product->warranty_available = $request->boolean('warranty_available');
        $product->warranty_time_month = $request->warranty_available ? $request->warranty_time_month : null;
        $product->save();

        $product->categories()->sync($request->category_ids ?? []);
        $product->parts()->sync($request->part_ids ?? []);

        $after = $product->fresh()->toArray();

        ActivityLogger::writeLog(
            $id ? 'update_product' : 'create_product',
            $id ? 'Updated product' : 'Created new product',
            $product,
            ['before' => $before, 'after' => $after],
            $request->user()
        );

        return $this->setJsonResponse('Product saved successfully');
    }

    public function delete($id)
    {
        $product = Product::findOrFail($id);

        ActivityLogger::writeLog(
            'delete_product',
            'Deleted product',
            $product,
            $product->toArray(),
            request()->user()
        );

        $product->delete();

        return $this->setJsonResponse('Product deleted successfully');
    }

    public function import()
    {
        $this->pageTitle = 'Import Products';
        return $this->render('pages.masters.products.import');
    }

    public function importTemplate()
    {
        $types = ProductType::all(['id', 'name']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        // headers
        $headers = ['PRODUCT_TYPE *', 'SKU', 'NAME *', 'FOB_PRICE', 'BDI_PRICE', 'CORPORATE_PRICE', 'GOVERNMENT_PRICE', 'PERSONAL_PRICE'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $col++;
        }

        // style header row: background #115641 and white text
        $lastColIndex = chr(ord('A') + count($headers) - 1);
        $headerRange = "A1:{$lastColIndex}1";
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF115641');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // autosize columns
        for ($c = 'A'; $c <= $lastColIndex; $c++) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
            if ($c === $lastColIndex) break; // prevent infinite loop when reaching beyond 'Z' logic
        }

        // Product Types sheet for reference
        $typeSheet = $spreadsheet->createSheet();
        $typeSheet->setTitle('Product Types');
        $typeSheet->setCellValue('A1', 'id');
        $typeSheet->setCellValue('B1', 'name');
        $row = 2;
        foreach ($types as $t) {
            $typeSheet->setCellValue('A' . $row, $t->id);
            $typeSheet->setCellValue('B' . $row, $t->name);
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            // apply data validation (dropdown) to product_type column A rows 2..500
            $range = "'Product Types'!\$B\$2:\$B\$" . $lastRow;
            for ($r = 2; $r <= 500; $r++) {
                $validation = $sheet->getCell('A' . $r)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Invalid input');
                $validation->setError('Value is not in the list.');
                $validation->setFormula1($range);
            }
        }

        // style Product Types header
        $typeSheet->getStyle('A1:B1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF115641');
        $typeSheet->getStyle('A1:B1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
        $typeSheet->getStyle('A1:B1')->getFont()->setBold(true);
        $typeSheet->getColumnDimension('A')->setAutoSize(true);
        $typeSheet->getColumnDimension('B')->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'products_import_template_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }

    public function importPreview(Request $request)
    {
        $this->pageTitle = 'Import Products - Preview';

        $request->validate([
            'import_file' => 'required|file',
        ]);

        $file = $request->file('import_file');
        $path = $file->getRealPath();

        try {
            $reader = IOFactory::createReaderForFile($path);
            $spreadsheet = $reader->load($path);
        } catch (\Exception $e) {
            return $this->setJsonResponse('Failed to read uploaded file: ' . $e->getMessage(), false);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        // read headers
        $headers = [];
        $col = 'A';
        while (true) {
            $val = $sheet->getCell($col . '1')->getValue();
            if ($val === null && $col > $highestCol) break;
            $headers[] = (string) $val;
            if ($col === $highestCol) break;
            $col++;
        }

        $rows = [];
        $previewIndex = 0;
        $maxPreview = min(500, max(2, (int) $highestRow));
        for ($r = 2; $r <= $maxPreview; $r++) {
            $cells = [];
            $col = 'A';
            $c = 0;
            while (true) {
                $cellValue = $sheet->getCell($col . $r)->getFormattedValue();
                $cells[] = $cellValue;
                $c++;
                if ($col === $highestCol) break;
                $col++;
            }
            // stop if row is completely empty
            $allEmpty = true;
            foreach ($cells as $cv) { if ($cv !== null && $cv !== '') { $allEmpty = false; break; } }
            if ($allEmpty) continue;

            $rows[] = [
                'preview_index' => $previewIndex++,
                'cells' => $cells,
                'row_class' => '',
            ];
        }

        $previewTableConfig = [
            'tabs' => [
                'all' => [
                    'label' => 'All',
                    'headers' => $headers,
                    'rows' => $rows,
                ],
            ],
            'default_tab' => 'all',
        ];

        // persist parsed preview into session so importStore can use it
        session(['products_import' => [
            'headers' => $headers,
            'rows' => $rows,
        ]]);

        $types = ProductType::all(['id', 'name']);
        return $this->render('pages.masters.products.import', compact('rows', 'previewTableConfig', 'types'));
    }

    public function importStore(Request $request)
    {
        $message = 'Import successful';

        // prefer posted edited rows if available
        if ($request->has('rows')) {
            $postedRows = $request->input('rows');
            $postedHeaders = $request->input('headers', []);
            $import = ['headers' => $postedHeaders, 'rows' => []];
            foreach ($postedRows as $idx => $r) {
                $import['rows'][] = ['preview_index' => $idx, 'cells' => $r['cells'] ?? []];
            }
        } else {
            $import = session('products_import');
        }

        if (empty($import) || empty($import['rows'])) {
            $err = 'No import preview data found. Please re-upload and preview before submitting.';
            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'application/json')) {
                return $this->setJsonResponse($err, [], 422);
            }
            return redirect()->back()->with('error', $err);
        }

        $headers = $import['headers'];
        $rows = $import['rows'];

        $created = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $r) {
                $cells = $r['cells'] ?? [];
                // map by header
                $data = [];
                foreach ($headers as $i => $h) {
                    $key = trim($h);
                    $value = $cells[$i] ?? null;
                    $data[$key] = $value;
                }

                // find product type by name (allow name or id)
                $typeId = null;
                $pt = $data['PRODUCT_TYPE'] ?? $data['PRODUCT_TYPE *'] ?? null;
                if ($pt !== null) {
                    // try by id first
                    if (is_numeric($pt)) {
                        $type = ProductType::find((int)$pt);
                    } else {
                        $type = ProductType::where('name', trim($pt))->first();
                    }
                    if ($type) $typeId = $type->id;
                }

                $sanitizeCurrency = fn($value) => $value !== null && $value !== '' ? (int) preg_replace('/[^0-9]/', '', $value) : null;

                $product = new Product();
                $product->product_type_id = $typeId;
                $product->sku = isset($data['SKU']) ? trim($data['SKU']) : null;
                $product->name = isset($data['NAME']) ? trim($data['NAME']) : null;
                $product->fob_price = $sanitizeCurrency($data['FOB_PRICE'] ?? null);
                $product->bdi_price = $sanitizeCurrency($data['BDI_PRICE'] ?? null);
                $product->corporate_price = $sanitizeCurrency($data['CORPORATE_PRICE'] ?? null);
                $product->government_price = $sanitizeCurrency($data['GOVERNMENT_PRICE'] ?? null);
                $product->personal_price = $sanitizeCurrency($data['PERSONAL_PRICE'] ?? null);

                // basic validation: require name
                if (empty($product->name)) {
                    $errors[] = 'Missing product name for a row; skipped.';
                    continue;
                }

                $product->save();
                $created++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors[] = 'Import failed: ' . $e->getMessage();
        }

        // If there are errors, keep preview data in session and redirect back to import page with errors
        if (!empty($errors)) {
            session(['products_import' => ['headers' => $headers, 'rows' => $rows]]);
            session(['import_errors' => $errors]);

            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'application/json')) {
                return $this->setJsonResponse('Import completed with errors', ['created' => $created, 'errors' => $errors], 207);
            }

            return redirect()->route('masters.products.import')->with('error', 'Some rows failed to import. See errors below.');
        }

        // success: clear session preview data and redirect to index with success
        session()->forget('products_import');
        $message = "Imported {$created} products.";

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'application/json')) {
            return $this->setJsonResponse($message, ['created' => $created]);
        }

        return redirect(url('/masters/products/'))->with('success', $message);
    }
}
