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

        // Simplify path handling
        $filePath = $attachment->file_path;
        
        // Check both with and without storage/ prefix
        $publicPaths = [
            storage_path('app/public/' . $filePath),
            storage_path('app/public/storage/' . $filePath),
            storage_path('app/public/' . ltrim($filePath, 'storage/'))
        ];
        
        // Try each possible path
        foreach ($publicPaths as $path) {
            if (file_exists($path)) {
                return response()->download($path, basename($filePath));
            }
        }

        // For existing proformas that may have been saved with incorrect paths
        if (str_contains($filePath, 'PROFORMA_')) {
            $fileName = basename($filePath);
            $fixedPath = storage_path('app/public/proformas/' . $fileName);
            
            if (file_exists($fixedPath)) {
                return response()->download($fixedPath, $fileName);
            }
        }

        return response()->json([
            'message' => 'File not found',
            'path' => $filePath,
            'tried_paths' => $publicPaths
        ], 404);
    }
}
