<?php
// النصوص العربية داخل كود PHP (وليس التعليقات) التي لم تُلف بعد بـ __() أو t()
//   /tmp/tools/bin/php dev/ar_literals.php php/app/controllers/*.php
foreach (array_slice($argv, 1) as $file) {
    $tokens = token_get_all(file_get_contents($file));
    $n = count($tokens);
    for ($i = 0; $i < $n; $i++) {
        $t = $tokens[$i];
        if (!is_array($t) || !in_array($t[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) continue;
        if (!preg_match('/[\x{0600}-\x{06FF}]/u', $t[1])) continue;
        // الدالة المحيطة: أقرب اسم دالة قبل القوس المفتوح
        $j = $i - 1; $depth = 0; $fn = '';
        for (; $j >= 0; $j--) {
            $u = $tokens[$j];
            if ($u === ')') $depth++;
            elseif ($u === '(') { if ($depth === 0) { $k = $j - 1; while ($k >= 0 && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) $k--; $fn = is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k]; break; } $depth--; }
            elseif ($u === ';' || $u === '{' || $u === '}') break;
        }
        if (in_array($fn, ['__', 't', 'js_t'], true)) continue;
        printf("%s:%d [%s] %s\n", $file, $t[2], $fn, mb_strimwidth(str_replace("\n", ' ', $t[1]), 0, 110, '…'));
    }
}
