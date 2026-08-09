<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 찜(관심 상품) ----------
// 소유자는 회원(mb_id) 하나뿐이다. 장바구니는 비회원 세션(ct_sid)도 받지만 찜은 안 받는다 —
// 찜의 값어치는 "다음에 다시 와서 본다" 에 있는데, 세션 바구니는 쿠키가 끊기면 사라지므로
// 비회원 찜 목록은 만들어 두고 잃어버리게 하는 셈이 된다. 대신 로그인으로 안내한다.
//
// 판매 중지·품절은 찜을 지우지 않는다 — 장바구니(cart_cart_rows)와 같은 원칙으로
// 목록에 avail=false 로 남긴다. 재입고를 기다리는 것이 찜의 흔한 쓰임이다.

// 지금 요청의 찜 소유자. 비회원이면 빈 문자열 — 호출한 쪽이 로그인 안내를 맡는다.
function cart_wish_mb_id()
{
    global $member;
    return isset($member['mb_id']) ? trim($member['mb_id']) : '';
}

// 찜했나 — 상세 화면이 하트를 채운 모양으로 그릴지 정하는 값
function cart_wish_has($it_id, $mb_id)
{
    global $g5;
    $mb_id = trim((string)$mb_id);
    if ($mb_id === '' || (int)$it_id < 1) return false;
    $row = sql_fetch(" select wi_id from `{$g5['ycart_wish_table']}`
        where mb_id = '".sql_real_escape_string($mb_id)."' and it_id = '".(int)$it_id."' ");
    return (bool)$row;
}

// 이 상품을 찜한 사람 수 — 하트 옆 숫자(다른 사람도 담아 두었다는 신호)
function cart_wish_count($it_id)
{
    global $g5;
    if ((int)$it_id < 1) return 0;
    $row = sql_fetch(" select count(*) as cnt from `{$g5['ycart_wish_table']}`
        where it_id = '".(int)$it_id."' ");
    return (int)$row['cnt'];
}

// 담기 — 빈 문자열 반환이 성공(cart_cart_add 관례). 이미 있으면 조용히 성공으로 둔다.
function cart_wish_add($it_id, $mb_id)
{
    global $g5;
    $it_id = (int)$it_id;
    $mb_id = trim((string)$mb_id);
    if ($mb_id === '') return '로그인이 필요합니다.';

    $item = cart_item_get($it_id);
    if (!$item || !(int)$item['it_show']) return '판매하지 않는 상품입니다.';
    // 전 연결 분류가 숨김인 상품은 프론트 어디서도 다루지 않는다(it_id 직접 지정 우회 차단)
    if (cart_item_is_hidden($it_id)) return '판매하지 않는 상품입니다.';

    sql_query(" insert into `{$g5['ycart_wish_table']}` (mb_id, it_id, wi_datetime)
        values ('".sql_real_escape_string($mb_id)."', '$it_id', '".G5_TIME_YMDHIS."')
        on duplicate key update wi_id = wi_id ", true);
    return '';
}

// 빼기 — mb_id 를 조건에 함께 넣어 남의 it_id 를 보내도 아무 행에 닿지 않는다
function cart_wish_remove($it_id, $mb_id)
{
    global $g5;
    $mb_id = trim((string)$mb_id);
    if ($mb_id === '' || (int)$it_id < 1) return;
    sql_query(" delete from `{$g5['ycart_wish_table']}`
        where mb_id = '".sql_real_escape_string($mb_id)."' and it_id = '".(int)$it_id."' ", true);
}

// 하트 한 번 = 토글. 화면이 다시 읽지 않고 그대로 그릴 수 있게 결과 상태를 함께 돌려준다.
// array('error' => 사유, 'on' => 지금 찜 상태, 'count' => 이 상품 찜 수)
function cart_wish_toggle($it_id, $mb_id)
{
    $it_id = (int)$it_id;
    if (cart_wish_has($it_id, $mb_id)) {
        cart_wish_remove($it_id, $mb_id);
        return array('error' => '', 'on' => false, 'count' => cart_wish_count($it_id));
    }
    $err = cart_wish_add($it_id, $mb_id);
    if ($err !== '') return array('error' => $err, 'on' => false, 'count' => cart_wish_count($it_id));
    return array('error' => '', 'on' => true, 'count' => cart_wish_count($it_id));
}

function cart_wish_total($mb_id)
{
    global $g5;
    $mb_id = trim((string)$mb_id);
    if ($mb_id === '') return 0;
    $row = sql_fetch(" select count(*) as cnt from `{$g5['ycart_wish_table']}`
        where mb_id = '".sql_real_escape_string($mb_id)."' ");
    return (int)$row['cnt'];
}

// 찜한 일시를 사람 말로 — 카드에서 실제로 궁금한 것은 "언제였나" 가 아니라 "얼마나 묵었나" 다.
// 최근 일주일은 상대 시간이 그 물음에 바로 답하고(오늘·어제·3일 전), 그보다 오래된 것은
// "24일 전" 이 되어 되레 세어 봐야 하므로 날짜로 돌아간다. 정확한 시각은 title 로 따로 준다.
//
// 날짜 경계로 센다 — 어제 23시와 오늘 1시는 두 시간 차이지만 사람에게는 "어제" 다.
// 최근 것에만 시:분을 붙인다: 오늘 담은 것들 사이의 순서는 시각이 있어야 갈린다.
function cart_wish_date_label($datetime)
{
    $ts = strtotime($datetime);
    if (!$ts) return '';

    $days = (int)floor((strtotime(date('Y-m-d', G5_SERVER_TIME)) - strtotime(date('Y-m-d', $ts))) / 86400);
    if ($days <= 0) return '오늘 '.date('H:i', $ts);
    if ($days === 1) return '어제 '.date('H:i', $ts);
    if ($days < 7) return $days.'일 전';
    return date('y.m.d', $ts);
}

// 찜 목록 — 최근 담은 것부터. 목록 카드(cart/list.blade)와 같은 모양으로 그릴 수 있게
// 대표 이미지·주소를 붙여 내보낸다. 대표 이미지는 행마다 캐지 않고 한 방에 받는다(N+1 방지).
function cart_wish_rows($mb_id, $offset = 0, $limit = 24)
{
    global $g5;
    $mb_id = trim((string)$mb_id);
    if ($mb_id === '') return array();

    $rows = array();
    $result = sql_query(" select w.wi_id, w.wi_datetime,
            i.it_id, i.it_code, i.it_name, i.it_price, i.it_stock, i.it_show
        from `{$g5['ycart_wish_table']}` w
        inner join `{$g5['ycart_item_table']}` i on i.it_id = w.it_id
        where w.mb_id = '".sql_real_escape_string($mb_id)."'
        order by w.wi_id desc limit ".(int)$offset.", ".(int)$limit);
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    if (!$rows) return array();

    $main_images = cart_item_main_images(array_column($rows, 'it_id'));
    foreach ($rows as $i => $r) {
        $it_id = (int)$r['it_id'];
        // 팔지 않게 된 상품도 목록에 남긴다 — 지우는 대신 왜 못 사는지 알려 준다
        $rows[$i]['avail'] = ((int)$r['it_show'] === 1 && !cart_item_is_hidden($it_id));
        $rows[$i]['soldout'] = ((int)$r['it_stock'] === 0);
        $rows[$i]['img'] = isset($main_images[$it_id])
            ? cart_item_thumb_url($main_images[$it_id], 400, 400, true) : '';
        $rows[$i]['href'] = cart_url('item.php', array('code' => $r['it_code']));
        $rows[$i]['wi_date'] = cart_wish_date_label($r['wi_datetime']);
        $rows[$i]['wi_full'] = date('Y-m-d H:i', strtotime($r['wi_datetime']));
    }
    return $rows;
}
