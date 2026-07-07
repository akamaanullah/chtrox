<div class="content-inner channels-hero-bg">
    <div class="channels-hero">
        <div class="hero-hash-box">
            <i data-lucide="hash" size="64"></i>
            <span class="status-online-glow"></span>
        </div>
        <h1>Collective Intelligence</h1>
        <p>Channels are where the entire ChatRox team aligns, discusses projects, and shares breakthroughs.</p>
    </div>

    <div class="channels-grid">
        <?php foreach ($channel_hero_cards as $card): ?>
            <a href="<?php echo \App\Core\View::url('channels/' . $card['id']); ?>" class="channel-card">
                <div class="ch-icon-pill">
                    <i data-lucide="<?php echo htmlspecialchars($card['icon']); ?>" size="18"></i>
                </div>
                <h3><?php echo htmlspecialchars($card['name']); ?></h3>
                <span class="ch-stat"><?php echo htmlspecialchars($card['stat']); ?></span>
                <div class="ch-footer">
                    <span class="jump-in">JUMP IN</span>
                    <i data-lucide="chevron-right" size="14"></i>
                </div>
                <div class="bg-hash-overlay">#</div>
            </a>
        <?php endforeach; ?>

        <div class="channel-card dark js-open-create-channel-modal" role="button" tabindex="0"
            title="Create new channel">
            <div class="ch-icon-pill dark">
                <i data-lucide="plus" size="18"></i>
            </div>
            <h3>New Channel</h3>
            <span class="ch-stat">SCALE THE CULTURE</span>
            <div class="bg-hash-overlay">#</div>
        </div>
    </div>
</div>
