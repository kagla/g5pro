<?php
if (php_sapi_name() !== 'cli') die('CLI only');
$_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '80'; $_SERVER['SCRIPT_NAME'] = '/index.php';
// CLI php.ini 에 mysqli.default_socket 이 없어 소켓 경로를 직접 지정한다 (tools/seed_load_test.php 와 동일)
if (file_exists('/run/mysqld/mysqld.sock')) ini_set('mysqli.default_socket', '/run/mysqld/mysqld.sock');
include_once __DIR__.'/../../../common.php';

// 뷰 컴파일 스모크 — 관리자(adm/booking/views)와 프론트(template/standard/booking)의
// 모든 .blade.php 를 대표 데이터로 렌더한다.
// 뷰가 늘면 $samples·$front_samples 에 케이스를 추가한다. 샘플이 없는 뷰는 FAIL 이므로 빠뜨릴 수 없다.
$views_dir = G5_ADMIN_PATH.'/booking/views';
// 운영 캐시(data/cache/pro/*)는 웹서버 소유라 CLI 로 못 쓴다. 테스트는 제 캐시를 쓴다 —
// 매번 비우고 시작하므로 옛 컴파일 결과가 남아 실패를 가리는 일도 없다.
function fresh_cache_dir($name)
{
    $dir = sys_get_temp_dir().'/'.$name;
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    foreach (glob($dir.'/*') as $old) @unlink($old);
    if (!is_dir($dir) || !is_writable($dir)) { echo "FAIL: 캐시 디렉터리를 못 만든다 ($dir)\n"; exit(1); }
    return $dir;
}
$cache_dir = fresh_cache_dir('g5pro_badm_test');
$front_cache_dir = fresh_cache_dir('g5pro_bfront_test');

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

$sample_config = array(
    'bc_id' => 1, 'bc_checkin_time' => '15:00', 'bc_checkout_time' => '11:00',
    'bc_hold_minutes' => 20, 'bc_open_months' => 6, 'bc_sameday_deadline' => '18:00',
    'bc_min_nights' => 1, 'bc_max_nights' => 7,
    'bc_cancel_policy' => "7:100\n3:50\n1:30\n0:0",
    'bc_refund_terms' => "체크인 7일 전까지 전액 환불합니다.\n이후에는 단계별 수수료가 붙습니다.",
    'bc_inicis_mid' => '', 'bc_inicis_sign_key' => '',
    'bc_inicis_iniapi_key' => '', 'bc_inicis_iniapi_iv' => '',
    'bc_card_test' => 1, 'bc_admin_email' => '',
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
    'config_form' => array(
        array('admin_url' => G5_ADMIN_URL, 'bc' => $sample_config),                       // 테스트 결제 켠 상태
        array('admin_url' => G5_ADMIN_URL, 'bc' => array('bc_card_test' => 0,
            'bc_inicis_mid' => 'realmid01', 'bc_inicis_sign_key' => 'SIGNKEY',
            'bc_inicis_iniapi_key' => 'APIKEY', 'bc_inicis_iniapi_iv' => 'APIIV',
            'bc_admin_email' => 'owner@example.com') + $sample_config),                   // 실 결제 분기
    ),
    'calendar' => array(
        sample_cal_case('2026-08', $sample_room),   // 토요일 시작 — 앞칸 6개
        sample_cal_case('2026-02', $sample_room),   // 일요일 시작·28일 — 앞뒤 빈칸 0개
        sample_cal_case('2026-08', null),           // 객실이 하나도 없는 분기
    ),
);

// ── 프론트 뷰 샘플 — template/standard/booking
// 뷰 루트는 template/booking 이 아니라 template/standard 다. 프론트 뷰는 layout.default 를
// 상속하고 partials 를 include 하므로 그 둘이 보이는 자리에서 그려야 운영과 같은 경로가 된다
// (운영은 g5_pro() 가 같은 루트를 쓴다 — extend/pro.10.extend.php).
$front_dir  = G5_PATH.'/template/standard/booking';
$front_root = G5_PATH.'/template/standard';

// 레이아웃이 요구하는 공통 키(site·menu·seo·jsonld …)는 실제 함수에서 받는다.
// 렌더 밖에서 한 번만 부른다 — 이 함수가 내는 경고까지 뷰 탓으로 세면 안 된다.
$common = g5_pro_common();

$front_conf = array(
    'checkin_time' => '15:00', 'checkout_time' => '11:00',
    'min_nights' => 1, 'max_nights' => 7, 'open_months' => 6,
    'refund_terms' => "체크인 7일 전까지 전액 환불합니다.\n이후에는 단계별 수수료가 붙습니다.",
    'cancel_rules' => array(7 => 100, 3 => 50, 1 => 30, 0 => 0),
);
$front_js = array(
    'br_id' => 3, 'ym' => '2026-08', 'limit_ym' => '2027-02', 'today' => '2026-08-04',
    'min_nights' => 1, 'max_nights' => 7, 'checkin_time' => '15:00', 'checkout_time' => '11:00',
    'ajax_url' => G5_URL.'/booking/ajax.calendar.php', 'reserve_url' => G5_URL.'/booking/reserve.php',
);

