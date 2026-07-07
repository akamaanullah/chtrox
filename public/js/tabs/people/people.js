/**
 * People directory search — real-time client-side contact card filtering.
 */
(function (global) {
    'use strict';

    var sessionAbort = null;

    function initPeopleSearch() {
        if (sessionAbort) sessionAbort.abort();
        sessionAbort = new AbortController();
        var signal = sessionAbort.signal;

        var searchBox = document.getElementById('peopleSearch');
        if (!searchBox) return;

        var cards = document.querySelectorAll('.people-page .contact-card');

        searchBox.addEventListener('input', function () {
            var q = searchBox.value.trim().toLowerCase();
            cards.forEach(function (card) {
                var nameEl = card.querySelector('h3');
                var name = nameEl ? nameEl.textContent.toLowerCase() : '';
                if (!q || name.indexOf(q) !== -1) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }, { signal: signal });

        searchBox.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                searchBox.value = '';
                cards.forEach(function (card) {
                    card.style.display = '';
                });
                searchBox.blur();
            }
        }, { signal: signal });
    }

    document.addEventListener('chatrox:page_load', function (e) {
        if (!e.detail || e.detail.active_tab !== 'people') return;
        initPeopleSearch();
    });

    document.addEventListener('chatrox:page_unload', function () {
        if (sessionAbort) sessionAbort.abort();
    });
})(typeof window !== 'undefined' ? window : this);
