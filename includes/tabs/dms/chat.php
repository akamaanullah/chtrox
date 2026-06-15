<?php
// Chat screen - open when user clicks a DM (e.g. ?tab=dms&with=emma)
$with_id = isset($_GET['with']) ? trim($_GET['with']) : '';
$dm_users = [
    'emma' => ['name' => 'Emma Williams', 'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150'],
    'oliver' => ['name' => 'Oliver Mitchell', 'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=150'],
];
$with_user = isset($dm_users[$with_id]) ? $dm_users[$with_id] : null;
if (!$with_user) {
    $with_id = 'emma';
    $with_user = $dm_users['emma'];
}

// Common mock data for media
$common_media = [
    'https://images.pexels.com/photos/1181675/pexels-photo-1181675.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/1181467/pexels-photo-1181467.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/1181354/pexels-photo-1181354.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/1181243/pexels-photo-1181243.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/1181208/pexels-photo-1181208.jpeg?auto=compress&cs=tinysrgb&w=800',
];

// Helper to render file cards consistently
function renderFileCard($name, $size)
{
    echo '
    <div class="dm-file-card">
        <div class="dm-file-icon">
            <i data-lucide="file" size="18"></i>
        </div>
        <div class="dm-file-main">
            <div class="dm-file-name">' . htmlspecialchars($name) . '</div>
            <div class="dm-file-meta">' . htmlspecialchars($size) . ' · FILE</div>
        </div>
        <button type="button" class="dm-file-download" aria-label="Download file">
            <i data-lucide="download" size="18"></i>
        </button>
    </div>';
}

