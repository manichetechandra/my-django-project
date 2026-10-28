# أساسيات الواجهة: HTML وCSS وBootstrap وJavaScript

::: intro
كل صفحة يراها الزائر في موقعنا هي في النهاية ملف **HTML** يصله من الخادم، تزيّنه **CSS**، وتحرّكه **JavaScript**. في هذا الفصل نتعلّم من هذه اللغات ما نحتاجه بالضبط لبناء الموقع، مع أمثلة صغيرة تجرّبها بنفسك في مجلد `C:\xampp\htdocs\test`.
:::

## HTML: هيكل الصفحة

**HTML** ليست لغة برمجة بل لغة **وصف**: نصف بها محتوى الصفحة باستعمال **وسوم** (tags) تُكتب بين قوسين زاويين. أغلب الوسوم تأتي في زوج: وسم فتح `<p>` ووسم إغلاق `</p>`، وما بينهما هو المحتوى.

أنشئ الملف `C:\xampp\htdocs\test\page.html` واكتب فيه:

```html page.html
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>صفحتي الأولى</title>
</head>
<body>
    <h1>مديرية الخدمات الجامعية</h1>
    <p>مرحباً بكم في <strong>الموقع الرسمي</strong> للمديرية.</p>
    <a href="https://www.google.com">رابط إلى جوجل</a>
    <img src="logo.png" alt="شعار المديرية" width="120">
</body>
</html>
```

وافتحه على `http://localhost/test/page.html`. لنفهم كل سطر:

| السطر | معناه |
|---|---|
| `<!DOCTYPE html>` | يخبر المتصفح أن هذه صفحة HTML5 حديثة |
| `<html lang="ar" dir="rtl">` | بداية الصفحة؛ `lang="ar"` اللغة عربية، و`dir="rtl"` الاتجاه من اليمين إلى اليسار |
| `<head>` | معلومات عن الصفحة لا تظهر داخلها: الترميز، العنوان، ملفات التصميم |
| `<meta charset="utf-8">` | ترميز UTF-8 الذي يدعم العربية وكل لغات العالم |
| `<meta name="viewport" ...>` | ضروري لتظهر الصفحة بشكل صحيح على الهواتف |
| `<title>` | العنوان الذي يظهر في تبويب المتصفح ونتائج محركات البحث |
| `<body>` | كل ما يظهر للزائر |

### الخصائص (Attributes)

داخل وسم الفتح نكتب **خصائص** بالشكل `اسم="قيمة"`. مثلاً في `<a href="...">` الخاصية `href` هي عنوان الرابط، وفي `<img src="..." alt="...">` الخاصية `src` مسار الصورة و`alt` نص بديل يظهر إن لم تُحمَّل الصورة (ويقرؤه قارئ الشاشة للمكفوفين). وأهم خاصيتين على الإطلاق:

- `class="..."`: اسم «صنف» نستعمله في CSS وJavaScript، ويمكن أن يحمله أكثر من عنصر، وللعنصر أكثر من صنف مفصولة بمسافة: `class="btn btn-success"`.
- `id="..."`: معرّف **فريد** لعنصر واحد فقط في الصفحة.

### الوسوم التي سنستعملها كثيراً

| الوسم | الاستعمال |
|---|---|
| `<h1>` ... `<h6>` | العناوين من الأكبر إلى الأصغر |
| `<p>` | فقرة |
| `<a href>` | رابط |
| `<img src alt>` | صورة (وسم مفرد بلا إغلاق) |
| `<br>` و`<hr>` | سطر جديد، وخط أفقي فاصل |
| `<strong>` و`<small>` | نص عريض، ونص صغير |
| `<ul>` و`<ol>` و`<li>` | قائمة نقطية، قائمة مرقّمة، وعنصر القائمة |
| `<div>` | صندوق عام لتجميع العناصر (أكثر وسم ستراه!) |
| `<span>` | جزء صغير داخل سطر نص |
| `<i class="fa fa-...">` | نستعمله لعرض الأيقونات |
| `<table>` `<tr>` `<th>` `<td>` | جدول، صف، خلية عنوان، خلية |
| `<nav>` `<header>` `<footer>` `<section>` | وسوم «دلالية» تصف دور الجزء: قائمة تنقّل، رأس، تذييل، قسم |
| `<script>` و`<style>` و`<link>` | تضمين JavaScript وCSS |

### النماذج (Forms)

النماذج هي الطريقة التي يرسل بها الزائر بيانات إلى الخادم: تسجيل الدخول، رسالة «اتصل بنا»، تقديم طلب... هذا مثال قريب جداً مما سنكتبه لاحقاً:

