/**
 * ChatRox Theme System — Single Source of Truth
 * Loaded render-blocking in <head> to prevent theme flash (FOUC).
 * Used by both front-end and admin dashboard.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'chatrox_theme';
    var DEFAULT_THEME = 'lime';

    var palettes = {
        indigo:  { '--indigo-50': '#eef2ff', '--indigo-100': '#e0e7ff', '--indigo-200': '#c7d2fe', '--indigo-300': '#a5b4fc', '--indigo-400': '#818cf8', '--indigo-500': '#6366f1', '--indigo-600': '#4f46e5', '--indigo-700': '#4338ca', '--indigo-800': '#3730a3', '--indigo-900': '#312e81', '--indigo-950': '#1e1b4b' },
        blue:    { '--indigo-50': '#eff6ff', '--indigo-100': '#dbeafe', '--indigo-200': '#bfdbfe', '--indigo-300': '#93c5fd', '--indigo-400': '#60a5fa', '--indigo-500': '#3b82f6', '--indigo-600': '#2563eb', '--indigo-700': '#1d4ed8', '--indigo-800': '#1e40af', '--indigo-900': '#1e3a8a', '--indigo-950': '#172554' },
        violet:  { '--indigo-50': '#f5f3ff', '--indigo-100': '#ede9fe', '--indigo-200': '#ddd6fe', '--indigo-300': '#c4b5fd', '--indigo-400': '#a78bfa', '--indigo-500': '#8b5cf6', '--indigo-600': '#7c3aed', '--indigo-700': '#6d28d9', '--indigo-800': '#5b21b6', '--indigo-900': '#4c1d95', '--indigo-950': '#2e1065' },
        emerald: { '--indigo-50': '#ecfdf5', '--indigo-100': '#d1fae5', '--indigo-200': '#a7f3d0', '--indigo-300': '#6ee7b7', '--indigo-400': '#34d399', '--indigo-500': '#10b981', '--indigo-600': '#059669', '--indigo-700': '#047857', '--indigo-800': '#065f46', '--indigo-900': '#064e3b', '--indigo-950': '#022c22' },
        rose:    { '--indigo-50': '#fff1f2', '--indigo-100': '#ffe4e6', '--indigo-200': '#fecdd3', '--indigo-300': '#fda4af', '--indigo-400': '#fb7185', '--indigo-500': '#f43f5e', '--indigo-600': '#e11d48', '--indigo-700': '#be123c', '--indigo-800': '#9f1239', '--indigo-900': '#881337', '--indigo-950': '#4c0519' },
        sky:     { '--indigo-50': '#f0f9ff', '--indigo-100': '#e0f2fe', '--indigo-200': '#bae6fd', '--indigo-300': '#7dd3fc', '--indigo-400': '#38bdf8', '--indigo-500': '#0ea5e9', '--indigo-600': '#0284c7', '--indigo-700': '#0369a1', '--indigo-800': '#075985', '--indigo-900': '#0c4a6e', '--indigo-950': '#082f49' },
        teal:    { '--indigo-50': '#f0fdfa', '--indigo-100': '#ccfbf1', '--indigo-200': '#99f6e4', '--indigo-300': '#5eead4', '--indigo-400': '#2dd4bf', '--indigo-500': '#14b8a6', '--indigo-600': '#0d9488', '--indigo-700': '#0f766e', '--indigo-800': '#115e59', '--indigo-900': '#134e4a', '--indigo-950': '#042f2e' },
        amber:   { '--indigo-50': '#fffbeb', '--indigo-100': '#fef3c7', '--indigo-200': '#fde68a', '--indigo-300': '#fcd34d', '--indigo-400': '#fbbf24', '--indigo-500': '#f59e0b', '--indigo-600': '#d97706', '--indigo-700': '#b45309', '--indigo-800': '#92400e', '--indigo-900': '#78350f', '--indigo-950': '#451a03' },
        cyan:    { '--indigo-50': '#ecfeff', '--indigo-100': '#cffafe', '--indigo-200': '#a5f3fc', '--indigo-300': '#67e8f9', '--indigo-400': '#22d3ee', '--indigo-500': '#06b6d4', '--indigo-600': '#0891b2', '--indigo-700': '#0e7490', '--indigo-800': '#155e75', '--indigo-900': '#164e63', '--indigo-950': '#083344' },
        fuchsia: { '--indigo-50': '#fdf4ff', '--indigo-100': '#fae8ff', '--indigo-200': '#f5d0fe', '--indigo-300': '#f0abfc', '--indigo-400': '#e879f9', '--indigo-500': '#d946ef', '--indigo-600': '#c026d3', '--indigo-700': '#a21caf', '--indigo-800': '#86198f', '--indigo-900': '#701a75', '--indigo-950': '#4a044e' },
        lime:    { '--indigo-50': '#f7fee7', '--indigo-100': '#ecfccb', '--indigo-200': '#d9f99d', '--indigo-300': '#bef264', '--indigo-400': '#a3e635', '--indigo-500': '#84cc16', '--indigo-600': '#65a30d', '--indigo-700': '#4d7c0f', '--indigo-800': '#3f6212', '--indigo-900': '#365314', '--indigo-950': '#1a2e05' },
        orange:  { '--indigo-50': '#fff7ed', '--indigo-100': '#ffedd5', '--indigo-200': '#fed7aa', '--indigo-300': '#fdba74', '--indigo-400': '#fb923c', '--indigo-500': '#f97316', '--indigo-600': '#ea580c', '--indigo-700': '#c2410c', '--indigo-800': '#9a3412', '--indigo-900': '#7c2d12', '--indigo-950': '#431407' }
    };

    function applyCssVars(themeName) {
        var colors = palettes[themeName];
        if (!colors) return false;
        var keys = Object.keys(colors);
        for (var i = 0; i < keys.length; i++) {
            document.documentElement.style.setProperty(keys[i], colors[keys[i]]);
        }
        return true;
    }

    function syncThemeUi(themeName) {
        document.querySelectorAll('input[name="theme_color"]').forEach(function (radio) {
            radio.checked = radio.value === themeName;
        });
        document.querySelectorAll('.color-option').forEach(function (option) {
            var radio = option.querySelector('input[name="theme_color"]');
            var value = radio ? radio.value : option.getAttribute('data-theme');
            option.classList.toggle('active', value === themeName);
        });
    }

    function applyTheme(themeName, persist) {
        if (!applyCssVars(themeName)) return;
        if (persist !== false) {
            localStorage.setItem(STORAGE_KEY, themeName);
        }
        syncThemeUi(themeName);
    }

    function applySaved() {
        var saved = localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME;
        applyCssVars(saved);
    }

    function init() {
        var saved = localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME;
        applyTheme(saved);

        document.querySelectorAll('input[name="theme_color"]').forEach(function (radio) {
            if (radio.dataset.themeBound === '1') return;
            radio.dataset.themeBound = '1';
            radio.addEventListener('change', function (e) {
                if (e.target.checked) {
                    applyTheme(e.target.value);
                }
            });
        });
    }

    window.ChatroxTheme = {
        palettes: palettes,
        applyTheme: applyTheme,
        applySaved: applySaved,
        init: init,
        syncThemeUi: syncThemeUi
    };
})();
