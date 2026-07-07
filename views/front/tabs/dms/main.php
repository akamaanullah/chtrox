<div class="dms-hero-area">
    <div class="grid-overlay"></div>

    <div class="hero-content">
        <div class="badge-pill">ENCRYPTED CONNECTION</div>
        <h1>Seamless <span class="text-primary">Conversations.</span></h1>
        <p>The hub for your direct office communication. Fast, secure, and intuitive for modern teams.</p>

        <div class="hero-actions">
            <a href="people" class="btn-dark">START NEW MESSAGE</a>
        </div>
    </div>

    <div class="connection-cards">
        <?php foreach ($dm_welcome_cards as $i => $card): ?>
            <a href="<?php echo \App\Core\View::url('dms/' . $card['username']); ?>" class="dm-welcome-card <?php echo $i === 0 ? 'dm-welcome-card--active bounce-in' : 'bounce-in'; ?>"
                style="text-decoration: none; color: inherit;" role="button" tabindex="0">
                <div class="dm-welcome-card__shape"></div>
                <div class="dm-welcome-card__avatar">
                    <img src="<?php echo htmlspecialchars($card['avatar']); ?>"
                        alt="<?php echo htmlspecialchars($card['name']); ?>">
                    <span class="dm-welcome-card__status status-<?php echo htmlspecialchars($card['presence_status'] ?? 'offline'); ?>"></span>
                </div>
                <h4 class="dm-welcome-card__name"><?php echo htmlspecialchars($card['name']); ?></h4>
                <div class="dm-welcome-card__resume">
                    <span>RESUME PROJECT THREAD</span>
                    <i data-lucide="arrow-right" size="14"></i>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