```html form.html
<form method="post" action="/contact/" enctype="multipart/form-data">
    <label for="name">الاسم الكامل</label>
    <input type="text" id="name" name="name" required>

    <label for="email">البريد الإلكتروني</label>
    <input type="email" id="email" name="email">

    <label for="subject">الموضوع</label>
    <select id="subject" name="subject">
        <option value="general">استفسار عام</option>
        <option value="complaint">شكوى</option>
    </select>

    <label for="message">الرسالة</label>
    <textarea id="message" name="message" rows="5"></textarea>

    <input type="file" name="document">
    <button type="submit">إرسال</button>
</form>
```

- `method="post"`: طريقة الإرسال. **GET** تضع البيانات في العنوان (مثل `?q=منحة` في البحث)، و**POST** ترسلها مخفية داخل الطلب، وتُستعمل لكل ما يغيّر البيانات أو يحتوي كلمات مرور.
- `action`: العنوان الذي تُرسَل إليه البيانات. إن تُرك فارغاً تُرسل إلى الصفحة نفسها.
- `enctype="multipart/form-data"`: **ضروري** عندما يحتوي النموذج على رفع ملفات.
- `name="..."`: أهم خاصية في الحقل! هي الاسم الذي سيستقبل به PHP القيمة: `$_POST['email']`.
- `required`: يمنع المتصفح الإرسال إن كان الحقل فارغاً.

## CSS: تصميم الصفحة

**CSS** تحدّد شكل العناصر. القاعدة تتكون من **محدِّد** (أيّ العناصر؟) و**خصائص** (كيف تبدو؟):

```css
.news-card {
    background: #ffffff;        /* لون الخلفية */
    border-radius: 10px;        /* زوايا دائرية */
    padding: 20px;              /* مسافة داخلية */
    margin-bottom: 30px;        /* مسافة خارجية من الأسفل */
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);   /* ظل */
    transition: all 0.3s ease;  /* حركة ناعمة عند التغيّر */
}

.news-card:hover {
    transform: translateY(-5px);   /* ترتفع البطاقة قليلاً عند مرور الفأرة */
}
```

### أين نكتب CSS؟

1. داخل وسم `<style>` في رأس الصفحة (هذا ما يفعله موقعنا في القالب الرئيسي وفي كل صفحة لها تصميم خاص).
2. في ملف منفصل `.css` نربطه بـ `<link rel="stylesheet" href="style.css">` (هكذا نستعمل Bootstrap، وملف `admin.css` في لوحة الإدارة).
3. مباشرة على العنصر: `<div style="color: red">` (للحالات الصغيرة فقط).

### المحدِّدات الأساسية

| المحدِّد | يختار | مثال |
|---|---|---|
| `p` | كل وسوم `<p>` | `p { line-height: 1.8; }` |
| `.card` | كل عنصر له `class="card"` | `.card { padding: 20px; }` |
| `#main` | العنصر الذي `id="main"` | `#main { width: 80%; }` |
| `.card img` | الصور **داخل** `.card` | `.card img { width: 100%; }` |
| `.btn:hover` | الزر عند مرور الفأرة | `.btn:hover { opacity: .8; }` |
| `a, button` | الروابط **والأزرار** معاً | `a, button { cursor: pointer; }` |

### نموذج الصندوق (Box Model)

كل عنصر في الصفحة صندوق مكوّن من طبقات: **المحتوى** في الوسط، ثم **padding** (حشوة داخلية)، ثم **border** (الإطار)، ثم **margin** (هامش خارجي يبعده عن جيرانه). عندما تكتب `padding: 10px 20px` فالقيمة الأولى للأعلى والأسفل والثانية لليمين واليسار.

### الوحدات والألوان

- `px` بكسل ثابت، `%` نسبة من العنصر الأب، `em` و`rem` نسبة من حجم الخط، `vh` نسبة من ارتفاع الشاشة.
- الألوان: بالاسم `white`، أو بالنظام الست عشري `#2c3e50` (لون موقعنا الأساسي الكحلي)، `#8cc040` (اللون الأخضر المميز)، أو مع شفافية `rgba(0,0,0,.5)`.

### التخطيط بـ Flexbox

`display: flex` يرتّب العناصر الأبناء في صف واحد ويتحكم في المسافات بينها بسهولة. سنراه كثيراً في لوحة الإدارة وبطاقة الطالب:

```css
.toolbar {
    display: flex;
    justify-content: space-between;   /* توزيع العناصر على العرض */
    align-items: center;              /* محاذاة عمودية في الوسط */
    gap: 10px;                        /* مسافة بين العناصر */
}
```

### الحركات (Animations)

```css
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
}
.section-title { animation: fadeInUp 0.8s ease; }
```

`@keyframes` تعرّف الحركة خطوة بخطوة، ثم نطبّقها على أي عنصر بـ `animation`.

### التصميم المتجاوب (Media Queries)

