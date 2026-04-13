<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadSource;
use App\Models\Leads\LeadSegment;
use App\Models\Leads\LeadStatus;
use App\Models\Leads\LeadClaim;
use App\Models\Leads\LeadMeeting;
use App\Models\Leads\LeadStatusLog;
use App\Models\Masters\Region;
use App\Models\Masters\Industry;
use App\Models\Masters\Jabatan;
use App\Models\Masters\MeetingType;
use App\Models\Masters\ExpenseType;
use App\Models\Masters\Product;
use App\Models\Orders\Quotation;
use App\Models\Orders\QuotationItems;
use App\Models\Orders\QuotationLog;
use App\Models\Orders\MeetingExpense;
use App\Models\Orders\MeetingExpenseDetail;
use App\Models\Orders\FinanceRequest;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportLeadController extends Controller
{
    private function authorizeSuperAdmin()
    {
        // Allow access to users who have the explicit 'leads.import' permission
        if (! auth()->check() || ! auth()->user()->hasPermission('leads.import')) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->authorizeSuperAdmin();
        $this->pageTitle = 'Import Leads';
        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json(['pageTitle' => 'Import Leads']);
        }

        return $this->render('pages.leads.import');
    }

    public function template(Request $request)
    {
        $this->authorizeSuperAdmin();
        $baseHeaders = [
            'source_id*',          // master lead source (lihat sheet Lead Sources)
            'segment_id*',         // master lead segment (lihat sheet Lead Segments)
            'industry_id',         // master industry (lihat sheet Industries)
            'region_id',           // boleh null = all regions / no region (lihat sheet Regions)
            'company_name',
            'company_address',
            'lead_title',          // Mr/Mrs/Ms/Dr (akan digabung ke lead_name di sistem)
            'lead_name',           // nama PIC utama
            'lead_position',       // jabatan_id (lihat sheet Jabatans)
            'lead_phone',
            'lead_email',
            'lead_needs',
            'nip_sales',           // jika diisi → langsung diklaim ke sales tsb
            'created_at',          // akan dipakai sebagai published_at
            'status_stage',        // cold | warm | hot | deal | available
        ];

        $meetingHeaders = [
            'Meeting Type',
            'Meeting URL',
            'Start Time Meeting',
            'End Time Meeting',
            'city_id',           // city_id (pakai id dari tab Regions)
            'Address',
            'Expense Type',
            'Notes',
            'Amount',
        ];
        /*
         * HOT quotation columns (product..description) temporarily disabled.
         * Previous definition:
         * $quotationHeaders = [...];
         * $importHeaders = array_merge($baseHeaders, $meetingHeaders, $quotationHeaders);
         */

        $importHeaders = array_merge($baseHeaders, $meetingHeaders);

        // Needs options harus sama dengan opsi di form Leads (Needs / Ice Machine Type)
        $needsOptions = [
            'Tube Ice',
            'Cube Ice',
            'Block Ice',
            'Flake ice',
            'Slurry Ice',
            'Flake Ice',
            'Cold Room',
            'Other ( Keperluan Kustom )',
        ];
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import');

        $sheet->fromArray($importHeaders, null, 'A1');
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'],
            ],
        ];
        $lastColIndex = count($importHeaders);
        $lastCol = Coordinate::stringFromColumnIndex($lastColIndex); // hitung kolom terakhir dinamis (mendukung > Z)
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);

        // Ganti warna header untuk kolom Meeting* menjadi #FFF1C2
        $meetingHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF1C2'],
            ],
        ];

        // Style HOT (quotation) disimpan untuk nanti jika kolom HOT diaktifkan lagi
        $hotHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FDD3D0'],
            ],
        ];

        $baseHeaderCount = count($baseHeaders);
        $meetingHeaderCount = count($meetingHeaders);

        // Apply style untuk header meeting
        for ($i = 1; $i <= $meetingHeaderCount; $i++) {
            $colIndex = $baseHeaderCount + $i; // 1-based
            $colLetter = chr(64 + $colIndex); // 1 -> A, 2 -> B, dst.
            $sheet->getStyle($colLetter . '1')->applyFromArray($meetingHeaderStyle);
        }

        // Style untuk header HOT (product..description) dinonaktifkan sementara, karena kolom tersebut tidak dipakai di template saat ini

        $sheet->freezePane('A2');
        for ($colIndex = 1; $colIndex <= $lastColIndex; $colIndex++) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Fetch master data
        $sources      = LeadSource::select('id', 'name')->orderBy('id')->get();
        $segments     = LeadSegment::select('id', 'name')->orderBy('id')->get();
        $regions      = Region::with(['regional', 'province', 'branch'])->orderBy('id')->get();
        $industries   = Industry::select('id', 'name')->orderBy('id')->get();
        $jabatans     = Jabatan::select('id', 'name')->orderBy('id')->get();
        $meetingTypes = MeetingType::select('id', 'name')->orderBy('id')->get();
        $expenseTypes = ExpenseType::select('id', 'name')->orderBy('id')->get();
        $products     = Product::select('id', 'name')->orderBy('id')->get();
        // Hanya ambil user dengan role sales untuk sheet "Sales NIP"
        $users      = User::whereHas('role', fn($q) => $q->where('code', 'sales'))
            ->select('nip', 'name')
            ->orderBy('nip')
            ->get();

        // Sample row (contoh pengisian untuk 1 baris COLD & WARM)
        for ($i = 0; $i < 2; $i++) {
            $sampleStages = ['cold', 'warm'];

            $base = [
                $sources[$i % max(1, $sources->count())]->id ?? '',
                $segments[$i % max(1, $segments->count())]->id ?? '',
                $industries[$i % max(1, $industries->count())]->id ?? '',
                $regions[$i % max(1, $regions->count())]->id ?? '',
                'Sample Company ' . ($i + 1),
                'Sample Address ' . ($i + 1),
                'Mr',
                'Sample Lead ' . ($i + 1),
                $jabatans[$i % max(1, $jabatans->count())]->id ?? '',
                '080000000' . $i,
                'sample' . ($i + 1) . '@example.com',
                $needsOptions[$i % count($needsOptions)] ?? '',
                $users[$i % max(1, $users->count())]->nip ?? '',
                Carbon::now()->subDays($i)->toDateString(),
                $sampleStages[$i] ?? '',
            ];

            $rowNum = $i + 2;
            $sheet->fromArray($base, null, 'A' . $rowNum);
        }

        // Other master sheets
        $this->addMasterSheet($spreadsheet, 'Lead Sources', ['id', 'name'], $sources);
        $this->addMasterSheet($spreadsheet, 'Lead Segments', ['id', 'name'], $segments);
        $this->addMasterSheet($spreadsheet, 'Industries', ['id', 'name'], $industries);

        // --- Regions sheet with corrected hierarchy ---
        $regionSheet = $spreadsheet->createSheet();
        $regionSheet->setTitle('Regions');
        // Hanya tampilkan data murni tanpa kolom display gabungan
        $regionCols = ['id', 'regional', 'province', 'branch', 'region'];
        $regionSheet->fromArray($regionCols, null, 'A1');
        $regionSheet->getStyle('A1:' . chr(64 + count($regionCols)) . '1')->applyFromArray($headerStyle);
        $regionSheet->freezePane('A2');
        $rowNum = 2;
        foreach ($regions as $r) {
            $regionSheet->fromArray([
                $r->id,
                optional($r->regional)->name,
                optional($r->province)->name,
                optional($r->branch)->name,
                $r->name,
            ], null, 'A' . $rowNum);
            $rowNum++;
        }
        foreach (range('A', chr(64 + count($regionCols))) as $col) {
            $regionSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->addMasterSheet($spreadsheet, 'Sales NIP', ['nip', 'name'], $users);
        $this->addMasterSheet($spreadsheet, 'Jabatans', ['id', 'name'], $jabatans);
        $this->addMasterSheet($spreadsheet, 'Products', ['id', 'name'], $products);

        // Data validation dropdown untuk kolom lead_title (kolom G) → Mr/Mrs
        $titleValidation = new DataValidation();
        $titleValidation->setType(DataValidation::TYPE_LIST);
        $titleValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $titleValidation->setAllowBlank(true);
        $titleValidation->setShowInputMessage(true);
        $titleValidation->setShowErrorMessage(true);
        $titleValidation->setShowDropDown(true);
        $titleValidation->setFormula1('"Mr,Mrs"');

        for ($row = 2; $row <= 1000; $row++) {
            $cell = 'G' . $row;
            $sheet->getCell($cell)->setDataValidation(clone $titleValidation);
        }

        // Data validation dropdown untuk kolom status_stage (kolom O)
        $statusValidation = new DataValidation();
        $statusValidation->setType(DataValidation::TYPE_LIST);
        $statusValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $statusValidation->setAllowBlank(true);
        $statusValidation->setShowInputMessage(true);
        $statusValidation->setShowErrorMessage(true);
        $statusValidation->setShowDropDown(true);
        // Tampilkan opsi status termasuk 'available'
        $statusValidation->setFormula1('"cold,warm,hot,deal,available"');

        // Terapkan ke baris 2 s.d. 1000 di kolom O
        for ($row = 2; $row <= 1000; $row++) {
            $cell = 'O' . $row;
            $sheet->getCell($cell)->setDataValidation(clone $statusValidation);
        }

        // Data validation dropdown untuk kolom ID master (source/segment/industry)
        $sourceLastRow       = 1 + max(1, $sources->count());
        $segmentLastRow      = 1 + max(1, $segments->count());
        $industryLastRow     = 1 + max(1, $industries->count());
        $regionLastRow       = 1 + max(1, $regions->count());
        $salesLastRow        = 1 + max(1, $users->count());
        $jabatansLastRow     = 1 + max(1, $jabatans->count());
        $meetingTypesLastRow = 1 + max(1, $meetingTypes->count());
        $expenseTypesLastRow = 1 + max(1, $expenseTypes->count());
        $productsLastRow     = 1 + max(1, $products->count());

        // Buat sheet tersembunyi khusus helper display "id - name" untuk dropdown di tab Import
        $helperSheet = $spreadsheet->createSheet();
        $helperSheet->setTitle('_Lookups');
        $helperSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        $displayStartRow = 2;

        // Lead Sources → kolom A di _Lookups
        $helperSheet->setCellValue('A1', 'source_display');
        $rowIdx = $displayStartRow;
        foreach ($sources as $src) {
            $helperSheet->setCellValue('A' . $rowIdx, ($src->id ?? '') . ' - ' . ($src->name ?? ''));
            $rowIdx++;
        }

        // Lead Segments → kolom B di _Lookups
        $helperSheet->setCellValue('B1', 'segment_display');
        $rowIdx = $displayStartRow;
        foreach ($segments as $seg) {
            $helperSheet->setCellValue('B' . $rowIdx, ($seg->id ?? '') . ' - ' . ($seg->name ?? ''));
            $rowIdx++;
        }

        // Industries → kolom C di _Lookups
        $helperSheet->setCellValue('C1', 'industry_display');
        $rowIdx = $displayStartRow;
        foreach ($industries as $ind) {
            $helperSheet->setCellValue('C' . $rowIdx, ($ind->id ?? '') . ' - ' . ($ind->name ?? ''));
            $rowIdx++;
        }

        // Jabatans → kolom D di _Lookups
        $helperSheet->setCellValue('D1', 'jabatan_display');
        $rowIdx = $displayStartRow;
        foreach ($jabatans as $jab) {
            $helperSheet->setCellValue('D' . $rowIdx, ($jab->id ?? '') . ' - ' . ($jab->name ?? ''));
            $rowIdx++;
        }

        // Regions → kolom E di _Lookups (id - name), sebagai pengganti kolom display di tab Regions
        $helperSheet->setCellValue('E1', 'region_display');
        $rowIdx = $displayStartRow;
        foreach ($regions as $reg) {
            $helperSheet->setCellValue('E' . $rowIdx, ($reg->id ?? '') . ' - ' . ($reg->name ?? ''));
            $rowIdx++;
        }

        // Meeting Types 
        $helperSheet->setCellValue('F1', 'meeting_type_display');
        $rowIdx = $displayStartRow;
        foreach ($meetingTypes as $mt) {
            $helperSheet->setCellValue('F' . $rowIdx, ($mt->id ?? '') . ' - ' . ($mt->name ?? ''));
            $rowIdx++;
        }

        // Expense Types
        $helperSheet->setCellValue('G1', 'expense_type_display');
        $rowIdx = $displayStartRow;
        foreach ($expenseTypes as $et) {
            $helperSheet->setCellValue('G' . $rowIdx, ($et->id ?? '') . ' - ' . ($et->name ?? ''));
            $rowIdx++;
        }

        // Products (disimpan sebagai referensi master, walau kolom product di template sedang dinonaktifkan)
        $helperSheet->setCellValue('H1', 'product_display');
        $rowIdx = $displayStartRow;
        foreach ($products as $prod) {
            $helperSheet->setCellValue('H' . $rowIdx, ($prod->id ?? '') . ' - ' . ($prod->name ?? ''));
            $rowIdx++;
        }

        /*
         * Term label options (booking_fee, 1,2,3,...) dan referensi ke kolom Term
         * untuk saat ini dinonaktifkan bersama kolom quotation (product..description).
         *
         * $helperSheet->setCellValue('I1', 'term_label_options');
         * ...
         * $termLabelLastRow = $rowIdx - 1;
         */

        // source_id (kolom A) → gunakan helper list di sheet _Lookups kolom A ("id - name")
        $sourceDv = new DataValidation();
        $sourceDv->setType(DataValidation::TYPE_LIST);
        $sourceDv->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $sourceDv->setAllowBlank(true);
        $sourceDv->setShowInputMessage(true);
        $sourceDv->setShowErrorMessage(true);
        $sourceDv->setShowDropDown(true);
        // gunakan single-quoted PHP string agar simbol $ tidak dianggap variabel,
        // dan tetap pakai tanda petik tunggal untuk nama sheet di formula Excel
        $sourceDv->setFormula1('\'_Lookups\'!$A$2:$A$' . $sourceLastRow);
        for ($row = 2; $row <= 1000; $row++) {
            $cell = 'A' . $row;
            $sheet->getCell($cell)->setDataValidation(clone $sourceDv);
        }

        // segment_id (kolom B)
        $segmentDv = new DataValidation();
        $segmentDv->setType(DataValidation::TYPE_LIST);
        $segmentDv->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $segmentDv->setAllowBlank(true);
        $segmentDv->setShowInputMessage(true);
        $segmentDv->setShowErrorMessage(true);
        $segmentDv->setShowDropDown(true);
        // gunakan helper list di sheet _Lookups kolom B ("id - name")
        $segmentDv->setFormula1('\'_Lookups\'!$B$2:$B$' . $segmentLastRow);
        for ($row = 2; $row <= 1000; $row++) {
            $cell = 'B' . $row;
            $sheet->getCell($cell)->setDataValidation(clone $segmentDv);
        }

        // industry_id (kolom C)
        $industryDv = new DataValidation();
        $industryDv->setType(DataValidation::TYPE_LIST);
        $industryDv->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $industryDv->setAllowBlank(true);
        $industryDv->setShowInputMessage(true);
        $industryDv->setShowErrorMessage(true);
        $industryDv->setShowDropDown(true);
        // gunakan helper list di sheet _Lookups kolom C ("id - name")
        $industryDv->setFormula1('\'_Lookups\'!$C$2:$C$' . $industryLastRow);
        for ($row = 2; $row <= 1000; $row++) {
            $cell = 'C' . $row;
            $sheet->getCell($cell)->setDataValidation(clone $industryDv);
        }

        // lead_position (kolom I) → gunakan kolom C (display) di sheet Jabatans
        $jabatanDv = new DataValidation();
        $jabatanDv->setType(DataValidation::TYPE_LIST);
        $jabatanDv->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $jabatanDv->setAllowBlank(true);
        $jabatanDv->setShowInputMessage(true);
        $jabatanDv->setShowErrorMessage(true);
        $jabatanDv->setShowDropDown(true);
        // gunakan helper list di sheet _Lookups kolom D ("id - name")
        $jabatanDv->setFormula1('\'_Lookups\'!$D$2:$D$' . $jabatansLastRow);
        for ($row = 2; $row <= 1000; $row++) {
            $cell = 'I' . $row;
            $sheet->getCell($cell)->setDataValidation(clone $jabatanDv);
        }



        // lead_needs (kolom L) → gunakan opsi yang sama seperti di form Leads
        $needsDv = new DataValidation();
        $needsDv->setType(DataValidation::TYPE_LIST);
        $needsDv->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $needsDv->setAllowBlank(true);
        $needsDv->setShowInputMessage(true);
        $needsDv->setShowErrorMessage(true);
        $needsDv->setShowDropDown(true);
        $needsDv->setFormula1('"' . implode(',', $needsOptions) . '"');
        for ($row = 2; $row <= 1000; $row++) {
            $cell = 'L' . $row;
            $sheet->getCell($cell)->setDataValidation(clone $needsDv);
        }

        // Format kolom Start Time & End Time Meeting (R,S) sebagai DateTime
        $sheet->getStyle('R2:S1000')
            ->getNumberFormat()
            ->setFormatCode('yyyy-mm-dd hh:mm');

        // Tambah data validation bertipe DATE untuk membantu input tanggal+jam
        $dateDv = new DataValidation();
        $dateDv->setType(DataValidation::TYPE_DATE);
        $dateDv->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $dateDv->setAllowBlank(true);
        $dateDv->setShowInputMessage(true);
        $dateDv->setShowErrorMessage(true);
        $dateDv->setOperator(DataValidation::OPERATOR_BETWEEN);
        // Boleh antara tahun 2000 s.d. 2100
        $dateDv->setFormula1('DATE(2000,1,1)');
        $dateDv->setFormula2('DATE(2100,12,31)');

        for ($row = 2; $row <= 1000; $row++) {
            foreach (['R', 'S'] as $col) {
                $cell = $col . $row;
                $sheet->getCell($cell)->setDataValidation(clone $dateDv);
            }
        }

     
        // Otomatis isi "-" di kolom meeting sesuai tipe meeting
        for ($row = 2; $row <= 1000; $row++) {
            $typeExpr = 'TRIM(MID($P' . $row . ',FIND("-",$P' . $row . ')+1,255))';

            // Meeting URL (Q): "-" untuk Offline Office/Canvass, Video Call, EXPO
            $formulaQ = '=IFERROR(IF(OR(' .
                $typeExpr . '="Offline - Office",' .
                $typeExpr . '="Offline - Canvass",' .
                $typeExpr . '="Video Call",' .
                $typeExpr . '="EXPO"),"-",""),"")';
            $sheet->setCellValue('Q' . $row, $formulaQ);

            
            $formulaTX = '=IFERROR(IF(OR(' .
                $typeExpr . '="Zoom / Google Meet",' .
                $typeExpr . '="Video Call"),"-",""),"")';
            $sheet->setCellValue('T' . $row, $formulaTX);
            $sheet->setCellValue('U' . $row, $formulaTX);
            $sheet->setCellValue('V' . $row, $formulaTX);
            $sheet->setCellValue('W' . $row, $formulaTX);
            $sheet->setCellValue('X' . $row, $formulaTX);
        }

        // Kolom T–X tetap di-center untuk tanda "-"
        $meetingExtraRangeStyle = $sheet->getStyle('T2:X1000');
        $meetingExtraRangeStyle->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Conditional formatting abu-abu untuk kolom meeting sesuai tipe meeting
        $typeExprRow2 = 'TRIM(MID($P2,FIND("-",$P2)+1,255))';

        // T–X abu-abu untuk Zoom & Video Call saja
        $conditionalTX = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $conditionalTX->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION);
        $conditionalTX->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_NONE);
        $conditionalTX->addCondition('OR(' .
            $typeExprRow2 . '="Zoom / Google Meet",' .
            $typeExprRow2 . '="Video Call")');
        $conditionalTXStyle = $conditionalTX->getStyle();
        $conditionalTXStyle->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F2F2F2');
        $existingConditionalsTX = $sheet->getStyle('T2:X1000')->getConditionalStyles();
        $existingConditionalsTX[] = $conditionalTX;
        $sheet->getStyle('T2:X1000')->setConditionalStyles($existingConditionalsTX);

        // Q abu-abu untuk Offline Office/Canvass, Video Call, EXPO
        $conditionalQ = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $conditionalQ->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION);
        $conditionalQ->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_NONE);
        $conditionalQ->addCondition('OR(' .
            $typeExprRow2 . '="Offline - Office",' .
            $typeExprRow2 . '="Offline - Canvass",' .
            $typeExprRow2 . '="Video Call",' .
            $typeExprRow2 . '="EXPO")');
        $conditionalQStyle = $conditionalQ->getStyle();
        $conditionalQStyle->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F2F2F2');
        $existingConditionalsQ = $sheet->getStyle('Q2:Q1000')->getConditionalStyles();
        $existingConditionalsQ[] = $conditionalQ;
        $sheet->getStyle('Q2:Q1000')->setConditionalStyles($existingConditionalsQ);

        // R,S: tidak diberi conditional abu-abu lagi (tetap bisa diisi semua tipe)

        $meetingTypeDv = new DataValidation();
        $meetingTypeDv->setType(DataValidation::TYPE_LIST);
        $meetingTypeDv->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $meetingTypeDv->setAllowBlank(true);
        $meetingTypeDv->setShowInputMessage(true);
        $meetingTypeDv->setShowErrorMessage(true);
        $meetingTypeDv->setShowDropDown(true);
        $meetingTypeDv->setFormula1('\'_Lookups\'!$F$2:$F$' . $meetingTypesLastRow);
        for ($row = 2; $row <= 1000; $row++) {
            $cell = 'P' . $row;
            $sheet->getCell($cell)->setDataValidation(clone $meetingTypeDv);
        }

        $expenseTypeDv = new DataValidation();
        $expenseTypeDv->setType(DataValidation::TYPE_LIST);
        $expenseTypeDv->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $expenseTypeDv->setAllowBlank(true);
        $expenseTypeDv->setShowInputMessage(true);
        $expenseTypeDv->setShowErrorMessage(true);
        $expenseTypeDv->setShowDropDown(true);
        $expenseTypeDv->setFormula1('\'_Lookups\'!$G$2:$G$' . $expenseTypesLastRow);
        for ($row = 2; $row <= 1000; $row++) {
            $cell = 'V' . $row;
            $sheet->getCell($cell)->setDataValidation(clone $expenseTypeDv);
        }

        /*
         * Seluruh data validation & formula untuk kolom quotation (product..description)
         * dinonaktifkan sementara. Jika nanti import HOT diaktifkan kembali,
         * blok berikut bisa digunakan lagi sebagai referensi.
         */

        // Download response or return base64 for API
        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            $tmp = tempnam(sys_get_temp_dir(), 'lead_import_') . '.xlsx';
            $writer->save($tmp);
            $content = file_get_contents($tmp);
            $base64 = base64_encode($content);
            @unlink($tmp);
            return response()->json([
                'filename' => 'lead_import_template.xlsx',
                'content_base64' => $base64,
            ]);
        }

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'lead_import_template.xlsx');
    }

    private function addMasterSheet(Spreadsheet $spreadsheet, string $title, array $headers, $collection)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($title);
        $sheet->fromArray($headers, null, 'A1');
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'],
            ],
        ];
        $sheet->getStyle('A1:' . chr(64 + count($headers)) . '1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($collection as $item) {
            // Hanya tulis kolom yang diminta di $headers (mis. id, name) tanpa kolom display tambahan
            $sheet->fromArray(array_values($item->only($headers)), null, 'A' . $row);
            $row++;
        }
        foreach (range('A', chr(64 + count($headers))) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    public function preview(Request $request)
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,csv',
        ]);

        $sources       = LeadSource::select('id', 'name')->orderBy('name')->get();
        $segments      = LeadSegment::select('id', 'name')->orderBy('name')->get();
        $regions       = Region::select('id', 'name')->orderBy('name')->get();
        // Dropdown NIP Sales di preview juga khusus role sales
        $users         = User::whereHas('role', fn($q) => $q->where('code', 'sales'))
            ->select('nip', 'name')
            ->orderBy('nip')
            ->get();
        $meetingTypes  = MeetingType::select('id', 'name')->orderBy('name')->get();
        $expenseTypes  = ExpenseType::select('id', 'name')->orderBy('name')->get();

        $file        = $request->file('import_file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet       = $spreadsheet->getSheet(0);

        $rows      = [];
        $validRows = [];
        $hasError  = false;

        foreach ($sheet->toArray(null, true, true, true) as $index => $row) {
            if ($index === 1) continue;

            // Ambil hanya ID dari nilai dropdown "ID - Name" (atau nilai mentah ID)
            $sourceId   = $this->extractIdFromCell($row['A'] ?? null);
            $segmentId  = $this->extractIdFromCell($row['B'] ?? null);
            $industryId = $this->extractIdFromCell($row['C'] ?? null);
            $regionId   = $this->extractIdFromCell($row['D'] ?? null);
            $nipSales   = $this->extractIdFromCell($row['M'] ?? null);

            /*
             * Quotation-related fields for HOT leads (product..description)
             * sementara tidak dipakai karena import difokuskan dulu untuk hingga WARM.
             *
             * $productId  = ...
             * $qty        = ...
             * dst.
             */

            try {
                $published = !empty($row['N'])
                    ? Carbon::parse($row['N'])->toDateTimeString()
                    : now()->toDateTimeString();
            } catch (\Exception $e) {
                $published = now()->toDateTimeString();
            }

            // Parse meeting & expense columns (P–X) for potential warm import backdate
            $meetingTypeId = $this->extractIdFromCell($row['P'] ?? null);

            try {
                $meetingStart = ! empty($row['R'])
                    ? Carbon::parse($row['R'])->toDateTimeString()
                    : null;
            } catch (\Exception $e) {
                $meetingStart = null;
            }

            try {
                $meetingEnd = ! empty($row['S'])
                    ? Carbon::parse($row['S'])->toDateTimeString()
                    : null;
            } catch (\Exception $e) {
                $meetingEnd = null;
            }

            $expenseTypeId  = $this->extractIdFromCell($row['V'] ?? null);
            $expenseNotes   = $row['W'] ?? null;
            $expenseAmount  = $row['X'] ?? null;

            $data = [
                'preview_index'      => (string) $index,
                'source_id'         => $sourceId,
                'segment_id'        => $segmentId,
                'industry_id'       => $industryId,
                'region_id'         => $regionId,
                'company_name'      => $row['E'] ?? null,
                'company_address'   => $row['F'] ?? null,
                'lead_title'        => $row['G'] ?? null,
                'lead_name'         => $row['H'] ?? null,
                'lead_position'     => $this->extractIdFromCell($row['I'] ?? null),
                'lead_phone'        => $row['J'] ?? null,
                'lead_email'        => $row['K'] ?? null,
                'lead_needs'        => $row['L'] ?? null,
                'nip_sales'         => $nipSales,
                'published_at'      => $published,

                // hanya status_stage yang dipakai untuk identifikasi stage
                'status_stage'      => isset($row['O']) ? trim((string)$row['O']) : '',

                // meeting & expense (untuk import WARM + backdate)
                'meeting_type_id'   => $meetingTypeId,
                'meeting_url'       => $row['Q'] ?? null,
                'meeting_start_at'  => $meetingStart,
                'meeting_end_at'    => $meetingEnd,
                'meeting_city'      => $row['T'] ?? null,
                'meeting_address'   => $row['U'] ?? null,
                'expense_type_id'   => $expenseTypeId,
                'expense_notes'     => $expenseNotes,
                'expense_amount'    => $expenseAmount,

                // quotation fields untuk HOT disimpan di komentar (sementara tidak dipakai)

                'error'             => '',
            ];

            if (empty($data['lead_name'])) {
                continue;
            }

            if (!LeadSource::where('id', $data['source_id'])->exists()) {
                $data['error'] = 'Invalid source_id';
            } elseif (!LeadSegment::where('id', $data['segment_id'])->exists()) {
                $data['error'] = 'Invalid segment_id';
            } elseif (
                !is_null($data['region_id'])
                && !Region::where('id', $data['region_id'])->exists()
            ) {
                $data['error'] = 'Invalid region_id';
            } elseif (
                $data['nip_sales']
                && !User::where('nip', $data['nip_sales'])->exists()
            ) {
                $data['error'] = 'NIP not found';
            } elseif (
                // Jika ada kolom meeting/expense yang diisi, tapi meeting_type_id kosong
                (
                    ! empty($data['meeting_url'])
                    || ! empty($data['meeting_start_at'])
                    || ! empty($data['meeting_end_at'])
                    || ! empty($data['meeting_city'])
                    || ! empty($data['meeting_address'])
                    || ! empty($data['expense_type_id'])
                    || ! empty($data['expense_notes'])
                    || (! empty($data['expense_amount']) && $data['expense_amount'] != 0)
                )
                && empty($data['meeting_type_id'])
            ) {
                $data['error'] = 'meeting_type_id is required when meeting/expense columns are filled';
            } elseif (
                ! empty($data['meeting_type_id'])
                && ! MeetingType::where('id', $data['meeting_type_id'])->exists()
            ) {
                $data['error'] = 'Invalid meeting_type_id';
            } elseif (
                ! empty($data['expense_type_id'])
                && ! ExpenseType::where('id', $data['expense_type_id'])->exists()
            ) {
                $data['error'] = 'Invalid expense_type_id';
            }

            // Validasi tambahan khusus untuk status HOT sementara dinonaktifkan,
            // karena import hanya dipakai sampai WARM.

            if ($data['error'] === '') {
                $validRows[] = $data;
            } else {
                $hasError = true;
            }

            $rows[] = $data;
        }

        // Simpan semua baris valid (tidak digrup) ke session untuk proses store()
        session(['import_lead_rows' => $validRows]);
        
        // Tambahkan group_key ke setiap baris untuk kebutuhan grouping di view & store
        $previewRows = [];
        foreach ($rows as $row) {
            $row['group_key'] = $this->buildLeadMeetingGroupKey($row);
            $previewRows[] = $row;
        }

        $previewTableConfig = $this->buildPreviewTableConfig($previewRows, $meetingTypes, $regions, $expenseTypes);

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'rows' => $previewRows,
                'hasError' => $hasError,
                'valid_count' => count($validRows),
                'preview_table_config' => $previewTableConfig,
                'sources' => $sources,
                'segments' => $segments,
                'regions' => $regions,
                'users' => $users,
                'meeting_types' => $meetingTypes,
                'expense_types' => $expenseTypes,
            ]);
        }

        $this->pageTitle = 'Import Leads';
        return $this->render('pages.leads.import', [
            'rows'     => $previewRows,
            'hasError' => $hasError,
            'previewTableConfig' => $previewTableConfig,
            'sources'  => $sources,
            'segments' => $segments,
            'regions'  => $regions,
            'users'    => $users,
            'meetingTypes' => $meetingTypes,
            'expenseTypes' => $expenseTypes,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();
        $imported = 0;

        // Ambil data mentah hasil baca file dari session (sudah diverifikasi di preview)
        $sessionRows  = session('import_lead_rows', []);
        $postedGroups = $request->input('rows', []);

        if (! empty($sessionRows)) {
            $rowsForImport = $sessionRows;

            $postedIndexes = array_map('strval', array_keys($postedGroups));
            if (! empty($postedIndexes)) {
                $rowsForImport = array_values(array_filter($rowsForImport, function ($row) use ($postedIndexes) {
                    return in_array((string) ($row['preview_index'] ?? ''), $postedIndexes, true);
                }));
            }

            // Jika user mengubah beberapa field di preview (source/segment/region/dll),
            // apply perubahan tersebut ke semua baris dalam grup yang sama.
            $seenGroups = [];
            foreach ($postedGroups as $posted) {
                $groupKey = $posted['group_key'] ?? null;
                if (! $groupKey || isset($seenGroups[$groupKey])) {
                    continue;
                }
                $seenGroups[$groupKey] = true;

                $overrides = [
                    'source_id'    => $posted['source_id']    ?? null,
                    'segment_id'   => $posted['segment_id']   ?? null,
                    'region_id'    => $posted['region_id']    ?? null,
                    'lead_name'    => $posted['lead_name']    ?? null,
                    'lead_email'   => $posted['lead_email']   ?? null,
                    'lead_phone'   => $posted['lead_phone']   ?? null,
                    'lead_needs'   => $posted['lead_needs']   ?? null,
                    'nip_sales'    => $posted['nip_sales']    ?? null,
                    'published_at' => $posted['published_at'] ?? null,
                    'status_stage' => $posted['status_stage'] ?? null,
                ];

                foreach ($rowsForImport as &$row) {
                    if ($this->buildLeadMeetingGroupKey($row) === $groupKey) {
                        foreach ($overrides as $field => $value) {
                            if ($value !== null) {
                                $row[$field] = $value;
                            }
                        }
                    }
                }
                unset($row); // break reference
            }
        } else {
            // fallback lama jika session kosong (tidak ideal, tapi jaga kompatibilitas)
            $rowsForImport = $postedGroups;
        }

        // Grupkan baris berdasarkan kombinasi data lead + meeting (duplikat baris = multi expense)
        $grouped = [];
        foreach ($rowsForImport as $row) {
            $key = $this->buildLeadMeetingGroupKey($row);
            $grouped[$key][] = $row;
        }

        foreach ($grouped as $groupRows) {
            $base = $groupRows[0];

            // skip invalid groups (cek hanya dari baris pertama karena nilai base sama)
            if (
                ! LeadSource::where('id', $base['source_id'])->exists()
                || ! LeadSegment::where('id', $base['segment_id'])->exists()
                || (! is_null($base['region_id']) && ! Region::where('id', $base['region_id'])->exists())
                || ($base['nip_sales'] && ! User::where('nip', $base['nip_sales'])->exists())
            ) {
                continue;
            }

            DB::transaction(function () use ($groupRows, $base, &$imported) {
                /* ----------------------------------------------------------
                * status priority:
                *   - if status_stage is set in file → map to LeadStatus
                *       cold → COLD, warm → WARM, hot → HOT, deal → DEAL
                *       available → PUBLISHED (available leads)
                *   - otherwise fallback:
                *       nip_sales present  → COLD
                *       nip_sales null     → PUBLISHED
                * -------------------------------------------------------- */

                $statusStage = isset($base['status_stage']) ? strtolower(trim((string) $base['status_stage'])) : '';

                switch ($statusStage) {
                    case 'cold':
                        $status = LeadStatus::COLD;
                        break;
                    case 'warm':
                        $status = LeadStatus::WARM;
                        break;
                    case 'hot':
                        $status = LeadStatus::HOT;
                        break;
                    case 'deal':
                        $status = LeadStatus::DEAL;
                        break;
                    case 'available':
                        // 'available' berarti lead masuk ke pool available (PUBLISHED)
                        $status = LeadStatus::PUBLISHED;
                        // pastikan nip_sales dikosongkan agar tidak otomatis diklaim
                        $base['nip_sales'] = null;
                        break;
                    default:
                        $status = $row['nip_sales']
                            ? LeadStatus::COLD
                            : LeadStatus::PUBLISHED;
                        break;
                }

                $lead = Lead::create([
                    'source_id'       => $base['source_id'],
                    'segment_id'      => $base['segment_id'],
                    'industry_id'     => $base['industry_id'] ?? null,
                    'region_id'       => $base['region_id'],   // may be null = “all regions”
                    'status_id'       => $status,
                    'company'         => $base['company_name'] ?? null,
                    'company_address' => $base['company_address'] ?? null,
                    'jabatan_id'      => $base['lead_position'] ?? null,
                    'name'            => $base['lead_name'],
                    'email'           => $base['lead_email'],
                    'phone'           => $base['lead_phone'],
                    'needs'           => $base['lead_needs'],
                    'interest_level'  => $status === LeadStatus::WARM ? 4 : null,
                    'published_at'    => $base['published_at'] ?? now(),
                ]);

                // Only create a claim when nip_sales is *not* null
                $sales = null;
                if ($base['nip_sales']) {
                    $sales = User::where('nip', $base['nip_sales'])->first();
                    if ($sales) {
                        LeadClaim::create([
                            'lead_id'    => $lead->id,
                            'sales_id'   => $sales->id,
                            'claimed_at' => now(),
                        ]);
                    }
                }

                // Jika status WARM dan ada data meeting/expense, anggap sudah di-approve (backdate)
                if ($status === LeadStatus::WARM) {
                    // Catat status log agar sejalan dengan perpindahan status lain
                    LeadStatusLog::create([
                        'lead_id'   => $lead->id,
                        'status_id' => LeadStatus::WARM,
                    ]);

                    $hasMeetingData = ! empty($base['meeting_type_id'])
                        || ! empty($base['meeting_start_at'])
                        || ! empty($base['meeting_end_at'])
                        || ! empty($base['meeting_city'])
                        || ! empty($base['meeting_address']);

                    // Hitung total expense & siapkan detail per baris (multi expense)
                    $totalAmount = 0;
                    $expenseDetails = [];
                    foreach ($groupRows as $row) {
                        $rawAmountRow = $row['expense_amount'] ?? null;
                        if ($rawAmountRow === null || $rawAmountRow === '') {
                            continue;
                        }

                        $amountRow = is_numeric($rawAmountRow)
                            ? (float) $rawAmountRow
                            : (float) str_replace([','], ['.'], (string) $rawAmountRow);

                        if ($amountRow <= 0) {
                            continue;
                        }

                        $totalAmount += $amountRow;
                        $expenseDetails[] = [
                            'expense_type_id' => $row['expense_type_id'] ?? null,
                            'notes'           => $row['expense_notes'] ?? null,
                            'amount'          => $amountRow,
                        ];
                    }

                    if ($hasMeetingData && $totalAmount > 0 && ! empty($base['meeting_type_id'])) {
                        $meetingType = MeetingType::find($base['meeting_type_id']);

                        $onlineNames = ['Zoom / Google Meet', 'Video Call'];
                        $isOnline = $meetingType && in_array($meetingType->name, $onlineNames);

                        $meeting = LeadMeeting::create([
                            'lead_id'            => $lead->id,
                            'meeting_type_id'    => $base['meeting_type_id'],
                            'is_online'          => $isOnline,
                            'online_url'         => $isOnline ? ($base['meeting_url'] ?? null) : null,
                            'scheduled_start_at' => $base['meeting_start_at'] ?? null,
                            'scheduled_end_at'   => $base['meeting_end_at'] ?? null,
                            'city'               => $isOnline ? null : ($base['meeting_city'] ?? null),
                            'address'            => $isOnline ? null : ($base['meeting_address'] ?? null),
                        ]);

                        // Anggap expense sudah di-approve oleh BM & Finance
                        $expense = MeetingExpense::create([
                            'meeting_id'   => $meeting->id,
                            'sales_id'     => $sales?->id,
                            'amount'       => $totalAmount,
                            'status'       => 'approved', // langsung dianggap sudah approve
                            'requested_at' => $base['published_at'] ?? now(),
                        ]);

                        foreach ($expenseDetails as $detail) {
                            MeetingExpenseDetail::create([
                                'meeting_expense_id' => $expense->id,
                                'expense_type_id'    => $detail['expense_type_id'],
                                'amount'             => $detail['amount'],
                                'notes'              => $detail['notes'],
                            ]);
                        }

                        // Tidak membuat FinanceRequest, karena khusus import backdate
                        // dianggap sudah disetujui oleh BM & Finance.
                    }
                }

                /*
                 * Pembuatan quotation otomatis untuk status HOT dinonaktifkan sementara,
                 * karena import difokuskan hanya untuk hingga WARM.
                 * Blok ini bisa diaktifkan lagi jika import HOT sudah dipakai.
                 */
                $imported++;
            });
        }

        session()->forget('import_lead_rows');

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'imported' => $imported,
            ]);
        }

        return redirect()
            ->route('leads.import')
            ->with('success', 'Leads imported successfully');
    }

    /**
     * Bangun key unik untuk mengelompokkan baris berdasarkan kombinasi data lead + meeting.
     * Digunakan di preview() dan store() supaya perilaku grouping konsisten.
     */
    private function buildLeadMeetingGroupKey(array $row): string
    {
        $keyParts = [
            $row['source_id']        ?? '',
            $row['segment_id']       ?? '',
            $row['industry_id']      ?? '',
            $row['region_id']        ?? '',
            $row['company_name']     ?? '',
            $row['company_address']  ?? '',
            $row['lead_title']       ?? '',
            $row['lead_name']        ?? '',
            $row['lead_position']    ?? '',
            $row['lead_phone']       ?? '',
            $row['lead_email']       ?? '',
            $row['lead_needs']       ?? '',
            $row['nip_sales']        ?? '',
            $row['published_at']     ?? '',
            $row['status_stage']     ?? '',
            $row['meeting_type_id']  ?? '',
            $row['meeting_start_at'] ?? '',
            $row['meeting_end_at']   ?? '',
            $row['meeting_city']     ?? '',
            $row['meeting_address']  ?? '',
        ];

        return implode('|', $keyParts);
    }

    private function buildPreviewTableConfig(array $rows, $meetingTypes, $regions, $expenseTypes): array
    {
        $tabs = [
            'cold' => [
                'label' => 'Cold',
                'headers' => [
                    '#',
                    'source_id*',
                    'segment_id*',
                    'region_id*',
                    'lead_name',
                    'lead_email',
                    'lead_phone',
                    'lead_needs',
                    'nip_sales',
                    'published_at',
                    'status_stage',
                    'Status',
                    '',
                ],
                'rows' => [],
            ],
            'warm' => [
                'label' => 'Warm',
                'headers' => [
                    '#',
                    'Meeting Type',
                    'Meeting URL',
                    'Start Time Meeting',
                    'End Time Meeting',
                    'Meeting City',
                    'Meeting Address',
                    'Expense Type',
                    'Expense Notes',
                    'Expense Amount',
                    'Status',
                    '',
                ],
                'rows' => [],
            ],
        ];

        $stageCounts = [
            'cold' => 0,
            'warm' => 0,
        ];

        $lastGroup = null;
        $groupIndex = 0;

        foreach ($rows as $row) {
            $stage = strtolower(trim((string) ($row['status_stage'] ?? '')));
            if (array_key_exists($stage, $stageCounts)) {
                $stageCounts[$stage]++;
            }

            $currentGroup = $row['group_key'] ?? (string) ($row['preview_index'] ?? $groupIndex);
            $isFirstInGroup = $currentGroup !== $lastGroup;
            if ($isFirstInGroup) {
                $groupIndex++;

                $tabs['cold']['rows'][] = [
                    'preview_index' => (string) ($row['preview_index'] ?? ''),
                    'group_key' => $currentGroup,
                    'group_index' => $groupIndex,
                    'row_class' => ! empty($row['error']) ? 'table-danger' : '',
                    'error' => $row['error'] ?? '',
                    'source_id' => $row['source_id'] ?? null,
                    'segment_id' => $row['segment_id'] ?? null,
                    'region_id' => $row['region_id'] ?? null,
                    'lead_name' => $row['lead_name'] ?? null,
                    'lead_email' => $row['lead_email'] ?? null,
                    'lead_phone' => $row['lead_phone'] ?? null,
                    'lead_needs' => $row['lead_needs'] ?? null,
                    'nip_sales' => $row['nip_sales'] ?? null,
                    'published_at' => $row['published_at'] ?? null,
                    'status_stage' => $row['status_stage'] ?? '',
                ];
            }

            $meetingTypeId = $row['meeting_type_id'] ?? null;
            $meetingCity = $row['meeting_city'] ?? null;
            $expenseTypeId = $row['expense_type_id'] ?? null;

            $tabs['warm']['rows'][] = [
                'preview_index' => (string) ($row['preview_index'] ?? ''),
                'group_key' => $currentGroup,
                'group_index' => $groupIndex,
                'is_first_in_group' => $isFirstInGroup,
                'row_class' => ! empty($row['error']) ? 'table-danger' : '',
                'error' => $row['error'] ?? '',
                'meeting_type_label' => $this->resolveCollectionLabel($meetingTypes, $meetingTypeId),
                'meeting_url' => $row['meeting_url'] ?? '',
                'meeting_start_at' => $row['meeting_start_at'] ?? '',
                'meeting_end_at' => $row['meeting_end_at'] ?? '',
                'meeting_city_label' => $this->resolveCollectionLabel($regions, $meetingCity),
                'meeting_address' => $row['meeting_address'] ?? '',
                'expense_type_label' => $this->resolveCollectionLabel($expenseTypes, $expenseTypeId),
                'expense_notes' => $row['expense_notes'] ?? '',
                'expense_amount' => $row['expense_amount'] ?? '',
            ];

            $lastGroup = $currentGroup;
        }

        $defaultTab = 'cold';
        foreach ($stageCounts as $stage => $count) {
            if ($count > 0) {
                $defaultTab = $stage;
                break;
            }
        }

        foreach ($tabs as $stage => &$config) {
            $config['count'] = count($config['rows'] ?? []);
            $config['has_rows'] = ($config['count'] ?? 0) > 0;
        }
        unset($config);

        return [
            'default_tab' => $defaultTab,
            'tabs' => $tabs,
        ];
    }

    private function resolveCollectionLabel($items, $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $resolved = $items->firstWhere('id', is_numeric($value) ? (int) $value : $value);

        return (string) ($resolved->name ?? $value);
    }

    private function extractIdFromCell($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '' || $text === '-') {
            return null;
        }

        // Pisahkan di karakter '-' pertama jika ada
        $parts = explode('-', $text, 2);
        return trim($parts[0]);
    }
}
