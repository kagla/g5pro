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
// 순정은 .sv 를 position:absolute 로 띄운다. 그런데 목록 컨테이너가 모서리를 둥글게
// 자르려고 overflow:hidden 을 쓰고(.list-panel · .list-simple · .gallery-card),
// 표 목록은 좁은 화면에서 가로 스크롤을 하려고 .list-table-wrap 에 overflow-x:auto 가
// 필요하다. 어느 쪽이든 조상이 자르므로 CSS 로는 풀 수 없다. 카드 목록만 멀쩡했던 것은
// 그 컨테이너에 overflow 가 없기 때문이다.
//
// 그래서 열리는 순간 뷰포트 기준 fixed 로 바꿔 조상의 자르기에서 벗어나게 한다.
// 순정 JS(common.js)가 .sv_on 을 붙인 다음에 이 코드가 돈다 — theme.js 가 뒤에 실려서다.
(function () {
    var GAP = 4;

    function place() {
        var sv = document.querySelector('.sv.sv_on');
        if (!sv) return;

        var wrap = sv.closest('.sv_wrap');
        if (!wrap) return;

        // 먼저 fixed 로 바꿔야 실제 크기를 잴 수 있다 (조상이 자르고 있으면 폭이 줄어든다)
        sv.style.position = 'fixed';
        sv.style.margin = '0';
        sv.style.left = '0';
        sv.style.top = '0';

        var at = wrap.getBoundingClientRect();
        var box = sv.getBoundingClientRect();
        var left = at.left;
        var top = at.bottom + GAP;

        // 화면 밖으로 나가면 안쪽으로 당긴다. 아래가 좁으면 트리거 위쪽에 띄운다.
        if (left + box.width > window.innerWidth - GAP) left = window.innerWidth - box.width - GAP;
        if (left < GAP) left = GAP;
        if (top + box.height > window.innerHeight - GAP && at.top - box.height - GAP > 0) {
            top = at.top - box.height - GAP;
        }

        sv.style.left = Math.round(left) + 'px';
        sv.style.top = Math.round(top) + 'px';
    }

    // 순정 핸들러가 먼저 끝나도록 한 박자 늦춘다
    function later() { setTimeout(place, 0); }

    document.addEventListener('click', later, false);
    document.addEventListener('focusin', later, false);

    // 스크롤·크기변경 중에는 위치가 어긋나므로 따라 움직인다
    window.addEventListener('scroll', place, true);
    window.addEventListener('resize', place);
})();
