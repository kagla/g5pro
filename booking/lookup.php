<?php
// 예약 조회 입구 — 회원은 제 예약 목록, 비회원은 예약번호+비밀번호 확인 폼.
// 확인을 통과하면 인가 세션(ss_booking_view_{bk_id})을 심고 view.php 로 보낸다.
include_once('./_common.php');
define('G5_PRO_PAGE', true); // g5pro 직통 화면

$lookup_url = G5_URL.'/booking/lookup.php';

// 예약번호는 booking_new_no() 가 만든 대문자 영숫자 10자다. 그 밖의 글자는 애초에 없다
function booking_lookup_no($str)
{
    return preg_replace('/[^A-Z0-9]/', '', strtoupper($str));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 실패는 check_token() 이 제 안에서 alert() 로 끝낸다 (바깥 alert 은 죽은 줄이 된다)
    check_token();

    // 배열로 온 값은 통째로 버린다 (reserve.php 와 같은 방어)
    $bk_no    = (isset($_POST['bk_no']) && !is_array($_POST['bk_no'])) ? booking_lookup_no(trim($_POST['bk_no'])) : '';
    // 비밀번호는 $_POST 의 값을 그대로 쓴다 — reserve_update.php 가 저장할 때도 같은 값
    // (common.php 가 GPC 전체에 건 addslashes 를 포함한)을 해시했다. 여기서 되돌리면 어긋난다
    $password = (isset($_POST['bk_password']) && !is_array($_POST['bk_password'])) ? trim($_POST['bk_password']) : '';

    $bk = $bk_no ? booking_get_by_no($bk_no) : null;

    // 회원 예약에는 비밀번호가 없다(reserve_update.php 가 빈 값으로 저장한다).
    // 그 예약은 이 폼으로 열 수 없고 로그인해서 목록으로 가야 한다.
    $ok = ($bk && $bk['mb_id'] === '' && $bk['bk_password'] !== ''
        && $password !== '' && check_password($password, $bk['bk_password']));

    if (!$ok) {
        // 무차별 대입 완화 — 한 번 틀릴 때마다 1초를 물린다
        sleep(1);
        alert('예약번호 또는 비밀번호가 일치하지 않습니다.', $lookup_url);
    }

    // 여기서만 인가가 생긴다. 이후 view/note/취소는 booking_owner_check() 로 이 세션만 본다
    set_session('ss_booking_view_'.(int)$bk['bk_id'], 1);
    goto_url(G5_URL.'/booking/view.php?bk_no='.$bk['bk_no']);
}

$status_label = array('hold' => '결제대기', 'confirmed' => '예약확정',
    'cancel_req' => '취소요청', 'cancelled' => '취소완료');

// 회원 목록. 결제를 마치지 못한 hold 는 예약이 아니므로 뺀다 —
// 유효시간이 지나면 저절로 사라지는 자리라 목록에 남기면 없는 예약을 있다고 보여 준다
$bookings = array();
if ($is_member) {
    $mb_id = sql_real_escape_string($member['mb_id']);
    $result = sql_query(" select bk.bk_no, bk.bk_checkin, bk.bk_checkout, bk.bk_person,
            bk.bk_total_price, bk.bk_status, bk.bk_datetime, r.br_subject
        from `{$g5['booking_table']}` bk
        left join `{$g5['booking_room_table']}` r on r.br_id = bk.br_id
        where bk.mb_id = '$mb_id' and bk.bk_status <> 'hold'
        order by bk.bk_id desc limit 50 ");
    while ($row = sql_fetch_array($result)) {
        $row['br_subject'] = (string)$row['br_subject'];
        $row['nights'] = count(booking_nights($row['bk_checkin'], $row['bk_checkout']));
        $row['status_text'] = isset($status_label[$row['bk_status']])
            ? $status_label[$row['bk_status']] : $row['bk_status'];
        $bookings[] = $row;
    }
}

$g5['title'] = '예약 조회';
g5_view('booking.lookup', array(
    'is_member' => (bool)$is_member,
    'bookings'  => $bookings,
    'token'     => get_token(),
));