```css
@media (max-width: 768px) {
    .carousel-caption h3 { font-size: 18px; }   /* عنوان أصغر على الهواتف */
}
```

القواعد داخل `@media (max-width: 768px)` لا تُطبَّق إلا إذا كان عرض الشاشة 768 بكسل أو أقل.

## Bootstrap 3: مكتبة تصميم جاهزة

بدل أن نصمّم كل زر وقائمة من الصفر نستعمل **Bootstrap**: ملف CSS (وملف JavaScript) فيه مئات الأصناف الجاهزة. يكفي أن نضيف الصنف المناسب للعنصر. يستعمل موقعنا الإصدار **3.4.1**، ونضمّنه من الإنترنت (CDN) بسطرين:

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
```

::: note
ملف JavaScript الخاص بـ Bootstrap 3 يحتاج مكتبة **jQuery**، لذلك يجب أن يأتي وسم jQuery **قبله** دائماً.
:::

### نظام الشبكة (Grid)

أهم ما في Bootstrap: يقسم عرض الصفحة إلى **12 عموداً** وهمياً، وأنت تختار كم عموداً يأخذ كل عنصر:

```html
<div class="container">          <!-- حاوية بعرض مناسب ومتمركزة -->
    <div class="row">            <!-- صف -->
        <div class="col-md-4 col-sm-6">خبر 1</div>
        <div class="col-md-4 col-sm-6">خبر 2</div>
        <div class="col-md-4 col-sm-6">خبر 3</div>
    </div>
</div>
```

`col-md-4` تعني: على الشاشات المتوسطة فأكبر يأخذ العنصر 4 من 12 (أي الثلث، فتصطف 3 أخبار). و`col-sm-6`: على الشاشات الصغيرة يأخذ النصف (خبران في الصف). وعلى الهاتف (أقل من 768 بكسل) يأخذ كل خبر العرض كاملاً تلقائياً. أحجام الشاشات: `xs` هاتف، `sm` لوحي، `md` حاسوب، `lg` شاشة كبيرة.

### مكوّنات سنستعملها

| الصنف | النتيجة |
|---|---|
| `btn btn-success` / `btn-primary` / `btn-danger` / `btn-default` | أزرار بألوان مختلفة |
| `btn-lg` / `btn-sm` / `btn-block` | زر كبير / صغير / بعرض كامل |
| `alert alert-success` / `alert-danger` / `alert-warning` / `alert-info` | صناديق الرسائل |
| `panel panel-default` + `panel-heading` + `panel-body` | لوحة بعنوان ومحتوى |
| `form-group` + `form-control` | تنسيق حقول النماذج |
| `table table-striped table-hover` | جدول منسّق |
| `label label-success` / `badge` | شارة صغيرة ملوّنة |
| `navbar navbar-inverse navbar-fixed-top` | شريط تنقّل داكن مثبّت أعلى الصفحة |
| `carousel slide` | السلايدر (سنبنيه في الفصل 13) |
| `text-center` / `pull-left` / `hidden-xs` | محاذاة، تعويم، إخفاء على الهاتف |

### Bootstrap والعربية

Bootstrap 3 مصمَّم أصلاً للغات التي تُكتب من اليسار. موقعنا يحلّ ذلك بقواعد CSS خاصة في القالب الرئيسي تقلب الاتجاه (`direction: rtl` وتعويم الأعمدة إلى اليمين)، وسنراها في الفصل 12.

## Font Awesome: الأيقونات

مكتبة **Font Awesome 4.7** توفّر أكثر من 600 أيقونة على شكل خط. نضمّنها بسطر واحد:

```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
```

ثم نستعمل أي أيقونة بوسم `<i>`:

```html
<i class="fa fa-home"></i> الرئيسية
<i class="fa fa-newspaper-o"></i> الأخبار
<i class="fa fa-graduation-cap fa-2x"></i>   <!-- fa-2x: ضعف الحجم -->
<i class="fa fa-spinner fa-spin"></i>        <!-- أيقونة تدور -->
```

قائمة كل الأيقونات وأسمائها موجودة على موقع `fontawesome.com/v4/icons`.

## JavaScript: التفاعل في المتصفح

**JavaScript** لغة برمجة حقيقية تعمل **داخل المتصفح** (على عكس PHP التي تعمل على الخادم). نستعملها لكل ما يتغيّر دون إعادة تحميل الصفحة: تحريك السلايدر، عدّاد الإحصائيات، فتح القوائم، جلب الإشعارات الجديدة كل 30 ثانية...

```html js-demo.html
<button id="btn">اضغطني</button>
<p id="result"></p>

