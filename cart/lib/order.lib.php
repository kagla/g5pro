<?php
if (!defined('_GNUBOARD_')) exit;

// 배송비 계산은 delivery.lib.php 로 갔다(권역 표와 한 몸이라 그쪽이 자리다).
// cart_shipping_fee() · cart_shipping_breakdown() · cart_ship_zone_* 를 그 파일에서 찾는다.

// ---------- 주문 ----------

function cart_order_get($od_id)
{
    global $g5;
    $row = sql_fetch(" select * from `{$g5['ycart_order_table']}` where od_id = '".(int)$od_id."' ");
    return $row ? $row : null;
}

function cart_order_get_by_no($od_no)
{
    global $g5;
    $od_no = sql_real_escape_string(trim($od_no));
    if ($od_no === '') return null;
    $row = sql_fetch(" select * from `{$g5['ycart_order_table']}` where od_no = '$od_no' ");
    return $row ? $row : null;
}

function cart_order_items($od_id)
{
    global $g5;
    $rows = array();
    $result = sql_query(" select * from `{$g5['ycart_order_item_table']}`
        where od_id = '".(int)$od_id."' order by oi_id ");
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

// 손님 화면용 주문 상품 — 위 배열에 사진(img)과 상품으로 가는 문(href)을 얹는다.
// 주문완료와 주문 상세가 같은 줄을 그리므로 여기 한 곳에서 만든다.
//
// 링크는 지금 손님이 열 수 있는 상품에만 건다 — 지워졌거나 노출이 꺼졌거나 소속 분류가 모두
// 숨김인 상품으로 보내면 cart/item.php 가 "없는 상품입니다" 로 튕긴다. 그런 줄은 href 가 ''.
// 존재 확인은 줄마다 묻지 않고 한 방에 — 주문 한 건에 상품이 여럿이다.
function cart_order_items_for_view($od_id)
{
    global $g5;
    $rows = cart_order_items($od_id);
    $it_ids = array_filter(array_map(function ($r) { return (int)$r['it_id']; }, $rows));

    $shown = array();
    if ($it_ids) {
        $res = sql_query(" select it_id, it_code, it_show from `{$g5['ycart_item_table']}`
            where it_id in (".implode(',', array_unique($it_ids)).") ");
        while ($r = sql_fetch_array($res)) {
            if (!(int)$r['it_show'] || cart_item_is_hidden((int)$r['it_id'])) continue;
            $shown[(int)$r['it_id']] = $r['it_code'];
        }
    }
    $main_images = cart_item_main_images($it_ids);

    foreach ($rows as $i => $r) {
        $iid = (int)$r['it_id'];
        // 주소는 상품코드(?code=)가 정식이다
        $rows[$i]['href'] = isset($shown[$iid]) ? cart_url('item.php', array('code' => $shown[$iid])) : '';
        // 64px 자리에 원본을 내려보내지 않는다(고해상도 화면까지 128px)
        $rows[$i]['img'] = isset($main_images[$iid])
            ? cart_item_thumb_url($main_images[$iid], 128, 128) : '';
    }
    return $rows;
}

// 주문번호 — 전부 숫자, yymmdd-hhiiss-nnnn (예: 260806-211459-4831).
// 날짜-시각-난수가 하이픈으로 나뉘어 한눈에 읽히고 전화로 불러주기도 쉽다.
// 같은 초의 충돌은 난수 4자리(1/10000)가 가르고, UNIQUE 제약과 짝을 이뤄 충돌 시 재생성한다.
function cart_order_no()
{
    $no = '';
    for ($try = 0; $try < 6; $try++) {
        $no = date('ymd-His-', G5_SERVER_TIME).sprintf('%04d', mt_rand(0, 9999));
        if (!cart_order_get_by_no($no)) return $no;
    }
    return $no;
}

// 관리자 화면이 다루는 상태 전부 — draft(결제 전 초안)는 주문이 아니므로 여기 없다.
// 상태를 늘릴 때 고쳐야 할 곳이 한 군데뿐이도록 목록을 여기 모은다(예전엔 관리자 3화면에
// 각각 복제돼 있어, 새 상태를 넣으면 어느 화면에서 조용히 빠지는 구조였다).
function cart_order_statuses()
{
    return array(
        'unpaid' => '입금대기', 'paid' => '결제완료', 'preparing' => '배송준비',
        'shipping' => '배송중', 'delivered' => '배송완료', 'confirmed' => '구매확정',
        'returned' => '반품', 'canceled' => '취소',
    );
}

// 매출로 치는 상태 — 결제가 확정된 이후 전부(취소 제외). draft·unpaid 는 돈이 아니다.
// 반품(returned)도 여기 든다: 반품은 돈이 오간 거래이고 돌려준 몫은 od_refund 로 따로 빼므로,
// 상태로 통째로 제외하면 환불하지 않은 배송비까지 매출에서 사라진다. 순매출 = od_total - od_refund.
// 대시보드·정산이 같은 판정을 쓰도록 SQL 조각까지 여기서 만든다.
function cart_order_paid_statuses()
{
    return array('paid', 'preparing', 'shipping', 'delivered', 'confirmed', 'returned');
}

function cart_order_paid_where($alias = '')
{
    $col = ($alias !== '' ? $alias.'.' : '').'od_status';
    return " $col in ('".implode("', '", cart_order_paid_statuses())."') ";
}

// 상태 뱃지의 갈래 — 색이 말하는 것은 "무엇인가" 가 아니라 "봐야 하는가" 다.
// 여덟 가지에 색 여덟을 주면 어느 색도 뜻을 갖지 못하므로 여섯 갈래로 접는다.
//
// **여기가 뜻을 정하는 유일한 곳이고, 색은 템플릿이 정한다.**
// 화면은 이 값을 CSS 클래스로 그대로 쓴다 — `class="cart-status is-<갈래>"`.
// 그래서 템플릿마다 팔레트가 달라도(standard 는 중성+강조 하나, old-standard 는 소다 블루)
// "배송중은 눈에 띄는 자리" 라는 뜻은 어디서나 같다. 손님 화면 두 템플릿과 관리자
// (adm/cart/views — 색은 관리자 팔레트, 클래스는 .cart-od-status)가 모두 이 함수를 부른다.
// 새 화면·새 템플릿도 색을 스스로 고르지 말고 여기를 부른다.
// 템플릿이 갖춰야 할 클래스는 여섯: is-wait / is-go / is-ship / is-done / is-bad / is-end.
function cart_order_status_tone($status)
{
    switch ($status) {
        case 'unpaid':                       // 손님이 아직 할 일이 남았다 — 여기만 튄다
            return 'wait';
        case 'paid': case 'preparing':
            return 'go';                     // 가게가 움직이는 중 — 기다리면 된다
        case 'shipping':                     // 지금 오는 중 — 손님이 가장 자주 확인하는 상태라
            return 'ship';                   // 진행 중 파랑에 묻지 않게 채워서 띄운다
        case 'delivered': case 'confirmed':
            return 'done';                   // 잘 받았다
        case 'returned':
            return 'bad';                    // 되돌아갔다
    }
    return 'end';                            // 취소·그 밖 — 끝났고 할 일이 없다
}

function cart_order_status_label($status, $pay_method = '')
{
    // unpaid 는 수단에 따라 말이 다르다 — 무통장은 입금을 기다리고, 카드는 결제를 기다린다
    if ($status === 'unpaid') {
        return ($pay_method === '' || $pay_method === 'bank') ? '입금대기' : '결제대기';
    }
    $map = cart_order_statuses();
    if ($status === 'canceled') return '취소됨';   // 목록 필터는 '취소', 상세 문장은 '취소됨'
    return isset($map[$status]) ? $map[$status] : $status;
}

// 체크아웃 대상 — 바구니에서 구매 가능한 행만. 화면(checkout.php)과 제출(checkout_update.php)이
// 같은 함수를 쓰므로 두 화면이 서로 다른 목록을 보는 일이 없다.
// $only_ct_ids — 바로구매 스코프: 이 ct_id 들만 주문 대상으로 본다(나머지 바구니 행은 없는 셈).
// 주문서 화면과 주문 생성이 같은 스코프를 받아야 expect_ct_ids 대조가 어긋나지 않는다.
function cart_checkout_lines($owner = null, $only_ct_ids = null)
{
    $lines = array();
    $blocked = array();
    foreach (cart_cart_items($owner) as $r) {
        if (is_array($only_ct_ids) && !in_array((int)$r['ct_id'], $only_ct_ids, true)) continue;
        if ($r['avail'] && !$r['over_stock']) $lines[] = $r;
        else $blocked[] = $r;
    }
    return array('lines' => $lines, 'blocked' => $blocked);
}

// 주문 생성 — 트랜잭션 안에서 [현재가 재계산 → 재고 원자 차감 → 스냅샷 insert → 바구니 비움].
// 재고가 하나라도 부족하면 전부 롤백하고 오류 문자열을 돌려준다. 성공 시 array(od_id, od_no).
// $input: od_name, od_hp, od_email, od_zip, od_addr1, od_addr2, od_memo,
//         od_pay_method('bank'), od_depositor, guest_pw(비회원 필수), mb_id('' = 비회원)
//
// $draft=true — PG 결제용 초안. 결제 전에는 주문이 "저장"되지 않는다는 사용자 요구의 구현:
//   재고 미차감·바구니 유지, od_status='draft', 비울 ct_id 목록만 od_ct_ids 에 기록.
//   초안은 모든 조회 화면에서 제외되고, 승인 확정(cart_order_confirm_paid)이 재고 차감과
//   함께 paid 로 전이하며, 그때 cart_order_after_paid() 가 바구니를 비운다.
//   같은 세션이 다시 제출하면 이전 초안을 지우고 새로 만든다(주문서 수정 반영).
function cart_order_create($input, $owner = null, $draft = false)
{
    global $g5;

    // 바로구매 스코프 — 주문서가 보여준 것과 같은 행들만 대상으로 한다
    $only = (isset($input['only_ct_ids']) && is_array($input['only_ct_ids']) && count($input['only_ct_ids']))
        ? $input['only_ct_ids'] : null;
    $picked = cart_checkout_lines($owner, $only);
    $lines = $picked['lines'];
    if (!count($lines)) return '주문할 수 있는 상품이 없습니다.';

    // 화면-제출 대조 — 주문서가 보여준 품목 집합·상품합과 지금이 다르면(그 사이 품절·가격 변경)
    // 조용히 다른 주문을 만들지 않고 되돌려 보낸다. 주문서 폼이 hidden 으로 기대값을 보낸다.
    if (isset($input['expect_ct_ids'])) {
        $expect = array_filter(array_map('intval', explode(',', (string)$input['expect_ct_ids'])));
        $now = array_map('intval', array_column($lines, 'ct_id'));
        sort($expect);
        sort($now);
        if ($expect !== $now) return '장바구니 상태가 바뀌었습니다(품절 등). 주문서를 다시 확인해 주세요.';
    }
    if (isset($input['expect_item_total'])) {
        $expect_total = (int)$input['expect_item_total'];
        $now_total = 0;
        foreach ($lines as $l) $now_total += (int)$l['sk_price'] * (int)$l['ct_qty'];
        if ($expect_total !== $now_total) return '상품 가격이 변경되었습니다. 주문서를 다시 확인해 주세요.';
    }

    $mb_id = isset($input['mb_id']) ? trim($input['mb_id']) : '';
    if ($mb_id === '' && strlen(trim($input['guest_pw'])) < 4) {
        return '비회원 주문 비밀번호를 4자 이상 입력하세요.';
    }

    // 금액 재계산 — 바구니 표시가가 아니라 지금 이 시점의 SKU 가격으로 확정한다
    $item_total = 0;
    foreach ($lines as $l) $item_total += (int)$l['sk_price'] * (int)$l['ct_qty'];
    // 내역까지 받아 둔다 — 어느 권역이 걸려 얼마가 붙었는지를 주문에 스냅샷으로 남긴다.
    // 나중에 요금표를 고쳐도 이 주문의 근거는 그때 값 그대로여야 한다.
    $ship = cart_shipping_breakdown($item_total, $input['od_zip']);
    $ship_fee = $ship['total'];
    $ship_zone = sql_real_escape_string(mb_substr($ship['zone'], 0, 50, 'utf-8'));
    $ship_extra = (int)$ship['extra'];

    // 쿠폰 — 회원만, 주문당 한 장. 화면이 보낸 금액은 쓰지 않고 여기서 다시 계산한다
    // (주문서의 숫자는 안내일 뿐이고, 결제창에 넘어갈 금액의 근거는 이 줄이어야 한다).
    // 못 쓰는 쿠폰이면 조용히 빼지 않고 되돌려 보낸다 — 깎일 줄 알았던 금액이 말없이
    // 사라지면 손님은 결제창의 금액을 보고서야 알게 된다.
    $cm_id = 0;
    $coupon = 0;
    if (!empty($input['cm_id'])) {
        $pick = cart_coupon_pick($mb_id, (int)$input['cm_id'], $lines, $item_total);
        if (!is_array($pick)) return $pick;
        $cm_id = (int)$pick['cm_id'];
        $coupon = (int)$pick['amount'];
    }
    $total = $item_total + $ship_fee - $coupon;

    $od_no = cart_order_no();
    $who = $mb_id !== '' ? $mb_id : 'guest';

    // 이 세션의 이전 초안은 버린다 — 아직 draft 인 경우에만(결제가 끝났으면 건드리지 않는다)
    if ($draft && !empty($_SESSION['ss_cart_draft_od_id'])) {
        $old_id = (int)$_SESSION['ss_cart_draft_od_id'];
        $old = cart_order_get($old_id);
        if ($old && $old['od_status'] === 'draft') {
            sql_query(" delete from `{$g5['ycart_payment_table']}` where od_id = '$old_id' ", true);
            sql_query(" delete from `{$g5['ycart_order_item_table']}` where od_id = '$old_id' ", true);
            sql_query(" delete from `{$g5['ycart_order_table']}` where od_id = '$old_id' and od_status = 'draft' ", true);
        }
        unset($_SESSION['ss_cart_draft_od_id']);
    }

    // 컬럼 길이 상한 — STRICT 모드 서버에서 초과 입력이 트랜잭션 중 DB 오류로 죽지 않게
    // 저장 전에 자른다(utf8 varchar 는 문자 수 기준이라 mb_substr)
    $cap = function ($v, $len) { return mb_substr(trim($v), 0, $len, 'utf-8'); };
    $input['od_name'] = $cap($input['od_name'], 50);
    $input['od_hp'] = $cap($input['od_hp'], 20);
    // 수령인 — 비우면 주문자와 같다(화면의 "주문자와 동일" 체크가 기본)
    if (!isset($input['od_recv_name']) || trim($input['od_recv_name']) === '') $input['od_recv_name'] = $input['od_name'];
    if (!isset($input['od_recv_hp']) || trim($input['od_recv_hp']) === '') $input['od_recv_hp'] = $input['od_hp'];
    $input['od_recv_name'] = $cap($input['od_recv_name'], 50);
    $input['od_recv_hp'] = $cap($input['od_recv_hp'], 20);
    $input['od_email'] = $cap($input['od_email'], 100);
    $input['od_zip'] = $cap($input['od_zip'], 10);
    $input['od_addr1'] = $cap($input['od_addr1'], 255);
    $input['od_addr2'] = $cap($input['od_addr2'], 255);
    $input['od_memo'] = $cap($input['od_memo'], 255);
    $input['od_depositor'] = $cap($input['od_depositor'], 50);

    sql_query(" START TRANSACTION ", true);

    sql_query(" insert into `{$g5['ycart_order_table']}`
        (od_no, mb_id, od_name, od_hp, od_email, od_recv_name, od_recv_hp,
         od_zip, od_addr1, od_addr2, od_memo,
         od_item_total, od_ship_fee, od_ship_zone, od_ship_extra, od_coupon, od_cm_id, od_point, od_total,
         od_status, od_pay_method, od_depositor, od_guest_pw, od_ct_ids, od_ip, od_datetime)
        values ('".sql_real_escape_string($od_no)."',
                '".sql_real_escape_string($mb_id)."',
                '".sql_real_escape_string(strip_tags(trim($input['od_name'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_hp'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_email'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_recv_name'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_recv_hp'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_zip'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_addr1'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_addr2'])))."',
                '".sql_real_escape_string(strip_tags(trim($input['od_memo'])))."',
                '$item_total', '$ship_fee', '$ship_zone', '$ship_extra', '$coupon', '$cm_id', 0, '$total',
                '".($draft ? 'draft' : 'unpaid')."',
                '".sql_real_escape_string($input['od_pay_method'])."',
                '".sql_real_escape_string(strip_tags(trim($input['od_depositor'])))."',
                '".sql_real_escape_string($mb_id === '' ? create_hash(trim($input['guest_pw'])) : '')."',
                '".sql_real_escape_string($draft ? implode(',', array_map('intval', array_column($lines, 'ct_id'))) : '')."',
                '".sql_real_escape_string($_SERVER['REMOTE_ADDR'])."',
                '".G5_TIME_YMDHIS."') ", true);
    $od_id = (int)sql_insert_id();

    foreach ($lines as $l) {
        // 원자 차감 — 실패(그 사이 품절)면 전부 되돌린다. 초안은 차감하지 않는다(확정 때 차감).
        if (!$draft && !cart_stock_move((int)$l['sk_id'], -(int)$l['ct_qty'], 'order', $od_no, $who)) {
            sql_query(" ROLLBACK ", true);
            return "'".addslashes($l['it_name'])."' 의 재고가 부족합니다. 장바구니 수량을 확인해 주세요.";
        }
        sql_query(" insert into `{$g5['ycart_order_item_table']}`
            (od_id, it_id, sk_id, oi_name, oi_option, oi_price, oi_qty, oi_total, oi_status)
            values ('$od_id', '".(int)$l['it_id']."', '".(int)$l['sk_id']."',
                    '".sql_real_escape_string($l['it_name'])."',
                    '".sql_real_escape_string($l['opt_label'])."',
                    '".(int)$l['sk_price']."', '".(int)$l['ct_qty']."',
                    '".((int)$l['sk_price'] * (int)$l['ct_qty'])."', 'normal') ", true);
    }

    // 쿠폰 소진 — 무통장은 지금이 확정이라 여기서 잠근다. PG 초안은 여기서 잠그지 않는다:
    // 초안은 제출할 때마다 버려지고 다시 만들어지므로, 초안 시점에 소진하면 결제창을 닫고
    // 이탈한 손님의 쿠폰이 그대로 묶인다. 재고와 똑같이 승인 확정(confirm_paid) 자리에서 잠근다.
    if (!$draft && $cm_id > 0 && !cart_coupon_consume($cm_id, $od_id, $coupon)) {
        sql_query(" ROLLBACK ", true);
        return '쿠폰이 이미 사용되었습니다. 주문서를 다시 확인해 주세요.';
    }

    // 주문된 행만 바구니에서 비운다 — 구매 불가로 남겨둔 행은 유지. 초안은 결제 확정 때 비운다.
    if (!$draft) {
        foreach ($lines as $l) cart_cart_remove((int)$l['ct_id'], $owner);
    }

    sql_query(" COMMIT ", true);
    if ($draft) $_SESSION['ss_cart_draft_od_id'] = $od_id;

    // 배송지 자동 저장(회원) — 다음 주문서가 "저장된 배송지 불러오기"로 쓴다
    cart_address_save($mb_id, $input['od_name'], $input['od_hp'], $input['od_email'],
        $input['od_zip'], $input['od_addr1'], $input['od_addr2']);

    // 손님이 "회원정보에도 저장" 을 켰을 때만 회원 기록을 고친다(주소록 저장과 별개)
    if ($mb_id !== '' && !empty($input['save_member'])) {
        cart_member_profile_save($mb_id, $input);
    }

    return array('od_id' => $od_id, 'od_no' => $od_no);
}

// ---------- 배송지 저장(주소록) ----------
// 회원 주문 때마다 자동 저장 — 주문자(이름·연락처·이메일)까지 함께 담아 "불러오기"가 주문서를
// 한 번에 채운다. 최근 10개만 남긴다. 비회원은 대상이 아니다.
//
// 같은 곳인지는 이름·연락처·주소로만 판단한다(이메일 제외). 이메일만 바꾼 주문이 같은 배송지의
// 새 줄을 만들면 주소록이 금방 지저분해지므로, 그때는 기존 줄의 이메일을 최신 값으로 갱신한다.
function cart_address_save($mb_id, $name, $hp, $email, $zip, $addr1, $addr2)
{
    global $g5;
    $mb_id = trim($mb_id);
    if ($mb_id === '' || trim($addr1) === '') return;

    $nm_e = sql_real_escape_string(mb_substr(trim($name), 0, 50, 'utf-8'));
    $hp_e = sql_real_escape_string(mb_substr(trim($hp), 0, 20, 'utf-8'));
    $em_e = sql_real_escape_string(mb_substr(trim($email), 0, 100, 'utf-8'));
    $zip_e = sql_real_escape_string(mb_substr(trim($zip), 0, 10, 'utf-8'));
    $a1_e = sql_real_escape_string(mb_substr(trim($addr1), 0, 255, 'utf-8'));
    $a2_e = sql_real_escape_string(mb_substr(trim($addr2), 0, 255, 'utf-8'));
    $mb_e = sql_real_escape_string($mb_id);

    $dup = sql_fetch(" select ad_id from `{$g5['ycart_address_table']}`
        where mb_id = '$mb_e' and ad_name = '$nm_e' and ad_hp = '$hp_e'
          and ad_zip = '$zip_e' and ad_addr1 = '$a1_e' and ad_addr2 = '$a2_e' ");
    if ($dup) {
        sql_query(" update `{$g5['ycart_address_table']}`
            set ad_email = '$em_e', ad_datetime = '".G5_TIME_YMDHIS."'
            where ad_id = '".(int)$dup['ad_id']."' ", true);
        return;
    }
    sql_query(" insert into `{$g5['ycart_address_table']}` (mb_id, ad_name, ad_hp, ad_email, ad_zip, ad_addr1, ad_addr2, ad_datetime)
        values ('$mb_e', '$nm_e', '$hp_e', '$em_e', '$zip_e', '$a1_e', '$a2_e', '".G5_TIME_YMDHIS."') ", true);

    // 오래된 것부터 정리 — 최근 10개 유지
    $result = sql_query(" select ad_id from `{$g5['ycart_address_table']}`
        where mb_id = '$mb_e' order by ad_datetime desc, ad_id desc limit 10, 100 ");
    while ($r = sql_fetch_array($result)) {
        sql_query(" delete from `{$g5['ycart_address_table']}` where ad_id = '".(int)$r['ad_id']."' ", true);
    }
}

// 주소록에서 한 건 지운다 — 반드시 자기 것만. mb_id 를 조건에 함께 넣어
// 남의 ad_id 를 보내도 아무 행에 닿지 않게 한다. 지운 행이 있으면 true.
function cart_address_delete($mb_id, $ad_id)
{
    global $g5;
    $mb_id = trim($mb_id);
    $ad_id = (int)$ad_id;
    if ($mb_id === '' || $ad_id < 1) return false;
    sql_query(" delete from `{$g5['ycart_address_table']}`
        where ad_id = '$ad_id' and mb_id = '".sql_real_escape_string($mb_id)."' ", true);
    return (bool)get_sql_affected_rows();
}

// 주문서에서 적은 연락처·배송지를 회원 기록에도 반영한다("회원정보에도 저장" 을 켰을 때만).
//
// 이름·이메일은 손대지 않는다. 이메일은 로그인·비밀번호 찾기에 쓰이고 회원끼리 겹치면 안 되는
// 값이라 주문서에서 조용히 바꿀 것이 아니고, 이름은 본인확인(cf_cert_*)과 엮여 있다.
// 휴대폰도 본인확인이 필수인 사이트에서는 인증으로 채워진 값이므로 건드리지 않는다.
// 그래서 화면 문구도 "연락처와 배송지" 라고 정확히 적는다 — 더 저장하는 것처럼 말하지 않는다.
function cart_member_profile_save($mb_id, $input)
{
    global $g5, $config;

    $mb_id = trim($mb_id);
    if ($mb_id === '') return;

    $set = array();

    $cert_locked = (!empty($config['cf_cert_use']) && !empty($config['cf_cert_req']));
    $hp = isset($input['od_hp']) ? trim($input['od_hp']) : '';
    if (!$cert_locked && $hp !== '') {
        // 순정과 같은 모양으로 저장한다 — 관리자 목록·문자 발송이 이 형식을 전제한다
        if (function_exists('hyphen_hp_number')) $hp = hyphen_hp_number($hp);
        $set[] = " mb_hp = '".sql_real_escape_string(mb_substr($hp, 0, 20, 'utf-8'))."' ";
    }

    // 주소는 세 칸이 한 묶음이라 우편번호·기본주소가 다 있을 때만 통째로 옮긴다.
    // 반쪽만 덮으면 옛 주소와 새 주소가 섞인 배송지가 회원 기록에 남는다.
    $zip = preg_replace('/[^0-9]/', '', isset($input['od_zip']) ? $input['od_zip'] : '');
    $addr1 = isset($input['od_addr1']) ? trim($input['od_addr1']) : '';
    $addr2 = isset($input['od_addr2']) ? trim($input['od_addr2']) : '';
    if ($zip !== '' && $addr1 !== '') {
        $set[] = " mb_zip1 = '".sql_real_escape_string(substr($zip, 0, 3))."' ";
        $set[] = " mb_zip2 = '".sql_real_escape_string(substr($zip, 3, 3))."' ";
        $set[] = " mb_addr1 = '".sql_real_escape_string(mb_substr($addr1, 0, 255, 'utf-8'))."' ";
        $set[] = " mb_addr2 = '".sql_real_escape_string(mb_substr($addr2, 0, 255, 'utf-8'))."' ";
        // 주문서는 참고항목·지번여부를 받지 않는다 — 옛 주소의 흔적이 새 주소에 붙지 않게 비운다
        $set[] = " mb_addr3 = '' ";
        $set[] = " mb_addr_jibeon = '' ";
    }

    if (!count($set)) return;
    sql_query(" update `{$g5['member_table']}` set ".implode(',', $set)."
        where mb_id = '".sql_real_escape_string($mb_id)."' ", true);
}

// 주문서 기본값 한 칸 — 가장 최근 주소록 값이 있으면 그것, 없으면 회원 정보.
// 주소록 조회는 한 요청에 한 번만 한다(칸마다 부르지 않게 캐시).
function cart_address_default($member, $ad_key, $mb_key)
{
    static $recent = null;
    if ($recent === null) {
        $list = !empty($member['mb_id']) ? cart_address_list($member['mb_id'], 1) : array();
        $recent = count($list) ? $list[0] : array();
    }
    if (!empty($recent[$ad_key])) return $recent[$ad_key];
    return isset($member[$mb_key]) ? $member[$mb_key] : '';
}

function cart_address_list($mb_id, $limit = 10)
{
    global $g5;
    $mb_id = trim($mb_id);
    if ($mb_id === '') return array();
    $rows = array();
    $result = sql_query(" select * from `{$g5['ycart_address_table']}`
        where mb_id = '".sql_real_escape_string($mb_id)."'
        order by ad_datetime desc, ad_id desc limit ".(int)$limit." ");
    while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}

// ---------- 관리자 상태 전이 ----------
// 허용 전이 화이트리스트 — 여기 없는 전이는 어떤 화면에서도 못 한다.
//   deposit  : unpaid(무통장) → paid (입금확인)
//   cancel   : unpaid·paid·preparing → canceled (+재고 복원. PG 환불은 별도 — 화면이 안내)
//   preparing: paid → preparing / shipping: paid·preparing → shipping / delivered: shipping → delivered
//   confirm  : delivered → confirmed (구매확정 — 고객이 직접 누르거나 관리자가 대신)
// 행을 잠그고 잠긴 상태로 재검증한다 — 결제 리턴(confirm_paid)과 같은 규율.
// 성공 시 빈 문자열, 실패 시 사유 문자열 반환.
function cart_order_transition($od_id, $action, $who = 'admin', $memo = '')
{
    global $g5;
    $od_id = (int)$od_id;

    $rules = array(
        'deposit' => array('from' => array('unpaid'), 'to' => 'paid'),
        'cancel' => array('from' => array('unpaid', 'paid', 'preparing'), 'to' => 'canceled'),
        'preparing' => array('from' => array('paid'), 'to' => 'preparing'),
        'shipping' => array('from' => array('paid', 'preparing'), 'to' => 'shipping'),
        'delivered' => array('from' => array('shipping'), 'to' => 'delivered'),
        // 구매확정은 배송완료에서만 — 아직 안 받은 물건을 확정할 수는 없다.
        // 되돌리는 전이는 두지 않는다(확정은 매듭이고, 이후 포인트 적립·반품 마감의 기준이 된다)
        'confirm' => array('from' => array('delivered'), 'to' => 'confirmed'),
        // 잘못 누른 것을 되돌린다. 앞으로 가는 길만 있으면 오클릭 한 번이 화면에서 고칠 수 없는
        // 자리로 주문을 밀어 넣는다 — 특히 배송완료는 손님에게 구매확정 문을 열고 반품 기한을
        // 시작시킨다. 되돌릴 때 그 시각들도 함께 지운다(안 지우면 기한이 헛 시각을 가리킨다).
        // 구매확정된 주문은 대상이 아니다 — from 이 delivered 뿐이라 규칙만으로 막힌다.
        'unship' => array('from' => array('shipping'), 'to' => 'paid'),
        'undeliver' => array('from' => array('delivered'), 'to' => 'shipping'),
        // 입금확인 되돌리기 — 입금관리에서 여러 건을 한 번에 넘기게 되면서 필요해졌다.
        // 한 건을 잘못 누르는 것과 열 건을 잘못 넘기는 것은 무게가 다르다.
        // 무통장만 대상이다(아래 pay_method 검사). 재고는 주문 때 이미 차감돼 있어 건드릴 것이 없다.
        'undeposit' => array('from' => array('paid'), 'to' => 'unpaid'),
    );
    if (!isset($rules[$action])) return '허용되지 않는 처리입니다.';
    $rule = $rules[$action];

    $fail = '';
    sql_query(" set autocommit = 0 ", true);
    sql_query(" start transaction ", true);

    $cur = sql_fetch(" select * from `{$g5['ycart_order_table']}` where od_id = '$od_id' for update ");
    if (!$cur) {
        $fail = '주문이 없습니다.';
    } elseif (!in_array($cur['od_status'], $rule['from'], true)) {
        $fail = '현재 상태('.cart_order_status_label($cur['od_status'], $cur['od_pay_method']).')에서는 할 수 없는 처리입니다.';
    } elseif ($action === 'deposit' && $cur['od_pay_method'] !== 'bank') {
        $fail = '무통장 주문만 입금확인 처리할 수 있습니다.';
    } elseif ($action === 'undeposit' && $cur['od_pay_method'] !== 'bank') {
        // 카드·간편결제의 paid 는 승인이 난 것이라 되돌린다고 돈이 돌아오지 않는다.
        // 그건 취소(자동 환불) 경로가 할 일이다.
        $fail = '무통장 주문만 입금확인을 되돌릴 수 있습니다.';
    } elseif ($action === 'confirm' && cart_return_blocks_confirm($od_id)) {
        // 반품이 처리를 기다리는 동안은 확정할 수 없다. 확정은 "다 잘 받았다" 는 매듭이라
        // 반품 진행 중에 찍히면 말이 어긋나고, 확정 뒤에는 반품 신청을 받지 않으므로
        // 손님의 신청이 그대로 묻힌다. 화면이 아니라 여기서 막는다 — 관리자 버튼도 같은 문을 쓴다.
        $fail = '반품 신청이 처리 중입니다. 처리를 마친 뒤에 구매확정할 수 있습니다.';
    } elseif ($action === 'undeliver' && count(cart_return_rows($od_id))) {
        // 반품은 배송완료 뒤에만 신청할 수 있다. 신청이 하나라도 있는 주문을 배송중으로
        // 되돌리면 "아직 안 갔는데 반품 신청이 있는" 말이 안 되는 주문이 된다.
        $fail = '반품 신청이 있는 주문은 되돌릴 수 없습니다. 반품을 먼저 처리하세요.';
    }

    // 취소는 재고를 원장에 남기며 되돌린다 — 주문 생성(차감)의 정확한 역연산.
    // 쿠폰도 같은 자리에서 되돌린다(아래 상태 갱신 뒤).
    if ($fail === '' && $action === 'cancel') {
        foreach (cart_order_items($od_id) as $it) {
            if (!cart_stock_move((int)$it['sk_id'], (int)$it['oi_qty'], 'cancel', $cur['od_no'], $who)) {
                $fail = '재고 복원에 실패했습니다.';
                break;
            }
        }
    }

    if ($fail === '') {
        $set = " od_status = '".$rule['to']."' ";
        if ($action === 'deposit') $set .= ", od_paid_at = '".G5_TIME_YMDHIS."' ";
        if ($action === 'shipping') $set .= ", od_shipped_at = '".G5_TIME_YMDHIS."' ";
        // 반품 기한은 "받은 날부터" 세므로 배송완료 시각을 따로 남긴다
        if ($action === 'delivered') $set .= ", od_delivered_at = '".G5_TIME_YMDHIS."' ";
        if ($action === 'confirm') $set .= ", od_confirmed_at = '".G5_TIME_YMDHIS."' ";
        // 되돌릴 때는 그 단계가 찍어 둔 시각도 지운다 — 남겨 두면 반품 기한이 헛 시각을 센다
        if ($action === 'unship') $set .= ", od_shipped_at = '1970-01-01 00:00:00' ";
        if ($action === 'undeliver') $set .= ", od_delivered_at = '1970-01-01 00:00:00' ";
        // 입금 시각을 지운다 — 안 지우면 "입금대기인데 입금 시각이 있는" 주문이 되고,
        // 무통장 만료 자동취소가 세는 기준(od_datetime)과 화면의 말이 어긋난다.
        if ($action === 'undeposit') $set .= ", od_paid_at = '1970-01-01 00:00:00' ";
        // 취소한 주문에 쓴 쿠폰은 손님에게 돌려준다. 기한이 이미 지났으면 되살려도 못 쓰지만
        // 기한을 늘려 주는 것은 쿠폰 정책을 바꾸는 일이라 사람이 정할 몫이다.
        if ($action === 'cancel') cart_coupon_release($od_id);
        sql_query(" update `{$g5['ycart_order_table']}` set $set
            where od_id = '$od_id' and od_status = '".sql_real_escape_string($cur['od_status'])."' ", true);
        if (get_sql_affected_rows() < 1) $fail = '상태가 이미 바뀌었습니다. 다시 확인해 주세요.';
    }

    // 이력은 상태 갱신과 같은 트랜잭션에 담는다 — 둘 중 하나만 남는 상태를 만들지 않는다.
    // 이 함수가 상태를 바꾸는 유일한 문이라 여기 한 줄이면 빠지는 전이가 없다.
    if ($fail === '') {
        sql_query(" insert into `{$g5['ycart_order_log_table']}`
            set od_id = '$od_id',
                ol_action = '".sql_real_escape_string($action)."',
                ol_from = '".sql_real_escape_string($cur['od_status'])."',
                ol_to = '".sql_real_escape_string($rule['to'])."',
                ol_who = '".sql_real_escape_string(mb_substr((string)$who, 0, 50, 'utf-8'))."',
                ol_ip = '".sql_real_escape_string(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')."',
                ol_memo = '".sql_real_escape_string(mb_substr(trim((string)$memo), 0, 255, 'utf-8'))."',
                ol_datetime = '".G5_TIME_YMDHIS."' ", true);
    }

    sql_query($fail === '' ? " commit " : " rollback ", true);
    sql_query(" set autocommit = 1 ", true);
    return $fail;
}

// 주문 한 건의 상태 변경 이력 — 최근 것부터. 관리자 주문상세가 표로 보여 준다.
// 방금 무엇을 눌렀는지가 가장 자주 찾는 정보라 맨 위에 둔다(배송관리 목록과 같은 방향).
function cart_order_log_rows($od_id)
{
    global $g5;
    $rows = array();
    $result = sql_query(" select * from `{$g5['ycart_order_log_table']}`
        where od_id = '".(int)$od_id."' order by ol_id desc ");
    while ($r = sql_fetch_array($result)) {
        $r['action_label'] = cart_order_action_label($r['ol_action']);
        // 누가 눌렀나 — 시스템·손님은 그렇게 적고, 나머지는 관리자 아이디 그대로
        $r['who_label'] = ($r['ol_who'] === 'system') ? '자동'
            : (($r['ol_who'] === 'customer') ? '손님' : $r['ol_who']);
        $rows[] = $r;
    }
    return $rows;
}

// 전이 이름을 사람 말로. 상태 이름표(cart_order_status_label)와 달리 "무엇을 했나" 를 적는다.
function cart_order_action_label($action)
{
    $map = array(
        'deposit' => '입금확인', 'preparing' => '배송준비', 'shipping' => '발송',
        'delivered' => '배송완료', 'confirm' => '구매확정', 'cancel' => '주문취소',
        'unship' => '발송 되돌림', 'undeliver' => '배송완료 되돌림',
        'undeposit' => '입금확인 되돌림',
        'edit' => '정보 수정',
    );
    return isset($map[$action]) ? $map[$action] : $action;
}

// ---------- 부대 정보 수정 ----------
// 주문에서 "무엇을 사는가"(품목·수량·금액)가 아니라 "어디로·누구 이름으로"에 해당하는 값만 고친다.
// 손님 화면과 관리자 화면이 같은 문을 쓴다 — 허용 목록·자르기·이력이 한 곳에만 있으면
// 한쪽에서 필드를 늘렸을 때 다른 쪽이 조용히 뒤처지지 않는다.
//
// "누가 언제까지 고칠 수 있나" 는 부르는 쪽이 정한다(손님은 발송 전 일부, 관리자는 발송 전 전부).
// 여기서는 "무엇을 고칠 수 있나" 만 못 박는다.
//
// 반환: '' 이면 성공(바뀐 것이 없어도 성공), 아니면 사람이 읽을 오류.
function cart_order_edit_fields($od_id, $fields, $who)
{
    global $g5;
    $od_id = (int)$od_id;

    // 컬럼 => (이력에 적을 이름, 최대 길이). 저장 때(cart_order_create)와 같은 길이로 자른다.
    // 금액(od_total·od_ship_fee)·상태·재고에 걸린 값은 일부러 뺐다 — 그것들은 문자열 수정이
    // 아니라 재계산·환불이 따라붙는 일이라 이 문으로 들어오면 안 된다.
    $allow = array(
        'od_depositor' => array('입금자명', 50),
        'od_recv_name' => array('받는분', 50),
        'od_recv_hp'   => array('받는분 연락처', 20),
        'od_name'      => array('주문자', 50),
        'od_hp'        => array('주문자 연락처', 20),
        'od_email'     => array('이메일', 100),
        'od_zip'       => array('우편번호', 10),
        'od_addr1'     => array('주소', 255),
        'od_addr2'     => array('상세주소', 255),
        'od_memo'      => array('배송 요청', 255),
    );

    $cur = sql_fetch(" select * from `{$g5['ycart_order_table']}` where od_id = '$od_id' ");
    if (!$cur) return '주문이 없습니다.';

    $sets = array();
    $diff = array();
    foreach ((array)$fields as $col => $val) {
        if (!isset($allow[$col]) || !isset($cur[$col])) continue;   // 허용 목록 밖은 조용히 버린다
        list($label, $len) = $allow[$col];
        $val = mb_substr(strip_tags(trim((string)$val)), 0, $len, 'utf-8');
        if ((string)$cur[$col] === $val) continue;                  // 안 바뀐 값은 이력에도 안 남긴다
        $sets[] = " `$col` = '".sql_real_escape_string($val)."' ";
        $diff[] = $label.' '.($cur[$col] !== '' ? $cur[$col] : '(없음)').'→'.($val !== '' ? $val : '(없음)');
    }
    if (!count($sets)) return '';

    sql_query(" update `{$g5['ycart_order_table']}` set ".implode(',', $sets)."
        where od_id = '$od_id' ", true);

    // 이력 — 배송지가 바뀐 주문은 나중에 반드시 "왜 이 주소로 갔나" 를 되짚게 된다.
    // 상태는 안 바뀌므로 from·to 에 지금 상태를 그대로 적는다(전이가 아님이 표에서 드러난다).
    sql_query(" insert into `{$g5['ycart_order_log_table']}`
        set od_id = '$od_id', ol_action = 'edit',
            ol_from = '".sql_real_escape_string($cur['od_status'])."',
            ol_to = '".sql_real_escape_string($cur['od_status'])."',
            ol_who = '".sql_real_escape_string(mb_substr((string)$who, 0, 50, 'utf-8'))."',
            ol_ip = '".sql_real_escape_string(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')."',
            ol_memo = '".sql_real_escape_string(mb_substr(implode(' / ', $diff), 0, 255, 'utf-8'))."',
            ol_datetime = '".G5_TIME_YMDHIS."' ", true);
    return '';
}

// 손님이 주문을 취소할 수 있나 — 배송 준비 전까지, 그리고 돈을 되돌릴 길이 있을 때만.
// 반환: '' 이면 가능, 아니면 왜 안 되는지(화면이 그대로 보여 준다).
//
// 무통장 결제완료가 유일한 예외다. 통장에 들어온 돈은 자동으로 되돌릴 방법이 없어서,
// 손님이 버튼 하나로 취소하면 "돌려줄 돈" 이 어디에도 안 남는다. 사람이 계좌를 받아야 한다.
function cart_order_customer_cancel_why_not($order)
{
    if (!$order) return '주문을 찾을 수 없습니다.';
    if ($order['od_status'] === 'unpaid') return '';
    if ($order['od_status'] === 'paid') {
        return ($order['od_pay_method'] === 'bank')
            ? '입금이 확인된 주문은 환불 계좌가 필요해 화면에서 바로 취소할 수 없습니다. 판매자에게 문의해 주세요.'
            : '';
    }
    return '배송 준비가 시작되어 취소할 수 없습니다. 판매자에게 문의해 주세요.';
}

// 손님이 고르는 취소 사유 — 빈 칸 앞에서 무슨 말을 적어야 할지 고민하지 않게 한다.
// 관리자 쪽 목록(주문 취소 모달)과 문구가 다른 이유: 여기는 손님이 자기 사정을 말하는 자리다.
function cart_cancel_reasons()
{
    return array('단순 변심', '주문 실수(수량·옵션)', '다시 주문하려고', '배송이 늦어져서', '다른 곳에서 구매');
}

// 이 주문을 지금 요청자가 볼 수 있는가 — 회원 본인, 방금 주문한 세션, 비회원 조회 인증 세션.
// 상세 화면과 구매확정 같은 처리 화면이 같은 판정을 쓰도록 한 곳에 둔다(둘이 어긋나면
// 화면에는 보이는데 버튼은 안 먹거나, 그 반대가 된다).
function cart_order_is_mine($order)
{
    global $member;
    if (!$order) return false;
    $mb_id = isset($member['mb_id']) ? $member['mb_id'] : '';
    if ($mb_id !== '' && $mb_id === $order['mb_id']) return true;
    if (!empty($_SESSION['ss_cart_last_od_no']) && $_SESSION['ss_cart_last_od_no'] === $order['od_no']) return true;
    if (!empty($_SESSION['ss_cart_guest_od_no']) && $_SESSION['ss_cart_guest_od_no'] === $order['od_no']) return true;
    return false;
}

// 무통장 입금 기한 초과 자동취소.
// 무통장 주문은 생성 즉시 재고를 차감하므로(cart_order_create), 기한이 없으면 입금하지 않은
// 주문이 재고를 무기한 잠근다 — 팔 수 있는 물건이 장부에만 없는 상태가 된다.
// 취소는 관리자 취소와 같은 경로(cart_order_transition)를 타서 재고 복원·잠금 규율이 하나로 남는다.
// PG 주문은 대상이 아니다: 초안(draft) 방식이라 승인 전 주문은 아예 조회에 안 잡히고,
// unpaid 로 남는 PG 주문은 지금 흐름에서 생기지 않는다.
// 한 번에 100건까지만 — 오래 밀린 몰에서 한 요청이 몇 분씩 붙잡지 않게 한다(다음 날 이어서 돈다).
// 반환: 취소한 주문번호 배열.
function cart_order_expire_unpaid()
{
    global $g5;
    $cc = cart_config();
    $days = (int)$cc['cc_unpaid_days'];
    if ($days < 1) return array();          // 0 = 자동취소 안 함

    $limit = date('Y-m-d H:i:s', G5_SERVER_TIME - $days * 86400);
    $targets = array();
    $result = sql_query(" select od_id, od_no from `{$g5['ycart_order_table']}`
        where od_status = 'unpaid' and od_pay_method = 'bank'
          and od_datetime < '$limit' order by od_id limit 100 ");
    while ($r = sql_fetch_array($result)) $targets[] = $r;

    $done = array();
    $reason = '입금 기한('.$days.'일) 초과로 자동 취소되었습니다.';
    foreach ($targets as $t) {
        // 전이가 실패하면(그새 입금확인 등) 그 건만 건너뛴다 — 나머지는 계속 처리한다
        if (cart_order_transition((int)$t['od_id'], 'cancel', 'system', $reason) !== '') continue;
        sql_query(" update `{$g5['ycart_order_table']}`
            set od_cancel_reason = '".sql_real_escape_string($reason)."',
                od_canceled_at = '".G5_TIME_YMDHIS."', od_canceled_by = 'system'
            where od_id = '".(int)$t['od_id']."' ", true);
        $done[] = $t['od_no'];
    }
    return $done;
}

// 결제 확정 직후 마무리 — 초안이 예약해 둔 장바구니 행을 비우고 세션 표식을 정리한다.
// 리턴(pay_return)은 사용자 브라우저 요청이라 주문 당시 세션이 살아 있다. od_ct_ids 를
// 비워 두 번 불려도 무해하게 한다. 무통장(비초안) 주문은 od_ct_ids 가 비어 있어 그냥 지나간다.
function cart_order_after_paid($od_id)
{
    global $g5;
    $od = cart_order_get((int)$od_id);
    if (!$od) return;
    if ($od['od_ct_ids'] !== '') {
        foreach (array_filter(array_map('intval', explode(',', $od['od_ct_ids']))) as $ct_id) {
            cart_cart_remove($ct_id);
        }
        sql_query(" update `{$g5['ycart_order_table']}` set od_ct_ids = '' where od_id = '".(int)$od_id."' ", true);
    }
    if (!empty($_SESSION['ss_cart_draft_od_id']) && (int)$_SESSION['ss_cart_draft_od_id'] === (int)$od_id) {
        unset($_SESSION['ss_cart_draft_od_id']);
    }
}
