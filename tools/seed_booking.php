<?php
/**
 * 예약 모듈 데모 시드 (CLI 전용)
 *
 * 관리자 화면(객실·캘린더·예약 목록·결제대사)과 손님 화면(/booking/)이
 * 빈 껍데기가 아니라 "돌아가는 펜션"으로 보이도록 데모 데이터를 넣는다.
 *
 *   객실 20 · 객실당 사진 3~5장(외부에서 내려받음) · 부가상품 12
 *   · 앞으로 3개월 캘린더 오버라이드(성수기 요금 + 수리 기간)
 *   · 예약 40건(확정 30 · 취소요청 3 · 취소완료 4 · 만료 hold 3)
 *
 * 테스트 결제용 소액 가격
 *   실제 카드로 이니시스 테스트 결제를 해 보는 것이 목적이라 모든 금액을 100원 단위
 *   소액으로 둔다 — 1박 300~900원, 주말 +100~300원, 인원 추가 100~200원,
 *   부가상품 100~500원, 성수기 오버라이드는 기준가 ×1.5~2 를 100원 단위로 반올림.
 *   1000원 미만 결제는 이니시스 acceptmethod 에 below1000 이 있어야 승인된다
 *   (template/standard/booking/pay.blade.php 에 들어 있다).
 *
 * 시드 식별
 *   객실  : br_content 끝의 '[seed]' 마커
 *   부가상품: ba_order 900번대 + 아래 SEED_ADDONS 의 이름
 *   나머지: 시드 객실(br_id)에 매달린 행만
 *   → 기존 데이터(br_id=45 '테스트 객실', ba_id=36 '조식' 등)는 건드리지 않는다.
 *
 * 실행:  php tools/seed_booking.php
 *        php tools/seed_booking.php --wipe                 (시드만 역순 삭제 후 종료)
 *        php tools/seed_booking.php --room=45 --images=3   (그 객실에 사진만 붙이고 종료)
 *
 *   --room 은 시드와 무관한 일회성 보조 작업이다. 시드 마커를 붙이지 않으므로
 *   --wipe 로 지워지지 않는다 — 손으로 만든 객실의 빈 갤러리를 채울 때 쓴다.
 */

if (php_sapi_name() !== 'cli') exit("CLI 전용입니다.\n");

// common.php 는 웹 요청을 전제로 한다 — 최소한의 서버 변수를 채워 준다
// (tools/tests/booking/*_test.php 와 같은 부트스트랩)
$_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '80'; $_SERVER['SCRIPT_NAME'] = '/index.php';
// CLI php.ini 에 mysqli.default_socket 이 없어 소켓 경로를 직접 지정한다 (tools/seed_load_test.php 와 동일)
if (file_exists('/run/mysqld/mysqld.sock')) ini_set('mysqli.default_socket', '/run/mysqld/mysqld.sock');
include_once __DIR__.'/../common.php';
include_once G5_LIB_PATH.'/booking.lib.php';

booking_install();   // 테이블이 없으면 만든다 (멱등)

define('SEED_MARK', '[seed]');
define('SEED_ADDON_ORDER', 900);        // 시드 부가상품의 ba_order 대역 (900~999)
define('SEED_IMAGE_DIR', G5_DATA_PATH.'/booking');

$opts = array_slice($argv, 1);
$do_wipe = in_array('--wipe', $opts, true);

// --room=<br_id> [--images=<n>] — 그 객실에만 사진을 붙이는 일회성 모드
$opt_room = 0; $opt_images = 3;
foreach ($opts as $o) {
    if (preg_match('/^--room=(\d+)$/', $o, $m))   $opt_room   = (int)$m[1];
    if (preg_match('/^--images=(\d+)$/', $o, $m)) $opt_images = (int)$m[1];
}

/* ================================================================== 데이터 정의 */

