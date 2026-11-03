-- =====================================================================
--  ترقية قسم «المسابقات» (2): إكمال الملف الناقص + الاستدعاءات + النتيجة الفردية
--
--  - جدول جديد competition_exams: مواعيد الاختبارات والمقابلات (يُستدعى إليها المترشحون)
--  - أعمدة جديدة في competition_applications:
--      complete_until / completed_at  ← المترشح يرفع الوثيقة الناقصة بنفسه قبل أجل يحدده الموظف
--      exam_id / seat_number / convoked_at ← الاستدعاء ورقم التسجيل
--      result / score / result_rank / result_note ← النتيجة الفردية لكل مترشح
--  - عمود جديد في competitions: results_published_at (النتائج لا تظهر للمترشحين قبل نشرها)
--  - عمود جديد في competition_application_files: added_later (وثيقة رفعها المترشح بعد الإرسال لإكمال ملفه)
--
--  طريقة التطبيق: phpMyAdmin ← قاعدتك ← Import ← هذا الملف (مرة واحدة، بعد 2026_11_recruitment.sql)
--  (لا تحتاجه إن استوردت schema.sql الجديد: كل هذا موجود فيه)
-- =====================================================================

SET NAMES utf8mb4;

-- مواعيد الاختبارات والمقابلات (فوج لكل موعد)
CREATE TABLE `competition_exams` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `competition_id`  INT UNSIGNED NOT NULL,
  `title`           VARCHAR(200) NOT NULL COMMENT 'مثل: المقابلة الشفهية — الفوج الأول',
  `title_fr`        VARCHAR(300) NULL DEFAULT NULL,
  `title_en`        VARCHAR(300) NULL DEFAULT NULL,
  `starts_at`       DATETIME     NOT NULL COMMENT 'تاريخ وساعة الاختبار',
  `place`           VARCHAR(300) NOT NULL DEFAULT '',
  `place_fr`        VARCHAR(500) NULL DEFAULT NULL,
  `place_en`        VARCHAR(500) NULL DEFAULT NULL,
  `instructions`    VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'تعليمات تظهر في الاستدعاء',
  `instructions_fr` VARCHAR(800) NULL DEFAULT NULL,
  `instructions_en` VARCHAR(800) NULL DEFAULT NULL,
  `order`           INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_competition_exams_competition` (`competition_id`, `starts_at`),
  CONSTRAINT `fk_competition_exams_competition` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `competitions`
  ADD COLUMN `results_published_at` DATETIME NULL COMMENT 'وقت نشر النتائج الفردية للمترشحين (NULL = غير منشورة)';

ALTER TABLE `competition_applications`
  ADD COLUMN `complete_until` DATE              NULL COMMENT 'آخر أجل ليرفع المترشح الوثائق الناقصة بنفسه',
  ADD COLUMN `completed_at`   DATETIME          NULL COMMENT 'آخر مرة أكمل فيها المترشح ملفه',
  ADD COLUMN `exam_id`        INT UNSIGNED      NULL COMMENT 'الاستدعاء: موعد الاختبار',
  ADD COLUMN `seat_number`    VARCHAR(20)       NULL COMMENT 'رقم التسجيل في الاختبار',
  ADD COLUMN `convoked_at`    DATETIME          NULL,
  ADD COLUMN `result`         VARCHAR(20)       NULL COMMENT 'passed | reserve | failed | absent',
  ADD COLUMN `score`          DECIMAL(5,2)      NULL COMMENT 'العلامة من 20',
  ADD COLUMN `result_rank`    SMALLINT UNSIGNED NULL COMMENT 'الترتيب',
  ADD COLUMN `result_note`    VARCHAR(500)      NULL COMMENT 'ملاحظة تظهر للمترشح مع النتيجة',
  ADD CONSTRAINT `fk_competition_applications_exam` FOREIGN KEY (`exam_id`) REFERENCES `competition_exams` (`id`) ON DELETE SET NULL;

ALTER TABLE `competition_application_files`
  ADD COLUMN `added_later` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'رفعها المترشح بعد الإرسال (إكمال ملف ناقص)';
