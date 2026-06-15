<?php
// Chat screen - open when user clicks a Component (e.g. ?tab=channels&id=general)
$channel_id = isset($_GET['id']) ? trim($_GET['id']) : '';
$channels = [
    'general' => ['name' => '#general', 'avatar' => 'G', 'stat' => '3 TEAM MEMBERS ACTIVE'],
    'development-announcements' => ['name' => '#development-announcements', 'avatar' => 'DA', 'stat' => '2 TEAM MEMBERS ACTIVE'],
];
$active_channel = isset($channels[$channel_id]) ? $channels[$channel_id] : null;
if (!$active_channel) {
    $channel_id = 'general';
    $active_channel = $channels['general'];
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
    ['side' => 'them', 'sender' => 'Emma Williams', 'text' => 'The new API endpoints are live in staging. Let me know if you face any issues.', 'time' => '2:13 PM'],
    ['side' => 'me', 'text' => 'Awesome, I will run the integration tests in an hour.', 'time' => '2:12 PM'],
    ['side' => 'them', 'sender' => 'Oliver Mitchell', 'text' => 'Don\'t forget to update your local .env files with the new staging secrets.', 'time' => '2:10 PM'],
    ['side' => 'them', 'sender' => 'Charlotte Anderson', 'text' => 'Has the design team finalized the new dashboard mockups?', 'time' => '1:26 PM', 'edited' => true],
    ['side' => 'me', 'text' => 'Yes, I shared them in the #design channel this morning.', 'time' => '1:25 PM'],
    ['side' => 'them', 'sender' => 'Liam Carter', 'text' => 'Great. We\'ll start breaking down the tasks for the upcoming sprint.', 'time' => '1:22 PM'],
    ['side' => 'me', 'text' => 'I can take the lead on the frontend components for the dashboard.', 'time' => '1:08 PM'],
    ['side' => 'them', 'sender' => 'Noah Bennett', 'text' => 'Sounds like a plan. I\'ll work on the database schemas and migrations.', 'time' => '1:06 PM'],
    ['side' => 'me', 'text' => 'Do we need a quick sync later to align on the data models?', 'time' => '1:05 PM'],
    ['side' => 'them', 'sender' => 'Emma Williams', 'text' => 'We can discuss it during tomorrow\'s daily standup to save time.', 'time' => '1:02 PM'],
    ['side' => 'them', 'sender' => 'Oliver Mitchell', 'text' => 'Just pushed a hotfix for the login issue on production. Everyone please verify.', 'time' => '12:47 PM'],
    ['side' => 'me', 'text' => 'Thanks, it\'s working fine on my end now.', 'time' => '12:46 PM'],
    ['side' => 'them', 'sender' => 'Charlotte Anderson', 'text' => 'Can someone review PR #142? It includes the new notification service implementation.', 'time' => '12:28 PM'],
    ['side' => 'them', 'sender' => 'Liam Carter', 'text' => 'I\'ll take a look after lunch.', 'time' => '12:16 PM'],
    ['side' => 'me', 'text' => 'I already left a few comments regarding the error handling.', 'time' => '12:15 PM'],
    ['side' => 'them', 'sender' => 'Sophia Reynolds', 'text' => 'Thanks!', 'time' => '12:02 AM'],
    ['side' => 'me', 'text' => 'Sure, sending it now.', 'time' => '12:02 AM'],
    ['side' => 'them', 'sender' => 'Oliver Mitchell', 'text' => 'Can someone share the password_configs file? Devops needs to verify it.', 'time' => '12:01 AM'],
    ['side' => 'me', 'text' => 'Deployment is scheduled for 8 PM tonight. Please wrap up ongoing work.', 'time' => '11:18 AM'],
    ['side' => 'them', 'sender' => 'Noah Bennett', 'text' => 'Any downtime expected during the deployment?', 'time' => '11:17 AM'],
    ['side' => 'me', 'text' => 'Around 5 minutes, mostly for database migrations.', 'time' => '11:16 AM'],
    ['side' => 'them', 'sender' => 'Emily Chen', 'text' => 'Got it. I\'ll notify the customer success team.', 'time' => '11:15 AM'],
    ['side' => 'them', 'sender' => 'Liam Carter', 'text' => 'Welcome to the team, Michael! Excited to have you on board.', 'time' => '11:10 AM'],
    ['side' => 'me', 'text' => 'Welcome Michael! Feel free to ask if you need help setting up the environment.', 'time' => '11:11 AM'],
    ['side' => 'them', 'sender' => 'Emma Williams', 'text' => 'Reminder: The quarterly all-hands meeting is tomorrow at 10 AM.', 'time' => '10:45 AM'],
    ['side' => 'them', 'sender' => 'Oliver Mitchell', 'text' => 'Are there any updates on the AWS migration timeline?', 'time' => 'Yesterday 5:45 PM'],
    ['side' => 'me', 'text' => 'We are aiming for Q3, but still evaluating the cost implications.', 'time' => 'Yesterday 5:50 PM'],
    ['side' => 'them', 'sender' => 'Charlotte Anderson', 'text' => 'Makes sense. Let me know if you need help with the technical assessment.', 'time' => 'Yesterday 5:52 PM'],
    ['side' => 'me', 'text' => 'Will do. Have a great weekend team!', 'time' => 'Yesterday 5:55 PM'],
    ['side' => 'them', 'sender' => 'Sophia Reynolds', 'text' => 'You too!', 'time' => 'Yesterday 5:58 PM'],
];
$initial_visible = 20;
$name = htmlspecialchars($active_channel['name']);
?>
<div class="dm-chat-screen">
    <div class="dm-chat-header">
        <a href="channels" class="dm-chat-back" title="Back to Channels">
            <i data-lucide="arrow-left" size="20"></i>
        </a>
        <div class="dm-chat-header-user">
            <div class="dm-chat-header-avatar">
                <i data-lucide="hash" size="20"></i>
            </div>
            <div class="dm-chat-header-info">
                <h2 class="dm-chat-header-name"><?php echo $name; ?></h2>
                <span class="dm-chat-header-meta"><?php echo htmlspecialchars($active_channel['stat']); ?></span>
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
                        <span
                            class="dm-msg-sender"><?php echo htmlspecialchars(isset($m['sender']) ? $m['sender'] : 'User'); ?></span>
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
                <div class="dm-details-header-actions">
                    <button type="button" class="btn-edit-channel-small js-open-edit-channel"
                        title="Edit Channel Details & Members">
                        Edit <i data-lucide="edit-2" size="12"></i>
                    </button>
                    <button type="button" class="dm-details-close js-details-close" aria-label="Close">
                        <i data-lucide="x" size="16"></i>
                    </button>
                </div>
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
                            <i data-lucide="hash" size="40"></i>
                        </div>
                        <h3 class="dm-details-name"><?php echo $name; ?></h3>
                        <span class="dm-details-handle">WORKSPACE CHANNEL</span>
                        <div class="dm-details-bio-wrap">
                            <span class="dm-details-bio-label">CHANNEL PURPOSE</span>
                            <p class="dm-details-bio">Team-wide discussions and project alignment.</p>
                        </div>

                        <div class="channel-members-section">
                            <div class="cms-header">
                                <span class="dm-details-bio-label">MEMBERS (4)</span>
                            </div>
                            <div class="cms-list">
                                <!-- Member 1 -->
                                <div class="cms-item">
                                    <div class="cms-item-left">
                                        <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=150"
                                            alt="Oliver" class="cms-avatar">
                                        <div class="cms-info">
                                            <span class="cms-name">Oliver Mitchell</span>
                                            <span class="cms-role">Admin</span>
                                        </div>
                                    </div>
                                    <button class="btn-remove-member" title="Remove member"><i data-lucide="user-minus"
                                            size="14"></i></button>
                                </div>
                                <!-- Member 2 -->
                                <div class="cms-item">
                                    <div class="cms-item-left">
                                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150"
                                            alt="Emma" class="cms-avatar">
                                        <div class="cms-info">
                                            <span class="cms-name">Emma Williams</span>
                                            <span class="cms-role">Member</span>
                                        </div>
                                    </div>
                                    <button class="btn-remove-member" title="Remove member"><i data-lucide="user-minus"
                                            size="14"></i></button>
                                </div>
                                <!-- Member 3 -->
                                <div class="cms-item">
                                    <div class="cms-item-left">
                                        <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=150"
                                            alt="David" class="cms-avatar">
                                        <div class="cms-info">
                                            <span class="cms-name">David Chen</span>
                                            <span class="cms-role">Member</span>
                                        </div>
                                    </div>
                                    <button class="btn-remove-member" title="Remove member"><i data-lucide="user-minus"
                                            size="14"></i></button>
                                </div>
                                <!-- Member 4 -->
                                <div class="cms-item">
                                    <div class="cms-item-left">
                                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=150"
                                            alt="Sophia" class="cms-avatar">
                                        <div class="cms-info">
                                            <span class="cms-name">Sophia Taylor</span>
                                            <span class="cms-role">Member</span>
                                        </div>
                                    </div>
                                    <button class="btn-remove-member" title="Remove member"><i data-lucide="user-minus"
                                            size="14"></i></button>
                                </div>
                            </div>
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

