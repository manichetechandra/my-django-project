-- =====================================================================
--  مديرية الخدمات الجامعية سطيف — بنية قاعدة بيانات MySQL
--  متوافق مع: MySQL 5.7+ / MySQL 8 / MariaDB 10.2+ (XAMPP)
--  الترميز: utf8mb4 (يدعم العربية والرموز التعبيرية)
--
--  طريقة الاستيراد (phpMyAdmin):
--    1) أنشئ قاعدة بيانات باسم dou_setif بترميز utf8mb4_unicode_ci
--    2) استورد هذا الملف ثم استورد data.sql
--  تنبيه: هذا الملف يحذف الجداول الموجودة بنفس الأسماء ثم يعيد إنشاءها.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+01:00';

DROP TABLE IF EXISTS `application_documents`;
DROP TABLE IF EXISTS `student_notifications`;
DROP TABLE IF EXISTS `student_applications`;
DROP TABLE IF EXISTS `application_types`;
DROP TABLE IF EXISTS `student_profiles`;
DROP TABLE IF EXISTS `site_statistics`;
DROP TABLE IF EXISTS `site_visits`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `faqs`;
DROP TABLE IF EXISTS `faq_categories`;
DROP TABLE IF EXISTS `download_files`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `departements`;
DROP TABLE IF EXISTS `cite_images`;
DROP TABLE IF EXISTS `cites`;
DROP TABLE IF EXISTS `activity_images`;
DROP TABLE IF EXISTS `activities`;
DROP TABLE IF EXISTS `latest_news`;
DROP TABLE IF EXISTS `news_images`;
DROP TABLE IF EXISTS `news`;
DROP TABLE IF EXISTS `directorate_histories`;
DROP TABLE IF EXISTS `organization_structures`;
DROP TABLE IF EXISTS `director_words`;
DROP TABLE IF EXISTS `statistics`;
DROP TABLE IF EXISTS `sliders`;
DROP TABLE IF EXISTS `users`;

