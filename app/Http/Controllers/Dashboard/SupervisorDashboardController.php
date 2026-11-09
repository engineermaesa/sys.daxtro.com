<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Aftersales\SupervisorDashboard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupervisorDashboardController extends Controller
{
    public static function authorize(Request $request): void
    {
        abort_unless(in_array($request->user()?->role?->code, ['supervisor_after_sales', 'head_after_sales'], true), 403);
    }

    private function filters(Request $request): array
    {
        self::authorize($request);

        return $request->validate([
            'period' => ['nullable', Rule::in(SupervisorDashboard::PERIODS)],
            'start_date' => 'required_if:period,custom|nullable|date_format:Y-m-d|after_or_equal:2000-01-01',
            'end_date' => 'required_if:period,custom|nullable|date_format:Y-m-d|after_or_equal:start_date|before_or_equal:2100-12-31',
            'region' => 'nullable|integer|exists:ref_regions,id',
            'category' => 'nullable|integer|exists:ref_product_types,id',
        ]);
    }

    public function summary(Request $request, SupervisorDashboard $dashboard)
    {
        return response()->json(['data' => $dashboard->summary($this->filters($request))]);
    }

    public function tickets(Request $request, SupervisorDashboard $dashboard)
    {
        $filters = $this->filters($request);
        $filters += $request->validate([
            'metric' => 'required|in:warranty,unclassified,problem',
            'bucket' => 'nullable|in:warranty,unclassified,billed,pm',
            'mode' => 'nullable|in:model,region,technician,machine,month',
            'column' => 'nullable|string|max:255',
            'problem' => 'required_if:metric,problem|nullable|in:refrigeration_system,mechanical,production,electrical,not_set',
            'page' => 'nullable|integer|min:1',
        ]);
        if ($filters['metric'] === 'unclassified') {
            $filters['bucket'] = 'unclassified';
        }
        if ($filters['metric'] !== 'problem' && isset($filters['mode']) && ! in_array($filters['mode'], ['model', 'region', 'technician'], true)) {
            abort(422);
        }

        return response()->json($dashboard->drilldown($filters)->paginate(15));
    }
}
