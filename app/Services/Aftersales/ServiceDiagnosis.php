<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketDiagnosisEntry;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketPhoto;
use App\Models\Aftersales\TicketServiceForm;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceDiagnosis
{
    public const FIELDS = ['problem', 'cause', 'action'];

    public function authorizeEdit(Ticket $ticket, TicketServiceForm $form, User $user): void
    {
        abort_unless(TicketDocuments::isTechnician($user, $ticket), 403, 'Only the assigned technician can edit diagnosis.');
        abort_if($form->isLocked(), 422, 'The Service Form is saved and locked.');
        abort_if($ticket->progress === TicketLog::STEP_CLOSED, 422, 'Diagnosis cannot be edited in this ticket status.');
    }

    public function structure(Ticket $ticket, TicketServiceForm $form): array
    {
        $entries = $form->diagnosisEntries()->get();
        $photos = $form->diagnosisPhotos()->get();
        $currentVisitNo = max(1, $ticket->visits()->count());
        $visit = $ticket->visits()->orderByDesc('scheduled_at')->first();
        $date = ($visit?->actual_start ?? $visit?->scheduled_at ?? now('Asia/Jakarta'))->copy()->timezone('Asia/Jakarta');

        return ['title' => $form->template->diagnosis_section_title,
            'photos_required' => ! TicketDocumentWorkflow::enabled($ticket) || TicketRecomings::current($ticket)?->recoming_type !== \App\Models\Aftersales\TicketRecoming::TYPE_PHONE_CALL,
            'legacy' => (bool) $form->legacy_without_diagnosis && $form->isLocked() && $entries->isEmpty(),
            'fields' => collect(self::FIELDS)->map(fn ($field) => [
                'key' => $field, 'label' => $form->template->diagnosis_fields[$field]['label'], 'hint' => $form->template->diagnosis_fields[$field]['hint'],
                'visit_date' => $date->toDateString(), 'visit_time' => $date->format('H:i'),
                'entries' => $entries->where('field', $field)->values()->map(fn ($e) => ['id' => $e->id, 'name' => $e->name, 'body' => $e->body, 'visit_notes' => $e->visit_notes, 'visit_no' => $e->visit_no, 'entry_date' => $e->entry_date->toDateString(), 'visit_time' => substr($e->visit_time, 0, 5), 'locked' => (bool) $e->locked_at || $e->visit_no < $currentVisitNo])->all(),
                'photos' => $photos->where('diagnosis_field', $field)->values()->map(fn ($p) => ['id' => $p->id, 'caption' => $p->caption, 'locked' => (bool) $p->locked_at, 'url' => route('aftersales.tickets.service-form.photos.show', [$ticket->id, $p->id], false)])->all(),
            ])->all()];
    }

    public function save(Ticket $ticket, TicketServiceForm $form, array $entries, User $user): void
    {
        DB::transaction(function () use ($ticket, $form, $entries, $user) {
            $form = TicketServiceForm::whereKey($form->id)->lockForUpdate()->firstOrFail();
            $this->authorizeEdit($ticket, $form, $user);
            $visit = $ticket->visits()->orderByDesc('scheduled_at')->first();
            $date = ($visit?->actual_start ?? $visit?->scheduled_at ?? now('Asia/Jakarta'))->copy()->timezone('Asia/Jakarta');
            $visitNo = max(1, $ticket->visits()->count(), (int) $form->diagnosisEntries()->whereNotNull('locked_at')->max('visit_no') + 1);
            $form->diagnosisEntries()->where('visit_no', '<', $visitNo)->whereNull('locked_at')->update(['locked_at' => now()]);
            foreach ($entries as $input) {
                $entry = isset($input['id']) ? $form->diagnosisEntries()->where('field', $input['field'])->findOrFail($input['id']) : $form->diagnosisEntries()->where('field', $input['field'])->where('visit_no', $visitNo)->whereNull('locked_at')->first();
                abort_if($entry?->locked_at, 409, 'Previously saved diagnosis notes are read-only. Add a new visit note.');
                if (! $entry) {
                    $entry = new TicketDiagnosisEntry(['ticket_service_form_id' => $form->id, 'field' => $input['field'], 'visit_no' => $visitNo, 'entry_date' => $date->toDateString(), 'visit_time' => $date->format('H:i:s'), 'created_by_user_id' => $user->id]);
                }
                $entry->fill(['name' => $input['name'] ?? null, 'body' => $input['body'] ?? null, 'visit_notes' => $input['visit_notes'] ?? null])->save();
            }
        }, 3);
    }

    public function missing(TicketServiceForm $form, bool $requirePhotos = true): array
    {
        if ($form->legacy_without_diagnosis && $form->isLocked()) {
            return [];
        }
        $missing = [];
        foreach (self::FIELDS as $field) {
            $entries = $form->diagnosisEntries()->where('field', $field)->get();
            if (! $entries->contains(fn ($e) => filled($e->body))) {
                $missing[] = "$field diagnosis text is required";
            }
            if ($entries->contains(fn ($e) => ! $e->locked_at && blank($e->body))) {
                $missing[] = "$field new visit note must contain diagnosis text";
            }
            if ($requirePhotos) {
                $count = $form->diagnosisPhotos()->where('diagnosis_field', $field)->count();
                if ($count < 1 || $count > 5) {
                    $missing[] = "$field requires 1–5 diagnosis photos";
                }
            }
        }

        return $missing;
    }

    public function lock(TicketServiceForm $form): void
    {
        $form->diagnosisEntries()->whereNull('locked_at')->update(['locked_at' => now()]);
        $form->diagnosisPhotos()->whereNull('locked_at')->update(['locked_at' => now()]);
    }

    public function upload(Ticket $ticket, TicketServiceForm $form, string $field, UploadedFile $file, ?string $caption, User $user): TicketPhoto
    {
        $bytes = $this->jpeg($file);
        $path = 'diagnosis/'.$ticket->id.'/'.Str::uuid().'.jpg';
        abort_unless(Storage::disk('local')->put($path, $bytes), 500, 'Could not store the photo. Retry the upload.');
        try {
            return DB::transaction(function () use ($ticket, $form, $field, $caption, $user, $path, $file) {
                $form = TicketServiceForm::whereKey($form->id)->lockForUpdate()->firstOrFail();
                $this->authorizeEdit($ticket, $form, $user);
                abort_if($form->diagnosisPhotos()->where('diagnosis_field', $field)->count() >= 5, 422, 'This field already has five photos.');

                return TicketPhoto::create(['ticket_id' => $ticket->id, 'ticket_service_form_id' => $form->id, 'diagnosis_field' => $field, 'category' => 'diagnosis', 'sequence' => (int) $form->diagnosisPhotos()->max('sequence') + 1, 'caption' => $caption, 'file_path' => $path, 'file_name' => $file->getClientOriginalName(), 'captured_at' => now()]);
            }, 3);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
    }

    public function changePhoto(Ticket $ticket, TicketServiceForm $form, TicketPhoto $photo, User $user, ?string $caption, bool $delete = false): void
    {
        DB::transaction(function () use ($ticket, $form, $photo, $user, $caption, $delete) {
            $form = TicketServiceForm::whereKey($form->id)->lockForUpdate()->firstOrFail();
            $this->authorizeEdit($ticket, $form, $user);
            $photo = $form->diagnosisPhotos()->findOrFail($photo->id);
            abort_if($photo->locked_at, 409, 'Previously saved diagnosis photos are read-only.');
            if ($delete) {
                $photo->delete();
            } else {
                $photo->update(['caption' => $caption]);
            }
        }, 3);
    }

    private function jpeg(UploadedFile $file): string
    {
        $mime = $file->getMimeType();
        if (in_array($mime, ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'], true)) {
            abort_unless(extension_loaded('imagick'), 422, 'HEIC conversion is unavailable on this server. Export this photo as JPG or PNG and retry.');
            $im = new \Imagick($file->getRealPath());
            $im->setIteratorIndex(0);
            if (method_exists($im, 'autoOrientImage')) {
                $im->autoOrientImage();
            } $im->thumbnailImage(1600, 1600, true);
            $im->setImageFormat('jpeg');
            $im->setImageCompressionQuality(82);

            return $im->getImageBlob();
        }
        $size = @getimagesize($file->getRealPath());
        abort_unless($size && $size[0] * $size[1] <= 50000000, 422, 'Photo dimensions are too large or invalid.');
        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        abort_unless($source, 422, 'This photo could not be decoded.');
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $orientation = (@exif_read_data($file->getRealPath())['Orientation']) ?? 1;
            if (in_array($orientation, [2, 5, 7], true)) {
                imageflip($source, IMG_FLIP_HORIZONTAL);
            }
            if ($orientation === 4) {
                imageflip($source, IMG_FLIP_VERTICAL);
            }
            $rotation = match ($orientation) {
                3 => 180, 5, 8 => 90, 6, 7 => -90, default => 0
            };
            if ($rotation) {
                $source = imagerotate($source, $rotation, 0);
            }
        }
        $ratio = min(1, 1600 / max(imagesx($source), imagesy($source)));
        $image = imagecreatetruecolor(max(1, (int) (imagesx($source) * $ratio)), max(1, (int) (imagesy($source) * $ratio)));
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagecopyresampled($image, $source, 0, 0, 0, 0, imagesx($image), imagesy($image), imagesx($source), imagesy($source));
        ob_start();
        imagejpeg($image, null, 82);
        $bytes = ob_get_clean();
        imagedestroy($source);
        imagedestroy($image);

        return $bytes;
    }
}
