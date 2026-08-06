<?php
if (!defined('_GNUBOARD_')) exit;

// 재고의 유일한 쓰기 경로. 감소는 조건부 원자 UPDATE 라 동시 주문 경합에 안전하다.
function cart_stock_move($sk_id, $diff, $reason, $ref = '', $who = '')
{
    global $g5;
    $sk_id = (int)$sk_id;
    $diff = (int)$diff;
    if ($diff === 0) return true;

    if ($diff < 0) {
        $need = -$diff;
        sql_query(" update `{$g5['ycart_sku_table']}`
            set sk_qty = sk_qty - '$need'
            where sk_id = '$sk_id' and sk_qty >= '$need' ", true);
        if (!get_sql_affected_rows()) return false;   // 재고 부족 — 아무것도 안 바뀜
    } else {
        sql_query(" update `{$g5['ycart_sku_table']}`
            set sk_qty = sk_qty + '$diff' where sk_id = '$sk_id' ", true);
        if (!get_sql_affected_rows()) return false;   // 없는 SKU
    }

    $row = sql_fetch(" select it_id, sk_qty from `{$g5['ycart_sku_table']}` where sk_id = '$sk_id' ");
    sql_query(" insert into `{$g5['ycart_stock_log_table']}`
        (sk_id, it_id, sl_diff, sl_after, sl_reason, sl_ref, sl_who, sl_datetime)
        values ('$sk_id', '".(int)$row['it_id']."', '$diff', '".(int)$row['sk_qty']."',
                '".sql_real_escape_string($reason)."', '".sql_real_escape_string($ref)."',
                '".sql_real_escape_string($who)."', '".G5_TIME_YMDHIS."') ", true);
    cart_item_cache_refresh((int)$row['it_id']);
    return true;
}

// 절대값 설정(관리자 입력·CSV) — 차이를 계산해 move 로 위임해 로그 규칙을 하나로 유지
function cart_stock_set($sk_id, $qty, $reason, $ref = '', $who = '')
{
    $row = cart_sku_get($sk_id);
    if (!$row) return false;
    $diff = (int)$qty - (int)$row['sk_qty'];
    if ($diff === 0) return true;
    if ($diff < 0 && (int)$row['sk_qty'] < -$diff) return false;
    return cart_stock_move($sk_id, $diff, $reason, $ref, $who);
}
