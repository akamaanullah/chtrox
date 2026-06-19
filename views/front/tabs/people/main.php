<div class="content-inner people-page">
    <div class="files-page-header">
        <div class="aph-left">
            <div class="aph-icon-box">
                <i data-lucide="users" size="20"></i>
            </div>
            <div class="aph-titles">
                <span class="label-tiny text-primary">CORPORATE DIRECTORY</span>
                <h3>Connect with Colleagues</h3>
                <p class="aph-subtitle">Search through <?php echo (int) $people_count; ?> team members in Chatrox.</p>
            </div>
        </div>
        <div class="aph-right">
            <div class="search-box page-search-box">
                <i data-lucide="search" size="18"></i>
                <input type="text" placeholder="Search by name...">
            </div>
        </div>
    </div>

    <div class="directory-grid">
        <?php foreach ($people_contacts as $contact): ?>
            <div class="contact-card">
                <div class="cc-shape shape-1"></div>
                <div class="cc-shape shape-2"></div>
                <div class="cc-avatar">
                    <img src="<?php echo htmlspecialchars($contact['avatar']); ?>"
                        alt="<?php echo htmlspecialchars($contact['name']); ?>">
                    <span class="status-indicator <?php echo htmlspecialchars($contact['status']); ?>"></span>
                </div>
                <h3><?php echo htmlspecialchars($contact['name']); ?></h3>
                <span class="cc-role"><?php echo htmlspecialchars($contact['role']); ?></span>

                <div class="cc-details">
                    <div class="ccd-item">
                        <i data-lucide="mail" size="14"></i>
                        <span><?php echo htmlspecialchars($contact['email']); ?></span>
                    </div>
                </div>

                <button type="button" class="btn-chat">
                    <i data-lucide="message-square" size="14"></i>
                    Chat
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>
