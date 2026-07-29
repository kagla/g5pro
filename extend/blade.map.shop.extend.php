<?php
/**
 * g5blade 화면 매핑 — 쇼핑몰(shop).
 * 서비스 단위로 매핑 파일을 나눈다: 기본(bbs·회원)은 blade.map.extend.php,
 * 쇼핑몰은 이 파일. 새 서비스가 생기면 blade.map.<서비스>.extend.php 로 추가한다.
 * 규칙은 동일 — 한 화면 = 한 함수, 전역변수를 뷰용 배열로 정리해 g5_view() 호출.
 */
if (!defined('_GNUBOARD_')) exit;

// item_list 에서 상품 배열만 뽑는다 (출력 없음) — extend/blade.shop_items.php 참고
function g5_shop_items($il)
{
    $GLOBALS['g5_blade_items'] = array();
    $il->set_list_skin(G5_PATH.'/extend/blade.shop_items.php');
    $il->run();   // 반환 HTML 은 버린다
    return $GLOBALS['g5_blade_items'];
}

// 상품 한 건을 뷰용으로 정리
function g5_shop_item_row($row)
{
    $it_id = $row['it_id'];
    $price = (int)$row['it_price'];
    $cust  = (int)$row['it_cust_price'];

    return array(
        'it_id'      => $it_id,
        'name'       => get_text($row['it_name']),
        'href'       => G5_SHOP_URL.'/item.php?it_id='.$it_id,
        'img'        => get_it_imageurl($it_id),           // 대표 이미지 URL
        'basic'      => isset($row['it_basic']) ? $row['it_basic'] : '',   // 순정 conv_content 완료 → {!! !!}
        'price'      => $price,
        'cust_price' => $cust,
        'discount'   => ($cust > 0 && $price > 0 && $cust > $price) ? (int)round((1 - $price / $cust) * 100) : 0,
        'is_soldout' => (isset($row['it_soldout']) && $row['it_soldout']) ? true : false,
        'use_cart'   => !isset($row['it_use']) || $row['it_use'] == 1,
    );
}

function g5_shop_item_rows($rows)
{
    $out = array();
    foreach ((array)$rows as $r) $out[] = g5_shop_item_row($r);
    return $out;
}

// ── 주문 상세 조회 (shop/orderinquiryview.php)
// 취소·환불 폼과 영수증 팝업 JS 가 얽혀 있어 순정 출력을 그대로 담고 CSS 로만 다듬는다.
function g5_map_shop_orderview($body_html)
{
    global $od, $is_admin;

    $items = g5_shop_order_items(isset($od['od_id']) ? $od['od_id'] : '');
    // 취소·반품·품절된 줄 — 순정은 "내역이 있습니다" 한 줄만 찍고 목록을 안 준다
    $cancelled = array();
    foreach ($items as $row) {
        if (in_array($row['status'], array('취소', '반품', '품절'), true)) $cancelled[] = $row;
    }

    g5_view('shop.orderview', array(
        'body_html'    => $body_html,
        'items'        => $items,
        'cancel_items' => $cancelled,
        'cancel_price' => isset($od['od_cancel_price']) ? (int)$od['od_cancel_price'] : 0,
        'cancel_notes' => g5_shop_cancel_notes(isset($od['od_shop_memo']) ? $od['od_shop_memo'] : ''),
        'od_id'      => isset($od['od_id']) ? $od['od_id'] : '',
        'od_time'    => isset($od['od_time']) ? $od['od_time'] : '',
        'status'     => isset($od['od_status']) ? $od['od_status'] : '',
        'list_href'  => G5_SHOP_URL.'/orderinquiry.php',
        'shop_href'  => G5_SHOP_URL.'/',
        'admin_href' => ($is_admin === 'super' && !empty($od['od_id']))
                        ? G5_ADMIN_URL.'/shop_admin/orderform.php?od_id='.$od['od_id'] : '',
    ));
}

