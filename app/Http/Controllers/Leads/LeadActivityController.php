<?php
namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Leads\{Lead, LeadActivityLog, LeadActivityList, LeadClaim, LeadStatus, LeadStatusLog};
use App\Models\Attachment;

class LeadActivityController extends Controller
{
    public function logs($leadId)
    {
        $lead = Lead::with(['activityLogs.user', 'activityLogs.activity', 'activityLogs.attachment'])->findOrFail($leadId);

        $logs = $lead->activityLogs->map(function ($log) {
            return [
                'logged_at' => $log->logged_at ? $log->logged_at->format('d M Y') : '-',
                'code'      => $log->activity->code ?? '-',
                'activity'  => $log->activity->name ?? '-',
                'note'      => $log->note,
                'user'      => $log->user->name ?? '-',
                'attachment'=> $log->attachment_id ? route('attachments.download', $log->attachment_id) : null,
            ];
        });

        return response()->json($logs);
    }

    public function save(Request $request, $leadId)
    {
        $lead = Lead::findOrFail($leadId);

        $data = $request->validate([
            'activity_id' => 'required|exists:lead_activity_lists,id',
            'note'        => 'nullable|string',
            'logged_at'   => 'required|date',
            'attachment'  => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);
        $activity = LeadActivityList::findOrFail($data['activity_id']);

        $attachmentId = null;
        if ($request->hasFile('attachment')) {
            $file     = $request->file('attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path     = $file->storeAs('activity-logs', $filename, 'local');

            $attachment = Attachment::create([
                'type'        => 'activity_log',
                'file_path'   => 'storage/' . $path,
                'mime_type'   => $file->getClientMimeType(),
                'size'        => $file->getSize(),
                'uploaded_by' => $request->user()->id ?? null,
            ]);

            $attachmentId = $attachment->id;
        }

        $movedToTrash = false;

        DB::transaction(function () use ($lead, $data, $request, $attachmentId, $activity, &$movedToTrash) {
            LeadActivityLog::create([
                'lead_id'       => $lead->id,
                'activity_id'   => $data['activity_id'],
                'note'          => $data['note'] ?? null,
                'logged_at'     => $data['logged_at'],
                'user_id'       => $request->user()->id,
                'attachment_id' => $attachmentId,
            ]);

            // A23 = Cold -> Masuk Trash
            if ($activity->code === 'A23' && (int) $lead->status_id === LeadStatus::COLD) {
                $firstClaim = $lead->claims()->orderBy('claimed_at')->first();
                if (! $lead->first_sales_id && $firstClaim) {
                    $lead->first_sales_id = $firstClaim->sales_id;
                }

                $lead->status_id = LeadStatus::TRASH_COLD;
                $lead->save();

                $activeClaim = LeadClaim::where('lead_id', $lead->id)
                    ->whereNull('released_at')
                    ->latest('id')
                    ->first();

                if ($activeClaim) {
                    $activeClaim->update([
                        'released_at' => now(),
                        'trash_note'  => $data['note'] ?? 'Moved to trash via activity A23',
                    ]);
                }

                LeadStatusLog::create([
                    'lead_id'   => $lead->id,
                    'status_id' => LeadStatus::TRASH_COLD,
                ]);

                $movedToTrash = true;
            }
        });

        return response()->json([
            'message' => $movedToTrash
                ? 'Activity log saved and lead moved to trash'
                : 'Activity log saved',
            'moved_to_trash' => $movedToTrash,
        ]);
    }
}
