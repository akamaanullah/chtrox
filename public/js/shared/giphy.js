/**
 * Giphy proxy client — calls ChatRox backend so the API key stays server-side.
 */
(function (global) {
    'use strict';

    function fetchGifs(query, limit) {
        var base = (global.CHATROX && global.CHATROX.baseUrl) ? global.CHATROX.baseUrl : '';
        limit = limit || 20;
        var url = base + '/api/giphy/gifs?limit=' + encodeURIComponent(String(limit));
        if (query) {
            url += '&q=' + encodeURIComponent(String(query));
        }

        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok || !data.success) {
                    var err = new Error(data.message || 'Failed to load GIFs');
                    err.code = data.error || 'giphy_failed';
                    throw err;
                }
                return data.gifs || [];
            });
        });
    }

    function isGifUrl(url) {
        if (!url || typeof url !== 'string') return false;
        url = url.trim();
        if (!/^https?:\/\//i.test(url) || !/giphy\.com/i.test(url)) return false;
        if (/^https?:\/\/[a-zA-Z0-9.-]+\.giphy\.com\/v1\/gifs\//i.test(url)) return true;
        if (/^https?:\/\/media[0-9]*\.giphy\.com\/media\//i.test(url)) return true;
        if (/^https?:\/\/i\.giphy\.com\//i.test(url)) return true;
        return false;
    }

    function resolveMessageType(type, body) {
        if (type === 'gif') return 'gif';
        if (type === 'text' && isGifUrl(body)) return 'gif';
        return type || 'text';
    }

    global.ChatRoxGiphy = {
        fetch: fetchGifs,
        isGifUrl: isGifUrl,
        resolveMessageType: resolveMessageType
    };
})(typeof window !== 'undefined' ? window : this);
