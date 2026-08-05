<?php
if (!defined('_GNUBOARD_')) exit;

// 뿌리오(비즈뿌리오) 문자 발송 — https://bizppurio.github.io/bizapi/
//
// 인증은 두 단계다: 연동 계정·비밀번호로 토큰을 받고(24시간 유효), 발송은 그 토큰으로 한다.
// 토큰은 파일로 캐싱한다 — 매 발송마다 발급받으면 토큰 API 가 율리밋에 걸린다.
// 설정: cf_ppurio_id(연동 계정) · cf_ppurio_pw(연동 비밀번호) · cf_ppurio_from(등록 발신번호)
//       · cf_ppurio_dev(1 이면 검수 서버)

function ppurio_api_host()
{
    global $config;
    return !empty($config['cf_ppurio_dev']) ? 'https://dev-api.bizppurio.com' : 'https://api.bizppurio.com';
}

// 문자 길이 판정용 바이트 수 — 통신사와 뿌리오는 EUC-KR 기준으로 센다 (UTF-8 로 세면 한글이 3바이트라 과대계산)
function ppurio_byte_len($text)
{
    $euc = @iconv('UTF-8', 'EUC-KR//IGNORE', $text);
    return strlen($euc !== false ? $euc : $text);
}

function ppurio_http($url, $headers, $body)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ));
    $res = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($res === false) return array('status' => 0, 'body' => null, 'error' => $err);
    return array('status' => $status, 'body' => json_decode($res, true), 'error' => '');
}

// 토큰을 돌려준다. $force 면 캐시를 버리고 새로 받는다 (발송이 인증 오류로 거절됐을 때)
function ppurio_token($force = false)
{
    global $config;
    $cache_dir = G5_DATA_PATH.'/cache/ppurio';
    $cache = $cache_dir.'/token.php';

    if (!$force && is_file($cache)) {
        // 캐시 형식: <?php exit; 뒤에 "만료유닉스타임|토큰" — 웹으로 긁어도 exit 만 나온다
        $line = trim((string)@file_get_contents($cache));
        $pos = strpos($line, "\n");
        if ($pos !== false) {
            list($expire, $token) = explode('|', substr($line, $pos + 1), 2) + array('', '');
            // 만료 1시간 전부터는 새로 받는다 — 발송 도중에 만료되는 낭패를 피한다
            if ($token !== '' && (int)$expire > time() + 3600) return $token;
        }
    }

    $basic = base64_encode($config['cf_ppurio_id'].':'.$config['cf_ppurio_pw']);
    $r = ppurio_http(ppurio_api_host().'/v1/token',
        array('Authorization: Basic '.$basic, 'Content-Type: application/json'), '');
    if ($r['status'] !== 200 || empty($r['body']['accesstoken'])) return '';

    // 응답의 expired(yyyyMMddHHmmss)를 그대로 믿기보다 보수적으로 23시간을 쓴다 — 시계가 어긋나도 안전
    if (!is_dir($cache_dir)) { @mkdir($cache_dir, G5_DIR_PERMISSION, true); @chmod($cache_dir, G5_DIR_PERMISSION); }
    @file_put_contents($cache, "<?php exit; ?>\n".(time() + 23 * 3600).'|'.$r['body']['accesstoken']);
    @chmod($cache, G5_FILE_PERMISSION);

    return $r['body']['accesstoken'];
}

// 문자 한 건 발송. 90바이트(EUC-KR) 이하는 SMS, 넘으면 LMS 로 자동 승격.
// $opt: 'from' => 발신번호(설정값 대신 쓸 때, 뿌리오에 등록된 번호여야 한다)
//       'sendtime' => 예약 발송 유닉스타임 (최대 30일 이내, 과거면 즉시 발송)
// 반환: array('ok' => bool, 'msg' => 사유, 'key' => 뿌리오 messagekey)
function ppurio_send_sms($to, $message, $opt = array())
{
    global $config;
    if (empty($config['cf_ppurio_id']) || empty($config['cf_ppurio_pw']))
        return array('ok' => false, 'msg' => '뿌리오 연동 계정이 설정되지 않았습니다.', 'key' => '');
    $from = preg_replace('/[^0-9]/', '', isset($opt['from']) ? (string)$opt['from'] : '');
    if ($from === '') $from = preg_replace('/[^0-9]/', '', (string)$config['cf_ppurio_from']);
    if ($from === '') return array('ok' => false, 'msg' => '뿌리오 발신번호가 설정되지 않았습니다.', 'key' => '');
    $to = preg_replace('/[^0-9]/', '', (string)$to);
    if ($to === '') return array('ok' => false, 'msg' => '수신번호가 없습니다.', 'key' => '');

    $token = ppurio_token();
    if ($token === '') return array('ok' => false, 'msg' => '뿌리오 토큰 발급에 실패했습니다. 연동 계정·비밀번호를 확인하세요.', 'key' => '');

    if (ppurio_byte_len($message) <= 90) {
        $type = 'sms';
        $content = array('sms' => array('message' => $message));
    } else {
        $type = 'lms';
        $content = array('lms' => array('message' => $message, 'subject' => mb_substr($message, 0, 20, 'UTF-8')));
    }
    $req = array(
        'account' => $config['cf_ppurio_id'],
        'type' => $type, 'from' => $from, 'to' => $to,
        'content' => $content,
    );
    if (!empty($opt['sendtime']) && (int)$opt['sendtime'] > time())
        $req['sendtime'] = (int)$opt['sendtime'];
    $body = json_encode($req, JSON_UNESCAPED_UNICODE);

    $r = ppurio_http(ppurio_api_host().'/v3/message',
        array('Authorization: Bearer '.$token, 'Content-Type: application/json'), $body);

    // 인증 거절이면 캐시된 토큰이 상한 것일 수 있다 — 한 번만 새 토큰으로 재시도
    if ($r['status'] === 401) {
        $token = ppurio_token(true);
        if ($token !== '') {
            $r = ppurio_http(ppurio_api_host().'/v3/message',
                array('Authorization: Bearer '.$token, 'Content-Type: application/json'), $body);
        }
    }

    if ($r['status'] === 200 && isset($r['body']['code']) && (int)$r['body']['code'] === 1000)
        return array('ok' => true, 'msg' => 'ok',
            'key' => isset($r['body']['messagekey']) ? (string)$r['body']['messagekey'] : '');

    $desc = isset($r['body']['description']) ? $r['body']['description'] : $r['error'];
    $code = isset($r['body']['code']) ? $r['body']['code'] : $r['status'];
    return array('ok' => false, 'msg' => '뿌리오 발송 거절 ('.$code.' '.$desc.')', 'key' => '');
}
