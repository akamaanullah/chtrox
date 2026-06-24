<?php
/** @var array<string, mixed> $audio */
use App\Helpers\FileTypeInfo;

$file_name = $file_name ?? 'File';
$file_size = $file_size ?? '';
$file_url = $file_url ?? '';
$mime_type = $mime_type ?? '';

$info = FileTypeInfo::get($file_name, $mime_type);
$extSlug = $info['extSlug'];
$extLabel = $info['extLabel'];
$type_label = $info['typeLabel'];
$lucide_icon = $info['lucideIcon'];
$iconCategory = $info['iconCategory'];
?>
<div class="dm-file-card">
    <div class="dm-file-icon dm-file-icon--cat-<?php echo htmlspecialchars($iconCategory); ?> dm-file-icon--<?php echo htmlspecialchars($extSlug); ?>" aria-hidden="true">
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
    <?php if (!empty($file_url)): ?>
        <a href="<?php echo htmlspecialchars($file_url); ?>" class="dm-file-download" download="<?php echo htmlspecialchars($file_name); ?>" aria-label="Download <?php echo htmlspecialchars($file_name); ?>">
            <i data-lucide="download" size="16"></i>
        </a>
    <?php else: ?>
        <button type="button" class="dm-file-download" aria-label="Download <?php echo htmlspecialchars($file_name); ?>">
            <i data-lucide="download" size="16"></i>
        </button>
    <?php endif; ?>
</div>
