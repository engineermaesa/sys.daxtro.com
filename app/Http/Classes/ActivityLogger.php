<?php

namespace App\Http\Classes;

use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Write a log entry to the UserActivityLog model.
     *
     * @param string $action The action performed by the user.
     * @param string|null $description A description of the action.
     * @param mixed|null $model The model associated with the action.
     * @param array $data Additional data related to the action.
     * @param mixed|null $user The user who performed the action. Defaults to the currently authenticated user.
     *
     * @return void
     */
    public static function writeLog($action, $description = null, $model = null, array $data = [], $user = null)
    {
        UserActivityLog::create([
            'user_id'     => $user?->id ?? Auth::id(),
            'action'      => $action,
            'model_type'  => $model ? get_class($model) : null,
            'model_id'    => $model?->id,
            'description' => $description,
            'data'        => !empty($data) ? $data : null,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }

}