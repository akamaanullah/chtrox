<?php
/** @var array<string, mixed> $audio */
$duration = (int)($audio['duration_seconds'] ?? 0);
$durationLabel = $duration > 0
    ? sprintf('%d:%02d', (int)floor($duration / 60), $duration % 60)
    : '0:00';
$seed = (int)($audio['id'] ?? 1);
$barCount = 36;
?>
<div class="dm-msg-voice">
    <button type="button" class="dm-voice-play js-voice-play" aria-label="Play voice message">
        <i data-lucide="play" size="14"></i>
    </button>
    <div class="dm-voice-main">
        <div class="dm-voice-wave js-voice-wave" role="slider" aria-label="Voice message progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <div class="dm-voice-bars dm-voice-bars--base">
                <?php for ($i = 0; $i < $barCount; $i++):
                    $barHeight = 4 + (($i * 11 + $seed * 7) % 17);
                ?>
                    <span class="dm-voice-bar" style="height: <?php echo $barHeight; ?>px"></span>
                <?php endfor; ?>
            </div>
            <div class="dm-voice-progress-clip js-voice-progress-clip" style="width: 0%">
                <div class="dm-voice-bars dm-voice-bars--active">
                    <?php for ($i = 0; $i < $barCount; $i++):
                        $barHeight = 4 + (($i * 11 + $seed * 7) % 17);
                    ?>
                        <span class="dm-voice-bar" style="height: <?php echo $barHeight; ?>px"></span>
                    <?php endfor; ?>
                </div>
            </div>
            <span class="dm-voice-scrubber js-voice-scrubber" style="left: 0%"></span>
        </div>
        <span class="dm-voice-duration"><?php echo htmlspecialchars($durationLabel); ?></span>
    </div>
    <video class="dm-voice-audio" src="<?php echo htmlspecialchars($audio['url']); ?>" data-src="<?php echo htmlspecialchars($audio['url']); ?>" preload="metadata" playsinline<?php echo $duration > 0 ? ' data-duration="' . $duration . '"' : ''; ?>></video>
</div>
