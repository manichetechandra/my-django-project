<?php
/**
 * خبر منسّق مؤقت للقطات الفصل 41 (المحرر في لوحة الإدارة والصفحة العامة) — ليس للقارئ.
 *   DB_NAME=dou_setif2 php tools/shots-rich.php add    ← يضيف الخبر 90 (slug = book-richtext)
 *   DB_NAME=dou_setif2 php tools/shots-rich.php del    ← يحذفه بعد اللقطات
 */
require dirname(__DIR__, 2) . '/php/app/bootstrap.php';

$id = 90;
DB::query('DELETE FROM news WHERE id = ?', [$id]);
if (($argv[1] ?? '') !== 'add') {
    echo "حُذف الخبر $id\n";
    exit;
}
$html = '<p>تعلن مديرية الخدمات الجامعية لولاية سطيف عن <strong>فتح باب إيداع ملفات الإيواء</strong> للموسم الجامعي 2026/2027، '
    . 'عبر <a href="/students/">فضاء الطالب</a>.</p>'
    . '<h2>الوثائق المطلوبة</h2>'
    . '<ul><li>شهادة التسجيل الجامعي أو شهادة النجاح في البكالوريا.</li><li>نسخة من بطاقة التعريف الوطنية.</li><li>صورتان شمسيتان.</li></ul>'
    . '<h2>رزنامة الإيداع</h2>'
    . '<table style="border-collapse: collapse; width: 100%;" border="1"><thead><tr><th>الفئة</th><th>من</th><th>إلى</th></tr></thead>'
    . '<tbody><tr><td>الطلبة الجدد</td><td>1 سبتمبر</td><td>15 سبتمبر</td></tr>'
    . '<tr><td>الطلبة القدامى</td><td>16 سبتمبر</td><td>30 سبتمبر</td></tr></tbody></table>'
    . '<p style="text-align: center;"><span style="color: #e03e2d;"><strong>تنبيه:</strong></span> لا يُقبل أي ملف بعد آخر أجل.</p>';
DB::query('INSERT INTO news (id, title, slug, summary, content, status, published_date, author_id) VALUES (?, ?, ?, ?, ?, ?, NOW(), NULL)', [
    $id,
    'رزنامة إيداع ملفات الإيواء للموسم الجامعي 2026/2027',
    'book-richtext',
    'فتح باب إيداع ملفات الإيواء عبر فضاء الطالب، مع الوثائق المطلوبة ورزنامة الإيداع.',
    RichText::normalize($html),
    'published',
]);
echo "أُضيف الخبر $id: /news/book-richtext/\n";
