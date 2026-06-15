document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    console.log('ChatRox Loaded');

    // Theme Switching Logic
    const themes = {
        indigo: { '--indigo-50': '#eef2ff', '--indigo-100': '#e0e7ff', '--indigo-200': '#c7d2fe', '--indigo-300': '#a5b4fc', '--indigo-400': '#818cf8', '--indigo-500': '#6366f1', '--indigo-600': '#4f46e5', '--indigo-700': '#4338ca', '--indigo-800': '#3730a3', '--indigo-900': '#312e81', '--indigo-950': '#1e1b4b' },
        blue: { '--indigo-50': '#eff6ff', '--indigo-100': '#dbeafe', '--indigo-200': '#bfdbfe', '--indigo-300': '#93c5fd', '--indigo-400': '#60a5fa', '--indigo-500': '#3b82f6', '--indigo-600': '#2563eb', '--indigo-700': '#1d4ed8', '--indigo-800': '#1e40af', '--indigo-900': '#1e3a8a', '--indigo-950': '#172554' },
        violet: { '--indigo-50': '#f5f3ff', '--indigo-100': '#ede9fe', '--indigo-200': '#ddd6fe', '--indigo-300': '#c4b5fd', '--indigo-400': '#a78bfa', '--indigo-500': '#8b5cf6', '--indigo-600': '#7c3aed', '--indigo-700': '#6d28d9', '--indigo-800': '#5b21b6', '--indigo-900': '#4c1d95', '--indigo-950': '#2e1065' },
        emerald: { '--indigo-50': '#ecfdf5', '--indigo-100': '#d1fae5', '--indigo-200': '#a7f3d0', '--indigo-300': '#6ee7b7', '--indigo-400': '#34d399', '--indigo-500': '#10b981', '--indigo-600': '#059669', '--indigo-700': '#047857', '--indigo-800': '#065f46', '--indigo-900': '#064e3b', '--indigo-950': '#022c22' },
        rose: { '--indigo-50': '#fff1f2', '--indigo-100': '#ffe4e6', '--indigo-200': '#fecdd3', '--indigo-300': '#fda4af', '--indigo-400': '#fb7185', '--indigo-500': '#f43f5e', '--indigo-600': '#e11d48', '--indigo-700': '#be123c', '--indigo-800': '#9f1239', '--indigo-900': '#881337', '--indigo-950': '#4c0519' },
        sky: { '--indigo-50': '#f0f9ff', '--indigo-100': '#e0f2fe', '--indigo-200': '#bae6fd', '--indigo-300': '#7dd3fc', '--indigo-400': '#38bdf8', '--indigo-500': '#0ea5e9', '--indigo-600': '#0284c7', '--indigo-700': '#0369a1', '--indigo-800': '#075985', '--indigo-900': '#0c4a6e', '--indigo-950': '#082f49' },
        teal: { '--indigo-50': '#f0fdfa', '--indigo-100': '#ccfbf1', '--indigo-200': '#99f6e4', '--indigo-300': '#5eead4', '--indigo-400': '#2dd4bf', '--indigo-500': '#14b8a6', '--indigo-600': '#0d9488', '--indigo-700': '#0f766e', '--indigo-800': '#115e59', '--indigo-900': '#134e4a', '--indigo-950': '#042f2e' },
        amber: { '--indigo-50': '#fffbeb', '--indigo-100': '#fef3c7', '--indigo-200': '#fde68a', '--indigo-300': '#fcd34d', '--indigo-400': '#fbbf24', '--indigo-500': '#f59e0b', '--indigo-600': '#d97706', '--indigo-700': '#b45309', '--indigo-800': '#92400e', '--indigo-900': '#78350f', '--indigo-950': '#451a03' },
        cyan: { '--indigo-50': '#ecfeff', '--indigo-100': '#cffafe', '--indigo-200': '#a5f3fc', '--indigo-300': '#67e8f9', '--indigo-400': '#22d3ee', '--indigo-500': '#06b6d4', '--indigo-600': '#0891b2', '--indigo-700': '#0e7490', '--indigo-800': '#155e75', '--indigo-900': '#164e63', '--indigo-950': '#083344' },
        fuchsia: { '--indigo-50': '#fdf4ff', '--indigo-100': '#fae8ff', '--indigo-200': '#f5d0fe', '--indigo-300': '#f0abfc', '--indigo-400': '#e879f9', '--indigo-500': '#d946ef', '--indigo-600': '#c026d3', '--indigo-700': '#a21caf', '--indigo-800': '#86198f', '--indigo-900': '#701a75', '--indigo-950': '#4a044e' },
        lime: { '--indigo-50': '#f7fee7', '--indigo-100': '#ecfccb', '--indigo-200': '#d9f99d', '--indigo-300': '#bef264', '--indigo-400': '#a3e635', '--indigo-500': '#84cc16', '--indigo-600': '#65a30d', '--indigo-700': '#4d7c0f', '--indigo-800': '#3f6212', '--indigo-900': '#365314', '--indigo-950': '#1a2e05' },
        orange: { '--indigo-50': '#fff7ed', '--indigo-100': '#ffedd5', '--indigo-200': '#fed7aa', '--indigo-300': '#fdba74', '--indigo-400': '#fb923c', '--indigo-500': '#f97316', '--indigo-600': '#ea580c', '--indigo-700': '#c2410c', '--indigo-800': '#9a3412', '--indigo-900': '#7c2d12', '--indigo-950': '#431407' }
    };

    function applyTheme(themeName) {
        const colors = themes[themeName];
        if (colors) {
            for (const [key, value] of Object.entries(colors)) {
                document.documentElement.style.setProperty(key, value);
            }
            localStorage.setItem('chatrox_theme', themeName);
        }
    }

    // Initialize UI based on saved theme
    const savedTheme = localStorage.getItem('chatrox_theme') || 'lime';
    applyTheme(savedTheme); // Apply the theme on load
    const activeRadio = document.querySelector(`input[name="theme_color"][value="${savedTheme}"]`);
    if (activeRadio) {
        activeRadio.checked = true;
    }

    // Listen for theme changes
    document.querySelectorAll('input[name="theme_color"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            applyTheme(e.target.value);
        });
    });

    // Handle interactive selection for list items (dir-item, aq-item; dm-item is now a link)
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