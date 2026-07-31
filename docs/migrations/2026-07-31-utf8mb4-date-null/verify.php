<?php
/**
 * utf8mb4 · 제로데이트 마이그레이션 회귀 검사
 *
 * upstream(그누보드 본체)을 병합한 뒤 반드시 한 번 돌린다.
 * 순정 파일을 되돌려 받으면 아래 항목이 조용히 원복되는데, 화면은 멀쩡해 보이고
 * 값만 틀리게 나오기 때문에 사람 눈으로는 잡히지 않는다.
 *
 *   php docs/migrations/2026-07-31-utf8mb4-date-null/verify.php
 *
 * 종료코드 0 = 이상 없음, 1 = 회귀 발견
 */

define('_GNUBOARD_', true);
$root = dirname(__DIR__, 3);
include $root.'/data/dbconfig.php';

$fail = 0;
function ok($label, $good, $detail = '') {
    global $fail;
    if (!$good) $fail++;
    printf("  [%s] %s%s\n", $good ? ' OK ' : 'FAIL', $label, $detail !== '' ? " — $detail" : '');
}

$db = new mysqli('127.0.0.1', G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB, 3306);
if ($db->connect_error) { echo "DB 접속 실패: {$db->connect_error}\n"; exit(1); }
$db->set_charset('utf8mb4');
$one = function ($sql) use ($db) { return $db->query($sql)->fetch_row()[0]; };

echo "\n== 1. 문자셋 ==\n";
$mb3 = $one("select count(*) from information_schema.columns
             where table_schema='".G5_MYSQL_DB."' and character_set_name like 'utf8mb3%'");
ok('utf8mb3 컬럼이 없다', $mb3 == 0, "$mb3 개");
ok("config.php 의 G5_DB_CHARSET 이 utf8mb4",
   (bool)preg_match("/G5_DB_CHARSET'\s*,\s*'utf8mb4'/", file_get_contents($root.'/config.php')));

echo "\n== 2. 이모지 왕복 ==\n";
$db->query("create temporary table _emoji_probe (v varchar(64)) charset=utf8mb4");
$s = $db->prepare("insert into _emoji_probe values (?)");
$emoji = "🎉🇰🇷👨‍👩‍👧‍👦";
$s->bind_param('s', $emoji);
$inserted = $s->execute();
$read = $inserted ? $one("select v from _emoji_probe") : '';
ok('4바이트 이모지가 그대로 저장된다', $read === $emoji, $inserted ? "읽은 값: $read" : $s->error);

echo "\n== 3. 제로데이트 ==\n";
$q = $db->query("select table_name t, column_name c, data_type d from information_schema.columns
                 where table_schema='".G5_MYSQL_DB."' and data_type in ('date','datetime')");
$zero = 0; $notnull = [];
while ($r = $q->fetch_assoc()) {
    $z = $r['d'] === 'date' ? '0000-00-00' : '0000-00-00 00:00:00';
    $zero += $one("select count(*) from `{$r['t']}` where `{$r['c']}` = '$z'");
}
ok('제로데이트 값이 한 행도 없다', $zero == 0, "$zero 행");
$def = $one("select count(*) from information_schema.columns
             where table_schema='".G5_MYSQL_DB."' and column_default like '%0000-00-00%'");
ok('제로데이트 DEFAULT 를 가진 컬럼이 없다', $def == 0, "$def 개");

// 기본키는 DB 가 NULL 을 허용하지 않으므로 예외로 둔다.
$nn = $db->query("select concat(table_name,'.',column_name) n, column_key k from information_schema.columns
                  where table_schema='".G5_MYSQL_DB."' and data_type in ('date','datetime') and is_nullable='NO'");
while ($r = $nn->fetch_assoc()) if ($r['k'] !== 'PRI') $notnull[] = $r['n'];
ok('기본키가 아닌 NOT NULL 날짜 컬럼이 없다', !$notnull, implode(', ', $notnull));

echo "\n== 4. 소스코드 ==\n";
// 헬퍼가 흡수하는 형태(주석·SQL 관용구)를 뺀 '살아있는' 제로데이트 비교만 센다.
// pro.10.extend.php 는 헬퍼가 사는 곳이라 옛 표현을 알아야 하므로 제외한다.
$cmd = "grep -rn \"0000-00-00\" --include=*.php --include=*.sql ".escapeshellarg($root)
     . " | grep -v /docs/ | grep -v pro.10.extend.php | grep -v pro_empty_date"
     // NULL 과 제로데이트를 함께 받는 관용 형태는 의도된 것이라 뺀다 (대소문자 무시)
     . " | grep -iv 'is not null and' | grep -iv 'is null or'";
exec($cmd, $lines);
ok('순정 코드에 제로데이트가 되살아나지 않았다', !$lines, count($lines).' 곳');
foreach (array_slice($lines, 0, 10) as $l) echo "        $l\n";

foreach (['pro_empty_date', 'pro_sql_date'] as $fn) {
    ok("헬퍼 $fn() 가 살아있다",
       (bool)preg_match("/function\s+$fn\s*\(/", file_get_contents($root.'/extend/pro.10.extend.php')));
}

echo "\n", $fail ? "회귀 {$fail} 건 — 위 FAIL 항목을 확인하세요.\n\n" : "이상 없음.\n\n";
exit($fail ? 1 : 0);