-- ---------------------------------------------------------------------
-- المستخدمون (بديل auth_user)
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`     VARCHAR(150) NOT NULL,
  `password`     VARCHAR(255) NOT NULL,
  `email`        VARCHAR(254) NOT NULL DEFAULT '',
  `first_name`   VARCHAR(150) NOT NULL DEFAULT '',
  `last_name`    VARCHAR(150) NOT NULL DEFAULT '',
  `is_staff`     TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'يستطيع دخول لوحة الإدارة',
  `is_superuser` TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'كل الصلاحيات',
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  `last_login`   DATETIME     NULL,
  `date_joined`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- الصفحة الرئيسية
-- ---------------------------------------------------------------------
CREATE TABLE `sliders` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(200) NOT NULL,
  `title_fr`    VARCHAR(300) NULL DEFAULT NULL,
  `title_en`    VARCHAR(300) NULL DEFAULT NULL,
  `subtitle`    VARCHAR(300) NOT NULL DEFAULT '',
  `subtitle_fr` VARCHAR(500) NULL DEFAULT NULL,
  `subtitle_en` VARCHAR(500) NULL DEFAULT NULL,
  `image`       VARCHAR(255) NOT NULL,
  `link`        VARCHAR(255) NULL,
  `button_text` VARCHAR(50)  NOT NULL DEFAULT '',
  `button_text_fr` VARCHAR(100) NULL DEFAULT NULL,
  `button_text_en` VARCHAR(100) NULL DEFAULT NULL,
  `order`       INT          NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `statistics` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `icon`      VARCHAR(50)  NOT NULL,
  `number`    INT          NOT NULL,
  `label`     VARCHAR(100) NOT NULL,
  `label_fr`  VARCHAR(200) NULL DEFAULT NULL,
  `label_en`  VARCHAR(200) NULL DEFAULT NULL,
  `order`     INT          NOT NULL DEFAULT 0,
  `is_active` TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `director_words` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `director_name`  VARCHAR(200) NOT NULL,
  `director_name_fr` VARCHAR(300) NULL DEFAULT NULL,
  `director_name_en` VARCHAR(300) NULL DEFAULT NULL,
  `director_title` VARCHAR(200) NOT NULL DEFAULT 'مدير الخدمات الجامعية',
  `director_title_fr` VARCHAR(300) NULL DEFAULT NULL,
  `director_title_en` VARCHAR(300) NULL DEFAULT NULL,
  `director_image` VARCHAR(255) NULL,
  `word_title`     VARCHAR(200) NOT NULL DEFAULT 'كلمة المدير',
  `word_title_fr`  VARCHAR(300) NULL DEFAULT NULL,
  `word_title_en`  VARCHAR(300) NULL DEFAULT NULL,
  `word_content`   MEDIUMTEXT   NOT NULL,
  `word_content_fr` MEDIUMTEXT   NULL,
  `word_content_en` MEDIUMTEXT   NULL,
  `signature`      VARCHAR(255) NULL,
  `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `organization_structures` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`              VARCHAR(200) NOT NULL DEFAULT 'الهيكل التنظيمي',
  `title_fr`           VARCHAR(300) NULL DEFAULT NULL,
  `title_en`           VARCHAR(300) NULL DEFAULT NULL,
  `description`        TEXT         NOT NULL,
  `description_fr`     TEXT         NULL,
  `description_en`     TEXT         NULL,
  `organization_chart` VARCHAR(255) NULL,
  `content`            MEDIUMTEXT   NOT NULL,
  `content_fr`         MEDIUMTEXT   NULL,
  `content_en`         MEDIUMTEXT   NULL,
  `is_active`          TINYINT(1)   NOT NULL DEFAULT 1,
  `updated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `directorate_histories` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`         VARCHAR(200) NOT NULL DEFAULT 'تاريخ المديرية',
  `title_fr`      VARCHAR(300) NULL DEFAULT NULL,
  `title_en`      VARCHAR(300) NULL DEFAULT NULL,
  `introduction`  TEXT         NOT NULL,
  `introduction_fr` TEXT         NULL,
  `introduction_en` TEXT         NULL,
  `content`       MEDIUMTEXT   NOT NULL,
  `content_fr`    MEDIUMTEXT   NULL,
  `content_en`    MEDIUMTEXT   NULL,
  `main_image`    VARCHAR(255) NULL,
  `founding_year` INT          NULL,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- الأخبار
-- ---------------------------------------------------------------------
CREATE TABLE `news` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`          VARCHAR(300) NOT NULL,
  `title_fr`       VARCHAR(500) NULL DEFAULT NULL,
  `title_en`       VARCHAR(500) NULL DEFAULT NULL,
  `slug`           VARCHAR(300) NOT NULL,
  `summary`        TEXT         NOT NULL,
  `summary_fr`     TEXT         NULL,
  `summary_en`     TEXT         NULL,
  `content`        MEDIUMTEXT   NOT NULL,
  `content_fr`     MEDIUMTEXT   NULL,
  `content_en`     MEDIUMTEXT   NULL,
  `main_image`     VARCHAR(255) NULL,
  `author_id`      INT UNSIGNED NULL,
  `published_date` DATETIME     NULL,
  `status`         VARCHAR(20)  NOT NULL DEFAULT 'published' COMMENT 'draft | published | archived',
  `views_count`    INT          NOT NULL DEFAULT 0,
  `is_featured`    TINYINT(1)   NOT NULL DEFAULT 0,
  `show_in_ticker` TINYINT(1)   NOT NULL DEFAULT 0,
  `order`          INT          NOT NULL DEFAULT 0,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_news_slug` (`slug`),
  KEY `idx_news_status_date` (`status`, `published_date`),
  CONSTRAINT `fk_news_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `news_images` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `news_id`     INT UNSIGNED NOT NULL,
  `image`       VARCHAR(255) NOT NULL,
  `caption`     VARCHAR(300) NOT NULL DEFAULT '',
  `caption_fr`  VARCHAR(500) NULL DEFAULT NULL,
  `caption_en`  VARCHAR(500) NULL DEFAULT NULL,
  `order`       INT          NOT NULL DEFAULT 0,
  `uploaded_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_news_images_news` (`news_id`),
  CONSTRAINT `fk_news_images_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `latest_news` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `description` TEXT         NOT NULL,
  `description_fr` TEXT         NULL,
  `description_en` TEXT         NULL,
  `image`       VARCHAR(255) NOT NULL,
  `link`        VARCHAR(255) NULL,
  `order`       INT          NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- النشاطات
-- ---------------------------------------------------------------------
CREATE TABLE `activities` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`              VARCHAR(300) NOT NULL,
  `title_fr`           VARCHAR(500) NULL DEFAULT NULL,
  `title_en`           VARCHAR(500) NULL DEFAULT NULL,
  `slug`               VARCHAR(300) NOT NULL,
  `activity_type`      VARCHAR(20)  NOT NULL DEFAULT 'culture' COMMENT 'sport | culture | scientific | social | artistic | other',
  `short_description`  VARCHAR(500) NOT NULL,
  `short_description_fr` VARCHAR(800) NULL DEFAULT NULL,
  `short_description_en` VARCHAR(800) NULL DEFAULT NULL,
  `description`        MEDIUMTEXT   NOT NULL,
  `description_fr`     MEDIUMTEXT   NULL,
  `description_en`     MEDIUMTEXT   NULL,
  `main_image`         VARCHAR(255) NOT NULL DEFAULT '',
  `date`               DATE         NOT NULL,
  `location`           VARCHAR(300) NOT NULL,
  `location_fr`        VARCHAR(500) NULL DEFAULT NULL,
  `location_en`        VARCHAR(500) NULL DEFAULT NULL,
  `organizer`          VARCHAR(200) NOT NULL DEFAULT '',
  `organizer_fr`       VARCHAR(300) NULL DEFAULT NULL,
  `organizer_en`       VARCHAR(300) NULL DEFAULT NULL,
  `participants_count` INT          NOT NULL DEFAULT 0,
  `is_featured`        TINYINT(1)   NOT NULL DEFAULT 0,
  `is_active`          TINYINT(1)   NOT NULL DEFAULT 1,
  `views_count`        INT          NOT NULL DEFAULT 0,
  `order`              INT          NOT NULL DEFAULT 0,
  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_activities_slug` (`slug`),
  KEY `idx_activities_active_date` (`is_active`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `activity_images` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `activity_id` INT UNSIGNED NOT NULL,
  `image`       VARCHAR(255) NOT NULL,
  `caption`     VARCHAR(300) NOT NULL DEFAULT '',
  `caption_fr`  VARCHAR(500) NULL DEFAULT NULL,
  `caption_en`  VARCHAR(500) NULL DEFAULT NULL,
  `order`       INT          NOT NULL DEFAULT 0,
  `uploaded_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_images_activity` (`activity_id`),
  CONSTRAINT `fk_activity_images_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- الإقامات الجامعية
