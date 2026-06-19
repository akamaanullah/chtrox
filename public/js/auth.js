document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.auth-password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var wrapper = btn.closest('.auth-input-wrapper');
            if (!wrapper) return;

            var input = wrapper.querySelector('.auth-input');
            if (!input) return;

            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            btn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');

            var icon = btn.querySelector('[data-lucide]');
            if (icon) {
                icon.setAttribute('data-lucide', isHidden ? 'eye-off' : 'eye');
                if (window.lucide) {
                    lucide.createIcons();
                }
            }
        });
    });
});
