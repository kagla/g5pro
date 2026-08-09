// 햄버거 메뉴 — 고정 브레이크포인트가 아니라 "메뉴가 실제로 잘리는 순간" 접는다.
// 메뉴 개수·글자 길이·폰트가 달라져도 알아서 맞는다.
(function () {
    var header = document.querySelector('.site-header');
    var btn = document.querySelector('.nav-toggle');
    var gnb = document.getElementById('gnb');
    if (!header || !btn || !gnb) return;

    function setOpen(open) {
        gnb.classList.toggle('open', open);
        btn.setAttribute('aria-expanded', String(open));
        btn.querySelector('.sound_only').textContent = open ? '메뉴 닫기' : '메뉴 열기';
    }

    // 메뉴가 자리에 다 들어가는지 잰다. .gnb 에 overflow 를 줄 수 없으므로
    // (드롭다운이 잘린다) scrollWidth 대신 자식 폭을 직접 더한다.
    function contentWidth(el) {
        var cs = getComputedStyle(el);
        var gap = parseFloat(cs.columnGap || cs.gap) || 0;
        var w = 0, n = 0;
        for (var i = 0; i < el.children.length; i++) {
            var kid = el.children[i];
            if (getComputedStyle(kid).display === 'none') continue;  // .gnb-util 은 펼침 모드에서 숨김
            w += kid.getBoundingClientRect().width;
            n++;
        }
        return n > 1 ? w + gap * (n - 1) : w;
    }

    // 펼친 상태로 되돌려 폭을 재고, 넘치면 다시 접는다.
    // 클래스 변경-측정-복원이 한 작업 안에서 끝나므로 화면 깜빡임은 없다.
    function updateMode() {
        if (header.classList.contains('nav-collapsed')) {
            setOpen(false);
            header.classList.remove('nav-collapsed');
        }

        // 여유 2px — 반올림 오차로 접혔다 펴졌다 하는 것을 막는다
        var overflows = contentWidth(gnb) > gnb.clientWidth + 2;

        header.classList.toggle('nav-collapsed', overflows);
        if (!overflows) setOpen(false);
    }

    updateMode();
    if (window.ResizeObserver) {
        var pending = false;
        new ResizeObserver(function () {
            if (pending) return;
            pending = true;
            requestAnimationFrame(function () { pending = false; updateMode(); });
        }).observe(header);
    } else {
        window.addEventListener('resize', updateMode);
    }
    // 웹폰트가 늦게 적용되면 글자 폭이 달라진다
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(updateMode);

    btn.addEventListener('click', function () {
        setOpen(btn.getAttribute('aria-expanded') !== 'true');
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && btn.getAttribute('aria-expanded') === 'true') {
            setOpen(false); btn.focus();
        }
    });
    document.addEventListener('click', function (e) {
        if (btn.getAttribute('aria-expanded') !== 'true') return;
        if (!gnb.contains(e.target) && !btn.contains(e.target)) setOpen(false);
    });
})();

// 검색 모달 — 트리거는 헤더 아이콘과 햄버거 패널 버튼 둘 다
(function () {
    var triggers = document.querySelectorAll('.search-open');
    var modal = document.getElementById('search-modal');
    if (!triggers.length || !modal) return;
    var input = modal.querySelector('input[name=stx]');
    var opener = null;   // 닫을 때 포커스를 돌려줄 대상

    function setOpen(on) {
        modal.hidden = !on;
        document.body.style.overflow = on ? 'hidden' : '';
        if (on) { if (input) input.focus(); }
        else if (opener) opener.focus();
    }

    Array.prototype.forEach.call(triggers, function (btn) {
        btn.addEventListener('click', function () {
            opener = btn;
            // 햄버거 패널에서 열었다면 패널은 닫는다
            var gnb = document.getElementById('gnb');
            var navBtn = document.querySelector('.nav-toggle');
            if (gnb && gnb.contains(btn) && navBtn) navBtn.click();
            setOpen(true);
        });
    });
    modal.addEventListener('click', function (e) {
        if (e.target.closest('[data-close]')) setOpen(false);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) setOpen(false);
    });
})();

