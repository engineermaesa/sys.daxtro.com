<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\CustomerImport;
use App\Models\User;

class ImportAccess
{
    public static function authorize(?User $user, string $type, ?CustomerImport $batch = null): void
    {
        $permission = match ($type) {
            CustomerImport::TYPE_CUSTOMER => 'masters.service-customers',
            CustomerImport::TYPE_SPAREPART => 'masters.spareparts',
            default => null,
        };
        abort_unless($permission && $user?->hasPermission($permission), 403);
        if ($batch) {
            $batch->assertType($type);
            self::assertOwner($batch, (int) $user->id);
        }
    }

    public static function assertOwner(CustomerImport $batch, int $userId): void
    {
        abort_unless((int) $batch->created_by_user_id === $userId, 403, 'Only the uploader can access this import.');
    }
}
