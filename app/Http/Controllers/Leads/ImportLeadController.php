<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadSource;
use App\Models\Leads\LeadSegment;
use App\Models\Leads\LeadStatus;
use App\Models\Leads\LeadClaim;
use App\Models\Masters\Region;
use App\Models\Masters\Industry;
use App\Models\Masters\Jabatan;
use App\Models\Masters\MeetingType;
use App\Models\Masters\ExpenseType;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportLeadController extends Controller
{
    private function authorizeSuperAdmin()
    {
        if (auth()->user()->role?->code !== 'super_admin') {
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
            'status_stage',        // cold | warm | hot | deal
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
        $lastCol = chr(ord('A') + count($importHeaders) - 1); // hitung kolom terakhir dinamis
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);

        // Ganti warna header untuk kolom Meeting* menjadi #FFF1C2
        $meetingHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF1C2'],
            ],
        ];
        $baseHeaderCount = count($baseHeaders);
        $meetingHeaderCount = count($meetingHeaders);
        for ($i = 1; $i <= $meetingHeaderCount; $i++) {
            $colIndex = $baseHeaderCount + $i; // 1-based
            $colLetter = chr(64 + $colIndex); // 1 -> A, 2 -> B, dst.
            $sheet->getStyle($colLetter . '1')->applyFromArray($meetingHeaderStyle);
        }

        $sheet->freezePane('A2');
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Fetch master data
        $sources      = LeadSource::select('id', 'name')->orderBy('id')->get();
        $segments     = LeadSegment::select('id', 'name')->orderBy('id')->get();
        $regions      = Region::with(['regional', 'province', 'branch'])->orderBy('id')->get();
        $industries   = Industry::select('id', 'name')->orderBy('id')->get();
        $jabatans     = Jabatan::select('id', 'name')->orderBy('id')->get();
        $meetingTypes = MeetingType::select('id', 'name')->orderBy('id')->get();
        $expenseTypes = ExpenseType::select('id', 'name')->orderBy('id')->get();
        // Hanya ambil user dengan role sales untuk sheet "Sales NIP"
        $users      = User::whereHas('role', fn($q) => $q->where('code', 'sales'))
            ->select('nip', 'name')
            ->orderBy('nip')
            ->get();

        // Sample row (contoh pengisian untuk 1 baris COLD)
        for ($i = 0; $i < 2; $i++) {
            $sampleStages = ['cold', 'cold'];

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

            $sheet->fromArray($base, null, 'A' . ($i + 2));
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
        $statusValidation->setFormula1('"cold,warm,hot,deal"');

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

            // Start & End Time Meeting (R,S): selalu bisa diisi (tanpa auto "-")
            // -> tidak diberi formula khusus, biarkan kosong default

            // city_id, Address, Expense Type, Notes, Amount (T–X):
            // "-" hanya untuk Zoom & Video Call (Offline/EXPO boleh diisi)
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

        $sources  = LeadSource::select('id', 'name')->orderBy('name')->get();
        $segments = LeadSegment::select('id', 'name')->orderBy('name')->get();
        $regions  = Region::select('id', 'name')->orderBy('name')->get();
        // Dropdown NIP Sales di preview juga khusus role sales
        $users    = User::whereHas('role', fn($q) => $q->where('code', 'sales'))
            ->select('nip', 'name')
            ->orderBy('nip')
            ->get();

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

            try {
                $published = !empty($row['N'])
                    ? Carbon::parse($row['N'])->toDateTimeString()
                    : now()->toDateTimeString();
            } catch (\Exception $e) {
                $published = now()->toDateTimeString();
            }

            $data = [
                'source_id'        => $sourceId,
                'segment_id'       => $segmentId,
                'industry_id'      => $industryId,
                'region_id'        => $regionId,
                'company_name'     => $row['E'] ?? null,
                'company_address'  => $row['F'] ?? null,
                'lead_title'       => $row['G'] ?? null,
                'lead_name'        => $row['H'] ?? null,
                'lead_position'    => $this->extractIdFromCell($row['I'] ?? null),
                'lead_phone'       => $row['J'] ?? null,
                'lead_email'       => $row['K'] ?? null,
                'lead_needs'       => $row['L'] ?? null,
                'nip_sales'        => $nipSales,
                'published_at'     => $published,

                // hanya status_stage yang dipakai untuk identifikasi stage
                'status_stage'     => isset($row['O']) ? trim((string)$row['O']) : '',

                'error'            => '',
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
            }

            if ($data['error'] === '') {
                $validRows[] = $data;
            } else {
                $hasError = true;
            }

            $rows[] = $data;
        }

        session(['import_lead_rows' => $validRows]);

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'rows' => $rows,
                'hasError' => $hasError,
                'valid_count' => count($validRows),
                'sources' => $sources,
                'segments' => $segments,
                'regions' => $regions,
                'users' => $users,
            ]);
        }

        $this->pageTitle = 'Import Leads';
        return $this->render('pages.leads.import', [
            'rows'     => $rows,
            'hasError' => $hasError,
            'sources'  => $sources,
            'segments' => $segments,
            'regions'  => $regions,
            'users'    => $users,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $rows = $request->input('rows', session('import_lead_rows', []));

        $imported = 0;

        foreach ($rows as $row) {
            // skip invalid rows
            if (
                ! LeadSource::where('id', $row['source_id'])->exists()
                || ! LeadSegment::where('id', $row['segment_id'])->exists()
                || (! is_null($row['region_id']) && ! Region::where('id', $row['region_id'])->exists())
                || ($row['nip_sales'] && ! User::where('nip', $row['nip_sales'])->exists())
            ) {
                continue;
            }

            DB::transaction(function () use ($row, &$imported) {
                /* ----------------------------------------------------------
                * status priority:
                *   - if status_stage is set in file → map to LeadStatus
                *       cold → COLD, warm → WARM, hot → HOT, deal → DEAL
                *   - otherwise fallback:
                *       nip_sales present  → COLD
                *       nip_sales null     → PUBLISHED
                * -------------------------------------------------------- */

                $statusStage = isset($row['status_stage']) ? strtolower(trim((string) $row['status_stage'])) : '';

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
                    default:
                        $status = $row['nip_sales']
                            ? LeadStatus::COLD
                            : LeadStatus::PUBLISHED;
                        break;
                }

                $lead = Lead::create([
                    'source_id'       => $row['source_id'],
                    'segment_id'      => $row['segment_id'],
                    'industry_id'     => $row['industry_id'] ?? null,
                    'region_id'       => $row['region_id'],   // may be null = “all regions”
                    'status_id'       => $status,
                    'company'         => $row['company_name'] ?? null,
                    'company_address' => $row['company_address'] ?? null,
                    'jabatan_id'      => $row['lead_position'] ?? null,
                    'name'            => $row['lead_name'],
                    'email'           => $row['lead_email'],
                    'phone'           => $row['lead_phone'],
                    'needs'           => $row['lead_needs'],
                    'published_at'    => $row['published_at'] ?? now(),
                ]);

                // Only create a claim when nip_sales is *not* null
                if ($row['nip_sales']) {
                    $sales = User::where('nip', $row['nip_sales'])->first();
                    if ($sales) {
                        LeadClaim::create([
                            'lead_id'    => $lead->id,
                            'sales_id'   => $sales->id,
                            'claimed_at' => now(),
                        ]);
                    }
                }
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
