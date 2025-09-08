<?php
namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leads\{Lead, LeadActivityLog, LeadActivityList};
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

        LeadActivityLog::create([
            'lead_id'     => $lead->id,
            'activity_id' => $data['activity_id'],
            'note'        => $data['note'] ?? null,
            'logged_at'   => $data['logged_at'],
            'user_id'     => $request->user()->id,
            'attachment_id' => $attachmentId,
        ]);

        return response()->json(['message' => 'Activity log saved']);
    }
}
