<?php
/**
 * باركود Code 128 بصيغة SVG يُولَّد داخل الموقع.
 * بديل خدمة bwipjs-api.metafloor.com الخارجية: كانت تستقبل رقم تسجيل كل طالب ورقم كل طلب
 * عند كل طباعة (تسريب بيانات شخصية لطرف ثالث)، وتتوقف الطباعة إن توقفت الخدمة.
 *
 *   <img src="<?= barcode_data_uri($student['registration_number']) ?>" alt="Barcode">
 */
final class Barcode
{
    /** أنماط رموز Code 128 الـ 107: الواحد خط أسود والصفر فراغ، وكل رمز 11 وحدة إلا التوقف ففيه 13 */
    private const BARS = [
        '11011001100', '11001101100', '11001100110', '10010011000', '10010001100', '10001001100', '10011001000', '10011000100',
        '10001100100', '11001001000', '11001000100', '11000100100', '10110011100', '10011011100', '10011001110', '10111001100',
        '10011101100', '10011100110', '11001110010', '11001011100', '11001001110', '11011100100', '11001110100', '11101101110',
        '11101001100', '11100101100', '11100100110', '11101100100', '11100110100', '11100110010', '11011011000', '11011000110',
        '11000110110', '10100011000', '10001011000', '10001000110', '10110001000', '10001101000', '10001100010', '11010001000',
        '11000101000', '11000100010', '10110111000', '10110001110', '10001101110', '10111011000', '10111000110', '10001110110',
        '11101110110', '11010001110', '11000101110', '11011101000', '11011100010', '11011101110', '11101011000', '11101000110',
        '11100010110', '11101101000', '11101100010', '11100011010', '11101111010', '11001000010', '11110001010', '10100110000',
        '10100001100', '10010110000', '10010000110', '10000101100', '10000100110', '10110010000', '10110000100', '10011010000',
        '10011000010', '10000110100', '10000110010', '11000010010', '11001010000', '11110111010', '11000010100', '10001111010',
        '10100111100', '10010111100', '10010011110', '10111100100', '10011110100', '10011110010', '11110100100', '11110010100',
        '11110010010', '11011011110', '11011110110', '11110110110', '10101111000', '10100011110', '10001011110', '10111101000',
        '10111100010', '11110101000', '11110100010', '10111011110', '10111101110', '11101011110', '11110101110', '11010000100',
        '11010010000', '11010011100', '1100011101011',
    ];
    private const START_B = 104;
    private const START_C = 105;
    private const STOP = 106;

    /** وحدات الباركود (سلسلة من 0 و 1) */
    public static function code128(string $text): string
    {
        $text = (string) preg_replace('/[^\x20-\x7E]/', '?', $text);
        $codes = [];
        if ($text !== '' && strlen($text) % 2 === 0 && ctype_digit($text)) {
            // المجموعة C: كل رقمين في رمز واحد (باركود أقصر)
            $codes[] = self::START_C;
            foreach (str_split($text, 2) as $pair) {
                $codes[] = (int) $pair;
            }
        } else {
            $codes[] = self::START_B;
            foreach (str_split($text) as $ch) {
                $codes[] = ord($ch) - 32;
            }
        }
        $sum = $codes[0];
        foreach (array_slice($codes, 1) as $i => $code) {
            $sum += ($i + 1) * $code;
        }
        $codes[] = $sum % 103;
        $codes[] = self::STOP;
        return implode('', array_map(fn($c) => self::BARS[$c], $codes));
    }

    /** صورة SVG: $scale بكسل لكل وحدة، $height ارتفاع الخطوط، مع النص تحتها اختيارياً */
    public static function svg(string $text, int $scale = 2, int $height = 60, bool $includeText = false): string
    {
        $modules = self::code128($text);
        $quiet = 10; // هامش أبيض على الجانبين يحتاجه القارئ
        $width = (strlen($modules) + 2 * $quiet) * $scale;
        $textHeight = $includeText ? 14 * $scale : 0;
        $rects = '';
        $len = strlen($modules);
        for ($i = 0; $i < $len; $i++) {
            if ($modules[$i] !== '1') {
                continue;
            }
            $run = 1;
            while ($i + $run < $len && $modules[$i + $run] === '1') {
                $run++;
            }
            $rects .= '<rect x="' . (($quiet + $i) * $scale) . '" y="0" width="' . ($run * $scale) . '" height="' . $height . '"/>';
            $i += $run - 1;
        }
        $label = $includeText
            ? '<text x="' . ($width / 2) . '" y="' . ($height + 12 * $scale) . '" font-family="monospace" font-size="' . (11 * $scale) . '" text-anchor="middle">'
                . htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</text>'
            : '';
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . ($height + $textHeight) . '" viewBox="0 0 ' . $width . ' ' . ($height + $textHeight) . '">'
            . '<rect width="100%" height="100%" fill="#fff"/><g fill="#000">' . $rects . '</g>' . $label . '</svg>';
    }
}

/** رابط data: لصورة الباركود (يعمل في الطباعة وبدون إنترنت) */
function barcode_data_uri($text, int $scale = 2, int $height = 60, bool $includeText = false): string
{
    return 'data:image/svg+xml;base64,' . base64_encode(Barcode::svg((string) $text, $scale, $height, $includeText));
}
