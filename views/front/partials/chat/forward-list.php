<?php

$forward_targets = $forward_targets ?? [];
?>
<div class="dm-forward-list" id="dmForwardList">
    <?php if (empty($forward_targets)): ?>
        <p class="dm-forward-empty">No people or channels available to forward to.</p>
    <?php else: ?>
        <?php foreach ($forward_targets as $target): ?>
            <?php
            $isChannel = ($target['type'] ?? '') === 'channel';
            $rowClass = 'dm-forward-row js-forward-row' . ($isChannel ? ' dm-forward-row--group' : '');
            $search = htmlspecialchars($target['search'] ?? '', ENT_QUOTES, 'UTF-8');
            $targetId = htmlspecialchars($target['id'] ?? '', ENT_QUOTES, 'UTF-8');
            $targetType = htmlspecialchars($target['type'] ?? 'dm', ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($target['label'] ?? '', ENT_QUOTES, 'UTF-8');
            ?>
            <label class="<?php echo $rowClass; ?>" data-search="<?php echo $search; ?>">
                <input type="checkbox"
                    name="forward_to[]"
                    value="<?php echo $targetId; ?>"
                    class="dm-forward-check js-forward-check"
                    data-type="<?php echo $targetType; ?>"
                    data-id="<?php echo $targetId; ?>">
                <?php if ($isChannel): ?>
                    <div class="dm-forward-avatar dm-forward-avatar--group">#</div>
                <?php else: ?>
                    <img src="<?php echo htmlspecialchars($target['avatar'] ?? ''); ?>"
                        alt=""
                        class="dm-forward-avatar">
                <?php endif; ?>
                <span class="dm-forward-name"><?php echo $label; ?></span>
            </label>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
