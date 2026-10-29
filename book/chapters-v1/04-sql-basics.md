# أساسيات قواعد البيانات MySQL ولغة SQL

::: intro
أين تُحفظ الأخبار والطلبة والطلبات والرسائل؟ في **قاعدة بيانات**. في هذا الفصل نفهم كيف تُنظَّم البيانات في جداول، ونتعلّم لغة **SQL** التي نخاطب بها قاعدة البيانات، ثم نرى كيف يتصل PHP بها بطريقة آمنة. هذا الفصل تمهيد ضروري للفصل 6 الذي نصمّم فيه قاعدة بيانات الموقع كاملة.
:::

## ما هي قاعدة البيانات؟

قاعدة البيانات العلائقية مجموعة **جداول**، وكل جدول يشبه ورقة Excel:

- **الأعمدة** (columns): أنواع المعلومات، مثل العنوان والتاريخ وعدد المشاهدات.
- **الصفوف** (rows): السجلات نفسها، فكل خبر صف.

مثلاً جدول الأخبار `news`:

| id | title | status | views_count | published_date |
|---|---|---|---|---|
| 1 | افتتاح المكتبة الجديدة | published | 150 | 2025-11-20 10:00:00 |
| 2 | انطلاق التسجيلات في الإيواء | published | 320 | 2025-11-22 09:30:00 |
| 3 | خبر قيد التحرير | draft | 0 | NULL |

**MySQL** (ونسختها المتوافقة **MariaDB** الموجودة في XAMPP) هو البرنامج الذي يخزّن هذه الجداول وينفّذ أوامرنا عليها. ولغة **SQL** هي لغة هذه الأوامر.

## أنواع البيانات

كل عمود له نوع يحدد ما يقبله. هذه الأنواع التي يستعملها موقعنا:

| النوع | ماذا يخزّن | مثال من الموقع |
|---|---|---|
| `INT` | عدد صحيح (حتى حوالي 2 مليار) | `views_count`، `order` |
| `INT UNSIGNED` | عدد صحيح موجب فقط (ضعف المدى) | المعرّفات `id` |
| `TINYINT(1)` | عدد صغير؛ نستعمله لـ «نعم/لا» (1 أو 0) | `is_active`، `is_featured` |
| `VARCHAR(n)` | نص قصير بحد أقصى n حرف | `title VARCHAR(300)` |
| `TEXT` | نص طويل (حتى 64 كيلوبايت) | `summary`، `message` |
| `MEDIUMTEXT` | نص طويل جداً (حتى 16 ميغابايت) | `content` محتوى الخبر |
| `DATE` | تاريخ فقط `2025-11-20` | تاريخ ميلاد الطالب |
| `DATETIME` | تاريخ ووقت `2025-11-20 10:00:00` | `created_at` |
| `DECIMAL(10,2)` | عدد عشري دقيق | المبالغ المالية |
| `BIGINT` | عدد صحيح كبير جداً | عدّادات الزيارات |

::: note
لا نخزّن الصور والملفات داخل قاعدة البيانات، بل نحفظها في مجلد على القرص (`uploads/`) ونخزّن في الجدول **مسارها** فقط كنص، مثل `news/2025/11/library.jpg`.
:::

## قيود الأعمدة والمفاتيح

| القيد | معناه |
|---|---|
| `NOT NULL` | الحقل إجباري، لا يقبل «لا شيء» |
| `NULL` | الحقل اختياري |
| `DEFAULT قيمة` | القيمة التي توضع إن لم نحدّد قيمة عند الإضافة |
| `AUTO_INCREMENT` | يأخذ تلقائياً الرقم التالي: 1، 2، 3... |
| `PRIMARY KEY` | **المفتاح الأساسي**: معرّف فريد لكل صف (عندنا دائماً `id`) |
| `UNIQUE KEY` | لا يسمح بتكرار القيمة، مثل اسم المستخدم أو `slug` الخبر |
| `KEY` (فهرس) | يسرّع البحث والترتيب على هذا العمود، مثل فهرس الكتاب |
| `FOREIGN KEY` | **مفتاح أجنبي**: يربط الصف بصف في جدول آخر |

### العلاقات بين الجداول

صورة الخبر تنتمي إلى خبر معيّن. بدل تكرار بيانات الخبر مع كل صورة، نضع في جدول الصور عموداً `news_id` يحمل رقم الخبر:

جدول `news_images`:

| id | news_id | image |
|---|---|---|
| 10 | 1 | news/gallery/a.jpg |
| 11 | 1 | news/gallery/b.jpg |
| 12 | 2 | news/gallery/c.jpg |

الخبر رقم 1 له صورتان، والخبر رقم 2 له صورة واحدة. هذه علاقة **واحد إلى متعدد**. والمفتاح الأجنبي يضمن ألا نضيف صورة لخبر غير موجود، ويحدد ماذا يحدث عند حذف الخبر:

- `ON DELETE CASCADE`: احذف الصور معه تلقائياً.
- `ON DELETE SET NULL`: أبقِ الصف لكن اجعل الرابط `NULL` (مثلاً عند حذف كاتب الخبر يبقى الخبر بلا كاتب).

