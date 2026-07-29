<?php
if (!defined('_GNUBOARD_')) exit;

$categories = array();
if ($is_category && $board['bo_category_list']) {
    $w_ca_name = isset($write['ca_name']) ? $write['ca_name'] : (isset($sca) ? $sca : '');
    foreach (explode('|', (string)$board['bo_category_list']) as $c) {
        $categories[] = array('name' => $c, 'selected' => ($w_ca_name === $c));
    }
}

// 순정 write_update.php 계약: 필수 hidden (basic 스킨과 동일)
// token 은 넣지 않는다 — js/common.js 가 제출 시 write_token.php 에서 받아 주입
$hidden = array(
    'uid'      => get_uniqid(),
    'w'        => $w,
    'bo_table' => $bo_table,
    'wr_id'    => isset($wr_id) ? $wr_id : '',
    'sca'      => isset($sca) ? $sca : '',
    'sfl'      => isset($sfl) ? $sfl : '',
    'stx'      => isset($stx) ? $stx : '',
    'spt'      => isset($spt) ? $spt : '',
    'sst'      => isset($sst) ? $sst : '',
    'sod'      => isset($sod) ? $sod : '',
    'page'     => isset($page) ? $page : '',
);

// 옵션: basic 스킨 로직 이식 (마크업은 뷰에서)
$option_hidden = '';
$options = array(); // ['name','value','label','checked']
if ($is_notice) {
    $options[] = array('name' => 'notice', 'value' => '1', 'label' => '공지', 'checked' => (bool)$notice_checked);
}
if ($is_html) {
    if ($is_dhtml_editor) {
        $option_hidden .= '<input type="hidden" value="html1" name="html">';
    } else {
        $options[] = array('name' => 'html', 'value' => $html_value, 'label' => 'html', 'checked' => (bool)$html_checked);
    }
}
if ($is_secret) {
    if ($is_admin || $is_secret == 1) {
        $options[] = array('name' => 'secret', 'value' => 'secret', 'label' => '비밀글', 'checked' => (bool)$secret_checked);
    } else {
        $option_hidden .= '<input type="hidden" name="secret" value="secret">';
    }
}
if ($is_mail) {
    $options[] = array('name' => 'mail', 'value' => 'mail', 'label' => '답변메일받기', 'checked' => (bool)$recv_email_checked);
}

g5_view('bbs.board_write', array(
    'board' => array(
        'bo_table'   => $bo_table,
        'bo_subject' => $board['bo_subject'],
    ),
    'w'          => $w,                       // '' 새글, 'u' 수정, 'r' 답변
    'action_url' => $action_url,
    'subject'    => $subject,                 // write.php 가공 완료(get_text) → 뷰 value 에 {!! !!}
    'categories' => $categories,
    'hidden'     => $hidden,
    'options'    => $options,
    'option_hidden' => $option_hidden,        // 스킨 조립 hidden HTML → {!! !!}
    'is_member'  => (bool)$is_member,
    'name'       => $name,
    'is_name'    => (bool)$is_name,
    'is_password'=> (bool)$is_password,
    'editor_html'    => $editor_html,          // 순정 에디터/textarea HTML → {!! !!}
    'editor_js'      => $editor_js,            // 순정 검증 JS 조각 → {!! !!}
    'is_use_captcha' => (bool)$is_use_captcha,
    'captcha_html'   => $is_use_captcha ? captcha_html() : '',
    'captcha_js'     => $is_use_captcha ? captcha_js() : '',
    'file_count'     => (int)$file_count,
    'files_exist'    => (function () use ($w, $file, $file_count) {
        // 수정 모드에서 기존 첨부 파일명 (인덱스 = 파일 슬롯)
        if ($w !== 'u' || !isset($file) || !is_array($file)) return array();
        $r = array();
        for ($i = 0; $i < $file_count; $i++) {
            $r[$i] = isset($file[$i]['source']) ? $file[$i]['source'] : '';
        }
        return $r;
    })(),
    'list_href'      => short_url_clean(G5_BBS_URL.'/board.php?bo_table='.$bo_table),
));
