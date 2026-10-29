<?php
// لف النصوص العربية الثابتة في كود PHP بـ __() (للمتحكمات)
//   php -d extension=tokenizer dev/wrap_php.php [--write] [--skip=file:line,...] files...
$write = false; $skip = []; $files = [];
foreach (array_slice($argv, 1) as $a) {
    if ($a === '--write') $write = true;
    elseif (str_starts_with($a, '--skip=')) $skip = explode(',', substr($a, 7));
    else $files[] = $a;
}
$noWrap = ['notify', 'error_log', '__', 't', 'js_t', 'RuntimeException', 'InvalidArgumentException', 'BadMethodCallException', 'exit', 'die'];
foreach ($files as $file) {
    $tokens = token_get_all(file_get_contents($file));
    $out = ''; $n = count($tokens); $changed = 0;
    for ($i = 0; $i < $n; $i++) {
        $t = $tokens[$i];
        $text = is_array($t) ? $t[1] : $t;
        if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING && preg_match('/\p{Arabic}/u', $t[1])) {
            $line = $t[2];
            $base = basename($file);
            // الدالة المحيطة
            $depth = 0; $fn = '';
            for ($j = $i - 1; $j >= 0; $j--) {
                $u = $tokens[$j];
                if ($u === ')' || $u === ']') $depth++;
                elseif ($u === '(' || $u === '[') {
                    if ($depth === 0) {
                        if ($u === '(') { $k = $j - 1; while ($k >= 0 && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) $k--; $fn = is_array($tokens[$k]) ? $tokens[$k][1] : ''; }
                        if ($fn !== '' || $u === '(') break;
                    } else $depth--;
                } elseif ($u === ';' || $u === '{' || $u === '}') break;
            }
            // هل هو جزء من وصل نصوص؟
            $prev = $i - 1; while ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_WHITESPACE) $prev--;
            $next = $i + 1; while ($next < $n && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) $next++;
            $concat = ($tokens[$prev] ?? '') === '.' || ($tokens[$next] ?? '') === '.';
            if (in_array("$base:$line", $skip, true) || in_array($fn, $noWrap, true)) {
                $out .= $text; continue;
            }
            if ($concat) {
                fwrite(STDERR, "$file:$line وصل نصوص (تُرك): $text\n");
                $out .= $text; continue;
            }
            $out .= '__(' . $text . ')'; $changed++;
            continue;
        }
        $out .= $text;
    }
    echo "$file: $changed\n";
    if ($write && $changed) file_put_contents($file, $out);
}
