<?php

namespace App\Services\Aftersales;

use App\Http\Classes\ActivityLogger;
use App\Models\Aftersales\PartStockMovement;
use App\Models\Aftersales\RefSparepart;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketPart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketStock
{
    public static function add(Ticket $ticket, int $sparepartId, int $qty, string $unit, int $userId, bool $planned = false): TicketPart
    {
        return DB::transaction(function () use ($ticket, $sparepartId, $qty, $unit, $userId, $planned) {
            $part = TicketPart::where('ticket_id', $ticket->id)->where('type', TicketPart::TYPE_ESTIMATED)
                ->where('ref_sparepart_id', $sparepartId)->first();

            if ($part) {
                self::setUsed($part, (int) $part->qty + $qty, $userId);

                return $part;
            }

            $part = TicketPart::create([
                'ticket_id' => $ticket->id,
                'ref_sparepart_id' => $sparepartId,
                'qty' => 0,
                'planned_quantity' => $planned ? $qty : null,
                'unit' => $unit,
                'unit_price' => RefSparepart::findOrFail($sparepartId)->price,
                'type' => TicketPart::TYPE_ESTIMATED,
                'created_by' => $userId,
            ]);
            self::setUsed($part, $qty, $userId);

            return $part;
        });
    }

    public static function setUsed(TicketPart $part, int $qty, int $userId): void
    {
        DB::transaction(function () use ($part, $qty, $userId) {
            $part->setRawAttributes(TicketPart::whereKey($part->id)->lockForUpdate()->firstOrFail()->getAttributes(), true);
            $sparepart = RefSparepart::withTrashed()->whereKey($part->ref_sparepart_id)->lockForUpdate()->firstOrFail();
            $oldQty = (float) $part->qty;
            
            $delta = max(0, $qty - (int) $part->untracked_quantity) - self::balance(['ticket_part_id' => $part->id]);

            if ($delta > 0) {
                if ($sparepart->stock < $delta) {
                    throw ValidationException::withMessages([
                        'parts' => "Not enough stock: {$sparepart->name} requested {$delta}, available {$sparepart->stock}.",
                    ]);
                }
                self::move($part, $sparepart, PartStockMovement::TYPE_TICKET_OUT, $delta, $userId);
            } elseif ($delta < 0) {
                self::move($part, $sparepart, PartStockMovement::TYPE_TICKET_RETURN, -$delta, $userId);
            }

            $part->update(['qty' => $qty, 'updated_by' => $userId]);

            if ($oldQty != $qty) {
                ActivityLogger::writeLog('part_quantity_changed', "Sparepart {$sparepart->name} used qty {$oldQty} -> {$qty}", $part->ticket, [
                    'ticket_part_id' => $part->id,
                    'ref_sparepart_id' => $sparepart->id,
                    'from' => $oldQty,
                    'to' => $qty,
                ]);
            }
        });
    }

    public static function returnAll(Ticket $ticket, int $userId): int
    {
        $total = 0;
        TicketPart::where('ticket_id', $ticket->id)->orderBy('ref_sparepart_id')->get()->each(function (TicketPart $part) use ($userId, &$total) {
            $held = self::balance(['ticket_part_id' => $part->id]);
            if ($held > 0) {
                $sparepart = RefSparepart::withTrashed()->whereKey($part->ref_sparepart_id)->lockForUpdate()->firstOrFail();
                self::move($part, $sparepart, PartStockMovement::TYPE_TICKET_RETURN, $held, $userId);
                $total += $held;
            }
        });

        return $total;
    }

    public static function held(Ticket $ticket): int
    {
        return self::balance(['ticket_id' => $ticket->id]);
    }

    private static function balance(array $where): int
    {
        $rows = PartStockMovement::where($where)
            ->whereIn('type', [PartStockMovement::TYPE_TICKET_OUT, PartStockMovement::TYPE_TICKET_RETURN])
            ->selectRaw('type, SUM(qty) AS total')->groupBy('type')->pluck('total', 'type');

        return (int) ($rows[PartStockMovement::TYPE_TICKET_OUT] ?? 0) - (int) ($rows[PartStockMovement::TYPE_TICKET_RETURN] ?? 0);
    }

    private static function move(TicketPart $part, RefSparepart $sparepart, string $type, int $qty, int $userId): void
    {
        $sparepart->stock += $type === PartStockMovement::TYPE_TICKET_OUT ? -$qty : $qty;
        $sparepart->updated_by = $userId;
        $sparepart->save();

        PartStockMovement::create([
            'ref_sparepart_id' => $sparepart->id,
            'type' => $type,
            'qty' => $qty,
            'ticket_id' => $part->ticket_id,
            'ticket_part_id' => $part->id,
            'note' => ($type === PartStockMovement::TYPE_TICKET_OUT ? 'Used on ticket ' : 'Returned from ticket ') . $part->ticket?->ticket_code,
            'created_by' => $userId,
        ]);
    }
}
