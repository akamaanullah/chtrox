<?php

use App\Core\View;
?>
<div class="dm-chat-screen" data-conversation-id="<?php echo $active_channel['conversation_id']; ?>" data-channel-id="<?php echo $active_channel['id']; ?>">
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
                id="dm-msg-<?php echo $m['id']; ?>" data-msg-index="<?php echo $m['id']; ?>" <?php echo $i >= $initial_visible ? ' data-initially-hidden="1"' : ''; ?>>
                <div class="dm-msg-body">
                    <?php if ($m['side'] === 'them'): ?>
                        <span
                            class="dm-msg-sender"><?php echo htmlspecialchars(isset($m['sender']) ? $m['sender'] : 'User'); ?></span>
                    <?php endif; ?>
                    <?php
                    $attachments = $m['attachments'] ?? [];
                    $has_attachments = !empty($attachments);
                    $images = array_filter($attachments, function($a) { return $a['category'] === 'image'; });
                    $docs = array_filter($attachments, function($a) { return $a['category'] !== 'image'; });
                    $images = array_values($images);
                    $docs = array_values($docs);
                    ?>
                    <div class="dm-msg-bubble<?php echo ($has_attachments || $m['message_type'] === 'gif') ? ' dm-msg-bubble--media' : ''; ?>">
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
                        <?php elseif ($m['text']): ?>
                            <p><?php echo $m['text']; ?></p>
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

                        <?php if (!empty($docs)): ?>
                            <div class="dm-msg-files">
                                <?php foreach ($docs as $d): ?>
                                    <?php View::render('partials/chat/file-card.php', [
                                        'file_name' => $d['original_name'], 
                                        'file_size' => number_format($d['size_bytes'] / 1024, 2) . ' KB',
                                        'file_url' => $d['url']
                                    ]); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
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
                    <div class="dm-msg-meta">
                        <span class="dm-msg-time">
                            <?php if (!empty($m['edited'])): ?>
                                <span class="dm-msg-edited-label" style="font-size: 11px; margin-right: 4px;">(Edited)</span>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($m['time']); ?>
                        </span>
                        <?php if ($m['side'] === 'me' && !empty($m['channel_read'])): ?>
                            <?php View::render('partials/chat/channel-read-receipt.php', [
                                'channel_read' => $m['channel_read'],
                            ]); ?>
                        <?php endif; ?>
                    </div>
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
        <?php if (count($messages) > $initial_visible): ?>
        <div class="dm-load-more-wrap" id="dmLoadMoreWrap">
            <button type="button" class="dm-load-more js-dm-load-more" id="dmLoadMore">Read more</button>
        </div>
        <?php endif; ?>
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

<!-- Channel read-by modal -->
<div class="ch-read-overlay js-ch-read-overlay" id="chReadOverlay" hidden></div>
<div class="ch-read-modal" id="chReadModal" role="dialog" aria-labelledby="chReadModalTitle" aria-modal="true" hidden>
    <div class="ch-read-modal-inner">
        <div class="ch-read-modal-header">
            <h3 class="ch-read-modal-title" id="chReadModalTitle">Read by</h3>
            <button type="button" class="ch-read-modal-close js-ch-read-close" aria-label="Close">
                <i data-lucide="x" size="20"></i>
            </button>
        </div>
        <div class="ch-read-modal-body" id="chReadModalBody"></div>
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