## الترميز: utf8mb4

لكي تُحفظ العربية سليمة يجب أن تكون قاعدة البيانات والجداول والاتصال كلها بترميز **utf8mb4**، مع **الترتيب** (collation) `utf8mb4_unicode_ci`. الترتيب يحدد كيف تُقارن النصوص وتُرتّب؛ و`ci` تعني أن المقارنة لا تفرّق بين الأحرف الكبيرة والصغيرة.

::: warning
تجنّب الترميز القديم `utf8` (بدون mb4) في MySQL: فهو لا يدعم إلا 3 بايتات للحرف، فيرفض الرموز التعبيرية وبعض الحروف. و`latin1` يحوّل العربية إلى علامات استفهام `????`.
:::

## لغة SQL خطوة بخطوة

افتح phpMyAdmin (`http://localhost/phpmyadmin`) واضغط تبويب **SQL** في الأعلى. هناك تكتب الأوامر وتضغط **Go** (تنفيذ). لنتدرّب على قاعدة بيانات تجريبية.

### إنشاء قاعدة بيانات وجدول

```sql
CREATE DATABASE test_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE test_db;

CREATE TABLE `news` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`          VARCHAR(300) NOT NULL,
  `status`         VARCHAR(20)  NOT NULL DEFAULT 'published',
  `views_count`    INT          NOT NULL DEFAULT 0,
  `published_date` DATETIME     NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- العلامة المائلة للخلف `` ` `` حول الأسماء اختيارية غالباً، لكنها ضرورية إذا كان الاسم كلمة محجوزة في SQL مثل `order` (وهو عمود موجود في عدة جداول عندنا!). لذلك نضعها دائماً.
- `ENGINE=InnoDB`: محرك التخزين الذي يدعم المفاتيح الأجنبية والمعاملات.
- كل أمر SQL ينتهي بفاصلة منقوطة.

### الإضافة: INSERT

```sql
INSERT INTO `news` (`title`, `views_count`, `published_date`)
VALUES ('افتتاح المكتبة الجديدة', 150, '2025-11-20 10:00:00');

-- عدة صفوف دفعة واحدة
INSERT INTO `news` (`title`, `status`) VALUES
  ('انطلاق التسجيلات', 'published'),
  ('خبر قيد التحرير', 'draft');
```

لم نحدد `id` لأنه `AUTO_INCREMENT`، ولا `status` في الأمر الأول فأخذ القيمة الافتراضية `published`. والسطر الذي يبدأ بـ `--` تعليق.

### القراءة: SELECT

```sql
SELECT * FROM `news`;                                   -- كل الأعمدة وكل الصفوف
SELECT `id`, `title` FROM `news`;                       -- أعمدة محددة
SELECT * FROM `news` WHERE `status` = 'published';      -- بشرط
SELECT * FROM `news` WHERE `status` = 'published' AND `views_count` > 100;
SELECT * FROM `news` ORDER BY `published_date` DESC;    -- ترتيب تنازلي (الأحدث أولاً)
SELECT * FROM `news` ORDER BY `id` DESC LIMIT 6;        -- أحدث 6 فقط
SELECT * FROM `news` LIMIT 10 OFFSET 20;                -- الصفحة الثالثة (10 في كل صفحة)
SELECT * FROM `news` WHERE `title` LIKE '%مكتبة%';      -- يحتوي كلمة «مكتبة»
SELECT * FROM `news` WHERE `id` IN (1, 3, 5);           -- ضمن قائمة
SELECT * FROM `news` WHERE `published_date` IS NULL;    -- القيم الفارغة (لا نستعمل = NULL)
```

| الجزء | دوره |
|---|---|
| `WHERE` | تصفية الصفوف بشرط؛ مع `AND` و`OR` و`NOT` |
| `ORDER BY ... ASC/DESC` | الترتيب تصاعدياً (الافتراضي) أو تنازلياً |
| `LIMIT n OFFSET m` | أخذ n صفاً بعد تخطّي m صفاً: أساس **تقسيم الصفحات** |
| `LIKE '%نص%'` | البحث الجزئي: `%` تعني «أي أحرف» |

### الدوال التجميعية

```sql
SELECT COUNT(*) FROM `news`;                              -- عدد الأخبار
SELECT SUM(`views_count`) FROM `news`;                    -- مجموع المشاهدات
SELECT `status`, COUNT(*) AS total FROM `news` GROUP BY `status`;   -- العدد لكل حالة
```

`GROUP BY` يجمع الصفوف المتشابهة في مجموعات، وهكذا نحسب في لوحة الموظفين عدد الطلبات لكل حالة. و`AS` تعطي العمود الناتج اسماً.

### الربط بين الجداول: JOIN

```sql
SELECT n.`title`, u.`username`
FROM `news` n
LEFT JOIN `users` u ON u.`id` = n.`author_id`;
```

يجلب عنوان كل خبر مع اسم كاتبه من جدول المستخدمين. `n` و`u` أسماء مختصرة للجدولين، و`LEFT JOIN` تُبقي الخبر حتى لو لم يكن له كاتب.

### التعديل والحذف: UPDATE وDELETE

```sql
UPDATE `news` SET `views_count` = `views_count` + 1 WHERE `id` = 1;
UPDATE `news` SET `status` = 'archived', `title` = 'عنوان جديد' WHERE `id` = 3;
DELETE FROM `news` WHERE `id` = 3;
```

::: warning
**لا تنسَ `WHERE`** مع UPDATE وDELETE! الأمر `DELETE FROM news;` بلا شرط يحذف **كل** الأخبار، و`UPDATE news SET title='x';` يغيّر عناوين كل الأخبار.
:::

### حذف جدول

```sql
DROP TABLE IF EXISTS `news`;     -- يحذف الجدول وكل بياناته
```

## phpMyAdmin بدون SQL

كل ما سبق يمكن فعله بالنقر أيضاً:

- **New** في القائمة اليسرى: إنشاء قاعدة بيانات (اختر الترتيب `utf8mb4_unicode_ci`).
- **Browse**: عرض صفوف الجدول وتعديلها أو حذفها.
- **Structure**: أعمدة الجدول وأنواعها وفهارسه.
- **Import**: استيراد ملف `.sql` (سنستورد به قاعدة بيانات الموقع).
- **Export**: تصدير نسخة احتياطية.

لكن تعلّم SQL ضروري لأن PHP لا يعرف النقر، بل يرسل أوامر SQL نصية.

## الاتصال من PHP: PDO

يوفّر PHP طريقة حديثة وآمنة للاتصال بقواعد البيانات اسمها **PDO**. أنشئ `C:\xampp\htdocs\test\db.php`:

```php db.php
<?php
$pdo = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=test_db;charset=utf8mb4',
    'root',     // اسم المستخدم
    '',         // كلمة المرور (فارغة في XAMPP)
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // الأخطاء ترمي استثناءات
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // الصفوف كمصفوفات ترابطية
    ]
);

