/**
 * profile.js 
 * Handles toggle functionality for profile sections
 */

document.addEventListener('DOMContentLoaded', function () {
    // Select all collapsible section headers
    const sectionHeaders = document.querySelectorAll('.profile-section.collapsible .section-header');

    sectionHeaders.forEach(header => {
        header.addEventListener('click', function () {
            const section = this.parentElement;

            // Toggle 'collapsed' class
            section.classList.toggle('collapsed');

            // Optional: Close other sections if you want accordion behavior
            // closeOtherSections(section);
        });
    });

    // Function to close other sections (Optional)
    function closeOtherSections(currentSection) {
        document.querySelectorAll('.profile-section.collapsible').forEach(section => {
            if (section !== currentSection) {
                section.classList.add('collapsed');
            }
        });
    }

    // Initialize: You can start with some sections collapsed if you want

    var avatarInput = document.getElementById('profileAvatarInput');
    var avatarImg = document.getElementById('profileAvatarImg');
    var avatarInitial = document.getElementById('profileAvatarInitial');

    if (avatarInput && avatarImg && avatarInitial) {
        avatarInput.addEventListener('change', function () {
            var file = this.files && this.files[0];
            if (!file || !file.type.startsWith('image/')) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                avatarImg.src = e.target.result;
                avatarImg.classList.remove('profile-avatar-img--hidden');
                avatarInitial.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }
});
