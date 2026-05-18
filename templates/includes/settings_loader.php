<?php

// Prevent direct access
if (!defined('ROOT_PATH')) {
    exit('No direct script access allowed');
}

// Ensure DB is available from front controller
if (!isset($pdo) || !($pdo instanceof PDO)) {
    require_once ROOT_PATH . 'config/db.php';
}

/**
 * Default system settings fallback
 */
$settings = [
    'site_name' => 'Film Studio',
    'maintenance_mode' => false,
    'studio_logo' => null
];

try {

    // Check if settings table exists before querying (prevents fatal crash)
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'settings'");
    if ($tableCheck && $tableCheck->rowCount() > 0) {

        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $key = $row['setting_key'] ?? null;
            $value = $row['setting_value'] ?? null;

            if ($key !== null) {
                $settings[$key] = $value;
            }
        }
    }

} catch (PDOException $e) {
    error_log("Settings loader failed: " . $e->getMessage());
    // Keep safe defaults, do not crash system
}

// Expose clean variables for legacy templates
$site_name = $settings['site_name'] ?? 'Film Studio';
$maintenance_mode = (bool)($settings['maintenance_mode'] ?? false);
$studio_logo = $settings['studio_logo'] ?? null;