// 취소 사유 — 순정은 od_shop_memo 에 로그처럼 덧붙인다:
//   "주문자 본인 직접 취소 - 2026-07-29 20:41:35 (취소이유 : …)"
// 이 칸에는 결제·PG 내부 이력도 함께 쌓이므로 통째로 보여주면 안 된다. 취소 사유 줄만 골라낸다.
function g5_shop_cancel_notes($memo)
{
    $out = array();
    if (!$memo) return $out;
    // 사유 자체에 괄호가 들어갈 수 있으므로(예: "주문을 잘못했어요 (수량·옵션)")
    // 비탐욕(.*?)으로 첫 ')' 에서 끊으면 안 된다. 줄 끝의 ')' 까지 통째로 잡는다.
    $re = '/주문자 본인 직접 취소 - ([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}) \(취소이유 : (.*)\)\s*$/mu';
    if (preg_match_all($re, stripslashes($memo), $m, PREG_SET_ORDER)) {
        foreach ($m as $row) {
            $out[] = array(
                'who'    => '주문자 직접 취소',
                'time'   => $row[1],
                'reason' => get_text(trim($row[2])),
            );
        }
    }
    return $out;
}

// 주문 상품 — 순정 orderinquiryview.php 와 같은 질의를 옵션 한 줄씩으로 편다.
// (순정 표는 rowspan/colspan 2줄 머리라 반응형으로 다루기 어렵다)
function g5_shop_order_items($od_id)
{
    global $g5;
    if (!$od_id) return array();
    $od_id = sql_real_escape_string($od_id);

    $out = array();
    $res = sql_query(" select it_id, it_name, ct_send_cost, it_sc_type
                         from {$g5['g5_shop_cart_table']}
                        where od_id = '$od_id' group by it_id order by ct_id ");
    while ($row = sql_fetch_array($res)) {
        $it_id = sql_real_escape_string($row['it_id']);

        // 배송비 표기 (조건부무료면 실제 계산 결과로 덮는다 — 순정과 동일)
        $send = array(1 => '착불', 2 => '무료');
        $ct_send_cost = isset($send[$row['ct_send_cost']]) ? $send[$row['ct_send_cost']] : '선불';
        if ($row['it_sc_type'] == 2) {
            $sum = sql_fetch(" select SUM(IF(io_type = 1, (io_price * ct_qty), ((ct_price + io_price) * ct_qty))) as price,
                                      SUM(ct_qty) as qty
                                 from {$g5['g5_shop_cart_table']}
                                where it_id = '$it_id' and od_id = '$od_id' ");
            if (get_item_sendcost($row['it_id'], $sum['price'], $sum['qty'], $od_id) == 0) $ct_send_cost = '무료';
        }

        $opts = sql_query(" select ct_id, ct_option, ct_qty, ct_price, ct_point, ct_status, io_type, io_price
                              from {$g5['g5_shop_cart_table']}
                             where od_id = '$od_id' and it_id = '$it_id'
                             order by io_type asc, ct_id asc ");
        $first = true;
        while ($o = sql_fetch_array($opts)) {
            $price = $o['io_type'] ? (int)$o['io_price'] : ((int)$o['ct_price'] + (int)$o['io_price']);
            $out[] = array(
                'it_id'   => $row['it_id'],
                'name'    => $row['it_name'],
                'href'    => shop_item_url($row['it_id']),
                'img'     => get_it_image($row['it_id'], 70, 70),   // 순정 <img> HTML → {!! !!}
                'first'   => $first,          // 같은 상품의 첫 옵션 줄인가 (이미지·이름을 여기만 보인다)
                'option'  => get_text($o['ct_option']),
                'qty'     => (int)$o['ct_qty'],
                'price'   => $price,
                'sum'     => $price * (int)$o['ct_qty'],
                'point'   => (int)$o['ct_point'] * (int)$o['ct_qty'],
                'send'    => $ct_send_cost,
                'status'  => $o['ct_status'],
            );
            $first = false;
        }
    }
    return $out;
}

// ── 쇼핑몰 메인 (shop/index.php)
function g5_map_shop_index()
{
    global $default;

    // 순정 index.php 와 같은 상품유형 4종 (설정에서 켠 것만)
    $blocks = array();
    $titles = array(1 => '히트상품', 2 => '추천상품', 3 => '최신상품', 4 => '인기상품');
    foreach ($titles as $type => $title) {
        if (empty($default['de_type'.$type.'_list_use'])) continue;
        $il = new item_list();
        $il->set_type($type);
        $items = g5_shop_items($il);
        if (!$items) continue;
        $blocks[] = array(
            'title' => $title,
            'href'  => shop_type_url((string)$type),
            'items' => g5_shop_item_rows($items),
        );
    }

    g5_view('shop.index', array(
        'blocks'    => $blocks,
        'cate_href' => G5_SHOP_URL.'/list.php?ca_id=',
        'categories'=> g5_shop_categories(),
    ));
}

// 1단계 상품분류 목록
function g5_shop_categories()
{
    global $g5;
    $out = array();
    $result = sql_query(" select ca_id, ca_name from `{$g5['g5_shop_category_table']}`
                           where length(ca_id) = 2 and ca_use = '1' order by ca_order, ca_id ", false);
    while ($result && ($row = sql_fetch_array($result))) {
        $out[] = array(
            'ca_id' => $row['ca_id'],
            'name'  => get_text($row['ca_name']),
            'href'  => G5_SHOP_URL.'/list.php?ca_id='.$row['ca_id'],
        );
    }
    return $out;
}

// ── 상품분류 목록 (shop/list.php)
function g5_map_shop_list($items, $total_count, $page, $total_page, $qstr2)
{
    global $ca, $ca_id, $sort, $sortodr;

    g5_view('shop.list', array(
        'category' => array(
            'ca_id' => $ca_id,
            'name'  => get_text($ca['ca_name']),
        ),
        'items'       => g5_shop_item_rows($items),
        'total_count' => (int)$total_count,
        'page'        => (int)$page,
        'total_page'  => (int)$total_page,
        'page_href'   => G5_SHOP_URL.'/list.php?ca_id='.$ca_id.'&sort='.$sort.'&sortodr='.$sortodr.'&page=',
        'sorts' => array(
            array('name' => '최신순',   'href' => G5_SHOP_URL.'/list.php?ca_id='.$ca_id.'&sort=it_id&sortodr=desc',      'active' => ($sort === 'it_id')),
            array('name' => '낮은가격', 'href' => G5_SHOP_URL.'/list.php?ca_id='.$ca_id.'&sort=it_price&sortodr=asc',     'active' => ($sort === 'it_price' && $sortodr === 'asc')),
            array('name' => '높은가격', 'href' => G5_SHOP_URL.'/list.php?ca_id='.$ca_id.'&sort=it_price&sortodr=desc',    'active' => ($sort === 'it_price' && $sortodr === 'desc')),
            array('name' => '이름순',   'href' => G5_SHOP_URL.'/list.php?ca_id='.$ca_id.'&sort=it_name&sortodr=asc',      'active' => ($sort === 'it_name')),
        ),
        'categories' => g5_shop_categories(),
    ));
}

// ── 상품 상세 (shop/item.php)
function g5_map_shop_item($form_html, $related)
{
    global $it, $ca, $g5, $default, $item_info, $is_orderable, $is_admin;
    global $item_use_count, $item_qa_count, $sns_share_links;

    $it_id = $it['it_id'];
    $price = (int)$it['it_price'];
    $cust  = (int)$it['it_cust_price'];

    g5_view('shop.item', array(
        'item' => array(
            'it_id'      => $it_id,
            'name'       => get_text($it['it_name']),
            'basic'      => conv_content($it['it_basic'], 1),        // 상품 설명 HTML → {!! !!}
            'explan'     => conv_content($it['it_explan'], 1),       // 상세 설명 HTML → {!! !!}
            'price'      => $price,
            'cust_price' => $cust,
            'discount'   => ($cust > 0 && $price > 0 && $cust > $price) ? (int)round((1 - $price / $cust) * 100) : 0,
            'is_soldout' => (bool)$it['it_soldout'],
        ),
        'category' => array(
            'ca_id' => $it['ca_id'],
            'name'  => isset($ca['ca_name']) ? get_text($ca['ca_name']) : '',
            'href'  => G5_SHOP_URL.'/list.php?ca_id='.$it['ca_id'],
        ),
        // 순정 item.form.skin.php 출력 그대로 — 이미지 갤러리·옵션·수량·장바구니가 모두 여기 들어 있고
        // js/shop.js 가 이 안의 id/class 를 잡는다. 우리가 다시 그리지 않고 CSS 로만 다듬는다.
        'form_html'    => $form_html,
        'is_orderable' => (bool)$is_orderable,
        'shop_js'      => G5_JS_URL.'/shop.js?ver='.G5_JS_VER,

        // ── 탭 내용
        'info_notice'  => g5_shop_item_info_rows($it, $item_info),                  // 상품 정보 고시
        'use_html'     => g5_blade_capture_include(G5_SHOP_PATH.'/itemuse.php'),    // 사용후기 (순정 스킨 HTML)
        'qa_html'      => g5_blade_capture_include(G5_SHOP_PATH.'/itemqa.php'),     // 상품문의
        'use_count'    => (int)$item_use_count,
        'qa_count'     => (int)$item_qa_count,
        'delivery_html'=> isset($default['de_baesong_content']) ? conv_content($default['de_baesong_content'], 1) : '',
        'change_html'  => isset($default['de_change_content'])  ? conv_content($default['de_change_content'], 1)  : '',

        'related'   => $related ? g5_shop_item_rows($related) : g5_shop_related_items($it_id),
        'cart_href' => G5_SHOP_URL.'/cart.php',
        // 상품 수정 바로가기 — 순정 itemform.php 와 같은 조건(최고관리자)일 때만
        'admin_href' => ($is_admin === 'super')
                        ? G5_ADMIN_URL.'/shop_admin/itemform.php?w=u&amp;it_id='.$it_id.'&amp;ca_id='.$it['ca_id'] : '',
    ));
}

// 관련상품 — 순정 item.info.skin.php 와 같은 질의
function g5_shop_related_items($it_id)
{
    global $g5, $default;
    if (empty($default['de_rel_list_use'])) return array();

    $sql = " select b.* from {$g5['g5_shop_item_relation_table']} a
               left join {$g5['g5_shop_item_table']} b on (a.it_id2 = b.it_id)
              where a.it_id = '".sql_real_escape_string($it_id)."' and b.it_use = '1' ";
    $rows = array();
    $res = sql_query($sql);
    while ($row = sql_fetch_array($res)) $rows[] = $row;
    return g5_shop_item_rows($rows);
}

// 상품 정보 고시 — it_info_value(직렬화)를 lib/iteminfo.lib.php 의 항목표와 맞춰 푼다
function g5_shop_item_info_rows($it, $item_info)
{
    if (empty($it['it_info_value'])) return array();
    $data = @unserialize(stripslashes($it['it_info_value']));
    if (!is_array($data)) return array();

    $gubun = $it['it_info_gubun'];
    if (!isset($item_info[$gubun]['article'])) return array();
    $article = $item_info[$gubun]['article'];

    $out = array();
    foreach ($data as $key => $val) {
        if (!isset($article[$key][0])) continue;
        $out[] = array('title' => $article[$key][0], 'value' => $val);
    }
    return $out;
}

// 순정 파일의 출력을 문자열로 받는다 (사용후기·상품문의처럼 echo 로 그리는 화면).
// 순정은 이런 파일이 전역 스코프에서 도는 것을 전제로 짰다 — 함수 안에서 그냥 include 하면
// $config·$g5·$it_id 가 안 보여 죽는다(cf_write_pages 가 없어 0 나눗셈). 전역을 끌어와 스코프를 맞춘다.
function g5_blade_capture_include($file)
{
    if (!is_file($file)) return '';
    foreach (array_keys($GLOBALS) as $g5b_key) {
        if ($g5b_key === 'GLOBALS' || $g5b_key === 'file' || $g5b_key === 'g5b_key') continue;
        global $$g5b_key;
    }
    ob_start();
    include($file);
    return trim(ob_get_clean());
}

// ── 장바구니 (shop/cart.php)
// 순정 cart.php 의 집계 로직(상품별 합계·배송비·조건부무료)을 그대로 옮긴다.
function g5_map_shop_cart($s_cart_id, $cart_action_url)
{
    global $g5, $default;

    $items = array();
    $tot_point = $tot_sell_price = 0;
    $continue_ca_id = '';

    $sql = " select a.ct_id, a.it_id, a.it_name, a.ct_price, a.ct_point, a.ct_qty,
                    a.ct_status, a.ct_send_cost, a.it_sc_type, b.ca_id
               from {$g5['g5_shop_cart_table']} a
               left join {$g5['g5_shop_item_table']} b on ( a.it_id = b.it_id )
              where a.od_id = '".sql_escape_string($s_cart_id)."'
              group by a.it_id order by a.ct_id ";
    $result = sql_query($sql);

    for ($i = 0; $result && ($row = sql_fetch_array($result)); $i++) {
        // 상품별 합계 (옵션 포함)
        $sum = sql_fetch(" select SUM(IF(io_type = 1, (io_price * ct_qty), ((ct_price + io_price) * ct_qty))) as price,
                                  SUM(ct_point * ct_qty) as point,
                                  SUM(ct_qty) as qty
                             from {$g5['g5_shop_cart_table']}
                            where it_id = '{$row['it_id']}' and od_id = '".sql_escape_string($s_cart_id)."' ");

        if ($i === 0) $continue_ca_id = $row['ca_id'];

        // 배송비 표기
        switch ($row['ct_send_cost']) {
            case 1:  $send_label = '착불'; break;
            case 2:  $send_label = '무료'; break;
            default: $send_label = '선불'; break;
        }
        // 조건부 무료배송
        if ($row['it_sc_type'] == 2) {
            if (get_item_sendcost($row['it_id'], $sum['price'], $sum['qty'], $s_cart_id) == 0) $send_label = '무료';
        }

        $items[] = array(
            'idx'        => $i,
            'ct_id'      => $row['ct_id'],
            'it_id'      => $row['it_id'],
            'name'       => get_text($row['it_name']),
            'href'       => shop_item_url($row['it_id']),
            'img'        => get_it_imageurl($row['it_id']),
            'options'    => print_item_options($row['it_id'], $s_cart_id),   // 순정 옵션 HTML → {!! !!}
            'qty'        => (int)$sum['qty'],
            'price'      => (int)$row['ct_price'],
            'point'      => (int)$sum['point'],
            'sell_price' => (int)$sum['price'],
            'send_label' => $send_label,
        );

        $tot_point      += (int)$sum['point'];
        $tot_sell_price += (int)$sum['price'];
    }

    $send_cost = $items ? (int)get_sendcost($s_cart_id, 0) : 0;

    g5_view('shop.cart', array(
        'items'       => $items,
        'count'       => count($items),
        'send_cost'   => $send_cost,
        'tot_point'   => $tot_point,
        'tot_price'   => $tot_sell_price + $send_cost,
        'action_url'  => $cart_action_url,
        'order_url'   => './orderform.php',
        'shop_url'    => G5_SHOP_URL.'/',
        'continue_url'=> $continue_ca_id ? G5_SHOP_URL.'/list.php?ca_id='.$continue_ca_id : G5_SHOP_URL.'/',
    ));
}

// ── 주문서 (shop/orderform.php)
// orderform.sub.php 는 1895줄짜리 결제 폼(PG 연동 JS·쿠폰·주소검색 포함)이다.
// 새로 만들면 결제가 깨질 위험이 커서, 순정 폼 출력을 그대로 받아 레이아웃만 씌운다.
function g5_map_shop_orderform($form_html)
{
    g5_view('shop.orderform', array(
        'form_html' => $form_html,
        'cart_url'  => G5_SHOP_URL.'/cart.php',
    ));
}
