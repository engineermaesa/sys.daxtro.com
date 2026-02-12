<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\{Attachment};
use App\Models\Leads\{LeadMeeting, LeadMeetingReschedule, LeadStatus, LeadStatusLog};
use App\Models\Masters\MeetingType;
use App\Models\Orders\{MeetingExpense, MeetingExpenseDetail, FinanceRequest};

class MeetingController extends Controller
{

    public function save(Request $request, $id = null)
    {
        $meetingType = MeetingType::find($request->meeting_type_id);
        $onlineNames = ['Zoom / Google Meet', 'Video Call'];
        $isOnline = $meetingType && in_array($meetingType->name, $onlineNames);
        $zoomId = MeetingType::where('name', 'Zoom / Google Meet')->value('id');

        $rules = [
            'lead_id'            => 'required|exists:leads,id',
            'meeting_type_id'    => 'required|exists:meeting_types,id',
            'scheduled_start_at' => 'required|date',
            'scheduled_end_at'   => 'required|date|after:scheduled_start_at',
            'meeting_url'        => ($request->meeting_type_id == $zoomId) ? 'required|url' : 'nullable|url',
            
            // Lead details validation
            'lead_name.*'        => 'required|string',
            'lead_type.*'        => 'required|in:office,canvas',
            'lead_province.*'    => 'required|string',
            'lead_city.*'        => 'required|string',
            'lead_product_id.*'  => 'required|exists:ref_products,id',
            'lead_price.*'       => 'required',
            'lead_description.*' => 'nullable|string',
        ];

        if (! $isOnline) {
            $rules['province'] = 'required|string';
            $rules['city'] = 'required|in:' . implode(',', config('cities'));
            $rules['address'] = 'required';
            $rules['expense_notes.*'] = 'nullable|required';
            $rules['expense_amount.*'] = 'nullable|required|numeric';
        }

        $request->validate($rules, [
            'lead_id.required' => 'Please select a lead.',
            'lead_id.exists' => 'The selected lead was not found.',

            'meeting_type_id.required' => 'Please select a meeting type.',
            'meeting_type_id.exists' => 'Invalid meeting type selected.',

            'scheduled_start_at.required' => 'The meeting start time is required.',
            'scheduled_start_at.date' => 'The meeting start time must be a valid date.',

            'scheduled_end_at.required' => 'The meeting end time is required.',
            'scheduled_end_at.date' => 'The meeting end time must be a valid date.',
            'scheduled_end_at.after' => 'The end time must be after the start time.',

            'province.required' => 'Province is required for offline meetings.',
            'city.required_if' => 'City is required for offline meetings.',
            'address.required_if' => 'Address is required for offline meetings.',

            'meeting_url.required' => 'The online meeting link is required for Zoom / Google Meet.',
            'meeting_url.url' => 'Please provide a valid URL for the online meeting.',

            'lead_name.*.required' => 'Lead name is required.',
            'lead_type.*.required' => 'Lead type is required.',
            'lead_type.*.in' => 'Lead type must be office or canvas.',
            'lead_province.*.required' => 'Lead province is required.',
            'lead_city.*.required' => 'Lead city is required.',
            'lead_product_id.*.required' => 'Product is required.',
            'lead_product_id.*.exists' => 'Invalid product selected.',
            'lead_price.*.required' => 'Price is required.',

            'expense_notes.*.required_if' => 'Expense notes is required for offline meetings.',
            'expense_amount.*.required_if' => 'Expense amount is required for offline meetings.',
            'expense_amount.*.numeric' => 'Expense amount must be a valid number.',
        ]);

        if (! $isOnline) {
            $validator = Validator::make($request->all(), [
                'expense_type_id' => 'required|array',
                'expense_type_id.*' => 'required|exists:ref_expense_types,id',
            ], [
                'expense_type_id.required' => 'Expense type is required for offline meetings.',
                'expense_type_id.*.required' => 'Each expense type is required.',
                'expense_type_id.*.exists' => 'Invalid expense type selected.',
            ]);

            $validator->validate();
        }

        DB::beginTransaction();
        try {
            if ($id) {
                $meeting = LeadMeeting::findOrFail($id);

                LeadMeetingReschedule::create([
                    'meeting_id'              => $meeting->id,
                    'old_scheduled_start_at'  => $meeting->scheduled_start_at,
                    'old_scheduled_end_at'    => $meeting->scheduled_end_at,
                    'new_scheduled_start_at'  => $request->scheduled_start_at,
                    'new_scheduled_end_at'    => $request->scheduled_end_at,
                    'old_online_url'          => $meeting->online_url,
                    'new_online_url'          => $request->meeting_url,
                    'old_location'            => trim(($meeting->province ?? '') . ' - ' . ($meeting->city ?? '') . ' ' . ($meeting->address ?? '')),
                    'new_location'            => trim(($request->province ?? '') . ' - ' . ($request->city ?? '') . ' ' . ($request->address ?? '')),
                    'reason'                  => $request->reason,
                    'rescheduled_by'          => $request->user()->id,
                    'rescheduled_at'          => now(),
                ]);

                $meeting->update([
                    'meeting_type_id'    => $request->meeting_type_id,
                    'is_online'          => $isOnline,
                    'scheduled_start_at' => $request->scheduled_start_at,
                    'scheduled_end_at'   => $request->scheduled_end_at,
                    'province'           => $request->province,
                    'city'               => $request->city,
                    'address'            => $request->address,
                    'online_url'         => $request->meeting_url,
                ]);

                // Update lead details
                $meeting->leadDetails()->delete();
                $this->saveMeetingLeadDetails($request, $meeting);
                
                if (! $isOnline && $meeting->expense && $meeting->expense->financeRequest) {
                    $meeting->expense->financeRequest->update([
                        'status' => 'pending',
                        'decided_at' => null,
                        'approver_id' => null,
                    ]);
                }

                if (! $isOnline) {
                    $this->updateMeetingExpense($request, $meeting);
                }

                DB::commit();
                if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => 'Meeting rescheduled successfully',
                        'data' => $meeting,
                    ]);
                }

