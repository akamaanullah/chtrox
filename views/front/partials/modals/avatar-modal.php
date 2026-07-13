<!-- Profile Picture Viewer Modal -->
<div id="avatarViewerModal" style="display: none; align-items: center; justify-content: center; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.92); backdrop-filter: blur(12px);">
    <div style="position: relative; max-width: 420px; width: 88%; display: flex; flex-direction: column; align-items: center;">
        <button type="button" id="avatarViewerClose" style="position: absolute; top: -50px; right: 0; background: rgba(255,255,255,0.12); border: none; color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 20px; line-height: 1; transition: background 0.2s;">✕</button>
        <div style="width: 100%; aspect-ratio: 1/1; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 48px rgba(0,0,0,0.5); border: 2px solid rgba(255,255,255,0.1); background: #1e293b;">
            <img id="avatarViewerImg" src="" alt="" style="width: 100%; height: 100%; object-fit: cover; display: block;">
        </div>
        <div id="avatarViewerName" style="color: #fff; font-size: 17px; font-weight: 700; margin-top: 16px; font-family: system-ui, -apple-system, sans-serif;"></div>
    </div>
</div>

<style>
@keyframes avatarZoomIn {
    from { transform: scale(0.88); opacity: 0; }
    to   { transform: scale(1);    opacity: 1; }
}
#avatarViewerModal > div { animation: avatarZoomIn 0.22s cubic-bezier(0.34,1.56,0.64,1) both; }
#avatarViewerClose:hover { background: rgba(255,255,255,0.25) !important; }
</style>

<script>
(function () {
    var modal   = document.getElementById('avatarViewerModal');
    var img     = document.getElementById('avatarViewerImg');
    var nameEl  = document.getElementById('avatarViewerName');
    var closeBtn = document.getElementById('avatarViewerClose');

    function openAvatar(src, name) {
        if (!modal) return;
        img.src = src || '';
        nameEl.textContent = name || '';
        modal.style.display = 'flex';
        // Re-trigger animation
        var inner = modal.firstElementChild;
        if (inner) { inner.style.animation = 'none'; inner.offsetHeight; inner.style.animation = ''; }
    }

    function closeAvatar() {
        if (modal) { modal.style.display = 'none'; }
        if (img)   { img.src = ''; }
    }

    if (closeBtn) closeBtn.addEventListener('click', closeAvatar);
    if (modal)    modal.addEventListener('click', function (e) { if (e.target === modal) closeAvatar(); });

    // ESC key to close
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'flex') closeAvatar();
    });

    // ---------------------------------------------------------------
    // Global capture-phase listener — fires BEFORE router's click handler
    // so we can preventDefault() reliably even for elements inside <a> tags.
    // ---------------------------------------------------------------
    document.addEventListener('click', function (e) {
        // --- Open avatar ---
        var trigger = e.target.closest('.js-open-avatar');
        if (trigger) {
            e.preventDefault();
            e.stopImmediatePropagation(); // block ALL other same-element listeners
            var src, name;
            if (trigger.tagName === 'IMG') {
                src  = trigger.getAttribute('data-src') || trigger.src;
                name = trigger.getAttribute('data-name') || trigger.alt;
            } else {
                src  = trigger.getAttribute('data-src');
                name = trigger.getAttribute('data-name');
                if (!src) {
                    var child = trigger.querySelector('img');
                    if (child) { src = child.src; name = name || child.alt; }
                }
            }
            if (src) openAvatar(src, name);
            return;
        }

        // --- DM details panel large avatar ---
        var detailsAvatar = e.target.closest('.dm-details-avatar');
        if (detailsAvatar) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var el = detailsAvatar.tagName === 'IMG' ? detailsAvatar : detailsAvatar.querySelector('img');
            if (el && el.src) openAvatar(el.src, el.alt);
            return;
        }
    }, true); // <-- capture: true — fires BEFORE bubble phase & BEFORE router

    // Expose globally so other scripts can call it
    window.openAvatarViewer = openAvatar;
    window.closeAvatarViewer = closeAvatar;
})();
</script>
