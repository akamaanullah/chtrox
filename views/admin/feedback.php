<header class="content-header">
    <div class="greeting-area">
        <div class="greeting-icon">
            <i data-lucide="message-square"></i>
        </div>
        <div class="greeting-text">
            <h1>Feedbacks & Reports</h1>
            <p class="date">Analyze and manage user comments, bug reports, and suggestions.</p>
        </div>
    </div>
</header>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">TOTAL REPORTS</span>
        <span class="stat-value"><?php echo $counts['all']; ?></span>
        <div class="stat-line blue"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">BUG REPORTS</span>
        <span class="stat-value"><?php echo $counts['bug']; ?></span>
        <div class="stat-line red"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">SUGGESTIONS</span>
        <span class="stat-value"><?php echo $counts['feature']; ?></span>
        <div class="stat-line orange"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">GENERAL FEEDBACK</span>
        <span class="stat-value"><?php echo $counts['feedback']; ?></span>
        <div class="stat-line green"></div>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="feedback-tabs-nav">
    <button class="tab-nav-btn active" data-target="all">
        <span>All</span>
        <span class="tab-count-badge"><?php echo $counts['all']; ?></span>
    </button>
    <button class="tab-nav-btn" data-target="bug">
        <span>Bugs</span>
        <span class="tab-count-badge"><?php echo $counts['bug']; ?></span>
    </button>
    <button class="tab-nav-btn" data-target="feature">
        <span>Suggestions</span>
        <span class="tab-count-badge"><?php echo $counts['feature']; ?></span>
    </button>
    <button class="tab-nav-btn" data-target="usability">
        <span>Usability</span>
        <span class="tab-count-badge"><?php echo $counts['usability']; ?></span>
    </button>
    <button class="tab-nav-btn" data-target="feedback">
        <span>General</span>
        <span class="tab-count-badge"><?php echo $counts['feedback']; ?></span>
    </button>
</div>

