<?php
declare(strict_types=1);

class Auth
{
    private const PWA_COOKIE_NAME       = 'EDUPRESENSI_PWA_AUTH';
    private const ADMIN_SESSION_SECONDS = 10800; // 3 hours strictly for Web Admin & Guru
    private const FOREVER_DAYS          = 3650;  // 10 years sliding forever login for Mobile PWA Pegawai

    private static ?array $cachedUser = null;

    /**
     * Resolve currently logged in user from PHP session OR secure rotating PWA forever-login cookie.
     * - Web Admin (superadmin, school_admin, teacher): strictly expires after 3 hours.
     * - Mobile PWA (employee): persistent forever login via rotating SHA-256 token.
     */
    public static function user(): ?array
    {
        Security::startSession();

        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        if (!empty($_SESSION['auth_user_id'])) {
            $sessRole = (string)($_SESSION['auth_role'] ?? '');
            $isPwaEmployee = ($sessRole === 'employee');

            // Enforce strict 3-hour session timeout for Web Admin & Guru roles
            if (!$isPwaEmployee) {
                $expiresAt = (int)($_SESSION['auth_expires_at'] ?? 0);
                if ($expiresAt <= 0 || time() > $expiresAt) {
                    unset(
                        $_SESSION['auth_user_id'],
                        $_SESSION['auth_role'],
                        $_SESSION['auth_school_id'],
                        $_SESSION['auth_expires_at'],
                        $_SESSION['auth_token_selector']
                    );
                    self::clearPersistentCookie();
                    return null;
                }
            }

            $user = self::fetchActiveUser((int)$_SESSION['auth_user_id']);
            if ($user) {
                self::$cachedUser = $user;
                return $user;
            }
            unset($_SESSION['auth_user_id'], $_SESSION['auth_role'], $_SESSION['auth_school_id'], $_SESSION['auth_expires_at']);
        }

        // Attempt secure automatic re-authentication via rotating PWA Forever-Login cookie (employee ONLY)
        $cookieUser = self::authenticateFromPersistentCookie();
        if ($cookieUser) {
            self::$cachedUser = $cookieUser;
            return $cookieUser;
        }

        return null;
    }

    private static function fetchActiveUser(int $userId): ?array
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("
                SELECT u.*, s.slug AS school_slug, s.name AS school_name, s.subscription_status, s.subscription_end
                FROM users u
                LEFT JOIN schools s ON s.id = u.school_id
                WHERE u.id = ? AND u.is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            return $user ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function loginUser(array $user, bool $persistForever = false): void
    {
        Security::startSession();
        session_regenerate_id(true);

        $isPwaEmployee = ($user['role'] === 'employee');

        $_SESSION['auth_user_id']   = (int)$user['id'];
        $_SESSION['auth_role']      = $user['role'];
        $_SESSION['auth_school_id'] = $user['school_id'] !== null ? (int)$user['school_id'] : null;

        if ($isPwaEmployee) {
            // Mobile PWA Pegawai: forever session (no 3-hour cutoff)
            unset($_SESSION['auth_expires_at']);
        } else {
            // Web Admin / Guru: strictly expires in 3 hours
            $_SESSION['auth_expires_at'] = time() + self::ADMIN_SESSION_SECONDS;
        }

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
        } catch (Throwable) {
        }

        if ($isPwaEmployee && $persistForever) {
            self::issuePersistentToken((int)$user['id'], $user['school_id'] !== null ? (int)$user['school_id'] : null);
        } elseif (!$isPwaEmployee) {
            // Ensure Web Admin never keeps an old forever-login cookie
            self::clearPersistentCookie();
        }

        self::$cachedUser = self::fetchActiveUser((int)$user['id']);
    }

    /**
     * Issue a cryptographically secure selector:validator cookie for PWA forever login.
     * Only the SHA-256 hash of validator is stored in MySQL.
     */
    public static function issuePersistentToken(int $userId, ?int $schoolId): void
    {
        $selector      = bin2hex(random_bytes(12)); // 24 chars
        $validator     = bin2hex(random_bytes(32)); // 64 chars
        $validatorHash = hash('sha256', $validator);
        $expiresTs     = time() + (86400 * self::FOREVER_DAYS);
        $expiresAt     = date('Y-m-d H:i:s', $expiresTs);
        $ua            = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device', 0, 255);
        $deviceName    = self::parseDeviceName($ua);

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("
                INSERT INTO persistent_tokens (user_id, school_id, selector, validator_hash, device_name, user_agent, ip_address, last_used_at, expires_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW())
            ");
            $stmt->execute([$userId, $schoolId, $selector, $validatorHash, $deviceName, $ua, Security::clientIp(), $expiresAt]);
            $_SESSION['auth_token_selector'] = $selector;
            self::setPersistentCookie($selector . ':' . $validator, $expiresTs);
        } catch (Throwable) {
        }
    }