// 객실 20개 — 이름, 소개, 기준/최대 인원, 인원추가요금, 실수, 주중가, 주말 가산액
// 금액은 전부 100원 단위 소액이다 (주중 300~900 · 주말 가산 100~300 · 인원추가 100~200).
// 방마다 값이 다른 것은 화면·계산이 한 값으로 눌리지 않게 하기 위한 것이고,
// 비싼 방이 여전히 비싸도록 원래의 등급 순서는 그대로 유지했다
$SEED_ROOMS = array(
    array('디럭스 스파',      '넓은 창으로 계곡이 내려다보이는 스파 객실입니다. 2인용 월풀 욕조와 캡슐 커피머신을 갖췄습니다.', 2, 4, 200, 3, 500, 200),
    array('패밀리 온돌',      '온 가족이 함께 눕는 좌식 온돌방입니다. 이불 4채와 넉넉한 주방이 준비되어 있습니다.',              4, 8, 100, 2, 500, 200),
    array('오션뷰 복층',      '거실 통창과 다락 침실이 있는 복층 객실입니다. 2층 창가에서 바다가 한눈에 들어옵니다.',            2, 6, 200, 2, 600, 300),
    array('독채 풀빌라',      '마당과 개인 수영장을 통째로 쓰는 독채입니다. 바비큐 데크와 야외 샤워장이 딸려 있습니다.',        4, 8, 200, 1, 900, 300),
    array('커플 자쿠지',      '커플 전용 객실로 창가 자쿠지와 무드 조명, 블루투스 스피커를 두었습니다.',                        2, 2,   0, 4, 400, 200),
    array('마운틴뷰 테라스',  '전용 테라스에서 앞산 능선을 마주 보는 객실입니다. 아침 안개가 특히 좋습니다.',                    2, 4, 100, 3, 400, 100),
    array('프리미엄 스위트',  '침실과 거실이 분리된 가장 넓은 객실입니다. 킹 침대와 6인용 다이닝 테이블을 갖췄습니다.',          2, 6, 200, 1, 800, 300),
    array('가든 독채',        '잔디 마당을 낀 단층 독채입니다. 문을 열면 바로 정원이라 아이들이 뛰어놀기 좋습니다.',            4, 6, 200, 2, 600, 200),
    array('다락방 로맨틱',    '천창으로 별이 보이는 다락 객실입니다. 낮은 층고가 주는 아늑함이 매력입니다.',                    2, 3, 100, 2, 300, 100),
    array('리버뷰 원룸',      '강가 산책로 바로 앞 원룸형 객실입니다. 짧게 머무는 여행에 알맞습니다.',                          2, 3, 100, 4, 300, 100),
    array('우드 캐빈',        '통나무로 지은 산장형 객실입니다. 벽난로와 흔들의자가 겨울에 특히 인기입니다.',                    2, 4, 100, 3, 400, 200),
    array('그랜드 패밀리',    '방 2개에 욕실 2개를 둔 대가족용 객실입니다. 세탁기와 건조기도 객실 안에 있습니다.',              6, 8, 200, 1, 800, 200),
    array('선셋 오션 스위트', '해가 지는 방향으로만 창을 낸 객실입니다. 저녁 시간대의 노을이 그대로 들어옵니다.',                2, 4, 200, 2, 700, 300),
    array('힐링 한옥채',      '대청마루가 있는 한옥 객실입니다. 툇마루에 앉아 마당을 바라보며 쉴 수 있습니다.',                  2, 5, 200, 2, 500, 200),
    array('스카이 루프탑',    '옥상 전용 데크를 쓰는 최상층 객실입니다. 밤에는 데크 조명 아래 야경이 펼쳐집니다.',              2, 4, 200, 2, 600, 300),
    array('포레스트 트윈',    '숲을 향해 트윈 침대를 나란히 둔 객실입니다. 친구·동료 여행에 알맞습니다.',                        2, 4, 100, 4, 400, 100),
    array('프라이빗 풀 스파', '실내 온수풀과 스파를 함께 갖춘 객실입니다. 계절에 상관없이 물놀이가 가능합니다.',                2, 6, 200, 1, 900, 300),
    array('노을 정원채',      '작은 정원을 낀 독채로, 화로대와 야외 테이블이 준비되어 있습니다.',                                4, 6, 200, 2, 500, 200),
    array('별빛 캠핑동',      '글램핑 감성의 텐트형 객실입니다. 침대와 에어컨을 갖춰 사계절 이용할 수 있습니다.',                2, 4, 100, 4, 300, 100),
    array('라운지 복층 스위트','1층 라운지와 2층 침실로 나뉜 복층 스위트입니다. 소규모 모임 장소로도 좋습니다.',                 4, 8, 200, 1, 700, 300),
);

