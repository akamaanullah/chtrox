/**
 * DMs sidebar – filter conversations by name or preview text.
 */
(function () {
    var sidebarSessionAbort = null;

    function initDmSidebarSearch() {
        if (sidebarSessionAbort) {
            sidebarSessionAbort.abort();
        }
        sidebarSessionAbort = new AbortController();
        var signal = sidebarSessionAbort.signal;

        var input = document.getElementById('dmSidebarSearch');
        var list = document.querySelector('.dm-list');
        var emptyEl = document.getElementById('dmSidebarEmpty');
        if (!input || !list) return;

        var items = list.querySelectorAll('.dm-item');
        if (!items.length) return;

        function filterSidebar() {
            var q = (input.value || '').trim().toLowerCase();
            var visible = 0;

            items.forEach(function (item) {
                var nameEl = item.querySelector('.dm-info h5');
                var previewEl = item.querySelector('.dm-msg');
                var name = (nameEl ? nameEl.textContent : '').toLowerCase();
                var preview = (previewEl ? previewEl.textContent : '').toLowerCase();
                var match = !q || name.indexOf(q) !== -1 || preview.indexOf(q) !== -1;
                item.classList.toggle('dm-item--hidden', !match);
                if (match) visible++;
            });

            if (emptyEl) {
                emptyEl.hidden = visible > 0 || !q;
            }
        }

        input.addEventListener('input', filterSidebar, { signal: signal });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                input.value = '';
                filterSidebar();
                input.blur();
            }
        }, { signal: signal });
    }

    document.addEventListener('chatrox:page_load', function () {
        initDmSidebarSearch();
    });
})();