// 이미지 크게보기 — 순정은 a.view_image 를 target="_blank" 로 새 탭에 띄우는데,
// 그 탭에 실리는 view_image.php 는 팝업 창을 전제로 짜여 있다. 창 크기 조절도
// 더블클릭으로 닫기도 브라우저가 막아서 사용자가 새 탭에 갇힌다.
// 같은 자리에서 열고 Esc·배경·닫기 버튼 어느 것으로도 닫히게 한다.
// 순정이 붙여 둔 클래스만 가로채므로 마크업은 건드리지 않는다 —
// 첨부 이미지(view_file_link)와 본문 안 이미지(get_view_thumbnail) 모두 걸린다.
// 확대·축소가 필요한 이유: 게시판 '이미지 폭' 설정보다 작은 그림은 순정이 줄이지
// 않아 이미 원본 크기로 보인다. 그런 그림은 띄우기만 해서는 하나도 커지지 않는다.
(function () {
    var box = null, imgEl = null, pctEl = null, opener = null;
    var scale = 1, tx = 0, ty = 0;          // scale 1 = 화면에 맞춘 상태
    var drag = null, swallowClick = false, firstLoad = true;
    var MIN = 0.1, MAX = 8;                 // 원본 대비 배율 한계

    function build() {
        box = document.createElement('div');
        box.className = 'lightbox';
        box.hidden = true;
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.setAttribute('aria-label', '이미지 크게보기');
        box.innerHTML =
            '<div class="lightbox-backdrop" data-close></div>' +
            '<figure class="lightbox-body"><img alt=""></figure>' +
            '<div class="lightbox-tools">' +
              '<button type="button" data-zoom="out" aria-label="작게 보기">' +
                '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2M8.5 11h5"/></svg>' +
              '</button>' +
              '<span class="lightbox-pct" aria-live="polite">100%</span>' +
              '<button type="button" data-zoom="in" aria-label="크게 보기">' +
                '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2M8.5 11h5M11 8.5v5"/></svg>' +
              '</button>' +
              '<button type="button" data-zoom="fit" aria-label="화면에 맞추기">' +
                '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4H4v5M15 4h5v5M9 20H4v-5M15 20h5v-5"/></svg>' +
              '</button>' +
            '</div>' +
            '<button type="button" class="lightbox-close" data-close aria-label="닫기">&times;</button>';
        document.body.appendChild(box);
        imgEl = box.querySelector('img');
        pctEl = box.querySelector('.lightbox-pct');

        // 처음 실릴 때만 배율을 되돌린다. 원본으로 바뀔 때 되돌리면 보던 자리를 잃는다
        imgEl.addEventListener('load', function () {
            if (firstLoad) { firstLoad = false; reset(); }
            else apply();   // 원본이 실렸다 — 원본 대비 배율 표시를 다시 계산한다
        });

        // 브라우저 기본 이미지 드래그앤드롭을 끈다. 켜져 있으면 누르고 끄는 순간
        // "그림을 다른 곳으로 끌어다 놓기"가 시작되면서 포인터를 가로채, 그림이
        // 따라오지 않는다. draggable 과 dragstart 를 둘 다 막아야 확실하다.
        imgEl.draggable = false;
        imgEl.addEventListener('dragstart', function (e) { e.preventDefault(); });

        box.addEventListener('click', function (e) {
            // 방금 끌었으면 그때 뒤따르는 click 은 삼킨다 — 안 그러면 끌다 놓는
            // 순간 닫힌다 (누른 곳과 뗀 곳이 다르면 click 은 공통 조상에서 난다)
            if (swallowClick) { swallowClick = false; return; }

            var z = e.target.closest('[data-zoom]');
            if (z) {
                var how = z.getAttribute('data-zoom');
                if (how === 'in') zoom(1.4);
                else if (how === 'out') zoom(1 / 1.4);
                else reset();
                return;
            }
            if (e.target.closest('[data-close]')) { close(); return; }
            if (e.target.closest('.lightbox-tools')) return;
            // 그림 위가 아니면 배경으로 보고 닫는다
            if (e.target !== imgEl) close();
        });

        // 그림을 두 번 누르면 화면맞춤 ↔ 2배를 오간다
        imgEl.addEventListener('dblclick', function () {
            if (scale === 1) zoom(2); else reset();
        });

        // 휠로도 조절한다 (뒤 문서가 스크롤되지 않게 막는다)
        box.addEventListener('wheel', function (e) {
            e.preventDefault();
            zoom(e.deltaY < 0 ? 1.15 : 1 / 1.15);
        }, { passive: false });

        // 화면보다 커졌을 때 끌어서 옮긴다.
        // 그림이 아니라 상자에서 받는다 — 확대하면 그림이 화면 밖으로 나가서,
        // 손이 그림 밖으로 벗어나도 계속 끌 수 있어야 한다.
        box.addEventListener('pointerdown', function (e) {
            if (scale <= 1) return;
            if (e.target.closest('.lightbox-tools, .lightbox-close')) return;
            drag = { x: e.clientX - tx, y: e.clientY - ty, ox: e.clientX, oy: e.clientY };
            box.setPointerCapture(e.pointerId);
            e.preventDefault();
        });
        box.addEventListener('pointermove', function (e) {
            if (!drag) return;
            tx = e.clientX - drag.x;
            ty = e.clientY - drag.y;
            // 손떨림은 클릭으로 남겨 둔다 — 4px 넘게 움직였을 때만 끌기로 본다
            if (Math.abs(e.clientX - drag.ox) + Math.abs(e.clientY - drag.oy) > 4) swallowClick = true;
            apply();
        });
        function endDrag(e) {
            drag = null;
            if (box.hasPointerCapture && box.hasPointerCapture(e.pointerId)) box.releasePointerCapture(e.pointerId);
        }
        box.addEventListener('pointerup', endDrag);
        box.addEventListener('pointercancel', endDrag);

        window.addEventListener('resize', function () { if (box && !box.hidden) apply(); });
    }

    // 표시하는 값은 '원본 대비' 배율이다. scale 은 화면맞춤 상태를 1 로 본 값이므로
    // 화면맞춤 자체가 이미 줄어든 상태(큰 그림)면 100% 보다 작게 나온다.
    function pct() {
        var fitted = imgEl.clientWidth || 0;
        var natural = imgEl.naturalWidth || 0;
        if (!fitted || !natural) return Math.round(scale * 100);
        return Math.round((fitted / natural) * scale * 100);
    }

    function apply() {
        imgEl.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + scale + ')';
        imgEl.classList.toggle('is-zoomed', scale > 1);
        // 끌 수 있는 자리가 그림 밖까지이므로 손 모양도 상자 전체에 준다
        box.classList.toggle('is-zoomed', scale > 1);
        if (!pctEl) return;
        var p = pct();
        pctEl.textContent = p + '%';
        // 100% 를 넘기면 원본에 없는 화소를 늘리는 것이라 흐려진다. 왜 흐린지
        // 알 수 있게 표시를 바꾸고, 원본이 몇 픽셀인지 알려 준다.
        pctEl.classList.toggle('over', p > 100);
        pctEl.title = imgEl.naturalWidth
            ? '원본 ' + imgEl.naturalWidth + '×' + imgEl.naturalHeight + 'px'
              + (p > 100 ? ' — 원본보다 크게 늘려 보고 있어 흐려집니다' : '')
            : '';
    }

    function reset() { scale = 1; tx = 0; ty = 0; apply(); }

    function zoom(by) {
        var fitted = imgEl.clientWidth || 1, natural = imgEl.naturalWidth || 1;
        var base = fitted / natural;            // 화면맞춤 상태의 원본 대비 배율
        var next = scale * by;
        if (base * next > MAX) next = MAX / base;
        if (base * next < MIN) next = MIN / base;
        scale = next;
        if (scale <= 1) { tx = 0; ty = 0; }     // 화면 안에 들어오면 위치를 되돌린다
        apply();
    }

    // 화면의 img 는 원본이 아닐 수 있다. get_view_thumbnail() 이 게시판 '이미지 폭'
    // 보다 큰 그림을 썸네일로 바꿔치기하기 때문이다 (4666px 짜리가 835px 로 줄기도 한다).
    // 원본 경로는 링크에만 남으므로 거기서 되찾는다 — bbs/view_image.php 와 같은 규칙이다.
    //   fn 이 / 로 시작   → 사이트 기준 경로 (편집기·1:1문의 본문 이미지)
    //   bo_table 이 있으면 → data/file/<게시판>/<파일명>  (게시판 첨부)
    function originalSrc(a, img) {
        var href = a.getAttribute('href') || '';
        var fnm = href.match(/[?&]fn=([^&]*)/);
        if (fnm) {
            var fn = decodeURIComponent(fnm[1].replace(/\+/g, ' '));
            if (fn.charAt(0) === '/') return (window.g5_url || '') + fn;
            var bt = href.match(/[?&]bo_table=([^&]*)/);
            if (bt && window.g5_data_url) {
                return window.g5_data_url + '/file/' + decodeURIComponent(bt[1]) + '/' + fn;
            }
        }
        return img.getAttribute('src');
    }

    // 원본은 수 MB 일 수 있다. 이미 받아 둔 화면의 그림을 먼저 띄워 즉시 보이게 하고,
    // 원본이 다 오면 조용히 바꿔치기한다. 원본을 못 받으면 있던 그림이 그대로 남는다.
    function open(shownSrc, origSrc, alt) {
        if (!box) build();
        firstLoad = true;
        reset();
        imgEl.alt = alt || '';
        imgEl.src = shownSrc;
        box.hidden = false;
        document.body.style.overflow = 'hidden';
        box.querySelector('.lightbox-close').focus();

        if (!origSrc || origSrc === shownSrc) return;
        box.classList.add('is-loading');
        var pre = new Image();
        pre.onload = function () {
            box.classList.remove('is-loading');
            if (box.hidden) return;          // 그새 닫혔으면 버린다
            imgEl.src = origSrc;
        };
        pre.onerror = function () { box.classList.remove('is-loading'); };
        pre.src = origSrc;
    }

    function close() {
        if (!box || box.hidden) return;
        box.hidden = true;
        imgEl.removeAttribute('src');   // 큰 그림을 물고 있지 않게 한다
        document.body.style.overflow = '';
        if (opener) { opener.focus(); opener = null; }
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a.view_image') : null;
        if (!a) return;
        var img = a.querySelector('img');
        if (!img) return;   // 그림이 없으면 순정 동작(새 탭)에 맡긴다
        e.preventDefault();
        opener = a;
        open(img.getAttribute("src"), originalSrc(a, img), img.getAttribute("alt"));
    });

    document.addEventListener('keydown', function (e) {
        if (!box || box.hidden) return;
        if (e.key === 'Escape') close();
        else if (e.key === '+' || e.key === '=') zoom(1.4);
        else if (e.key === '-') zoom(1 / 1.4);
        else if (e.key === '0') reset();
    });
})();

