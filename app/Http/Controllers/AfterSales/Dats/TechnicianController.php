<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\TechnicianProfile;
use App\Models\Aftersales\TechnicianSkill;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketLog;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TechnicianController extends Controller
{
    public function page(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.technicians'), 403);

        $this->pageTitle = 'Technicians';

        return $this->render('pages.aftersales.technicians.index');
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.technicians'), 403);

        $query = TechnicianProfile::with(['user', 'region', 'skills']);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $paginated = $query->orderByDesc('id')->paginate($perPage);

        $items = collect($paginated->items())->map(function (TechnicianProfile $profile) {
            return array_merge($profile->toArray(), [
                'status' => $profile->status,
                'total_tickets' => Ticket::where('assigned_technician_id', $profile->user_id)->count(),
            ]);
        });

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function show(Request $request, TechnicianProfile $technician)
    {
        abort_unless($request->user()?->hasPermission('masters.technicians'), 403);

        $technician->load(['user', 'region', 'skills']);

        return response()->json([
            'status' => 'success',
            'data' => array_merge($technician->toArray(), [
                'status' => $technician->status,
                'kpi' => $this->kpiFor($technician->user_id),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasPermission('masters.technicians'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'grade' => 'nullable|string|max:255',
            'region_id' => 'nullable|integer|exists:ref_regions,id',
            'certification' => 'nullable|string|max:255',
            'skills' => 'nullable|array',
            'skills.*.skill_name' => 'required_with:skills|string|max:255',
            'skills.*.level' => 'required_with:skills|in:basic,intermediate,expert',
        ]);

        $technicianRoleId = UserRole::where('code', 'technician')->value('id');
        abort_if(! $technicianRoleId, 500, 'Technician role is not seeded.');

        $profile = DB::transaction(function () use ($validated, $technicianRoleId, $request) {
            $user = User::create([
                'role_id' => $technicianRoleId,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => bcrypt($validated['password']),
                'grade' => $validated['grade'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $profile = TechnicianProfile::create([
                'user_id' => $user->id,
                'region_id' => $validated['region_id'] ?? null,
                'certification' => $validated['certification'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['skills'] ?? [] as $skill) {
                TechnicianSkill::create([
                    'technician_profile_id' => $profile->id,
                    'skill_name' => $skill['skill_name'],
                    'level' => $skill['level'],
                    'created_by' => $request->user()->id,
                ]);
            }

            return $profile;
        });

        $profile->load(['user', 'region', 'skills']);

        return $this->setJsonResponse('Technician created successfully', ['data' => $profile], 201);
    }

    public function update(Request $request, TechnicianProfile $technician)
    {
        abort_unless($request->user()?->hasPermission('masters.technicians'), 403);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'grade' => 'nullable|string|max:255',
            'region_id' => 'nullable|integer|exists:ref_regions,id',
            'certification' => 'nullable|string|max:255',
            'skills' => 'nullable|array',
            'skills.*.skill_name' => 'required_with:skills|string|max:255',
            'skills.*.level' => 'required_with:skills|in:basic,intermediate,expert',
        ]);

        DB::transaction(function () use ($validated, $technician, $request) {
            $technician->user->update(array_filter([
                'name' => $validated['name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'grade' => $validated['grade'] ?? null,
            ], fn ($v) => $v !== null));

            $technician->update([
                'region_id' => $validated['region_id'] ?? $technician->region_id,
                'certification' => $validated['certification'] ?? $technician->certification,
                'updated_by' => $request->user()->id,
            ]);

            if (array_key_exists('skills', $validated)) {
                $technician->skills()->delete();
                foreach ($validated['skills'] as $skill) {
                    TechnicianSkill::create([
                        'technician_profile_id' => $technician->id,
                        'skill_name' => $skill['skill_name'],
                        'level' => $skill['level'],
                        'created_by' => $request->user()->id,
                    ]);
                }
            }
        });

        $technician->load(['user', 'region', 'skills']);

        return $this->setJsonResponse('Technician updated successfully', ['data' => $technician->fresh(['user', 'region', 'skills'])]);
    }

    public function destroy(Request $request, TechnicianProfile $technician)
    {
        abort_unless($request->user()?->hasPermission('masters.technicians'), 403);

        $technician->update(['deleted_by' => $request->user()->id, 'is_deleted' => true]);
        $technician->delete();

        return $this->setJsonResponse('Technician deleted successfully');
    }

    public function kpi(Request $request, TechnicianProfile $technician)
    {
        abort_unless($request->user()?->hasPermission('aftersales.kpi.view'), 403);

        return response()->json(['status' => 'success', 'data' => $this->kpiFor($technician->user_id)]);
    }

    /**
     * All aggregates are computed on the fly, keyed by user_id — never by
     * technician name — per PRD §3.2 / §8.
     */
    private function kpiFor(int $userId): array
    {
        $tickets = Ticket::where('assigned_technician_id', $userId)->with('logs', 'satisfaction')->get();

        $totalTickets = $tickets->count();
        $closedTickets = $tickets->filter(fn (Ticket $t) => $t->progress === TicketLog::STEP_CLOSED);
        $closingRate = $totalTickets > 0 ? round($closedTickets->count() / $totalTickets * 100, 2) : 0;

        $onTimeClosed = $closedTickets->filter(fn (Ticket $t) => $t->sla_status === 'On Time')->count();
        $slaAdherence = $closedTickets->count() > 0 ? round($onTimeClosed / $closedTickets->count() * 100, 2) : 0;

        $avgCsat = $tickets->pluck('satisfaction')->filter()->avg('average_score');

        return [
            'total_tickets' => $totalTickets,
            'closed_tickets' => $closedTickets->count(),
            'closing_rate' => $closingRate,
            'sla_adherence' => $slaAdherence,
            'avg_csat' => $avgCsat ? round($avgCsat, 2) : null,
        ];
    }
}
