<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;

class DocumentController extends Controller
{
    // GET /api/documents/list
    public function list(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Superadmin bisa melihat semua dokumen
        if ($user->role && $user->role->code === 'super_admin') {
            $documents = Document::all();

            return response()->json([
                'message'    => 'Documents list (superadmin)',
                'user_grade' => $user->grade,
                'role'       => $user->role->code,
                'data'       => $documents,
            ]);
        }

        // Selain superadmin, hanya boleh lihat dokumen dengan grade yang sama
        $documents = Document::where('grade', $user->grade)->get();

        return response()->json([
            'message'    => 'Documents list by grade',
            'user_grade' => $user->grade,
            'role'       => optional($user->role)->code,
            'data'       => $documents,
        ]);
    }

    // POST /api/documents/save/{id?}
    public function save(Request $request, $id = null)
    {
        $user = $request->user();

        if (! $user || ! $user->role || $user->role->code !== 'super_admin') {
            return response()->json([
                'message' => 'Forbidden: only super_admin can save documents',
            ], 403);
        }

        $validated = $request->validate([
            'user_id'   => ['required', 'integer', 'exists:users,id'],
            'grade'     => ['nullable', 'string', 'max:100'],
            'judul'     => ['required', 'string'],
            'deskripsi' => ['nullable', 'string'],
            'file'      => ['required', 'file', 'mimes:pdf'],
        ]);

        // simpan file pdf ke storage/app/documents
        $uploadedFile = $request->file('file');
        $storedPath   = $uploadedFile->store('documents');

        $data = [
            'user_id'   => $validated['user_id'],
            'grade'     => $validated['grade'] ?? null,
            'judul'     => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'file'      => $storedPath,
        ];

        if ($id) {
            $document = Document::findOrFail($id);
            $document->update($data);
            $isNew = false;
        } else {
            $document = Document::create($data);
            $isNew = true;
        }

        return response()->json([
            'message' => $isNew ? 'Document created' : 'Document updated',
            'data'    => $document,
        ]);
    }

    // GET /api/documents/form/{id?}
    public function form($id = null)
    {
        if ($id) {
            $document = Document::findOrFail($id);
        } else {
            $document = null;
        }

        return response()->json([
            'message' => $id ? 'Document detail' : 'Document form',
            'data'    => $document,
        ]);
    }

    // GET /api/documents/download/{id}
    public function download($id)
    {
        $document = Document::findOrFail($id);

        if (! $document->file || ! Storage::exists($document->file)) {
            return response()->json([
                'message' => 'File not found',
            ], 404);
        }

        // Optional: gunakan judul sebagai nama file download
        $filename = ($document->judul ?: 'document') . '.pdf';

        return Storage::download($document->file, $filename);
    }

    // DELETE /api/documents/delete/{id}
    public function delete($id)
    {
        $user = auth()->user();

        if (! $user || ! $user->role || $user->role->code !== 'super_admin') {
            return response()->json([
                'message' => 'Forbidden: only super_admin can delete documents',
            ], 403);
        }

        $document = Document::findOrFail($id);

        // Hapus file fisik kalau ada
        if ($document->file && Storage::exists($document->file)) {
            Storage::delete($document->file);
        }

        // Soft delete (isi kolom deleted_at)
        $document->delete();

        return response()->json([
            'message' => 'Document deleted',
            'id'      => $id,
        ]);
    }
}