<!-- Edit Channel Modal -->
<div class="modal-overlay" id="editChannelModal">
    <div class="modal-content modal-content--create-channel">
        <div class="cc-modal-header">
            <div class="cc-modal-titles">
                <h3>Edit Channel</h3>
                <span class="cc-modal-subtitle">UPDATE CHANNEL DETAILS & MEMBERS</span>
            </div>
            <button type="button" class="modal-close js-close-edit-channel-modal">
                <i data-lucide="x" size="20"></i>
            </button>
        </div>
        <div class="cc-modal-body custom-scrollbar">
            <form id="editChannelForm" class="cc-form">
                <div class="cc-field">
                    <label class="cc-label">CHANNEL NAME</label>
                    <div class="cc-input-wrap cc-input-wrap--hash">
                        <span class="cc-input-prefix">#</span>
                        <input type="text" name="channel_name" id="ecChannelName" value="general" required
                            maxlength="80" class="cc-input">
                    </div>
                </div>

                <div class="cc-field">
                    <label class="cc-label">CHANNEL PURPOSE</label>
                    <textarea name="channel_purpose" id="ecChannelPurpose"
                        class="edit-channel-textarea">Team-wide discussions and project alignment.</textarea>
                </div>

                <div class="cc-field" id="ecSpecificPeopleField">
                    <div class="cc-specific-header">
                        <label class="cc-label">MANAGE MEMBERS</label>
                        <span class="cc-selected-count" id="ecSelectedCount">4 members</span>
                    </div>
                    <div class="cc-search-wrap">
                        <i data-lucide="search" size="18"></i>
                        <input type="text" class="cc-search" placeholder="Search people..." id="ecSearchPeople">
                    </div>
                    <div class="cc-members-list custom-scrollbar" id="ecMembersList">
                        <!-- Existing Members -->
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Oliver Mitchell</span>
                                <span class="cc-member-handle">Admin</span>
                            </div>
                            <input type="checkbox" name="members[]" value="oliver" class="cc-member-check" checked
                                disabled>
                        </label>
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Emma Williams</span>
                                <span class="cc-member-handle">Member</span>
                            </div>
                            <input type="checkbox" name="members[]" value="emma" class="cc-member-check" checked>
                        </label>
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">David Chen</span>
                                <span class="cc-member-handle">Member</span>
                            </div>
                            <input type="checkbox" name="members[]" value="david" class="cc-member-check" checked>
                        </label>
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Sophia Taylor</span>
                                <span class="cc-member-handle">Member</span>
                            </div>
                            <input type="checkbox" name="members[]" value="sophia" class="cc-member-check" checked>
                        </label>
                        <!-- Other Workspace Members -->
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Charlotte Anderson</span>
                                <span class="cc-member-handle">@charlotteanderson</span>
                            </div>
                            <input type="checkbox" name="members[]" value="charlotte" class="cc-member-check">
                        </label>
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Liam Carter</span>
                                <span class="cc-member-handle">@liamcarter</span>
                            </div>
                            <input type="checkbox" name="members[]" value="liam" class="cc-member-check">
                        </label>
                    </div>
                </div>

                <button type="submit" class="cc-submit-btn" id="ecSubmitBtn">SAVE CHANGES</button>
            </form>
        </div>
    </div>
</div>