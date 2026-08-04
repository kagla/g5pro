<?php
if (php_sapi_name() !== 'cli') die('CLI only');
$_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '80'; $_SERVER['SCRIPT_NAME'] = '/index.php';
// CLI php.ini 에 mysqli.default_socket 이 없어 소켓 경로를 직접 지정한다 (tools/seed_load_test.php 와 동일)
if (file_exists('/run/mysqld/mysqld.sock')) ini_set('mysqli.default_socket', '/run/mysqld/mysqld.sock');
include_once __DIR__.'/../../../common.php';
include_once G5_LIB_PATH.'/booking.lib.php';

booking_install();

$keys = array('booking_table','booking_room_table','booking_room_image_table','booking_calendar_table',
    'booking_addon_table','booking_addon_item_table','booking_note_table','booking_config_table',
    'booking_inicis_log_table');
$fail = 0;
foreach ($keys as $key) {
    if (!isset($g5[$key])) { echo "FAIL: \$g5['$key'] 상수 없음\n"; $fail++; continue; }
    if (!sql_query(" DESC `{$g5[$key]}` ", false)) { echo "FAIL: {$g5[$key]} 테이블 없음\n"; $fail++; continue; }
    $row = sql_fetch(" SHOW TABLE STATUS WHERE Name = '{$g5[$key]}' ");
    if (strtolower($row['Engine']) !== 'innodb') { echo "FAIL: {$g5[$key]} 엔진이 InnoDB 아님 ({$row['Engine']})\n"; $fail++; }
}
echo $fail ? "schema_test: $fail FAIL\n" : "schema_test: OK\n";
exit($fail ? 1 : 0);
