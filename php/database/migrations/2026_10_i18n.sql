-- =====================================================================
--  ترقية قاعدة بيانات النسخة 1.0 إلى 2.0 (تعدد اللغات)
--  يضيف لكل نص يظهر للزوار عمودين: _fr للفرنسية و _en للإنجليزية.
--  الأعمدة فارغة (NULL) في البداية: يظهر النص العربي حتى تُضاف الترجمة
--  من لوحة الإدارة أو بأداة database/tools/translate_content.php
--
--  طريقة التطبيق: phpMyAdmin ← قاعدة dou_setif ← Import ← هذا الملف (مرة واحدة فقط)
--  (لا تحتاجه إن استوردت schema.sql الجديد: الأعمدة موجودة فيه)
-- =====================================================================

SET NAMES utf8mb4;

ALTER TABLE `sliders`
  ADD COLUMN `title_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `title`,
  ADD COLUMN `title_en` VARCHAR(300) NULL DEFAULT NULL AFTER `title_fr`,
  ADD COLUMN `subtitle_fr` VARCHAR(500) NULL DEFAULT NULL AFTER `subtitle`,
  ADD COLUMN `subtitle_en` VARCHAR(500) NULL DEFAULT NULL AFTER `subtitle_fr`,
  ADD COLUMN `button_text_fr` VARCHAR(100) NULL DEFAULT NULL AFTER `button_text`,
  ADD COLUMN `button_text_en` VARCHAR(100) NULL DEFAULT NULL AFTER `button_text_fr`;

ALTER TABLE `statistics`
  ADD COLUMN `label_fr` VARCHAR(200) NULL DEFAULT NULL AFTER `label`,
  ADD COLUMN `label_en` VARCHAR(200) NULL DEFAULT NULL AFTER `label_fr`;

ALTER TABLE `director_words`
  ADD COLUMN `director_name_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `director_name`,
  ADD COLUMN `director_name_en` VARCHAR(300) NULL DEFAULT NULL AFTER `director_name_fr`,
  ADD COLUMN `director_title_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `director_title`,
  ADD COLUMN `director_title_en` VARCHAR(300) NULL DEFAULT NULL AFTER `director_title_fr`,
  ADD COLUMN `word_title_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `word_title`,
  ADD COLUMN `word_title_en` VARCHAR(300) NULL DEFAULT NULL AFTER `word_title_fr`,
  ADD COLUMN `word_content_fr` MEDIUMTEXT NULL AFTER `word_content`,
  ADD COLUMN `word_content_en` MEDIUMTEXT NULL AFTER `word_content_fr`;

ALTER TABLE `organization_structures`
  ADD COLUMN `title_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `title`,
  ADD COLUMN `title_en` VARCHAR(300) NULL DEFAULT NULL AFTER `title_fr`,
  ADD COLUMN `description_fr` TEXT NULL AFTER `description`,
  ADD COLUMN `description_en` TEXT NULL AFTER `description_fr`,
  ADD COLUMN `content_fr` MEDIUMTEXT NULL AFTER `content`,
  ADD COLUMN `content_en` MEDIUMTEXT NULL AFTER `content_fr`;

ALTER TABLE `directorate_histories`
  ADD COLUMN `title_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `title`,
  ADD COLUMN `title_en` VARCHAR(300) NULL DEFAULT NULL AFTER `title_fr`,
  ADD COLUMN `introduction_fr` TEXT NULL AFTER `introduction`,
  ADD COLUMN `introduction_en` TEXT NULL AFTER `introduction_fr`,
  ADD COLUMN `content_fr` MEDIUMTEXT NULL AFTER `content`,
  ADD COLUMN `content_en` MEDIUMTEXT NULL AFTER `content_fr`;

ALTER TABLE `news`
  ADD COLUMN `title_fr` VARCHAR(500) NULL DEFAULT NULL AFTER `title`,
  ADD COLUMN `title_en` VARCHAR(500) NULL DEFAULT NULL AFTER `title_fr`,
  ADD COLUMN `summary_fr` TEXT NULL AFTER `summary`,
  ADD COLUMN `summary_en` TEXT NULL AFTER `summary_fr`,
  ADD COLUMN `content_fr` MEDIUMTEXT NULL AFTER `content`,
  ADD COLUMN `content_en` MEDIUMTEXT NULL AFTER `content_fr`;

ALTER TABLE `news_images`
  ADD COLUMN `caption_fr` VARCHAR(500) NULL DEFAULT NULL AFTER `caption`,
  ADD COLUMN `caption_en` VARCHAR(500) NULL DEFAULT NULL AFTER `caption_fr`;

ALTER TABLE `latest_news`
  ADD COLUMN `description_fr` TEXT NULL AFTER `description`,
  ADD COLUMN `description_en` TEXT NULL AFTER `description_fr`;

