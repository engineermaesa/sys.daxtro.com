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
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

        // --- Import sheet ---
        // Kolom dasar (dipakai semua stage)
        $importHeaders = [
            'source_id*',          // master lead source
            'segment_id*',         // master lead segment
            'region_id',           // boleh null = all regions / no region
            'lead_name',
            'lead_email',
            'lead_phone',
            'lead_needs',
            'nip_sales',           // kalau diisi dan status_stage cold/warm/hot/deal → langsung ke sales tsb
            'published_at',
            'status_stage',        // cold | warm | hot | deal (opsional, akan dipakai di logic import lanjutan)

            // Field khusus WARM (quotation)
            'quotation_number',    // nomor quotation
            'quotation_date',      // tanggal quotation
            'quotation_total',     // total nilai quotation

            // Field khusus HOT (progress pembayaran / terms)
            'total_terms',         // total termin pembayaran yang disepakati
            'paid_terms',          // sudah bayar berapa termin
            'paid_amount',         // total nominal yang sudah dibayar
            'remaining_amount',    // sisa nominal yang belum dibayar

            // Field khusus DEAL
            'deal_closed_at',      // tanggal deal / closing
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
        $sheet->getStyle('A1:'.$lastCol.'1')->applyFromArray($headerStyle);
        $sheet->freezePane('A2');
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Fetch master data
        $sources  = LeadSource::select('id', 'name')->orderBy('id')->get();
        $segments = LeadSegment::select('id', 'name')->orderBy('id')->get();
        $regions  = Region::with(['regional', 'province', 'branch'])->orderBy('id')->get();
        $users    = User::select('nip', 'name')->orderBy('nip')->get();

        // Sample rows (contoh pengisian untuk masing-masing stage)
        for ($i = 0; $i < 5; $i++) {
            $sampleStages = ['cold', 'warm', 'hot', 'deal', ''];

            $base = [
                $sources[$i % max(1, $sources->count())]->id ?? '',
                $segments[$i % max(1, $segments->count())]->id ?? '',
                $regions[$i % max(1, $regions->count())]->id ?? '',
                'Sample Lead '.($i + 1),
                'sample'.($i + 1).'@example.com',
                '080000000'.$i,
                'Sample needs '.($i + 1),
                $users[$i % max(1, $users->count())]->nip ?? '',
                Carbon::now()->subDays($i)->toDateString(),
                $sampleStages[$i] ?? '',
            ];

            // default kosong utk kolom tambahan
            $extra = array_fill(0, count($importHeaders) - count($base), '');

            // isi contoh sesuai stage
            if ($sampleStages[$i] === 'warm') {
                // warm → sudah ada quotation
                $extra[0] = 'Q-2026-0001';                     // quotation_number
                $extra[1] = Carbon::now()->subDays(1)->toDateString(); // quotation_date
                $extra[2] = 100000000;                         // quotation_total
            } elseif ($sampleStages[$i] === 'hot') {
                $extra[0] = 'Q-2026-0002';
                $extra[1] = Carbon::now()->subDays(5)->toDateString();
                $extra[2] = 150000000;
                $extra[3] = 3;        // total_terms
                $extra[4] = 1;        // paid_terms
                $extra[5] = 50000000; // paid_amount
                $extra[6] = 100000000;// remaining_amount
            } elseif ($sampleStages[$i] === 'deal') {
                $extra[0] = 'Q-2026-0003';
                $extra[1] = Carbon::now()->subDays(10)->toDateString();
                $extra[2] = 200000000;
                $extra[3] = 4;        // total_terms
                $extra[4] = 4;        // paid_terms
                $extra[5] = 200000000;// paid_amount
                $extra[6] = 0;        // remaining_amount
                $extra[7] = Carbon::now()->toDateString(); // deal_closed_at
            }

            $sheet->fromArray(array_merge($base, $extra), null, 'A'.($i + 2));
        }

        // Other master sheets
        $this->addMasterSheet($spreadsheet, 'Lead Sources', ['id', 'name'], $sources);
        $this->addMasterSheet($spreadsheet, 'Lead Segments', ['id', 'name'], $segments);

        // --- Regions sheet with corrected hierarchy ---
        $regionSheet = $spreadsheet->createSheet();
        $regionSheet->setTitle('Regions');
        $regionCols = ['id', 'regional', 'province', 'branch', 'region'];
        $regionSheet->fromArray($regionCols, null, 'A1');
        $regionSheet->getStyle('A1:'.chr(64 + count($regionCols)).'1')->applyFromArray($headerStyle);
        $regionSheet->freezePane('A2');
        $rowNum = 2;
        foreach ($regions as $r) {
            $regionSheet->fromArray([
                $r->id,
                optional($r->regional)->name,
                optional($r->province)->name,
                optional($r->branch)->name,
                $r->name,
            ], null, 'A'.$rowNum);
            $rowNum++;
        }
        foreach (range('A', chr(64 + count($regionCols))) as $col) {
            $regionSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->addMasterSheet($spreadsheet, 'Sales NIP', ['nip', 'name'], $users);

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
        $sheet->getStyle('A1:'.chr(64 + count($headers)).'1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($collection as $item) {
            $sheet->fromArray(array_values($item->only($headers)), null, 'A'.$row);
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
        $users    = User::select('nip', 'name')->orderBy('nip')->get();

        $file        = $request->file('import_file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet       = $spreadsheet->getSheet(0);

        $rows      = [];
        $validRows = [];
        $hasError  = false;

        foreach ($sheet->toArray(null, true, true, true) as $index => $row) {
            if ($index === 1) continue;

            // treat blank or '-' as null
            $rawRegion = trim((string)$row['C']);
            $regionId  = ($rawRegion === '' || $rawRegion === '-') ? null : $rawRegion;

            try {
                $published = $row['I']
                    ? Carbon::parse($row['I'])->toDateTimeString()
                    : now()->toDateTimeString();
            } catch (\Exception $e) {
                $published = now()->toDateTimeString();
            }

            $data = [
                'source_id'        => $row['A'],
                'segment_id'       => $row['B'],
                'region_id'        => $regionId,
                'lead_name'        => $row['D'],
                'lead_email'       => $row['E'],
                'lead_phone'       => $row['F'],
                'lead_needs'       => $row['G'],
                'nip_sales'        => $row['H'],
                'published_at'     => $published,

                // kolom tambahan untuk stage & detail (dibaca untuk preview)
                'status_stage'     => isset($row['J']) ? trim((string)$row['J']) : '',
                'quotation_number' => isset($row['K']) ? (string)$row['K'] : '',
                'quotation_date'   => isset($row['L']) ? (string)$row['L'] : '',
                'quotation_total'  => isset($row['M']) ? (string)$row['M'] : '',
                'total_terms'      => isset($row['N']) ? (string)$row['N'] : '',
                'paid_terms'       => isset($row['O']) ? (string)$row['O'] : '',
                'paid_amount'      => isset($row['P']) ? (string)$row['P'] : '',
                'remaining_amount' => isset($row['Q']) ? (string)$row['Q'] : '',
                'deal_closed_at'   => isset($row['R']) ? (string)$row['R'] : '',

                'error'            => '',
            ];

            if (empty($data['lead_name'])) {
                continue;
            }

            if (!LeadSource::where('id', $data['source_id'])->exists()) {
                $data['error'] = 'Invalid source_id';
            } elseif (!LeadSegment::where('id', $data['segment_id'])->exists()) {
                $data['error'] = 'Invalid segment_id';
            } elseif (!is_null($data['region_id'])
                      && !Region::where('id', $data['region_id'])->exists()
            ) {
                $data['error'] = 'Invalid region_id';
            } elseif ($data['nip_sales']
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
            if (! LeadSource::where('id', $row['source_id'])->exists()
                || ! LeadSegment::where('id', $row['segment_id'])->exists()
                || (! is_null($row['region_id']) && ! Region::where('id', $row['region_id'])->exists())
                || ($row['nip_sales'] && ! User::where('nip', $row['nip_sales'])->exists())
            ) {
                continue;
            }

            DB::transaction(function () use ($row, &$imported) {
                /* ----------------------------------------------------------
                * status:  ― nip_sales present  →  LeadStatus::COLD
                *          ― nip_sales null     →  LeadStatus::PUBLISHED
                * -------------------------------------------------------- */
                $status = $row['nip_sales']
                    ? LeadStatus::COLD
                    : LeadStatus::PUBLISHED;

                $lead = Lead::create([
                    'source_id'    => $row['source_id'],
                    'segment_id'   => $row['segment_id'],
                    'region_id'    => $row['region_id'],   // may be null = “all regions”
                    'status_id'    => $status,
                    'name'         => $row['lead_name'],
                    'email'        => $row['lead_email'],
                    'phone'        => $row['lead_phone'],
                    'needs'        => $row['lead_needs'],
                    'published_at' => $row['published_at'] ?? now(),
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
}
