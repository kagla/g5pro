<?php
if (!defined('_GNUBOARD_')) exit;

$menu['menu950'] = array(
    array('950000', '예약관리', G5_ADMIN_URL.'/booking/booking_list.php', 'booking'),
    array('950100', '예약목록', G5_ADMIN_URL.'/booking/booking_list.php', 'booking_list'),
    array('950200', '객실관리', G5_ADMIN_URL.'/booking/room_list.php', 'booking_room'),
    array('950300', '요금·재고 캘린더', G5_ADMIN_URL.'/booking/calendar.php', 'booking_calendar'),
    array('950400', '부가상품관리', G5_ADMIN_URL.'/booking/addon_list.php', 'booking_addon'),
    array('950500', '결제대사', G5_ADMIN_URL.'/booking/recon.php', 'booking_recon'),
    array('950600', '환경설정', G5_ADMIN_URL.'/booking/config_form.php', 'booking_config'),
    array('950700', '설치/업그레이드', G5_ADMIN_URL.'/booking/upgrade.php', 'booking_upgrade'),
);