// 프로필 드롭다운
(function () {
    var wrap = document.getElementById('profile');
    if (!wrap) return;
    var btn = wrap.querySelector('.profile-btn');
    var menu = document.getElementById('profile-menu');
    if (!btn || !menu) return;

    function setOpen(on) {
        menu.classList.toggle('open', on);
        btn.setAttribute('aria-expanded', String(on));
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        setOpen(btn.getAttribute('aria-expanded') !== 'true');
    });
    document.addEventListener('click', function (e) {
        if (btn.getAttribute('aria-expanded') === 'true' && !wrap.contains(e.target)) setOpen(false);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && btn.getAttribute('aria-expanded') === 'true') { setOpen(false); btn.focus(); }
    });
})();

// 점 세 개(kebab) 메뉴 — 목록 관리 도구·읽기 화면 관리 메뉴가 함께 쓰는 공용 열고닫기
(function () {
    function setOpen(k, on) {
        k.classList.toggle('open', on);
        var b = k.querySelector('.kebab-btn');
        if (b) b.setAttribute('aria-expanded', String(on));
    }
    function closeAll(except) {
        [].forEach.call(document.querySelectorAll('.kebab.open'), function (k) {
            if (k !== except) setOpen(k, false);
        });
    }
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.kebab-btn');
        if (btn) {
            var k = btn.closest('.kebab');
            closeAll(k);
            setOpen(k, !k.classList.contains('open'));
            return;
        }
        closeAll(null);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAll(null);
    });
})();

