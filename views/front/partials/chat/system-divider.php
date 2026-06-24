<?php
/** @var int|string $id */
/** @var string $text */
/** @var string $created_at */
$createdAt = $created_at ?? '';
?>
<div class="dm-system-divider" id="dm-msg-<?php echo (int)$id; ?>" data-msg-index="<?php echo (int)$id; ?>" data-system-message="1" data-created-at="<?php echo htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?>">
    <span class="dm-system-divider-text"><?php echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); ?></span>
</div>
