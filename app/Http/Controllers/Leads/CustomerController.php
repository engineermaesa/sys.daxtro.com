<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Models\Leads\{LeadClaim, LeadStatus};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function show(Request $request, $claimId)
    {
        $claim = $this->authorizedClaim($request, $claimId);

        return response()->json([
            'lead' => $claim->lead,
            'customer' => $claim->lead->customer,
        ]);
    }

    public function store(Request $request, $claimId)
    {
        $claim = $this->authorizedClaim($request, $claimId);

        $validator = Validator::make($request->all(), [
            'location_link'      => 'nullable|url|max:255',
            'electricity'         => 'nullable|numeric|min:0',
            'building_area'       => 'nullable|numeric|min:0',
            'access_road_width'   => 'nullable|numeric|min:0',
            'file_cad'            => 'nullable|array',
            'file_cad.*'          => 'file|extensions:dwg,dxf,zip|max:10240',
            'remove_file_cad'     => 'nullable|array',
            'remove_file_cad.*'   => 'string',
        ]);

        if ($validator->fails()) {
            return $this->setJsonResponse(
                $validator->errors()->first(),
                ['errors' => $validator->errors()->toArray()],
                422
            );
        }

        $data = $validator->safe()->only(['location_link', 'electricity', 'building_area', 'access_road_width']);

        $existingFiles = $claim->lead->customer?->file_cad ?? [];

        if ($request->filled('remove_file_cad')) {
            $filesToRemove = $request->input('remove_file_cad');
            Storage::disk('public')->delete(array_intersect($existingFiles, $filesToRemove));
            $existingFiles = array_values(array_diff($existingFiles, $filesToRemove));
        }

        if ($request->hasFile('file_cad')) {
            foreach ($request->file('file_cad') as $file) {
                $existingFiles[] = $file->store('cad', 'public');
            }
        }

        $data['file_cad'] = $existingFiles;

        $customer = $claim->lead->customer()->updateOrCreate(
            ['leads_id' => $claim->lead_id],
            $data
        );

        return $this->setJsonResponse(
            'Customer data saved successfully.',
            ['customer' => $customer]
        );
    }

    protected function authorizedClaim(Request $request, $claimId): LeadClaim
    {
        $claim = LeadClaim::with(['lead.customer', 'lead.product.type'])
            ->whereNull('released_at')
            ->whereNull('trash_note')
            ->findOrFail($claimId);

        abort_unless($claim->lead && $claim->lead->status_id == LeadStatus::DEAL, 403, 'Lead is not in DEAL status.');

        $userRole = $request->user()->role?->code;
        if ($userRole === 'sales') {
            abort_unless($claim->sales_id === $request->user()->id, 403);
        }

        return $claim;
    }
}