// 게시판 목록 관리 도구 — 전체 선택 + 점 세 개 메뉴(선택이동·복사·삭제)
// 순정 board_list_update.php 가 btn_submit 값으로 갈라지고, 복사·이동은 move.php 팝업으로 넘긴다.
(function () {
    var f = document.getElementById('fboardlist');
    if (!f) return;

    function items() {
        return [].slice.call(f.querySelectorAll('input[name="chk_wr_id[]"]'));
    }
    // 표(넓은 화면)와 카드(좁은 화면) 레이아웃이 같은 글을 각각 그린다.
    // 지금 보이는 쪽만 다뤄야 같은 wr_id 가 두 번 전송되지 않는다.
    function visible() {
        return items().filter(function (c) { return c.offsetParent !== null; });
    }

    var alls = [].slice.call(f.querySelectorAll('.chk-all'));
    var count = f.querySelector('.chk-count');
    var tools = f.querySelector('.list-tools');

    function sync() {
        var v = visible();
        var n = v.filter(function (c) { return c.checked; }).length;
        alls.forEach(function (a) {
            a.checked = (n > 0 && n === v.length);
            a.indeterminate = (n > 0 && n < v.length);
        });
        if (count) count.textContent = n ? n + '개 선택' : '';
        // 넓은 화면에선 선택이 있을 때만 도구 줄을 보인다 (좁은 화면은 CSS 가 항상 표시)
        if (tools) tools.classList.toggle('active', n > 0);
    }

    alls.forEach(function (a) {
        a.addEventListener('change', function () {
            visible().forEach(function (c) { c.checked = a.checked; });
            sync();
        });
    });
    f.addEventListener('change', function (e) {
        if (e.target.name === 'chk_wr_id[]') sync();
    });
    // 창 크기가 바뀌면 보이는 레이아웃이 달라진다
    window.addEventListener('resize', sync);
    sync();

    var pressed = '';
    f.addEventListener('click', function (e) {
        var b = e.target.closest ? e.target.closest('button[name="btn_submit"]') : null;
        if (b) pressed = b.value;
    });

    f.addEventListener('submit', function (e) {
        function restore() { items().forEach(function (c) { c.disabled = false; }); }

        if (!visible().filter(function (c) { return c.checked; }).length) {
            alert(pressed + '할 게시물을 하나 이상 선택하세요.');
            e.preventDefault();
            return;
        }
        // 안 보이는 레이아웃의 체크는 전송에서 뺀다
        items().forEach(function (c) { c.disabled = (c.offsetParent === null); });

        if (pressed === '선택삭제') {
            if (!confirm('선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다.\n답변글이 있다면 답변글도 함께 선택해야 삭제됩니다.')) {
                e.preventDefault();
                restore();
                return;
            }
            f.removeAttribute('target');
            f.action = f.dataset.deleteAction;
        } else {
            // 복사·이동은 대상 게시판을 새 창에서 고른다 (순정 move.php 와 같은 계약)
            f.sw.value = (pressed === '선택복사') ? 'copy' : 'move';
            window.open('', 'g5move', 'left=60,top=60,width=560,height=640,scrollbars=1');
            f.target = 'g5move';
            f.action = f.dataset.moveAction;
        }
        // 제출 직렬화가 끝난 뒤 되돌린다 (팝업 제출이면 이 화면은 그대로 남는다)
        setTimeout(restore, 0);
    });
})();