-- ---------------------------------------------------------------------
CREATE TABLE `cites` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(200) NOT NULL,
  `name_fr`     VARCHAR(300) NULL DEFAULT NULL,
  `name_en`     VARCHAR(300) NULL DEFAULT NULL,
  `slug`        VARCHAR(200) NOT NULL,
  `description` MEDIUMTEXT   NOT NULL,
  `description_fr` MEDIUMTEXT   NULL,
  `description_en` MEDIUMTEXT   NULL,
  `main_image`  VARCHAR(255) NOT NULL DEFAULT '',
  `address`     VARCHAR(300) NULL,
  `address_fr`  VARCHAR(500) NULL DEFAULT NULL,
  `address_en`  VARCHAR(500) NULL DEFAULT NULL,
  `capacity`    INT          NULL,
  `phone`       VARCHAR(20)  NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `order`       INT          NOT NULL DEFAULT 0,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cites_name` (`name`),
  UNIQUE KEY `uq_cites_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `cite_images` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cite_id`     INT UNSIGNED NOT NULL,
  `image`       VARCHAR(255) NOT NULL,
  `caption`     VARCHAR(200) NOT NULL DEFAULT '',
  `caption_fr`  VARCHAR(300) NULL DEFAULT NULL,
  `caption_en`  VARCHAR(300) NULL DEFAULT NULL,
  `order`       INT          NOT NULL DEFAULT 0,
  `uploaded_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cite_images_cite` (`cite_id`),
  CONSTRAINT `fk_cite_images_cite` FOREIGN KEY (`cite_id`) REFERENCES `cites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- المصالح / الأقسام
