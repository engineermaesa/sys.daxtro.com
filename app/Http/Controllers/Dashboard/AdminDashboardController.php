<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Aftersales\AdminDashboard;
use App\Services\Aftersales\TicketDocuments;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminDashboardController extends Controller
{
    private function filters(Request $request): array
    {
        $user = $request->user();
        abort_unless(in_array($user?->role?->code, ['after_sales', 'supervisor_after_sales', 'head_after_sales', 'super_admin'], true)
            && $user->hasPermission('dashboard') && TicketDocuments::canListTickets($user)
            && (TicketDocuments::canViewAll($user) || TicketDocuments::isSupervisorScoped($user)), 403);

        return $request->validate(['page' => 'nullable|integer|min:1', 'per_page' => 'nullable|integer|min:1|max:100', 'period' => 'prohibited']);
    }

    public function summary(Request $request, AdminDashboard $dashboard)
    {
        $this->filters($request);

        return response()->json(['data' => $dashboard->summary($request->user())]);
    }

    public function tickets(Request $request, AdminDashboard $dashboard)
    {
        $filters = $this->filters($request) + $request->validate([
            'queue' => ['required', Rule::in(['all', 'progress', 'sla', 'visits', 'docs_rework', ...array_keys(AdminDashboard::FOLLOW_UP)])],
            'progress' => ['required_if:queue,progress', 'nullable', Rule::in([...array_keys(AdminDashboard::PROGRESS), 'recoming'])],
            'sla' => 'required_if:queue,sla|nullable|in:on_track,at_risk,overdue,not_configured',
            'date' => 'required_if:queue,visits|nullable|date_format:Y-m-d',
            'type' => 'nullable|in:visit,recoming,phone_call',
            'technician_id' => 'nullable|integer|min:1',
        ]);
        $rows = $dashboard->queue($request->user(), $filters)->orderByDesc('id')->paginate($filters['per_page'] ?? 20)->withQueryString();
        $rows->through(fn ($row) => $dashboard->ticketItem($row));

        return response()->json($rows);
    }

    public function hygiene(Request $request, AdminDashboard $dashboard)
    {
        $filters = $this->filters($request) + $request->validate(['queue' => ['required', Rule::in(array_keys(AdminDashboard::HYGIENE))]]);
        $rows = $dashboard->hygiene($request->user(), $filters['queue'])->orderByDesc('id')->paginate($filters['per_page'] ?? 20)->withQueryString();
        $rows->through(fn ($row) => $dashboard->hygieneItem($row, $filters['queue']));

        return response()->json(['entity' => $dashboard->hygieneEntity($filters['queue']), ...$rows->toArray()]);
    }

    public function technicians(Request $request, AdminDashboard $dashboard)
    {
        $filters = $this->filters($request);
        $rows = $dashboard->technicians($request->user())->paginate($filters['per_page'] ?? 20)->withQueryString();
        $offset = ($rows->currentPage() - 1) * $rows->perPage();
        $rows->setCollection($rows->getCollection()->map(fn ($row, $i) => $dashboard->technicianItem($row, $offset + $i + 1)));

        return response()->json($rows);
    }
}