    private static function authenticateFromPersistentCookie(): ?array
    {
        $raw = $_COOKIE[self::PWA_COOKIE_NAME] ?? '';
        if ($raw === '' || !str_contains($raw, ':')) {
            return null;
        }
        [$selector, $validator] = explode(':', $raw, 2);
        if (strlen($selector) !== 24 || strlen($validator) !== 64) {
            self::clearPersistentCookie();
            return null;
        }

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("
                SELECT * FROM persistent_tokens
                WHERE selector = ? AND expires_at > NOW()
                LIMIT 1
            ");
            $stmt->execute([$selector]);
            $token = $stmt->fetch();
            if (!$token) {
                self::clearPersistentCookie();
                return null;
            }

            $calcHash = hash('sha256', $validator);
            if (!hash_equals($token['validator_hash'], $calcHash)) {
                // Possible stolen/replayed cookie — revoke this token immediately
                $del = $pdo->prepare("DELETE FROM persistent_tokens WHERE id = ?");
                $del->execute([$token['id']]);
                self::clearPersistentCookie();
                return null;
            }

            $user = self::fetchActiveUser((int)$token['user_id']);
            // Only Mobile PWA employee accounts may use persistent forever login
            if (!$user || $user['role'] !== 'employee') {
                $del = $pdo->prepare("DELETE FROM persistent_tokens WHERE id = ?");
                $del->execute([$token['id']]);
                self::clearPersistentCookie();
                return null;
            }

            // Rotate validator on re-auth & extend sliding 10-year expiry
            $newValidator     = bin2hex(random_bytes(32));
            $newValidatorHash = hash('sha256', $newValidator);
            $newExpiresTs     = time() + (86400 * self::FOREVER_DAYS);
            $newExpiresAt     = date('Y-m-d H:i:s', $newExpiresTs);

            $upd = $pdo->prepare("
                UPDATE persistent_tokens
                SET validator_hash = ?, last_used_at = NOW(), expires_at = ?, ip_address = ?
                WHERE id = ?
            ");
            $upd->execute([$newValidatorHash, $newExpiresAt, Security::clientIp(), $token['id']]);

            session_regenerate_id(true);
            $_SESSION['auth_user_id']        = (int)$user['id'];
            $_SESSION['auth_role']           = $user['role'];
            $_SESSION['auth_school_id']      = $user['school_id'] !== null ? (int)$user['school_id'] : null;
            $_SESSION['auth_token_selector'] = $selector;

            self::setPersistentCookie($selector . ':' . $newValidator, $newExpiresTs);
            return $user;
        } catch (Throwable) {
            return null;
        }
    }

    public static function logout(): void
    {
        Security::startSession();
        $raw = $_COOKIE[self::PWA_COOKIE_NAME] ?? '';
        if ($raw !== '' && str_contains($raw, ':')) {
            [$selector] = explode(':', $raw, 2);
            try {
                $pdo = Database::pdo();
                $stmt = $pdo->prepare("DELETE FROM persistent_tokens WHERE selector = ?");
                $stmt->execute([$selector]);
            } catch (Throwable) {
            }
        }
        self::clearPersistentCookie();
        $_SESSION = [];
        session_destroy();
        self::$cachedUser = null;
    }

    public static function listDevices(int $userId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT id, selector, device_name, ip_address, last_used_at, created_at, expires_at
            FROM persistent_tokens
            WHERE user_id = ? AND expires_at > NOW()
            ORDER BY last_used_at DESC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        $currentSelector = $_SESSION['auth_token_selector'] ?? '';
        foreach ($rows as &$r) {
            $r['is_current'] = ($currentSelector !== '' && $r['selector'] === $currentSelector);
            unset($r['selector']);
        }
        return $rows;
    }

    public static function revokeDevice(int $userId, int $tokenId): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("DELETE FROM persistent_tokens WHERE id = ? AND user_id = ?");
        $stmt->execute([$tokenId, $userId]);
        return $stmt->rowCount() > 0;
    }

    private static function setPersistentCookie(string $value, int $expiresTs): void
    {
        setcookie(self::PWA_COOKIE_NAME, $value, [
            'expires'  => $expiresTs,
            'path'     => app_base_path() ?: '/',
            'secure'   => app_scheme() === 'https',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function clearPersistentCookie(): void
    {
        setcookie(self::PWA_COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => app_base_path() ?: '/',
            'secure'   => app_scheme() === 'https',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function parseDeviceName(string $ua): string
    {
        $os = 'Perangkat Lain';
        if (stripos($ua, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($ua, 'iPhone') !== false) {
            $os = 'iPhone';
        } elseif (stripos($ua, 'iPad') !== false) {
            $os = 'iPad';
        } elseif (stripos($ua, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($ua, 'Macintosh') !== false) {
            $os = 'macOS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        }

        $browser = 'Browser';
        if (stripos($ua, 'Edg') !== false) {
            $browser = 'Edge';
        } elseif (stripos($ua, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($ua, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (stripos($ua, 'Firefox') !== false) {
            $browser = 'Firefox';
        }

        return "{$os} ({$browser})";
    }

    public static function requireSuperadmin(bool $json = false): array
    {
        $user = self::user();
        if (!$user || $user['role'] !== 'superadmin') {
            if ($json) {
                Response::json(['success' => false, 'message' => 'Akses ditolak. Login Superadmin diperlukan.'], 401);
            }
            Response::redirect(app_url('/superadmin/login'));
        }
        return $user;
    }

    public static function requireSchoolRole(array $school, array $allowedRoles, bool $json = false): array
    {
        $user = self::user();
        $isValidTenant = $user
            && (int)$user['school_id'] === (int)$school['id']
            && in_array($user['role'], $allowedRoles, true);

        if (!$isValidTenant) {
            if ($json) {
                Response::json(['success' => false, 'message' => 'Sesi tidak valid atau akses ditolak untuk sekolah ini.'], 401);
            }
            Response::redirect(Tenant::schoolLocalUrl($school['slug']));
        }

        if (!Tenant::isSchoolActive($school)) {
            if ($json) {
                Response::json(['success' => false, 'message' => 'Langganan sekolah telah berakhir atau ditangguhkan.'], 402);
            }
            Response::render('public/school_suspended', ['school' => $school]);
            exit;
        }

        return $user;
    }
}
