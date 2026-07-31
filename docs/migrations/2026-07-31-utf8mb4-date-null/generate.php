<?php
/**
 * 이 저장소의 코드를 쓰는 다른 사이트가 자기 DB 로 마이그레이션 SQL 을 만드는 스크립트.
 *
 * 옆에 있는 02·03 SQL 은 g5pro 의 테이블 목록이 박혀 있어 남의 DB 에서는 못 쓴다.
 * 이 스크립트는 information_schema 를 읽어 그 DB 에 맞는 문장을 새로 뽑는다.
 *
 *   php docs/migrations/2026-07-31-utf8mb4-date-null/generate.php > my-migration.sql
 *
 * 실행하지 않고 파일로만 뱉는다. 내용을 눈으로 확인하고, 백업을 뜬 뒤 직접 적용할 것.
 *
 *   mysqldump -u계정 -p DB명 > backup.sql
 *   mysql -u계정 -p DB명 < my-migration.sql
 *
 * 적용 후 config.php 의 G5_DB_CHARSET 을 'utf8mb4' 로 바꾸고 verify.php 를 돌린다.
 *
 * 참고: 옮기지 않아도 코드는 그대로 돌아간다. pro_sql_date() 가 컬럼을 보고
 * NOT NULL 이면 제로데이트를, NULL 을 받으면 NULL 을 넣기 때문이다.
 */

define('_GNUBOARD_', true);
include dirname(__DIR__, 3).'/data/dbconfig.php';

$db = new mysqli(G5_MYSQL_HOST === 'localhost' ? '127.0.0.1' : G5_MYSQL_HOST,
                 G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
if ($db->connect_error) { fwrite(STDERR, "DB 접속 실패: {$db->connect_error}\n"); exit(1); }
$schema = G5_MYSQL_DB;

echo "-- {$schema} 마이그레이션 (generate.php 생성)\n";
echo "-- 반드시 백업을 뜬 뒤 적용할 것.\n\n";

echo "-- ── 1. utf8mb4 ──\n";
echo "-- 콜레이션은 순정이 쓰는 utf8mb4_unicode_ci 에 맞춘다. 서버 기본과 섞이면 조인에서 충돌한다.\n";
echo "ALTER DATABASE `{$schema}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
$q = $db->query("select table_name from information_schema.tables
                 where table_schema = '{$schema}' and table_type = 'BASE TABLE' order by table_name");
$tables = 0;
while ($r = $q->fetch_row()) {
    echo "ALTER TABLE `{$r[0]}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    $tables++;
}

echo "\n-- ── 2. 제로데이트 ──\n";
echo "-- NOT NULL 인 date/datetime 을 NULL 허용으로 바꾸고 0000-00-00 값을 NULL 로 정리한다.\n";
echo "-- 기본키는 DB 가 NULL 을 거부하므로 건너뛴다.\n";
$q = $db->query("select table_name t, column_name c, column_type ct, data_type d, column_key k
                   from information_schema.columns
                  where table_schema = '{$schema}'
                    and data_type in ('date','datetime')
                  order by table_name, ordinal_position");
$alter = $upd = array();
while ($r = $q->fetch_assoc()) {
    $zero = $r['d'] === 'date' ? '0000-00-00' : '0000-00-00 00:00:00';
    if ($r['k'] !== 'PRI') {
        $alter[] = "ALTER TABLE `{$r['t']}` MODIFY COLUMN `{$r['c']}` {$r['ct']} NULL DEFAULT NULL;";
        $upd[]   = "UPDATE `{$r['t']}` SET `{$r['c']}` = NULL WHERE `{$r['c']}` = '{$zero}';";
    }
}
echo implode("\n", $alter), "\n\n", implode("\n", $upd), "\n";

fwrite(STDERR, "테이블 {$tables} 개, 날짜 컬럼 ".count($alter)." 개 분량을 생성했습니다.\n");