<script>
    // متغيّر ثابت يحمل عنصر الزر
    const btn = document.getElementById('btn');
    let count = 0;                         // متغيّر يمكن تغييره

    // دالة تُنفَّذ عند الضغط على الزر
    btn.addEventListener('click', function () {
        count++;
        document.getElementById('result').textContent = 'عدد الضغطات: ' + count;
    });

    // تنفيذ كود كل ثانيتين
    setInterval(function () {
        console.log('مرّت ثانيتان');       // تظهر في تبويب Console (F12)
    }, 2000);
</script>
```

أهم المفاهيم:

- **المتغيرات**: `const` لقيمة لا تتغير، و`let` لقيمة تتغير.
- **الدوال**: `function name(params) { ... }` أو الشكل المختصر `(x) => x * 2`.
- **DOM**: تمثيل الصفحة ككائنات. نختار العناصر بـ `document.getElementById('id')` أو `document.querySelector('.class')`.
- **الأحداث**: `addEventListener('click', ...)` لتنفيذ كود عند الضغط، و`'submit'` عند إرسال نموذج، و`'DOMContentLoaded'` عند اكتمال تحميل الصفحة.
- **fetch**: إرسال طلب إلى الخادم في الخلفية (AJAX) واستقبال الرد بصيغة **JSON** دون إعادة تحميل الصفحة. هكذا تعمل إشعارات الطالب.

```javascript
fetch('/students/api/notifications/')
    .then(response => response.json())      // تحويل الرد إلى كائن
    .then(data => {
        console.log(data.unread_count);     // استعمال البيانات
    });
```

### JSON

**JSON** صيغة نصية لتبادل البيانات بين الخادم والمتصفح، تشبه مصفوفات PHP:

```json
{ "unread_count": 3, "notifications": [ { "id": 7, "title": "تم قبول طلبك" } ] }
```

## jQuery

**jQuery** مكتبة JavaScript قديمة لكنها ما زالت منتشرة، وBootstrap 3 يعتمد عليها. تختصر الكثير من الكود باستعمال الرمز `$`:

```javascript
$(document).ready(function () {           // عند اكتمال تحميل الصفحة
    $('.alert').fadeOut(5000);            // إخفاء الرسائل تدريجياً خلال 5 ثوانٍ
    $('#myCarousel').carousel({ interval: 5000 });   // تشغيل السلايدر
    $('.btn-delete').on('click', function () {
        return confirm('هل أنت متأكد من الحذف؟');   // نافذة تأكيد
    });
});
```

نضمّنها قبل Bootstrap:

```html
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
```

## تجربة شاملة: صفحة بـ Bootstrap

لنجمع كل ما سبق في صفحة صغيرة تشبه موقعنا. أنشئ `C:\xampp\htdocs\test\bootstrap.html`:

```html bootstrap.html
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تجربة Bootstrap</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { direction: rtl; text-align: right; padding-top: 70px; }
        .navbar-inverse { background: #2c3e50; border: none; }
        [class*="col-"] { float: right; }     /* الأعمدة من اليمين */
        .card { background: #fff; border-radius: 10px; padding: 20px;
                box-shadow: 0 5px 15px rgba(0,0,0,.1); margin-bottom: 20px; }
    </style>
</head>
<body style="background:#f5f7fa">
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fa fa-university"></i> المديرية</a>
        </div>
    </nav>

    <div class="container">
        <div class="alert alert-success"><i class="fa fa-check"></i> تم التحميل بنجاح</div>
        <div class="row">
            <div class="col-md-4"><div class="card"><h4>الإيواء</h4><p>خدمة السكن الجامعي.</p></div></div>
            <div class="col-md-4"><div class="card"><h4>المنحة</h4><p>المنحة الجامعية.</p></div></div>
            <div class="col-md-4"><div class="card"><h4>النقل</h4><p>النقل الجامعي.</p></div></div>
        </div>
        <button class="btn btn-success btn-lg" id="hello"><i class="fa fa-bell"></i> اضغط</button>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
    <script>
        $('#hello').on('click', function () { alert('مرحباً!'); });
    </script>
</body>
</html>
```

::: try
صغّر نافذة المتصفح تدريجياً، أو استعمل وضع الهاتف في أدوات المطوّر (F12)، ولاحظ كيف تنتقل البطاقات الثلاث من صف واحد إلى عمود واحد. هذا هو «التصميم المتجاوب» الذي يقدّمه نظام الشبكة.
:::

::: summary
- HTML يصف المحتوى بالوسوم والخصائص، وأهم الخصائص `class` و`id` و`name` في النماذج.
- CSS يصمّم العناصر بالمحدِّدات والخصائص، و`@media` للتصميم المتجاوب.
- Bootstrap 3 يوفّر شبكة 12 عموداً ومكوّنات جاهزة، ويحتاج jQuery.
- Font Awesome يوفّر الأيقونات بـ `<i class="fa fa-...">`.
- JavaScript يعمل في المتصفح للتفاعل، و`fetch` يجلب البيانات من الخادم بصيغة JSON.
:::
