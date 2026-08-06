<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 이니시스 표준결제(INIStdPay) 어댑터 ----------
// 순정 라이브러리(shop/inicis/libs)를 참조만 한다 — INIStdPayUtil(서명), properties(승인·망취소
// URL 화이트리스트), HttpClient(전문 왕복). 흐름과 검증 순서는 부킹 모듈에서 실결제로 검증된
// 구조를 그대로 따른다: 사전검증 → 승인 → 응답 로그 선기록 → 대조 → 확정, 어긋나면 망취소.

function cart_inicis_libs()
{
    $path = (defined('G5_SHOP_PATH') ? G5_SHOP_PATH : G5_PATH.'/shop').'/inicis/libs';
    if (!class_exists('INIStdPayUtil')) include_once($path.'/INIStdPayUtil.php');
    if (!class_exists('properties'))    include_once($path.'/properties.php');
    if (!class_exists('HttpClient'))    include_once($path.'/HttpClient.php');
}

function cart_inicis_conf()
{
    $cc = cart_config();
    return array(
        'mid' => trim($cc['cc_inicis_mid']),
        'sign_key' => trim($cc['cc_inicis_signkey']),
        'js_url' => 'https://stdpay.inicis.com/stdjs/INIStdPay.js',
    );
}

// 결제창 파라미터 — 새 oid 발급·기록 후 서명까지 만들어 돌려준다
function cart_inicis_ready($od)
{
    cart_inicis_libs();
    $conf = cart_inicis_conf();
    $util = new INIStdPayUtil();

    $oid = cart_pay_new_oid($od);
    $timestamp = $util->getTimestamp();
    $price = (int)$od['od_total'];

    cart_payment_log((int)$od['od_id'], 'inicis', '', $price, 'req',
        array('step' => 'ready', 'oid' => $oid, 'timestamp' => $timestamp));

    return array(
        'js_url' => $conf['js_url'],
        'fields' => array(
            'version' => '1.0',
            'gopaymethod' => 'Card',
            'mid' => $conf['mid'],
            'oid' => $oid,
            'price' => $price,
            'timestamp' => $timestamp,
            'use_chkfake' => 'Y',
            'signature' => $util->makeSignature(array(
                'oid' => $oid, 'price' => $price, 'timestamp' => $timestamp)),
            'verification' => $util->makeSignature(array(
                'oid' => $oid, 'price' => $price, 'signKey' => $conf['sign_key'], 'timestamp' => $timestamp)),
            'mKey' => $util->makeHash($conf['sign_key'], 'sha256'),
            'currency' => 'WON',
            'goodname' => cart_pay_goodname((int)$od['od_id']),
            'buyername' => $od['od_name'],
            'buyertel' => $od['od_hp'],
            'buyeremail' => $od['od_email'],
            'returnUrl' => cart_url('pay_return.php', array('m' => 'inicis')),
            'closeUrl' => (defined('G5_SHOP_URL') ? G5_SHOP_URL : G5_URL.'/shop').'/inicis/close.php',
            'acceptmethod' => 'below1000',
        ),
    );
}

