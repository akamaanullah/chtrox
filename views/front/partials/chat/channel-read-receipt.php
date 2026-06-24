<?php

$readCount = (int) ($channel_read['read_count'] ?? 0);
$memberCount = (int) ($channel_read['member_count'] ?? 0);
$readBy = $channel_read['read_by'] ?? [];
$notRead = $channel_read['not_read'] ?? [];
$avatarLimit = 2;
$readersJson = htmlspecialchars(json_encode($readBy), ENT_QUOTES, 'UTF-8');
$notReadJson = htmlspecialchars(json_encode($notRead), ENT_QUOTES, 'UTF-8');

$labelText = "Seen by {$readCount}";
if ($readCount === 0) {
    $labelText = "Sent";
} elseif ($readCount === $memberCount && $memberCount > 0) {
    $labelText = "Seen by all";
}
?>
<button type="button"
    class="ch-read-receipt js-channel-seen-by"
    data-readers="<?php echo $readersJson; ?>"
    data-not-read="<?php echo $notReadJson; ?>"
    data-read-count="<?php echo $readCount; ?>"
    data-member-count="<?php echo $memberCount; ?>"
    aria-label="<?php echo htmlspecialchars($labelText); ?>">
    <span class="ch-read-receipt-label"><?php echo htmlspecialchars($labelText); ?></span>
    <?php if (!empty($readBy)): ?>
        <span class="ch-read-receipt-avatars" aria-hidden="true">
            <?php foreach (array_slice($readBy, 0, $avatarLimit) as $reader): ?>
                <img src="<?php echo htmlspecialchars($reader['avatar']); ?>"
                    alt="<?php echo htmlspecialchars($reader['name']); ?>">
            <?php endforeach; ?>
            <?php if ($readCount > $avatarLimit): ?>
                <span class="ch-read-receipt-more">+<?php echo $readCount - $avatarLimit; ?></span>
            <?php endif; ?>
        </span>
    <?php endif; ?>
</button>
