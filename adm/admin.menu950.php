<?php
if (!defined('_GNUBOARD_')) exit;

// 5번째 원소 'super' — adm/booking/* 는 _common.php 에서 최고관리자만 통과시킨다.
// 이 표시가 없으면 권한관리 화면에 "부여 가능한 메뉴"로 뜨는데, 부여해도 실제로는 못 들어간다
// (adm/admin.lib.php:668 admin_menu_is_super_only). 그룹 헤더 950000 은 끝이 '000' 이라
// admin_get_assignable_auth_menu() 가 애초에 건너뛰므로 표시 대상이 아니다.
$menu['menu950'] = array(
    array('950000', '예약관리', G5_ADMIN_URL.'/booking/booking_list.php', 'booking'),
    array('950100', '예약목록', G5_ADMIN_URL.'/booking/booking_list.php', 'booking_list', 'super'),
    array('950200', '객실관리', G5_ADMIN_URL.'/booking/room_list.php', 'booking_room', 'super'),
    array('950300', '요금·재고 캘린더', G5_ADMIN_URL.'/booking/calendar.php', 'booking_calendar', 'super'),
    array('950400', '부가상품관리', G5_ADMIN_URL.'/booking/addon_list.php', 'booking_addon', 'super'),
    array('950500', '결제대사', G5_ADMIN_URL.'/booking/recon.php', 'booking_recon', 'super'),
    array('950600', '환경설정', G5_ADMIN_URL.'/booking/config_form.php', 'booking_config', 'super'),
    array('950700', '설치/업그레이드', G5_ADMIN_URL.'/booking/upgrade.php', 'booking_upgrade', 'super'),
);