// 부가상품 12개 — 이름, 가격(100~500원), 최대수량 (기존 ba_id=36 '조식' 과 이름이 겹치지 않게 한다)
$SEED_ADDONS = array(
    array('바베큐 세트',        400, 5),
    array('숯+그릴 대여',       200, 4),
    array('조식 세트(2인)',     300, 8),
    array('침구 추가',          100, 6),
    array('픽업 서비스(편도)',  300, 2),
    array('불멍 세트',          400, 3),
    array('와인 웰컴 세트',     500, 4),
    array('케이크 서프라이즈',  400, 2),
    array('반려동물 동반',      300, 2),
    array('수영장 이용권',      200, 10),
    array('조식 도시락',        100, 8),
    array('낚시대 대여',        100, 6),
);

$SEED_NAMES = array('홍길동', '김철수', '이영희', '박민수', '최지우', '정다은', '강태호', '윤서연',
                    '임재현', '오하늘', '한지민', '서준호', '문가영', '배성우', '신유진');

/* ================================================================== 시드 삭제 */

// 시드 객실 br_id 목록. 마커가 붙은 객실만 시드로 본다
function seed_room_ids()
{
    global $g5;
    $ids = array();
    $res = sql_query(" select br_id from `{$g5['booking_room_table']}`
        where br_content like '%".sql_real_escape_string(SEED_MARK)."%' order by br_id ", false);
    while ($res && $row = sql_fetch_array($res)) $ids[] = (int)$row['br_id'];
    return $ids;
}

// 넣은 순서의 역순으로 지운다: 메모·부가상품스냅샷 → 예약 → 캘린더 → 이미지(파일 포함) → 객실 → 부가상품
function seed_wipe()
{
    global $g5, $SEED_ADDONS;
    $stat = array('room' => 0, 'image' => 0, 'file' => 0, 'calendar' => 0, 'booking' => 0, 'note' => 0, 'addon' => 0);

    $ids = seed_room_ids();
    if ($ids) {
        $in = implode(',', $ids);

        $res = sql_query(" select bk_id from `{$g5['booking_table']}` where br_id in ($in) ", false);
        $bk_ids = array();
        while ($res && $row = sql_fetch_array($res)) $bk_ids[] = (int)$row['bk_id'];
        if ($bk_ids) {
            $bin = implode(',', $bk_ids);
            sql_query(" delete from `{$g5['booking_note_table']}` where bk_id in ($bin) ", true);
            $stat['note'] = get_sql_affected_rows();
            sql_query(" delete from `{$g5['booking_addon_item_table']}` where bk_id in ($bin) ", true);
            sql_query(" delete from `{$g5['booking_table']}` where bk_id in ($bin) ", true);
            $stat['booking'] = get_sql_affected_rows();
        }

        sql_query(" delete from `{$g5['booking_calendar_table']}` where br_id in ($in) ", true);
        $stat['calendar'] = get_sql_affected_rows();

        // 파일은 DB 행을 지우기 전에 먼저 걷는다 — 행이 사라지면 파일명을 알 길이 없다
        $res = sql_query(" select bi_file from `{$g5['booking_room_image_table']}` where br_id in ($in) ", false);
        while ($res && $row = sql_fetch_array($res)) {
            $path = SEED_IMAGE_DIR.'/'.basename($row['bi_file']);
            if ($row['bi_file'] !== '' && is_file($path) && @unlink($path)) $stat['file']++;
        }
        sql_query(" delete from `{$g5['booking_room_image_table']}` where br_id in ($in) ", true);
        $stat['image'] = get_sql_affected_rows();

        sql_query(" delete from `{$g5['booking_room_addon_table']}` where br_id in ($in) ", true);
        sql_query(" delete from `{$g5['booking_room_table']}` where br_id in ($in) ", true);
        $stat['room'] = get_sql_affected_rows();
    }

    // 부가상품은 매달린 객실이 없다 — ba_order 대역과 이름을 함께 봐야 시드만 걸린다
    $names = array();
    foreach ($SEED_ADDONS as $a) $names[] = "'".sql_real_escape_string($a[0])."'";
    sql_query(" delete from `{$g5['booking_addon_table']}`
        where ba_order between ".SEED_ADDON_ORDER." and ".(SEED_ADDON_ORDER + 99)."
          and ba_subject in (".implode(',', $names).") ", true);
    $stat['addon'] = get_sql_affected_rows();

    return $stat;
}

if ($do_wipe) {
    $s = seed_wipe();
    printf("시드 삭제 완료 — 객실 %d · 이미지 %d행(파일 %d) · 캘린더 %d · 예약 %d(메모 %d) · 부가상품 %d\n",
        $s['room'], $s['image'], $s['file'], $s['calendar'], $s['booking'], $s['note'], $s['addon']);
    exit(0);
}

/* ============================================== 객실 하나에 사진만 붙이기 (--room) */

// 시드가 아니다 — 마커를 붙이지 않고 시드 객실 목록에도 들어가지 않으므로 --wipe 대상이 아니다.
// 이미 사진이 있으면 손대지 않는다 (같은 객실에 두 번 돌려도 안전하다)
if ($opt_room > 0) {
    $room = sql_fetch(" select * from `{$g5['booking_room_table']}` where br_id = '$opt_room' ");
    if (!$room) exit("br_id={$opt_room} 객실이 없습니다.\n");

    $has = sql_fetch(" select count(*) as cnt from `{$g5['booking_room_image_table']}` where br_id = '$opt_room' ");
    if ((int)$has['cnt'] > 0)
        exit("br_id={$opt_room} '{$room['br_subject']}' 에 이미 사진 ".(int)$has['cnt']."장이 있습니다 — 건너뜁니다.\n");

    seed_image_dir_ready();

    $want = max(1, min(10, $opt_images));
    $ok = 0;
    $lock = 7000 + $opt_room * 10;   // 시드(1000번대)와 겹치지 않는 대역
    for ($k = 0; $k < $want; $k++) {
        $file = seed_save_image($lock++);
        if ($file !== '') {
            $ok++;
            sql_query(" insert into `{$g5['booking_room_image_table']}` set
                br_id = '$opt_room', bi_file = '".sql_real_escape_string($file)."',
                bi_order = '$ok', bi_main = '".($ok === 1 ? 1 : 0)."' ", true);
        }
        usleep(mt_rand(300000, 500000));   // 남의 서버다 — 사이를 둔다
    }
    if ($ok === 0) exit("사진을 한 장도 받지 못했습니다. 네트워크를 확인하십시오.\n");
    printf("br_id=%d '%s' 에 사진 %d장 저장 (요청 %d)\n", $opt_room, $room['br_subject'], $ok, $want);
    exit(0);
}

/* ================================================================== 안전장치 */

$exists = seed_room_ids();
if ($exists) {
    echo "이미 시드됨 — 시드 객실 ".count($exists)."개가 남아 있습니다.\n";
    echo "  php tools/seed_booking.php --wipe   로 삭제한 뒤 다시 실행하십시오.\n";
    exit(1);
}

$t0 = microtime(true);
mt_srand(20260804);   // 실행마다 값이 널뛰지 않게 고정 씨앗을 쓴다

/* ================================================================== 1. 객실 20 */

$rooms = array();     // br_id => 객실 행
foreach ($SEED_ROOMS as $i => $r) {
    list($subject, $desc, $base, $max, $person_price, $count, $weekday, $weekend_add) = $r;
    $weekend = (int)$weekday + (int)$weekend_add;   // 둘 다 100원 단위라 합도 100원 단위다
    $content = $desc."\n\n체크인 15:00 / 체크아웃 11:00\n".SEED_MARK;
    sql_query(" insert into `{$g5['booking_room_table']}` set
        br_subject = '".sql_real_escape_string($subject)."',
        br_content = '".sql_real_escape_string($content)."',
        br_base_person = '$base', br_max_person = '$max',
        br_person_price = '$person_price', br_room_count = '$count',
        br_weekday_price = '$weekday', br_weekend_price = '$weekend',
        br_use = 1, br_order = '".($i + 1)."',
        br_datetime = '".date('Y-m-d H:i:s', G5_SERVER_TIME)."' ", true);
    $br_id = sql_insert_id();
    $rooms[$br_id] = sql_fetch(" select * from `{$g5['booking_room_table']}` where br_id = '$br_id' ");
}
echo "객실 ".count($rooms)."개 등록\n";

/* ================================================================== 2. 객실 사진 */

// 무료 이미지 한 장. 1순위 loremflickr, 실패하면 picsum 으로 폴백한다.
// 받은 바이트가 진짜 이미지인지 getimagesize() 로 확인한다 — 404 안내 HTML 을
// .jpg 로 저장해 두면 화면에서 깨진 사진으로만 보인다
function seed_fetch($url, $timeout = 30)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_USERAGENT      => 'g5pro-seed/1.0',
    ));
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !is_string($body) || strlen($body) < 2048) return '';
    return $body;
}

