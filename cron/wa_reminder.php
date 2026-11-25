<?php
declare(strict_types=1);

/**
 * Cron Job for Automated WhatsApp Billing Reminders (H-3 and H-0 Due Date).
 * CLI Usage:   php cron/wa_reminder.php
 * HTTP Usage:  GET /cron/wa_reminder.php?key=edupresensi-cron-2026
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/core/Security.php';
require_once dirname(__DIR__) . '/core/Tenant.php';
require_once dirname(__DIR__) . '/core/WaGateway.php';

$saas = Tenant::saasSettings(true);

if (PHP_SAPI !== 'cli') {
    $providedKey = $_GET['key'] ?? '';
    if ($providedKey === '' || !hash_equals((string)$saas['cron_secret'], (string)$providedKey)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized cron key.']);
        exit;
    }
}

$result = WaGateway::runDueReminders();

if (PHP_SAPI === 'cli') {
    echo '[' . date('Y-m-d H:i:s') . '] WA Reminder executed: ' . json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
}
