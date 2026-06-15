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
    // Color selection logic
    const colorOptions = document.querySelectorAll('.color-option');
    colorOptions.forEach(option => {
        option.addEventListener('click', function () {
            colorOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');

            // Here you can add logic to update the theme color globally if needed
            // const selectedColor = this.querySelector('.color-label').textContent;
            // console.log('Selected Theme:', selectedColor);
        });
    });
});
