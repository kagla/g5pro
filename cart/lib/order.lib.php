<?php
if (!defined('_GNUBOARD_')) exit;

// ---------- 배송비 ----------
// 몰 전역 단일 정책(설정 화면): 기본 배송비 + 조건부 무료(기준액 0 이면 없음) + 제주 추가비.
// 조건부 무료를 충족해도 제주 추가비는 남는다 — 실제 택배 원가가 남는 구간이라 몰 관례를 따른다.
function cart_shipping_fee($item_total, $zip = '')
{
    $cc = cart_config();
    $fee = (int)$cc['cc_ship_base'];
    if ((int)$cc['cc_ship_free'] > 0 && (int)$item_total >= (int)$cc['cc_ship_free']) {
        $fee = 0;
    }
    if (cart_zip_is_jeju($zip)) {
        $fee += (int)$cc['cc_ship_jeju'];
    }
    return $fee;
}

// 제주 판정 — 새 우편번호(5자리)는 제주 전역이 63000~63644, 프리픽스 '63' 으로 충분
function cart_zip_is_jeju($zip)
{
    $zip = preg_replace('/[^0-9]/', '', (string)$zip);
    return strlen($zip) === 5 && substr($zip, 0, 2) === '63';
}
