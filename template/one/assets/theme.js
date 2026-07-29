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
