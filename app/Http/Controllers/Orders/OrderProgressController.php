<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProgressLog;
use Illuminate\Http\Request;

class OrderProgressController extends Controller
{
    public function form(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);
        return $this->respondWith($request, 'pages.orders.progress-form', compact('order'));
    }

    public function save(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        $data = $request->validate([
            'progress_step' => 'required|in:1,2,3,4,5,6,7,8',
            'note'          => 'nullable|string',
            'logged_at'     => 'required|date',
            'attachment'    => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ]);

        $attachmentId = null;
        if ($request->hasFile('attachment')) {
            $file     = $request->file('attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path     = $file->storeAs('progress-logs', $filename, 'local');

            $attachment = \App\Models\Attachment::create([
                'type'        => 'progress_log',
                'file_path'   => 'storage/' . $path,
                'mime_type'   => $file->getClientMimeType(),
                'size'        => $file->getSize(),
                'uploaded_by' => $request->user()->id ?? null,
            ]);

            $attachmentId = $attachment->id;
        }

        OrderProgressLog::create([
            'order_id'      => $order->id,
            'progress_step' => $data['progress_step'],
            'note'          => $data['note'] ?? null,
            'logged_at'     => $data['logged_at'],
            'user_id'       => $request->user()->id,
            'attachment_id' => $attachmentId,
        ]);

        return $this->setJsonResponse('Progress log saved successfully');
    }

    public function logs($orderId)
    {
        $order = Order::with(['progressLogs.user', 'progressLogs.attachment'])->findOrFail($orderId);

        $stepLabels = [
            1 => 'Order Publish',
            2 => 'On Production',
            3 => 'Running Test',
            4 => 'Delivery to Indonesia',
            5 => 'Legal Confirmation',
            6 => 'Delivery to Customer Location',
            7 => 'Installation',
            8 => 'BAST',
        ];

        $logs = $order->progressLogs->map(function ($log) use ($stepLabels) {
            return [
                'logged_at' => $log->logged_at ? $log->logged_at->format('d M Y') : '-',
                'step'      => $log->progress_step,
                'step_label'=> $stepLabels[$log->progress_step] ?? '-',
                'note'      => $log->note,
                'user'      => $log->user->name ?? '-',
                'attachment'=> $log->attachment_id ? route('attachments.download', $log->attachment_id) : null,
            ];
        });

        return response()->json($logs);
    }
}