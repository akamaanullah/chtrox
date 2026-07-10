<?php

use App\Core\View;
use App\Helpers\MessageDateDivider;

$contact_profile = $contact_profile ?? [
    'handle' => '@user',
    'bio' => 'No bio added yet.',
    'is_online' => false,
    'presence_label' => 'Offline',
];
$conversation_media = $conversation_media ?? [];
$conversation_files = $conversation_files ?? [];
$has_older_messages = !empty($has_older_messages);
$oldest_message_id = (int)($oldest_message_id ?? 0);
?>
<div class="dm-chat-screen" data-conversation-id="<?php echo $conversation_id; ?>" data-with-username="<?php echo htmlspecialchars($with_id); ?>" data-with-member-id="<?php echo $with_user['id']; ?>" data-has-older="<?php echo $has_older_messages ? '1' : '0'; ?>" data-oldest-message-id="<?php echo $oldest_message_id; ?>">
    <?php if (empty($with_user['id'])): ?>
        <div class="dm-chat-empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; padding: 40px; text-align: center; color: var(--text-slate); background: #f8fafc;">
            <div style="background: rgba(79, 70, 229, 0.1); color: var(--indigo-600); padding: 24px; border-radius: 50%; margin-bottom: 24px; display: inline-flex; align-items: center; justify-content: center;">
                <i data-lucide="users" style="width: 48px; height: 48px;"></i>
            </div>
            <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">No Users Available</h2>
            <p style="font-size: 15px; color: #64748b; max-width: 320px; margin-bottom: 24px; line-height: 1.5;">You are currently the only active member in this workspace. Invite colleagues to start direct messaging.</p>
            <a href="<?php echo \App\Core\View::url('home'); ?>" class="btn-dark" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; padding: 12px 24px; border-radius: 12px; background: var(--indigo-600); color: #fff;">
                <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i> Invite Members
            </a>
        </div>
    <?php else: ?>
    <div class="dm-chat-header">
        <a href="dms" class="dm-chat-back" title="Back to DMs">
            <i data-lucide="arrow-left" size="20"></i>
        </a>
        <div class="dm-chat-header-user js-chat-details-open" data-member-id="<?php echo $with_user['id']; ?>" style="cursor: pointer;">
            <div class="dm-chat-header-avatar">
                <img src="<?php echo htmlspecialchars($with_user['avatar']); ?>" alt="">
                <span class="presence-dot dm-chat-header-status dm-chat-header-status--<?php echo htmlspecialchars($contact_profile['presence_status'] ?? 'offline'); ?>"></span>
            </div>
            <div class="dm-chat-header-info">
                <h2 class="dm-chat-header-name"><?php echo $name; ?></h2>
                <span class="dm-chat-header-meta presence-label" data-member-id="<?php echo $with_user['id']; ?>"><?php echo htmlspecialchars($contact_profile['presence_label']); ?></span>
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
        <div class="dm-load-more-wrap dm-load-more-wrap--hidden" id="dmLoadNewerWrap" style="margin-bottom: 8px;">
            <button type="button" class="dm-load-more js-dm-load-newer" id="dmLoadNewer">Load newer messages</button>
        </div>
        <?php foreach ($messages as $i => $m): ?>
            <div class="dm-chat-msg dm-chat-msg--<?php echo $m['side']; ?> <?php echo $i >= $initial_visible ? 'dm-chat-msg--hidden' : ''; ?><?php echo !empty($m['deleted_for_everyone']) ? ' dm-chat-msg--deleted-everyone' : ''; ?><?php echo !empty($m['is_pinned']) ? ' dm-chat-msg--pinned' : ''; ?>"
                id="dm-msg-<?php echo $m['id']; ?>" data-msg-index="<?php echo $m['id']; ?>" data-created-at="<?php echo htmlspecialchars($m['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo $i >= $initial_visible ? ' data-initially-hidden="1"' : ''; ?><?php echo !empty($m['deleted_for_everyone']) ? ' data-deleted-everyone="1"' : ''; ?><?php echo !empty($m['is_pinned']) ? ' data-pinned="1"' : ''; ?>>
                <div class="dm-msg-body">
                    <?php if ($m['side'] === 'them' && empty($m['deleted_for_everyone'])): ?>
                        <span class="dm-msg-sender"><?php echo $name; ?></span>
                    <?php endif; ?>
                    <?php if (!empty($m['deleted_for_everyone'])): ?>
                        <?php View::render('partials/chat/deleted-message-bubble.php'); ?>
                    <?php else: ?>
                    <?php
                    $attachments = $m['attachments'] ?? [];
                    $has_attachments = !empty($attachments);
                    $images = array_filter($attachments, function($a) { return $a['category'] === 'image'; });
                    $docs = array_filter($attachments, function($a) { return !in_array($a['category'], ['image', 'audio'], true); });
                    $audioFiles = array_filter($attachments, function($a) { return $a['category'] === 'audio'; });
                    $images = array_values($images);
                    $docs = array_values($docs);
                    $audioFiles = array_values($audioFiles);
                    ?>
                    <div class="dm-msg-bubble<?php echo ($has_attachments || $m['message_type'] === 'gif' || $m['message_type'] === 'voice') ? ' dm-msg-bubble--media' : ''; ?>">
                        <?php if (!empty($m['is_forwarded'])): ?>
                            <?php View::render('partials/chat/forward-label.php'); ?>
                        <?php endif; ?>
                        <?php if (!empty($m['reply_to_id'])): ?>
                            <div class="dm-msg-reply-wrap" data-reply-to-id="dm-msg-<?php echo $m['reply_to_id']; ?>">
                                <div class="dm-msg-reply-preview"><?php echo htmlspecialchars($m['reply_snippet'] ?? 'Replying...'); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($m['message_type'] === 'gif'): ?>
                            <div class="dm-msg-images dm-msg-images--single">
                                <img src="<?php echo htmlspecialchars($m['text']); ?>" alt="" class="dm-msg-img js-msg-img" loading="lazy">
                            </div>
                        <?php elseif ($m['message_type'] === 'voice'): ?>
                            <?php
                            $voiceAudio = null;
                            foreach ($attachments as $att) {
                                if (($att['category'] ?? '') !== 'image') {
                                    $voiceAudio = $att;
                                    break;
                                }
                            }
                            if ($voiceAudio):
                                $voiceAudio['duration_seconds'] = (int)($m['voice_duration_seconds'] ?? 0);
                                View::render('partials/chat/voice-player.php', ['audio' => $voiceAudio]);
                            endif;
                            ?>
                        <?php elseif ($m['text']): ?>
                            <?php 
                            $cleaned = \App\Helpers\HtmlSanitizer::clean($m['text']);
                            if (strpos($cleaned, '<pre>') !== false || strpos($cleaned, '<p>') === 0) {
                                echo $cleaned;
                            } else {
                                echo '<p>' . $cleaned . '</p>';
                            }
                            ?>
                        <?php endif; ?>

                        <?php if (!empty($images)): ?>
                            <?php if (count($images) === 1): ?>
                                <div class="dm-msg-images dm-msg-images--single">
                                    <img src="<?php echo htmlspecialchars($images[0]['url']); ?>" alt="" class="dm-msg-img js-msg-img" loading="lazy">
                                </div>
                            <?php else: ?>
                                <?php 
                                $img_urls = array_map(function($img) { return $img['url']; }, $images);
                                $grid_json = htmlspecialchars(json_encode($img_urls), ENT_QUOTES, 'UTF-8');
                                $showCount = count($images) > 4 ? 4 : count($images);
                                $moreCount = count($images) > 4 ? count($images) - 4 : 0;
                                ?>
                                <div class="dm-msg-images dm-msg-images--grid dm-msg-images--count-<?php echo $showCount; ?>"
                                    data-lightbox-srcs="<?php echo $grid_json; ?>">
                                    <?php for ($idx = 0; $idx < $showCount; $idx++): ?>
                                        <?php if ($moreCount > 0 && $idx === 3): ?>
                                            <div class="dm-msg-grid-cell-wrap">
                                                <img src="<?php echo htmlspecialchars($images[$idx]['url']); ?>" alt="" class="dm-msg-img js-msg-img" loading="lazy" data-index="<?php echo $idx; ?>">
                                                <span class="dm-msg-grid-more">+<?php echo $moreCount; ?></span>
                                            </div>
                                        <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($images[$idx]['url']); ?>" alt="" class="dm-msg-img js-msg-img" loading="lazy" data-index="<?php echo $idx; ?>">
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!empty($docs) && ($m['message_type'] ?? '') !== 'voice'): ?>
                            <div class="dm-msg-files">
                                <?php foreach ($docs as $d): ?>
                                    <?php View::render('partials/chat/file-card.php', [
                                        'file_name' => $d['original_name'],
                                        'file_size' => \App\Helpers\FileUploadPolicy::formatSize((int)$d['size_bytes']),
                                        'file_url' => $d['url'],
                                        'mime_type' => $d['mime_type'] ?? '',
                                    ]); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="dm-msg-reactions">
                        <?php if (!empty($m['reactions'])): ?>
                            <?php foreach ($m['reactions'] as $r): ?>
                                <span class="dm-reaction-bubble<?php echo !empty($r['reacted']) ? ' dm-reaction-bubble--active' : ''; ?>" data-emoji="<?php echo htmlspecialchars($r['emoji']); ?>">
                                    <span class="dm-reaction-emoji"><?php echo htmlspecialchars($r['emoji']); ?></span>
                                    <span class="dm-reaction-count"><?php echo htmlspecialchars($r['count']); ?></span>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <span class="dm-msg-time">
                        <?php if (!empty($m['edited'])): ?>
                            <span class="dm-msg-edited-label" style="font-size: 11px; margin-right: 4px;">(Edited)</span>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($m['time']); ?>
                        <?php if ($m['side'] === 'me'): ?>
                            <?php View::render('partials/chat/read-receipt.php', [
                                'read_status' => $m['read_status'] ?? 'sent',
                            ]); ?>
                        <?php endif; ?>
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
            <?php MessageDateDivider::maybeRenderAfter($messages, $i, $initial_visible ?? 999999); ?>
        <?php endforeach; ?>
        <?php if (count($messages) > $initial_visible || $has_older_messages): ?>
        <div class="dm-load-more-wrap" id="dmLoadMoreWrap">
            <button type="button" class="dm-load-more js-dm-load-more" id="dmLoadMore">Load older messages</button>
        </div>
        <?php endif; ?>
        <div class="dm-chat-search-empty-state" id="dmChatSearchEmptyState" style="display: none; flex-direction: column; align-items: center; justify-content: center; padding: 40px 24px; text-align: center; color: #94a3b8; flex-shrink: 0; margin: auto;">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 12px; color: #94a3b8; opacity: 0.7;">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.3-4.3"></path>
                <path d="m15 9-6 6"></path>
                <path d="m9 9 6 6"></path>
            </svg>
            <div style="font-weight: 600; font-size: 15px; color: #64748b;">No matches in visible messages</div>
            <div style="font-size: 13px; margin-top: 6px; max-width: 280px; line-height: 1.4;">Use the dropdown below the search input to check the complete conversation history.</div>
        </div>
    </div>

    <div class="dm-voice-permission-notice" id="dmVoicePermissionNotice" hidden>
        <div class="dm-voice-permission-notice__text">
            <strong>Microphone access needed</strong>
            <p id="dmVoicePermissionMessage">Allow microphone access to record voice notes.</p>
        </div>
        <button type="button" class="dm-voice-permission-retry js-voice-permission-retry">Allow microphone</button>
        <button type="button" class="dm-voice-permission-dismiss js-voice-permission-dismiss" aria-label="Dismiss">
            <i data-lucide="x" size="16"></i>
        </button>
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

    <div class="reaction-overlay" id="dmReactionOverlay" hidden></div>
    <div class="reaction-modal" id="dmReactionModal" role="dialog" aria-labelledby="dmReactionModalTitle" aria-modal="true" hidden>
        <div class="reaction-modal-inner">
            <div class="reaction-modal-header">
                <div class="reaction-modal-title" id="dmReactionModalTitle">Reactions</div>
                <button type="button" class="reaction-modal-close js-dm-reaction-close" aria-label="Close">
                    <i data-lucide="x" size="20"></i>
                </button>
            </div>
            <div class="reaction-modal-body" id="dmReactionModalBody"></div>
        </div>
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
                    <?php View::render('partials/chat/forward-list.php', ['forward_targets' => $forward_targets ?? []]); ?>
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
                <button type="button" class="dm-chat-tool-btn dm-chat-tool-btn--media js-voice-toggle" data-action="voice"
                    title="Voice note" aria-label="Voice note" aria-pressed="false"><i data-lucide="mic" size="18"></i></button>
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
            <div class="dm-voice-recorder" id="dmVoiceRecorder" hidden>
                <button type="button" class="dm-voice-recording-cancel js-voice-recording-cancel" aria-label="Cancel recording">
                    <i data-lucide="trash-2" size="18"></i>
                </button>
                <span class="dm-voice-recording-dot" aria-hidden="true"></span>
                <span class="dm-voice-recording-time" id="dmVoiceRecordingTime">0:00</span>
                <canvas class="dm-voice-waveform" id="dmVoiceWaveform" aria-hidden="true"></canvas>
            </div>
            <button type="submit" class="dm-chat-send" id="dmChatSend" title="Send">
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
                        <div class="dm-details-avatar-wrap" data-member-id="<?php echo $with_user['id']; ?>">
                            <img src="<?php echo htmlspecialchars($with_user['avatar']); ?>" alt=""
                                class="dm-details-avatar">
                            <span class="presence-dot dm-details-status dm-details-status--<?php echo htmlspecialchars($contact_profile['presence_status'] ?? 'offline'); ?>"></span>
                        </div>
                        <h3 class="dm-details-name"><?php echo $name; ?></h3>
                        <span class="dm-details-handle"><?php echo htmlspecialchars($contact_profile['handle']); ?></span>
                        <div class="dm-details-bio-wrap">
                            <span class="dm-details-bio-label">PROFESSIONAL BIO</span>
                            <p class="dm-details-bio"><?php echo htmlspecialchars($contact_profile['bio']); ?></p>
                        </div>
                        <p class="dm-details-presence presence-label" data-member-id="<?php echo $with_user['id']; ?>"><?php echo htmlspecialchars($contact_profile['presence_label']); ?></p>
                    </div>
                </div>
                <div class="dm-details-content dm-details-content--hidden" id="dmDetailsContentMedia" role="tabpanel"
                    hidden>
                    <div class="dm-details-media-grid" id="dmDetailsMediaGrid">
                        <?php $mediaCount = 0; ?>
                        <?php foreach ($conversation_media as $item): ?>
                            <?php $mediaCount++; ?>
                            <button type="button" class="dm-details-media-thumb-btn js-details-media-jump<?php echo $mediaCount > 21 ? ' dm-details-media-thumb--hidden' : ''; ?>"
                                data-message-id="<?php echo (int)$item['message_id']; ?>"
                                aria-label="<?php echo htmlspecialchars($item['label']); ?>">
                                <img src="<?php echo htmlspecialchars($item['url']); ?>" alt="<?php echo htmlspecialchars($item['label']); ?>"
                                    class="dm-details-media-thumb" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($conversation_media) > 21): ?>
                        <div class="dm-details-media-more-wrap" id="dmDetailsMediaMoreWrap" style="text-align: center; margin-top: 16px; padding-bottom: 16px;">
                            <button type="button" class="profile-panel-btn profile-panel-btn--secondary js-details-media-load-more" style="width: 100%; margin: 0; padding: 10px; font-size: 13px;">Load More</button>
                        </div>
                    <?php endif; ?>
                    <div class="dm-details-empty<?php echo empty($conversation_media) ? ' dm-details-empty--show' : ''; ?>" id="dmDetailsMediaEmpty"<?php echo !empty($conversation_media) ? ' hidden' : ''; ?>>No media shared yet</div>
                </div>
                <div class="dm-details-content dm-details-content--hidden" id="dmDetailsContentFiles" role="tabpanel"
                    hidden>
                    <div class="dm-details-files-list" id="dmDetailsFilesList">
                        <?php foreach ($conversation_files as $file): ?>
                            <div class="dm-details-file-row">
                                <a href="<?php echo htmlspecialchars($file['url']); ?>" target="_blank" class="dm-details-file-link" title="View file">
                                    <span class="dm-details-file-icon"><i data-lucide="file-text" size="18"></i></span>
                                    <div class="dm-details-file-info">
                                        <span class="dm-details-file-name"><?php echo htmlspecialchars($file['name']); ?></span>
                                        <span class="dm-details-file-size"><?php echo htmlspecialchars($file['size_label']); ?></span>
                                    </div>
                                </a>
                                <div class="dm-details-file-actions">
                                    <a href="<?php echo htmlspecialchars($file['url']); ?>" target="_blank" class="dm-details-file-action" title="View"><i data-lucide="eye" size="14"></i></a>
                                    <a href="<?php echo htmlspecialchars($file['url']); ?>" download class="dm-details-file-action" title="Download"><i data-lucide="download" size="14"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="dm-details-empty<?php echo empty($conversation_files) ? ' dm-details-empty--show' : ''; ?>" id="dmDetailsFilesEmpty"<?php echo !empty($conversation_files) ? ' hidden' : ''; ?>>No files shared yet</div>
                </div>
                <div class="dm-details-content dm-details-content--hidden" id="dmDetailsContentPinned" role="tabpanel"
                    hidden>
                    <div class="dm-details-pinned-list" id="dmDetailsPinnedList"></div>
                    <div class="dm-details-pinned-empty" id="dmDetailsPinnedEmpty">No pinned messages</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>