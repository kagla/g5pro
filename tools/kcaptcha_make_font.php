<?php
/**
 * kcaptcha 폰트 스프라이트를 TTF 로 굽는다.
 *
 * plugin/kcaptcha/fonts/ 안의 파일은 폰트가 아니라 미리 구워 놓은 PNG 다.
 * 형식은 이렇다.
 *   - 0행은 글자 경계 표시줄이다. 글자마다 불투명 구간을 두어 시작·끝 x 를 알린다.
 *     칸과 칸 사이는 투명해야 하고, 마지막 글자 뒤에도 투명 여백이 있어야
 *     경계 인식이 닫힌다.
 *   - 1행부터가 글자 그림이다. 배경은 투명, 글자는 검정이다.
 *   - $alphabet 순서대로 36자(0~9, a~z)가 다 들어 있어야 한다.
 *   - kcaptcha 는 이미지 한 장에 폰트 하나를 무작위로 골라 쓴다.
 *     폰트를 더 넣을수록 한 벌만 보고 학습한 풀이기가 흔들린다.
 *
 * 기존 3종은 속이 빈 윤곽선 글자다. 꽉 찬 검정 덩어리는 윤곽선보다 알아보기
 * 쉬워서 그대로 구워 넣으면 폰트 수는 늘어도 평균 난도가 내려간다.
 * 그래서 기본을 윤곽선으로 둔다.
 *
 * 사용법:
 *   php tools/kcaptcha_make_font.php <ttf> <출력.png> [숫자높이=35] [outline|solid]
 */

$alphabet = '0123456789abcdefghijklmnopqrstuvwxyz';

$sprite_height = 70;  // 기존 3종과 같게 맞춘다
$digit_top     = 13;  // 숫자 잉크가 시작할 행. 기존 3종이 13~16 이다
$pad           = 1;   // 표시줄이 글자보다 좌우로 더 잡는 여유
$gap           = 10;  // 글자 칸 사이 투명 간격
$margin        = 6;   // 첫 글자 시작 x

$ttf    = $argv[1] ?? '';
$out    = $argv[2] ?? '';
$target = (int)($argv[3] ?? 35);
$style  = $argv[4] ?? 'outline';
$stroke = 2; // 윤곽선 두께. 기존 3종이 2px 쯤 된다

if (!$ttf || !$out) {
    fwrite(STDERR, "사용법: php tools/kcaptcha_make_font.php <ttf> <출력.png> [숫자높이]\n");
    exit(1);
}
if (!is_readable($ttf)) {
    fwrite(STDERR, "TTF 를 읽을 수 없다: $ttf\n");
    exit(1);
}
if (!function_exists('imagettftext')) {
    fwrite(STDERR, "GD 에 FreeType 이 없다.\n");
    exit(1);
}

/**
 * 글자 하나를 큰 임시 판에 그려 잉크가 놓인 자리를 돌려준다.
 * 기준선을 모든 글자에 똑같이 두어야 세로가 맞으므로 판을 공유한다.
 */
function render_glyph($ttf, $size, $char)
{
    $pad_box = 60;
    $im = imagecreatetruecolor(300, 300);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    imagefilledrectangle($im, 0, 0, 299, 299, imagecolorallocatealpha($im, 0, 0, 0, 127));
    imagealphablending($im, true);

    $black = imagecolorallocate($im, 0, 0, 0);
    imagettftext($im, $size, 0, $pad_box, 220, $black, $ttf, $char);

    $x0 = 300; $y0 = 300; $x1 = -1; $y1 = -1;
    for ($y = 0; $y < 300; $y++) {
        for ($x = 0; $x < 300; $x++) {
            if (((imagecolorat($im, $x, $y) >> 24) & 0x7f) < 127) {
                if ($x < $x0) $x0 = $x;
                if ($x > $x1) $x1 = $x;
                if ($y < $y0) $y0 = $y;
                if ($y > $y1) $y1 = $y;
            }
        }
    }
    if ($x1 < 0) return null; // 빈 글자
    return array('im' => $im, 'x0' => $x0, 'y0' => $y0, 'x1' => $x1, 'y1' => $y1);
}

/**
 * 꽉 찬 글자를 속 빈 윤곽선으로 깎는다.
 * 안쪽으로 $stroke 만큼 줄인 모양을 빼면 테두리만 남는다.
 * 바깥 가장자리의 알파는 그대로 두어 계단이 덜 보이게 한다.
 */
