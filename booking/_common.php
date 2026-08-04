<?php
// 예약 모듈 프론트 공통 — booking/ 안의 모든 페이지가 첫 줄에서 include 한다.
//
// bbs/_common.php · shop/_common.php 와 같은 자리이고 같은 방식으로 common.php 를 직접 탄다.
// 루트 _common.php 를 거치지 않는 이유: 그 파일 안의 include 가 './common.php' 라
// 실행 스크립트의 작업 디렉터리 기준으로 풀려 엉뚱한 자리를 가리킨다.
//
// 여기서 '../common.php' 대신 __DIR__ 을 쓰는 것도 같은 이유다. PHP 는 './' · '../' 로
// 시작하는 경로를 부르는 파일이 아니라 작업 디렉터리 기준으로 푼다. 이 파일은 booking/ 뿐
// 아니라 한 단계 더 깊은 booking/inicis/ 에서도 include 되므로, 상대 경로로 적으면 그쪽에서
// booking/common.php 를 찾다가 실패한다.
include_once(dirname(__DIR__).'/common.php');
include_once(G5_LIB_PATH.'/booking.lib.php');

// 테이블이 없으면 보여 줄 것이 없다. 여기서 만들지는 않는다 —
// 설치(DDL)는 관리자 첫 진입(adm/booking/_common.php)의 몫이고,
// 익명 요청이 스키마를 건드리는 길은 열어 두지 않는다.
if (!booking_installed()) {
    // JSON 엔드포인트에 HTML 알림을 흘리면 호출한 쪽에서 파싱 오류로만 보인다
    if (defined('G5_BOOKING_JSON')) {
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(array('error' => '예약 모듈이 아직 설치되지 않았습니다.')));
    }
    alert('예약 모듈이 아직 설치되지 않았습니다. 관리자에게 문의해 주세요.', G5_URL);
}

// 객실 이미지 URL. 저장은 data/booking/ 아래 파일명만 DB(bi_file)에 남긴다
function booking_image_url($file)
{
    return $file ? G5_DATA_URL.'/booking/'.$file : '';
}
