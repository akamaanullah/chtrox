<?php

$file_name = $file_name ?? 'File';
$file_size = $file_size ?? '';
$extRaw = pathinfo($file_name, PATHINFO_EXTENSION);
$extSlug = strtolower(preg_replace('/[^a-z0-9]/', '', $extRaw ?: 'file'));
$extLabel = strtoupper($extRaw ?: 'FILE');
$typeLabels = [
    'txt' => 'Text Document',
    'pdf' => 'PDF Document',
    'doc' => 'Word Document',
    'docx' => 'Word Document',
    'xls' => 'Spreadsheet',
    'xlsx' => 'Spreadsheet',
    'csv' => 'Spreadsheet',
    'zip' => 'Archive',
    'rar' => 'Archive',
    'png' => 'Image',
    'jpg' => 'Image',
    'jpeg' => 'Image',
];
$iconMap = [
    'pdf' => 'file-text',
    'txt' => 'file-text',
    'doc' => 'file-text',
    'docx' => 'file-text',
    'xls' => 'file-spreadsheet',
    'xlsx' => 'file-spreadsheet',
    'csv' => 'file-spreadsheet',
    'zip' => 'archive',
    'rar' => 'archive',
    'png' => 'image',
    'jpg' => 'image',
    'jpeg' => 'image',
];
$type_label = $typeLabels[$extSlug] ?? 'File';
$lucide_icon = $iconMap[$extSlug] ?? 'file';
?>
<div class="dm-file-card">
    <div class="dm-file-icon dm-file-icon--<?php echo htmlspecialchars($extSlug); ?>" aria-hidden="true">
        <i data-lucide="<?php echo htmlspecialchars($lucide_icon); ?>" size="18"></i>
        <span class="dm-file-ext"><?php echo htmlspecialchars($extLabel); ?></span>
    </div>
    <div class="dm-file-body">
        <span class="dm-file-name" title="<?php echo htmlspecialchars($file_name); ?>"><?php echo htmlspecialchars($file_name); ?></span>
        <span class="dm-file-meta">
            <span class="dm-file-size"><?php echo htmlspecialchars($file_size); ?></span>
            <span class="dm-file-sep" aria-hidden="true">·</span>
            <span class="dm-file-type"><?php echo htmlspecialchars($type_label); ?></span>
        </span>
    </div>
    <button type="button" class="dm-file-download" aria-label="Download <?php echo htmlspecialchars($file_name); ?>">
        <i data-lucide="download" size="16"></i>
    </button>
</div>