// Messages: newest first (index 0 = latest). Show first 20; rest hidden until "Read more"
$messages = [
    ['side' => 'them', 'text' => 'Will do. Should have an update by end of day.', 'time' => '2:13 PM'],
    ['side' => 'me', 'text' => 'Understood. Keep me posted. I\'ll hold the marketing copy until we have a clear date.', 'time' => '2:12 PM'],
    ['side' => 'them', 'text' => 'Heads up – we might need to push the release by a day. Found an edge case in the payment flow. Discussing with the team.', 'time' => '2:10 PM'],
    ['side' => 'them', 'text' => 'Perfect. I\'ll loop them in. Thanks!', 'time' => '1:26 PM'],
    ['side' => 'me', 'text' => 'Handoff doc is in the shared drive. I\'ve tagged Emma and Charlotte. Review by Friday.', 'time' => '1:25 PM'],
    ['side' => 'them', 'text' => 'Appreciate it. The design team asked about the mobile components – any update on the handoff?', 'time' => '1:22 PM', 'edited' => true],
    ['side' => 'me', 'text' => 'I\'ll add you to the access list and send both in the next 10 mins.', 'time' => '1:08 PM'],
    ['side' => 'them', 'text' => 'Yes please, that would help. Also need access to the dev database for the migration test.', 'time' => '1:06 PM'],
    ['side' => 'me', 'text' => 'Staging is on the new flow since last week. I can share the config if you need it.', 'time' => '1:05 PM'],
    ['side' => 'them', 'text' => 'Quick question – are we using the new auth flow for the staging environment or still on the legacy one?', 'time' => '1:02 PM'],
    ['side' => 'me', 'text' => 'Sounds good. Let me know if you need anything else.', 'time' => '12:47 PM'],
    ['side' => 'them', 'text' => 'Got it, will address those and merge by EOD.', 'time' => '12:46 PM'],
    ['side' => 'me', 'text' => 'Just reviewed. Left a couple of comments on section 3. Rest looks good to go.', 'time' => '12:45 PM'],
    ['side' => 'them', 'text' => 'Heads up – the API integration docs need your sign-off before we ship. Can you take a look by 2 PM?', 'time' => '12:28 PM'],
    ['side' => 'them', 'text' => 'No problem. I\'ll join from the other call.', 'time' => '12:16 PM'],
    ['side' => 'me', 'text' => 'Meeting moved to 4 PM – conflict with the design review. Updated the invite.', 'time' => '12:15 PM'],
    ['side' => 'them', 'text' => 'Thanks!', 'time' => '12:02 AM'],
    ['side' => 'me', 'text' => 'Sure, sending it now.', 'time' => '12:02 AM'],
    ['side' => 'them', 'text' => 'Thanks! One more thing – can you share the password_configs file when you get a chance? Security review is pending.', 'time' => '12:01 AM'],
    ['side' => 'me', 'text' => 'On it. Will push the final version by 4 PM.', 'time' => '11:18 AM'],
    ['side' => 'them', 'text' => 'Perfect. I\'ll send a calendar invite. Also need the updated deck before EOD.', 'time' => '11:17 AM'],
    ['side' => 'me', 'text' => 'Yeah, after 2 PM works. Can we do 30 mins?', 'time' => '11:16 AM'],
    ['side' => 'them', 'text' => 'Hi, are you free for a quick sync on the Q4 roadmap?', 'time' => '11:15 AM'],
    ['side' => 'them', 'text' => 'Can you send the budget breakdown by tomorrow?', 'time' => '11:10 AM'],
    ['side' => 'me', 'text' => 'Will do. I\'ll have it in your inbox by 9 AM.', 'time' => '11:11 AM'],
    ['side' => 'them', 'text' => 'The client asked for a revised timeline. Can we move the demo to Thursday?', 'time' => '10:45 AM'],
    ['side' => 'me', 'text' => 'Let me check with the team. I\'ll confirm by noon.', 'time' => '10:50 AM'],
    ['side' => 'them', 'text' => 'Thanks. Also need the updated specs for the integration.', 'time' => '10:52 AM'],
    ['side' => 'me', 'text' => 'Attached in the last email. Let me know if you need anything else.', 'time' => '10:55 AM'],
    ['side' => 'them', 'text' => 'Got it. The QA team found a few bugs in the staging build.', 'time' => '10:58 AM'],
    ['side' => 'me', 'text' => 'I saw the ticket. We\'re fixing them today. Should be deployed by EOD.', 'time' => '11:00 AM'],
    ['side' => 'them', 'text' => 'Perfect. Keep me in the loop.', 'time' => '11:02 AM'],
    ['side' => 'me', 'text' => 'Will do. I\'ll ping you once it\'s live.', 'time' => '11:03 AM'],
    ['side' => 'them', 'text' => 'Reminder: all-hands at 3 PM. Can you share your slides by 2?', 'time' => '9:30 AM'],
    ['side' => 'me', 'text' => 'Slides are ready. Sending the link in the channel.', 'time' => '9:35 AM'],
    ['side' => 'them', 'text' => 'Great. HR wants the diversity report by Friday.', 'time' => '9:40 AM'],
    ['side' => 'me', 'text' => 'I\'ve requested the data from analytics. Should have it by Thursday.', 'time' => '9:45 AM'],
    ['side' => 'them', 'text' => 'Sounds good. Let\'s sync on the roadmap next week.', 'time' => '9:50 AM'],
    ['side' => 'me', 'text' => 'Sure. I\'ll block time on Tuesday.', 'time' => '9:52 AM'],
    ['side' => 'them', 'text' => 'The new hire starts Monday. Can you add them to the onboarding channel?', 'time' => '9:15 AM'],
    ['side' => 'me', 'text' => 'Done. I\'ve added them to #onboarding and #general.', 'time' => '9:20 AM'],
    ['side' => 'them', 'text' => 'Thanks. Don\'t forget the compliance training deadline is next week.', 'time' => '9:22 AM'],
    ['side' => 'me', 'text' => 'Already completed. I\'ll send the certificate to HR.', 'time' => '9:25 AM'],
    ['side' => 'them', 'text' => 'Nice. Can we do a quick standup at 10?', 'time' => '9:28 AM'],
    ['side' => 'me', 'text' => 'Yep, I\'ll be there.', 'time' => '9:29 AM'],
    ['side' => 'them', 'text' => 'The vendor contract is up for renewal. Need your sign-off by Wednesday.', 'time' => 'Yesterday 5:45 PM'],
    ['side' => 'me', 'text' => 'I\'ll review it tonight and send comments tomorrow.', 'time' => 'Yesterday 5:50 PM'],
    ['side' => 'them', 'text' => 'Appreciate it. Finance is asking for the Q3 forecast.', 'time' => 'Yesterday 5:52 PM'],
    ['side' => 'me', 'text' => 'Submitting it by EOD. Just waiting on the final numbers.', 'time' => 'Yesterday 5:55 PM'],
    ['side' => 'them', 'text' => 'Cool. Have a good evening.', 'time' => 'Yesterday 5:58 PM'],
    ['side' => 'me', 'text' => 'You too!', 'time' => 'Yesterday 6:00 PM'],
];
$initial_visible = 20;
$name = htmlspecialchars($with_user['name']);
?>
<div class="dm-chat-screen">
    <div class="dm-chat-header">
        <a href="dms" class="dm-chat-back" title="Back to DMs">
            <i data-lucide="arrow-left" size="20"></i>
        </a>
        <div class="dm-chat-header-user">
            <div class="dm-chat-header-avatar">
                <img src="<?php echo htmlspecialchars($with_user['avatar']); ?>" alt="">
                <span class="dm-chat-header-status"></span>
            </div>
            <div class="dm-chat-header-info">
                <h2 class="dm-chat-header-name"><?php echo $name; ?></h2>
                <span class="dm-chat-header-meta">Active now</span>
            </div>
        </div>
        <div class="dm-chat-header-actions">
            <div class="dm-chat-search">
                <i data-lucide="search" size="18"></i>
                <input type="text" class="dm-chat-search-input js-dm-chat-search" id="dmChatSearch"
                    placeholder="Search in chat..." aria-label="Search messages" autocomplete="off">
            </div>
            <button type="button" class="dm-chat-more js-chat-details-open" title="Details" aria-label="Chat details">
                <i data-lucide="more-horizontal" size="20"></i>
            </button>
        </div>
    </div>

    <!-- Drag & Drop Overlay -->
    <div class="dm-chat-drag-overlay" id="dmDragOverlay">
        <div class="dm-drag-icon-box">
            <i data-lucide="upload-cloud" size="32"></i>
        </div>
        <div class="dm-drag-text">Drop files to upload</div>
        <div class="dm-drag-subtext">Images and documents are supported</div>
    </div>

    <div class="dm-chat-messages" id="dmChatMessages">
        <?php foreach ($messages as $i => $m): ?>
            <div class="dm-chat-msg dm-chat-msg--<?php echo $m['side']; ?> <?php echo $i >= $initial_visible ? 'dm-chat-msg--hidden' : ''; ?>"
                id="dm-msg-<?php echo $i; ?>" data-msg-index="<?php echo $i; ?>" <?php echo $i >= $initial_visible ? ' data-initially-hidden="1"' : ''; ?>>
                <div class="dm-msg-body">
                    <?php if ($m['side'] === 'them'): ?>
                        <span class="dm-msg-sender"><?php echo $name; ?></span>
                    <?php endif; ?>
                    <?php
                    $is_media = ($m['side'] === 'them' && $i === 0) || // Image grid
                        ($m['side'] === 'them' && strpos($m['text'], 'password_configs file') !== false) ||
                        ($m['side'] === 'me' && $m['text'] === 'Sure, sending it now.');
                    ?>
                    <div class="dm-msg-bubble<?php echo $is_media ? ' dm-msg-bubble--media' : ''; ?>">
                        <?php if ($m['side'] === 'them' && $i === 0): ?>
                            <?php $grid_json = htmlspecialchars(json_encode($common_media), ENT_QUOTES, 'UTF-8'); ?>
                            <div class="dm-msg-images dm-msg-images--grid dm-msg-images--count-4"
                                data-lightbox-srcs="<?php echo $grid_json; ?>">
                                <img src="<?php echo htmlspecialchars($common_media[0]); ?>" alt=""
                                    class="dm-msg-img js-msg-img" loading="lazy" data-index="0">
                                <img src="<?php echo htmlspecialchars($common_media[1]); ?>" alt=""
                                    class="dm-msg-img js-msg-img" loading="lazy" data-index="1">
                                <img src="<?php echo htmlspecialchars($common_media[2]); ?>" alt=""
                                    class="dm-msg-img js-msg-img" loading="lazy" data-index="2">
                                <div class="dm-msg-grid-cell-wrap">
                                    <img src="<?php echo htmlspecialchars($common_media[3]); ?>" alt=""
                                        class="dm-msg-img js-msg-img" loading="lazy" data-index="3">
                                    <span class="dm-msg-grid-more">+1</span>
                                </div>
                            </div>
                        <?php elseif ($m['side'] === 'them' && strpos($m['text'], 'password_configs file') !== false): ?>
                            <div class="dm-msg-files">
                                <?php renderFileCard('password_configs.txt', '0.05 MB'); ?>
                            </div>
                        <?php elseif ($m['side'] === 'me' && $m['text'] === 'Sure, sending it now.'): ?>
                            <div class="dm-msg-files">
                                <?php renderFileCard('password_configs.txt', '0.05 MB'); ?>
                            </div>
                        <?php else: ?>
                            <p><?php echo htmlspecialchars($m['text']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="dm-msg-reactions"></div>
                    <span class="dm-msg-time">
                        <?php if (!empty($m['edited'])): ?>
                            <span class="dm-msg-edited-label" style="font-size: 11px; margin-right: 4px;">(Edited)</span>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($m['time']); ?>
                    </span>
                </div>
                <div class="dm-msg-actions" aria-label="Message actions">
                    <button type="button" class="dm-msg-action js-msg-react" title="Reaction" aria-label="Reaction"><i
                            data-lucide="smile-plus" size="16"></i></button>
                    <button type="button" class="dm-msg-action js-msg-reply" title="Reply" aria-label="Reply"><i
                            data-lucide="reply" size="16"></i></button>
                    <button type="button" class="dm-msg-action js-msg-pin" title="Pin" aria-label="Pin"><i data-lucide="pin"
                            size="16"></i></button>
                    <button type="button" class="dm-msg-action js-msg-forward" title="Forward" aria-label="Forward"><i
                            data-lucide="forward" size="16"></i></button>
                    <?php if ($m['side'] === 'me'): ?>
                        <span class="dm-msg-actions-sep" aria-hidden="true"></span>
                        <button type="button" class="dm-msg-action js-msg-edit" title="Edit Message"
                            aria-label="Edit Message"><i data-lucide="edit-2" size="16"></i></button>
                    <?php endif; ?>
                    <span class="dm-msg-actions-sep" aria-hidden="true"></span>
                    <button type="button" class="dm-msg-action dm-msg-action--delete js-msg-delete" title="Delete"
                        aria-label="Delete"><i data-lucide="trash-2" size="16"></i></button>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="dm-load-more-wrap" id="dmLoadMoreWrap">
            <button type="button" class="dm-load-more js-dm-load-more" id="dmLoadMore">Read more</button>
        </div>
    </div>

    <!-- Quick reaction picker (WhatsApp style: Heart, Thumb, Emoji) -->
    <div class="dm-reaction-picker" id="dmReactionPicker" role="dialog" aria-label="Quick reactions" hidden>
        <button type="button" class="dm-reaction-option" data-emoji="❤️" title="Heart" aria-label="Heart">❤️</button>
        <button type="button" class="dm-reaction-option" data-emoji="👍" title="Thumbs up"
            aria-label="Thumbs up">👍</button>
        <button type="button" class="dm-reaction-option" data-emoji="😂" title="Laugh" aria-label="Laugh">😂</button>
        <button type="button" class="dm-reaction-option" data-emoji="😮" title="Wow" aria-label="Wow">😮</button>
        <button type="button" class="dm-reaction-option" data-emoji="😢" title="Sad" aria-label="Sad">😢</button>
        <button type="button" class="dm-reaction-option" data-emoji="🙏" title="Thanks" aria-label="Thanks">🙏</button>
    </div>

    <!-- Forward message modal – search, people, groups, scroll, select count -->
    <div class="dm-forward-overlay js-forward-overlay" id="dmForwardOverlay" hidden></div>
    <div class="dm-forward-modal" id="dmForwardModal" role="dialog" aria-labelledby="dmForwardTitle" aria-modal="true"
        hidden>
        <div class="dm-forward-modal-inner">
            <div class="dm-forward-header">
                <h3 class="dm-forward-title" id="dmForwardTitle">Forward to</h3>
                <button type="button" class="dm-forward-close js-forward-close" aria-label="Close">
                    <i data-lucide="x" size="20"></i>
                </button>
            </div>
            <div class="dm-forward-search-wrap">
                <i data-lucide="search" size="18"></i>
                <input type="text" class="dm-forward-search js-forward-search" id="dmForwardSearch"
                    placeholder="Search people or groups..." aria-label="Search" autocomplete="off">
            </div>
            <div class="dm-forward-selected-count" id="dmForwardSelectedCount">0 selected</div>
            <div class="dm-forward-body">
                <div class="dm-forward-scroll" id="dmForwardScroll">
                    <div class="dm-forward-list" id="dmForwardList">
                        <label class="dm-forward-row js-forward-row" data-search="emma williams">
                            <input type="checkbox" name="forward_to[]" value="emma"
                                class="dm-forward-check js-forward-check">
                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150"
                                alt="" class="dm-forward-avatar">
                            <span class="dm-forward-name">Emma Williams</span>
                        </label>
                        <label class="dm-forward-row dm-forward-row--group js-forward-row" data-search="general">
                            <input type="checkbox" name="forward_to[]" value="general"
                                class="dm-forward-check js-forward-check">
                            <div class="dm-forward-avatar dm-forward-avatar--group">#</div>
                            <span class="dm-forward-name">#general</span>
                        </label>
                        <label class="dm-forward-row js-forward-row" data-search="oliver mitchell">
                            <input type="checkbox" name="forward_to[]" value="oliver"
                                class="dm-forward-check js-forward-check">
                            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=150"
                                alt="" class="dm-forward-avatar">
                            <span class="dm-forward-name">Oliver Mitchell</span>
                        </label>
                        <label class="dm-forward-row dm-forward-row--group js-forward-row" data-search="design huddle">
                            <input type="checkbox" name="forward_to[]" value="design-huddle"
                                class="dm-forward-check js-forward-check">
                            <div class="dm-forward-avatar dm-forward-avatar--group">#</div>
                            <span class="dm-forward-name">#design-huddle</span>
                        </label>
                        <label class="dm-forward-row js-forward-row" data-search="charlotte anderson">
                            <input type="checkbox" name="forward_to[]" value="charlotte"
                                class="dm-forward-check js-forward-check">
                            <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&q=80&w=150"
                                alt="" class="dm-forward-avatar">
                            <span class="dm-forward-name">Charlotte Anderson</span>
                        </label>
                        <label class="dm-forward-row dm-forward-row--group js-forward-row"
                            data-search="development announcements">
                            <input type="checkbox" name="forward_to[]" value="dev-announce"
                                class="dm-forward-check js-forward-check">
                            <div class="dm-forward-avatar dm-forward-avatar--group">#</div>
                            <span class="dm-forward-name">#development-announcements</span>
                        </label>
                        <label class="dm-forward-row js-forward-row" data-search="sophia reynolds">
                            <input type="checkbox" name="forward_to[]" value="sophia"
                                class="dm-forward-check js-forward-check">
                            <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=150"
                                alt="" class="dm-forward-avatar">
                            <span class="dm-forward-name">Sophia Reynolds</span>
                        </label>
                        <label class="dm-forward-row dm-forward-row--group js-forward-row" data-search="design team">
                            <input type="checkbox" name="forward_to[]" value="design-team"
                                class="dm-forward-check js-forward-check">
                            <div class="dm-forward-avatar dm-forward-avatar--group"><i data-lucide="users"
                                    size="18"></i></div>
                            <span class="dm-forward-name">Design Team</span>
                        </label>
                        <label class="dm-forward-row js-forward-row" data-search="liam carter">
                            <input type="checkbox" name="forward_to[]" value="liam"
                                class="dm-forward-check js-forward-check">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=150"
                                alt="" class="dm-forward-avatar">
                            <span class="dm-forward-name">Liam Carter</span>
                        </label>
                        <label class="dm-forward-row dm-forward-row--group js-forward-row" data-search="engineering">
                            <input type="checkbox" name="forward_to[]" value="engineering"
                                class="dm-forward-check js-forward-check">
                            <div class="dm-forward-avatar dm-forward-avatar--group"><i data-lucide="code" size="18"></i>
                            </div>
                            <span class="dm-forward-name">Engineering</span>
                        </label>
                        <label class="dm-forward-row js-forward-row" data-search="noah bennett">
                            <input type="checkbox" name="forward_to[]" value="noah"
                                class="dm-forward-check js-forward-check">
                            <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=150"
                                alt="" class="dm-forward-avatar">
                            <span class="dm-forward-name">Noah Bennett</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="dm-forward-footer">
                <button type="button" class="dm-forward-btn dm-forward-cancel js-forward-close">Cancel</button>
                <button type="button" class="dm-forward-btn dm-forward-submit js-forward-submit"
                    id="dmForwardSubmit">Forward</button>
            </div>
        </div>
    </div>

    <div class="dm-chat-input-wrap">
        <div class="dm-chat-toolbar" role="toolbar" aria-label="Formatting">
            <div class="dm-chat-toolbar-group">
                <button type="button" class="dm-chat-tool-btn" data-cmd="bold" title="Bold" aria-label="Bold"><i
                        data-lucide="bold" size="18"></i></button>
                <button type="button" class="dm-chat-tool-btn" data-cmd="italic" title="Italic" aria-label="Italic"><i
                        data-lucide="italic" size="18"></i></button>
                <button type="button" class="dm-chat-tool-btn" data-cmd="strikeThrough" title="Strikethrough"
                    aria-label="Strikethrough"><i data-lucide="strikethrough" size="18"></i></button>
            </div>
            <span class="dm-chat-toolbar-sep" aria-hidden="true"></span>
            <div class="dm-chat-toolbar-group">
                <button type="button" class="dm-chat-tool-btn" data-cmd="insertUnorderedList" title="Bullet list"
                    aria-label="Bullet list"><i data-lucide="list" size="18"></i></button>
                <button type="button" class="dm-chat-tool-btn" data-cmd="insertOrderedList" title="Numbered list"
                    aria-label="Numbered list"><i data-lucide="list-ordered" size="18"></i></button>
            </div>
            <span class="dm-chat-toolbar-sep" aria-hidden="true"></span>
            <div class="dm-chat-toolbar-group">
                <button type="button" class="dm-chat-tool-btn" data-cmd="justifyLeft" title="Align left"
                    aria-label="Align left"><i data-lucide="align-left" size="18"></i></button>
                <button type="button" class="dm-chat-tool-btn" data-cmd="justifyCenter" title="Align center"
                    aria-label="Align center"><i data-lucide="align-center" size="18"></i></button>
                <button type="button" class="dm-chat-tool-btn" data-cmd="justifyRight" title="Align right"
                    aria-label="Align right"><i data-lucide="align-right" size="18"></i></button>
            </div>
            <span class="dm-chat-toolbar-sep" aria-hidden="true"></span>
            <div class="dm-chat-toolbar-group dm-chat-toolbar-group--emoji">
                <button type="button" class="dm-chat-tool-btn dm-chat-tool-btn--media js-emoji-toggle"
                    data-action="emoji" title="Emoji" aria-label="Emoji" aria-expanded="false" aria-haspopup="true"><i
                        data-lucide="smile-plus" size="18"></i></button>
                <div class="dm-emoji-picker" id="dmEmojiPicker" role="dialog" aria-label="Emoji picker" hidden>
                    <div class="dm-emoji-picker-inner">
                        <?php
                        $emojis = ['😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊', '😇', '🙂', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '🤗', '🤔', '🤨', '😐', '😑', '😶', '🙄', '😏', '😣', '😥', '😮', '🤐', '😯', '😪', '😫', '👍', '👎', '👌', '✌️', '🤞', '🤟', '🤘', '🤙', '👋', '🤚', '🖐️', '✋', '🖖', '👏', '🙌', '💪', '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '🔥', '⭐', '🌟', '✨', '💫', '✅', '❌', '❗', '❓', '‼️', '💯', '😎', '🤓', '😢', '😭', '😤', '😡', '🥳', '😴', '🤢', '🥵', '🥶', '😱', '😳', '🥺', '🙃', '😛', '😜', '🤪', '😝', '🤑', '🤠', '🥴', '🙈', '🙉', '🙊', '💋', '💌', '💐', '🌸', '🌺', '🌻', '🍀', '🌹', '🥀', '🌷', '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🎉', '🎊', '🎈', '🎁', '🏆', '⚽', '🎯', '🎮', '📱', '💻', '🎵', '🎶', '☕', '🍕', '🍔', '🌮', '🍩', '🍪'];
                        foreach ($emojis as $emoji):
                            ?><button type="button" class="dm-emoji-btn"
                                data-emoji="<?php echo htmlspecialchars($emoji); ?>"><?php echo $emoji; ?></button><?php endforeach; ?>
                    </div>
                </div>
                <button type="button" class="dm-chat-tool-btn dm-chat-tool-btn--media js-gif-toggle" data-action="gif"
                    title="GIF" aria-label="GIF" aria-expanded="false" aria-haspopup="true"><i data-lucide="gift"
                        size="18"></i></button>
                <div class="dm-gif-picker" id="dmGifPicker" role="dialog" aria-label="GIF picker" hidden>
                    <div class="dm-gif-picker-header">
                        <div class="search-box">
                            <i data-lucide="search" size="14"></i>
                            <input type="text" class="dm-gif-search-input js-gif-search" placeholder="Search GIFs..."
                                aria-label="Search GIFs">
                        </div>
                    </div>
                    <div class="dm-gif-picker-inner" id="dmGifPickerResults">
                        <div class="dm-gif-loading">Loading trending GIFs...</div>
                    </div>
                </div>
                <button type="button" class="dm-chat-tool-btn dm-chat-tool-btn--media" data-action="attach"
                    title="Attach file" aria-label="Attach file"><i data-lucide="paperclip" size="18"></i></button>
                <button type="button" class="dm-chat-tool-btn dm-chat-tool-btn--media" data-action="voice"
                    title="Voice note" aria-label="Voice note"><i data-lucide="mic" size="18"></i></button>
            </div>
        </div>
        <form class="dm-chat-form" id="dmChatForm" action="#" method="post">
            <input type="file" id="dmChatFileInput" class="dm-chat-file-input" multiple accept="*/*"
                aria-label="Attach file" hidden>
            <div class="dm-chat-attached-wrap" id="dmChatAttachedWrap" hidden></div>
            <div class="dm-reply-preview" id="dmReplyPreview" hidden>
                <div class="dm-reply-preview-inner">
                    <span class="dm-reply-preview-label">Replying to</span>
                    <span class="dm-reply-preview-thumb-wrap" id="dmReplyPreviewThumbWrap" hidden><img
                            class="dm-reply-preview-thumb" id="dmReplyPreviewThumb" alt=""></span>
                    <span class="dm-reply-preview-text" id="dmReplyPreviewText"></span>
                    <button type="button" class="dm-reply-preview-cancel js-reply-cancel" aria-label="Cancel reply"><i
                            data-lucide="x" size="16"></i></button>
                </div>
            </div>
            <div class="dm-chat-input-area" id="dmChatInput" contenteditable="true" role="textbox" aria-multiline="true"
                aria-label="Type a message" data-placeholder="Type a message..."></div>
            <button type="submit" class="dm-chat-send" title="Send">
                <i data-lucide="send" size="18"></i>
            </button>
        </form>
    </div>

    <!-- Image lightbox – download top right with cross, thumbnails below image -->
    <div class="dm-msg-lightbox js-msg-lightbox" id="dmMsgLightbox" role="dialog" aria-label="View image" hidden>
        <div class="dm-msg-lightbox-header">
            <a href="#" class="dm-msg-lightbox-download js-msg-lightbox-download" download title="Download"
                aria-label="Download" hidden>
                <i data-lucide="download" size="20"></i>
            </a>
            <button type="button" class="dm-msg-lightbox-close js-msg-lightbox-close" aria-label="Close">
                <i data-lucide="x" size="24"></i>
            </button>
        </div>
        <button type="button" class="dm-msg-lightbox-prev js-msg-lightbox-prev" aria-label="Previous" title="Previous"
            hidden>
            <i data-lucide="chevron-left" size="28"></i>
        </button>
        <button type="button" class="dm-msg-lightbox-next js-msg-lightbox-next" aria-label="Next" title="Next" hidden>
            <i data-lucide="chevron-right" size="28"></i>
        </button>
        <div class="dm-msg-lightbox-content">
            <img src="" alt="" class="dm-msg-lightbox-img" id="dmMsgLightboxImg">
            <div class="dm-msg-lightbox-thumbnails" id="dmMsgLightboxThumbnails"></div>
        </div>
    </div>

    <!-- Chat Details panel (Profile, Media, Files, Pinned) -->
    <div class="dm-details-overlay js-details-overlay" id="dmDetailsOverlay" aria-hidden="true" hidden></div>
    <div class="dm-details-panel" id="dmDetailsPanel" role="dialog" aria-labelledby="dmDetailsTitle" aria-modal="true"
        hidden>
        <div class="dm-details-panel-inner">
            <div class="dm-details-header">
                <div class="dm-details-titles">
                    <h2 class="dm-details-title" id="dmDetailsTitle">Details</h2>
                    <span class="dm-details-subtitle">CONTACT DETAILS</span>
                </div>
                <button type="button" class="dm-details-close js-details-close" aria-label="Close">
                    <i data-lucide="x" size="20"></i>
                </button>
            </div>
            <div class="dm-details-tabs" role="tablist">
                <button type="button" class="dm-details-tab dm-details-tab--active" role="tab" aria-selected="true"
                    data-tab="profile" id="dmDetailsTabProfile">Profile</button>
                <button type="button" class="dm-details-tab" role="tab" aria-selected="false" data-tab="media"
                    id="dmDetailsTabMedia">Media</button>
                <button type="button" class="dm-details-tab" role="tab" aria-selected="false" data-tab="files"
                    id="dmDetailsTabFiles">Files</button>
                <button type="button" class="dm-details-tab" role="tab" aria-selected="false" data-tab="pinned"
                    id="dmDetailsTabPinned">Pinned</button>
            </div>
            <div class="dm-details-search-wrap dm-details-search-wrap--hidden" id="dmDetailsSearchWrap">
                <div class="search-box">
                    <i data-lucide="search" size="16"></i>
                    <input type="text" class="dm-details-search-input js-details-search" id="dmDetailsSearch"
                        placeholder="Search..." aria-label="Search items" autocomplete="off">
                </div>
            </div>
            <div class="dm-details-body">
                <div class="dm-details-content dm-details-content--profile" id="dmDetailsContentProfile"
                    role="tabpanel">
                    <div class="dm-details-profile">
                        <div class="dm-details-avatar-wrap">
                            <img src="<?php echo htmlspecialchars($with_user['avatar']); ?>" alt=""
                                class="dm-details-avatar">
                            <span class="dm-details-status"></span>
                        </div>
                        <h3 class="dm-details-name"><?php echo $name; ?></h3>
                        <span
                            class="dm-details-handle">@<?php echo strtoupper(str_replace(' ', '_', $with_user['name'])); ?></span>
                        <div class="dm-details-bio-wrap">
                            <span class="dm-details-bio-label">PROFESSIONAL BIO</span>
                            <p class="dm-details-bio">Project Manager | Bringing order to chaos.</p>
                        </div>
                    </div>
                </div>
                <div class="dm-details-content dm-details-content--hidden" id="dmDetailsContentMedia" role="tabpanel"
                    hidden>
                    <div class="dm-details-media-grid" id="dmDetailsMediaGrid">
                        <?php foreach ($common_media as $ms): ?>
                            <img src="<?php echo htmlspecialchars($ms); ?>" alt="" class="dm-details-media-thumb"
                                loading="lazy">
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="dm-details-content dm-details-content--hidden" id="dmDetailsContentFiles" role="tabpanel"
                    hidden>
                    <div class="dm-details-files-list">
                        <a href="#" class="dm-details-file-row" download>
                            <span class="dm-details-file-icon"><i data-lucide="file-text" size="18"></i></span>
                            <div class="dm-details-file-info">
                                <span class="dm-details-file-name">Project_Roadmap_2024.pdf</span>
                                <span class="dm-details-file-size">2.4 MB</span>
                            </div>
                            <i data-lucide="download" size="18" class="dm-details-file-dl"></i>
                        </a>
                        <a href="#" class="dm-details-file-row" download>
                            <span class="dm-details-file-icon"><i data-lucide="file-text" size="18"></i></span>
                            <div class="dm-details-file-info">
                                <span class="dm-details-file-name">password_configs.txt</span>
                                <span class="dm-details-file-size">0.05 MB</span>
                            </div>
                            <i data-lucide="download" size="18" class="dm-details-file-dl"></i>
                        </a>
                    </div>
                </div>
                <div class="dm-details-content dm-details-content--hidden" id="dmDetailsContentPinned" role="tabpanel"
                    hidden>
                    <div class="dm-details-pinned-list" id="dmDetailsPinnedList"></div>
                    <div class="dm-details-pinned-empty" id="dmDetailsPinnedEmpty">No pinned messages</div>
                </div>
            </div>
        </div>
    </div>
</div>