<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsReader;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class SatisfactionSurveyController extends Controller
{
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
}