// استعلام بدون مدخلات من المستخدم
$rows = $pdo->query('SELECT * FROM `news` ORDER BY `id` DESC')->fetchAll();
foreach ($rows as $row) {
    echo htmlspecialchars($row['title']) . '<br>';
}

// استعلام فيه مدخل من المستخدم: استعلام مُحضَّر
$stmt = $pdo->prepare('SELECT * FROM `news` WHERE `id` = ?');
$stmt->execute([$_GET['id'] ?? 0]);
$news = $stmt->fetch();          // صف واحد أو false
var_dump($news);
```

جرّب `http://localhost/test/db.php?id=1`.

- `fetchAll()` تُرجع كل الصفوف، و`fetch()` صفاً واحداً، و`fetchColumn()` قيمة واحدة (مفيدة مع `COUNT(*)`).
- `$pdo->lastInsertId()` تُرجع `id` آخر صف أُضيف.

## حقن SQL: أخطر ثغرة، وكيف نمنعها

لماذا لم نكتب ببساطة:

```php
$pdo->query("SELECT * FROM users WHERE username = '" . $_POST['username'] . "'");
```

لأن الزائر قد يكتب في خانة الاسم: `' OR '1'='1`. فيصبح الاستعلام:

```sql
SELECT * FROM users WHERE username = '' OR '1'='1'
```

والشرط صحيح دائماً، فيدخل المهاجم دون كلمة مرور! وقد يكتب أوامر تحذف الجداول. هذا هجوم **حقن SQL** (SQL Injection).

**الحل** هو الاستعلامات المُحضَّرة (prepared statements): نكتب علامة `?` (أو اسماً مثل `:username`) مكان القيمة، ونمرّر القيم منفصلة:

```php
$stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
$stmt->execute(['username' => $_POST['username']]);
```

هنا يتعامل MySQL مع القيمة دائماً على أنها **بيانات** وليست جزءاً من الأمر، مهما كتب الزائر.

::: why
في مشروعنا سنبني صنفاً صغيراً اسمه `DB` يغلّف PDO (الفصل 7)، وكل دواله تستعمل الاستعلامات المُحضَّرة تلقائياً. بهذا تصبح الطريقة الآمنة هي الطريقة الأسهل، فلا نقع في الخطأ سهواً.
:::

::: try
في phpMyAdmin أضف بعض الأخبار إلى `test_db.news`، ثم عدّل `db.php` ليعرض الأخبار المنشورة فقط مرتبة من الأحدث، مع عدد مشاهدات كل خبر. وعندما تنتهي احذف قاعدة البيانات التجريبية: `DROP DATABASE test_db;`.
:::

::: summary
- قاعدة البيانات جداول، والجدول أعمدة (لها أنواع) وصفوف.
- `PRIMARY KEY` يعرّف كل صف، و`FOREIGN KEY` يربط الجداول.
- الأوامر الأساسية: `CREATE TABLE`، `INSERT`، `SELECT` (مع `WHERE` و`ORDER BY` و`LIMIT`)، `UPDATE`، `DELETE`.
- نستعمل الترميز `utf8mb4` في كل مكان.
- نتصل من PHP بـ PDO، ونستعمل **دائماً** الاستعلامات المُحضَّرة لمنع حقن SQL.
:::
