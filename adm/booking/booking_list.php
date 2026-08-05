<?php
// 예약 목록 — 업주가 하루에 몇 번씩 여는 화면. 여기서 걸러 내고 상세로 들어간다.
$sub_menu = '950100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

// 배열로 온 값은 통째로 버린다 (예약 모듈 공통 방어 — 배열에 (string) 을 씌우면 경고만 남고 검증이 샌다)
function bl_get($key)
{
    return (isset($_GET[$key]) && !is_array($_GET[$key])) ? trim((string)$_GET[$key]) : '';
}

// 상태 필터. 빈 값은 "결제대기 제외"가 기본이다 —
// hold 는 결제를 마치지 못한 자리이고 유효시간이 지나면 저절로 풀린다. 목록에 늘 섞이면
// 실제 예약과 구분이 어렵다. 'all' 을 고르면 그것까지 보여 준다
// 'active' 는 확정+취소요청 묶음 — 객실관리의 예약수가 세는 범위와 같다.
// 예약수를 눌러 온 화면의 건수가 누른 숫자와 어긋나지 않게 하려고 있다
$status = bl_get('status');
$status_list = array('hold', 'confirmed', 'cancel_req', 'cancelled', 'active');
if ($status !== '' && $status !== 'all' && !in_array($status, $status_list, true)) $status = '';

// 객실 필터 — 객실관리의 예약수를 눌러 들어오는 길이기도 하다
$br_id = (int)bl_get('br_id');
if ($br_id < 0) $br_id = 0;

// 기간은 체크인 날짜 기준이다 (예약일이 아니다 — 업주가 찾는 것은 "언제 오는 손님"이다)
$sdate = preg_match('/^\d{4}-\d{2}-\d{2}$/', bl_get('sdate')) ? bl_get('sdate') : '';
$edate = preg_match('/^\d{4}-\d{2}-\d{2}$/', bl_get('edate')) ? bl_get('edate') : '';

// common.php 가 GPC 전체에 addslashes 를 걸어 둔다. 화면에 되비칠 값은 원문이라야 하고
// 쿼리에 넣을 값은 여기서 다시 이스케이프한다
$stx = stripslashes(bl_get('stx'));

$where = " where (1) ";
if ($status === '')            $where .= " and b.bk_status <> 'hold' ";
else if ($status === 'active') $where .= " and b.bk_status in ('confirmed','cancel_req') ";
else if ($status !== 'all')    $where .= " and b.bk_status = '".sql_real_escape_string($status)."' ";
if ($br_id) $where .= " and b.br_id = '$br_id' ";
if ($sdate) $where .= " and b.bk_checkin >= '".sql_real_escape_string($sdate)."' ";
if ($edate) $where .= " and b.bk_checkin <= '".sql_real_escape_string($edate)."' ";
if ($stx !== '') {
    $like = '%'.sql_real_escape_string($stx).'%';
    // 연락처는 하이픈을 뺀 모양으로도 찾는다 — 업주는 01012345678 로 적어 넣는 일이 잦다.
    // 다만 "번호처럼 생긴 검색어"일 때만이다 — 예약번호(PQW0QEVS7F)에서 숫자만 뽑아 걸면
    // '07' 같은 두 글자가 남아 엉뚱한 연락처가 줄줄이 걸린다
    $digits = preg_match('/^[0-9\-\s]+$/', $stx) ? preg_replace('/[^0-9]/', '', $stx) : '';
    $where .= " and ( b.bk_name like '$like' or b.bk_hp like '$like' or b.bk_no like '$like' ";
    if ($digits !== '') $where .= " or replace(b.bk_hp, '-', '') like '%".sql_real_escape_string($digits)."%' ";
    $where .= " ) ";
}

$row = sql_fetch(" select count(*) as cnt from `{$g5['booking_table']}` b $where ");
$total_count = (int)$row['cnt'];

$rows = (int)$config['cf_page_rows'];
if ($rows < 1) $rows = 15;
$page = (int)bl_get('page');
if ($page < 1) $page = 1;
$total_page = (int)ceil($total_count / $rows);
$from_record = ($page - 1) * $rows;