-- ---------------------------------------------------------------------
CREATE TABLE `departements` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`               VARCHAR(200) NOT NULL,
  `name_fr`            VARCHAR(300) NULL DEFAULT NULL,
  `name_en`            VARCHAR(300) NULL DEFAULT NULL,
  `slug`               VARCHAR(200) NOT NULL,
  `description`        MEDIUMTEXT   NOT NULL,
  `description_fr`     MEDIUMTEXT   NULL,
  `description_en`     MEDIUMTEXT   NULL,
  `image`              VARCHAR(255) NOT NULL DEFAULT '',
  `head_of_department` VARCHAR(200) NULL,
  `head_of_department_fr` VARCHAR(300) NULL DEFAULT NULL,
  `head_of_department_en` VARCHAR(300) NULL DEFAULT NULL,
  `email`              VARCHAR(254) NULL,
  `phone`              VARCHAR(20)  NULL,
  `website`            VARCHAR(255) NULL,
  `is_active`          TINYINT(1)   NOT NULL DEFAULT 1,
  `order`              INT          NOT NULL DEFAULT 0,
  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_departements_name` (`name`),
  UNIQUE KEY `uq_departements_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ---------------------------------------------------------------------
-- الخدمات
-- ---------------------------------------------------------------------
CREATE TABLE `services` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`                VARCHAR(200) NOT NULL,
  `title_fr`             VARCHAR(300) NULL DEFAULT NULL,
  `title_en`             VARCHAR(300) NULL DEFAULT NULL,
  `slug`                 VARCHAR(200) NOT NULL,
  `service_type`         VARCHAR(20)  NOT NULL COMMENT 'transport | housing | catering | scholarship',
  `short_description`    TEXT         NOT NULL,
  `short_description_fr` TEXT         NULL,
  `short_description_en` TEXT         NULL,
  `detailed_description` MEDIUMTEXT   NOT NULL,
  `detailed_description_fr` MEDIUMTEXT   NULL,
  `detailed_description_en` MEDIUMTEXT   NULL,
  `main_image`           VARCHAR(255) NULL,
  `icon`                 VARCHAR(50)  NOT NULL DEFAULT 'fa-info-circle',
  `requirements`         TEXT         NOT NULL,
  `requirements_fr`      TEXT         NULL,
  `requirements_en`      TEXT         NULL,
  `how_to_apply`         TEXT         NOT NULL,
  `how_to_apply_fr`      TEXT         NULL,
  `how_to_apply_en`      TEXT         NULL,
  `contact_info`         TEXT         NOT NULL,
  `contact_info_fr`      TEXT         NULL,
  `contact_info_en`      TEXT         NULL,
  `is_active`            TINYINT(1)   NOT NULL DEFAULT 1,
  `featured`             TINYINT(1)   NOT NULL DEFAULT 0,
  `display_order`        INT          NOT NULL DEFAULT 0,
  `created_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_services_slug` (`slug`),
  KEY `idx_services_type` (`service_type`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ---------------------------------------------------------------------
-- رسائل الاتصال
-- ---------------------------------------------------------------------
CREATE TABLE `contact_messages` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`   VARCHAR(100) NOT NULL,
  `email`       VARCHAR(254) NOT NULL,
  `phone`       VARCHAR(20)  NOT NULL DEFAULT '',
  `subject`     VARCHAR(20)  NOT NULL COMMENT 'general | transport | housing | catering | scholarship | complaint | suggestion | other',
  `message`     TEXT         NOT NULL,
  `is_read`     TINYINT(1)   NOT NULL DEFAULT 0,
  `is_replied`  TINYINT(1)   NOT NULL DEFAULT 0,
  `admin_notes` TEXT         NOT NULL,
  `ip_address`  VARCHAR(45)  NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- مركز التحميلات
-- ---------------------------------------------------------------------
CREATE TABLE `download_files` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`           VARCHAR(200) NOT NULL,
  `title_fr`        VARCHAR(300) NULL DEFAULT NULL,
  `title_en`        VARCHAR(300) NULL DEFAULT NULL,
  `slug`            VARCHAR(200) NOT NULL,
  `description`     TEXT         NOT NULL,
  `description_fr`  TEXT         NULL,
  `description_en`  TEXT         NULL,
  `category`        VARCHAR(30)  NOT NULL COMMENT 'scholarship_forms | housing_forms | transport_forms | catering_forms | guides | regulations | reports | other',
  `file`            VARCHAR(255) NOT NULL,
  `file_size`       INT          NOT NULL DEFAULT 0,
  `downloads_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
  `featured`        TINYINT(1)   NOT NULL DEFAULT 0,
  `display_order`   INT          NOT NULL DEFAULT 0,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_download_files_slug` (`slug`),
  KEY `idx_download_files_category` (`category`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- الأسئلة الشائعة
-- ---------------------------------------------------------------------
CREATE TABLE `faq_categories` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`      VARCHAR(100) NOT NULL,
  `name_fr`   VARCHAR(200) NULL DEFAULT NULL,
  `name_en`   VARCHAR(200) NULL DEFAULT NULL,
  `icon`      VARCHAR(50)  NOT NULL DEFAULT 'fa-question-circle',
  `order`     INT          NOT NULL DEFAULT 0,
  `is_active` TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `faqs` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `question`    VARCHAR(300) NOT NULL,
  `question_fr` VARCHAR(500) NULL DEFAULT NULL,
  `question_en` VARCHAR(500) NULL DEFAULT NULL,
  `answer`      TEXT         NOT NULL,
  `answer_fr`   TEXT         NULL,
  `answer_en`   TEXT         NULL,
  `order`       INT          NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_faqs_category` (`category_id`),
  CONSTRAINT `fk_faqs_category` FOREIGN KEY (`category_id`) REFERENCES `faq_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- شريط الإشعارات العامة أعلى الموقع
-- ---------------------------------------------------------------------
CREATE TABLE `notifications` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`             VARCHAR(200) NOT NULL,
  `title_fr`          VARCHAR(300) NULL DEFAULT NULL,
  `title_en`          VARCHAR(300) NULL DEFAULT NULL,
  `message`           TEXT         NOT NULL,
  `message_fr`        TEXT         NULL,
  `message_en`        TEXT         NULL,
  `notification_type` VARCHAR(10)  NOT NULL DEFAULT 'info' COMMENT 'info | success | warning | danger | primary',
  `link`              VARCHAR(255) NOT NULL DEFAULT '',
  `link_text`         VARCHAR(50)  NOT NULL DEFAULT 'المزيد',
  `link_text_fr`      VARCHAR(100) NULL DEFAULT NULL,
  `link_text_en`      VARCHAR(100) NULL DEFAULT NULL,
  `start_date`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_date`          DATETIME     NULL,
  `is_active`         TINYINT(1)   NOT NULL DEFAULT 1,
  `is_dismissible`    TINYINT(1)   NOT NULL DEFAULT 1,
  `priority`          INT          NOT NULL DEFAULT 0,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_active` (`is_active`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- إحصائيات الزوار
-- ---------------------------------------------------------------------
CREATE TABLE `site_visits` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address`   VARCHAR(45)  NOT NULL,
  `user_agent`   TEXT         NULL,
  `page_visited` VARCHAR(255) NULL,
  `visited_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_site_visits_ip_date` (`ip_address`, `visited_at`),
  KEY `idx_site_visits_date` (`visited_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_statistics` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `total_visitors`   INT UNSIGNED NOT NULL DEFAULT 0,
  `total_page_views` INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- فضاء الطالب
-- ---------------------------------------------------------------------
CREATE TABLE `student_profiles` (
  `id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`             INT UNSIGNED  NOT NULL,
  `registration_number` VARCHAR(20)   NOT NULL,
  `full_name_arabic`    VARCHAR(100)  NOT NULL,
  `birth_date`          DATE          NOT NULL,
  `birth_place`         VARCHAR(100)  NOT NULL,
  `gender`              CHAR(1)       NOT NULL COMMENT 'M | F',
  `phone`               VARCHAR(10)   NOT NULL,
  `address`             TEXT          NOT NULL,
  `wilaya`              VARCHAR(100)  NOT NULL,
  `university`          VARCHAR(200)  NOT NULL DEFAULT 'جامعة سطيف',
  `faculty`             VARCHAR(200)  NOT NULL,
  `department`          VARCHAR(200)  NOT NULL,
  `current_year`        VARCHAR(2)    NOT NULL COMMENT '1 | 2 | 3 | M1 | M2 | D',
  `average`             DECIMAL(4,2)  NULL,
  `photo`               VARCHAR(255)  NULL,
  `is_verified`         TINYINT(1)    NOT NULL DEFAULT 0,
  `is_active`           TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_profiles_user` (`user_id`),
  UNIQUE KEY `uq_student_profiles_regnum` (`registration_number`),
  CONSTRAINT `fk_student_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `application_types` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`               VARCHAR(100) NOT NULL,
  `name_fr`            VARCHAR(200) NULL DEFAULT NULL,
  `name_en`            VARCHAR(200) NULL DEFAULT NULL,
  `description`        TEXT         NOT NULL,
  `description_fr`     TEXT         NULL,
  `description_en`     TEXT         NULL,
  `icon`               VARCHAR(50)  NOT NULL DEFAULT 'fa-file-text',
  `color`              VARCHAR(7)   NOT NULL DEFAULT '#3498db',
  `required_documents` TEXT         NOT NULL,
  `required_documents_fr` TEXT         NULL,
  `required_documents_en` TEXT         NULL,
  `is_active`          TINYINT(1)   NOT NULL DEFAULT 1,
  `order`              INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `student_applications` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id`          INT UNSIGNED NOT NULL,
  `application_type_id` INT UNSIGNED NOT NULL,
  `application_number`  VARCHAR(20)  NOT NULL,
  `status`              VARCHAR(20)  NOT NULL DEFAULT 'pending' COMMENT 'pending | under_review | approved | rejected | cancelled',
  `details`             TEXT         NULL,
  `admin_notes`         TEXT         NULL,
  `submitted_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at`         DATETIME     NULL,
  `reviewed_by_id`      INT UNSIGNED NULL,
  `updated_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_applications_number` (`application_number`),
  KEY `idx_student_applications_student` (`student_id`, `submitted_at`),
  KEY `idx_student_applications_status` (`status`),
  KEY `idx_student_applications_submitted` (`submitted_at`),
  CONSTRAINT `fk_student_applications_student` FOREIGN KEY (`student_id`) REFERENCES `student_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_student_applications_type` FOREIGN KEY (`application_type_id`) REFERENCES `application_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_student_applications_reviewer` FOREIGN KEY (`reviewed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `application_documents` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `document_name`  VARCHAR(200) NOT NULL,
  `document_file`  VARCHAR(255) NOT NULL,
  `uploaded_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_application_documents_app` (`application_id`),
  CONSTRAINT `fk_application_documents_app` FOREIGN KEY (`application_id`) REFERENCES `student_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `student_notifications` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id`        INT UNSIGNED NOT NULL,
  `title`             VARCHAR(200) NOT NULL,
  `title_fr`          VARCHAR(300) NULL DEFAULT NULL,
  `title_en`          VARCHAR(300) NULL DEFAULT NULL,
  `message`           TEXT         NOT NULL,
  `message_fr`        TEXT         NULL,
  `message_en`        TEXT         NULL,
  `link`              VARCHAR(500) NULL,
  `notification_type` VARCHAR(20)  NOT NULL DEFAULT 'info' COMMENT 'info | success | warning | error',
  `is_read`           TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at`           DATETIME     NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_notifications_student` (`student_id`, `is_read`, `created_at`),
  CONSTRAINT `fk_student_notifications_student` FOREIGN KEY (`student_id`) REFERENCES `student_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
