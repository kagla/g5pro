<?php
if (php_sapi_name() !== 'cli') die('CLI only');
$_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '80'; $_SERVER['SCRIPT_NAME'] = '/index.php';
// CLI php.ini 에 mysqli.default_socket 이 없어 소켓 경로를 직접 지정한다 (tools/seed_load_test.php 와 동일)
if (file_exists('/run/mysqld/mysqld.sock')) ini_set('mysqli.default_socket', '/run/mysqld/mysqld.sock');
include_once __DIR__.'/../../../common.php';

// 관리자 뷰 컴파일 스모크 — adm/booking/views 의 모든 .blade.php 를 대표 데이터로 렌더한다.
// 뷰가 늘면 $samples 에 케이스를 추가한다. 샘플이 없는 뷰는 FAIL 이므로 빠뜨릴 수 없다.
$views_dir = G5_ADMIN_PATH.'/booking/views';
// 운영 캐시(data/cache/pro/badm)는 웹서버 소유라 CLI 로 못 쓴다. 테스트는 제 캐시를 쓴다 —
// 매번 비우고 시작하므로 옛 컴파일 결과가 남아 실패를 가리는 일도 없다.
$cache_dir = sys_get_temp_dir().'/g5pro_badm_test';
if (!is_dir($cache_dir)) { @mkdir($cache_dir, 0777, true); }
foreach (glob($cache_dir.'/*') as $old) @unlink($old);
if (!is_dir($cache_dir) || !is_writable($cache_dir)) { echo "FAIL: 캐시 디렉터리를 못 만든다 ($cache_dir)\n"; exit(1); }

$sample_room = array(
    'br_id' => 3, 'br_subject' => '디럭스 더블', 'br_content' => "바다가 보이는 방\n2층",
    'br_base_person' => 2, 'br_max_person' => 4, 'br_person_price' => 10000,
    'br_room_count' => 5, 'br_weekday_price' => 120000, 'br_weekend_price' => 180000,
    'br_use' => 1, 'br_order' => 10, 'br_datetime' => '2026-08-01 10:00:00',
);
$empty_room = array(
    'br_id' => 0, 'br_subject' => '', 'br_content' => '',
    'br_base_person' => 2, 'br_max_person' => 4, 'br_person_price' => 0,
    'br_room_count' => 1, 'br_weekday_price' => 0, 'br_weekend_price' => 0,
    'br_use' => 1, 'br_order' => 0, 'br_datetime' => '',
);

// 캘린더 한 달치 샘플 — 한 칸씩 손으로 적는 대신 만들고, 특이 케이스만 몇 날에 심는다
// (15일=요금 지정, 20일=실수 0 인데 예약 1 → 초과, 21일=실수 1 에 예약 3 → 초과)
function sample_cal_days($ym, $last_day)
{
    $days = array();
    for ($d = 1; $d <= $last_day; $d++) {
        $date = $ym.'-'.sprintf('%02d', $d);
        $w = (int)date('w', strtotime($date));
        $sellable = ($d == 20) ? 0 : (($d == 21) ? 1 : 5);
        $booked = ($d == 21) ? 3 : 1;
        $days[] = array(
            'date' => $date, 'day' => $d, 'w' => $w,
            'price' => ($d == 15) ? 250000 : (($w === 5 || $w === 6) ? 180000 : 120000),
            'price_override' => ($d == 15),
            'sellable' => $sellable, 'count_override' => ($d == 20 || $d == 21),
            'booked' => $booked, 'remain' => $sellable - $booked,
            'oversold' => ($booked > $sellable),
        );
    }
    return $days;
}

function sample_cal_case($ym, $room)
{
    $last_day = (int)date('t', strtotime($ym.'-01'));
    $lead = (int)date('w', strtotime($ym.'-01'));
    return array(
        'admin_url' => G5_ADMIN_URL,
        'rooms' => $room ? array($room) : array(),
        'room' => $room,
        'ym' => $ym,
        'days' => $room ? sample_cal_days($ym, $last_day) : array(),
        'lead_blank' => $room ? $lead : 0,
        'tail_blank' => $room ? ((7 - ($lead + $last_day) % 7) % 7) : 0,
        'first_date' => $ym.'-01', 'last_date' => $ym.'-'.sprintf('%02d', $last_day),
        'prev_ym' => date('Y-m', strtotime($ym.'-01 -1 month')),
        'next_ym' => date('Y-m', strtotime($ym.'-01 +1 month')),
    );
}

