<?php

$status = $read_status ?? 'sent';
$icon = $status === 'sent' ? 'check' : 'check-check';
$labels = [
    'sent' => 'Sent',
    'delivered' => 'Delivered',
    'read' => 'Seen',
];
$label = $labels[$status] ?? 'Sent';
$compact = !empty($compact);
$classes = 'dm-read-receipt dm-read-receipt--' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
if ($compact) {
    $classes .= ' dm-read-receipt--compact';
}
?>
<span class="<?php echo $classes; ?>"
    data-read-status="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>"
    title="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>"
    aria-label="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>">
    <i data-lucide="<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?>"></i>
    <span class="dm-read-receipt-label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
</span>