// 성공하면 저장한 파일명, 실패하면 ''
function seed_save_image($lock)
{
    $urls = array(
        'https://loremflickr.com/1200/800/hotel,room?lock='.$lock,
        'https://picsum.photos/1200/800?random='.$lock,
    );
    foreach ($urls as $url) {
        $body = seed_fetch($url);
        if ($body === '') continue;
        $file = md5(uniqid(mt_rand(), true)).'.jpg';
        $path = SEED_IMAGE_DIR.'/'.$file;
        if (@file_put_contents($path, $body) === false) continue;
        if (!@getimagesize($path)) { @unlink($path); continue; }
        @chmod($path, 0644);   // www-data 가 읽어야 화면에 뜬다
        return $file;
    }
    return '';
}

// 이미지 디렉터리를 쓸 수 있게 만든다. 못 쓰면 여기서 멈춘다
function seed_image_dir_ready()
{
    if (!is_dir(SEED_IMAGE_DIR)) {
        @mkdir(SEED_IMAGE_DIR, G5_DIR_PERMISSION, true);
        @chmod(SEED_IMAGE_DIR, G5_DIR_PERMISSION);
    }
    if (!is_dir(SEED_IMAGE_DIR) || !is_writable(SEED_IMAGE_DIR))
        exit('이미지 디렉터리를 쓸 수 없습니다: '.SEED_IMAGE_DIR."\n");
}

