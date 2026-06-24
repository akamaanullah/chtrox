<!-- Create Channel Modal - Premium UI -->
<div class="modal-overlay" id="createChannelModal">
    <div class="modal-content modal-content--create-channel">
        <div class="cc-modal-header">
            <div class="cc-modal-titles">
                <h3>Create Channel</h3>
                <span class="cc-modal-subtitle">START A NEW SPACE FOR YOUR TEAM</span>
            </div>
            <button type="button" class="modal-close js-close-create-channel-modal">
                <i data-lucide="x" size="20"></i>
            </button>
        </div>
        <div class="cc-modal-body custom-scrollbar">
            <form id="createChannelForm" class="cc-form">
                <div class="cc-field">
                    <label class="cc-label">CHANNEL NAME</label>
                    <div class="cc-input-wrap cc-input-wrap--hash">
                        <span class="cc-input-prefix">#</span>
                        <input type="text" name="channel_name" id="ccChannelName" placeholder="e.g. design-sprint"
                            required maxlength="80" class="cc-input">
                    </div>
                </div>

                <div class="cc-field">
                    <label class="cc-label">VISIBILITY</label>
                    <div class="cc-visibility-pills">
                        <label class="cc-pill cc-pill--public">
                            <input type="radio" name="visibility" value="public" checked>
                            <i data-lucide="megaphone" size="16"></i>
                            <span>PUBLIC</span>
                        </label>
                        <label class="cc-pill cc-pill--private">
                            <input type="radio" name="visibility" value="private">
                            <i data-lucide="lock" size="16"></i>
                            <span>PRIVATE</span>
                        </label>
                    </div>
                    <p class="cc-visibility-desc" id="visibilityDesc">Anyone in the workspace can find and join this
                        channel.</p>
                </div>

                <div class="cc-field cc-field--toggle">
                    <div class="cc-toggle-block">
                        <div class="cc-toggle-label-wrap">
                            <span class="cc-toggle-title">ADD ALL MEMBERS OF CHATROX</span>
                            <span class="cc-toggle-desc">Automatically add everyone in the company</span>
                        </div>
                        <label class="cc-toggle">
                            <input type="checkbox" name="add_all_members" id="addAllMembers">
                            <span class="cc-toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="cc-field" id="ccSpecificPeopleField">
                    <div class="cc-specific-header">
                        <label class="cc-label">ADD SPECIFIC PEOPLE</label>
                        <span class="cc-selected-count" id="selectedCount">0 selected</span>
                    </div>
                    <div class="cc-search-wrap">
                        <i data-lucide="search" size="18"></i>
                        <input type="text" class="cc-search" placeholder="Search people..." id="searchPeople">
                    </div>
                    <div class="cc-members-list custom-scrollbar" id="ccMembersList">
                        <?php 
                        $currentUserMemberId = (int)(\App\Core\Session::user()['workspace_member_id'] ?? 0);
                        if (!isset($workspace_members)) {
                            $workspace_members = \App\Models\ChannelConversation::getWorkspaceMembers();
                        }
                        foreach ($workspace_members as $m):
                            $mId = (int)$m['member_id'];
                            if ($mId === $currentUserMemberId) continue;
                            
                            $avatarUrl = $m['avatar_path'] ?: DEFAULT_AVATAR_URL;
                            $displayName = htmlspecialchars($m['first_name'] . ' ' . $m['last_name']);
                            $roleLabel = '@' . htmlspecialchars($m['username'] ?? 'member');
                        ?>
                        <label class="cc-member-row" data-member-name="<?php echo strtolower($displayName); ?>">
                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>"
                                alt="<?php echo $displayName; ?>" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name"><?php echo $displayName; ?></span>
                                <span class="cc-member-handle"><?php echo $roleLabel; ?></span>
                            </div>
                            <input type="checkbox" name="members[]" value="<?php echo $mId; ?>" class="cc-member-check">
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="cc-submit-btn" id="ccSubmitBtn" disabled>FINALIZE & CREATE CHANNEL</button>
            </form>
        </div>
    </div>
</div>