// 뷰 이름 => 케이스 목록(각 케이스는 run() 에 넘길 데이터 배열)
$samples = array(
    'room_list' => array(
        array('admin_url' => G5_ADMIN_URL, 'rooms' => array(
            $sample_room + array('booking_cnt' => 4),
            array('br_id' => 4, 'br_subject' => '스탠다드', 'br_room_count' => 2,
                'br_base_person' => 2, 'br_max_person' => 2, 'br_weekday_price' => 80000,
                'br_weekend_price' => 100000, 'br_use' => 0, 'booking_cnt' => 0),
        )),
        array('admin_url' => G5_ADMIN_URL, 'rooms' => array()),   // 빈 목록 분기
    ),
    'addon_list' => array(
        array('admin_url' => G5_ADMIN_URL, 'addons' => array(
            array('ba_id' => 1, 'ba_subject' => '조식 2인', 'ba_price' => 20000,
                'ba_max_qty' => 4, 'ba_use' => 1, 'ba_order' => 0),
            array('ba_id' => 2, 'ba_subject' => '바비큐 세트', 'ba_price' => 50000,
                'ba_max_qty' => 2, 'ba_use' => 0, 'ba_order' => 10),
        )),
        array('admin_url' => G5_ADMIN_URL, 'addons' => array()),   // 빈 목록 분기
    ),
    'room_form' => array(
        array('w' => '', 'admin_url' => G5_ADMIN_URL,
            'room' => $empty_room, 'images' => array(), 'booking_cnt' => 0),
        array('w' => 'u', 'admin_url' => G5_ADMIN_URL,
            'room' => $sample_room, 'booking_cnt' => 4, 'images' => array(
                array('bi_id' => 11, 'br_id' => 3, 'bi_file' => 'aaaa.jpg', 'bi_order' => 0, 'bi_main' => 1),
                array('bi_id' => 12, 'br_id' => 3, 'bi_file' => 'bbbb.png', 'bi_order' => 1, 'bi_main' => 0),
            )),
    ),
    'calendar' => array(
        sample_cal_case('2026-08', $sample_room),   // 토요일 시작 — 앞칸 6개
        sample_cal_case('2026-02', $sample_room),   // 일요일 시작·28일 — 앞뒤 빈칸 0개
        sample_cal_case('2026-08', null),           // 객실이 하나도 없는 분기
    ),
);

$fail = 0;
$files = glob($views_dir.'/*.blade.php');
sort($files);
if (!$files) { echo "FAIL: $views_dir 에 뷰가 없다\n"; $fail++; }

foreach ($files as $file) {
    $view = basename($file, '.blade.php');
    if (!isset($samples[$view])) {
        echo "FAIL: $view 뷰의 샘플 데이터가 blade_test.php 에 없다\n"; $fail++; continue;
    }
    foreach ($samples[$view] as $case => $data) {
        // MODE_DEBUG — 캐시된 옛 컴파일 결과가 실패를 가리지 않게 매번 다시 컴파일한다
        $blade = new \eftec\bladeone\BladeOne($views_dir, $cache_dir, \eftec\bladeone\BladeOne::MODE_DEBUG);
        $notices = array();
        set_error_handler(function ($no, $msg, $f, $l) use (&$notices) { $notices[] = "$msg ($f:$l)"; return true; });
        try {
            $out = $blade->run($view, $data);
        } catch (\Throwable $e) {
            restore_error_handler();
            echo "FAIL: {$view}[{$case}] 렌더 예외 — ".get_class($e).': '.$e->getMessage()."\n"; $fail++; continue;
        }
        restore_error_handler();

        if ($notices) { echo "FAIL: {$view}[{$case}] 렌더 중 경고 — ".implode(' | ', $notices)."\n"; $fail++; }
        if (trim($out) === '') { echo "FAIL: {$view}[{$case}] 출력이 비었다\n"; $fail++; continue; }
        // 붙어 쓴 디렉티브는 컴파일되지 않고 그대로 새어 나온다 (BladeOne 함정)
        if (preg_match('/@(if|else|elseif|endif|foreach|endforeach|for|endfor|include|php|endphp|isset|empty|unset|json)\b/', $out, $m)) {
            echo "FAIL: {$view}[{$case}] 출력에 미컴파일 디렉티브 {$m[0]} 가 남았다\n"; $fail++;
        }
        if (strpos($out, '{{') !== false || strpos($out, '{!!') !== false) {
            echo "FAIL: {$view}[{$case}] 출력에 미컴파일 echo 태그가 남았다\n"; $fail++;
        }
    }
}

echo $fail ? "blade_test: $fail FAIL\n" : "blade_test: OK\n";
exit($fail ? 1 : 0);
