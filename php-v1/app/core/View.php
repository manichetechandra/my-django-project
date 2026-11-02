<?php
/**
 * محرك قوالب بسيط بنفس فكرة Django: extends + block.
 *
 * في القالب الفرعي:
 *     <?php extend('base/base'); ?>
 *     <?php block('content'); ?> ... <?php endblock(); ?>
 *
 * في القالب الأساسي (layout) نفس block()/endblock() مع محتوى افتراضي.
 */
final class View
{
    public static ?string $layout = null;
    public static array $blocks = [];
    public static array $stack = [];
    public static bool $inLayout = false;

    public static function render(string $view, array $context = []): string
    {
        $prev = [self::$layout, self::$blocks, self::$stack, self::$inLayout];
        self::$layout = null;
        self::$blocks = [];
        self::$stack = [];
        self::$inLayout = false;
        try {
            $html = self::renderFile(view_path($view), $context);
            // سلسلة الوراثة (قالب يرث قالباً يرث آخر...)
            while (self::$layout !== null) {
                $layout = self::$layout;
                self::$layout = null;
                self::$inLayout = true;
                $html = self::renderFile(view_path($layout), $context);
            }
            return $html;
        } finally {
            [self::$layout, self::$blocks, self::$stack, self::$inLayout] = $prev;
        }
    }

    private static function renderFile(string $__file, array $__context): string
    {
        if (!is_file($__file)) {
            throw new RuntimeException('القالب غير موجود: ' . $__file);
        }
        extract($__context, EXTR_SKIP);
        $__level = ob_get_level();
        ob_start();
        try {
            include $__file;
        } catch (Throwable $e) {
            while (ob_get_level() > $__level) {
                ob_end_clean();
            }
            throw $e;
        }
        return ob_get_clean();
    }

    public static function startBlock(string $name): void
    {
        self::$stack[] = $name;
        ob_start();
    }

    public static function endBlock(): void
    {
        $name = array_pop(self::$stack);
        $content = ob_get_clean();
        if (self::$inLayout || self::$layout === null) {
            // داخل القالب الأساسي: نطبع محتوى الابن إن وُجد وإلا المحتوى الافتراضي
            echo self::$blocks[$name] ?? $content;
        } elseif (!isset(self::$blocks[$name])) {
            self::$blocks[$name] = $content;
        }
    }
}

function extend(string $layout): void
{
    View::$layout = $layout;
    View::$inLayout = false;
}

function block(string $name): void
{
    View::startBlock($name);
}

function endblock(): void
{
    View::endBlock();
}

/** محتوى كتلة معرّفة في القالب الفرعي (أو قيمة افتراضية) */
function block_content(string $name, string $default = ''): string
{
    return View::$blocks[$name] ?? $default;
}

function view_path(string $view): string
{
    return VIEW_PATH . '/' . $view . '.php';
}