// 라이트/다크 토글 + 레이어팝업 닫기
(function () {
    var btn = document.getElementById('theme-toggle');
    if (btn) {
        btn.addEventListener('click', function () {
            var next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = next;
            try { localStorage.setItem('g5-theme', next); } catch (e) {}
        });
    }
    document.querySelectorAll('.pop-close').forEach(function (el) {
        el.addEventListener('click', function () {
            var pop = document.getElementById('pop-' + el.dataset.id);
            var chk = pop.querySelector('.pop-disable');
            if (chk && chk.checked) {
                var d = new Date();
                d.setTime(d.getTime() + parseInt(chk.dataset.hours, 10) * 3600 * 1000);
                document.cookie = 'hd_pops_' + el.dataset.id + '=1; expires=' + d.toUTCString() + '; path=/';
            }
            pop.remove();
        });
    });
})();

// 글쓴이 사이드뷰 — 목록 카드·표 안에 갇히는 것을 푼다.
//
// 두 가지가 겹쳐 있다.
//  1) 목록 컨테이너가 모서리를 둥글게 자르려고 overflow:hidden 을 쓴다
//     (.list-panel · .list-simple · .gallery-card). 표 목록은 좁은 화면 가로 스크롤 때문에
//     .list-table-wrap 에 overflow-x:auto 가 필요해 더 확실히 잘린다.
//  2) 카드에 hover transform 이 있다 (.list-card>li:hover · .gallery-card:hover).
//     transform 이 걸린 요소는 position:fixed 의 기준점이 되므로, 좌표만 화면 기준으로
//     바꿔서는 마우스를 올린 동안 엉뚱한 자리에 뜨거나 그대로 잘린다.
//
// 그래서 좌표를 고치는 대신 요소를 body 로 꺼낸다. 조상이 무엇을 자르든, 어떤 transform 을
// 걸든 영향을 받지 않는다. 순정이 .sv 를 .sv_wrap 안에서 찾으므로(closest→find),
// 다음 클릭 전에 반드시 제자리로 돌려놓아야 한다 — 그래서 캡처 단계에서 먼저 되돌린다.
(function () {
    var GAP = 4;
    var moved = null;   // body 로 꺼내둔 .sv
    var home = null;    // { parent, next } 원래 자리

    function restore() {
        if (!moved) return;
        if (home.next && home.next.parentNode === home.parent) home.parent.insertBefore(moved, home.next);
        else home.parent.appendChild(moved);
        moved.removeAttribute('style');
        moved = null;
        home = null;
    }

    function place() {
        var sv = document.querySelector('.sv.sv_on');
        if (!sv) { restore(); return; }

        var wrap = sv.closest('.sv_wrap');
        if (!wrap) return;

        var at = wrap.getBoundingClientRect();   // 트리거는 움직이지 않으므로 먼저 잰다

        // 스타일을 먼저 주고 옮긴다. 순서를 바꾸면 body 흐름에 잠깐 얹힌 상태로 배치될 수 있고,
        // 그 한 번의 레이아웃이 문서 크기를 흔들어 스크롤바가 생겼다 사라진다.
        sv.style.position = 'fixed';
        sv.style.margin = '0';
        sv.style.top = '0';
        sv.style.left = '-9999px';   // 화면 밖에서 크기만 잰다 (왼쪽 넘침은 스크롤을 만들지 않는다)

        if (sv !== moved) {
            restore();
            home = { parent: sv.parentNode, next: sv.nextSibling };
            document.body.appendChild(sv);
            moved = sv;
        }

        var box = sv.getBoundingClientRect();
        var left = at.left;
        var top = at.bottom + GAP;

        // 화면 밖으로 나가면 안쪽으로 당기고, 아래가 좁으면 트리거 위쪽에 띄운다
        if (left + box.width > window.innerWidth - GAP) left = window.innerWidth - box.width - GAP;
        if (left < GAP) left = GAP;
        if (top + box.height > window.innerHeight - GAP && at.top - box.height - GAP > 0) {
            top = at.top - box.height - GAP;
        }

        sv.style.left = Math.round(left) + 'px';
        sv.style.top = Math.round(top) + 'px';
    }

    // 순정 핸들러(common.js)가 .sv 를 .sv_wrap 안에서 찾기 전에 제자리로 돌려놓는다.
    //
    // 다만 메뉴 안을 누른 것이라면 건드리지 않는다. 클릭이 전달되는 도중에 그 대상을
    // DOM 에서 옮기면 브라우저가 링크 이동을 취소해 버려, 눌러도 아무 일이 없는 것처럼 보인다.
    // 메뉴 안 클릭은 어차피 페이지를 떠나거나(이동) 새 창을 열므로 되돌릴 필요도 없고,
    // 남아 있어도 다음 트리거 클릭 때 이 핸들러가 정리한다.
    function restoreUnlessInside(e) {
        if (e.target && e.target.closest && e.target.closest('.sv')) return;
        restore();
    }

    document.addEventListener('click', restoreUnlessInside, true);
    document.addEventListener('focusin', restoreUnlessInside, true);

    // 순정이 .sv_on 을 붙인 뒤에 자리를 잡는다.
    // setTimeout 으로 미루면 그 사이에 한 프레임이 그려질 수 있고, 그동안 메뉴는 아직
    // 목록 안에 있으므로 컨테이너에 스크롤바가 번쩍인다. 순정 핸들러는 트리거 자신에게,
    // 이 핸들러는 document 에 달려 있어 같은 이벤트 안에서 반드시 뒤에 돈다 — 바로 옮겨도 된다.
    document.addEventListener('click', place, false);
    document.addEventListener('focusin', place, false);

    // 스크롤·크기변경 때 따라 움직이게 했더니 이벤트마다 강제 레이아웃이 일어나 화면이 끊겼다.
    // 캡처라 표의 가로 스크롤에도 걸렸다. 따라다니는 대신 닫는다 — 계산이 아예 없고,
    // 드롭다운이 스크롤 중에 닫히는 것은 흔한 동작이라 어색하지 않다.
    function close() {
        var sv = document.querySelector('.sv.sv_on');
        if (sv) sv.classList.remove('sv_on');
        restore();
    }

    window.addEventListener('scroll', close, true);
    window.addEventListener('resize', close);
})();

