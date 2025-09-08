<?php

namespace App\Http\Controllers;

use App\Models\Attachment;

class AttachmentController extends Controller
{
    public function download($id)
    {
        $attachment = Attachment::findOrFail($id);

        if (empty($attachment->file_path)) {
            return response()->json(['message' => 'File path not set in attachment record.'], 400);
        }

        $relative = str_starts_with($attachment->file_path, 'storage/')
        ? substr($attachment->file_path, 8)
        : $attachment->file_path;
        
        if (empty($relative)) {
            return response()->json(['message' => 'Relative file path is empty.'], 400);
        }

        $publicPath  = storage_path('app/public/' . $relative);
        $privatePath = storage_path('app/private/' . $relative);

        if (!is_dir(dirname($publicPath)) && !is_dir(dirname($privatePath))) {
            return response()->json(['message' => 'Folder not found.'], 404);
        }

        if (file_exists($publicPath)) {
            return response()->download($publicPath, basename($relative));
        }

        if (file_exists($privatePath)) {
            return response()->download($privatePath, basename($relative));
        }

        return response()->json(['message' => 'File not found.'], 404);
    }
}
