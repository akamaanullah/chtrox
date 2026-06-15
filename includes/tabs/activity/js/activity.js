// Activity tab functionality
document.addEventListener('DOMContentLoaded', () => {
    // Only run if we are on the activity tab
    const activityFeed = document.querySelector('.activity-feed');
    if (!activityFeed) return;

    const activityCards = document.querySelectorAll('.activity-card');
    const aqItems = document.querySelectorAll('.aq-item');
    const filterPills = document.querySelectorAll('.filter-pills .pill');

    // 1. Sidebar filtering (aq-items to activity cards)
    aqItems.forEach(item => {
        item.addEventListener('click', () => {
            const targetId = item.getAttribute('data-target');
            if (!targetId) return;

            // Remove active class from all sidebar items and pills
            aqItems.forEach(aq => aq.classList.remove('active'));
            filterPills.forEach(pill => pill.classList.remove('active'));

            // Add active class to clicked item
            item.classList.add('active');

            // Show only the target card
            activityCards.forEach(card => {
                const cardId = card.getAttribute('data-id');
                if (cardId === targetId) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Add pointer cursor for better UX
        item.style.cursor = 'pointer';
    });

    // 2. Filter Pills logic (ALL, MENTION, FILE, REACTION)
    filterPills.forEach(pill => {
        pill.addEventListener('click', () => {
            const filterType = pill.textContent.trim().toUpperCase();

            // Active state management
            filterPills.forEach(p => p.classList.remove('active'));
            aqItems.forEach(aq => aq.classList.remove('active'));
            pill.classList.add('active');

            // Show cards based on filter
            activityCards.forEach(card => {
                if (filterType === 'ALL') {
                    card.style.display = 'flex';
                } else {
                    const textContent = card.textContent.toLowerCase();
                    let isMatch = false;

                    // Simple keyword matching for demo purposes
                    if (filterType === 'MENTION' && textContent.includes('mentioned you')) {
                        isMatch = true;
                    } else if (filterType === 'FILE' && (textContent.includes('shared a document') || textContent.includes('uploaded'))) {
                        isMatch = true;
                    } else if (filterType === 'REACTION' && textContent.includes('reacted with')) {
                        isMatch = true;
                    }

                    if (isMatch) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        });
    });

    // 3. Delete Activity Card Logic
    const deleteButtons = document.querySelectorAll('.js-delete-activity');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Prevent the click from triggering the card's broader click event (if any exists)
            e.stopPropagation();
            const card = e.target.closest('.activity-card');
            if (card) {
                // Step 1: Slide out to the right and fade
                card.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.transform = 'translateX(100%)';
                card.style.opacity = '0';

                // Step 2: Collapse height smoothly
                setTimeout(() => {
                    // Lock the height explicitly to current height before collapsing
                    const currentHeight = card.offsetHeight;
                    card.style.height = currentHeight + 'px';

                    // Force reflow
                    card.offsetHeight;

                    // Now collapse everything
                    card.style.height = '0';
                    card.style.paddingTop = '0';
                    card.style.paddingBottom = '0';
                    card.style.marginTop = '0';
                    card.style.marginBottom = '0';
                    card.style.border = 'none';
                    card.style.overflow = 'hidden';

                    // Step 3: Remove from DOM / Hide completely
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 400); // Wait for collapse animation
                }, 400); // Wait for slide out animation
            }
        });
    });
});

