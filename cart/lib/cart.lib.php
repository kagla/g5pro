<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 장바구니 ----------
// 소유자는 회원(mb_id) 또는 비회원 세션키(ct_sid) 둘 중 하나. 가격은 담지 않는다 —
// 표시는 현재가, 확정은 체크아웃 서버 재계산(스펙 원칙).

// 현재 요청의 바구니 소유자. 회원 로그인 상태면 세션 바구니를 회원 것으로 이관(claim)한다.
// $override 는 CLI 테스트용 — array('mb_id' => '', 'sid' => '') 꼴.
function cart_cart_owner($override = null)
{
    global $member;
    if (is_array($override)) return $override;

    $mb_id = isset($member['mb_id']) ? trim($member['mb_id']) : '';
    if ($mb_id !== '') {
        if (!empty($_SESSION['ss_cart_sid'])) {
            cart_cart_claim($mb_id, $_SESSION['ss_cart_sid']);
            unset($_SESSION['ss_cart_sid']);
        }
        return array('mb_id' => $mb_id, 'sid' => '');
    }
    if (empty($_SESSION['ss_cart_sid'])) {
        $_SESSION['ss_cart_sid'] = md5(uniqid((string)mt_rand(), true));
    }
    return array('mb_id' => '', 'sid' => $_SESSION['ss_cart_sid']);
}

function cart_cart_where($owner)
{
    return " mb_id = '".sql_real_escape_string($owner['mb_id'])."'
        and ct_sid = '".sql_real_escape_string($owner['sid'])."' ";
}

