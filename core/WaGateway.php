<?php
declare(strict_types=1);

/**
 * Production Live WhatsApp Gateway Client (ZERO simulation mode).
 * Supports real HTTP API calls to:
 * - Fonnte        (https://api.fonnte.com/send)
 * - Wablas        (https://{server}.wablas.com/api/send-message)
 * - Starsender    (https://api.starsender.online/api/send)
 * - MPWA / Baileys(https://{domain}/send-message)
 * - WAHA          (https://{domain}/api/sendText)
 * - WhaCenter     (https://app.whacenter.com/api/send)
 * - Generic REST  (Custom JSON Webhook)
 */
class WaGateway
{
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }
        return $digits;
    }

    public static function renderTemplate(string $template, array $vars): string
    {
        $replacements = [];
        foreach ($vars as $k => $v) {
            $replacements['{' . $k . '}'] = (string)$v;
        }
        return strtr($template, $replacements);
    }

    public static function isConfigured(?array $saas = null): array
    {
        $saas     = $saas ?? Tenant::saasSettings(true);
        $enabled  = (int)($saas['wa_enabled'] ?? 0) === 1;
        $apiUrl   = trim((string)($saas['wa_api_url'] ?? ''));
        $apiToken = trim((string)($saas['wa_api_token'] ?? ''));

        if (!function_exists('curl_init')) {
            return ['ready' => false, 'reason' => 'Ekstensi PHP cURL tidak aktif di server. Aktifkan extension=curl di php.ini.'];
        }
        if (!$enabled) {
            return ['ready' => false, 'reason' => 'WA Gateway belum diaktifkan. Centang "Aktifkan Live WA Gateway" lalu simpan konfigurasi.'];
        }
        if ($apiUrl === '' || !filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            return ['ready' => false, 'reason' => 'API Endpoint URL WA Gateway belum diisi atau format URL tidak valid.'];
        }
        if ($apiToken === '') {
            return ['ready' => false, 'reason' => 'API Token / Key WA Gateway masih kosong. Masukkan token asli dari provider WA Anda.'];
        }
        return ['ready' => true, 'reason' => ''];
    }

    /**
     * Send a real WhatsApp message through the configured live WA Gateway API.
     * Never simulates success — returns failure if gateway is inactive, unconfigured,
     * unreachable, or rejected by the provider JSON response.
     */
    public static function send(?int $schoolId, string $phone, string $message, string $type = 'manual'): array
    {
        $saas  = Tenant::saasSettings(true);
        $clean = self::normalizePhone($phone);
        if ($clean === '' || strlen($clean) < 10) {
            return ['ok' => false, 'status' => 'failed', 'response' => 'Nomor WhatsApp tujuan tidak valid (' . $phone . ').'];
        }

        $cfgCheck = self::isConfigured($saas);
        if (!$cfgCheck['ready']) {
            self::logAttempt($schoolId, $clean, $type, $message, 'failed', $cfgCheck['reason']);
            return ['ok' => false, 'status' => 'failed', 'response' => $cfgCheck['reason']];
        }

        $apiUrl   = trim((string)$saas['wa_api_url']);
        $apiToken = trim((string)$saas['wa_api_token']);
        $sender   = self::normalizePhone((string)($saas['wa_sender'] ?? ''));
        $provider = strtolower(trim((string)($saas['wa_provider'] ?? 'fonnte')));

        $ch = curl_init($apiUrl);
        $headers = [];
        $postFields = null;

        if ($provider === 'fonnte') {
            $headers = ['Authorization: ' . $apiToken];
            $postFields = [
                'target'      => $clean,
                'message'     => $message,
                'countryCode' => '62',
            ];
        } elseif ($provider === 'wablas') {
            $headers = [
                'Authorization: ' . $apiToken,
                'Content-Type: application/json',
            ];
            $postFields = json_encode([
                'phone'   => $clean,
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE);
        } elseif ($provider === 'starsender') {
            $headers = [
                'Authorization: ' . $apiToken,
                'Content-Type: application/json',
            ];
            $postFields = json_encode([
                'messageType' => 'text',
                'to'          => $clean,
                'body'        => $message,
            ], JSON_UNESCAPED_UNICODE);
        } elseif ($provider === 'mpwa') {
            $headers = ['Content-Type: application/json'];
            $postFields = json_encode([
                'api_key' => $apiToken,
                'sender'  => $sender !== '' ? $sender : trim((string)($saas['wa_sender'] ?? '')),
                'number'  => $clean,
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE);
        } elseif ($provider === 'waha') {
            $headers = [
                'X-Api-Key: ' . $apiToken,
                'Content-Type: application/json',
            ];
            $sessionName = trim((string)($saas['wa_sender'] ?? '')) ?: 'default';
            $postFields = json_encode([
                'session' => $sessionName,
                'chatId'  => $clean . '@c.us',
                'text'    => $message,
            ], JSON_UNESCAPED_UNICODE);
        } elseif ($provider === 'whacenter') {
            $postFields = [
                'device_id' => $apiToken,
                'number'    => $clean,
                'message'   => $message,
            ];
        } else {
            // Generic REST JSON Webhook
            $headers = [
                'Authorization: Bearer ' . $apiToken,
                'Content-Type: application/json',
            ];
            $postFields = json_encode([
                'api_key' => $apiToken,
                'sender'  => $sender,
                'target'  => $clean,
                'phone'   => $clean,
                'number'  => $clean,
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE);
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $rawRes = curl_exec($ch);
        $curlErr = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Fallback for local Windows XAMPP setups missing CA bundle (errno 60/77)
        if ($rawRes === false && in_array($curlErrno, [60, 77], true)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $rawRes = curl_exec($ch);
            $curlErr = curl_error($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
        curl_close($ch);

        $status       = 'failed';
        $responseBody = '';

        if ($rawRes === false || $curlErr !== '') {
            $responseBody = 'Koneksi ke WA Gateway gagal (cURL): ' . $curlErr;
        } else {
            $resStr = trim((string)$rawRes);
            $decoded = json_decode($resStr, true);

            if ($httpCode < 200 || $httpCode >= 300) {
                $providerErr = self::extractProviderMessage($decoded);
                $responseBody = "HTTP {$httpCode}" . ($providerErr !== '' ? " — {$providerErr}" : ': ' . substr($resStr, 0, 500));
            } else {
                // Strictly inspect provider JSON body (Fonnte/Wablas/MPWA/Starsender return HTTP 200 even on auth/device error!)
                $apiRejected = self::isProviderRejected($decoded);
                if ($apiRejected !== null) {
                    $status = 'failed';
                    $responseBody = 'Ditolak oleh provider (' . strtoupper($provider) . '): ' . $apiRejected;
                } else {
                    $status = 'sent';
                    $okMsg = self::extractProviderMessage($decoded);
                    $responseBody = $okMsg !== ''
                        ? "Terkirim via " . strtoupper($provider) . " (HTTP {$httpCode}): {$okMsg}"
                        : "Terkirim via " . strtoupper($provider) . " (HTTP {$httpCode}): " . substr($resStr, 0, 500);
                }
            }
        }

        self::logAttempt($schoolId, $clean, $type, $message, $status, substr($responseBody, 0, 1000));

        return [
            'ok'        => $status === 'sent',
            'status'    => $status,
            'http_code' => $httpCode,
            'response'  => $responseBody,
        ];
    }

    /**
     * Detect logical rejection inside HTTP 200 JSON responses from WA providers.
     * Returns rejection reason string if rejected, or null if accepted.
     */
    private static function isProviderRejected(mixed $decoded): ?string
    {
        if (!is_array($decoded)) {
            return null;
        }
        if (array_key_exists('status', $decoded)) {
            $st = $decoded['status'];
            if ($st === false || $st === 0 || $st === 'false' || strtolower((string)$st) === 'error' || strtolower((string)$st) === 'failed') {
                return self::extractProviderMessage($decoded) ?: 'Status provider bernilai false/failed.';
            }
        }
        if (array_key_exists('success', $decoded) && ($decoded['success'] === false || $decoded['success'] === 0 || $decoded['success'] === 'false')) {
            return self::extractProviderMessage($decoded) ?: 'Response success=false dari provider.';
        }
        if (array_key_exists('ok', $decoded) && $decoded['ok'] === false) {
            return self::extractProviderMessage($decoded) ?: 'Response ok=false dari provider.';
        }
        if (!empty($decoded['error']) && is_string($decoded['error'])) {
            return $decoded['error'];
        }
        return null;
    }

    private static function extractProviderMessage(mixed $decoded): string
    {
        if (!is_array($decoded)) {
            return '';
        }
        foreach (['reason', 'message', 'msg', 'detail', 'error', 'info'] as $key) {
            if (!empty($decoded[$key]) && is_string($decoded[$key])) {
                return trim($decoded[$key]);
            }
        }
        return '';
    }

    private static function logAttempt(?int $schoolId, string $phone, string $type, string $message, string $status, string $responseBody): void
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("
                INSERT INTO wa_logs (school_id, phone, reminder_type, message, status, response_body, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$schoolId, $phone, $type, $message, $status, $responseBody]);
        } catch (Throwable) {
        }
    }

    /**
     * Check all schools expiring in 3 days (H-3) and today (H-0), and send REAL live WA reminders.
     */
    public static function runDueReminders(): array
    {
        $pdo   = Database::pdo();
        $saas  = Tenant::saasSettings(true);

        $cfgCheck = self::isConfigured($saas);
        if (!$cfgCheck['ready']) {
            return [
                'ok'      => false,
                'error'   => $cfgCheck['reason'],
                'h3_sent' => 0,
                'h0_sent' => 0,
                'failed'  => 0,
                'details' => [],
            ];
        }

        $today = date('Y-m-d');
        $h3    = date('Y-m-d', strtotime('+3 days'));

        $summary = ['ok' => true, 'error' => '', 'h3_sent' => 0, 'h0_sent' => 0, 'failed' => 0, 'details' => []];

        // H-3 Reminders
        $stmtH3 = $pdo->prepare("
            SELECT s.*, p.name AS package_name, p.price_monthly
            FROM schools s
            LEFT JOIN packages p ON p.id = s.package_id
            WHERE s.subscription_status IN ('active', 'trial')
              AND s.subscription_end = ?
              AND (s.last_reminded_h3_date IS NULL OR s.last_reminded_h3_date <> ?)
              AND s.phone IS NOT NULL AND s.phone <> ''
        ");
        $stmtH3->execute([$h3, $today]);

        foreach ($stmtH3->fetchAll() as $school) {
            $msg = self::renderTemplate($saas['wa_template_h3'], [
                'app_name'        => $saas['app_name'],
                'nama_sekolah'    => $school['name'],
                'paket'           => $school['package_name'] ?? 'Standar',
                'tanggal_expired' => format_date_id($school['subscription_end']),
                'nominal'         => format_rupiah($school['price_monthly'] ?? 0),
                'link_portal'     => 'https://' . Tenant::schoolDisplayUrl($school['slug']),
            ]);
            $res = self::send((int)$school['id'], $school['phone'], $msg, 'h3');
            if ($res['ok']) {
                $pdo->prepare("UPDATE schools SET last_reminded_h3_date = ? WHERE id = ?")->execute([$today, $school['id']]);
                $summary['h3_sent']++;
            } else {
                $summary['failed']++;
            }
            $summary['details'][] = ['school' => $school['name'], 'type' => 'H-3', 'status' => $res['status'], 'response' => $res['response']];
        }

        // H-0 Reminders (Due Today)
        $stmtH0 = $pdo->prepare("
            SELECT s.*, p.name AS package_name, p.price_monthly
            FROM schools s
            LEFT JOIN packages p ON p.id = s.package_id
            WHERE s.subscription_status IN ('active', 'trial', 'expired')
              AND s.subscription_end = ?
              AND (s.last_reminded_h0_date IS NULL OR s.last_reminded_h0_date <> ?)
              AND s.phone IS NOT NULL AND s.phone <> ''
        ");
        $stmtH0->execute([$today, $today]);

        foreach ($stmtH0->fetchAll() as $school) {
            $msg = self::renderTemplate($saas['wa_template_h0'], [
                'app_name'        => $saas['app_name'],
                'nama_sekolah'    => $school['name'],
                'paket'           => $school['package_name'] ?? 'Standar',
                'tanggal_expired' => format_date_id($school['subscription_end']),
                'nominal'         => format_rupiah($school['price_monthly'] ?? 0),
                'link_portal'     => 'https://' . Tenant::schoolDisplayUrl($school['slug']),
            ]);
            $res = self::send((int)$school['id'], $school['phone'], $msg, 'h0');
            if ($res['ok']) {
                $pdo->prepare("UPDATE schools SET last_reminded_h0_date = ? WHERE id = ?")->execute([$today, $school['id']]);
                $summary['h0_sent']++;
            } else {
                $summary['failed']++;
            }
            $summary['details'][] = ['school' => $school['name'], 'type' => 'H-0', 'status' => $res['status'], 'response' => $res['response']];
        }

        return $summary;
    }
}
