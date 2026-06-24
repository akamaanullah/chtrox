/**
 * ChatRox toast notifications — replaces native browser alerts.
 */
(function (global) {
    'use strict';

    var ICONS = {
        success: 'check-circle',
        error: 'alert-circle',
        warning: 'alert-triangle',
        info: 'info'
    };

    var TITLES = {
        success: 'Success',
        error: 'Something went wrong',
        warning: 'Heads up',
        info: 'Notice'
    };

    var container = null;
    var toastSeq = 0;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function ensureContainer() {
        if (container && document.body.contains(container)) {
            return container;
        }
        container = document.createElement('div');
        container.className = 'crx-toast-stack';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-relevant', 'additions');
        document.body.appendChild(container);
        return container;
    }

    function buildMessageHtml(message) {
        if (!message) {
            return '';
        }
        if (Array.isArray(message)) {
            if (message.length === 1) {
                return '<p class="crx-toast-text">' + escapeHtml(message[0]) + '</p>';
            }
            var items = message.map(function (line) {
                return '<li>' + escapeHtml(line) + '</li>';
            }).join('');
            return '<ul class="crx-toast-list">' + items + '</ul>';
        }
        return '<p class="crx-toast-text">' + escapeHtml(message) + '</p>';
    }

    function normalizeOptions(input, type) {
        if (typeof input === 'string') {
            return {
                title: TITLES[type] || TITLES.info,
                message: input,
                type: type || 'info',
                duration: 5200
            };
        }

        var opts = input || {};
        var resolvedType = opts.type || type || 'info';

        return {
            title: opts.title || TITLES[resolvedType] || TITLES.info,
            message: opts.message || '',
            type: resolvedType,
            duration: typeof opts.duration === 'number' ? opts.duration : 5200
        };
    }

    function dismissToast(toastEl) {
        if (!toastEl || toastEl.classList.contains('crx-toast--leaving')) {
            return;
        }
        toastEl.classList.add('crx-toast--leaving');
        window.setTimeout(function () {
            if (toastEl.parentNode) {
                toastEl.parentNode.removeChild(toastEl);
            }
        }, 220);
    }

    function show(input, type) {
        var opts = normalizeOptions(input, type);
        var stack = ensureContainer();
        var id = 'crx-toast-' + (++toastSeq);
        var icon = ICONS[opts.type] || ICONS.info;

        var toast = document.createElement('div');
        toast.className = 'crx-toast crx-toast--' + opts.type;
        toast.id = id;
        toast.setAttribute('role', opts.type === 'error' ? 'alert' : 'status');

        toast.innerHTML =
            '<div class="crx-toast-icon" aria-hidden="true">' +
                '<i data-lucide="' + icon + '" size="18"></i>' +
            '</div>' +
            '<div class="crx-toast-body">' +
                '<p class="crx-toast-title">' + escapeHtml(opts.title) + '</p>' +
                buildMessageHtml(opts.message) +
            '</div>' +
            '<button type="button" class="crx-toast-close" aria-label="Dismiss notification">' +
                '<i data-lucide="x" size="16"></i>' +
            '</button>';

        stack.appendChild(toast);

        if (global.lucide && typeof global.lucide.createIcons === 'function') {
            global.lucide.createIcons();
        }

        var closeBtn = toast.querySelector('.crx-toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                dismissToast(toast);
            });
        }

        if (opts.duration > 0) {
            window.setTimeout(function () {
                dismissToast(toast);
            }, opts.duration);
        }

        return toast;
    }

    global.ChatRoxToast = {
        show: show,
        success: function (message, title) {
            return show({ title: title, message: message, type: 'success' });
        },
        error: function (message, title) {
            return show({ title: title || TITLES.error, message: message, type: 'error', duration: 6200 });
        },
        warning: function (message, title) {
            return show({ title: title || TITLES.warning, message: message, type: 'warning' });
        },
        info: function (message, title) {
            return show({ title: title, message: message, type: 'info' });
        }
    };
})(typeof window !== 'undefined' ? window : this);
