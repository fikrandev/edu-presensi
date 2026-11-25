<?php
declare(strict_types=1);

class Tenant
{
    private static ?array $saasCache = null;
    private static ?array $currentSchool = null;

    public static function saasSettings(bool $refresh = false): array
    {
        if (self::$saasCache !== null && !$refresh) {
            return self::$saasCache;
        }
        $defaults = [
            'id'              => 1,
            'app_name'        => 'EduPresensi',
            'tagline'         => 'Sistem SaaS Manajemen Absensi Sekolah Modern',
            'display_domain'  => 'eduabsen.sekolah-indonesia.id',
            'logo_path'       => 'assets/img/default-logo.svg',
            'favicon_path'    => 'assets/img/default-favicon.svg',
            'wa_provider'     => 'fonnte',
            'wa_api_url'      => 'https://api.fonnte.com/send',
            'wa_api_token'    => '',
            'wa_sender'       => '',
            'wa_enabled'      => 0,
            'wa_template_h3'  => "Halo Admin *{nama_sekolah}*,\n\nPengingat tagihan langganan *{app_name}* paket *{paket}* akan jatuh tempo dalam *3 hari* pada tanggal *{tanggal_expired}*.\nNominal perpanjangan: *{nominal}*.\n\nAkses portal sekolah Anda: {link_portal}\nTerima kasih.",
            'wa_template_h0'  => "Halo Admin *{nama_sekolah}*,\n\nHari ini (*{tanggal_expired}*) adalah tanggal jatuh tempo langganan *{app_name}* paket *{paket}*.\nNominal perpanjangan: *{nominal}*.\n\nMohon segera lakukan perpanjangan agar layanan absensi tetap aktif.\nPortal: {link_portal}",
            'cron_secret'     => 'edupresensi-cron-2026',
        ];

        try {
            $pdo = Database::pdo();
            $row = $pdo->query("SELECT * FROM saas_settings WHERE id = 1 LIMIT 1")->fetch();
            if ($row) {
                self::$saasCache = array_merge($defaults, $row);
                return self::$saasCache;
            }
        } catch (Throwable) {
        }

        self::$saasCache = $defaults;
        return self::$saasCache;
    }

    public static function findSchoolBySlug(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || in_array($slug, RESERVED_SLUGS, true)) {
            return null;
        }
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("
                SELECT s.*, p.name AS package_name, p.features_json, p.max_employees, p.max_students,
                       p.price_monthly, p.price_6months, p.price_yearly
                FROM schools s
                LEFT JOIN packages p ON p.id = s.package_id
                WHERE s.slug = ?
                LIMIT 1
            ");
            $stmt->execute([$slug]);
            $school = $stmt->fetch();
            if (!$school) {
                return null;
            }
            $school['features'] = self::decodeFeatures($school['features_json'] ?? null);

            // Auto-update status to expired if subscription_end has passed
            $today = date('Y-m-d');
            if (
                in_array($school['subscription_status'], ['active', 'trial'], true)
                && !empty($school['subscription_end'])
                && $school['subscription_end'] < $today
            ) {
                $upd = $pdo->prepare("UPDATE schools SET subscription_status = 'expired' WHERE id = ?");
                $upd->execute([$school['id']]);
                $school['subscription_status'] = 'expired';
            }

            return $school;
        } catch (Throwable) {
            return null;
        }
    }

    public static function setCurrentSchool(?array $school): void
    {
        self::$currentSchool = $school;
    }

    public static function currentSchool(): ?array
    {
        return self::$currentSchool;
    }

    public static function isSchoolActive(array $school): bool
    {
        if (!in_array($school['subscription_status'] ?? '', ['active', 'trial'], true)) {
            return false;
        }
        if (!empty($school['subscription_end']) && $school['subscription_end'] < date('Y-m-d')) {
            return false;
        }
        return true;
    }

    public static function decodeFeatures(?string $json): array
    {
        if (!$json) {
            return array_keys(SAAS_FEATURES);
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function hasFeature(?array $school, string $featureKey): bool
    {
        if (!$school) {
            return false;
        }
        $features = $school['features'] ?? self::decodeFeatures($school['features_json'] ?? null);
        return in_array($featureKey, $features, true);
    }

    public static function requireFeature(array $school, string $featureKey, bool $jsonMode = true): void
    {
        if (!self::hasFeature($school, $featureKey)) {
            $label = SAAS_FEATURES[$featureKey]['label'] ?? $featureKey;
            if ($jsonMode) {
                Response::json([
                    'success' => false,
                    'message' => "Fitur '{$label}' tidak termasuk dalam paket langganan sekolah Anda saat ini. Hubungi Superadmin untuk upgrade paket."
                ], 403);
            }
            http_response_code(403);
            echo "<div style='font-family:sans-serif;padding:40px;text-align:center'><h2>Fitur Tidak Tersedia</h2><p>Fitur <b>" . Security::e($label) . "</b> tidak aktif pada paket sekolah Anda.</p></div>";
            exit;
        }
    }

    public static function schoolDisplayUrl(string $slug): string
    {
        $saas = self::saasSettings();
        $domain = rtrim($saas['display_domain'] ?: 'eduabsen.sekolah-indonesia.id', '/');
        return $domain . '/' . ltrim($slug, '/');
    }

    public static function schoolLocalUrl(string $slug, string $subpath = ''): string
    {
        $path = '/' . trim($slug, '/');
        if ($subpath !== '') {
            $path .= '/' . ltrim($subpath, '/');
        }
        return app_url($path);
    }

    public static function brandingAssetUrl(?string $path, string $fallback): string
    {
        $use = ($path && file_exists(APP_ROOT . '/' . ltrim($path, '/'))) ? $path : $fallback;
        return app_url($use);
    }
}
