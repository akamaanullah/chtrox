/** Message HTML → plain text helpers (sidebar preview, reply snippet). */
(function () {
    function toPlain(html, singleLine) {
        if (!html) return '';
        var normalized = String(html)
            .replace(/<br\s*\/?>/gi, '\n')
            .replace(/<\/div>/gi, '\n')
            .replace(/<\/p>/gi, '\n')
            .replace(/<\/li>/gi, '\n');
        var tmp = document.createElement('div');
        tmp.innerHTML = normalized;
        var text = (tmp.textContent || tmp.innerText || '').replace(/\r/g, '');
        text = text.replace(/\n{3,}/g, '\n\n').trim();
        if (singleLine) {
            text = text.replace(/\s+/g, ' ').trim();
        }
        return text;
    }

    window.ChatRoxText = {
        toPlain: toPlain,
        toSidebarPreview: function (html, maxLen) {
            var text = toPlain(html, true);
            if (!text) return 'Attachment';
            maxLen = maxLen || 30;
            return text.length > maxLen ? text.substring(0, maxLen) + '...' : text;
        },
        bubbleToPlain: function (bubble, singleLine) {
            if (!bubble) return '';
            var clone = bubble.cloneNode(true);
            clone.querySelectorAll('.dm-msg-reply-wrap, .dm-msg-reactions, .dm-msg-files, .dm-msg-images').forEach(function (n) {
                n.remove();
            });
            return toPlain(clone.innerHTML, singleLine);
        }
    };
})();

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();

    // Initialize theme system from shared themes-shared.js
    if (window.ChatroxTheme) {
        ChatroxTheme.init();
    }

    // Handle interactive selection for list items (dir-item, aq-item)
    document.addEventListener('click', (e) => {
        const item = e.target.closest('.dir-item');
        if (item) {
            const list = item.parentElement;
            list.querySelectorAll('.dir-item').forEach(el => el.classList.remove('active'));
            item.classList.add('active');
        }
    });

    /* More menu – tap on mobile (bottom nav has no hover) */
    const mobileNavMq = window.matchMedia('(max-width: 992px)');

    document.querySelectorAll('.more-trigger').forEach(function (trigger) {
        var moreBtn = trigger.querySelector('.nav-item.no-link');
        if (!moreBtn) return;

        moreBtn.addEventListener('click', function (e) {
            if (!mobileNavMq.matches) return;
            e.preventDefault();
            e.stopPropagation();
            var isOpen = trigger.classList.contains('more-open');
            document.querySelectorAll('.more-trigger.more-open').forEach(function (t) {
                t.classList.remove('more-open');
            });
            if (!isOpen) trigger.classList.add('more-open');
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.more-trigger')) {
            document.querySelectorAll('.more-trigger.more-open').forEach(function (t) {
                t.classList.remove('more-open');
            });
        }
    });

});