// 푸터 사업자정보 — 좁은 화면에서만 접는다.
// 마크업은 open 인 채로 나온다. 스크립트가 죽어도 정보는 펼쳐진 채 남아야 하기 때문이다
// (의무 노출이라 안 보이는 쪽으로 실패하면 안 된다). 접는 일은 여기서만 한다.
(function () {
    var fold = document.querySelector('.ft-fold');
    if (!fold || !window.matchMedia) return;

    var mq = window.matchMedia('(max-width: 620px)');

    // 폭이 기준을 넘나들 때만 손댄다. 그 사이에 이용자가 직접 여닫은 것은 그대로 둔다
    function sync(e) { fold.open = !e.matches; }

    sync(mq);
    if (mq.addEventListener) mq.addEventListener('change', sync);
    else if (mq.addListener) mq.addListener(sync);   // 사파리 13 이하
})();

// 비밀번호 눈 — .pw-wrap 안의 버튼이 그 칸의 가림을 껐다 켠다.
// 위임으로 걸어 나중에 어느 화면에 감싸개를 붙여도 그대로 동작한다.
// jQuery 는 레이아웃이 <head> 에서 먼저 싣지만, 없는 환경에서도 조용히 지나가게 확인한다.
(function () {
    if (!window.jQuery) return;
    var $ = window.jQuery;

    $(document).on('click', '.pw-eye', function () {
        var $btn = $(this), $inp = $btn.closest('.pw-wrap').find('input').first();
        if (!$inp.length) return;

        var el = $inp[0], show = ($inp.attr('type') === 'password');
        // type 을 바꾸면 브라우저가 커서를 끝으로 보낸다 — 있던 자리를 기억했다 돌려놓는다
        var s = null, e = null;
        try { s = el.selectionStart; e = el.selectionEnd; } catch (err) {}

        $inp.attr('type', show ? 'text' : 'password');
        $btn.toggleClass('is-on', show).attr({
            'aria-pressed': show ? 'true' : 'false',
            'aria-label': show ? '비밀번호 숨기기' : '비밀번호 표시',
            'title': show ? '비밀번호 숨기기' : '비밀번호 표시'
        });

        try { el.focus(); if (s !== null) el.setSelectionRange(s, e); } catch (err) {}
    });
})();