<!-- Tabs Content -->
<div class="feedback-tabs-content">
    <!-- Empty States -->
    <div id="empty-state-all" class="feedback-empty-state-wrapper" style="display: none;">
        <div class="feedback-empty-state">
            <div class="feedback-empty-icon"><i data-lucide="message-square-off"></i></div>
            <div>
                <h3 class="feedback-empty-title">No Feedback Found</h3>
                <p class="feedback-empty-sub">No feedback or reports have been submitted in this workspace.</p>
            </div>
        </div>
    </div>
    <div id="empty-state-bug" class="feedback-empty-state-wrapper" style="display: none;">
        <div class="feedback-empty-state">
            <div class="feedback-empty-icon"><i data-lucide="message-square-off"></i></div>
            <div>
                <h3 class="feedback-empty-title">No Bug Reports</h3>
                <p class="feedback-empty-sub">Great news! No bug reports are currently active.</p>
            </div>
        </div>
    </div>
    <div id="empty-state-feature" class="feedback-empty-state-wrapper" style="display: none;">
        <div class="feedback-empty-state">
            <div class="feedback-empty-icon"><i data-lucide="message-square-off"></i></div>
            <div>
                <h3 class="feedback-empty-title">No Suggestions Yet</h3>
                <p class="feedback-empty-sub">No feature requests have been submitted yet.</p>
            </div>
        </div>
    </div>
    <div id="empty-state-usability" class="feedback-empty-state-wrapper" style="display: none;">
        <div class="feedback-empty-state">
            <div class="feedback-empty-icon"><i data-lucide="message-square-off"></i></div>
            <div>
                <h3 class="feedback-empty-title">No Usability Issues</h3>
                <p class="feedback-empty-sub">No design or usability concerns reported.</p>
            </div>
        </div>
    </div>
    <div id="empty-state-feedback" class="feedback-empty-state-wrapper" style="display: none;">
        <div class="feedback-empty-state">
            <div class="feedback-empty-icon"><i data-lucide="message-square-off"></i></div>
            <div>
                <h3 class="feedback-empty-title">No General Feedback</h3>
                <p class="feedback-empty-sub">No general feedback or reviews available.</p>
            </div>
        </div>
    </div>

    <!-- Cards Wrapper -->
    <div id="feedback-cards-container" style="display: grid; grid-template-columns: 1fr; gap: 20px;">
        <?php foreach ($feedbacks as $item): ?>
            <div class="feedback-card" data-feedback-id="<?php echo (int)$item['id']; ?>" data-feedback-type="<?php echo \App\Core\View::e($item['type']); ?>">
                <div class="feedback-card-header">
                    <div class="feedback-user-info">
                        <img src="<?php echo !empty($item['avatar_path']) ? BASE_URL . '/' . $item['avatar_path'] : BASE_URL . '/assets/images/default-avatar.svg'; ?>" class="feedback-user-avatar">
                        <div class="feedback-user-details">
                            <h4><?php echo \App\Core\View::e($item['user_name']); ?></h4>
                            <span><?php echo \App\Core\View::e($item['email']); ?></span>
                        </div>
                    </div>
                    <div class="feedback-meta-info">
                        <span class="feedback-meta-date"><?php echo date('M d, Y h:i A', strtotime($item['created_at'])); ?></span>
                        <span class="tag-pill tag-<?php echo \App\Core\View::e($item['type']); ?>">
                            <?php 
                                if ($item['type'] === 'bug') echo 'Bug Report';
                                elseif ($item['type'] === 'feature') echo 'Suggestion';
                                elseif ($item['type'] === 'usability') echo 'Usability';
                                else echo 'General';
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="feedback-card-body">
                    <!-- Rating -->
                    <?php if (!empty($item['rating'])): ?>
                        <div class="feedback-rating-info">
                            <span class="feedback-rating-label">Experience Rating:</span>
                            <span class="feedback-rating-emoji" title="Rating: <?php echo $item['rating']; ?>/5">
                                <?php 
                                    $emojis = [1 => '😠', 2 => '🙁', 3 => '😐', 4 => '🙂', 5 => '😍'];
                                    echo $emojis[$item['rating']] ?? '';
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <h3 class="feedback-subject"><?php echo \App\Core\View::e($item['subject']); ?></h3>
                    <p class="feedback-message"><?php echo \App\Core\View::e($item['message']); ?></p>
                    
                    <div class="feedback-card-footer">
                        <div>
                            <?php if (!empty($item['attachment_path'])): ?>
                                <a href="<?php echo BASE_URL . '/' . $item['attachment_path']; ?>" target="_blank" class="feedback-attachment-link">
                                    <i data-lucide="paperclip"></i>
                                    Download Attachment
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <button class="feedback-delete-btn" onclick="deleteFeedback(<?php echo (int)$item['id']; ?>)" title="Delete Feedback">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination Controls -->
    <div id="pagination-controls-wrapper" style="display: none; justify-content: space-between; align-items: center; margin-top: 32px; padding-top: 20px; border-top: 1px solid var(--border-color); flex-wrap: wrap; gap: 16px;">
        <span id="pagination-status" style="font-size: 13px; color: var(--text-slate); font-weight: 500;">
            Showing page <strong id="current-page-num">1</strong> of <strong id="total-pages-num">1</strong> (Total: <span id="total-items-num">0</span> items)
        </span>
        
        <div style="display: flex; gap: 8px;">
            <button id="btn-prev-page" class="feedback-attachment-link" style="border: none; cursor: pointer; padding: 6px 12px; font-size: 13px; display: inline-flex; align-items: center; gap: 4px; outline: none;">
                <i data-lucide="chevron-left" style="width: 14px; height: 14px;"></i>
                Previous
            </button>
            <button id="btn-next-page" class="feedback-attachment-link" style="border: none; cursor: pointer; padding: 6px 12px; font-size: 13px; display: inline-flex; align-items: center; gap: 4px; outline: none;">
                Next
                <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
            </button>
        </div>
    </div>
</div>

<!-- Custom Confirmation Modal -->
<div id="deleteConfirmModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title-area">
                <div class="modal-icon" style="background: #fee2e2; color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.1);">
                    <i data-lucide="alert-triangle" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <h3>Delete Feedback</h3>
                    <p style="color: #ef4444; font-weight: 700; margin-top: 4px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Confirm Action</p>
                </div>
            </div>
            <button class="modal-close" id="closeDeleteModalBtn">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>
        <div class="modal-body" style="padding: 24px 30px;">
            <p style="font-size: 15px; color: #475569; line-height: 1.5; margin: 0;">Are you sure you want to delete this feedback report? This action cannot be undone and will permanently remove the record and its attachment from the disk.</p>
        </div>
        <div class="modal-footer" style="display: flex; gap: 12px; justify-content: flex-end; padding: 20px 30px 24px; border-top: 1px solid var(--border-color); background: #f8fafc;">
            <button type="button" class="feedback-attachment-link" id="cancelDeleteBtn" style="border: none; cursor: pointer; background: #e2e8f0; color: var(--text-slate); padding: 10px 18px; border-radius: 12px; font-weight: 700; font-size: 13px;">Cancel</button>
            <button type="button" class="feedback-attachment-link" id="confirmDeleteBtn" style="border: none; cursor: pointer; background: #ef4444; color: white; padding: 10px 18px; border-radius: 12px; font-weight: 700; font-size: 13px;">Delete</button>
        </div>
    </div>