// 승인 리턴 처리 — 성공하면 완료 URL 반환, 실패하면 alert(내부에서 exit).
function cart_inicis_return()
{
    cart_inicis_libs();
    $conf = cart_inicis_conf();
    $util = new INIStdPayUtil();
    $prop = new properties();

    $pay_url = cart_url('basket.php');

    // 1. 승인 전 검증 — 여기서 멈추면 돈은 움직이지 않았다
    if (strcmp('0000', cart_pay_req('resultCode')) !== 0) {
        $msg = clean_xss_tags(cart_pay_req('resultMsg'));
        alert($msg !== '' ? $msg : '결제가 취소되었습니다.', $pay_url);
    }

    $oid = preg_replace('/[^A-Za-z0-9\-_]/', '', cart_pay_req('orderNumber'));
    if ($oid === '') alert('주문번호가 없습니다.', $pay_url);
    $od = cart_order_get_by_oid($oid);
    if (!$od) alert('결제 대상 주문을 찾을 수 없습니다.', $pay_url);
    $od_id = (int)$od['od_id'];
    $price = (int)$od['od_total'];
    $retry_url = cart_url('pay.php', array('od_no' => $od['od_no']));

    if (cart_pay_req('mid') !== $conf['mid']) {
        alert('요청된 상점아이디가 설정과 다릅니다.', $retry_url);
    }

    // 승인 URL 은 결제창이 준 값을 그대로 믿지 않는다 — idc_name 화이트리스트로 재조립·대조
    $idc_list = array('fc', 'ks', 'stg');
    $idc_name = cart_pay_req('idc_name');
    if (!in_array($idc_name, $idc_list, true)
        || strcmp($prop->getAuthUrl($idc_name), cart_pay_req('authUrl')) !== 0) {
        cart_payment_log($od_id, 'inicis', '', $price, 'failed',
            array('step' => 'urlfail', 'idc_name' => $idc_name, 'authUrl' => cart_pay_req('authUrl')));
        alert('승인 요청 주소가 올바르지 않습니다.', $retry_url);
    }
    $authUrl = $prop->getAuthUrl($idc_name);
    $authToken = cart_pay_req('authToken');
    if ($authToken === '') alert('인증 토큰이 없습니다.', $retry_url);

    // 2. 승인 요청
    $timestamp = $util->getTimestamp();
    $authMap = array(
        'mid' => $conf['mid'],
        'authToken' => $authToken,
        'signature' => $util->makeSignature(array('authToken' => $authToken, 'timestamp' => $timestamp)),
        'timestamp' => $timestamp,
        'charset' => 'UTF-8',
        'format' => 'JSON',
    );
    cart_payment_log($od_id, 'inicis', '', $price, 'req',
        array('step' => 'auth_req', 'oid' => $oid, 'authUrl' => $authUrl, 'timestamp' => $timestamp));

    $http = new HttpClient();
    $comm_ok = $http->processHTTP($authUrl, $authMap);
    $res = $comm_ok ? json_decode($http->body, true) : null;
    if (!is_array($res)) $res = array();
    $tid = cart_pay_res($res, 'tid');
    $res_code = cart_pay_res($res, 'resultCode');

    // 응답을 확정보다 먼저 남긴다 — 뒤에서 무엇이 터지든 승인 유무를 이 줄로 되짚는다
    cart_payment_log($od_id, 'inicis', $tid, (int)cart_pay_res($res, 'TotPrice'),
        ($res_code === '0000') ? 'res' : 'failed',
        $comm_ok ? $http->body : ('통신 실패: '.$http->getErrorMsg()));

    // 3. 승인 결과 검증 — 금액·주문번호·서명이 한 치도 어긋나지 않아야 확정
    $fail = '';
    $fail_msg = '';
    if (!$comm_ok) {
        $fail = 'http';   // 요청은 갔는데 응답만 끊겼을 수 있다 — 취소 쪽으로 실패한다
    } elseif ($res_code === '') {
        $fail = 'parse';
    } elseif (strcmp('0000', $res_code) !== 0) {
        $fail = 'code';
        $fail_msg = clean_xss_tags(cart_pay_res($res, 'resultMsg'));
    } elseif (cart_pay_res($res, 'MOID') === '' || cart_pay_res($res, 'TotPrice') === ''
        || cart_pay_res($res, 'authSignature') === '') {
        $fail = 'parse';
    } else {
        $secure = array('mid' => $conf['mid'], 'tstamp' => $timestamp,
            'MOID' => cart_pay_res($res, 'MOID'), 'TotPrice' => cart_pay_res($res, 'TotPrice'));
        if (strcmp($util->makeSignatureAuth($secure), cart_pay_res($res, 'authSignature')) !== 0) $fail = 'signature';
        elseif (cart_pay_res($res, 'MOID') !== $oid) $fail = 'moid';
        elseif ((int)cart_pay_res($res, 'TotPrice') !== $price) $fail = 'amount';
    }

    // 4. 확정 — 주문 행을 잠그고 잠긴 값으로 재검증
    if ($fail === '') {
        $fail = cart_order_confirm_paid($od_id, $oid, 'inicis', $tid, (int)cart_pay_res($res, 'TotPrice'));
    }

    // 5. 실패 → 망취소로 승인을 되돌린다.
    // tid 유무는 조건에 넣지 않는다 — 'http'(응답 유실)·'parse'(해석 불가)는 정의상 tid 를
    // 모르는 채 승인이 잡혀 있을 수 있는 실패다. 망취소 전문은 authToken 만 쓰므로 tid 없이
    // 보내면 되고, 승인이 실제로 없었다면 이니시스가 "취소 대상 없음"으로 응답할 뿐 무해하다.
    if ($fail !== '') {
        $net_url = in_array($idc_name, $idc_list, true) ? $prop->getNetCancel($idc_name) : '';
        $sent = 'skip';
        $body = '';
        if ($net_url !== '' && in_array($fail, array('http', 'parse', 'signature', 'moid', 'amount', 'amount2', 'duplicate', 'status', 'oid', 'update', 'missing'), true)) {
            $net_ts = $util->getTimestamp();
            $netMap = array(
                'mid' => $conf['mid'],
                'authToken' => $authToken,
                'signature' => $util->makeSignature(array('authToken' => $authToken, 'timestamp' => $net_ts)),
                'timestamp' => $net_ts,
                'charset' => 'UTF-8',
                'format' => 'JSON',
            );
            $net = new HttpClient();
            $sent = $net->processHTTP($net_url, $netMap) ? 'sent' : 'commfail';
            $body = $sent === 'sent' ? $net->body : $net->getErrorMsg();
        }
        cart_payment_log($od_id, 'inicis', $tid, $price, 'netcancel',
            array('reason' => $fail, 'sent' => $sent, 'body' => $body));
        if ($sent !== 'sent') {
            // 망취소가 안 나갔거나 통신이 끊겼다 — 이중 결제 가능 상태. 운영 시그널을 남기고
            // 사용자에게 "취소했습니다"라고 단정하지 않는다
            error_log('[cart-pay] inicis netcancel '.$sent.' od_id='.$od_id.' reason='.$fail.' tid='.$tid);
            alert('결제 확인이 지연되고 있습니다. 잠시 후 주문 조회에서 상태를 확인해 주세요. 중복 결제된 경우 자동으로 취소되며, 계속되면 고객센터로 문의해 주세요.', $retry_url);
        }
        alert($fail_msg !== '' ? $fail_msg : '결제를 확정하지 못해 승인을 취소했습니다. 다시 시도해 주세요. ('.$fail.')',
            $retry_url);
    }

    // 성공 — approved 이력은 확정 트랜잭션 안에서 이미 남았다(중복 리턴 오판 방지)
    $_SESSION['ss_cart_last_od_no'] = $od['od_no'];
    return cart_url('complete.php', array('od_no' => $od['od_no']));
}
