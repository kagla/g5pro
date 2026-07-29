// 모바일 햄버거 메뉴
(function () {
    var btn = document.querySelector('.nav-toggle');
    var gnb = document.getElementById('gnb');
    if (!btn || !gnb) return;

    function setOpen(open) {
        gnb.classList.toggle('open', open);
        btn.setAttribute('aria-expanded', String(open));
        btn.querySelector('.sound_only').textContent = open ? '메뉴 닫기' : '메뉴 열기';
    }

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
    // 데스크탑 폭으로 넓어지면 열림 상태를 정리한다
    window.matchMedia('(min-width: 621px)').addEventListener('change', function (m) {
        if (m.matches) setOpen(false);
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
