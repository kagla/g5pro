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

// 확정된 예약 한 건 — 관리자 상세와 프론트 결제·완료 화면이 쓰는 예약 행 모양 그대로
$sample_bk = array(
    'bk_id' => 7, 'bk_no' => 'ABCD123456', 'br_id' => 3,
    'bk_checkin' => '2026-08-14', 'bk_checkout' => '2026-08-16', 'bk_person' => 3,
    'bk_name' => '홍길동', 'bk_hp' => '010-1234-5678', 'bk_email' => 'a@example.com',
    'bk_request' => "늦게 도착합니다.\n조용한 방으로 부탁드립니다.",
    'mb_id' => '', 'bk_room_price' => 300000, 'bk_person_price' => 20000,
    'bk_addon_price' => 40000, 'bk_total_price' => 360000,
    'bk_status' => 'confirmed', 'bk_hold_expire' => '2026-08-04 13:20:00',
    'bk_oid' => 'ABCD123456T175400000042', 'bk_tid' => 'StdpayCARDINIpayTest20260804132000',
    'bk_pay_time' => '2026-08-04 13:05:00', 'bk_datetime' => '2026-08-04 12:45:00',
);
$sample_addon_items = array(
    array('bt_subject' => '조식 2인', 'bt_price' => 20000, 'bt_qty' => 2, 'bt_amount' => 40000),
);

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
    'booking_list' => array(
        array('admin_url' => G5_ADMIN_URL, 'total_count' => 3,
            'status' => '', 'sdate' => '', 'edate' => '', 'stx' => '',
            'list' => array(
                // 미확인 요청 배지가 붙은 확정 건
                array('bk_id' => 7, 'bk_no' => 'ABCD123456', 'br_subject' => '디럭스 더블',
                    'bk_checkin' => '2026-08-14', 'bk_checkout' => '2026-08-16', 'nights' => 2,
                    'bk_name' => '홍길동', 'bk_hp' => '010-1234-5678', 'bk_person' => 3,
                    'bk_total_price' => 360000, 'bk_status' => 'confirmed', 'status_text' => '예약확정',
                    'new_note_cnt' => 2, 'bk_datetime' => '2026-08-04 12:45:00'),
                // 취소요청 — 배지 없음
                array('bk_id' => 8, 'bk_no' => 'EFGH789012', 'br_subject' => '스탠다드',
                    'bk_checkin' => '2026-09-01', 'bk_checkout' => '2026-09-02', 'nights' => 1,
                    'bk_name' => '김철수', 'bk_hp' => '010-9999-8888', 'bk_person' => 2,
                    'bk_total_price' => 120000, 'bk_status' => 'cancel_req', 'status_text' => '취소요청',
                    'new_note_cnt' => 0, 'bk_datetime' => '2026-08-01 09:00:00'),
                // 객실이 지워진 결제대기 건 (전체 필터에서만 보인다)
                array('bk_id' => 9, 'bk_no' => 'ZZZZ999999', 'br_subject' => '',
                    'bk_checkin' => '2026-07-01', 'bk_checkout' => '2026-07-02', 'nights' => 1,
                    'bk_name' => '이영희', 'bk_hp' => '', 'bk_person' => 1,
                    'bk_total_price' => 0, 'bk_status' => 'hold', 'status_text' => '결제대기',
                    'new_note_cnt' => 0, 'bk_datetime' => '2026-06-20 09:00:00'),
            )),
        // 빈 목록 + 필터가 걸린 분기 (검색어·기간·상태가 폼에 되비쳐야 한다)
        array('admin_url' => G5_ADMIN_URL, 'total_count' => 0, 'list' => array(),
            'status' => 'cancelled', 'sdate' => '2026-08-01', 'edate' => '2026-08-31', 'stx' => '홍길동'),
    ),
    'booking_view' => array(
        // 취소요청 — 승인·직권취소 버튼, 부가상품, 미확인 고객 요청, 환불 기록이 모두 있는 분기
        array('admin_url' => G5_ADMIN_URL,
            'bk' => array('bk_refund_plan_price' => 324000, 'bk_status' => 'cancel_req',
                'bk_cancel_memo' => '일정이 바뀌었습니다', 'bk_refund_price' => 0,
                'bk_ip' => '127.0.0.1', 'mb_id' => '') + $sample_bk,
            'br_subject' => '디럭스 더블', 'nights' => 2, 'status_text' => '취소요청',
            'addon_items' => $sample_addon_items,
            'notes' => array(
                array('bn_id' => 1, 'is_guest' => true, 'writer_text' => '고객',
                    'bn_content' => "수건 두 장만 더 부탁드립니다.\n감사합니다.",
                    'bn_checked' => 0, 'bn_datetime' => '2026-08-05 09:10:00'),
                array('bn_id' => 2, 'is_guest' => false, 'writer_text' => '업주(고객에게 보임)',
                    'bn_content' => '네, 준비해 두겠습니다.', 'bn_checked' => 1,
                    'bn_datetime' => '2026-08-05 10:00:00'),
            ),
            'refund_logs' => array(
                array('bl_type' => 'refund', 'type_text' => '환불', 'bl_price' => 324000,
                    'bl_result_code' => '01', 'ok' => false, 'bl_tid' => 'StdpayCARDINIpayTest0001',
                    'bl_datetime' => '2026-08-05 11:00:00'),
            ),
            'pay_time' => '2026-08-04 13:05:00', 'cancel_time' => '2026-08-05 08:00:00',
            'refund_time' => '', 'hold_expire' => '2026-08-04 13:20:00',
            'can_approve' => true, 'can_force' => true),
        // 결제대기 — 액션 없음, 메모·부가상품·거래기록 없음, 객실 삭제, 결제 정보 비어 있음
        array('admin_url' => G5_ADMIN_URL,
            'bk' => array('bk_status' => 'hold', 'bk_oid' => '', 'bk_tid' => '',
                'bk_request' => '', 'bk_email' => '', 'bk_person_price' => 0,
                'bk_addon_price' => 0, 'bk_total_price' => 300000, 'bk_refund_plan_price' => 0,
                'bk_refund_price' => 0, 'bk_cancel_memo' => '', 'bk_ip' => '', 'mb_id' => '') + $sample_bk,
            'br_subject' => '', 'nights' => 2, 'status_text' => '결제대기',
            'addon_items' => array(), 'notes' => array(), 'refund_logs' => array(),
            'pay_time' => '', 'cancel_time' => '', 'refund_time' => '',
            'hold_expire' => '2026-08-04 13:20:00',
            'can_approve' => false, 'can_force' => false),
        // 취소완료 — 환불액이 찍힌 분기 (회원 예약)
        array('admin_url' => G5_ADMIN_URL,
            'bk' => array('bk_status' => 'cancelled', 'mb_id' => 'testuser',
                'bk_refund_plan_price' => 360000, 'bk_refund_price' => 360000,
                'bk_cancel_memo' => '관리자 직권 취소', 'bk_ip' => '127.0.0.1') + $sample_bk,
            'br_subject' => '디럭스 더블', 'nights' => 2, 'status_text' => '취소완료',
            'addon_items' => $sample_addon_items, 'notes' => array(),
            'refund_logs' => array(
                array('bl_type' => 'netcancel', 'type_text' => '망취소', 'bl_price' => 360000,
                    'bl_result_code' => 'amount', 'ok' => false, 'bl_tid' => '',
                    'bl_datetime' => '2026-08-05 11:00:00'),
                array('bl_type' => 'refund', 'type_text' => '환불', 'bl_price' => 360000,
                    'bl_result_code' => '00', 'ok' => true, 'bl_tid' => 'StdpayCARDINIpayTest0001',
                    'bl_datetime' => '2026-08-05 11:05:00'),
            ),
            'pay_time' => '2026-08-04 13:05:00', 'cancel_time' => '2026-08-05 11:00:00',
            'refund_time' => '2026-08-05 11:05:00', 'hold_expire' => '',
            'can_approve' => false, 'can_force' => false),
        // 확정인데 거래번호가 없는 분기 — 결제대사로 안내하는 경고가 떠야 한다
        array('admin_url' => G5_ADMIN_URL,
            'bk' => array('bk_tid' => '', 'bk_refund_plan_price' => 0, 'bk_refund_price' => 0,
                'bk_cancel_memo' => '', 'bk_ip' => '127.0.0.1', 'mb_id' => '') + $sample_bk,
            'br_subject' => '디럭스 더블', 'nights' => 2, 'status_text' => '예약확정',
            'addon_items' => array(), 'notes' => array(), 'refund_logs' => array(),
            'pay_time' => '2026-08-04 13:05:00', 'cancel_time' => '', 'refund_time' => '',
            'hold_expire' => '', 'can_approve' => false, 'can_force' => true),
    ),
    'recon' => array(
        array('admin_url' => G5_ADMIN_URL,
            'unmatched' => array(
                // 조치할 수 있는 건 — 확정·환불 버튼이 둘 다 나온다
                array('bl_id' => 51, 'bl_oid' => 'ABCD123456T175400000042',
                    'bl_tid' => 'StdpayCARDINIpayTest0001', 'bl_price' => 360000,
                    'bl_datetime' => '2026-08-04 13:05:00', 'bk_id' => 7, 'bk_no' => 'ABCD123456',
                    'bk_status' => 'hold', 'status_text' => '결제대기', 'bk_name' => '홍길동',
                    'bk_hp' => '010-1234-5678', 'bk_total_price' => 360000,
                    'stay' => '2026-08-14 ~ 2026-08-16', 'br_subject' => '디럭스 더블', 'blocked' => ''),
                // 예약 행이 없는 건 — 버튼 없이 이유만
                array('bl_id' => 52, 'bl_oid' => 'NOSUCHOID0001', 'bl_tid' => 'StdpayCARDINIpayTest0002',
                    'bl_price' => 120000, 'bl_datetime' => '2026-08-03 10:00:00', 'bk_id' => 0,
                    'bk_no' => '', 'bk_status' => '', 'status_text' => '예약 없음', 'bk_name' => '',
                    'bk_hp' => '', 'bk_total_price' => 0, 'stay' => '', 'br_subject' => '',
                    'blocked' => '이 주문번호의 예약이 없습니다.'),
                // 금액이 어긋난 건 — 청구액을 함께 보여 주고 버튼은 막는다
                array('bl_id' => 53, 'bl_oid' => 'EFGH789012T175400000043', 'bl_tid' => 'StdpayCARDINIpayTest0003',
                    'bl_price' => 100000, 'bl_datetime' => '2026-08-02 10:00:00', 'bk_id' => 8,
                    'bk_no' => 'EFGH789012', 'bk_status' => 'hold', 'status_text' => '결제대기',
                    'bk_name' => '김철수', 'bk_hp' => '010-9999-8888', 'bk_total_price' => 120000,
                    'stay' => '2026-09-01 ~ 2026-09-02', 'br_subject' => '스탠다드',
                    'blocked' => '승인 금액과 예약 청구액이 다릅니다.'),
            ),
            'notid' => array(
                array('bk_id' => 11, 'bk_no' => 'IJKL345678', 'bk_oid' => '',
                    'bk_name' => '박영수', 'bk_hp' => '010-2222-3333', 'bk_total_price' => 200000,
                    'stay' => '2026-10-01 ~ 2026-10-03', 'br_subject' => '디럭스 더블', 'pay_time' => ''),
            )),
        // 대사할 건이 하나도 없는 분기
        array('admin_url' => G5_ADMIN_URL, 'unmatched' => array(), 'notid' => array()),
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

// 목록 화면의 관리자 바로가기 — booking/index.php 가 만드는 모양 그대로.
// 관리자가 아니어도 rooms 키는 객실마다 채워진다(뷰가 isset 없이 읽는다).
$front_rooms = array(
    $sample_room + array('image' => G5_DATA_URL.'/booking/aaaa.jpg'),
    array('br_id' => 4, 'br_subject' => '스탠다드', 'br_base_person' => 2,
        'br_max_person' => 2, 'br_weekday_price' => 80000, 'image' => ''),  // 이미지 없는 분기
);
function front_admin_links($rooms, $is_super)
{
    $links = array('booking' => $is_super ? G5_ADMIN_URL.'/booking/booking_list.php' : '',
        'rooms' => array());
    foreach ($rooms as $r) {
        $links['rooms'][$r['br_id']] = $is_super
            ? G5_ADMIN_URL.'/booking/room_form.php?w=u&br_id='.(int)$r['br_id'] : '';
    }
    return $links;
}

$front_samples = array(
    'index' => array(
        // 손님 — 관리자 링크가 모두 빈 문자열이라 톱니·예약관리 칸이 안 나가야 한다
        array('rooms' => $front_rooms, 'admin_links' => front_admin_links($front_rooms, false)),
        // 최고관리자 — 같은 목록에 톱니와 예약관리 바로가기가 붙는 분기
        array('rooms' => $front_rooms, 'admin_links' => front_admin_links($front_rooms, true)),
        // 빈 목록 분기 (객실이 없어도 관리자에게는 예약관리 칸이 남는다)
        array('rooms' => array(), 'admin_links' => front_admin_links(array(), true)),
    ),
    'room' => array(
        array('room' => $sample_room, 'conf' => $front_conf, 'js' => $front_js,
            'images' => array(G5_DATA_URL.'/booking/aaaa.jpg', G5_DATA_URL.'/booking/bbbb.png'),
            'addons' => array(array('ba_id' => 1, 'ba_subject' => '조식 2인', 'ba_price' => 20000)),
            'admin_edit_url' => ''),   // 손님 — 톱니가 안 나가야 한다
        // 사진·부가상품·취소규정·설명이 하나도 없고, 대신 최고관리자라 톱니가 붙는 분기
        array('room' => array('br_content' => '', 'br_person_price' => 0) + $sample_room,
            'conf' => array('cancel_rules' => array(), 'refund_terms' => '') + $front_conf,
            'js' => $front_js, 'images' => array(), 'addons' => array(),
            'admin_edit_url' => G5_ADMIN_URL.'/booking/room_form.php?w=u&br_id=3'),
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
    'pay' => array(
        // 부가상품이 붙은 결제 — 남은 시간이 넉넉한 분기
        array('bk' => $sample_bk, 'room' => $sample_room, 'nights' => 2,
            'addon_items' => $sample_addon_items,
            'oid' => 'ABCD123456T175400000042', 'left' => 1140,
            'conf' => array('mid' => 'INIpayTest',
                'js_url' => 'https://stgstdpay.inicis.com/stdjs/INIStdPay.js'),
            'return_url' => G5_URL.'/booking/inicis/return.php',
            'close_url'  => G5_URL.'/shop/inicis/close.php',
            'sign_url'   => G5_URL.'/booking/inicis/makesignature.php',
            'checkin_time' => '15:00', 'checkout_time' => '11:00'),
        // 부가상품·이메일이 없고 남은 시간이 1초뿐인 분기
        array('bk' => array('bk_email' => '', 'bk_addon_price' => 0,
                'bk_total_price' => 300000, 'bk_request' => '') + $sample_bk,
            'room' => $sample_room, 'nights' => 2, 'addon_items' => array(),
            'oid' => 'ABCD123456T175400000042', 'left' => 1,
            'conf' => array('mid' => 'INIpayTest',
                'js_url' => 'https://stgstdpay.inicis.com/stdjs/INIStdPay.js'),
            'return_url' => G5_URL.'/booking/inicis/return.php',
            'close_url'  => G5_URL.'/shop/inicis/close.php',
            'sign_url'   => G5_URL.'/booking/inicis/makesignature.php',
            'checkin_time' => '15:00', 'checkout_time' => '11:00'),
    ),
    'lookup' => array(
        // 회원 — 상태가 서로 다른 예약이 목록에 있는 분기
        array('is_member' => true, 'token' => '1754000000.abc', 'bookings' => array(
            array('bk_no' => 'ABCD123456', 'br_subject' => '디럭스 더블',
                'bk_checkin' => '2026-08-14', 'bk_checkout' => '2026-08-16', 'nights' => 2,
                'bk_person' => 3, 'bk_total_price' => 360000,
                'bk_status' => 'confirmed', 'status_text' => '예약확정',
                'bk_datetime' => '2026-08-04 12:45:00'),
            // 객실이 지워진 예약 — br_subject 가 빈 문자열로 온다
            array('bk_no' => 'ZZZZ999999', 'br_subject' => '',
                'bk_checkin' => '2026-07-01', 'bk_checkout' => '2026-07-02', 'nights' => 1,
                'bk_person' => 2, 'bk_total_price' => 120000,
                'bk_status' => 'cancelled', 'status_text' => '취소완료',
                'bk_datetime' => '2026-06-20 09:00:00'),
        )),
        array('is_member' => true, 'token' => '1754000000.abc', 'bookings' => array()),  // 빈 목록 분기
        array('is_member' => false, 'token' => '1754000000.abc', 'bookings' => array()), // 비회원 폼 분기
    ),
    'view' => array(
        // 확정 — 취소 가능·부가상품·요청사항·메모가 모두 있는 분기
        array('bk' => $sample_bk, 'room' => array('br_subject' => '디럭스 더블'),
            'addon_items' => $sample_addon_items, 'nights' => 2,
            'notes' => array(
                array('bn_writer' => 'guest', 'writer_text' => '고객',
                    'bn_content' => "수건 두 장만 더 부탁드립니다.\n감사합니다.",
                    'bn_datetime' => '2026-08-05 09:10:00'),
                array('bn_writer' => 'admin', 'writer_text' => '업주',
                    'bn_content' => '네, 준비해 두겠습니다.', 'bn_datetime' => '2026-08-05 10:00:00'),
            ),
            'pay_time' => '2026-08-04 13:05:00',
            'status_text' => '예약확정', 'can_cancel' => true, 'days_before' => 10,
            'refund_rate' => 100, 'refund_price' => 360000, 'token' => '1754000000.abc',
            'conf' => array('checkin_time' => '15:00', 'checkout_time' => '11:00',
                'refund_terms' => "체크인 7일 전까지 전액 환불합니다.")),
        // 취소완료 — 취소 폼·부가상품·요청사항·메모·결제일시·환불약관이 하나도 없는 분기
        array('bk' => array('bk_status' => 'cancelled', 'bk_request' => '',
                'bk_addon_price' => 0, 'bk_total_price' => 300000,
                'bk_pay_time' => '1970-01-01 00:00:00') + $sample_bk,
            'room' => array('br_subject' => ''),
            'addon_items' => array(), 'nights' => 2, 'notes' => array(), 'pay_time' => '',
            'status_text' => '취소완료', 'can_cancel' => false, 'days_before' => -3,
            'refund_rate' => 0, 'refund_price' => 0, 'token' => '1754000000.abc',
            'conf' => array('checkin_time' => '15:00', 'checkout_time' => '11:00',
                'refund_terms' => '')),
        // 취소요청 — 상태 칩의 세 번째 갈래
        array('bk' => array('bk_status' => 'cancel_req') + $sample_bk,
            'room' => array('br_subject' => '디럭스 더블'),
            'addon_items' => $sample_addon_items, 'nights' => 2, 'notes' => array(),
            'pay_time' => '2026-08-04 13:05:00',
            'status_text' => '취소요청', 'can_cancel' => false, 'days_before' => 10,
            'refund_rate' => 100, 'refund_price' => 360000, 'token' => '1754000000.abc',
            'conf' => array('checkin_time' => '15:00', 'checkout_time' => '11:00',
                'refund_terms' => '')),
    ),
    'complete' => array(
        // 비회원 — 메일·요청사항·부가상품·환불약관이 모두 있는 분기
        array('bk' => $sample_bk, 'room' => $sample_room, 'nights' => 2,
            'addon_items' => $sample_addon_items, 'is_member' => false,
            'conf' => array('checkin_time' => '15:00', 'checkout_time' => '11:00',
                'refund_terms' => "체크인 7일 전까지 전액 환불합니다.")),
        // 회원 — 메일·요청사항·부가상품·환불약관이 하나도 없는 분기
        array('bk' => array('bk_email' => '', 'bk_request' => '', 'bk_addon_price' => 0,
                'bk_total_price' => 300000) + $sample_bk,
            'room' => $sample_room, 'nights' => 2, 'addon_items' => array(), 'is_member' => true,
            'conf' => array('checkin_time' => '15:00', 'checkout_time' => '11:00',
                'refund_terms' => '')),
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