$front_samples = array(
    'index' => array(
        array('rooms' => array(
            $sample_room + array('image' => G5_DATA_URL.'/booking/aaaa.jpg'),
            array('br_id' => 4, 'br_subject' => '스탠다드', 'br_base_person' => 2,
                'br_max_person' => 2, 'br_weekday_price' => 80000, 'image' => ''),  // 이미지 없는 분기
        )),
        array('rooms' => array()),   // 빈 목록 분기
    ),
    'room' => array(
        array('room' => $sample_room, 'conf' => $front_conf, 'js' => $front_js,
            'images' => array(G5_DATA_URL.'/booking/aaaa.jpg', G5_DATA_URL.'/booking/bbbb.png'),
            'addons' => array(array('ba_id' => 1, 'ba_subject' => '조식 2인', 'ba_price' => 20000))),
        // 사진·부가상품·취소규정·설명이 하나도 없는 분기
        array('room' => array('br_content' => '', 'br_person_price' => 0) + $sample_room,
            'conf' => array('cancel_rules' => array(), 'refund_terms' => '') + $front_conf,
            'js' => $front_js, 'images' => array(), 'addons' => array()),
    ),
    'reserve' => array(
        // 비회원 — 비밀번호 칸이 나오고 부가상품·환불약관이 다 있는 분기
        array('room' => $sample_room, 'checkin' => '2026-08-14', 'checkout' => '2026-08-16',
            'nights' => 2, 'person' => 2, 'is_member' => false, 'token' => '1754000000.abc',
            'conf' => array('hold_minutes' => 20) + $front_conf,
            'guest' => array('name' => '', 'hp' => '', 'email' => ''),
            'addons' => array(
                array('ba_id' => 1, 'ba_subject' => '조식 2인', 'ba_price' => 20000, 'ba_max_qty' => 4),
                array('ba_id' => 2, 'ba_subject' => '바비큐 세트', 'ba_price' => 50000, 'ba_max_qty' => 2),
            ),
            'price' => array('room' => 300000, 'person' => 0, 'addon' => 0,
                'total' => 300000, 'addon_items' => array())),
        // 회원 — 비밀번호 칸이 없고 부가상품·인원추가요금·환불약관이 없는 분기
        array('room' => array('br_person_price' => 0) + $sample_room,
            'checkin' => '2026-08-14', 'checkout' => '2026-08-15',
            'nights' => 1, 'person' => 2, 'is_member' => true, 'token' => '1754000000.abc',
            'conf' => array('hold_minutes' => 20, 'refund_terms' => '') + $front_conf,
            'guest' => array('name' => '홍길동', 'hp' => '010-1234-5678', 'email' => 'a@example.com'),
            'addons' => array(),
            'price' => array('room' => 120000, 'person' => 0, 'addon' => 0,
                'total' => 120000, 'addon_items' => array())),
    ),
);

// 뷰 한 묶음을 대표 데이터로 렌더해 본다. $root 는 BladeOne 뷰 루트,
// $dir 는 글롭할 디렉터리(루트보다 아래일 수 있다), $prefix 는 그때 붙는 뷰 이름 접두사.
function blade_smoke($dir, $root, $cache_dir, $samples, $prefix, $extra)
{
    $fail = 0;
    $files = glob($dir.'/*.blade.php');
    sort($files);
    if (!$files) { echo "FAIL: $dir 에 뷰가 없다\n"; return 1; }

    foreach ($files as $file) {
        $view = basename($file, '.blade.php');
        if (!isset($samples[$view])) {
            echo "FAIL: {$prefix}{$view} 뷰의 샘플 데이터가 blade_test.php 에 없다\n"; $fail++; continue;
        }
        foreach ($samples[$view] as $case => $data) {
            $name = "{$prefix}{$view}[{$case}]";
            // MODE_DEBUG — 캐시된 옛 컴파일 결과가 실패를 가리지 않게 매번 다시 컴파일한다
            $blade = new \eftec\bladeone\BladeOne($root, $cache_dir, \eftec\bladeone\BladeOne::MODE_DEBUG);
            $notices = array();
            set_error_handler(function ($no, $msg, $f, $l) use (&$notices) { $notices[] = "$msg ($f:$l)"; return true; });
            try {
                $out = $blade->run($prefix.$view, array_merge($extra, $data));
            } catch (\Throwable $e) {
                restore_error_handler();
                echo "FAIL: $name 렌더 예외 — ".get_class($e).': '.$e->getMessage()."\n"; $fail++; continue;
            }
            restore_error_handler();

            if ($notices) { echo "FAIL: $name 렌더 중 경고 — ".implode(' | ', $notices)."\n"; $fail++; }
            if (trim($out) === '') { echo "FAIL: $name 출력이 비었다\n"; $fail++; continue; }
            // 붙어 쓴 디렉티브는 컴파일되지 않고 그대로 새어 나온다 (BladeOne 함정)
            if (preg_match('/@(if|else|elseif|endif|foreach|endforeach|for|endfor|include|php|endphp|isset|empty|unset|json)\b/', $out, $m)) {
                echo "FAIL: $name 출력에 미컴파일 디렉티브 {$m[0]} 가 남았다\n"; $fail++;
            }
            if (strpos($out, '{{') !== false || strpos($out, '{!!') !== false) {
                echo "FAIL: $name 출력에 미컴파일 echo 태그가 남았다\n"; $fail++;
            }
        }
    }
    return $fail;
}

$fail  = blade_smoke($views_dir, $views_dir, $cache_dir, $samples, '', array());
$fail += blade_smoke($front_dir, $front_root, $front_cache_dir, $front_samples, 'booking.', $common);

echo $fail ? "blade_test: $fail FAIL\n" : "blade_test: OK\n";
exit($fail ? 1 : 0);
