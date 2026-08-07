<?php
/**
 * 옵션 조합 프리셋 저장·삭제 (AJAX 전용).
 *
 * 상품 폼의 옵션 입력칸(옵션명·값)을 이름 붙여 저장해 두고 다음 상품에서 불러 쓴다.
 * 불러오기는 화면을 그릴 때 목록을 통째로 내려 주므로(item_form.php) 여기서 다루지 않는다 —
 * 여기는 쓰기만 맡는다.
 *
 * 응답은 JSON 하나다. {ok:true, presets:[...]} 또는 {ok:false, msg}.
 * 성공 시 갱신된 목록을 함께 돌려주어 화면이 다시 읽지 않아도 select 를 새로 그릴 수 있다.
 */

$sub_menu = '600100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// 프리셋 테이블은 모듈이 자리잡은 뒤에 추가됐다. 이미 설치를 마친 사이트는 첫 진입 자동 설치를
// 다시 타지 않으므로(cart_installed 는 설정 테이블만 본다), 쓰기 직전에 없으면 여기서 만든다.
// cart_create_tables() 는 없는 테이블만 만들어 여러 번 불러도 안전하다.
if (!sql_query(" DESC `{$g5['ycart_option_preset_table']}` ", false)) cart_create_tables();

function cart_preset_out($ok, $msg = '')
{
    $res = array('ok' => $ok);
    if (!$ok) {
        $res['msg'] = $msg;
    } else {
        // 화면이 곧바로 다시 그릴 수 있게 이름·내용만 추린 목록을 함께 준다
        $list = array();
        foreach (cart_option_preset_list() as $p) {
            $list[] = array('id' => (int)$p['op_id'], 'name' => $p['op_name'], 'sets' => $p['sets']);
        }
        $res['presets'] = $list;
    }
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

$w = (isset($_POST['w']) && !is_array($_POST['w'])) ? $_POST['w'] : '';

if ($w === 'd') {
    $op_id = (isset($_POST['op_id']) && !is_array($_POST['op_id'])) ? (int)$_POST['op_id'] : 0;
    if ($op_id < 1) cart_preset_out(false, '지울 조합을 고르세요.');
    cart_option_preset_delete($op_id);
    cart_preset_out(true);
}

// 저장 — 옵션 묶음은 JSON 문자열로 온다([{name, vals[]}, ...]).
// 정규화·검증은 라이브러리(cart_option_preset_save)가 하고 여기서는 형태만 본다.
$name = (isset($_POST['op_name']) && !is_array($_POST['op_name'])) ? $_POST['op_name'] : '';
$raw  = (isset($_POST['op_data']) && !is_array($_POST['op_data'])) ? $_POST['op_data'] : '';
$sets = json_decode($raw, true);
if (!is_array($sets)) cart_preset_out(false, '옵션 내용을 읽지 못했습니다.');

$err = cart_option_preset_save($name, $sets);
if ($err) cart_preset_out(false, $err);
cart_preset_out(true);
