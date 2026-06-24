/**
 * Shared message media helpers — image grid (max 4 + overlay) and file size labels.
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

    function formatFileSize(bytes) {
        if (global.ChatRoxUpload && typeof global.ChatRoxUpload.formatSize === 'function') {
            return global.ChatRoxUpload.formatSize(bytes);
        }

        bytes = parseInt(bytes, 10) || 0;
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(1) + ' GB';
        }
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(1) + ' MB';
        }
        if (bytes >= 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }
        return bytes + ' B';
    }

    /**
     * WhatsApp-style grid: max 4 thumbnails, +N overlay on last cell when more remain.
     *
     * @param {Array<{url: string}>} images
     * @returns {string}
     */
    function buildImageGridHtml(images) {
        if (!images || !images.length) {
            return '';
        }

        if (images.length === 1) {
            return '<div class="dm-msg-images dm-msg-images--single">' +
                '<img src="' + escapeHtml(images[0].url) + '" alt="" class="dm-msg-img js-msg-img" loading="lazy">' +
                '</div>';
        }

        var showCount = images.length > 4 ? 4 : images.length;
        var moreCount = images.length > 4 ? images.length - 4 : 0;
        var urls = images.map(function (img) { return img.url; });
        var gridJson = escapeHtml(JSON.stringify(urls));
        var html = '<div class="dm-msg-images dm-msg-images--grid dm-msg-images--count-' + showCount + '" data-lightbox-srcs="' + gridJson + '">';

        for (var idx = 0; idx < showCount; idx++) {
            var src = escapeHtml(images[idx].url);
            if (moreCount > 0 && idx === 3) {
                html += '<div class="dm-msg-grid-cell-wrap">' +
                    '<img src="' + src + '" alt="" class="dm-msg-img js-msg-img" loading="lazy" data-index="' + idx + '">' +
                    '<span class="dm-msg-grid-more">+' + moreCount + '</span>' +
                    '</div>';
            } else {
                html += '<img src="' + src + '" alt="" class="dm-msg-img js-msg-img" loading="lazy" data-index="' + idx + '">';
            }
        }

        html += '</div>';
        return html;
    }

    global.ChatRoxMedia = {
        buildImageGridHtml: buildImageGridHtml,
        formatFileSize: formatFileSize
    };
})(typeof window !== 'undefined' ? window : this);
