/**
 * Chat day dividers — Today, Yesterday, then date (j/n/y).
 */
(function (global) {
    'use strict';

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function dayKeyFromDate(date) {
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function dayKey(timestamp) {
        if (!timestamp) return '';
        var str = String(timestamp).trim();
        var match = str.match(/^(\d{4}-\d{2}-\d{2})/);
        if (match) {
            return match[1];
        }
        var normalized = str.replace(' ', 'T');
        var date = new Date(normalized);
        if (isNaN(date.getTime())) return '';
        return dayKeyFromDate(date);
    }

    function labelForDayKey(dayKeyValue) {
        if (!dayKeyValue) return '';

        var today = dayKeyFromDate(new Date());
        var yesterdayDate = new Date();
        yesterdayDate.setDate(yesterdayDate.getDate() - 1);
        var yesterday = dayKeyFromDate(yesterdayDate);

        if (dayKeyValue === today) return 'Today';
        if (dayKeyValue === yesterday) return 'Yesterday';

        var parts = dayKeyValue.split('-');
        if (parts.length !== 3) return dayKeyValue;

        return parseInt(parts[2], 10) + '/' + parseInt(parts[1], 10) + '/' + String(parts[0]).slice(-2);
    }

    function buildDateDividerHtml(label, dayKeyValue) {
        return '<div class="dm-date-divider" data-date-key="' + escapeHtml(dayKeyValue) + '">' +
            '<span class="dm-date-divider-text">' + escapeHtml(label) + '</span></div>';
    }

    function isTimelineElement(el) {
        if (!el || el.nodeType !== 1) return false;
        if (el.classList.contains('dm-chat-msg--hidden') || 
            el.classList.contains('dm-chat-msg--search-nomatch') ||
            el.style.display === 'none') {
            return false;
        }
        return el.classList.contains('dm-chat-msg')
            || el.classList.contains('dm-system-divider')
            || el.getAttribute('data-system-message') === '1';
    }

    function getTimelineElements(container) {
        if (!container) return [];
        return Array.from(container.children).filter(isTimelineElement);
    }

    function getElementDayKey(el) {
        var createdAt = el.getAttribute('data-created-at') || '';
        return dayKey(createdAt);
    }

    function reconcileDateDividers(container) {
        if (!container) return;

        container.querySelectorAll('.dm-date-divider').forEach(function (el) {
            el.remove();
        });

        var items = getTimelineElements(container);
        for (var i = 0; i < items.length; i++) {
            var current = items[i];
            var currentKey = getElementDayKey(current);
            if (!currentKey) continue;

            var next = items[i + 1] || null;
            var nextKey = next ? getElementDayKey(next) : null;

            if (nextKey !== currentKey) {
                var html = buildDateDividerHtml(labelForDayKey(currentKey), currentKey);
                current.insertAdjacentHTML('afterend', html);
            }
        }
    }

    global.ChatRoxDateDivider = {
        dayKey: dayKey,
        labelForDayKey: labelForDayKey,
        buildDateDividerHtml: buildDateDividerHtml,
        reconcileDateDividers: reconcileDateDividers,
    };
})(window);
