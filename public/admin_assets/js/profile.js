/**
 * profile.js 
 * Handles toggle functionality for profile sections and dynamic form saves
 */

document.addEventListener('DOMContentLoaded', function () {
    const utils = window.ChatroxUtils;
    
    // Select all collapsible section headers
    const sectionHeaders = document.querySelectorAll('.profile-section.collapsible .section-header');

    sectionHeaders.forEach(header => {
        header.addEventListener('click', function () {
            const section = this.parentElement;
            section.classList.toggle('collapsed');
        });
    });

    // Avatar preview handler
    const avatarInput = document.getElementById('profileAvatarInput');
    const avatarImg = document.getElementById('profileAvatarImg');
    const avatarInitial = document.getElementById('profileAvatarInitial');

    if (avatarInput && avatarImg && avatarInitial) {
        avatarInput.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file || !file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                avatarImg.src = e.target.result;
                avatarImg.classList.remove('profile-avatar-img--hidden');
                avatarInitial.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }

    // Submit profile updates via FormData AJAX
    const saveBtn = document.getElementById('profileSaveBtn');
    const form = document.getElementById('profileForm');

    if (saveBtn && form) {
        saveBtn.addEventListener('click', function (e) {
            e.preventDefault();

            const formData = new FormData(form);

            // Set two factor enabled checkbox value explicitly
            const tfaToggle = document.getElementById('2fa-toggle');
            if (tfaToggle) {
                formData.set('two_factor_enabled', tfaToggle.checked ? '1' : '0');
            }

            const headers = {};
            if (window.CHATROX_ADMIN && window.CHATROX_ADMIN.csrfToken) {
                headers['X-CSRF-Token'] = window.CHATROX_ADMIN.csrfToken;
            }

            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            fetch(window.CHATROX_ADMIN.baseUrl + '/api/admin/profile', {
                method: 'POST',
                headers: headers,
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Changes';

                if (res.success) {
                    utils.showToast(res.message || 'Profile saved successfully!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    utils.showToast(res.error || 'Failed to save changes.', 'error');
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Changes';
                console.error(err);
                utils.showToast('An unexpected error occurred.', 'error');
            });
        });
    }
});
