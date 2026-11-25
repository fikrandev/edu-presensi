# EduPresensi SaaS — Sistem Manajemen Absensi Sekolah Multi-Tenant

Aplikasi SaaS Manajemen Absensi Sekolah berbasis **PHP Native (PHP 8.x)**, **MySQL**, **Tailwind CSS**, **jQuery / JavaScript**, dan **FontAwesome 6** dengan tema biru modern yang ramah pengguna.

## Fitur Utama

### A. Superadmin (Desktop Sidebar & Responsive Mobile Bottom Navigation)
- **Login Superadmin** dengan proteksi CSRF & brute-force rate limiting.
- **Dashboard Monitoring Pelanggan** (sekolah aktif, jatuh tempo ≤7 hari, omset bulanan & total, grafik 6 bulan).
- **Daftar Sekolah Berlangganan**: tambah sekolah otomatis membuat akun Admin Sekolah + shift default, tombol cepat **Perpanjang 1 Bulan, 6 Bulan, dan 1 Tahun**, serta URL subfolder otomatis seperti `eduabsen.sekolah-indonesia.id/bina-insan-palu`.
- **Daftar Paket & Checklist Fitur**: buat/edit paket baru cukup centang fitur apa saja yang aktif (enforced di portal sekolah).
- **Keuangan, Omset & Harga Paket**: manajemen invoice/pembayaran serta update cepat harga paket 1/6/12 bulan.
- **WA Gateway Pengingat Jatuh Tempo (H-3 & Hari-H)**: mendukung Fonnte, Wablas, & Generic REST Webhook, template pesan kustom, tes kirim, trigger manual, serta cron otomatis harian (`cron/wa_reminder.php`).
- **Pengaturan Logo & Favicon Global SaaS**: otomatis berlaku ke semua halaman dan menjadi **Icon Installer PWA Mobile Pegawai**.

### B. Admin Sekolah (`/{slug}/admin`)
- **Dashboard Monitoring** kehadiran pegawai & siswa real-time + grafik 7 hari.
- **Data Pegawai**: CRUD, **Import Excel (`.xlsx`/`.xls` — menolak `.csv`)** + download template `.xlsx`, Kelola Master Shift, dan **Jadwal Shift & Koordinat Kantor Custom per Hari per Orang** (pola mingguan Senin–Minggu & override tanggal khusus dengan peta interaktif).
- **Data Siswa**: CRUD, **Import Excel (`.xlsx`)**, serta **Generate & Cetak Kartu Absen QR Code** (satuan maupun massal per kelas siap print A4).
- **Laporan Absensi Pegawai**: Log Absen harian, **Matrix Monitoring Pengisian** (kalender 1–31), **Rekapitulasi & Persentase Kehadiran** (+ Export Excel `.xlsx`), serta **Approval Pengajuan Perbaikan Absen**.
- **Pengaturan Sekolah**: atur nama sekolah, profil, serta titik koordinat GPS & radius geofence default.
- **Manajemen Akun Guru** untuk absensi siswa berbasis QR Code.

### C. Admin Guru (`/{slug}/guru`)
- **Buat Sesi Absen (Tarik Data Kelas)** otomatis menarik seluruh siswa aktif di kelas tersebut.
- **Scan QR Code Siswa** menggunakan kamera HP/laptop (`html5-qrcode`) + dukungan input manual/USB scanner & status manual (Hadir/Sakit/Izin/Alpha).
- **Rekapitulasi & Laporan** kehadiran siswa per kelas & periode + Export Excel `.xlsx`.

### D. Pegawai — Mobile PWA (`/{slug}/pegawai`)
- **Installable Mobile PWA** (`/{slug}/manifest.json` & `/{slug}/sw.js`) dengan icon installer dari Favicon Superadmin.
- **Keamanan Login Selamanya (PWA)**: menggunakan cookie `HttpOnly` + `SameSite=Lax` bertipe `selector:validator` dengan hash `SHA-256` di MySQL dan rotasi token otomatis setiap sesi ulang, plus manajemen daftar perangkat & tombol cabut akses perangkat.
- **Dashboard Monitoring Kehadiran Pribadi**.
- **Check-in & Check-out GPS Geofencing** sesuai jadwal shift & koordinat harian masing-masing pegawai + opsional foto selfie.
- **Pengajuan Perbaikan Absen** (Lupa Check-in/out, Koreksi Jam, Izin, Sakit, Dinas Luar + bukti foto).
- **Pengaturan Akun** (profil, foto, ubah password, kelola perangkat login PWA).

---

## Cara Instalasi (XAMPP / Production)

1. Aktifkan **Apache** (pastikan `mod_rewrite` aktif) dan **MySQL** di XAMPP Control Panel.
2. Sesuaikan konfigurasi database di `config/database.php` jika diperlukan (default: `127.0.0.1`, user `root`, password kosong, database `edu_presensi_saas`).
3. Buka browser dan akses:
   ```
   http://localhost/edu-presensi/install.php
   ```
   Klik **Jalankan Instalasi Database & Seed Demo** (atau import manual `database/schema.sql` lalu jalankan `php database/seed.php`).

## Akun Demo Default (Password: `Admin123!`)
| Role | URL Akses Lokal | Email Login |
|---|---|---|
| Superadmin | `http://localhost/edu-presensi/superadmin/login` | `superadmin@edupresensi.id` |
| Admin Sekolah (Bina Insan Palu) | `http://localhost/edu-presensi/bina-insan-palu` | `admin@binainsan.sch.id` |
| Admin Guru (Bina Insan Palu) | `http://localhost/edu-presensi/bina-insan-palu` | `guru@binainsan.sch.id` |
| Pegawai Mobile PWA | `http://localhost/edu-presensi/bina-insan-palu` | `pegawai@binainsan.sch.id` |

## Jadwal Cron Pengingat WA Otomatis (H-3 & Hari-H)
Tambahkan di Cron Job server / cPanel setiap hari pukul 08:00:
```bash
0 8 * * * /usr/bin/php /path/to/edu-presensi/cron/wa_reminder.php
```
Atau via HTTP URL yang tampil di halaman **Superadmin → WA Gateway**.