// 담기 — 같은 SKU 는 수량 합산(UNIQUE owner_sku + ON DUPLICATE KEY). 빈 문자열 반환=성공.
function cart_cart_add($sk_id, $qty, $owner = null)
{
    global $g5;
    $owner = cart_cart_owner($owner);
    $sk_id = (int)$sk_id;
    $qty = max(1, (int)$qty);

    $sku = cart_sku_get($sk_id);
    if (!$sku || !(int)$sku['sk_use']) return '판매하지 않는 옵션입니다.';
    $item = cart_item_get((int)$sku['it_id']);
    if (!$item || !(int)$item['it_show']) return '판매하지 않는 상품입니다.';
    // 전 연결 분류가 숨김인 상품은 프론트 어디서도 안 판다 — sk_id 직접 지정 우회 차단
    if (cart_item_is_hidden((int)$item['it_id'])) return '판매하지 않는 상품입니다.';
    if ((int)$sku['sk_qty'] <= 0) return '품절된 옵션입니다.';

    sql_query(" insert into `{$g5['ycart_cart_table']}`
        (mb_id, ct_sid, sk_id, ct_qty, ct_datetime)
        values ('".sql_real_escape_string($owner['mb_id'])."',
                '".sql_real_escape_string($owner['sid'])."',
                '$sk_id', '$qty', '".G5_TIME_YMDHIS."')
        on duplicate key update ct_qty = ct_qty + '$qty', ct_datetime = '".G5_TIME_YMDHIS."' ", true);
    return '';
}

// 바구니 행 + 상품·SKU 현재 정보. 판매 중지/품절은 지우지 않고 avail=false 로 표시만 한다 —
// 손님이 "왜 사라졌지" 하지 않게 화면이 사유를 보여 주고, 체크아웃이 최종 거른다.
//
// 정렬: 최근에 담은 묶음이 위로, 한 묶음 안에서는 고른 순서 그대로.
// 상세 화면에서 옵션 여러 줄을 한 번에 담으면 같은 시각으로 들어오므로, 시각만 보면
// 묶음 위치가 정해지고 ct_id 오름차순이 그 안의 순서를 화면과 맞춘다
// (ct_id 만으로 내림차순 하면 한 번에 담은 줄이 거꾸로 보인다).
function cart_cart_items($owner = null)
{
    global $g5;
    $owner = cart_cart_owner($owner);
    $rows = array();
    $result = sql_query(" select b.ct_id, b.sk_id, b.ct_qty,
            s.sk_option, s.sk_price, s.sk_qty, s.sk_use,
            i.it_id, i.it_code, i.it_name, i.it_show
        from `{$g5['ycart_cart_table']}` b
        inner join `{$g5['ycart_sku_table']}` s on s.sk_id = b.sk_id
        inner join `{$g5['ycart_item_table']}` i on i.it_id = s.it_id
        where ".cart_cart_where($owner)."
        order by b.ct_datetime desc, b.ct_id asc ");
    while ($r = sql_fetch_array($result)) {
        $opt = json_decode($r['sk_option'], true);
        $r['opt_label'] = (is_array($opt) && count($opt)) ? implode(' / ', array_values($opt)) : '';
        // 바구니 행은 소수라 행마다 N:M 숨김 판정을 해도 무해하다
        $r['avail'] = ((int)$r['sk_use'] && (int)$r['it_show'] && (int)$r['sk_qty'] > 0
            && !cart_item_is_hidden((int)$r['it_id']));
        $r['over_stock'] = ($r['avail'] && (int)$r['ct_qty'] > (int)$r['sk_qty']);
        $rows[] = $r;
    }
    return $rows;
}

// 수량 바꾸기. **재고보다 많이는 담기지 않는다** — 넘긴 채로 두었다가 주문 단계에서 막으면
// 손님은 담을 때가 아니라 결제하려 할 때 문제를 만난다. 넘겨 달라고 하면 재고까지만 담고
// 얼마로 잘렸는지 돌려준다(화면이 그 자리에서 이유를 알린다).
// 반환: array('qty' => 실제 담긴 수량, 'clamped' => 잘렸나, 'max' => 재고)
function cart_cart_set_qty($ct_id, $qty, $owner = null)
{
    global $g5;
    $owner = cart_cart_owner($owner);
    $ct_id = (int)$ct_id;
    $qty = (int)$qty;
    if ($qty <= 0) { cart_cart_remove($ct_id, $owner); return array('qty' => 0, 'clamped' => false, 'max' => 0); }

    // 이 줄이 가리키는 SKU 의 지금 재고. 담은 뒤 관리자가 재고를 줄였을 수도 있어 그때그때 읽는다.
    $row = sql_fetch(" select sk_id from `{$g5['ycart_cart_table']}`
        where ct_id = '$ct_id' and ".cart_cart_where($owner));
    $max = 0;
    if ($row) {
        $sku = cart_sku_get((int)$row['sk_id']);
        $max = $sku ? (int)$sku['sk_qty'] : 0;
    }
    $clamped = false;
    if ($max > 0 && $qty > $max) { $qty = $max; $clamped = true; }
    // ct_datetime 은 건드리지 않는다 — 그 값이 목록 정렬 기준이라, 수량만 고쳤는데도 그 줄이
    // 맨 위로 튀어 오른다. 여러 줄을 차례로 고치면 순서가 계속 뒤집혀 어디를 고쳤는지 잃는다.
    // ct_datetime 은 "담은 시각" 이고, 수량 변경은 담는 행위가 아니다.
    sql_query(" update `{$g5['ycart_cart_table']}`
        set ct_qty = '$qty'
        where ct_id = '$ct_id' and ".cart_cart_where($owner), true);
    return array('qty' => $qty, 'clamped' => $clamped, 'max' => $max);
}

function cart_cart_remove($ct_id, $owner = null)
{
    global $g5;
    $owner = cart_cart_owner($owner);
    sql_query(" delete from `{$g5['ycart_cart_table']}`
        where ct_id = '".(int)$ct_id."' and ".cart_cart_where($owner), true);
    return '';
}

function cart_cart_clear($owner = null)
{
    global $g5;
    $owner = cart_cart_owner($owner);
    sql_query(" delete from `{$g5['ycart_cart_table']}` where ".cart_cart_where($owner), true);
}

function cart_cart_count($owner = null)
{
    global $g5;
    $owner = cart_cart_owner($owner);
    $row = sql_fetch(" select count(*) as cnt from `{$g5['ycart_cart_table']}`
        where ".cart_cart_where($owner));
    return (int)$row['cnt'];
}

// 로그인 시 비회원 바구니를 회원 바구니로 이관. 같은 SKU 가 양쪽에 있으면 수량을 합친다.
function cart_cart_claim($mb_id, $sid)
{
    global $g5;
    $mb_id_esc = sql_real_escape_string($mb_id);
    $sid_esc = sql_real_escape_string($sid);
    if ($mb_id_esc === '' || $sid_esc === '') return;

    $result = sql_query(" select ct_id, sk_id, ct_qty from `{$g5['ycart_cart_table']}`
        where mb_id = '' and ct_sid = '$sid_esc' ");
    while ($r = sql_fetch_array($result)) {
        sql_query(" insert into `{$g5['ycart_cart_table']}`
            (mb_id, ct_sid, sk_id, ct_qty, ct_datetime)
            values ('$mb_id_esc', '', '".(int)$r['sk_id']."', '".(int)$r['ct_qty']."', '".G5_TIME_YMDHIS."')
            on duplicate key update ct_qty = ct_qty + '".(int)$r['ct_qty']."' ", true);
        sql_query(" delete from `{$g5['ycart_cart_table']}` where ct_id = '".(int)$r['ct_id']."' ", true);
    }
}