// ── 확인 대화상자 ────────────────────────────────────────────────
// 브라우저 confirm() 을 대신한다. 시스템 창은 CSS 가 안 먹어서 화면이 거기서 끊긴다.
//
// 쓰는 법 두 가지 —
//   g5Confirm('정말 지울까요?', function () { ... })            // 코드에서 부를 때
//   <form data-confirm="정말 지울까요?">                          // 화면에 적어 둘 때
//   <a href="…" data-confirm="…" data-confirm-danger>            // 되돌릴 수 없는 처리
//
// confirm() 과 달리 **기다려 주지 않는다**(값을 돌려주지 않는다). 눌렀을 때 할 일을
// 콜백으로 넘겨야 한다 — 시스템 창처럼 스크립트를 멈춰 세울 방법이 브라우저에 없다.
(function () {
    if (!window.jQuery) return;
    var $ = window.jQuery;

    window.g5Confirm = function (opts, onOk) {
        if (typeof opts === 'string') opts = { message: opts };
        opts = opts || {};

        var $prev = $(document.activeElement);   // 닫은 뒤 원래 있던 자리로 초점을 돌려준다
        var $dlg = $('<div class="g5-dlg" role="dialog" aria-modal="true"></div>');
        var $panel = $('<div class="g5-dlg-panel"></div>').appendTo($dlg);

        if (opts.title) $('<h2 class="g5-dlg-title"></h2>').text(opts.title).appendTo($panel);
        $('<p class="g5-dlg-body"></p>').text(opts.message || '').appendTo($panel);

        var $foot = $('<div class="g5-dlg-foot"></div>').appendTo($panel);
        var $cancel = $('<button type="button" class="btn"></button>')
            .text(opts.cancelText || '취소').appendTo($foot);
        var $ok = $('<button type="button" class="btn"></button>')
            .addClass(opts.danger ? 'btn-danger' : 'btn-primary')
            .text(opts.okText || '확인').appendTo($foot);

        function close() {
            $dlg.remove();
            $(document).off('keydown.g5dlg');
            try { $prev.trigger('focus'); } catch (e) {}
        }
        $cancel.on('click', close);
        $ok.on('click', function () { close(); if (typeof onOk === 'function') onOk(); });
        // 덮개를 눌러도 닫는다 — 확인이 아니라 취소다(실수로 진행되면 안 된다)
        $dlg.on('click', function (e) { if (e.target === $dlg[0]) close(); });
        $(document).on('keydown.g5dlg', function (e) {
            if (e.which === 27) { close(); return; }
            // Tab 을 두 버튼 안에 가둔다 — 뒤 화면으로 초점이 새면 어디 있는지 알 수 없다
            if (e.which === 9) {
                var a = $panel.find('button').toArray();
                var i = a.indexOf(document.activeElement);
                var n = e.shiftKey ? (i <= 0 ? a.length - 1 : i - 1) : (i === a.length - 1 ? 0 : i + 1);
                a[n].focus();
                e.preventDefault();
            }
        });

        $('body').append($dlg);
        $ok.trigger('focus');
    };

    // 화면에 적어 두는 방식 — 폼과 링크에 data-confirm 만 달면 된다.
    // 확인하면 같은 동작을 다시 일으키되, 이번에는 플래그를 보고 그냥 지나가게 한다.
    $(document).on('submit', 'form[data-confirm]', function (e) {
        var f = this;
        if (f.dataset.confirmed === '1') return;
        e.preventDefault();
        g5Confirm({ message: f.dataset.confirm, danger: f.hasAttribute('data-confirm-danger') },
            function () { f.dataset.confirmed = '1'; f.submit(); });
    });
    $(document).on('click', 'a[data-confirm]', function (e) {
        var a = this;
        e.preventDefault();
        g5Confirm({ message: a.dataset.confirm, danger: a.hasAttribute('data-confirm-danger') },
            function () { location.href = a.href; });
    });
})();
// ── 토스트 ────────────────────────────────────────────────────
// g5Toast('저장했습니다')                        — 화면 아래 가운데(일반 알림)
// g5Toast('재고가 9개뿐입니다', {anchor: btn})    — 누른 것 바로 위(그 자리에서 설명)
//
// alert() 은 스크립트를 멈추고 손을 쓰게 만든다. "알기만 하면 되는 말" 에는 과하다.
// 아래 가운데는 일반적인 알림 자리지만, 누른 곳이 화면 위쪽이면 눈이 거기 있어서 못 본다 —
// 무엇 때문에 나온 말인지 분명하면 그 곁에 붙인다.
//
// 규칙 둘:
//  · **붙인 쪽지는 한 번에 하나** — 여러 줄에서 눌러 여기저기 쪽지가 남으면 화면이 지저분해지고
//    서로 겹친다. 새로 띄울 때 앞의 것을 먼저 거둔다.
//  · **타이머는 제 쪽지만 닫는다** — 예전에는 "가장 최근 쪽지" 를 닫게 해 두어, 그 사이 다른
//    쪽지가 뜨면 먼저 것이 영영 안 사라졌다.
(function () {
    if (!window.jQuery) return;
    var $ = window.jQuery, $box = null, shown = {};   // key -> {$t, timer}

    function close($t, key) {
        if (key && shown[key]) { clearTimeout(shown[key].timer); delete shown[key]; }
        $t.addClass('is-out');
        setTimeout(function () { $t.remove(); }, 180);
    }

    function arm(key, $t, ms) {
        // 타이머는 제 쪽지($t)만 닫는다 — 전역 상태를 보지 않는다
        if (shown[key]) clearTimeout(shown[key].timer);
        shown[key] = { $t: $t, timer: setTimeout(function () { close($t, key); }, ms) };
    }

    window.g5Toast = function (message, opts) {
        if (typeof opts === 'number') opts = { ms: opts };
        opts = opts || {};
        var ms = opts.ms || 2600, anchor = opts.anchor ? $(opts.anchor) : null;
        var anchored = !!(anchor && anchor.length);
        var key = anchored ? 'anchored' : ('c:' + message);   // 붙인 쪽지는 자리를 하나만 쓴다

        // 같은 쪽지가 이미 떠 있으면 새로 만들지 않고 시간만 늘린다
        if (shown[key] && shown[key].$t.parent().length && shown[key].$t.data('msg') === message
            && (!anchored || shown[key].$t.data('anchor') === anchor[0])) {
            arm(key, shown[key].$t, ms);
            return;
        }
        // 자리를 넘겨받는다 — 붙인 쪽지는 한 번에 하나뿐이라 앞의 것을 거둔다
        if (shown[key]) close(shown[key].$t, key);

        var $t = $('<div class="g5-toast"></div>')
            .data('msg', message)
            .append($('<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8.2v4.4M12 15.6h.01"/></svg>'))
            .append($('<span></span>').text(message));

        if (anchored) {
            $t.addClass('is-anchored').attr('role', 'status').data('anchor', anchor[0]).appendTo('body');
            var r = anchor[0].getBoundingClientRect(), w = $t.outerWidth(), h = $t.outerHeight(),
                pad = 8, vw = document.documentElement.clientWidth;
            var left = Math.max(pad, Math.min(r.left + r.width / 2 - w / 2, vw - w - pad));
            var top = r.top - h - 10;
            if (top < pad) top = r.bottom + 10;                 // 위가 좁으면 아래로 뒤집는다
            $t.css({ left: Math.round(left) + 'px', top: Math.round(top) + 'px' });
            // 꼬리는 쪽지가 좌우로 밀렸어도 누른 것을 가리켜야 한다
            var tail = Math.max(12, Math.min(r.left + r.width / 2 - left, w - 12));
            $t.css('--tail', Math.round(tail) + 'px')
              .toggleClass('is-below', top > r.top);
        } else {
            if (!$box) $box = $('<div class="g5-toasts" role="status" aria-live="polite"></div>').appendTo('body');
            $t.appendTo($box);
        }

        $t.on('click', function () { close($t, key); });
        arm(key, $t, ms);
    };
})();

