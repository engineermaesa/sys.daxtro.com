<?php

namespace App\Http\Controllers\AfterSales\Survey;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\TicketPhotoToken;
use App\Services\Aftersales\ServiceFormImage;
use App\Services\Aftersales\TicketDocuments;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InspectionPhotoController extends Controller
{
    public function show(string $token)
    {
        $link = $this->findLink($token);
        $value = $link->value()->with('field', 'inspection.item', 'photos')->firstOrFail();

        return response()->json(['status' => 'success', 'data' => [
            'ticket_code' => $link->ticket->ticket_code,
            'item' => ['number' => $value->inspection->item->number, 'name' => $value->inspection->item->name],
            'field' => ['key' => $value->field->key, 'label' => $value->field->label, 'unit' => $value->field->unit],
            'value' => $value->value,
            'has_photo' => $value->photos->isNotEmpty(),
            'photo_count' => $value->photos->count(),
            'latest_photo_id' => $value->photos->last()?->id,
            'expires_at' => $link->expires_at,
        ]]);
    }

    public function upload(Request $request, string $token)
    {
        $data = $request->validate(ServiceFormImage::rules() + ['source' => 'sometimes|in:camera,gallery']);

        $link = $this->findLink($token);
        $value = $link->value()->firstOrFail();
        $capturedAt = isset($data['captured_at'])
            ? Carbon::parse($data['captured_at'], 'Asia/Jakarta')->timezone('Asia/Jakarta')
            : (($data['source'] ?? 'camera') === 'camera' ? Carbon::now('Asia/Jakarta') : null);
        $photo = TicketDocuments::attachPhoto($value, $data['photo'], $link->ticket, $link->created_by_user_id, $capturedAt, $data['caption'] ?? null);
        $link->update(['used_at' => Carbon::now('Asia/Jakarta')]);

        return response()->json(['status' => 'success', 'message' => 'Photo sent to the form', 'data' => [
            'photo_id' => $photo->id,
            'captured_at' => $photo->captured_at,
            'caption' => $photo->caption,
        ]], 201);
    }

    private function findLink(string $token): TicketPhotoToken
    {
        $link = TicketPhotoToken::with('ticket')->where('token_hash', hash('sha256', $token))->firstOrFail();
        abort_if($link->expires_at->lt(Carbon::now('Asia/Jakarta')), 410, 'This camera link has expired. Create a new QR code.');
        abort_unless($link->created_by_user_id && $link->ticket?->hasTechnician((int) $link->created_by_user_id), 403,
            'This camera link must belong to an assigned technician.');

        return $link;
    }
}