seed_image_dir_ready();

$img_ok = 0; $img_try = 0; $lock = 1000;
foreach ($rooms as $br_id => $room) {
    $shots = mt_rand(3, 5);
    $order = 0;
    for ($k = 0; $k < $shots; $k++) {
        $img_try++;
        $file = seed_save_image($lock++);
        if ($file !== '') {
            $order++;
            sql_query(" insert into `{$g5['booking_room_image_table']}` set
                br_id = '$br_id', bi_file = '".sql_real_escape_string($file)."',
                bi_order = '$order', bi_main = '".($order === 1 ? 1 : 0)."' ", true);
            $img_ok++;
        }
        usleep(mt_rand(300000, 500000));   // 남의 서버다 — 사이를 둔다
    }
    // 절반 넘게 실패하면 네트워크·차단 문제다. 더 두드리지 않고 멈춘다
    if ($img_try >= 10 && ($img_try - $img_ok) * 2 > $img_try) {
        echo "이미지 다운로드 실패가 절반을 넘었습니다 ($img_ok/$img_try). 중단합니다.\n";
        echo "  네트워크를 확인한 뒤 php tools/seed_booking.php --wipe 로 지우고 다시 실행하십시오.\n";
        exit(1);
    }
}
echo "객실 사진 {$img_ok}장 저장 (시도 {$img_try})\n";

/* ================================================================== 3. 부가상품 12 */

$addon_ids = array();
foreach ($SEED_ADDONS as $i => $a) {
    sql_query(" insert into `{$g5['booking_addon_table']}` set
        ba_subject = '".sql_real_escape_string($a[0])."',
        ba_price = '".(int)$a[1]."', ba_max_qty = '".(int)$a[2]."',
        ba_use = 1, ba_order = '".(SEED_ADDON_ORDER + $i + 1)."' ", true);
    $addon_ids[] = sql_insert_id();
}

// 상품은 담긴 객실에서만 팔린다 — 시드에서는 전 객실에 전 상품을 담는다
$map_cnt = 0;
foreach (array_keys($rooms) as $br_id) {
    foreach ($addon_ids as $i => $ba_id) {
        sql_query(" insert ignore into `{$g5['booking_room_addon_table']}` set
            br_id = '$br_id', ba_id = '$ba_id', bra_order = '$i' ", true);
        $map_cnt++;
    }
}
echo "부가상품 ".count($addon_ids)."개 등록 (객실 매핑 {$map_cnt}건)\n";

/* ================================================================== 4. 캘린더 오버라이드 */

// 앞으로 3개월 안에서:
//   - 다가오는 달 11~24일 = 성수기. 객실 절반(홀수 번째)의 요금을 그날 기준가 ×1.5~2 로
//     덮는다. 소액 가격이므로 1000원이 아니라 100원 단위로 반올림한다
//   - 객실 2곳에 1주짜리 수리 기간 — 판매 실수를 실수-1 (실수 1이면 0) 로 내린다
// 한 날짜에 둘이 겹칠 수 있으므로 배열에 모았다가 한 번에 넣는다 (unique key br_id+bd_date)
$cal = array();   // br_id => date => array(price, count)
$room_ids = array_keys($rooms);

$peak_start = date('Y-m-11', strtotime('+1 month', G5_SERVER_TIME));
$peak_days = array();
for ($d = 0; $d < 14; $d++) $peak_days[] = date('Y-m-d', strtotime("+$d day", strtotime($peak_start)));

foreach ($room_ids as $n => $br_id) {
    if ($n % 2 !== 0) continue;   // 절반만
    $rate = mt_rand(150, 200) / 100;
    foreach ($peak_days as $date) {
        $base = (int)date('w', strtotime($date)) >= 5
            ? (int)$rooms[$br_id]['br_weekend_price'] : (int)$rooms[$br_id]['br_weekday_price'];
        $cal[$br_id][$date] = array('price' => (int)(round($base * $rate / 100) * 100), 'count' => -1);
    }
}

// 수리 기간 — 3번째·8번째 객실, 다가오는 달 초 1주일
$fix_start = date('Y-m-03', strtotime('+1 month', G5_SERVER_TIME));
foreach (array(2, 7) as $idx) {
    if (!isset($room_ids[$idx])) continue;
    $br_id = $room_ids[$idx];
    $left = max(0, (int)$rooms[$br_id]['br_room_count'] - 1);
    for ($d = 0; $d < 7; $d++) {
        $date = date('Y-m-d', strtotime("+$d day", strtotime($fix_start)));
        if (!isset($cal[$br_id][$date])) $cal[$br_id][$date] = array('price' => -1, 'count' => -1);
        $cal[$br_id][$date]['count'] = $left;
    }
}

$cal_rows = 0;
foreach ($cal as $br_id => $dates) {
    foreach ($dates as $date => $v) {
        sql_query(" insert into `{$g5['booking_calendar_table']}` set
            br_id = '".(int)$br_id."', bd_date = '".sql_real_escape_string($date)."',
            bd_price = '".(int)$v['price']."', bd_room_count = '".(int)$v['count']."'
            on duplicate key update bd_price = values(bd_price), bd_room_count = values(bd_room_count) ", true);
        $cal_rows++;
    }
}
echo "캘린더 오버라이드 {$cal_rows}행 (성수기 ".count($peak_days)."일 + 수리 2객실×7일)\n";

/* ================================================================== 5. 예약 40건 */

// 과거 2주 ~ 미래 6주. 상태별 배분: 확정 30 · 취소요청 3 · 취소완료 4 · 만료 hold 3
$plan = array_merge(
    array_fill(0, 30, 'confirmed'),
    array_fill(0, 3, 'cancel_req'),
    array_fill(0, 4, 'cancelled'),
    array_fill(0, 3, 'hold')
);

$bc = booking_config();
$pw = get_encrypt_string('1234');
$now_ts = G5_SERVER_TIME;

// 이미 잡아 둔 자리 — br_id|date => 건수. 잔여를 넘겨 잡지 않으려고 직접 센다
// (booking_create_hold 는 과거 날짜·hold 만 만들 수 있어 이 분포를 못 만든다)
$taken = array();

function seed_sellable($room, $date)
{
    global $cal;
    $br_id = (int)$room['br_id'];
    if (isset($cal[$br_id][$date]) && (int)$cal[$br_id][$date]['count'] >= 0)
        return (int)$cal[$br_id][$date]['count'];
    return (int)$room['br_room_count'];
}

// 자리가 남는 (객실, 체크인, 박수) 를 찾는다. 못 찾으면 null
function seed_pick_stay($rooms, &$taken, $off_min, $off_max, $occupy)
{
    $ids = array_keys($rooms);
    for ($try = 0; $try < 200; $try++) {
        $room = $rooms[$ids[mt_rand(0, count($ids) - 1)]];
        $nights = mt_rand(1, 3);
        $off = mt_rand($off_min, $off_max);
        $checkin = date('Y-m-d', strtotime("+$off day", G5_SERVER_TIME));
        $checkout = date('Y-m-d', strtotime('+'.($off + $nights).' day', G5_SERVER_TIME));
        $ok = true;
        foreach (booking_nights($checkin, $checkout) as $d) {
            $key = $room['br_id'].'|'.$d;
            $used = isset($taken[$key]) ? $taken[$key] : 0;
            if ($used + 1 > seed_sellable($room, $d)) { $ok = false; break; }
        }
        if (!$ok) continue;
        if ($occupy) {
            foreach (booking_nights($checkin, $checkout) as $d) {
                $key = $room['br_id'].'|'.$d;
                $taken[$key] = (isset($taken[$key]) ? $taken[$key] : 0) + 1;
            }
        }
        return array('room' => $room, 'checkin' => $checkin, 'checkout' => $checkout);
    }
    return null;
}

$made = array('confirmed' => 0, 'cancel_req' => 0, 'cancelled' => 0, 'hold' => 0);
$note_cnt = 0; $addon_used = 0;

foreach ($plan as $i => $status) {
    // 상태별 날짜 대역 — 취소·대기 건은 앞으로 남은 날이 있어야 환불 규정이 살아난다
    if ($status === 'confirmed')      { $lo = -14; $hi = 42; }
    else if ($status === 'cancel_req'){ $lo = 10;  $hi = 30; }
    else if ($status === 'cancelled') { $lo = 8;   $hi = 35; }
    else                              { $lo = 3;   $hi = 20; }

    // 확정·취소요청만 재고를 쓴다 (booking_booked_count 와 같은 기준).
    // 취소완료·만료 hold 는 자리를 비워 둔 건이라 세지 않는다
    $occupy = ($status === 'confirmed' || $status === 'cancel_req');
    $stay = seed_pick_stay($rooms, $taken, $lo, $hi, $occupy);
    if (!$stay) continue;

    $room = $stay['room'];
    $checkin = $stay['checkin']; $checkout = $stay['checkout'];
    $nights = count(booking_nights($checkin, $checkout));
    $person = mt_rand((int)$room['br_base_person'], (int)$room['br_max_person']);

    // 절반쯤은 부가상품을 곁들인다
    $addons = array();
    if ($i % 2 === 0) {
        $pick = mt_rand(1, 2);
        for ($k = 0; $k < $pick; $k++) {
            $ba_id = $addon_ids[mt_rand(0, count($addon_ids) - 1)];
            $addons[$ba_id] = mt_rand(1, 2);
        }
        $addon_used++;
    }

    $price = booking_calc_price($room, $checkin, $checkout, $person, $addons);

    $name = $SEED_NAMES[$i % count($SEED_NAMES)];
    $hp = sprintf('010-0000-%04d', 1000 + $i);
    $bk_no = booking_new_no();
    // 주문번호는 결제 화면(booking/pay.php)이 찍는 규칙 그대로 — 예약번호 + 'T' + 시각 + 두 자리
    $order_ts = $now_ts - mt_rand(3600, 30 * 86400);
    $oid = $bk_no.'T'.$order_ts.mt_rand(10, 99);
    $datetime = date('Y-m-d H:i:s', $order_ts);

    $set = " bk_no = '".sql_real_escape_string($bk_no)."', br_id = '".(int)$room['br_id']."',
        bk_checkin = '$checkin', bk_checkout = '$checkout', bk_person = '$person',
        bk_name = '".sql_real_escape_string($name)."', bk_hp = '$hp',
        bk_email = '".sql_real_escape_string('guest'.($i + 1).'@example.com')."',
        bk_request = '".sql_real_escape_string($i % 3 === 0 ? '늦은 체크인 예정입니다. 21시쯤 도착합니다.' : '')."',
        mb_id = '', bk_password = '".sql_real_escape_string($pw)."',
        bk_room_price = '{$price['room']}', bk_person_price = '{$price['person']}',
        bk_addon_price = '{$price['addon']}', bk_total_price = '{$price['total']}',
        bk_oid = '".sql_real_escape_string($oid)."',
        bk_datetime = '$datetime', bk_ip = '127.0.0.1' ";

    if ($status === 'hold') {
        // 만료된 점유 — 결제창까지 갔다가 돌아오지 않은 건이다.
        // 거래번호는 승인이 끝나야 생기므로 비워 둔다 (결제대사 B 케이스는 confirmed 만 본다)
        $set .= " , bk_status = 'hold',
            bk_hold_expire = '".date('Y-m-d H:i:s', $order_ts + (int)$bc['bc_hold_minutes'] * 60)."' ";
    } else {
        // 확정·취소 건은 결제가 끝난 건이다. 거래번호가 비면 결제대사 B 케이스에 오탐으로 뜬다
        $tid = 'SEED'.strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 16));
        $pay_ts = $order_ts + mt_rand(60, 600);
        $set .= " , bk_tid = '$tid', bk_pay_time = '".date('Y-m-d H:i:s', $pay_ts)."' ";

        if ($status === 'confirmed') {
            $set .= " , bk_status = 'confirmed' ";
        } else {
            $cancel_ts = min($now_ts - 3600, $pay_ts + mt_rand(86400, 5 * 86400));
            $days_before = (int)floor((strtotime($checkin) - $cancel_ts) / 86400);
            $rate = booking_refund_rate($bc['bc_cancel_policy'], $days_before);
            $plan_price = booking_refund_amount($price['total'], $rate);
            $set .= " , bk_cancel_time = '".date('Y-m-d H:i:s', $cancel_ts)."',
                bk_cancel_memo = '".sql_real_escape_string('일정이 변경되어 취소합니다.')."',
                bk_refund_plan_price = '".(int)$plan_price."' ";
            if ($status === 'cancel_req') {
                $set .= " , bk_status = 'cancel_req' ";
            } else {
                $set .= " , bk_status = 'cancelled', bk_refund_price = '".(int)$plan_price."',
                    bk_refund_time = '".date('Y-m-d H:i:s', $cancel_ts + mt_rand(600, 7200))."' ";
            }
        }
    }

    sql_query(" insert into `{$g5['booking_table']}` set $set ", true);
    $bk_id = sql_insert_id();
    $made[$status]++;

    // 부가상품 스냅샷 — 예약 당시의 이름·단가를 그대로 박아 둔다 (booking_create_hold 와 같은 모양)
    foreach ($price['addon_items'] as $item) {
        sql_query(" insert into `{$g5['booking_addon_item_table']}` set bk_id = '$bk_id',
            bt_subject = '".sql_real_escape_string($item['subject'])."',
            bt_price = '{$item['price']}', bt_qty = '{$item['qty']}', bt_amount = '{$item['amount']}' ", true);
    }

    // 메모 — 네 건에 하나꼴로 손님 문의와 관리자 응대를 2~3줄 남긴다
    if ($i % 4 === 0) {
        $notes = array(
            array('guest', '주차는 몇 대까지 가능한가요?'),
            array('admin', '객실당 2대까지 가능합니다. 마당 쪽에 대시면 됩니다.'),
        );
        if ($i % 8 === 0) $notes[] = array('guest', '감사합니다. 도착 시간 맞춰 가겠습니다.');
        foreach ($notes as $n => $note) {
            sql_query(" insert into `{$g5['booking_note_table']}` set bk_id = '$bk_id',
                bn_writer = '{$note[0]}', bn_content = '".sql_real_escape_string($note[1])."',
                bn_checked = '".($note[0] === 'guest' ? 1 : 0)."',
                bn_datetime = '".date('Y-m-d H:i:s', $order_ts + 3600 * ($n + 1))."' ", true);
            $note_cnt++;
        }
    }
}

$total_bk = array_sum($made);
printf("예약 %d건 (확정 %d · 취소요청 %d · 취소완료 %d · 만료 %d) · 메모 %d · 부가상품 포함 %d건\n",
    $total_bk, $made['confirmed'], $made['cancel_req'], $made['cancelled'], $made['hold'],
    $note_cnt, $addon_used);

/* ================================================================== 요약 */

printf("\n완료 — 객실 %d / 이미지 %d장 / 부가상품 %d / 캘린더 %d행 / 예약 %d건 (%.1f초)\n",
    count($rooms), $img_ok, count($addon_ids), $cal_rows, $total_bk, microtime(true) - $t0);
// G5_URL 은 웹 요청의 SCRIPT_NAME 으로 만들어진다 — CLI 에서는 엉뚱한 값이라 경로만 알린다
echo "확인: /booking/ (손님) · /".G5_ADMIN_DIR."/booking/room_list.php (관리자)\n";
