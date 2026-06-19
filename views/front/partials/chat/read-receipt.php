<?php

$status = $read_status ?? 'sent';
$icon = $status === 'sent' ? 'check' : 'check-check';
$labels = [
    'sent' => 'Sent',
    'delivered' => 'Delivered',
    'read' => 'Seen',
];
$label = $labels[$status] ?? 'Sent';
?>
<span class="dm-read-receipt dm-read-receipt--<?php echo htmlspecialchars($status); ?>"
    data-read-status="<?php echo htmlspecialchars($status); ?>"
    title="<?php echo htmlspecialchars($label); ?>"
    aria-label="<?php echo htmlspecialchars($label); ?>">
    <i data-lucide="<?php echo htmlspecialchars($icon); ?>"></i>
    <span class="dm-read-receipt-label"><?php echo htmlspecialchars($label); ?></span>
</span>
