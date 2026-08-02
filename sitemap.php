<?php
/*
 * sitemap.php — 검색엔진에게 이 사이트의 주소를 알린다.
 *
 * robots.txt 의 Sitemap: 줄이 이 파일을 가리킨다. .xml 이 아닌 이유는, 이 저장소가
 * 루트 .htaccess 를 서버별로 관리해(.gitignore) 리라이트에 기대면 규칙이 저장소에
 * 담기지 않기 때문이다. 구글·빙 모두 사이트맵 경로를 가리지 않는다.
 * (편의를 위한 /sitemap.xml 별칭은 서버의 .htaccess 가 따로 얹는다)
 *
 * 담는 것 — 메인·게시판 목록·공개 게시글·내용관리·판매중 상품·FAQ·투표 결과
 * 빼는 것 — 비밀글, 1:1문의, 읽기권한이 비회원보다 높은 게시판, 접근제한 그룹
 *
 * priority 와 changefreq 는 넣지 않는다. 구글이 오래전부터 무시한다.
 */
include_once('./common.php');

define('G5_SITEMAP_PER_PAGE', 40000);   // 규격 상한은 50,000. 여유를 둔다

$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 0;
$urls  = array();

// ── 공개 게시판만 추린다.
//    비회원 등급은 1 이다(common.php). 읽기권한이 그보다 높으면 손님은 못 본다.
//    접근제한이 걸린 그룹도 뺀다.
$boards = array();
$sql = " select b.bo_table, b.bo_subject
           from {$g5['board_table']} b
           left join {$g5['group_table']} g on g.gr_id = b.gr_id
          where b.bo_use_search = 1
            and b.bo_read_level <= 1
            and ifnull(g.gr_use_access, 0) = 0
          order by b.bo_table ";
$res = sql_query($sql, false);
while ($res && ($row = sql_fetch_array($res))) $boards[] = $row;

// ── 고정 화면
$urls[] = array('loc' => G5_URL.'/');
$urls[] = array('loc' => G5_BBS_URL.'/faq.php');

foreach ($boards as $b) {
    $urls[] = array('loc' => G5_BBS_URL.'/board.php?bo_table='.$b['bo_table']);
}

// ── 내용관리
$res = sql_query(" select co_id from {$g5['content_table']} order by co_id ", false);
while ($res && ($row = sql_fetch_array($res))) {
    $urls[] = array('loc' => G5_BBS_URL.'/content.php?co_id='.$row['co_id']);
}

// ── 진행 중인 투표
$res = sql_query(" select po_id from {$g5['poll_table']} where po_use = 1 order by po_id ", false);
while ($res && ($row = sql_fetch_array($res))) {
    $urls[] = array('loc' => G5_BBS_URL.'/poll_result.php?po_id='.$row['po_id']);
}

// ── 게시글. 비밀글은 뺀다 — 순정도 302 로 비밀번호 화면에 보낸다
foreach ($boards as $b) {
    $table = $g5['write_prefix'].$b['bo_table'];
    $res = sql_query(" select wr_id, wr_last, wr_datetime
                         from `{$table}`
                        where wr_is_comment = 0
                          and wr_option not like '%secret%'
                        order by wr_id ", false);
    while ($res && ($row = sql_fetch_array($res))) {
        $urls[] = array(
            'loc'     => G5_BBS_URL.'/board.php?bo_table='.$b['bo_table'].'&wr_id='.$row['wr_id'],
            'lastmod' => $row['wr_last'] ? $row['wr_last'] : $row['wr_datetime'],
        );
    }
}

// ── 판매중 상품
if (defined('G5_USE_SHOP') && G5_USE_SHOP) {
    $res = sql_query(" select it_id, it_update_time
                         from {$g5['g5_shop_item_table']}
                        where it_use = 1
                        order by it_id ", false);
    while ($res && ($row = sql_fetch_array($res))) {
        $urls[] = array(
            'loc'     => G5_SHOP_URL.'/item.php?it_id='.$row['it_id'],
            'lastmod' => $row['it_update_time'],
        );
    }
}

$total = count($urls);
$pages = max(1, (int)ceil($total / G5_SITEMAP_PER_PAGE));

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";

// ── 한 장에 안 들어가면 인덱스를 먼저 내보낸다.
//    지금은 수십 개라 한 장으로 끝나지만, 커졌을 때 손대지 않아도 되게 해 둔다.
if ($pages > 1 && !$page) {
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
    for ($i = 1; $i <= $pages; $i++) {
        echo '  <sitemap><loc>'.g5_sitemap_esc(G5_URL.'/sitemap.php?page='.$i).'</loc></sitemap>'."\n";
    }
    echo '</sitemapindex>';
    exit;
}

$slice = $page ? array_slice($urls, ($page - 1) * G5_SITEMAP_PER_PAGE, G5_SITEMAP_PER_PAGE) : $urls;

echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
foreach ($slice as $u) {
    echo '  <url><loc>'.g5_sitemap_esc($u['loc']).'</loc>';
    if (!empty($u['lastmod'])) {
        $ts = strtotime($u['lastmod']);
        if ($ts) echo '<lastmod>'.date('c', $ts).'</lastmod>';
    }
    echo "</url>\n";
}
echo '</urlset>';

// 주소 안의 & 는 XML 에서 그대로 둘 수 없다. bo_table=…&wr_id=… 가 매번 걸린다
function g5_sitemap_esc($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
