<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 쿠폰 ----------
// 발급 경로는 넷(코드 입력·관리자 지급·가입 축하·첫구매)이지만 기계는 하나다:
// **어느 길로 왔든 회원 쿠폰함에 한 장이 들어오고, 주문서에서 한 장을 골라 쓴다.**
// 경로마다 다른 계산·다른 화면을 만들면 규칙이 넷이 되고, 그중 하나는 반드시 틀어진다.
//
// 비회원은 쿠폰을 쓸 수 없다 — "쿠폰함" 이라는 개념이 회원에게만 성립하고,
// 코드 한 줄로 익명 다중 사용을 막을 방법이 마땅치 않다.
//
// 규칙 요약 (자세한 근거는 docs 로드맵 3단계)
//   · 주문당 한 장. 두 장이 겹치는 순간 반품·부분취소의 안분 계산이 시작된다.
//   · 할인 대상은 cp_target 에 걸리는 품목 합계. 배송비는 깎지 않는다(무료배송 기준이 그 몫).
//   · 정률은 10원 절사. cp_max 로 상한을 둘 수 있다("10% 최대 5천원").
//   · 최소 주문금액은 할인 전 상품합 기준 — 주문서의 expect_item_total 과 같은 잣대다.

// 발급 경로 — cp_issue
function cart_coupon_issues()
{
    return array(
        'code'     => '코드 입력',
        'manual'   => '관리자 지급',
        'join'     => '가입 축하',
        'firstbuy' => '첫 구매',
    );
}

function cart_coupon_types()
{
    return array('rate' => '정률(%)', 'fixed' => '정액(원)');
}

function cart_coupon_get($cp_id)
{
    global $g5;
    $cp_id = (int)$cp_id;
    if ($cp_id < 1) return null;
    return sql_fetch(" select * from `{$g5['ycart_coupon_table']}` where cp_id = '$cp_id' ");
}

// 할인 문구 — 목록·쿠폰함·주문서가 같은 말을 쓰게 한 곳에서 만든다
function cart_coupon_label($cp)
{
    if (!$cp) return '';
    if ($cp['cp_type'] === 'rate') {
        $s = (int)$cp['cp_value'].'% 할인';
        if ((int)$cp['cp_max'] > 0) $s .= ' (최대 '.number_format((int)$cp['cp_max']).'원)';
        return $s;
    }
    return number_format((int)$cp['cp_value']).'원 할인';
}

