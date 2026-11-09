<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Aftersales\TechnicianDashboard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TechnicianDashboardController extends Controller
{
    private function filters(Request $request): array
    {
        abort_unless($request->user()?->role?->code === 'technician' && $request->user()->hasPermission('dashboard') && $request->user()->hasPermission('aftersales.tickets.view-own'), 403);

        return $request->validate([
            'period' => ['nullable', Rule::in(TechnicianDashboard::PERIODS)],
            'user_id' => 'prohibited', 'technician_id' => 'prohibited', 'assigned_technician_id' => 'prohibited',
            'page' => 'nullable|integer|min:1', 'per_page' => 'nullable|integer|min:1|max:100',
        ]);
    }

    public function summary(Request $request, TechnicianDashboard $dashboard)
    {
        return response()->json(['data' => $dashboard->summary((int) $request->user()->id, $this->filters($request))]);
    }

    public function tasks(Request $request, TechnicianDashboard $dashboard)
    {
        $filters = $this->filters($request);
        $rows = $dashboard->tasks((int) $request->user()->id)->paginate($filters['per_page'] ?? 20);
        $rows->through(fn ($row) => $dashboard->taskItem($row));

        return response()->json($rows);
    }

    public function tickets(Request $request, TechnicianDashboard $dashboard)
    {
        $filters = $this->filters($request) + $request->validate([
            'metric' => 'required|in:status,documents,share_survey,recoming,closed',
            'status' => ['nullable', Rule::in(['assigned', 'on_site', 'repair', 'waiting_sparepart', 'documentation_published', 'satisfaction_submitted', 'happy_call_completed', 'recoming'])],
            'week_start' => 'nullable|date_format:Y-m-d',
        ]);
        $rows = $dashboard->drilldown((int) $request->user()->id, $filters)->orderByDesc('id')->paginate($filters['per_page'] ?? 20);
        $rows->through(fn ($row) => $dashboard->ticketItem($row));

        return response()->json($rows);
    }

    public function documents(Request $request, TechnicianDashboard $dashboard)
    {
        $filters = $this->filters($request);
        $rows = $dashboard->documents((int) $request->user()->id)->orderBy('id')->orderBy('document_type')->orderBy('document_id')->paginate($filters['per_page'] ?? 10);
        $rows->through(fn ($row) => $dashboard->ticketItem($row) + ['document_id' => (int) $row->document_id, 'document_type' => $row->document_type, 'status' => 'draft']);

        return response()->json($rows);
    }

    public function recomings(Request $request, TechnicianDashboard $dashboard)
    {
        $filters = $this->filters($request);
        $rows = $dashboard->drilldown((int) $request->user()->id, ['metric' => 'recoming'])
            ->orderBy('recoming_at')->orderBy('id')->paginate($filters['per_page'] ?? 10);
        $rows->through(fn ($row) => $dashboard->recomingItem($row));

        return response()->json($rows);
    }
}
