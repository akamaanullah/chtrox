/**
 * script.js
 * Global dashboard logic and shared utilities
 */

// --- Shared Utilities (Global) ---
window.ChatroxUtils = {
    // Modal Utilities
    closeModal: function (modal) {
        if (modal) modal.classList.remove('active');
    },

    // Update selected count for member lists
    updateSelectedCount: function (container, countId, checkClass) {
        const countEl = container.querySelector('#' + countId || '');
        if (!countEl) return;
        const checks = container.querySelectorAll(checkClass);
        let n = 0;
        checks.forEach(c => { if (c.checked) n++; });
        countEl.textContent = n + ' selected';
    },

    // Setup person search in lists
    setupPersonSearch: function (inputElement, listContainer) {
        if (!inputElement || !listContainer) return;
        inputElement.addEventListener('input', function () {
            const term = this.value.toLowerCase();
            const rows = listContainer.querySelectorAll('.cc-member-row');
            rows.forEach(row => {
                const name = row.querySelector('.cc-member-name').textContent.toLowerCase();
                const handle = row.querySelector('.cc-member-handle').textContent.toLowerCase();
                row.style.display = (name.includes(term) || handle.includes(term)) ? 'flex' : 'none';
            });
        });
    },

    // Generic Table Filter
    filterTable: function (rows, config) {
        if (!rows) return;
        // config: { searchInput, filters: [{ selector, value }] }
        const searchTerm = config.searchInput ? config.searchInput.value.toLowerCase() : '';

        rows.forEach(row => {
            let matchesSearch = true;
            if (searchTerm) {
                const text = row.textContent.toLowerCase();
                matchesSearch = text.includes(searchTerm);
            }

            let matchesFilters = true;
            if (config.filters) {
                config.filters.forEach(f => {
                    if (f.value === '') return;
                    const cell = row.querySelector(f.selector);
                    if (cell) {
                        const cellText = cell.textContent.toLowerCase();
                        if (cellText !== f.value.toLowerCase()) matchesFilters = false;
                    }
                });
            }

            row.style.display = (matchesSearch && matchesFilters) ? "" : "none";
        });

        // Handle No Results
        if (config.noResultsId) {
            const noResults = document.getElementById(config.noResultsId);
            if (noResults) {
                const hasVisible = Array.from(rows).some(r => r.style.display !== "none");
                noResults.style.display = hasVisible ? "none" : "flex";
            }
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    // Stat Cards Fade-in
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });

    console.log('Dashboard utilities initialized');
});