// 미확인 요청 수는 서브쿼리로 함께 센다 — 행마다 따로 물으면 한 화면에 쿼리가 페이지 행수만큼 는다
$list = array();
$result = sql_query(" select b.*, r.br_subject,
        (select count(*) from `{$g5['booking_note_table']}` n
          where n.bk_id = b.bk_id and n.bn_writer = 'guest' and n.bn_checked = 0) as new_note_cnt
    from `{$g5['booking_table']}` b
    left join `{$g5['booking_room_table']}` r on r.br_id = b.br_id
    $where
    order by b.bk_id desc
    limit $from_record, $rows ");
while ($r = sql_fetch_array($result)) {
    $list[] = array(
        'bk_id' => (int)$r['bk_id'], 'bk_no' => $r['bk_no'],
        'br_subject' => (string)$r['br_subject'],
        'bk_checkin' => $r['bk_checkin'], 'bk_checkout' => $r['bk_checkout'],
        'nights' => count(booking_nights($r['bk_checkin'], $r['bk_checkout'])),
        'bk_name' => $r['bk_name'], 'bk_hp' => $r['bk_hp'], 'bk_person' => (int)$r['bk_person'],
        'bk_total_price' => (int)$r['bk_total_price'],
        'bk_status' => $r['bk_status'], 'status_text' => booking_status_label($r['bk_status']),
        'new_note_cnt' => (int)$r['new_note_cnt'],
        'bk_datetime' => $r['bk_datetime'],
    );
}

// 검색폼의 객실 선택지 — 숨김 객실도 넣는다. 지난 예약은 숨긴 객실에도 남아 있다
$room_opts = array();
$result = sql_query(" select br_id, br_subject, br_use from `{$g5['booking_room_table']}`
    order by br_order, br_id ");
while ($r = sql_fetch_array($result)) $room_opts[] = $r;

// 페이징 링크에 지금 건 필터를 그대로 달고 간다
$qstr = 'status='.urlencode($status).'&amp;br_id='.$br_id.'&amp;sdate='.urlencode($sdate)
      . '&amp;edate='.urlencode($edate).'&amp;stx='.urlencode($stx);

// 빠른 기간 버튼 — 날짜 계산은 서버 시간으로 여기서 끝낸다 (브라우저 시계·시간대에 흔들리지 않는다).
// 주는 관리자 캘린더 표기와 같게 일요일에 시작한다
$today = date('Y-m-d', G5_SERVER_TIME);
$week_start = date('Y-m-d', G5_SERVER_TIME - (int)date('w', G5_SERVER_TIME) * 86400);
$quick_ranges = array(
    array('label' => '오늘',   's' => $today, 'e' => $today),
    array('label' => '어제',   's' => date('Y-m-d', strtotime($today.' -1 day')),
                               'e' => date('Y-m-d', strtotime($today.' -1 day'))),
    array('label' => '이번주', 's' => $week_start,
                               'e' => date('Y-m-d', strtotime($week_start.' +6 day'))),
    array('label' => '지난주', 's' => date('Y-m-d', strtotime($week_start.' -7 day')),
                               'e' => date('Y-m-d', strtotime($week_start.' -1 day'))),
    array('label' => '이번달', 's' => date('Y-m-01', G5_SERVER_TIME),
                               'e' => date('Y-m-t', G5_SERVER_TIME)),
    array('label' => '지난달', 's' => date('Y-m-01', strtotime($today.' first day of last month')),
                               'e' => date('Y-m-t', strtotime($today.' first day of last month'))),
);

$g5['title'] = '예약목록';
include_once(G5_ADMIN_PATH.'/admin.head.php');

badm_view('booking_list', array(
    'list' => $list, 'total_count' => $total_count,
    'status' => $status, 'br_id' => $br_id, 'room_opts' => $room_opts,
    'sdate' => $sdate, 'edate' => $edate, 'stx' => $stx,
    'quick_ranges' => $quick_ranges,
    'admin_url' => G5_ADMIN_URL,
));

// 페이징 HTML 은 순정 get_paging() 이 만든 마크업 그대로다. 뷰는 {{ }} 로만 쓰므로
// (이스케이프되어 태그가 글자로 보인다) 이 자리에서 바로 내보낸다 — 순정 목록 파일과 같은 관례
echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'],
    $page, $total_page, './booking_list.php?'.$qstr.'&amp;page=');

include_once(G5_ADMIN_PATH.'/admin.tail.php');
