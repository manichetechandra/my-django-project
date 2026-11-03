-- =====================================================================
--  ترقية النسخة 2.0: قسم «المسابقات» (مسابقات التوظيف + الترشح الإلكتروني)
--
--  يضيف 7 جداول وعموداً واحداً في users (صلاحية الاطلاع على ملفات المترشحين).
--  المعطيات الشخصية للمترشحين (رقم التعريف، تاريخ ومكان الميلاد، العنوان، الهاتف، البريد)
--  تُحفظ مشفّرة (AES-256-GCM) في عمود personal_data، ورقم التعريف لا يُحفظ واضحاً أبداً:
--  نحفظ بصمته فقط (HMAC) في nin_hash لمنع الترشح مرتين لنفس المسابقة.
--  الوثائق المرفوعة (PDF) تُحفظ مشفّرة خارج مجلد الموقع العام: storage/private/recruitment/
--
--  طريقة التطبيق: phpMyAdmin ← قاعدتك ← Import ← هذا الملف (مرة واحدة فقط)
--  (لا تحتاجه إن استوردت schema.sql الجديد: الجداول موجودة فيه)
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `users`
  ADD COLUMN `can_recruit` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'مسابقات التوظيف: الاطلاع على ملفات المترشحين' AFTER `is_superuser`;

