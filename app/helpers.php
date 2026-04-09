<?php

use App\Models\{User, UserRole, UserPermission};

if (! function_exists('hasRole')) {
    function hasRole(int|User $user, string $roleCode): bool
    {
        if (! $user instanceof User) {
            $user = User::find($user);
        }

        return $user?->role?->code === $roleCode;
    }
}

if (! function_exists('hasPermission')) {
    function hasPermission(int|User $user, string $permissionCode): bool
    {
        if (! $user instanceof User) {
            $user = User::find($user);
        }

        if (! $user) {
            return false;
        }

        return $user->permissions()->where('code', $permissionCode)->exists();
    }
}

if (! function_exists('format_needs_label')) {
    /**
     * Normalize a raw product/needs label to a compact needs value.
     * Examples: "Tube Ice Machine | Compressor-Bitzer" -> "Tube Ice"
     */
    function format_needs_label(?string $raw): ?string
    {
        if ($raw === null) return null;

        $s = trim($raw);
        if ($s === '') return null;

        $lower = strtolower($s);

        if (str_contains($lower, 'tube')) {
            return 'Tube Ice';
        }

        if (str_contains($lower, 'cube')) {
            return 'Cube Ice';
        }

        // Fallback: strip parenthetical explanations and pipe-suffixes
        // e.g. "Something ( Mesin Es Kristal Tabung )" -> "Something"
        $s = preg_replace('/\s*\(.*\)\s*/', '', $s);
        $parts = preg_split('/\||-/', $s);
        return trim($parts[0] ?? $s) ?: null;
    }
}
