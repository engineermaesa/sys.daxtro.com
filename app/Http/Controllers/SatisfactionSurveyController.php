<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SatisfactionSurveyController extends Controller
{
    // TODO: backend satisfaction survey sementara dinonaktifkan (dikomentari) dan memakai DUMMY DATA.
    // Survey nanti dibuat otomatis saat ticket dibuat oleh role aftersales,
    // sumber data diganti dari Google Sheets ke database.
    public function index(Request $request)
    {
        $all = $this->dummyResponses();

        if ($request->filled('search')) {
            $term = strtolower($request->string('search'));
            $all = $all->filter(fn ($r) => str_contains(
                strtolower($r['ticket_id'].' '.$r['customer'].' '.$r['company'].' '.$r['technician']),
                $term
            ));
        }

        $perPage = 5;
        $page = max(1, (int) $request->get('page', 1));
        $all = $all->values();

        $responses = new LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('pages.satisfaction-survey.index', [
            'responses' => $responses,
        ]);
    }

    // TODO: DUMMY DATA — ganti dengan data dari DB saat backend survey siap.
    protected function dummyResponses(): Collection
    {
        $labels = [
            'Punctuality', 'Technician Attitude', 'Technical Knowledge', 'Work Speed', 'Service Quality',
            'Cleanliness', 'Work Explanation', 'Overall Satisfaction', 'Technician Appearance',
        ];

        $rows = [
            ['DAX-AF-20261012-1', 'Budi Santoso', 'PT Global Dynamics', 'WO-0001', '2026-10-12', '2026-10-12 16:42', 'Ahmad Fauzi', 'Service', [5, 5, 5, 4, 5, 5, 5, 5, 4], true, '', 'Teknisi cepat dan ramah.', 'Budi Santoso', 'Production Manager', 'Customer link', 'needs_review'],
            ['DAX-AF-20261010-2', 'Rina Wijaya', 'CV Heavy Logistics', 'WO-0002', '2026-10-10', '2026-10-10 11:10', 'Rizky Pratama', 'Repair', [4, 4, 4, 4, 5, 4, 4, 4, 4], false, 'Mesin masih berbunyi saat beban penuh.', 'Perlu follow up sparepart.', 'Rina Wijaya', 'Supervisor', 'Admin assisted', 'reviewed'],
            ['DAX-AF-20261008-3', 'Hendra Gunawan', 'PT Maju Bersama', 'WO-0003', '2026-10-08', '2026-10-09 09:15', 'Dedi Kurniawan', 'Preventive Maintenance', [3, 4, 3, 3, 4, 3, 4, 3, 4], true, '', '', 'Hendra Gunawan', 'Owner', 'Customer link', 'needs_review'],
            ['DAX-AF-20261005-4', 'Siti Aminah', 'PT Sinar Abadi', 'WO-0004', '2026-10-05', '2026-10-05 14:30', 'Ahmad Fauzi', 'Installation', [5, 5, 4, 5, 5, 5, 5, 5, 5], true, '', 'Instalasi rapi, terima kasih.', 'Siti Aminah', 'Factory Manager', 'Admin assisted', 'reviewed'],
            ['DAX-AF-20261003-5', 'Agus Salim', 'UD Berkah Jaya', 'WO-0005', '2026-10-03', '2026-10-04 08:05', 'Rizky Pratama', 'Service', [2, 3, 3, 2, 3, 3, 2, 2, 3], false, 'Teknisi datang terlambat, masalah belum tuntas.', 'Mohon ketepatan waktu ditingkatkan.', 'Agus Salim', 'Operator', 'Customer link', 'needs_review'],
            ['DAX-AF-20260929-6', 'Dewi Lestari', 'PT Nusantara Pack', 'WO-0006', '2026-09-29', '2026-09-29 17:20', 'Dedi Kurniawan', 'Repair', [4, 5, 5, 4, 4, 4, 5, 4, 5], true, '', 'Penjelasan hasil pekerjaan jelas.', 'Dewi Lestari', 'Maintenance Head', 'Customer link', 'reviewed'],
            ['DAX-AF-20260925-7', 'Yoga Prakoso', 'PT Citra Mandiri', 'WO-0007', '2026-09-25', '2026-09-26 10:00', 'Ahmad Fauzi', 'Preventive Maintenance', [5, 4, 5, 5, 5, 4, 4, 5, 4], true, '', '', 'Yoga Prakoso', 'Supervisor', 'Admin assisted', 'needs_review'],
            ['DAX-AF-20260920-8', 'Lilis Marlina', 'CV Tiga Roda', 'WO-0008', '2026-09-20', '2026-09-20 13:45', 'Rizky Pratama', 'Service', [4, 4, 4, 3, 4, 4, 4, 4, 4], false, 'Kebocoran kecil masih terlihat.', 'Tolong dijadwalkan kunjungan ulang.', 'Lilis Marlina', 'Owner', 'Customer link', 'needs_review'],
        ];

        return collect($rows)->map(function ($r) use ($labels) {
            [$ticket, $customer, $company, $wo, $serviceDate, $submitted, $tech, $jobType, $scores, $machineOk, $pending, $feedback, $respondent, $role, $source, $review] = $r;

            return [
                'ticket_id' => $ticket,
                'customer' => $customer,
                'company' => $company,
                'wo_number' => $wo,
                'service_date' => Carbon::parse($serviceDate, 'Asia/Jakarta'),
                'technician' => $tech,
                'job_type' => $jobType,
                'avg_score' => round(array_sum($scores) / count($scores), 1),
                'score_breakdown' => array_map(
                    fn ($label, $score) => ['label' => $label, 'score' => (float) $score],
                    $labels,
                    $scores
                ),
                'machine_ok' => $machineOk,
                'pending_issues' => $pending,
                'feedback' => $feedback,
                'respondent' => $respondent,
                'role' => $role,
                'submitted_at' => Carbon::parse($submitted, 'Asia/Jakarta'),
                'source' => $source,
                'review_status' => $review,
            ];
        });
    }

    /*
    protected const JOB_TYPES = ['Service', 'Repair', 'Preventive Maintenance', 'Installation'];

    protected const SCORE_LABELS = [
        'Ketepatan Waktu Kedatangan' => 'Punctuality',
        'Sikap & Keramahan Teknisi' => 'Technician Attitude',
        'Pengetahuan & Kompetensi Teknisi' => 'Technical Knowledge',
        'Kecepatan Menyelesaikan Pekerjaan' => 'Work Speed',
        'Kualitas Hasil Service / Repair' => 'Service Quality',
        'Kerapihan & Kebersihan Area Kerja' => 'Cleanliness',
        'Penjelasan Hasil Pekerjaan' => 'Work Explanation',
        'Kepuasan Pelayanan Secara Keseluruhan' => 'Overall Satisfaction',
        'Kerapihan Penampilan Teknisi (Grooming)' => 'Technician Appearance',
    ];

    public function index(Request $request, GoogleSheetsReader $sheets)
    {
        $all = $this->getResponses($sheets);

        $jobTypeCounts = collect(self::JOB_TYPES)->mapWithKeys(
            fn ($type) => [$type => $all->filter(fn ($r) => strcasecmp($r['job_type'], $type) === 0)->count()]
        );

        $pending = $all->filter(fn ($r) => $r['machine_ok'] === false);
        $resolved = $all->filter(fn ($r) => $r['machine_ok'] === true);
        $resolvedPct = $all->count() ? (int) round($resolved->count() / $all->count() * 100) : 0;

        $filtered = $this->applyFilters($all, $request)->values();

        $perPage = 5;
        $page = max(1, (int) $request->get('page', 1));
        $responses = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('pages.satisfaction-survey.index', [
            'responses' => $responses,
            'jobTypes' => self::JOB_TYPES,
            'jobTypeCounts' => $jobTypeCounts,
            'totalWorkType' => $all->count(),
            'pendingCount' => $pending->count(),
            'pendingCompanies' => $pending->pluck('company')->filter()->unique()->values(),
            'resolvedCount' => $resolved->count(),
            'resolvedPct' => $resolvedPct,
        ]);
    }

    protected function applyFilters($all, Request $request)
    {
        return $all
            ->when($request->filled('search'), function ($c) use ($request) {
                $term = strtolower($request->string('search'));

                return $c->filter(fn ($r) => str_contains(
                    strtolower($r['customer'].' '.$r['company'].' '.$r['technician']),
                    $term
                ));
            })
            ->when($request->filled('job_type'), fn ($c) => $c->filter(
                fn ($r) => strcasecmp($r['job_type'], $request->string('job_type')) === 0
            ))
            ->when($request->filled('from_date'), fn ($c) => $c->filter(
                fn ($r) => $r['service_date'] && $r['service_date']->format('Y-m-d') >= $request->string('from_date')
            ))
            ->when($request->filled('to_date'), fn ($c) => $c->filter(
                fn ($r) => $r['service_date'] && $r['service_date']->format('Y-m-d') <= $request->string('to_date')
            ))
            ->when($request->get('sort') === 'score_asc', fn ($c) => $c->sortBy('avg_score'))
            ->when($request->get('sort') === 'score_desc', fn ($c) => $c->sortByDesc('avg_score'));
    }

    protected function getResponses(GoogleSheetsReader $sheets)
    {
        $rows = Cache::remember('satisfaction_survey_rows', now()->addMinutes(5), function () use ($sheets) {
            return $sheets->getRows('A:Z');
        });

        $header = array_values(array_filter(array_map('trim', $rows[0] ?? [])));

        return collect(array_slice($rows, 1))
            ->filter(fn ($row) => count(array_filter($row)) > 0)
            ->map(function ($row) use ($header) {
                $row = array_pad($row, count($header), '');
                $data = array_combine($header, array_slice($row, 0, count($header)));

                return $this->mapRow($data);
            })
            ->reverse()
            ->values();
    }

    protected function mapRow(array $data): array
    {
        $breakdown = collect(self::SCORE_LABELS)->map(fn ($label, $key) => [
            'label' => $label,
            'score' => is_numeric($data[$key] ?? null) ? (float) $data[$key] : null,
        ])->values();

        $scores = $breakdown->pluck('score')->filter(fn ($s) => $s !== null);

        $machineOkRaw = strtolower(trim($data['Apakah Mesin Sudah Berfungsi Dengan Baik?'] ?? ''));

        return [
            'customer' => $data['Nama Customer'] ?? '',
            'company' => $data['Nama Perusahaan'] ?? '',
            'wo_number' => $data['Nomor Work Order'] ?? '',
            'service_date' => $this->parseDate($data['Tanggal Service'] ?? null),
            'technician' => $data['Nama Teknisi'] ?? '',
            'job_type' => $data['Jenis Pekerjaan'] ?? '',
            'avg_score' => $scores->isNotEmpty() ? round($scores->avg(), 1) : null,
            'score_breakdown' => $breakdown->all(),
            'machine_ok' => match ($machineOkRaw) {
                'ya' => true,
                'tidak' => false,
                default => null,
            },
            'pending_issues' => $data['Mohon jelaskan kendala yang masih ada'] ?? '',
            'feedback' => $data['Tuliskan kritik & saran Anda di sini'] ?? '',
            'respondent' => $data['Nama'] ?? '',
            'role' => $data['Jabatan'] ?? '',
            'submitted_at' => $this->parseDate($data['Timestamp'] ?? null, true),
        ];
    }

    protected function parseDate(?string $value, bool $withTime = false): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $formats = $withTime
            ? ['d/m/Y H:i:s', 'd/m/Y']
            : ['d/m/Y', 'd/m/Y H:i:s'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value, 'Asia/Jakarta');
            } catch (\Throwable $e) {
                // try next format
            }
        }

        return null;
    }
    */
}