-- ---------------------------------------------------------------------
-- المسابقات (الإعلانات)
-- ---------------------------------------------------------------------
CREATE TABLE `competitions` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`            VARCHAR(300) NOT NULL,
  `title_fr`         VARCHAR(500) NULL DEFAULT NULL,
  `title_en`         VARCHAR(500) NULL DEFAULT NULL,
  `slug`             VARCHAR(300) NOT NULL,
  `reference`        VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'رقم الإعلان أو المقرر',
  `recruitment_mode` VARCHAR(30)  NOT NULL DEFAULT 'qualification' COMMENT 'qualification | exam | professional | contract',
  `summary`          VARCHAR(500) NOT NULL DEFAULT '',
  `summary_fr`       VARCHAR(800) NULL DEFAULT NULL,
  `summary_en`       VARCHAR(800) NULL DEFAULT NULL,
  `description`      TEXT         NOT NULL,
  `description_fr`   TEXT         NULL,
  `description_en`   TEXT         NULL,
  `conditions`       TEXT         NOT NULL COMMENT 'شروط المشاركة: شرط في كل سطر',
  `conditions_fr`    TEXT         NULL,
  `conditions_en`    TEXT         NULL,
  `deposit_info`     TEXT         NULL COMMENT 'مكان وكيفية إيداع الملف الورقي (إن وُجد)',
  `deposit_info_fr`  TEXT         NULL,
  `deposit_info_en`  TEXT         NULL,
  `opens_at`         DATE         NOT NULL COMMENT 'أول يوم لإيداع الملفات',
  `closes_at`        DATE         NOT NULL COMMENT 'آخر يوم لإيداع الملفات (حتى 23:59 بتوقيت الجزائر)',
  `exam_date`        DATE         NULL COMMENT 'تاريخ الاختبار أو المقابلة',
  `online_apply`     TINYINT(1)   NOT NULL DEFAULT 1 COMMENT 'قبول الترشح الإلكتروني',
  `status`           VARCHAR(20)  NOT NULL DEFAULT 'published' COMMENT 'draft | published | cancelled',
  `views_count`      INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_competitions_slug` (`slug`),
  KEY `idx_competitions_dates` (`status`, `opens_at`, `closes_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- الرتب والمناصب المفتوحة في كل مسابقة
CREATE TABLE `competition_posts` (
  `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `competition_id`  INT UNSIGNED      NOT NULL,
  `title`           VARCHAR(200)      NOT NULL COMMENT 'الرتبة',
  `title_fr`        VARCHAR(300)      NULL DEFAULT NULL,
  `title_en`        VARCHAR(300)      NULL DEFAULT NULL,
  `positions`       SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'عدد المناصب',
  `requirements`    VARCHAR(300)      NOT NULL DEFAULT '' COMMENT 'الشهادة أو التخصص المطلوب',
  `requirements_fr` VARCHAR(500)      NULL DEFAULT NULL,
  `requirements_en` VARCHAR(500)      NULL DEFAULT NULL,
  `location`        VARCHAR(200)      NOT NULL DEFAULT '' COMMENT 'مكان التعيين',
  `location_fr`     VARCHAR(300)      NULL DEFAULT NULL,
  `location_en`     VARCHAR(300)      NULL DEFAULT NULL,
  `order`           INT               NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_competition_posts_competition` (`competition_id`, `order`),
  CONSTRAINT `fk_competition_posts_competition` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- الوثائق المطلوبة في ملف الترشح (كل سطر = خانة رفع PDF في استمارة الترشح الإلكتروني)
CREATE TABLE `competition_requirements` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `competition_id` INT UNSIGNED NOT NULL,
  `label`          VARCHAR(200) NOT NULL,
  `label_fr`       VARCHAR(300) NULL DEFAULT NULL,
  `label_en`       VARCHAR(300) NULL DEFAULT NULL,
  `is_required`    TINYINT(1)   NOT NULL DEFAULT 1,
  `order`          INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_competition_requirements_competition` (`competition_id`, `order`),
  CONSTRAINT `fk_competition_requirements_competition` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- الملفات المنشورة للجميع: الإعلان الرسمي، استمارة الترشح، قائمة المقبولين، النتائج النهائية
CREATE TABLE `competition_files` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `competition_id`  INT UNSIGNED NOT NULL,
  `title`           VARCHAR(200) NOT NULL,
  `title_fr`        VARCHAR(300) NULL DEFAULT NULL,
  `title_en`        VARCHAR(300) NULL DEFAULT NULL,
  `kind`            VARCHAR(20)  NOT NULL DEFAULT 'announcement' COMMENT 'announcement | form | admitted | results | other',
  `file`            VARCHAR(255) NOT NULL,
  `file_size`       INT          NOT NULL DEFAULT 0,
  `downloads_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `order`           INT          NOT NULL DEFAULT 0,
  `uploaded_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_competition_files_competition` (`competition_id`, `order`),
  CONSTRAINT `fk_competition_files_competition` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- طلبات الترشح الإلكتروني (معطيات شخصية محمية)
-- ---------------------------------------------------------------------
CREATE TABLE `competition_applications` (
  `id`                INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `competition_id`    INT UNSIGNED      NOT NULL,
  `post_id`           INT UNSIGNED      NULL,
  `post_title`        VARCHAR(200)      NOT NULL DEFAULT '' COMMENT 'اسم الرتبة يوم الترشح (يبقى حتى لو عُدّلت الرتبة)',
  `tracking_code`     VARCHAR(20)       NOT NULL COMMENT 'رقم المتابعة: عشوائي غير متسلسل',
  `secret_hash`       VARCHAR(255)      NOT NULL COMMENT 'بصمة الرمز السري (password_hash) — الرمز نفسه لا يُحفظ',
  `nin_hash`          CHAR(64)          NOT NULL COMMENT 'HMAC لرقم التعريف الوطني (الرقم نفسه مشفّر داخل personal_data)',
  `last_name`         VARCHAR(60)       NOT NULL,
  `first_name`        VARCHAR(60)       NOT NULL,
  `last_name_latin`   VARCHAR(60)       NOT NULL,
  `first_name_latin`  VARCHAR(60)       NOT NULL,
  `gender`            CHAR(1)           NOT NULL COMMENT 'M | F',
  `diploma`           VARCHAR(150)      NOT NULL,
  `specialty`         VARCHAR(150)      NOT NULL,
  `graduation_year`   SMALLINT UNSIGNED NOT NULL,
  `experience_years`  TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  `personal_data`     TEXT              NOT NULL COMMENT 'مشفّرة AES-256-GCM: رقم التعريف، تاريخ ومكان الميلاد، العنوان، الولاية، الهاتف، البريد، الخدمة الوطنية',
  `status`            VARCHAR(20)       NOT NULL DEFAULT 'pending' COMMENT 'pending | under_review | incomplete | accepted | rejected',
  `candidate_message` TEXT              NULL COMMENT 'رسالة للمترشح تظهر في صفحة متابعة الطلب',
  `internal_notes`    TEXT              NULL COMMENT 'ملاحظات داخلية للموظفين فقط',
  `submitted_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at`       DATETIME          NULL,
  `reviewed_by_id`    INT UNSIGNED      NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_competition_applications_tracking` (`tracking_code`),
  UNIQUE KEY `uq_competition_applications_nin` (`competition_id`, `nin_hash`),
  KEY `idx_competition_applications_status` (`competition_id`, `status`, `submitted_at`),
  CONSTRAINT `fk_competition_applications_competition` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_competition_applications_post` FOREIGN KEY (`post_id`) REFERENCES `competition_posts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_competition_applications_reviewer` FOREIGN KEY (`reviewed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- وثائق المترشحين: ملفات مشفّرة بأسماء عشوائية في storage/private/recruitment/<رقم المسابقة>/
CREATE TABLE `competition_application_files` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `requirement_id` INT UNSIGNED NULL,
  `label`          VARCHAR(200) NOT NULL COMMENT 'اسم الوثيقة المطلوبة يوم الترشح',
  `original_name`  VARCHAR(150) NOT NULL,
  `stored_name`    CHAR(36)     NOT NULL COMMENT '32 حرفاً عشوائياً + .enc',
  `size`           INT UNSIGNED NOT NULL,
  `sha256`         CHAR(64)     NOT NULL COMMENT 'بصمة الملف الأصلي للتحقق من سلامته',
  `uploaded_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_competition_application_files_stored` (`stored_name`),
  KEY `idx_competition_application_files_application` (`application_id`),
  CONSTRAINT `fk_competition_application_files_application` FOREIGN KEY (`application_id`) REFERENCES `competition_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_competition_application_files_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `competition_requirements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- سجل الاطلاع: من فتح ملف أي مترشح، ومن نزّل وثائقه، ومن غيّر حالته، ومن صدّر القوائم
-- (بلا مفاتيح أجنبية عمداً: يبقى السجل حتى بعد حذف الطلبات أو الحسابات)
CREATE TABLE `recruitment_audit` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NULL COMMENT 'NULL = المترشح نفسه',
  `username`       VARCHAR(150) NOT NULL DEFAULT '',
  `action`         VARCHAR(30)  NOT NULL,
  `competition_id` INT UNSIGNED NULL,
  `application_id` INT UNSIGNED NULL,
  `details`        VARCHAR(500) NOT NULL DEFAULT '',
  `ip_address`     VARCHAR(45)  NOT NULL DEFAULT '',
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_recruitment_audit_application` (`application_id`, `created_at`),
  KEY `idx_recruitment_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
