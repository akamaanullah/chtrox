<?php
$currentUserRole = \App\Core\Session::user()['workspace_role'] ?? 'member';
$canInvite = in_array($currentUserRole, ['owner', 'admin'], true);
?>
<div class="content-inner people-page">
    <div class="files-page-header">
        <div class="aph-left">
            <div class="aph-icon-box">
                <i data-lucide="users" size="20"></i>
            </div>
            <div class="aph-titles">
                <span class="label-tiny text-primary">CORPORATE DIRECTORY</span>
                <h3>Connect with Colleagues</h3>
                <p class="aph-subtitle">Search through <?php echo (int) $people_count; ?> team members in ChatRox.</p>
            </div>
        </div>
        <div class="aph-right">
            <div class="search-box page-search-box">
                <i data-lucide="search" size="18"></i>
                <input type="text" id="peopleSearch" placeholder="Search by name...">
            </div>
        </div>
    </div>

    <?php if (empty($people_contacts)): ?>
        <div class="people-empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 350px; padding: 40px; text-align: center; color: var(--text-slate); background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(10px); border-radius: 20px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04); margin-top: 20px;">
            <div style="background: rgba(79, 70, 229, 0.1); color: var(--indigo-600); padding: 24px; border-radius: 50%; margin-bottom: 24px; display: inline-flex; align-items: center; justify-content: center;">
                <i data-lucide="users-round" style="width: 48px; height: 48px;"></i>
            </div>
            <h2 style="font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">No Colleagues Found</h2>
            <p style="font-size: 15px; color: #64748b; max-width: 380px; margin-bottom: 24px; line-height: 1.5;">There are no other active members in this workspace.</p>
            <?php if ($canInvite): ?>
                <a href="<?php echo \App\Core\View::url('admin/members'); ?>" class="btn-dark" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; padding: 12px 24px; border-radius: 12px; background: var(--indigo-600); color: #fff; border: none; cursor: pointer;">
                    <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i> Invite Colleagues
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="directory-grid">
            <?php foreach ($people_contacts as $contact): ?>
                <div class="contact-card">
                    <div class="cc-shape shape-1"></div>
                    <div class="cc-shape shape-2"></div>
                    <a href="<?php echo \App\Core\View::url('dms/' . $contact['username']); ?>" style="text-decoration: none; color: inherit; display: block; margin-bottom: 12px;">
                        <div class="cc-avatar" style="margin: 0 auto 12px;">
                            <img src="<?php echo htmlspecialchars($contact['avatar']); ?>"
                                alt="<?php echo htmlspecialchars($contact['name']); ?>">
                            <span class="status-indicator <?php echo htmlspecialchars($contact['status']); ?>"></span>
                        </div>
                        <h3 style="margin: 0;"><?php echo htmlspecialchars($contact['name']); ?></h3>
                    </a>
                    <span class="cc-role"><?php echo htmlspecialchars($contact['role']); ?></span>

                    <div class="cc-details">
                        <div class="ccd-item">
                            <i data-lucide="mail" size="14"></i>
                            <span><?php echo htmlspecialchars($contact['email']); ?></span>
                        </div>
                    </div>

                    <a href="<?php echo \App\Core\View::url('dms/' . $contact['username']); ?>" class="btn-chat">
                        <i data-lucide="message-square" size="14"></i>
                        Chat
                    </a>
                </div>
            <?php endforeach; ?>

            <?php if (count($people_contacts) <= 1 && $canInvite): ?>
                <!-- Dotted invitation card for workspaces with only 1 member -->
                <a href="<?php echo \App\Core\View::url('admin/members'); ?>" class="contact-card invitation-card" style="border: 2px dashed var(--indigo-500); background: rgba(79, 70, 229, 0.03); display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 300px; cursor: pointer; text-decoration: none;">
                    <div class="cc-avatar" style="background: rgba(79, 70, 229, 0.1); color: var(--indigo-600); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <i data-lucide="user-plus" style="width: 28px; height: 28px;"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Invite Colleagues</h3>
                    <p style="font-size: 13px; color: #64748b; text-align: center; max-width: 200px; margin-bottom: 16px; line-height: 1.4;">Work is better together. Add your team members!</p>
                    <button type="button" class="btn-dark" style="padding: 8px 16px; font-size: 13px; border-radius: 8px; background: var(--indigo-600); color: #fff; border: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                        <i data-lucide="send" style="width: 12px; height: 12px;"></i> Send Invite
                    </button>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