/* 수량칸(.cart-qty) — 장바구니와 상품 상세가 같은 규칙을 쓴다.
   재고에서 멈추고, 더 못 가는 버튼은 흐리게 칠하되 disabled 로 막지는 않는다
   (막으면 브라우저가 클릭을 안 넘겨줘 왜 안 되는지 말할 기회가 없다).
   화면마다 저장하는 방법은 다르므로(장바구니는 ajax, 상세는 폼) 여기엔 칠하기와
   말투만 둔다. data-max 가 0 이면 품절, 음수면 "아직 모름"(옵션을 안 골랐을 때). */
(function ($) {
    function num(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

    // .attr 이 아니라 .data 로 읽는다 — 장바구니는 저장 뒤 서버가 알려 준 재고를
    // $box.data('max', n) 으로 다시 심는데, 그건 속성을 안 고친다. 속성만 보면 옛 값이 남는다.
    window.g5QtyMax = function (box) {
        var v = parseInt($(box).data('max'), 10);
        return isNaN(v) ? -1 : v;
    };

    window.g5QtyLimitMsg = function (max) {
        if (max < 0) return '옵션을 먼저 고르세요';
        return max > 0 ? '재고가 ' + num(max) + '개뿐이라 더 담을 수 없습니다'
                       : '품절된 상품이라 더 담을 수 없습니다';
    };

    window.g5QtyPaint = function (box) {
        var $box = $(box), max = window.g5QtyMax($box),
            v = parseInt($box.find('.cart-qty-input').val(), 10) || 1;
        function mark($b, off) { $b.toggleClass('is-limit', off).attr('aria-disabled', off ? 'true' : 'false'); }
        mark($box.find('[data-d="-1"]'), v <= 1);
        mark($box.find('[data-d="1"]'), max >= 0 && v >= max);
    };

    // 누른 결과의 다음 값 — 한계면 이유를 그 자리에 띄우고 null 을 준다(부르는 쪽은 저장을 건너뛴다).
    window.g5QtyNext = function (btn, minusHint) {
        var $box = $(btn).closest('.cart-qty'),
            max = window.g5QtyMax($box),
            v = parseInt($box.find('.cart-qty-input').val(), 10) || 1,
            d = parseInt($(btn).attr('data-d'), 10);

        if (d > 0 && max >= 0 && v >= max) { g5Toast(window.g5QtyLimitMsg(max), { anchor: btn }); return null; }
        if (d < 0 && v <= 1) {
            // 뺄 자리가 있는 화면(장바구니·고른 옵션 줄)에서만 어떻게 빼는지 덧붙인다.
            // 구매 폼처럼 뺄 것이 없는 곳에는 '' 를 넘겨 한 마디로 끝낸다.
            g5Toast('수량은 1개부터입니다' + (minusHint ? '. ' + minusHint : ''), { anchor: btn });
            return null;
        }
        return v + d;
    };

    // 직접 쳐 넣은 값 자르기 — 한계까지 깎였으면 이유를 말한다
    window.g5QtyClamp = function (box) {
        var $box = $(box), $in = $box.find('.cart-qty-input'),
            max = window.g5QtyMax($box),
            v = Math.max(1, parseInt($in.val(), 10) || 1);
        if (max >= 0 && v > max) { v = Math.max(1, max); g5Toast(window.g5QtyLimitMsg(max), { anchor: $in }); }
        $in.val(v);
        window.g5QtyPaint($box);
        return v;
    };
})(jQuery);
