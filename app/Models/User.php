<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserPermission;
use App\Models\UserRolePermission;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'company_id',
        'branch_id',
        'name',
        'email',
        'nip',
        'phone',
        'password',
        'grade',
        'target',
        'target_visit',
        'target_leads',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Ambil breakdown target bulanan dari field target (format: total|json).
     */
    public function getMonthlyTargetsAttribute(): array
    {
        $raw = $this->attributes['target'] ?? null;

        if (! $raw || ! is_string($raw)) {
            return [];
        }

        if (! str_contains($raw, '|')) {
            return [];
        }

        [, $json] = explode('|', $raw, 2);

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Ambil total target tahunan dari field target (bagian sebelum '|').
     */
    public function getTargetTotalAttribute(): float
    {
        $raw = $this->attributes['target'] ?? null;

        if (! $raw || ! is_string($raw)) {
            return 0.0;
        }

        $parts = explode('|', $raw, 2);

        return (float) ($parts[0] ?? 0);
    }

    /**
     * Ambil breakdown target leads bulanan dari field target_leads (format: total|json).
     */
    public function getMonthlyLeadsTargetsAttribute(): array
    {
        $raw = $this->attributes['target_leads'] ?? null;

        if (! $raw || ! is_string($raw)) {
            return [];
        }

        if (! str_contains($raw, '|')) {
            return [];
        }

        [, $json] = explode('|', $raw, 2);

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Ambil total target leads tahunan dari field target_leads (bagian sebelum '|').
     */
    public function getTargetLeadsTotalAttribute(): float
    {
        $raw = $this->attributes['target_leads'] ?? null;

        if (! $raw || ! is_string($raw)) {
            return 0.0;
        }

        $parts = explode('|', $raw, 2);

        return (float) ($parts[0] ?? 0);
    }

    /**
     * Ambil breakdown target visit bulanan dari field target_visit (format: total|json).
     */
    public function getMonthlyVisitTargetsAttribute(): array
    {
        $raw = $this->attributes['target_visit'] ?? null;

        if (! $raw || ! is_string($raw)) {
            return [];
        }

        if (! str_contains($raw, '|')) {
            return [];
        }

        [, $json] = explode('|', $raw, 2);

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Ambil total target visit tahunan dari field target_visit (bagian sebelum '|').
     */
    public function getTargetVisitTotalAttribute(): float
    {
        $raw = $this->attributes['target_visit'] ?? null;

        if (! $raw || ! is_string($raw)) {
            return 0.0;
        }

        $parts = explode('|', $raw, 2);

        return (float) ($parts[0] ?? 0);
    }

    public function role()
    {
        return $this->belongsTo(UserRole::class, 'role_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Masters\Branch::class, 'branch_id');
    }


    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function permissions()
    {
        return $this->hasManyThrough(
            UserPermission::class,
            UserRolePermission::class,
            'role_id',
            'id',
            'role_id',
            'permission_id'
        );
    }

    public function hasPermission(string $code): bool
    {
        return $this->permissions()->where('code', $code)->exists();
    }
}
