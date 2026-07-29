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

    // 펼친 상태로 되돌려 폭을 재고, 넘치면 다시 접는다.
    // 클래스 변경-측정-복원이 한 작업 안에서 끝나므로 화면 깜빡임은 없다.
    function updateMode() {
        var wasCollapsed = header.classList.contains('nav-collapsed');
        if (wasCollapsed) { setOpen(false); header.classList.remove('nav-collapsed'); }

        // 여유 2px — 반올림 오차로 접혔다 펴졌다 하는 것을 막는다
        var overflows = gnb.scrollWidth > gnb.clientWidth + 2;

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
