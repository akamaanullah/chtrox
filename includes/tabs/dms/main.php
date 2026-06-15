<?php
if (!empty($_GET['with'])) {
    include __DIR__ . '/chat.php';
    return;
}
?>
<div class="dms-hero-area">
    <div class="grid-overlay"></div>

    <div class="hero-content">
        <div class="badge-pill">ENCRYPTED CONNECTION</div>
        <h1>Seamless <br> <span class="text-primary">Conversations.</span></h1>
        <p>The hub for your direct office communication. Fast, <br> secure, and intuitive for modern teams.</p>

        <div class="hero-actions">
            <a href="people" class="btn-dark">START NEW MESSAGE</a>
        </div>
    </div>

    <div class="connection-cards">
        <div class="dm-welcome-card dm-welcome-card--active bounce-in" role="button" tabindex="0">
            <div class="dm-welcome-card__shape"></div>
            <div class="dm-welcome-card__avatar">
                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150"
                    alt="Emma">
                <span class="dm-welcome-card__status"></span>
            </div>
            <h4 class="dm-welcome-card__name">Emma Williams</h4>
            <div class="dm-welcome-card__resume">
                <span>RESUME PROJECT THREAD</span>
                <i data-lucide="arrow-right" size="14"></i>
            </div>
        </div>

        <div class="dm-welcome-card bounce-in" style="animation-delay: 0.1s;" role="button" tabindex="0">
            <div class="dm-welcome-card__shape"></div>
            <div class="dm-welcome-card__avatar">
                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=150"
                    alt="Oliver">
                <span class="dm-welcome-card__status"></span>
            </div>
            <h4 class="dm-welcome-card__name">Oliver Mitchell</h4>
            <div class="dm-welcome-card__resume">
                <span>RESUME PROJECT THREAD</span>
                <i data-lucide="arrow-right" size="14"></i>
            </div>
        </div>
    </div>
</div>