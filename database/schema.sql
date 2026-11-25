-- EduPresensi SaaS - Manajemen Absensi Sekolah
-- MySQL / MariaDB Schema (InnoDB, utf8mb4)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `saas_settings` (
  `id` TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  `app_name` VARCHAR(100) NOT NULL DEFAULT 'EduPresensi',
  `tagline` VARCHAR(200) NOT NULL DEFAULT 'Sistem SaaS Manajemen Absensi Sekolah Modern',
  `display_domain` VARCHAR(150) NOT NULL DEFAULT 'eduabsen.sekolah-indonesia.id',
  `logo_path` VARCHAR(255) DEFAULT 'assets/img/default-logo.svg',
  `favicon_path` VARCHAR(255) DEFAULT 'assets/img/default-favicon.svg',
  `wa_provider` VARCHAR(30) NOT NULL DEFAULT 'fonnte',
  `wa_api_url` VARCHAR(255) DEFAULT 'https://api.fonnte.com/send',
  `wa_api_token` VARCHAR(255) DEFAULT '',
  `wa_sender` VARCHAR(40) DEFAULT '',
  `wa_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `wa_template_h3` TEXT NULL,
  `wa_template_h0` TEXT NULL,
  `cron_secret` VARCHAR(100) NOT NULL DEFAULT 'edupresensi-cron-2026',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `packages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `price_monthly` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `price_6months` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `price_yearly` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `max_employees` INT UNSIGNED NOT NULL DEFAULT 50,
  `max_students` INT UNSIGNED NOT NULL DEFAULT 500,
  `features_json` TEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schools` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `package_id` INT UNSIGNED NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `npsn` VARCHAR(30) NULL,
  `email` VARCHAR(120) NULL,
  `phone` VARCHAR(30) NULL,
  `address` TEXT NULL,
  `principal_name` VARCHAR(120) NULL,
  `office_location_name` VARCHAR(150) NULL,
  `office_lat` DECIMAL(10,7) NOT NULL DEFAULT -0.8917000,
  `office_lng` DECIMAL(10,7) NOT NULL DEFAULT 119.8707000,
  `office_radius` INT UNSIGNED NOT NULL DEFAULT 150,
  `force_global_location` TINYINT(1) NOT NULL DEFAULT 0,
  `subscription_status` ENUM('active','trial','expired','suspended') NOT NULL DEFAULT 'active',
  `subscription_start` DATE NULL,
  `subscription_end` DATE NULL,
  `last_reminded_h3_date` DATE NULL,
  `last_reminded_h0_date` DATE NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_schools_status_end` (`subscription_status`, `subscription_end`),
  CONSTRAINT `fk_schools_package` FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `subscription_transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(60) NOT NULL UNIQUE,
  `school_id` INT UNSIGNED NOT NULL,
  `package_id` INT UNSIGNED NULL,
  `duration_months` INT UNSIGNED NOT NULL DEFAULT 1,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('paid','pending','cancelled') NOT NULL DEFAULT 'paid',
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Transfer Bank',
  `notes` VARCHAR(255) NULL,
  `period_start` DATE NULL,
  `period_end` DATE NULL,
  `paid_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tx_school` (`school_id`),
  INDEX `idx_tx_status_date` (`status`, `paid_at`),
  CONSTRAINT `fk_tx_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tx_package` FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wa_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NULL,
  `phone` VARCHAR(30) NOT NULL,
  `reminder_type` ENUM('h3','h0','manual','test') NOT NULL DEFAULT 'manual',
  `message` TEXT NOT NULL,
  `status` ENUM('sent','failed','simulated') NOT NULL DEFAULT 'simulated',
  `response_body` TEXT NULL,
  `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_wa_school` (`school_id`),
  INDEX `idx_wa_sent_at` (`sent_at`),
  CONSTRAINT `fk_wa_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shifts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(20) NOT NULL DEFAULT 'REG',
  `name` VARCHAR(80) NOT NULL,
  `start_time` TIME NOT NULL DEFAULT '07:15:00',
  `end_time` TIME NOT NULL DEFAULT '15:00:00',
  `checkin_open_before` INT UNSIGNED NOT NULL DEFAULT 60,
  `late_tolerance_minutes` INT UNSIGNED NOT NULL DEFAULT 10,
  `max_late_minutes` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = tidak dibatasi',
  `early_checkout_tolerance` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'menit sebelum jam pulang',
  `is_cross_day` TINYINT(1) NOT NULL DEFAULT 0,
  `color` VARCHAR(20) NOT NULL DEFAULT '#1E5BF0',
  `description` VARCHAR(200) NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_shifts_school` (`school_id`),
  CONSTRAINT `fk_shifts_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `offices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(20) NOT NULL DEFAULT 'KTR',
  `name` VARCHAR(150) NOT NULL,
  `address` VARCHAR(255) NULL,
  `latitude` DECIMAL(10,7) NOT NULL,
  `longitude` DECIMAL(10,7) NOT NULL,
  `radius` INT UNSIGNED NOT NULL DEFAULT 150,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_offices_school` (`school_id`),
  CONSTRAINT `fk_offices_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `office_shifts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `office_id` INT UNSIGNED NOT NULL,
  `shift_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_office_shift` (`office_id`, `shift_id`),
  INDEX `idx_os_school` (`school_id`),
  CONSTRAINT `fk_os_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_os_office` FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_os_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NULL,
  `role` ENUM('superadmin','school_admin','teacher','employee') NOT NULL,
  `nip_nik` VARCHAR(50) NULL,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `position_title` VARCHAR(100) NULL,
  `subject_name` VARCHAR(100) NULL,
  `assigned_classes` VARCHAR(255) NULL,
  `default_office_id` INT UNSIGNED NULL,
  `default_shift_id` INT UNSIGNED NULL,
  `default_lat` DECIMAL(10,7) NULL,
  `default_lng` DECIMAL(10,7) NULL,
  `default_radius` INT UNSIGNED NULL,
  `avatar_path` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_school_role` (`school_id`, `role`),
  INDEX `idx_users_email` (`email`),
  CONSTRAINT `fk_users_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_users_shift` FOREIGN KEY (`default_shift_id`) REFERENCES `shifts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `persistent_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `school_id` INT UNSIGNED NULL,
  `selector` CHAR(24) NOT NULL UNIQUE,
  `validator_hash` CHAR(64) NOT NULL,
  `device_name` VARCHAR(120) NULL,
  `user_agent` VARCHAR(255) NULL,
  `ip_address` VARCHAR(45) NULL,
  `last_used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pt_user` (`user_id`),
  CONSTRAINT `fk_pt_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `identifier` VARCHAR(150) NOT NULL,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_login_ip` (`ip_address`, `attempted_at`),
  INDEX `idx_login_ident` (`identifier`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employee_schedules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `day_of_week` TINYINT UNSIGNED NULL COMMENT '1=Senin .. 7=Minggu',
  `specific_date` DATE NULL COMMENT 'Override untuk tanggal tertentu',
  `office_id` INT UNSIGNED NULL,
  `shift_id` INT UNSIGNED NULL,
  `is_day_off` TINYINT(1) NOT NULL DEFAULT 0,
  `custom_lat` DECIMAL(10,7) NULL,
  `custom_lng` DECIMAL(10,7) NULL,
  `custom_radius` INT UNSIGNED NULL,
  `location_name` VARCHAR(120) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_es_school_user` (`school_id`, `user_id`),
  INDEX `idx_es_user_day` (`user_id`, `day_of_week`),
  INDEX `idx_es_user_date` (`user_id`, `specific_date`),
  CONSTRAINT `fk_es_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_es_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_es_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employee_attendances` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `attendance_date` DATE NOT NULL,
  `shift_id` INT UNSIGNED NULL,
  `shift_name` VARCHAR(80) NULL,
  `scheduled_start` TIME NULL,
  `scheduled_end` TIME NULL,
  `target_lat` DECIMAL(10,7) NULL,
  `target_lng` DECIMAL(10,7) NULL,
  `target_radius` INT UNSIGNED NULL,
  `checkin_time` DATETIME NULL,
  `checkin_lat` DECIMAL(10,7) NULL,
  `checkin_lng` DECIMAL(10,7) NULL,
  `checkin_distance` DECIMAL(10,2) NULL,
  `checkin_photo` VARCHAR(255) NULL,
  `checkin_note` VARCHAR(255) NULL,
  `checkout_time` DATETIME NULL,
  `checkout_lat` DECIMAL(10,7) NULL,
  `checkout_lng` DECIMAL(10,7) NULL,
  `checkout_distance` DECIMAL(10,2) NULL,
  `checkout_photo` VARCHAR(255) NULL,
  `checkout_note` VARCHAR(255) NULL,
  `status` ENUM('hadir','terlambat','izin','sakit','alpha','dinas_luar') NOT NULL DEFAULT 'hadir',
  `late_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_corrected` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_emp_att_day` (`school_id`, `user_id`, `attendance_date`),
  INDEX `idx_emp_att_date` (`school_id`, `attendance_date`),
  CONSTRAINT `fk_ea_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ea_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `attendance_corrections` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `attendance_date` DATE NOT NULL,
  `correction_type` ENUM('lupa_checkin','lupa_checkout','koreksi_jam','izin','sakit','dinas_luar') NOT NULL,
  `requested_checkin` TIME NULL,
  `requested_checkout` TIME NULL,
  `reason` TEXT NOT NULL,
  `attachment_path` VARCHAR(255) NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` INT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `admin_note` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ac_school_status` (`school_id`, `status`),
  CONSTRAINT `fk_ac_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ac_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `students` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `nisn` VARCHAR(40) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `class_name` VARCHAR(50) NOT NULL,
  `gender` ENUM('L','P') NOT NULL DEFAULT 'L',
  `guardian_name` VARCHAR(120) NULL,
  `guardian_phone` VARCHAR(30) NULL,
  `qr_token` VARCHAR(64) NOT NULL UNIQUE,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_student_school_nisn` (`school_id`, `nisn`),
  INDEX `idx_student_class` (`school_id`, `class_name`),
  CONSTRAINT `fk_students_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_attendance_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `teacher_id` INT UNSIGNED NOT NULL,
  `class_name` VARCHAR(50) NOT NULL,
  `subject_name` VARCHAR(100) NOT NULL,
  `session_title` VARCHAR(150) NOT NULL,
  `session_date` DATE NOT NULL,
  `start_time` TIME NULL,
  `end_time` TIME NULL,
  `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
  `notes` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_sas_school_date` (`school_id`, `session_date`),
  INDEX `idx_sas_teacher` (`teacher_id`),
  CONSTRAINT `fk_sas_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sas_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_attendance_records` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `session_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `status` ENUM('hadir','sakit','izin','alpha') NOT NULL DEFAULT 'alpha',
  `scanned_at` DATETIME NULL,
  `scan_method` ENUM('qr','manual') NULL,
  `note` VARCHAR(200) NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_sar_session_student` (`session_id`, `student_id`),
  INDEX `idx_sar_school` (`school_id`),
  CONSTRAINT `fk_sar_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sar_session` FOREIGN KEY (`session_id`) REFERENCES `student_attendance_sessions`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sar_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