ALTER TABLE `activities`
  ADD COLUMN `title_fr` VARCHAR(500) NULL DEFAULT NULL AFTER `title`,
  ADD COLUMN `title_en` VARCHAR(500) NULL DEFAULT NULL AFTER `title_fr`,
  ADD COLUMN `short_description_fr` VARCHAR(800) NULL DEFAULT NULL AFTER `short_description`,
  ADD COLUMN `short_description_en` VARCHAR(800) NULL DEFAULT NULL AFTER `short_description_fr`,
  ADD COLUMN `description_fr` MEDIUMTEXT NULL AFTER `description`,
  ADD COLUMN `description_en` MEDIUMTEXT NULL AFTER `description_fr`,
  ADD COLUMN `location_fr` VARCHAR(500) NULL DEFAULT NULL AFTER `location`,
  ADD COLUMN `location_en` VARCHAR(500) NULL DEFAULT NULL AFTER `location_fr`,
  ADD COLUMN `organizer_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `organizer`,
  ADD COLUMN `organizer_en` VARCHAR(300) NULL DEFAULT NULL AFTER `organizer_fr`;

ALTER TABLE `activity_images`
  ADD COLUMN `caption_fr` VARCHAR(500) NULL DEFAULT NULL AFTER `caption`,
  ADD COLUMN `caption_en` VARCHAR(500) NULL DEFAULT NULL AFTER `caption_fr`;

ALTER TABLE `cites`
  ADD COLUMN `name_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `name`,
  ADD COLUMN `name_en` VARCHAR(300) NULL DEFAULT NULL AFTER `name_fr`,
  ADD COLUMN `description_fr` MEDIUMTEXT NULL AFTER `description`,
  ADD COLUMN `description_en` MEDIUMTEXT NULL AFTER `description_fr`,
  ADD COLUMN `address_fr` VARCHAR(500) NULL DEFAULT NULL AFTER `address`,
  ADD COLUMN `address_en` VARCHAR(500) NULL DEFAULT NULL AFTER `address_fr`;

ALTER TABLE `cite_images`
  ADD COLUMN `caption_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `caption`,
  ADD COLUMN `caption_en` VARCHAR(300) NULL DEFAULT NULL AFTER `caption_fr`;

ALTER TABLE `departements`
  ADD COLUMN `name_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `name`,
  ADD COLUMN `name_en` VARCHAR(300) NULL DEFAULT NULL AFTER `name_fr`,
  ADD COLUMN `description_fr` MEDIUMTEXT NULL AFTER `description`,
  ADD COLUMN `description_en` MEDIUMTEXT NULL AFTER `description_fr`,
  ADD COLUMN `head_of_department_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `head_of_department`,
  ADD COLUMN `head_of_department_en` VARCHAR(300) NULL DEFAULT NULL AFTER `head_of_department_fr`;

ALTER TABLE `services`
  ADD COLUMN `title_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `title`,
  ADD COLUMN `title_en` VARCHAR(300) NULL DEFAULT NULL AFTER `title_fr`,
  ADD COLUMN `short_description_fr` TEXT NULL AFTER `short_description`,
  ADD COLUMN `short_description_en` TEXT NULL AFTER `short_description_fr`,
  ADD COLUMN `detailed_description_fr` MEDIUMTEXT NULL AFTER `detailed_description`,
  ADD COLUMN `detailed_description_en` MEDIUMTEXT NULL AFTER `detailed_description_fr`,
  ADD COLUMN `requirements_fr` TEXT NULL AFTER `requirements`,
  ADD COLUMN `requirements_en` TEXT NULL AFTER `requirements_fr`,
  ADD COLUMN `how_to_apply_fr` TEXT NULL AFTER `how_to_apply`,
  ADD COLUMN `how_to_apply_en` TEXT NULL AFTER `how_to_apply_fr`,
  ADD COLUMN `contact_info_fr` TEXT NULL AFTER `contact_info`,
  ADD COLUMN `contact_info_en` TEXT NULL AFTER `contact_info_fr`;

ALTER TABLE `download_files`
  ADD COLUMN `title_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `title`,
  ADD COLUMN `title_en` VARCHAR(300) NULL DEFAULT NULL AFTER `title_fr`,
  ADD COLUMN `description_fr` TEXT NULL AFTER `description`,
  ADD COLUMN `description_en` TEXT NULL AFTER `description_fr`;

ALTER TABLE `faq_categories`
  ADD COLUMN `name_fr` VARCHAR(200) NULL DEFAULT NULL AFTER `name`,
  ADD COLUMN `name_en` VARCHAR(200) NULL DEFAULT NULL AFTER `name_fr`;

ALTER TABLE `faqs`
  ADD COLUMN `question_fr` VARCHAR(500) NULL DEFAULT NULL AFTER `question`,
  ADD COLUMN `question_en` VARCHAR(500) NULL DEFAULT NULL AFTER `question_fr`,
  ADD COLUMN `answer_fr` TEXT NULL AFTER `answer`,
  ADD COLUMN `answer_en` TEXT NULL AFTER `answer_fr`;

ALTER TABLE `notifications`
  ADD COLUMN `title_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `title`,
  ADD COLUMN `title_en` VARCHAR(300) NULL DEFAULT NULL AFTER `title_fr`,
  ADD COLUMN `message_fr` TEXT NULL AFTER `message`,
  ADD COLUMN `message_en` TEXT NULL AFTER `message_fr`,
  ADD COLUMN `link_text_fr` VARCHAR(100) NULL DEFAULT NULL AFTER `link_text`,
  ADD COLUMN `link_text_en` VARCHAR(100) NULL DEFAULT NULL AFTER `link_text_fr`;

ALTER TABLE `application_types`
  ADD COLUMN `name_fr` VARCHAR(200) NULL DEFAULT NULL AFTER `name`,
  ADD COLUMN `name_en` VARCHAR(200) NULL DEFAULT NULL AFTER `name_fr`,
  ADD COLUMN `description_fr` TEXT NULL AFTER `description`,
  ADD COLUMN `description_en` TEXT NULL AFTER `description_fr`,
  ADD COLUMN `required_documents_fr` TEXT NULL AFTER `required_documents`,
  ADD COLUMN `required_documents_en` TEXT NULL AFTER `required_documents_fr`;

ALTER TABLE `student_notifications`
  ADD COLUMN `title_fr` VARCHAR(300) NULL DEFAULT NULL AFTER `title`,
  ADD COLUMN `title_en` VARCHAR(300) NULL DEFAULT NULL AFTER `title_fr`,
  ADD COLUMN `message_fr` TEXT NULL AFTER `message`,
  ADD COLUMN `message_en` TEXT NULL AFTER `message_fr`;
