<?php
// 예약 추가 요청 저장 — view.php 의 폼이 보내는 자리.
// 손님 쪽 메모(bn_writer='guest')만 여기서 만든다. 업주 답(admin)은 관리자 화면의 몫이다.
include_once('./_common.php');

// 실패는 check_token() 이 제 안에서 alert() 로 끝낸다
check_token();

$lookup_url = G5_URL.'/booking/lookup.php';

$bk_no = (isset($_POST['bk_no']) && !is_array($_POST['bk_no'])) ? trim($_POST['bk_no']) : '';
$bk = $bk_no ? booking_get_by_no($bk_no) : null;

// 조회 화면과 같은 잣대 하나만 본다 — 예약번호를 안다고 남의 예약에 글을 남길 수는 없다
if (!booking_owner_check($bk)) alert('예약 정보를 확인할 수 없습니다.', $lookup_url);

$view_url = G5_URL.'/booking/view.php?bk_no='.$bk['bk_no'];

// 여러 줄 글이다. clean_xss_tags 의 기본값은 줄바꿈까지 지우므로 마지막 인자를 끈다
// (reserve_update.php 의 요청사항과 같은 처리)
$content = (isset($_POST['bn_content']) && !is_array($_POST['bn_content'])) ? trim($_POST['bn_content']) : '';
$content = clean_xss_tags($content, 0, 0, 0, 0);
if (trim($content) === '') alert('요청 내용을 입력해 주세요.', $view_url);

sql_query(" insert into `{$g5['booking_note_table']}` set
    bk_id = '".(int)$bk['bk_id']."',
    bn_writer = 'guest',
    bn_content = '".sql_real_escape_string($content)."',
    bn_checked = 0,
    bn_datetime = '".date('Y-m-d H:i:s', G5_SERVER_TIME)."' ", true);

// 업주에게 알린다. 발송 실패는 무시한다 — 메모는 이미 남았고 흐름을 막을 이유가 없다
// (common.php 는 mailer.lib.php 를 로드하지 않는다 — 순정 호출부가 각자 include 한다)
include_once(G5_LIB_PATH.'/mailer.lib.php');
$bc = booking_config();
// 예약 전용 주소가 비어 있으면 사이트 관리자 주소로 간다 — 알림이 조용히 사라지는 편보다 낫다
$to = trim($bc['bc_admin_email']) !== '' ? trim($bc['bc_admin_email']) : $config['cf_admin_email'];
if ($to) {
    $subject = '['.$config['cf_title'].'] 예약 '.$bk['bk_no'].' 추가 요청';
    // HTML 메일이다. clean_xss_tags 는 <b> 같은 양성 태그를 남기므로 여기서 다시 막는다
    $mail_content = '<p>'.get_text($bk['bk_name']).'님이 추가 요청을 남겼습니다.</p><ul>'
        .'<li>예약번호: '.get_text($bk['bk_no']).'</li>'
        .'<li>기간: '.$bk['bk_checkin'].' ~ '.$bk['bk_checkout'].'</li>'
        .'<li>연락처: '.get_text($bk['bk_hp']).'</li>'
        .'</ul><p>'.nl2br(get_text($content)).'</p>';
    @mailer($config['cf_title'], $config['cf_admin_email'], $to, $subject, $mail_content, 1);
}

goto_url($view_url);