</div>

<script>
    var cards = [];
    var pendingDeleteId = null;

    document.addEventListener('DOMContentLoaded', function () {
        var activeTab = 'all';
        var currentPage = 1;
        var itemsPerPage = 5;

        var tabBtns = document.querySelectorAll('.tab-nav-btn');
        cards = Array.from(document.querySelectorAll('.feedback-card'));
        var emptyStateWrappers = document.querySelectorAll('.feedback-empty-state-wrapper');
        var paginationControls = document.getElementById('pagination-controls-wrapper');
        var cardsContainer = document.getElementById('feedback-cards-container');

        // Modal Elements
        var deleteModal = document.getElementById('deleteConfirmModal');
        var closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');
        var cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

        function render() {
            // Filter cards based on active tab
            var filteredCards = cards.filter(function (card) {
                if (activeTab === 'all') return true;
                return card.getAttribute('data-feedback-type') === activeTab;
            });

            // Hide all empty state wrappers first
            emptyStateWrappers.forEach(function (wrapper) {
                wrapper.style.display = 'none';
            });

            if (filteredCards.length === 0) {
                cardsContainer.style.display = 'none';
                paginationControls.style.display = 'none';
                
                // Show corresponding empty state
                var emptyState = document.getElementById('empty-state-' + activeTab);
                if (emptyState) emptyState.style.display = 'block';
                return;
            }

            cardsContainer.style.display = 'grid';

            // Calculate pages
            var totalItems = filteredCards.length;
            var totalPages = Math.ceil(totalItems / itemsPerPage);

            // Adjust currentPage if out of bounds
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            var startIndex = (currentPage - 1) * itemsPerPage;
            var endIndex = startIndex + itemsPerPage;

            // Show/Hide cards
            cards.forEach(function (card) {
                card.style.display = 'none';
            });

            filteredCards.forEach(function (card, index) {
                if (index >= startIndex && index < endIndex) {
                    card.style.display = 'block';
                    card.style.opacity = '1';
                }
            });

            // Render pagination controls
            if (totalPages > 1) {
                paginationControls.style.display = 'flex';
                
                // Update labels
                document.getElementById('current-page-num').textContent = currentPage;
                document.getElementById('total-pages-num').textContent = totalPages;
                document.getElementById('total-items-num').textContent = totalItems;

                // Update buttons state
                var btnPrev = document.getElementById('btn-prev-page');
                var btnNext = document.getElementById('btn-next-page');

                if (currentPage === 1) {
                    btnPrev.style.opacity = '0.5';
                    btnPrev.style.pointerEvents = 'none';
                    btnPrev.style.background = '#e2e8f0';
                    btnPrev.style.color = '#94a3b8';
                } else {
                    btnPrev.style.opacity = '1';
                    btnPrev.style.pointerEvents = 'auto';
                    btnPrev.style.background = '';
                    btnPrev.style.color = '';
                }

                if (currentPage === totalPages) {
                    btnNext.style.opacity = '0.5';
                    btnNext.style.pointerEvents = 'none';
                    btnNext.style.background = '#e2e8f0';
                    btnNext.style.color = '#94a3b8';
                } else {
                    btnNext.style.opacity = '1';
                    btnNext.style.pointerEvents = 'auto';
                    btnNext.style.background = '';
                    btnNext.style.color = '';
                }
            } else {
                paginationControls.style.display = 'none';
            }
        }

        // Set up tab events
        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                tabBtns.forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');

                activeTab = btn.getAttribute('data-target');
                currentPage = 1;
                render();
            });
        });

        // Pagination buttons events
        document.getElementById('btn-prev-page').addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage--;
                render();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });

        document.getElementById('btn-next-page').addEventListener('click', function () {
            var filteredCards = cards.filter(function (card) {
                if (activeTab === 'all') return true;
                return card.getAttribute('data-feedback-type') === activeTab;
            });
            var totalPages = Math.ceil(filteredCards.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                render();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });

        // Modal Close triggers
        function closeModal() {
            deleteModal.classList.remove('active');
            pendingDeleteId = null;
        }

        closeDeleteModalBtn.addEventListener('click', closeModal);
        cancelDeleteBtn.addEventListener('click', closeModal);

        // Confirm Delete trigger
        confirmDeleteBtn.addEventListener('click', function() {
            if (!pendingDeleteId) return;
            executeDelete(pendingDeleteId);
            closeModal();
        });

        // Expose render function for deletion callback
        window.reRenderFeedbacks = function() {
            render();
        };

        // Initial render
        render();

        // Initialize Lucide icons
        if (window.lucide) {
            window.lucide.createIcons();
        }
    });

    function showToast(message, type = 'success') {
        var toast = document.createElement('div');
        toast.className = 'admin-toast ' + type;
        toast.style.position = 'fixed';
        toast.style.bottom = '24px';
        toast.style.right = '24px';
        toast.style.background = type === 'success' ? '#10b981' : '#ef4444';
        toast.style.color = '#ffffff';
        toast.style.padding = '12px 24px';
        toast.style.borderRadius = '12px';
        toast.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1)';
        toast.style.zIndex = '9999';
        toast.style.fontWeight = '600';
        toast.style.fontSize = '14px';
        toast.style.transition = 'all 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.textContent = message;

        document.body.appendChild(toast);

        setTimeout(function () {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        }, 10);

        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(function () {
                toast.remove();
            }, 300);
        }, 3000);
    }

    function updateCountsAfterDelete(deletedType) {
        // Update top stats and tab counts
        var tabBadgeAll = document.querySelector('.tab-nav-btn[data-target="all"] .tab-count-badge');
        var tabBadgeType = document.querySelector('.tab-nav-btn[data-target="' + deletedType + '"] .tab-count-badge');
        
        if (tabBadgeAll) tabBadgeAll.textContent = Math.max(0, parseInt(tabBadgeAll.textContent) - 1);
        if (tabBadgeType) tabBadgeType.textContent = Math.max(0, parseInt(tabBadgeType.textContent) - 1);

        // Stats cards
        var statValAll = document.querySelector('.stat-card:nth-child(1) .stat-value');
        if (statValAll) statValAll.textContent = Math.max(0, parseInt(statValAll.textContent) - 1);

        var typeMapIndex = {
            'bug': 2,
            'feature': 3,
            'feedback': 4
        };
        var index = typeMapIndex[deletedType];
        if (index) {
            var statValType = document.querySelector('.stat-card:nth-child(' + index + ') .stat-value');
            if (statValType) statValType.textContent = Math.max(0, parseInt(statValType.textContent) - 1);
        }
    }

    // Trigger delete confirmation modal
    function deleteFeedback(id) {
        pendingDeleteId = id;
        var deleteModal = document.getElementById('deleteConfirmModal');
        if (deleteModal) {
            deleteModal.classList.add('active');
        }
    }

    // Execute actual fetch delete
    function executeDelete(id) {
        var token = (window.CHATROX_ADMIN && window.CHATROX_ADMIN.csrfToken) ? window.CHATROX_ADMIN.csrfToken : '';

        fetch((window.CHATROX_ADMIN ? window.CHATROX_ADMIN.baseUrl : '') + '/api/admin/feedback', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ id: id })
        })
        .then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok) {
                    throw new Error(data.error || 'Failed to delete feedback');
                }
                return data;
            });
        })
        .then(function (data) {
            // Success! Remove card from DOM with fade transition
            var cardElement = document.querySelector('.feedback-card[data-feedback-id="' + id + '"]');
            if (cardElement) {
                cardElement.style.opacity = '0';
                cardElement.style.transform = 'scale(0.9)';
                setTimeout(function () {
                    // Update our local JS list
                    cards = cards.filter(function(c) {
                        return c !== cardElement;
                    });
                    
                    // Remove element from DOM
                    cardElement.remove();
                    
                    // Update counts
                    updateCountsAfterDelete(cardElement.getAttribute('data-feedback-type'));
                    
                    // Show success toast
                    showToast('Feedback report successfully deleted.');

                    // Re-render
                    if (window.reRenderFeedbacks) {
                        window.reRenderFeedbacks();
                    }
                }, 300);
            }
        })
        .catch(function (err) {
            showToast(err.message || 'Failed to delete feedback report.', 'error');
        });
    }
</script>
