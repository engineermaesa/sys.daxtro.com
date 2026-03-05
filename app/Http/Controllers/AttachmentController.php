<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{

    public function download($id)
    {
        $attachment = Attachment::findOrFail($id);

        if (empty($attachment->file_path)) {
            return response()->json(['message' => 'File path not set in attachment record.'], 400);
        }

        $filePath = str_replace('\\', '/', $attachment->file_path);
        $filePath = ltrim($filePath, '/');

        $candidatePaths = [];

        // Normalise common "storage/" prefix used in DB
        if (str_starts_with($filePath, 'storage/')) {
            $relativePath = ltrim(substr($filePath, strlen('storage/')), '/');

            foreach (['public', 'local'] as $disk) {
                try {
                    $candidatePaths[] = Storage::disk($disk)->path($relativePath);
                } catch (\Throwable $e) {
                }
            }
        } else {
            foreach (['public', 'local'] as $disk) {
                try {
                    $candidatePaths[] = Storage::disk($disk)->path($filePath);
                } catch (\Throwable $e) {
                }
            }
        }

        // Remove duplicates
        $candidatePaths = array_values(array_unique($candidatePaths));

        // Try each possible path
        foreach ($candidatePaths as $path) {
            if (is_file($path)) {
                return response()->download($path, basename($path));
            }
        }

        // Fallback for old proforma files that may have inconsistent paths
        if (str_contains($filePath, 'PROFORMA_')) {
            $fileName = basename($filePath);

            foreach (['public', 'local'] as $disk) {
                try {
                    $fixedPath = Storage::disk($disk)->path('proformas/' . $fileName);
                } catch (\Throwable $e) {
                    continue;
                }

                if (is_file($fixedPath)) {
                    return response()->download($fixedPath, $fileName);
                }
            }
        }

        return response()->json([
            'message' => 'File not found',
            'path' => $filePath,
            'tried_paths' => $candidatePaths,
        ], 404);
    }
}
