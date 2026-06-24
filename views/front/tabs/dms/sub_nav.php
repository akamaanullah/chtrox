<?php

use App\Core\View;

?>
<div class="sub-nav">
    <div class="sub-nav-header">
        <h2>DMS</h2>
        <a href="people" class="circle-btn" title="New Message" style="text-decoration: none;">
            <i data-lucide="edit"></i>
        </a>
    </div>

    <div class="search-box">
        <i data-lucide="search" size="18"></i>
        <input type="text" class="js-dm-sidebar-search" id="dmSidebarSearch" placeholder="Search dms..." aria-label="Search direct messages" autocomplete="off">
    </div>

    <div class="dms-directory">
        <h4 class="section-label">DMS DIRECTORY</h4>
        <div class="dm-list">
            <?php foreach ($dm_sidebar_items as $item): ?>
                <a href="dms/<?php echo htmlspecialchars($item['id']); ?>"
                    data-dm-username="<?php echo htmlspecialchars($item['id']); ?>"
                    data-conversation-id="<?php echo (int)($item['conversation_id'] ?? 0); ?>"
                    data-last-is-mine="<?php echo !empty($item['last_is_mine']) ? '1' : '0'; ?>"
                    data-last-read-status="<?php echo htmlspecialchars($item['read_status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    data-last-preview="<?php echo htmlspecialchars($item['preview']); ?>"
                    class="dm-item <?php echo ($active_with === $item['id']) ? 'active' : ''; ?>">
                    <div class="avatar-sm">
                        <img src="<?php echo htmlspecialchars($item['avatar']); ?>"
                            alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <span class="status-online"></span>
                    </div>
                    <div class="dm-info">
                        <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                        <div class="dm-msg">
                            <?php if (!empty($item['last_is_mine'])): ?>
                                <?php View::render('partials/chat/read-receipt.php', [
                                    'read_status' => $item['read_status'] ?? 'sent',
                                    'compact' => true,
                                ]); ?>
                            <?php endif; ?>
                            <span class="dm-msg-text"><?php echo htmlspecialchars($item['preview']); ?></span>
                        </div>
                    </div>
                    <div class="dm-right">
                        <span class="time"><?php echo htmlspecialchars($item['time']); ?></span>
                        <?php if (!empty($item['unread'])): ?>
                            <span class="unread-count"><?php echo (int) $item['unread']; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <p class="dm-sidebar-empty" id="dmSidebarEmpty" hidden>No conversations match your search</p>
    </div>
</div>
