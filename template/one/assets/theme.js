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