function outline_glyph($g, $stroke)
{
    $im = $g['im'];
    $ink = array();
    for ($y = $g['y0'] - $stroke; $y <= $g['y1'] + $stroke; $y++) {
        for ($x = $g['x0'] - $stroke; $x <= $g['x1'] + $stroke; $x++) {
            $inside = $x >= 0 && $y >= 0 && $x < 300 && $y < 300;
            $ink[$y][$x] = $inside && ((imagecolorat($im, $x, $y) >> 24) & 0x7f) < 64;
        }
    }

    // 알파 합성이 켜져 있으면 투명 픽셀을 덮어써도 아무 일이 안 일어난다
    imagealphablending($im, false);
    $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
    for ($y = $g['y0']; $y <= $g['y1']; $y++) {
        for ($x = $g['x0']; $x <= $g['x1']; $x++) {
            if (empty($ink[$y][$x])) continue;

            // 사방 $stroke 안이 모두 글자면 안쪽이다 — 지운다
            $eroded = true;
            for ($dy = -$stroke; $dy <= $stroke && $eroded; $dy++) {
                for ($dx = -$stroke; $dx <= $stroke; $dx++) {
                    if (empty($ink[$y + $dy][$x + $dx])) { $eroded = false; break; }
                }
            }
            if ($eroded) imagesetpixel($im, $x, $y, $transparent);
        }
    }
    return $g;
}

// 숫자 높이가 목표에 맞는 글자 크기를 찾는다
$size = 1;
for ($try = 8; $try <= 90; $try++) {
    $top = 300; $bot = -1;
    foreach (str_split('0123456789') as $d) {
        $g = render_glyph($ttf, $try, $d);
        if (!$g) continue;
        if ($g['y0'] < $top) $top = $g['y0'];
        if ($g['y1'] > $bot) $bot = $g['y1'];
        imagedestroy($g['im']);
    }
    if ($bot - $top + 1 > $target) break;
    $size = $try;
}

// 확정된 크기로 36자를 모두 그린다
$glyphs = array();
$digit_top_px = 300; $digit_bot_px = -1;
foreach (str_split($alphabet) as $ch) {
    $g = render_glyph($ttf, $size, $ch);
    if (!$g) {
        fwrite(STDERR, "글자를 그릴 수 없다: '$ch' — 이 TTF 에는 없는 글자다.\n");
        exit(1);
    }
    $glyphs[$ch] = $g;
    if (strpos('0123456789', $ch) !== false) {
        if ($g['y0'] < $digit_top_px) $digit_top_px = $g['y0'];
        if ($g['y1'] > $digit_bot_px) $digit_bot_px = $g['y1'];
    }
}

// 임시 판의 y 를 스프라이트의 y 로 옮기는 값. 숫자 윗줄이 $digit_top 에 오게 한다
$shift = $digit_top - $digit_top_px;

// 가로 배치를 먼저 계산해 스프라이트 폭을 정한다
$layout = array();
$x = $margin;
foreach (str_split($alphabet) as $ch) {
    $g = $glyphs[$ch];
    $w = $g['x1'] - $g['x0'] + 1;
    $layout[$ch] = array('mark_start' => $x, 'art_x' => $x + $pad, 'w' => $w);
    $x += $w + $pad * 2 + $gap;
}
$sprite_width = $x + $margin;

$sp = imagecreatetruecolor($sprite_width, $sprite_height);
imagealphablending($sp, false);
imagesavealpha($sp, true);
imagefilledrectangle($sp, 0, 0, $sprite_width - 1, $sprite_height - 1,
    imagecolorallocatealpha($sp, 0, 0, 0, 127));

$over = array();
foreach (str_split($alphabet) as $ch) {
    $g = $glyphs[$ch];
    if ($style === 'outline') $g = outline_glyph($g, $stroke);
    $l = $layout[$ch];

    // 0행 표시줄: 글자 폭 + 좌우 여유만큼 불투명하게 채운다
    for ($mx = $l['mark_start']; $mx < $l['mark_start'] + $l['w'] + $pad * 2; $mx++) {
        imagesetpixel($sp, $mx, 0, imagecolorallocatealpha($sp, 0, 0, 0, 0));
    }

    // 글자 그림을 옮겨 그린다. 알파를 그대로 살려야 하므로 픽셀 단위로 복사한다
    for ($sy = $g['y0']; $sy <= $g['y1']; $sy++) {
        $ty = $sy + $shift;
        if ($ty < 1 || $ty >= $sprite_height) {
            $over[$ch] = true;
            continue;
        }
        for ($sx = $g['x0']; $sx <= $g['x1']; $sx++) {
            $rgb = imagecolorat($g['im'], $sx, $sy);
            $a = ($rgb >> 24) & 0x7f;
            if ($a >= 127) continue;
            imagesetpixel($sp, $l['art_x'] + ($sx - $g['x0']), $ty,
                imagecolorallocatealpha($sp, 0, 0, 0, $a));
        }
    }
    imagedestroy($g['im']);
}

if (!is_dir(dirname($out))) mkdir(dirname($out), 0755, true);
imagepng($sp, $out);

printf("만들었다: %s\n", $out);
printf("  글자 크기 %dpt, 모양 %s, 스프라이트 %dx%d, 숫자 잉크 y=%d~%d\n",
    $size, $style, $sprite_width, $sprite_height, $digit_top, $digit_bot_px + $shift);
if ($over) {
    printf("  주의: 스프라이트 밖으로 나가 잘린 글자 — %s\n", implode(' ', array_keys($over)));
    printf("        숫자만 쓰면 상관없지만 \$allowed_symbols 에 글자를 넣을 거면 숫자높이를 줄여라.\n");
}
