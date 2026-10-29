<?php
// استعلام سريع على قاعدة الموقع التجريبي:  DB_NAME=dou_setif2 /tmp/tools/bin/php dev/sql.php "SELECT id, title FROM news"
$cfg = (require __DIR__ . '/../php/config.php')['db'];
$pdo = new PDO("mysql:host={$cfg['host']};port=" . ($cfg['port'] ?? 3306) . ";dbname={$cfg['name']};charset=utf8mb4", $cfg['user'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
foreach (array_slice($argv, 1) as $sql) {
    $st = $pdo->query($sql);
    if ($st->columnCount() === 0) { echo "OK ({$st->rowCount()})\n"; continue; }
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo implode(' | ', array_map(fn($v) => $v === null ? 'NULL' : mb_strimwidth(str_replace("\n", ' ', (string) $v), 0, 80, '…'), $row)), "\n";
    }
}
