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
        $importHeaders = [
            'source_id*', 'segment_id*', 'region_id',
            'lead_name', 'lead_email', 'lead_phone',
            'lead_needs', 'nip_sales', 'published_at',
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
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        $sheet->freezePane('A2');
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Fetch master data
        $sources  = LeadSource::select('id', 'name')->orderBy('id')->get();
        $segments = LeadSegment::select('id', 'name')->orderBy('id')->get();
        $regions  = Region::with(['regional', 'province', 'branch'])->orderBy('id')->get();
        $users    = User::select('nip', 'name')->orderBy('nip')->get();

        // Sample rows
        for ($i = 0; $i < 5; $i++) {
            $sheet->fromArray([
                $sources[$i % max(1, $sources->count())]->id ?? '',
                $segments[$i % max(1, $segments->count())]->id ?? '',
                $regions[$i % max(1, $regions->count())]->id ?? '',
                'Sample Lead '.($i + 1),
                'sample'.($i + 1).'@example.com',
                '080000000'.$i,
                'Sample needs '.($i + 1),
                $users[$i % max(1, $users->count())]->nip ?? '',
                Carbon::now()->subDays($i)->toDateString(),
            ], null, 'A'.($i + 2));
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
                'source_id'    => $row['A'],
                'segment_id'   => $row['B'],
                'region_id'    => $regionId,
                'lead_name'    => $row['D'],
                'lead_email'   => $row['E'],
                'lead_phone'   => $row['F'],
                'lead_needs'   => $row['G'],
                'nip_sales'    => $row['H'],
                'published_at' => $published,
                'error'        => '',
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