                return $this->setJsonResponse('Meeting rescheduled successfully');
            }

            $meeting = LeadMeeting::create([
                'lead_id'            => $request->lead_id,
                'meeting_type_id'    => $request->meeting_type_id,
                'is_online'          => $isOnline,
                'online_url'         => $request->meeting_url,
                'scheduled_start_at' => $request->scheduled_start_at,
                'scheduled_end_at'   => $request->scheduled_end_at,
                'province'           => $request->province,
                'city'               => $request->city,
                'address'            => $request->address,
            ]);

            $this->saveMeetingLeadDetails($request, $meeting);

            if (! $isOnline) {
                $this->createMeetingExpense($request, $meeting);
            }

            DB::commit();
            if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Meeting created successfully',
                    'data' => $meeting,
                ]);
            }

            return $this->setJsonResponse('Meeting created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Meeting Save Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error saving meeting: ' . $e->getMessage()], 500);
        }
    }

    protected function saveMeetingLeadDetails(Request $request, LeadMeeting $meeting)
    {
        foreach ($request->lead_name as $index => $name) {
            LeadMeetingDetail::create([
                'lead_meeting_id' => $meeting->id,
                'name'            => $name,
                'type'            => $request->lead_type[$index],
                'province'        => $request->lead_province[$index],
                'city'            => $request->lead_city[$index],
                'product_id'      => $request->lead_product_id[$index],
                'price'           => str_replace(['.', ','], ['', '.'], $request->lead_price[$index]),
                'description'     => $request->lead_description[$index] ?? null,
            ]);
        }
    }
    
    protected function createMeetingExpense(Request $request, LeadMeeting $meeting)
    {
        $expenseItems = [];
        $totalAmount = 0;
        $typeIds = $request->expense_type_id ?? [];
        $notes = $request->expense_notes ?? [];
        $amounts = $request->expense_amount ?? [];

        foreach ($typeIds as $idx => $typeId) {
            $note = $notes[$idx] ?? null;
            $amount = $amounts[$idx] ?? null;
            if ($typeId && $note && $amount) {
                $expenseItems[] = [
                    'expense_type_id' => $typeId,
                    'notes'           => $note,
                    'amount'          => $amount,
                ];
                $totalAmount += $amount;
            }
        }
        
        $expenseStatus = $totalAmount > 0 ? 'submitted' : 'approved';

        $expense = MeetingExpense::create([
            'meeting_id'   => $meeting->id,
            'sales_id'     => $request->user()->id,
            'amount'       => $totalAmount,
            'status'       => $expenseStatus,
            'requested_at' => now(),
        ]);

        foreach ($expenseItems as $item) {
            $item['meeting_expense_id'] = $expense->id;
            MeetingExpenseDetail::create($item);
        }

        if ($totalAmount > 0) {
            FinanceRequest::create([
                'request_type' => 'meeting-expense',
                'reference_id' => $expense->id,
                'requester_id' => $request->user()->id,
                'status'       => 'pending',
            ]);
        }
    }

    protected function updateMeetingExpense(Request $request, LeadMeeting $meeting)
    {
        $typeIds = $request->expense_type_id ?? [];
        $notes = $request->expense_notes ?? [];
        $amounts = $request->expense_amount ?? [];

        // Hitung total baru
        $totalAmount = 0;
        $expenseItems = [];

        foreach ($typeIds as $idx => $typeId) {
            $note = $notes[$idx] ?? null;
            $amount = $amounts[$idx] ?? null;
            if ($typeId && $note && $amount) {
                $expenseItems[] = [
                    'expense_type_id' => $typeId,
                    'notes'           => $note,
                    'amount'          => $amount,
                ];
                $totalAmount += $amount;
            }
        }

        $expense = $meeting->expense;

        if (! $expense) {
            // Kalau tidak ada, buat baru seperti createMeetingExpense
            return $this->createMeetingExpense($request, $meeting);
        }

        // Update total amount
        $expenseStatus = $totalAmount > 0 ? 'submitted' : 'approved';

        $expense->update([
            'amount'       => $totalAmount,
            'status'       => $expenseStatus,
            'requested_at' => now(),
        ]);

        // Hapus detail lama & masukkan ulang
        $expense->details()->delete();

        foreach ($expenseItems as $item) {
            $item['meeting_expense_id'] = $expense->id;
            MeetingExpenseDetail::create($item);
        }

        // Update or remove finance request based on amount
        if ($expense->financeRequest) {
            if ($totalAmount > 0) {
                $expense->financeRequest->update([
                    'status' => 'pending',
                    'decided_at' => null,
                    'approver_id' => null,
                ]);
            } else {
                $expense->financeRequest->update(['status' => 'canceled']);
            }
        } elseif ($totalAmount > 0) {
            FinanceRequest::create([
                'request_type' => 'meeting-expense',
                'reference_id' => $expense->id,
                'requester_id' => $request->user()->id,
                'status'       => 'pending',
            ]);
        }
    }

    public function resultForm(Request $request, $id)
    {
        $meeting = LeadMeeting::with('expense')->findOrFail($id);

        if (!$meeting->is_online && optional($meeting->expense)->status !== 'approved') {
            abort(403, 'Finance approval is required before submitting result.');
        }

        $data = LeadMeeting::with('lead')->findOrFail($id);

        if ($data->result && $data->result !== 'waiting') {
            abort(403, 'Meeting result already submitted.');
        }

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json(['data' => $data]);
        }

        return view('pages.leads.cold.meeting-result', compact('data'));
    }

    public function result($id, Request $request)
    {
        $request->validate([
            'result'        => 'required|in:yes,no,waiting',
            'summary'       => 'required|string',
            'attachment_id' => $request->result === 'yes' ? 'required|file|mimes:pdf,jpg,png,docx,doc|max:5120' : 'nullable',
        ]);

        $meeting = LeadMeeting::findOrFail($id);

        $meeting->result  = $request->result;
        $meeting->summary = $request->summary;

        if ($request->result === 'yes' && $request->hasFile('attachment_id')) {
            $file = $request->file('attachment_id');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('meetings', $filename, 'local'); 

            // Simpan data ke tabel attachments
            $attachment = Attachment::create([
                'type'        => 'meeting',
                'file_path'   => 'storage/' . $path,
                'mime_type'   => $file->getClientMimeType(),
                'size'        => $file->getSize(),
                'uploaded_by' => $request->user()->id ?? null,
            ]);

            $meeting->attachment_id = $attachment->id;
        }

        $meeting->save();

        // Update lead status when result is final
        if ($request->result !== 'waiting') {
            $lead = $meeting->lead;
            $newStatus = $request->result === 'yes'
                ? LeadStatus::WARM
                : LeadStatus::TRASH_COLD;

            $lead->status_id = $newStatus;
            $lead->save();
            
            // Force update timestamp saat pindah status
            Log::info('Before update - Lead ID: ' . $lead->id . ', updated_at: ' . $lead->updated_at);
            
            DB::table('leads')
                ->where('id', $lead->id)
                ->update(['updated_at' => now()]);
            
            // Update claim's claimed_at when status changes to WARM
            if ($newStatus === LeadStatus::WARM) {
                DB::table('lead_claims')
                    ->where('lead_id', $lead->id)
                    ->whereNull('released_at')
                    ->update(['claimed_at' => now()]);
            }
            
            $lead->refresh();
            Log::info('After update - Lead ID: ' . $lead->id . ', updated_at: ' . $lead->updated_at);

            LeadStatusLog::create([
                'lead_id'   => $lead->id,
                'status_id' => $newStatus,
            ]);
        }

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Meeting result saved successfully',
                'meeting' => $meeting,
            ]);
        }

        return $this->setJsonResponse('Meeting result saved successfully');
    }

    public function cancel(Request $request, $id)
    {
        $meeting = LeadMeeting::with([
            'expense.details',
            'expense.financeRequest',
            'reschedules',
        ])->findOrFail($id);

        DB::transaction(function () use ($meeting) {
            if ($meeting->expense) {
                $meeting->expense->details()->delete();
                $meeting->expense->financeRequest()->delete();
                $meeting->expense->delete();
            }

            $meeting->reschedules()->delete();

            $meeting->delete();
        });

        if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Meeting canceled']);
        }

        return $this->setJsonResponse('Meeting canceled');
    }
}
