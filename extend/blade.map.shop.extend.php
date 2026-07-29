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
    global $it, $ca, $default;

    $it_id = $it['it_id'];
    $price = (int)$it['it_price'];
    $cust  = (int)$it['it_cust_price'];

    g5_view('shop.item', array(
        'item' => array(
            'it_id'      => $it_id,
            'name'       => get_text($it['it_name']),
            'img'        => get_it_imageurl($it_id),
            'basic'      => conv_content($it['it_basic'], 1),        // 상품 설명 HTML → {!! !!}
            'explan'     => conv_content($it['it_explan'], 1),       // 상세 설명 HTML → {!! !!}
            'price'      => $price,
            'cust_price' => $cust,
            'discount'   => ($cust > 0 && $price > 0 && $cust > $price) ? (int)round((1 - $price / $cust) * 100) : 0,
            'point'      => (int)$it['it_point'],
            'maker'      => get_text($it['it_maker']),
            'origin'     => get_text($it['it_origin']),
            'brand'      => get_text($it['it_brand']),
            'model'      => get_text($it['it_model']),
            'delivery'   => (int)$it['it_sc_price'],
            'is_soldout' => (bool)$it['it_soldout'],
            'stock'      => get_it_stock_qty($it_id),
        ),
        'category' => array(
            'ca_id' => $it['ca_id'],
            'name'  => isset($ca['ca_name']) ? get_text($ca['ca_name']) : '',
            'href'  => G5_SHOP_URL.'/list.php?ca_id='.$it['ca_id'],
        ),
        'form_html' => $form_html,   // 순정 item.form.skin.php 출력(옵션·수량·장바구니 버튼) → {!! !!}
        'related'   => g5_shop_item_rows($related),
        'cart_href' => G5_SHOP_URL.'/cart.php',
    ));
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
