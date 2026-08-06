<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 토스페이먼츠 어댑터 ----------
// 결제창은 v1 SDK(requestPayment), 승인은 서버가 /v1/payments/confirm 을 부르는 동기 구조.
// confirm 은 요청 금액과 실제 결제 금액이 다르면 토스가 거절하므로, 우리는 [주문 조회 →
// successUrl 의 amount 를 od_total 과 대조 → confirm → 응답 금액·orderId 재대조 → 확정]
// 순서로 이중 확인한다. 확정 실패 시 취소 API 로 되돌린다.

function cart_toss_conf()
{
    $cc = cart_config();
    return array(
        'ckey' => trim($cc['cc_toss_ckey']),
        'skey' => trim($cc['cc_toss_skey']),
        'js_url' => 'https://js.tosspayments.com/v1/payment',
        'api' => 'https://api.tosspayments.com/v1/payments',
    );
}

function cart_toss_auth_header($skey)
{
    return 'Authorization: Basic '.base64_encode($skey.':');
}

function cart_toss_ready($od)
{
    $conf = cart_toss_conf();
    $oid = cart_pay_new_oid($od);

    cart_payment_log((int)$od['od_id'], 'toss', '', (int)$od['od_total'], 'req',
        array('step' => 'ready', 'oid' => $oid));

    return array(
        'js_url' => $conf['js_url'],
        'ckey' => $conf['ckey'],
        'params' => array(
            'amount' => (int)$od['od_total'],
            'orderId' => $oid,
            'orderName' => cart_pay_goodname((int)$od['od_id']),
            'customerName' => $od['od_name'],
            'successUrl' => cart_url('pay_return.php', array('m' => 'toss')),
            'failUrl' => cart_url('pay.php', array('od_no' => $od['od_no'], 'fail' => '1')),
        ),
    );
}

// successUrl 리턴 처리 — 성공 시 완료 URL 반환, 실패 시 alert(내부 exit)
function cart_toss_return()
{
    $conf = cart_toss_conf();
    $pay_url = cart_url('basket.php');

    $payment_key = preg_replace('/[^A-Za-z0-9\-_]/', '', cart_pay_req('paymentKey'));
    $oid = preg_replace('/[^A-Za-z0-9\-_]/', '', cart_pay_req('orderId'));
    $amount = (int)cart_pay_req('amount');
    if ($payment_key === '' || $oid === '') alert('결제 정보가 없습니다.', $pay_url);

    $od = cart_order_get_by_oid($oid);
    if (!$od) alert('결제 대상 주문을 찾을 수 없습니다.', $pay_url);
    $od_id = (int)$od['od_id'];
    $price = (int)$od['od_total'];
    $retry_url = cart_url('pay.php', array('od_no' => $od['od_no']));

    // 승인 전 대조 — successUrl 파라미터의 금액이 주문 금액과 다르면 승인 자체를 안 부른다
    if ($amount !== $price) {
        cart_payment_log($od_id, 'toss', $payment_key, $amount, 'failed',
            array('step' => 'precheck', 'reason' => 'amount', 'expect' => $price));
        alert('결제 금액이 주문 금액과 다릅니다.', $retry_url);
    }

    cart_payment_log($od_id, 'toss', $payment_key, $price, 'req',
        array('step' => 'confirm_req', 'oid' => $oid));

    list($code, $res, $raw) = cart_http_post_json($conf['api'].'/confirm',
        array('paymentKey' => $payment_key, 'orderId' => $oid, 'amount' => $price),
        array(cart_toss_auth_header($conf['skey'])));

    // 응답을 확정보다 먼저 남긴다
    cart_payment_log($od_id, 'toss', $payment_key, $price,
        ($code === 200) ? 'res' : 'failed', $raw !== '' ? $raw : ('http '.$code));

    // 실패 분류가 곧 취소 발동 매트릭스다:
    //   code  = 4xx — 토스가 승인 자체를 거절, 돈이 잡히지 않았음이 확실 → 취소 생략
    //   parse = 200인데 본문 해석 불가 — 승인(매입)은 이미 성공한 상태 → 반드시 취소
    //   http  = 무응답(0)·5xx — 승인 여부 불명 → 취소 쪽으로 실패한다
    //   상태는 카드 confirm 의 정상 종결인 DONE 만 성공으로 본다(IN_PROGRESS 는 매입 미완)
    $fail = '';
    $fail_msg = '';
    if ($code >= 400 && $code < 500) {
        $fail = 'code';
        if (is_array($res)) $fail_msg = clean_xss_tags(cart_pay_res($res, 'message'));
    } elseif ($code === 200 && !is_array($res)) {
        $fail = 'parse';
    } elseif ($code !== 200) {
        $fail = 'http';
    } elseif (cart_pay_res($res, 'orderId') !== $oid) {
        $fail = 'moid';
    } elseif ((int)cart_pay_res($res, 'totalAmount') !== $price) {
        $fail = 'amount';
    } elseif (cart_pay_res($res, 'status') !== 'DONE') {
        $fail = 'pgstatus';
    }

    if ($fail === '') {
        $fail = cart_order_confirm_paid($od_id, $oid, 'toss', $payment_key, $price);
    }

    if ($fail !== '') {
        // 승인이 났을 수 있는 실패(통신 단절·해석 불가 포함)는 취소 API 로 되돌린다
        $sent = 'skip';
        $body = '';
        if ($payment_key !== '' && $fail !== 'code') {
            list($ccode, $cres, $craw) = cart_http_post_json($conf['api'].'/'.$payment_key.'/cancel',
                array('cancelReason' => '주문 확정 실패 자동취소 ('.$fail.')'),
                array(cart_toss_auth_header($conf['skey'])));
            $sent = ($ccode === 200) ? 'sent' : 'commfail';
            $body = $craw;
        }
        cart_payment_log($od_id, 'toss', $payment_key, $price, 'netcancel',
            array('reason' => $fail, 'sent' => $sent, 'body' => $body));
        if ($fail !== 'code' && $sent !== 'sent') {
            error_log('[cart-pay] toss cancel '.$sent.' od_id='.$od_id.' reason='.$fail.' paymentKey='.$payment_key);
            alert('결제 확인이 지연되고 있습니다. 잠시 후 주문 조회에서 상태를 확인해 주세요. 중복 결제된 경우 자동으로 취소되며, 계속되면 고객센터로 문의해 주세요.', $retry_url);
        }
        alert($fail_msg !== '' ? $fail_msg : '결제를 확정하지 못해 승인을 취소했습니다. 다시 시도해 주세요. ('.$fail.')',
            $retry_url);
    }

    // approved 이력은 확정 트랜잭션 안에서 이미 남았다
    $_SESSION['ss_cart_last_od_no'] = $od['od_no'];
    return cart_url('complete.php', array('od_no' => $od['od_no']));
}
