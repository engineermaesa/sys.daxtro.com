<?php

namespace App\Http\Controllers\AfterSales\Dats;

use App\Http\Controllers\Controller;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketPhoto;
use App\Services\Aftersales\ServiceDiagnosis;
use App\Services\Aftersales\TicketDocuments;
use Illuminate\Http\Request;

class ServiceDiagnosisController extends Controller
{
    public function upload(Request $request, Ticket $ticket, ServiceDiagnosis $service)
    {
        $form = TicketDocuments::document($ticket, TicketDocuments::TYPE_FORM);
        $service->authorizeEdit($ticket, $form, $request->user());
        $data = $request->validate(['field' => 'required|in:problem,cause,root_cause,action', 'caption' => 'nullable|string|max:500', 'photo' => 'required|file|max:10240|mimetypes:image/jpeg,image/png,image/heic,image/heif,image/heic-sequence,image/heif-sequence']);
        $service->upload($ticket, $form, $data['field'], $request->file('photo'), $data['caption'] ?? null, $request->user());

        return response()->json(['data' => $service->structure($ticket, $form)], 201);
    }

    public function update(Request $request, Ticket $ticket, TicketPhoto $photo, ServiceDiagnosis $service)
    {
        $data = $request->validate(['caption' => 'nullable|string|max:500']);
        $form = TicketDocuments::document($ticket, TicketDocuments::TYPE_FORM);
        $service->changePhoto($ticket, $form, $photo, $request->user(), $data['caption'] ?? null);

        return response()->json(['data' => $service->structure($ticket, $form)]);
    }

    public function destroy(Request $request, Ticket $ticket, TicketPhoto $photo, ServiceDiagnosis $service)
    {
        $form = TicketDocuments::document($ticket, TicketDocuments::TYPE_FORM);
        $service->changePhoto($ticket, $form, $photo, $request->user(), null, true);

        return response()->json(['data' => $service->structure($ticket, $form)]);
    }
}
