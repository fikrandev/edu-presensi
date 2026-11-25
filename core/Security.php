<?php
declare(strict_types=1);

class Security
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = app_scheme() === 'https';
            ini_set('session.gc_maxlifetime', '10800'); // 3 hours
            session_name('EDUPRESENSI_SESS');
            session_set_cookie_params([
                'lifetime' => 10800, // 3 hours for web session cookie
                'path'     => app_base_path() ?: '/',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function csrfToken(): string
    {
        self::startSession();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function verifyCsrf(): bool
    {
        self::startSession();
        $sessionToken = $_SESSION['_csrf_token'] ?? '';
        if ($sessionToken === '') {
            return false;
        }
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $postToken   = $_POST['_csrf'] ?? '';
        $token       = $headerToken !== '' ? $headerToken : $postToken;

        if ($token === '' && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $json = json_decode($raw ?: '{}', true);
            if (is_array($json) && !empty($json['_csrf'])) {
                $token = (string)$json['_csrf'];
            }
        }
        return $token !== '' && hash_equals($sessionToken, $token);
    }

    public static function requireCsrf(): void
    {
        if (!self::verifyCsrf()) {
            Response::json(['success' => false, 'message' => 'Sesi keamanan (CSRF) tidak valid. Silakan muat ulang halaman.'], 419);
        }
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 11]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function clientIp(): string
    {
        return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
    }

    public static function checkLoginRateLimit(string $identifier, int $maxAttempts = 8, int $windowMinutes = 15): bool
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM login_attempts
                WHERE (ip_address = ? OR identifier = ?)
                  AND attempted_at >= (NOW() - INTERVAL ? MINUTE)
            ");
            $stmt->execute([self::clientIp(), strtolower(trim($identifier)), $windowMinutes]);
            return ((int)$stmt->fetchColumn()) < $maxAttempts;
        } catch (Throwable) {
            return true;
        }
    }

    public static function recordFailedLogin(string $identifier): void
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, identifier, attempted_at) VALUES (?, ?, NOW())");
            $stmt->execute([self::clientIp(), strtolower(trim($identifier))]);
            // Periodic cleanup
            $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
        } catch (Throwable) {
        }
    }

    public static function clearLoginAttempts(string $identifier): void
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND identifier = ?");
            $stmt->execute([self::clientIp(), strtolower(trim($identifier))]);
        } catch (Throwable) {
        }
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-');
    }

    public static function isValidSlug(string $slug): bool
    {
        if ($slug === '' || strlen($slug) < 3 || strlen($slug) > 80) {
            return false;
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return false;
        }
        return !in_array($slug, RESERVED_SLUGS, true);
    }

    /**
     * Secure image upload with MIME & extension checks.
     */
    public static function uploadImage(array $file, string $subdir, array $allowedExts = ['png', 'jpg', 'jpeg', 'webp', 'svg', 'ico'], int $maxBytes = 2097152): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Gagal mengunggah file gambar.'];
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            return ['ok' => false, 'error' => 'Ukuran file melebihi batas maksimal (' . round($maxBytes / 1048576, 1) . ' MB).'];
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            return ['ok' => false, 'error' => 'Format file tidak didukung. Gunakan: ' . strtoupper(implode(', ', $allowedExts))];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']) ?: '';
        $allowedMimes = [
            'png'  => ['image/png'],
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'webp' => ['image/webp'],
            'svg'  => ['image/svg+xml', 'text/plain', 'text/xml', 'application/xml'],
            'ico'  => ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'application/octet-stream'],
        ];
        if (isset($allowedMimes[$ext]) && !in_array($mime, $allowedMimes[$ext], true)) {
            return ['ok' => false, 'error' => 'Tipe MIME file tidak sesuai (' . $mime . ').'];
        }

        $targetDir = UPLOAD_DIR . DIRECTORY_SEPARATOR . trim($subdir, '/\\');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['ok' => false, 'error' => 'Gagal menyimpan file ke server.'];
        }

        return [
            'ok'   => true,
            'path' => 'uploads/' . trim($subdir, '/\\') . '/' . $filename,
        ];
    }

    /**
     * Save a base64-encoded selfie/evidence image safely.
     */
    public static function saveBase64Image(string $dataUri, string $subdir): ?string
    {
        if (!preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,(.+)$/i', $dataUri, $matches)) {
            return null;
        }
        $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        $binary = base64_decode($matches[2], true);
        if ($binary === false || strlen($binary) > 2 * 1024 * 1024) {
            return null;
        }

        $targetDir = UPLOAD_DIR . DIRECTORY_SEPARATOR . trim($subdir, '/\\');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        file_put_contents($targetDir . DIRECTORY_SEPARATOR . $filename, $binary);
        return 'uploads/' . trim($subdir, '/\\') . '/' . $filename;
    }

    /**
     * Haversine distance in meters between two GPS coordinates.
     */
    public static function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }
}
