<?php

class SiteSettings
{
    private static $cache;

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = [];
        $rows = Database::getInstance()->query("SELECT setting_key, setting_value FROM site_settings");
        foreach ($rows as $row) {
            self::$cache[$row['setting_key']] = $row['setting_value'];
        }
        return self::$cache;
    }

    public static function get(string $key, string $default = ''): string
    {
        $settings = self::all();
        return array_key_exists($key, $settings) ? (string) $settings[$key] : $default;
    }

    public static function updateMany(array $values, int $userId): void
    {
        $db = Database::getInstance();
        $db->transaction(function ($db) use ($values, $userId) {
            foreach ($values as $key => $value) {
                $db->execute(
                    "INSERT INTO site_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)",
                    [$key, $value, $userId]
                );
            }
        });
        self::$cache = null;
    }

    public static function refresh(): void
    {
        self::$cache = null;
    }
}
