/**
 * Scoped Lucide icon hydration — avoids full-document scans on every update.
 */
(function (global) {
    'use strict';

    function refreshIcons(root) {
        if (!global.lucide || typeof global.lucide.createIcons !== 'function') {
            return;
        }

        if (!root) {
            global.lucide.createIcons();
            return;
        }

        if (root.nodeType === 1) {
            global.lucide.createIcons({ nodes: [root] });
            return;
        }

        if (typeof root.length === 'number' && root.length) {
            var nodes = [];
            for (var i = 0; i < root.length; i++) {
                if (root[i] && root[i].nodeType === 1) nodes.push(root[i]);
            }
            if (nodes.length) global.lucide.createIcons({ nodes: nodes });
        }
    }

    global.ChatRoxLucide = {
        refresh: refreshIcons
    };
})(typeof window !== 'undefined' ? window : this);