// 쓸 수 있는 범위 — '' 전체 / 'ca:분류코드' / 'it:상품id'
function cart_coupon_target_label($cp)
{
    global $g5;
    $t = trim($cp['cp_target']);
    if ($t === '') return '전체 상품';
    if (strpos($t, 'ca:') === 0) {
        $ca = cart_category_get_by_code(substr($t, 3));
        return $ca ? $ca['ca_name'].' 분류' : '없는 분류('.substr($t, 3).')';
    }
    if (strpos($t, 'it:') === 0) {
        $it = sql_fetch(" select it_name from `{$g5['ycart_item_table']}`
            where it_id = '".(int)substr($t, 3)."' ");
        return $it ? $it['it_name'] : '없는 상품';
    }
    return '전체 상품';
}

// 이 품목이 쿠폰의 대상인가. 분류는 하위 분류까지 인정한다 —
// "겨울옷 10%" 를 걸면 그 아래 코트·니트가 함께 걸리는 것이 사람의 기대다.
function cart_coupon_line_matches($cp, $line)
{
    $t = trim($cp['cp_target']);
    if ($t === '') return true;

    if (strpos($t, 'it:') === 0) return (int)$line['it_id'] === (int)substr($t, 3);

    if (strpos($t, 'ca:') === 0) {
        static $cache = array();
        $code = substr($t, 3);
        if (!isset($cache[$code])) {
            $ca = cart_category_get_by_code($code);
            $cache[$code] = $ca
                ? array_merge(array((int)$ca['ca_id']), cart_category_descendant_ids((int)$ca['ca_id']))
                : array();
        }
        foreach (cart_item_ca_ids((int)$line['it_id']) as $ca_id) {
            if (in_array((int)$ca_id, $cache[$code], true)) return true;
        }
        return false;
    }
    return true;
}

// 깎일 금액. $lines 는 주문 대상 줄들(장바구니 행 또는 주문 품목) —
// 필요한 것은 it_id 와 줄 합계뿐이라 두 모양을 함께 받는다.
// 정률은 10원 절사한다(원 단위가 남으면 카드 승인액과 눈으로 대조하기 나쁘다).
function cart_coupon_discount($cp, $lines)
{
    $base = 0;
    foreach ($lines as $l) {
        if (!cart_coupon_line_matches($cp, $l)) continue;
        $base += isset($l['line_total'])
            ? (int)$l['line_total']
            : (isset($l['oi_total']) ? (int)$l['oi_total'] : (int)$l['sk_price'] * (int)$l['ct_qty']);
    }
    if ($base <= 0) return 0;

    if ($cp['cp_type'] === 'rate') {
        $amount = (int)floor($base * (int)$cp['cp_value'] / 100 / 10) * 10;
        if ((int)$cp['cp_max'] > 0 && $amount > (int)$cp['cp_max']) $amount = (int)$cp['cp_max'];
    } else {
        $amount = (int)$cp['cp_value'];
    }
    // 대상 품목 합계보다 더 깎지 않는다 — 정액 쿠폰이 배송비까지 먹으면 안 된다
    if ($amount > $base) $amount = $base;
    return max(0, $amount);
}

// 이 장을 지금 이 주문에 쓸 수 있나. 쓸 수 있으면 '', 아니면 사람이 읽을 이유.
// 화면과 주문 확정이 같은 함수를 부른다 — 주문서가 "쓸 수 있음" 이라 했는데 제출이
// 다른 이유로 막히면 손님은 무엇을 고쳐야 할지 알 수 없다.
// $cm 은 쿠폰함 행과 쿠폰 정의를 join 한 한 줄이다(cart_coupon_mine 이 주는 모양).
function cart_coupon_why_not($cm, $lines, $item_total)
{
    if (!$cm || !(int)$cm['cp_use']) return '지금은 쓸 수 없는 쿠폰입니다';
    if ((int)$cm['cm_od_id'] > 0) return '이미 사용한 쿠폰입니다';
    if ($cm['cm_end'] < date('Y-m-d', G5_SERVER_TIME)) return '사용 기한이 지났습니다';
    if ((int)$cm['cp_min'] > 0 && $item_total < (int)$cm['cp_min']) {
        return number_format((int)$cm['cp_min']).'원 이상 구매할 때 쓸 수 있습니다';
    }
    if (cart_coupon_discount($cm, $lines) <= 0) {
        return trim($cm['cp_target']) === ''
            ? '이 주문에서는 깎일 금액이 없습니다'
            : cart_coupon_target_label($cm).' 상품이 이 주문에 없습니다';
    }
    return '';
}

// ---------- 발급 ----------

// 한 장 발급. 이미 있으면 아무 일도 안 한다(UNIQUE 가 최종 방어선).
// 성공하면 cm_id, 아니면 0.
function cart_coupon_issue($cp, $mb_id)
{
    global $g5;
    $mb_id = trim($mb_id);
    if (!$cp || $mb_id === '' || !(int)$cp['cp_use']) return 0;

    $today = date('Y-m-d', G5_SERVER_TIME);
    if ($cp['cp_begin'] > $today || $cp['cp_end'] < $today) return 0;

    // 이 장의 만료일 — 받은 날부터 세는 쿠폰(cp_days)은 그 날짜, 아니면 쿠폰 자체의 종료일.
    // 발급 시점에 굳혀 둔다: 나중에 관리자가 기간을 줄여도 이미 받은 사람의 기한은 안 줄어든다.
    $end = (int)$cp['cp_days'] > 0
        ? date('Y-m-d', G5_SERVER_TIME + (int)$cp['cp_days'] * 86400)
        : $cp['cp_end'];
    if ($end > $cp['cp_end']) $end = $cp['cp_end'];

    // insert ignore 는 무시됐을 때도 오류를 안 낸다 — 진짜 들어갔는지는 affected rows 로만 안다
    // (sql_insert_id 는 무시된 경우 이전 값이 남아 "발급했다" 로 오독된다)
    sql_query(" insert ignore into `{$g5['ycart_coupon_mb_table']}`
        (cp_id, mb_id, cm_end, cm_issued_at)
        values ('".(int)$cp['cp_id']."', '".sql_real_escape_string($mb_id)."',
                '".sql_real_escape_string($end)."', '".G5_TIME_YMDHIS."') ", true);
    return get_sql_affected_rows() > 0 ? (int)sql_insert_id() : 0;
}

// 자동 지급을 "지연 발급" 으로 — 가입 순간에 주려면 순정 회원가입 코드에 훅을 박아야 한다.
// 대신 쿠폰함·주문서에 들어올 때 "받을 자격이 있는데 아직 없는" 쿠폰을 그 자리에서 준다.
// 손님 눈에는 차이가 없고(처음 보는 화면에 이미 들어와 있다), 순정은 한 줄도 안 바뀐다.
// UNIQUE (cp_id, mb_id) 가 중복 발급을 막으므로 몇 번 불려도 안전하다.
//
// 첫구매 쿠폰은 "구매를 마친 적이 있는 회원" 에게 준다 — 구매확정(confirmed)을 기준으로 삼아
// 주문하자마자 받아 그 주문에 쓰는 고리를 끊는다.
function cart_coupon_grant_auto($mb_id)
{
    global $g5;
    $mb_id = trim($mb_id);
    if ($mb_id === '') return 0;

    static $done = array();
    if (isset($done[$mb_id])) return 0;
    $done[$mb_id] = true;

    $today = sql_real_escape_string(date('Y-m-d', G5_SERVER_TIME));
    $mb_e = sql_real_escape_string($mb_id);

    $n = 0;
    $result = sql_query(" select * from `{$g5['ycart_coupon_table']}`
        where cp_use = 1 and cp_issue in ('join', 'firstbuy')
          and cp_begin <= '$today' and cp_end >= '$today'
          and cp_id not in (select cp_id from `{$g5['ycart_coupon_mb_table']}` where mb_id = '$mb_e') ");
    $pending = array();
    while ($r = sql_fetch_array($result)) $pending[] = $r;

    foreach ($pending as $cp) {
        if ($cp['cp_issue'] === 'firstbuy' && !cart_coupon_has_bought($mb_id)) continue;
        if (cart_coupon_issue($cp, $mb_id)) $n++;
    }
    return $n;
}

// 첫구매 쿠폰의 자격 — 구매확정한 주문이 한 건이라도 있으면 준다
function cart_coupon_has_bought($mb_id)
{
    global $g5;
    static $cache = array();
    if (isset($cache[$mb_id])) return $cache[$mb_id];
    $row = sql_fetch(" select od_id from `{$g5['ycart_order_table']}`
        where mb_id = '".sql_real_escape_string($mb_id)."' and od_status = 'confirmed' limit 1 ");
    return $cache[$mb_id] = (bool)$row;
}

// 코드 입력 — 쿠폰함에 담는 방법 중 하나일 뿐이다. 담기면 나머지는 다른 쿠폰과 똑같이 흐른다.
// 돌려주는 값: array('ok' => bool, 'msg' => 사람이 읽을 말)
function cart_coupon_redeem_code($mb_id, $code)
{
    global $g5;
    $mb_id = trim($mb_id);
    $code = strtoupper(trim($code));
    if ($mb_id === '') return array('ok' => false, 'msg' => '회원만 쿠폰을 받을 수 있습니다.');
    if ($code === '') return array('ok' => false, 'msg' => '쿠폰 코드를 입력해 주세요.');

    $cp = sql_fetch(" select * from `{$g5['ycart_coupon_table']}`
        where cp_code = '".sql_real_escape_string($code)."' and cp_use = 1 ");
    // 없는 코드와 기간 지난 코드를 구별해 알린다 — "왜 안 되는지" 를 모르면 다시 시도만 한다
    if (!$cp) return array('ok' => false, 'msg' => '없는 쿠폰 코드입니다. 다시 확인해 주세요.');

    $today = date('Y-m-d', G5_SERVER_TIME);
    if ($cp['cp_begin'] > $today) return array('ok' => false, 'msg' => '아직 받을 수 없는 쿠폰입니다.');
    if ($cp['cp_end'] < $today) return array('ok' => false, 'msg' => '기간이 끝난 쿠폰입니다.');

    $have = sql_fetch(" select cm_id from `{$g5['ycart_coupon_mb_table']}`
        where cp_id = '".(int)$cp['cp_id']."' and mb_id = '".sql_real_escape_string($mb_id)."' ");
    if ($have) return array('ok' => false, 'msg' => '이미 받은 쿠폰입니다.');

    if (!cart_coupon_issue($cp, $mb_id)) {
        return array('ok' => false, 'msg' => '쿠폰을 받지 못했습니다. 잠시 후 다시 시도해 주세요.');
    }
    return array('ok' => true, 'msg' => '"'.$cp['cp_name'].'" 쿠폰을 받았습니다.');
}

// ---------- 쿠폰함 ----------

// 내 쿠폰 목록. 쓴 것·기한 지난 것도 함께 준다(왜 없는지 보이지 않으면 사라진 것으로 읽힌다).
// 정렬은 "쓸 수 있는 것 먼저, 그중 기한이 임박한 것 먼저".
function cart_coupon_mine($mb_id, $include_used = true)
{
    global $g5;
    $mb_id = trim($mb_id);
    if ($mb_id === '') return array();

    $where = $include_used ? '' : " and m.cm_od_id = 0 ";
    $rows = array();
    // c.* 를 먼저 펼치고 m.* 를 나중에 — 같은 이름(cp_id)은 뒤엣것이 이긴다
    $result = sql_query(" select c.*, m.*
        from `{$g5['ycart_coupon_mb_table']}` m
        inner join `{$g5['ycart_coupon_table']}` c on c.cp_id = m.cp_id
        where m.mb_id = '".sql_real_escape_string($mb_id)."' $where
        order by m.cm_od_id asc, m.cm_end asc, m.cm_id desc ");

    $today = date('Y-m-d', G5_SERVER_TIME);
    while ($r = sql_fetch_array($result)) {
        $r['used'] = ((int)$r['cm_od_id'] > 0);
        $r['expired'] = (!$r['used'] && $r['cm_end'] < $today);
        $r['live'] = (!$r['used'] && !$r['expired'] && (int)$r['cp_use'] === 1);
        $r['label'] = cart_coupon_label($r);
        $r['target_label'] = cart_coupon_target_label($r);
        $r['left_days'] = (int)floor((strtotime($r['cm_end'].' 23:59:59') - G5_SERVER_TIME) / 86400);
        $rows[] = $r;
    }
    // 쓴 것·기한 지난 것은 뒤로 (SQL 정렬은 사용 여부까지만 가른다)
    usort($rows, function ($a, $b) {
        if ($a['live'] !== $b['live']) return $a['live'] ? -1 : 1;
        if ($a['live']) return strcmp($a['cm_end'], $b['cm_end']);
        return (int)$b['cm_id'] - (int)$a['cm_id'];
    });
    return $rows;
}

// 주문서에 올릴 목록 — 쓸 수 있는 장만, 이 주문에서 얼마가 깎이는지까지 미리 계산해 붙인다.
// 못 쓰는 장도 이유와 함께 남긴다(안 보이면 "내 쿠폰이 사라졌다" 가 된다).
function cart_coupon_choices($mb_id, $lines, $item_total)
{
    $out = array();
    foreach (cart_coupon_mine($mb_id, false) as $cm) {
        $why = cart_coupon_why_not($cm, $lines, $item_total);
        $out[] = array(
            'cm_id' => (int)$cm['cm_id'],
            'name' => $cm['cp_name'],
            'label' => $cm['label'],
            'target_label' => $cm['target_label'],
            'cm_end' => $cm['cm_end'],
            'left_days' => $cm['left_days'],
            'amount' => $why === '' ? cart_coupon_discount($cm, $lines) : 0,
            'why_not' => $why,
        );
    }
    // 많이 깎이는 것부터 — 고르는 수고를 덜어 준다
    usort($out, function ($a, $b) {
        if (($a['why_not'] === '') !== ($b['why_not'] === '')) return $a['why_not'] === '' ? -1 : 1;
        return $b['amount'] - $a['amount'];
    });
    return $out;
}

// 주문 확정용 — 이 회원이 이 장을 이 주문에 쓸 수 있는지 다시 보고 금액을 굳힌다.
// 화면 값을 믿지 않는다. array('cm_id', 'amount') 또는 실패 사유 문자열.
function cart_coupon_pick($mb_id, $cm_id, $lines, $item_total)
{
    global $g5;
    $cm_id = (int)$cm_id;
    if ($cm_id < 1) return array('cm_id' => 0, 'amount' => 0);
    if (trim($mb_id) === '') return '쿠폰은 회원만 사용할 수 있습니다.';

    $cm = sql_fetch(" select c.*, m.* from `{$g5['ycart_coupon_mb_table']}` m
        inner join `{$g5['ycart_coupon_table']}` c on c.cp_id = m.cp_id
        where m.cm_id = '$cm_id' and m.mb_id = '".sql_real_escape_string($mb_id)."' ");
    if (!$cm) return '쿠폰을 찾을 수 없습니다.';

    $why = cart_coupon_why_not($cm, $lines, $item_total);
    if ($why !== '') return '쿠폰을 사용할 수 없습니다 — '.$why.'.';

    return array('cm_id' => $cm_id, 'amount' => cart_coupon_discount($cm, $lines));
}

// 소진 — 아직 안 쓴 장일 때만 이 주문의 것으로 잠근다. 바뀐 행이 없으면 그 사이 다른 주문이
// 먼저 썼다는 뜻이라 false 를 준다(부르는 쪽이 재고 부족과 같은 무게로 다룬다).
// PG 초안은 여러 개가 동시에 떠 있을 수 있어, 이 원자 갱신이 유일한 진짜 방어선이다.
function cart_coupon_consume($cm_id, $od_id, $amount)
{
    global $g5;
    $cm_id = (int)$cm_id;
    if ($cm_id < 1) return true;
    sql_query(" update `{$g5['ycart_coupon_mb_table']}`
        set cm_od_id = '".(int)$od_id."', cm_amount = '".(int)$amount."',
            cm_used_at = '".G5_TIME_YMDHIS."'
        where cm_id = '$cm_id' and cm_od_id = 0 ", true);
    if (get_sql_affected_rows() > 0) return true;

    // 이미 이 주문의 것이면 성공으로 본다 — 같은 주문이 두 번 확정되는 경로(결제 리턴 중복,
    // 무통장으로 만들어진 주문이 뒤늦게 PG 로 확정되는 경우)에서 제 쿠폰을 "남이 썼다" 로
    // 오판해 방금 성공한 승인을 망취소하면 안 된다.
    $row = sql_fetch(" select cm_od_id from `{$g5['ycart_coupon_mb_table']}` where cm_id = '$cm_id' ");
    return $row && (int)$row['cm_od_id'] === (int)$od_id;
}

// 되살림 — 주문 취소·전체 반품 때. 기한이 이미 지났으면 되살려도 못 쓰지만 그대로 돌려준다:
// 기한을 늘려 주는 것은 쿠폰 정책을 바꾸는 일이라 사람이 정할 몫이다.
function cart_coupon_release($od_id)
{
    global $g5;
    $od_id = (int)$od_id;
    if ($od_id < 1) return;
    sql_query(" update `{$g5['ycart_coupon_mb_table']}`
        set cm_od_id = 0, cm_amount = 0, cm_used_at = '1970-01-01 00:00:00'
        where cm_od_id = '$od_id' ", true);
}

// ---------- 관리자 ----------

// 코드 중복 검사 — 빈 코드는 여러 장이 가질 수 있다(자동 지급 전용 쿠폰)
function cart_coupon_code_error($code, $except_cp_id = 0)
{
    global $g5;
    $code = strtoupper(trim($code));
    if ($code === '') return '';
    if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $code)) {
        return '쿠폰 코드는 영문 대문자·숫자·- _ 로 3~30자입니다.';
    }
    $dup = sql_fetch(" select cp_id from `{$g5['ycart_coupon_table']}`
        where cp_code = '".sql_real_escape_string($code)."'
          and cp_id <> '".(int)$except_cp_id."' ");
    return $dup ? '이미 쓰고 있는 쿠폰 코드입니다.' : '';
}

// 관리자 일괄 지급 — 회원 아이디 목록에 한 장씩. 이미 가진 사람은 조용히 건너뛴다.
// 돌려주는 값: array('issued' => 발급 수, 'skipped' => 건너뛴 수, 'unknown' => 없는 아이디들)
function cart_coupon_grant_many($cp_id, $mb_ids)
{
    global $g5;
    $cp = cart_coupon_get($cp_id);
    $out = array('issued' => 0, 'skipped' => 0, 'unknown' => array());
    if (!$cp) return $out;

    foreach ($mb_ids as $raw) {
        $mb_id = trim($raw);
        if ($mb_id === '') continue;
        $mb = sql_fetch(" select mb_id from `{$g5['member_table']}`
            where mb_id = '".sql_real_escape_string($mb_id)."' ");
        if (!$mb) { $out['unknown'][] = $mb_id; continue; }
        if (cart_coupon_issue($cp, $mb['mb_id'])) $out['issued']++;
        else $out['skipped']++;
    }
    return $out;
}

// 관리자 목록에 곁들일 발급·사용 집계
function cart_coupon_stats($cp_id)
{
    global $g5;
    $row = sql_fetch(" select count(*) as issued, sum(cm_od_id > 0) as used,
                              sum(cm_amount) as amount
        from `{$g5['ycart_coupon_mb_table']}` where cp_id = '".(int)$cp_id."' ");
    return array(
        'issued' => (int)$row['issued'],
        'used' => (int)$row['used'],
        'amount' => (int)$row['amount'],
    );
}

function cart_coupon_save($data, $cp_id = 0)
{
    global $g5;
    $cp_id = (int)$cp_id;
    $cap = function ($v, $len) { return mb_substr(trim((string)$v), 0, $len, 'utf-8'); };

    $set = " cp_name = '".sql_real_escape_string($cap($data['cp_name'], 100))."',
             cp_code = '".sql_real_escape_string(strtoupper($cap($data['cp_code'], 30)))."',
             cp_issue = '".sql_real_escape_string($data['cp_issue'])."',
             cp_type = '".sql_real_escape_string($data['cp_type'])."',
             cp_value = '".(int)$data['cp_value']."',
             cp_max = '".(int)$data['cp_max']."',
             cp_min = '".(int)$data['cp_min']."',
             cp_target = '".sql_real_escape_string($cap($data['cp_target'], 40))."',
             cp_begin = '".sql_real_escape_string($data['cp_begin'])."',
             cp_end = '".sql_real_escape_string($data['cp_end'])."',
             cp_days = '".(int)$data['cp_days']."',
             cp_use = '".((int)$data['cp_use'] ? 1 : 0)."',
             cp_memo = '".sql_real_escape_string($cap($data['cp_memo'], 255))."' ";

    if ($cp_id > 0) {
        sql_query(" update `{$g5['ycart_coupon_table']}` set $set where cp_id = '$cp_id' ", true);
        return $cp_id;
    }
    sql_query(" insert into `{$g5['ycart_coupon_table']}`
        set $set, cp_datetime = '".G5_TIME_YMDHIS."' ", true);
    return (int)sql_insert_id();
}

// 삭제 — 이미 나간 장이 있으면 지우지 않는다. 정의가 사라지면 회원 쿠폰함의 그 장이
// 이름도 조건도 없는 유령이 되고, 지난 주문의 할인 근거도 함께 사라진다.
// 더 안 쓰려면 '사용 안 함'(cp_use=0)으로 둔다.
function cart_coupon_delete($cp_id)
{
    global $g5;
    $cp_id = (int)$cp_id;
    if ($cp_id < 1) return '없는 쿠폰입니다.';
    $issued = sql_fetch(" select count(*) as cnt from `{$g5['ycart_coupon_mb_table']}`
        where cp_id = '$cp_id' ");
    if ((int)$issued['cnt'] > 0) {
        return '이미 '.number_format((int)$issued['cnt']).'장이 발급된 쿠폰이라 지울 수 없습니다. "사용 안 함" 으로 바꿔 주세요.';
    }
    sql_query(" delete from `{$g5['ycart_coupon_table']}` where cp_id = '$cp_id' ", true);
    return '';
}
