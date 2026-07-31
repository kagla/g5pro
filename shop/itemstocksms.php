<?php
include_once('./_common.php');
define('G5_BLADE_PAGE', true); // g5blade 직통 화면 (재입고 알림 팝업)

$it_id = isset($_REQUEST['it_id']) ? safe_replace_regex($_REQUEST['it_id'], 'it_id') : '';

$g5['title'] = '상품 재입고 알림 (SMS)';
include_once(G5_PATH.'/head.sub.php');

// 상품정보
$it = get_shop_item($it_id, true);

if(! (isset($it['it_id']) && $it['it_id']))
    alert_close('상품정보가 존재하지 않습니다.');

if(!$it['it_soldout'] || !$it['it_stock_sms'])
    alert_close('재입고SMS 알림을 신청할 수 없는 상품입니다.');

g5_map_shop_itemstocksms($it, $member['mb_hp'], get_text($config['cf_privacy'])); // g5blade

include_once(G5_PATH.'/tail.sub.php');
