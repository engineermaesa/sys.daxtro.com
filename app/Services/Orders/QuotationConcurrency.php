<?php

namespace App\Services\Orders;

use App\Models\Orders\TempQuotation;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class QuotationConcurrency
{
    public const MESSAGE = 'The quotation changed or another action is in progress. Reload and try again.';

    public static function transaction(Closure $callback, int $attempts = 1, ?Connection $connection = null, ?Closure $onFailure = null): mixed
    {
        try {
            return ($connection ?? DB::connection())->transaction($callback, $attempts);
        } catch (Throwable $exception) {
            if ($onFailure) {
                try {
                    $onFailure();
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }
            abort_if(self::isConflict($exception), 409, self::MESSAGE);
            throw $exception;
        }
    }

    public static function isConflict(Throwable $exception): bool
    {
        if ($exception instanceof \Illuminate\Database\DeadlockException) {
            return true;
        }
        if (!$exception instanceof QueryException) {
            return $exception->getPrevious() !== null && self::isConflict($exception->getPrevious());
        }
        $state = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $code = (int) ($exception->errorInfo[1] ?? 0);
        $message = strtolower($exception->getMessage());
        return in_array($state, ['40001', '40P01', '55P03'], true)
            || in_array($code, [1205, 1213, 3572], true)
            || str_contains($message, 'database is locked')
            || str_contains($message, 'database table is locked');
    }

    public static function lockLead(int $leadId, ?Connection $connection = null): void
    {
        abort_unless(($connection ?? DB::connection())->table('leads')->where('id', $leadId)->lockForUpdate()->first(), 404);
    }

    public static function checkDraft(TempQuotation $draft, ?TempQuotation $observed, Request $request): void
    {
        $expected = $request->filled('draft_version') ? (int) $request->input('draft_version') : ($observed?->lock_version ?? 0);
        abort_if(!$observed || $draft->id !== $observed->id || ($draft->lock_version ?? 0) !== $expected,
            409, 'This draft has changed. Reload it before saving, submitting, or cancelling.');
    }

    public static function afterCommit(Closure $callback): void
    {
        DB::afterCommit(function () use ($callback) {
            // A delivery failure must not turn an already committed mutation into a failed response.
            try {
                $callback();
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }
}
