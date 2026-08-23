<?php

namespace App\Support;

class TenantContext
{
    protected static ?int $currentId = null;

    public static function set(?int $tenantId): void
    {
        static::$currentId = $tenantId;
    }

    public static function id(): ?int
    {
        return static::$currentId;
    }

    public static function clear(): void
    {
        static::$currentId = null;
    }
}
