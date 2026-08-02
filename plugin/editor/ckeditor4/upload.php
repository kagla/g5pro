<?php
/*
 * /plugin/editor/ckeditor4/upload.php — g5se 용 CKEditor 4 이미지 업로드.
 * 원본 (Laravel 기반 EditorImage 의존) 을 gnuboard 표준 패턴으로 재작성.
 *
 * 응답 형식:
 *  - GET responseType=json (또는 imageUploadUrl 의 drop-paste): JSON
 *  - 그 외 (filebrowserUploadUrl): <script>window.parent.CKEDITOR.tools.callFunction(...)
 */

include_once(__DIR__ . '/../../../common.php');

$responseType = isset($_GET['responseType']) ? strtolower($_GET['responseType']) : '';
if ($responseType !== 'json' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $responseType = 'json';
}

function ckeditor4_print_error($type, $msg) {
    if ($type === 'json') {
        echo json_encode(array('uploaded' => 0, 'error' => array('message' => $msg)));
    } else {
        echo "<script>alert(" . json_encode($msg) . ");</script>";
    }
    exit;
}

// 로그인 가드 — 비회원 업로드 차단
if (!isset($is_member) || !$is_member) {
    ckeditor4_print_error($responseType, '로그인 후 이용해 주십시오.');
}

// 업로드 대상 파일
if (empty($_FILES['upload']) || empty($_FILES['upload']['tmp_name'])) {
    ckeditor4_print_error($responseType, '파일이 존재하지 않습니다.');
}
$upFile = $_FILES['upload'];

// 확장자 검증
$ext = strtolower(pathinfo($upFile['name'], PATHINFO_EXTENSION));
if (!preg_match('/^(jpe?g|gif|png|webp)$/', $ext)) {
    ckeditor4_print_error($responseType, 'jpg / gif / png / webp 파일만 가능합니다.');
}
if (isset($upFile['size']) && $upFile['size'] >= (15 * 1024 * 1024)) {
    ckeditor4_print_error($responseType, '이미지 파일의 용량을 15M 미만으로 올려주세요.');
}
if ($ext === 'jpeg') $ext = 'jpg';

// 저장 경로 — data/editor/YYMM
$ym = date('ym', G5_SERVER_TIME);
$data_dir = G5_DATA_PATH . '/editor/' . $ym;
$data_url = G5_DATA_URL  . '/editor/' . $ym;
if (!is_dir($data_dir)) {
    @mkdir($data_dir, G5_DIR_PERMISSION, true);
    @chmod($data_dir, G5_DIR_PERMISSION);
}

// 파일명: ip_microtime.ext
$file_name = sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) . '_' . get_microtime() . '.' . $ext;
$save_dir  = $data_dir . '/' . $file_name;

// 보안 hook (선택)
if (function_exists('run_event')) {
    run_event('ckeditor_photo_upload', $data_dir, $data_url);
}

if (!move_uploaded_file($upFile['tmp_name'], $save_dir)) {
    ckeditor4_print_error($responseType, '업로드 실패');
}
@chmod($save_dir, defined('G5_FILE_PERMISSION') ? G5_FILE_PERMISSION : 0644);

// 이미지 검증 (변조 차단)
$imgsize = @getimagesize($save_dir);
if (!$imgsize) {
    @unlink($save_dir);
    ckeditor4_print_error($responseType, '올바른 이미지가 아닙니다.');
}

$file_url = $data_url . '/' . $file_name;
$funcNum  = isset($_GET['CKEditorFuncNum']) ? (int)$_GET['CKEditorFuncNum'] : 0;

if ($responseType === 'json') {
    echo json_encode(array(
        'uploaded' => 1,
        'fileName' => $file_name,
        'url'      => $file_url,
    ));
} else {
    echo "<script>window.parent.CKEDITOR.tools.callFunction({$funcNum}, " . json_encode($file_url) . ", '');</script>";
}
exit;
