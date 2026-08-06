<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 장바구니 ----------
// 소유자는 회원(mb_id) 또는 비회원 세션키(bk_sid) 둘 중 하나. 가격은 담지 않는다 —
// 표시는 현재가, 확정은 체크아웃 서버 재계산(스펙 원칙).

// 현재 요청의 바구니 소유자. 회원 로그인 상태면 세션 바구니를 회원 것으로 이관(claim)한다.
// $override 는 CLI 테스트용 — array('mb_id' => '', 'sid' => '') 꼴.
function cart_basket_owner($override = null)
{
    global $member;
    if (is_array($override)) return $override;

    $mb_id = isset($member['mb_id']) ? trim($member['mb_id']) : '';
    if ($mb_id !== '') {
        if (!empty($_SESSION['ss_cart_sid'])) {
            cart_basket_claim($mb_id, $_SESSION['ss_cart_sid']);
            unset($_SESSION['ss_cart_sid']);
        }
        return array('mb_id' => $mb_id, 'sid' => '');
    }
    if (empty($_SESSION['ss_cart_sid'])) {
        $_SESSION['ss_cart_sid'] = md5(uniqid((string)mt_rand(), true));
    }
    return array('mb_id' => '', 'sid' => $_SESSION['ss_cart_sid']);
}

function cart_basket_where($owner)
{
    return " mb_id = '".sql_real_escape_string($owner['mb_id'])."'
        and bk_sid = '".sql_real_escape_string($owner['sid'])."' ";
}

// 담기 — 같은 SKU 는 수량 합산(UNIQUE owner_sku + ON DUPLICATE KEY). 빈 문자열 반환=성공.
function cart_basket_add($sk_id, $qty, $owner = null)
{
    global $g5;
    $owner = cart_basket_owner($owner);
    $sk_id = (int)$sk_id;
    $qty = max(1, (int)$qty);

    $sku = cart_sku_get($sk_id);
    if (!$sku || !(int)$sku['sk_use']) return '판매하지 않는 옵션입니다.';
    $item = cart_item_get((int)$sku['it_id']);
    if (!$item || !(int)$item['it_show']) return '판매하지 않는 상품입니다.';
    // 숨긴 분류 서브트리는 프론트 어디서도 안 판다(1단계 확정 의미론) — sk_id 직접 지정 우회 차단
    if (in_array((int)$item['ca_id'], cart_hidden_category_ids(), true)) return '판매하지 않는 상품입니다.';
    if ((int)$sku['sk_qty'] <= 0) return '품절된 옵션입니다.';

    sql_query(" insert into `{$g5['cart_basket_table']}`
        (mb_id, bk_sid, sk_id, bk_qty, bk_datetime)
        values ('".sql_real_escape_string($owner['mb_id'])."',
                '".sql_real_escape_string($owner['sid'])."',
                '$sk_id', '$qty', '".G5_TIME_YMDHIS."')
        on duplicate key update bk_qty = bk_qty + '$qty', bk_datetime = '".G5_TIME_YMDHIS."' ", true);
    return '';
}

// 바구니 행 + 상품·SKU 현재 정보. 판매 중지/품절은 지우지 않고 avail=false 로 표시만 한다 —
// 손님이 "왜 사라졌지" 하지 않게 화면이 사유를 보여 주고, 체크아웃이 최종 거른다.
function cart_basket_items($owner = null)
{
    global $g5;
    $owner = cart_basket_owner($owner);
    $rows = array();
    $hidden = cart_hidden_category_ids();
    $result = sql_query(" select b.bk_id, b.sk_id, b.bk_qty,
            s.sk_option, s.sk_price, s.sk_qty, s.sk_use,
            i.it_id, i.it_name, i.it_show, i.ca_id
        from `{$g5['cart_basket_table']}` b
        inner join `{$g5['cart_sku_table']}` s on s.sk_id = b.sk_id
        inner join `{$g5['cart_item_table']}` i on i.it_id = s.it_id
        where ".cart_basket_where($owner)."
        order by b.bk_id desc ");
    while ($r = sql_fetch_array($result)) {
        $opt = json_decode($r['sk_option'], true);
        $r['opt_label'] = (is_array($opt) && count($opt)) ? implode(' / ', array_values($opt)) : '';
        $r['avail'] = ((int)$r['sk_use'] && (int)$r['it_show'] && (int)$r['sk_qty'] > 0
            && !in_array((int)$r['ca_id'], $hidden, true));
        $r['over_stock'] = ($r['avail'] && (int)$r['bk_qty'] > (int)$r['sk_qty']);
        $rows[] = $r;
    }
    return $rows;
}

function cart_basket_set_qty($bk_id, $qty, $owner = null)
{
    global $g5;
    $owner = cart_basket_owner($owner);
    $bk_id = (int)$bk_id;
    $qty = (int)$qty;
    if ($qty <= 0) return cart_basket_remove($bk_id, $owner);
    sql_query(" update `{$g5['cart_basket_table']}`
        set bk_qty = '$qty', bk_datetime = '".G5_TIME_YMDHIS."'
        where bk_id = '$bk_id' and ".cart_basket_where($owner), true);
    return '';
}

function cart_basket_remove($bk_id, $owner = null)
{
    global $g5;
    $owner = cart_basket_owner($owner);
    sql_query(" delete from `{$g5['cart_basket_table']}`
        where bk_id = '".(int)$bk_id."' and ".cart_basket_where($owner), true);
    return '';
}

function cart_basket_clear($owner = null)
{
    global $g5;
    $owner = cart_basket_owner($owner);
    sql_query(" delete from `{$g5['cart_basket_table']}` where ".cart_basket_where($owner), true);
}

function cart_basket_count($owner = null)
{
    global $g5;
    $owner = cart_basket_owner($owner);
    $row = sql_fetch(" select count(*) as cnt from `{$g5['cart_basket_table']}`
        where ".cart_basket_where($owner));
    return (int)$row['cnt'];
}

// 로그인 시 비회원 바구니를 회원 바구니로 이관. 같은 SKU 가 양쪽에 있으면 수량을 합친다.
function cart_basket_claim($mb_id, $sid)
{
    global $g5;
    $mb_id_esc = sql_real_escape_string($mb_id);
    $sid_esc = sql_real_escape_string($sid);
    if ($mb_id_esc === '' || $sid_esc === '') return;

    $result = sql_query(" select bk_id, sk_id, bk_qty from `{$g5['cart_basket_table']}`
        where mb_id = '' and bk_sid = '$sid_esc' ");
    while ($r = sql_fetch_array($result)) {
        sql_query(" insert into `{$g5['cart_basket_table']}`
            (mb_id, bk_sid, sk_id, bk_qty, bk_datetime)
            values ('$mb_id_esc', '', '".(int)$r['sk_id']."', '".(int)$r['bk_qty']."', '".G5_TIME_YMDHIS."')
            on duplicate key update bk_qty = bk_qty + '".(int)$r['bk_qty']."' ", true);
        sql_query(" delete from `{$g5['cart_basket_table']}` where bk_id = '".(int)$r['bk_id']."' ", true);
    }
}
