<?php

namespace App\Http\Controllers\AfterSales\Survey;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\TicketPhotoToken;
use App\Services\Aftersales\TicketDocuments;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InspectionPhotoController extends Controller
{
    public function show(string $token)
    {
        $link = $this->findLink($token);
        $value = $link->value()->with('field', 'inspection.item', 'photo')->first();

        return response()->json(['status' => 'success', 'data' => [
            'ticket_code' => $link->ticket->ticket_code,
            'item' => ['number' => $value->inspection->item->number, 'name' => $value->inspection->item->name],
            'field' => ['key' => $value->field->key, 'label' => $value->field->label, 'unit' => $value->field->unit],
            'value' => $value->value,
            'has_photo' => (bool) $value->photo,
            'expires_at' => $link->expires_at,
        ]]);
    }

    public function upload(Request $request, string $token)
    {
        $request->validate(['photo' => 'required|file|image|max:10240']);

        $link = $this->findLink($token);
        $value = $link->value()->with('inspection.form')->first();
        abort_if($value->inspection->form->isLocked(), 422, 'The Service Form is saved and locked.');

        $photo = TicketDocuments::attachPhoto($value, $request->file('photo'), $link->ticket, $link->created_by_user_id);
        $link->update(['used_at' => Carbon::now('Asia/Jakarta')]);

        return response()->json(['status' => 'success', 'message' => 'Photo sent to the form', 'data' => [
            'photo_id' => $photo->id,
            'captured_at' => $photo->captured_at,
        ]], 201);
    }

    private function findLink(string $token): TicketPhotoToken
    {
        $link = TicketPhotoToken::with('ticket')->where('token_hash', hash('sha256', $token))->firstOrFail();
        abort_if($link->expires_at->lt(Carbon::now('Asia/Jakarta')), 410, 'This camera link has expired. Create a new QR code.');

        return $link;
    }
}
