<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentCounter extends Model
{
    public const TYPE_TICKET = 'ticket';
    public const TYPE_WORK_ORDER = 'work_order';

    public $timestamps = true;

    protected $fillable = [
        'type',
        'date',
        'last_number',
    ];

    /**
     * Atomically claim the next sequence number for the given document type
     * and date, using a row lock — not COUNT() — so concurrent creation never
     * collides and soft-deleted rows never shift numbering. See PRD §4.
     */
    public static function nextNumber(string $type, ?Carbon $date = null): int
    {
        $date = ($date ?? Carbon::now('Asia/Jakarta'))->toDateString();

        return DB::transaction(function () use ($type, $date) {
            $counter = self::query()
                ->where('type', $type)
                ->where('date', $date)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = self::create([
                    'type' => $type,
                    'date' => $date,
                    'last_number' => 0,
                ]);

                $counter = self::query()
                    ->where('id', $counter->id)
                    ->lockForUpdate()
                    ->first();
            }

            $counter->last_number += 1;
            $counter->save();

            return $counter->last_number;
        });
    }

    /**
     * Read-only preview of the number `nextNumber()` would hand out next, for
     * display purposes (e.g. showing the upcoming ticket code before saving).
     * Not reserved — a concurrent save can still claim the same number first.
     */
    public static function peekNextNumber(string $type, ?Carbon $date = null): int
    {
        $date = ($date ?? Carbon::now('Asia/Jakarta'))->toDateString();

        $counter = self::query()
            ->where('type', $type)
            ->where('date', $date)
            ->first();

        return ($counter->last_number ?? 0) + 1;
    }
}
