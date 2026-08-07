<?php

# KCAPTCHA configuration file

$alphabet = "0123456789abcdefghijklmnopqrstuvwxyz"; # do not change without changing font files!

# symbols used to draw CAPTCHA
$allowed_symbols = "0123456789"; #digits
//$allowed_symbols = "0123456789abcdef"; #digits // 스캔 방지를 위하여 abcdef 추가 151029 15:00
//$allowed_symbols = "abcdeghkmnpqsuvxyz"; #digits
//$allowed_symbols = "23456789abcdeghkmnpqsuvxyz"; #alphabet without similar symbols (o=0, 1=l, i=j, t=f)

# folder with fonts
$fontsdir = 'fonts';

# CAPTCHA string length
# 길이가 고정이면 푸는 쪽이 칸 수를 세지 않아도 되니 랜덤이 유리해 보인다.
# 260807 측정에서 사람의 첫 시도 적중률이 고정은 10장 중 9장, 랜덤은 14장 중 10장이었다.
# 틀린 이유는 늘 글자 수를 잘못 센 것이다. 사람이 무는 값에 비해 얻는 게 적어 고정으로 둔다.
# 랜덤으로 바꾸려면 아래 두 줄을 맞바꾸면 된다. 그리는 쪽이 세션의 정답 길이를 따르도록
# kcaptcha.lib.php 를 이미 고쳐 두었으므로 길이가 어긋나는 문제는 없다.
//$length = mt_rand(5,6); # random 5 or 6
$length = 6;

# CAPTCHA image size (you do not need to change it, whis parameters is optimal)
$width = 160;
$height = 60;

# symbol's vertical fluctuation amplitude divided by 2
//$fluctuation_amplitude = 5;
//$fluctuation_amplitude = 11; // 파동&진폭 151028 14:00
$fluctuation_amplitude = 5; // 파동&진폭 원래대로 151029 15:00

#noise
//$white_noise_density=0; // no white noise
$white_noise_density=1/6;
//$black_noise_density=0; // no black noise
$black_noise_density=1/20;

# increase safety by prevention of spaces between symbols
# true 로 하면 글자를 붙여 한 글자씩 잘라내지 못하게 막는다. OCR 방어 효과는 가장 크다.
# 다만 260807 측정에서 사람의 첫 시도 적중률이 15장 중 8장(53%)까지 떨어졌다.
# 세로 잘림을 고친 뒤 다시 재도 6장 중 3장이었다. 틀린 경우가 전부 같은 숫자가
# 붙어 뭉친 것이라(555065, 122805) 잘림과는 무관한 겹치기 자체의 값이다.
# 방문자 절반이 틀리므로 켜지 않는다.
$no_spaces = false;

# show credits
$show_credits = false; # set to false to remove credits line. Credits adds 12 pixels to image height
$credits = 'www.captcha.ru'; # if empty, HTTP_HOST will be shown

# CAPTCHA image colors (RGB, 0-255)
//$foreground_color = array(0, 0, 0);
//$background_color = array(255, 255, 255);
# 검정 글자 흰 배경으로 고정되어 있으면 밝기 한 번만 잘라도 글자가 깨끗이 분리된다.
# 매번 다른 색으로 뽑아 그 한 번을 못 쓰게 한다.
# 글자는 어둡게 배경은 밝게 묶어 두어 사람 눈에는 그대로 보인다 — 260807 측정에서
# 첫 시도 적중률이 고정 흑백과 같았다. 폭을 0~80 / 215~255 로 잡아 최저 대비를 확보한다.
$foreground_color = array(mt_rand(0,80), mt_rand(0,80), mt_rand(0,80));
$background_color = array(mt_rand(215,255), mt_rand(215,255), mt_rand(215,255));

# JPEG quality of CAPTCHA image (bigger is better quality, but larger file size)
$jpeg_quality = 90;

$wave = true;