<?php
$sub_menu = '950700';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

// 모듈을 업데이트한 뒤 관리자가 이 버튼 한 번으로 스키마를 맞추는 것이 운영 절차다.
// 순정 DB업그레이드와는 무관하며, booking_install() 은 멱등이므로 몇 번 눌러도 안전하다.
$ran = false;
$created = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_token();

    $before = array();
    foreach (booking_table_ddl() as $key => $ddl) {
        $before[$key] = (bool)sql_query(" DESC `{$g5[$key]}` ", false);
    }
    booking_install();
    foreach ($before as $key => $existed) {
        if (!$existed) $created[] = $g5[$key];
    }
    $ran = true;
}

$tables = array();
foreach (booking_table_ddl() as $key => $ddl) {
    $tables[] = array('name' => $g5[$key], 'exists' => (bool)sql_query(" DESC `{$g5[$key]}` ", false));
}

$g5['title'] = '예약 설치/업그레이드';
include_once(G5_ADMIN_PATH.'/admin.head.php');
?>

<div class="local_desc01 local_desc">
    <p>예약 모듈의 테이블을 확인하고 없는 것을 만듭니다. 모듈 파일을 업데이트한 뒤 한 번 실행하십시오. 여러 번 실행해도 기존 자료는 그대로입니다.</p>
</div>

<?php if ($ran) { ?>
<div class="local_desc01 local_desc">
    <?php if ($created) { ?>
    <p><strong>이번 실행에서 만든 테이블 <?php echo count($created); ?>개</strong> — <?php echo implode(', ', array_map('get_text', $created)); ?></p>
    <?php } else { ?>
    <p><strong>이미 최신입니다.</strong> 새로 만들거나 바꾼 항목이 없습니다.</p>
    <?php } ?>
</div>
<?php } ?>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption>예약 모듈 테이블 상태</caption>
        <thead>
        <tr><th scope="col">번호</th><th scope="col">테이블명</th><th scope="col">상태</th></tr>
        </thead>
        <tbody>
        <?php foreach ($tables as $i => $t) { ?>
        <tr>
            <td><?php echo $i + 1; ?></td>
            <td><?php echo get_text($t['name']); ?></td>
            <td><?php echo $t['exists'] ? '✓ 있음' : '✗ 없음'; ?></td>
        </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<form name="fbookingupgrade" action="./upgrade.php" method="post">
<input type="hidden" name="token" value="">
<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="설치/업그레이드 실행" class="btn_submit btn">
</div>
</form>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
