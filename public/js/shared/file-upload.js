/**
 * Shared file upload helpers: size validation, progress upload, progress UI.
 * All extensions allowed — production teams share code/SQL/config files.
 */
(function (global) {
    'use strict';

    function maxBytes() {
        if (global.CHATROX && global.CHATROX.maxFileSizeBytes) {
            return parseInt(global.CHATROX.maxFileSizeBytes, 10) || 41943040;
        }
        return 41943040;
    }

    function maxSizeLabel() {
        if (global.CHATROX && global.CHATROX.maxFileSizeLabel) {
            return global.CHATROX.maxFileSizeLabel;
        }
        return formatSize(maxBytes());
    }

    function formatSize(bytes) {
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
     * @param {FileList|File[]|Array<{file: File}>} fileList
     * @returns {{valid: boolean, errors: string[], files: Array<{name: string, size: number, file: File}>}}
     */
    function validateFiles(fileList) {
        var errors = [];
        var files = [];
        var limit = maxBytes();
        var limitLabel = maxSizeLabel();
        var list = fileList || [];

        for (var i = 0; i < list.length; i++) {
            var item = list[i];
            var file = item && item.file ? item.file : item;
            if (!file || !file.name) {
                continue;
            }
            if (!file.size) {
                errors.push('"' + file.name + '" is empty.');
                continue;
            }
            if (file.size > limit) {
                errors.push('"' + file.name + '" exceeds ' + limitLabel + ' limit.');
                continue;
            }
            files.push({
                name: file.name,
                size: file.size,
                file: file
            });
        }

        return {
            valid: errors.length === 0 && files.length > 0,
            errors: errors,
            files: files
        };
    }

    function buildProgressBarHtml(percent, label) {
        percent = Math.max(0, Math.min(100, parseInt(percent, 10) || 0));
        label = label || (percent >= 100 ? 'Sending message…' : 'Uploading… ' + percent + '%');
        return '' +
            '<div class="dm-upload-progress" aria-live="polite">' +
            '<div class="dm-upload-progress-track">' +
            '<div class="dm-upload-progress-fill js-upload-progress-fill" style="width:' + percent + '%"></div>' +
            '</div>' +
            '<span class="dm-upload-progress-label js-upload-progress-label">' + label + '</span>' +
            '</div>';
    }

    /**
     * @param {File[]|Array<{file: File}>} files
     * @param {{onProgress?: function(number): void}} options
     * @returns {Promise<object>}
     */
    function upload(files, options) {
        options = options || {};
        var normalized = [];
        (files || []).forEach(function (item) {
            var file = item && item.file ? item.file : item;
            if (file) {
                normalized.push(file);
            }
        });

        if (!normalized.length) {
            return Promise.reject(new Error('No files to upload.'));
        }

        var validation = validateFiles(normalized);
        if (validation.files.length === 0) {
            return Promise.reject(new Error(validation.errors.join(' ') || 'Invalid files.'));
        }

        var validFiles = validation.files.map(function (f) { return f.file; });

        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            var formData = new FormData();
            validFiles.forEach(function (file) {
                formData.append('files[]', file);
            });

            xhr.upload.addEventListener('progress', function (event) {
                if (!event.lengthComputable || typeof options.onProgress !== 'function') {
                    return;
                }
                options.onProgress(Math.round((event.loaded / event.total) * 100));
            });

            xhr.addEventListener('load', function () {
                var payload = null;
                try {
                    payload = JSON.parse(xhr.responseText || '{}');
                } catch (err) {
                    reject(new Error('Upload response was invalid.'));
                    return;
                }

                if (xhr.status >= 200 && xhr.status < 300 && payload.success) {
                    if (validation.errors.length > 0) {
                        payload.partial = true;
                        payload.errors = payload.errors || [];
                        validation.errors.forEach(function (errStr) {
                            payload.errors.push({
                                name: 'File',
                                error: 'client_validation_failed',
                                message: errStr
                            });
                        });
                    }
                    resolve(payload);
                    return;
                }

                reject(new Error(payload.message || payload.error || 'Upload failed.'));
            });

            xhr.addEventListener('error', function () {
                reject(new Error('Network error during upload.'));
            });

            xhr.addEventListener('abort', function () {
                reject(new Error('Upload cancelled.'));
            });

            xhr.open('POST', (global.CHATROX && global.CHATROX.apiUrl ? global.CHATROX.apiUrl : '') + '/files/upload');
            if (global.CHATROX && global.CHATROX.csrfToken) {
                xhr.setRequestHeader('X-CSRF-Token', global.CHATROX.csrfToken);
            }
            xhr.send(formData);
        });
    }

    global.ChatRoxUpload = {
        maxBytes: maxBytes,
        maxSizeLabel: maxSizeLabel,
        formatSize: formatSize,
        validateFiles: validateFiles,
        upload: upload,
        buildProgressBarHtml: buildProgressBarHtml
    };
})(typeof window !== 'undefined' ? window : this);
