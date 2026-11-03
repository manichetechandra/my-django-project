<?php
/**
 * بداية وثيقة رسمية للطباعة (الاستدعاء، إشعار النتيجة): ورقة A4 بترويسة رسمية.
 * المتغيرات: $doc_title (عنوان الوثيقة)، $app، $c
 */
?><!DOCTYPE html>
<html lang="<?= e(lang()) ?>" dir="<?= text_dir() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($doc_title) ?> — <?= e($app['tracking_code']) ?></title>
    <link rel="stylesheet" href="<?= asset('css/competitions-print.css') ?>">
</head>
<body>
<div class="doc-toolbar">
    <button type="button" onclick="window.print()" class="doc-btn doc-btn-primary">&#128438; <?= t('طباعة') ?></button>
    <a href="<?= e(url('competitions:track')) ?>" class="doc-btn"><?= t('رجوع إلى صفحة طلبي') ?></a>
    <span class="doc-tip"><?= t('نصيحة: اختر «حفظ بصيغة PDF» في نافذة الطباعة للاحتفاظ بنسخة.') ?></span>
</div>
<main class="doc-sheet">
    <header class="doc-head">
        <div class="doc-republic"><?= t('الجمهورية الجزائرية الديمقراطية الشعبية') ?></div>
        <div class="doc-head-row">
            <div class="doc-head-org">
                <div><?= t('وزارة التعليم العالي والبحث العلمي') ?></div>
                <div><?= t('الديوان الوطني للخدمات الجامعية') ?></div>
                <div class="doc-org-name"><?= t('مديرية الخدمات الجامعية سطيف') ?></div>
            </div>
            <img class="doc-logo" src="<?= asset('images/logo.svg') ?>" alt="" width="64" height="64">
        </div>
    </header>
    <div class="doc-meta">
        <span><?= t('رقم المتابعة') . colon() ?> <b dir="ltr"><?= e($app['tracking_code']) ?></b></span>
        <span><?= t('سطيف في: {date}', ['date' => format_date(date('Y-m-d'), 'j F Y')]) ?></span>
    </div>
    <h1 class="doc-title"><?= e($doc_title) ?></h1>
