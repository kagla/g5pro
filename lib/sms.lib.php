<?php
if (!defined('_GNUBOARD_')) exit;

// 문자 발송 공용 입구 — 업체 분기는 이 함수 안에만 있다.
// 새 코드는 이것만 부른다 (예약 모듈 booking_notify 등). cf_sms_use 로 업체를 고른다.
//
// 아이코드('icode')는 순정 호출부(주문·QA·가입인증)가 화면마다 SMS 클래스를 직접 쓰는
// 구조라 여기서 겸하지 않는다 — 전역 발신번호 설정이 없어 보낼 번호를 정할 수 없다.
// 아이코드까지 이 입구로 모으려면 발신번호 설정을 먼저 만들어야 한다.
//
// 반환: array('ok' => bool, 'msg' => 사유)
function g5_sms_send($to, $message)
{
    global $config;

    if (empty($config['cf_sms_use']))
        return array('ok' => false, 'msg' => 'SMS 사용 안 함');

    if ($config['cf_sms_use'] === 'ppurio') {
        include_once(G5_LIB_PATH.'/ppurio.sms.lib.php');
        return ppurio_send_sms($to, $message);
    }

    return array('ok' => false, 'msg' => '이 업체('.$config['cf_sms_use'].')는 공용 발송을 지원하지 않습니다.');
}
