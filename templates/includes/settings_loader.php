<?php
// The global $conn is available from public/index.php, no need to re-require db.php

$settings = [];
try {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Silently continue if settings table doesn't exist yet
}

$site_name = $settings['site_name'] ?? "Film Studio";
$studio_logo = $settings['studio_logo_url'] ?? "";
$maintenance_mode = ($settings['maintenance_mode'] ?? '0') === '1';