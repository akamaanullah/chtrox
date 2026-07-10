function toggleAboutModal() {
    const modal = document.getElementById('aboutModal');
    if (modal) {
        modal.classList.toggle('active');
    }
}

function togglePrivacyModal() {
    const modal = document.getElementById('privacyModal');
    if (modal) {
        modal.classList.toggle('active');
    }
}

function toggleGuideModal() {
    const modal = document.getElementById('guideModal');
    if (modal) {
        modal.classList.toggle('active');
    }
}

function toggleSecurityModal() {
    const modal = document.getElementById('securityModal');
    if (modal) {
        modal.classList.toggle('active');
    }
}

function openAnnouncementModal(button) {
    if (!button) return;
    var modal = document.getElementById('announcementModal');
    if (!modal) return;

    var title = button.getAttribute('data-title') || 'Announcement';
    var body = button.getAttribute('data-body') || '';
    var tag = button.getAttribute('data-tag') || 'UPDATE';
    var tagClass = button.getAttribute('data-tag-class') || 'update';
    var postedBy = button.getAttribute('data-posted-by') || 'Workspace Admin';
    var postedAt = button.getAttribute('data-posted-at') || '';

    var iconMap = { IMPORTANT: '🚨', CELEBRATION: '🎂', UPDATE: '📢' };
    var iconEl = document.getElementById('announcementModalIcon');
    var titleEl = document.getElementById('announcementModalTitle');
    var bodyEl = document.getElementById('announcementModalBody');
    var tagEl = document.getElementById('announcementModalTag');
    var authorEl = document.getElementById('announcementModalAuthor');
    var dateEl = document.getElementById('announcementModalDate');
    var avatarEl = document.getElementById('announcementModalAvatar');

    if (iconEl) iconEl.textContent = iconMap[tag] || '📢';
    if (titleEl) titleEl.textContent = title;
    if (bodyEl) bodyEl.textContent = body;
    if (tagEl) {
        tagEl.textContent = tag;
        tagEl.className = 'tag ' + tagClass;
    }
    if (authorEl) authorEl.textContent = postedBy;
    if (dateEl) dateEl.textContent = postedAt;
    if (avatarEl) avatarEl.textContent = (postedBy.trim().charAt(0) || 'A').toUpperCase();

    modal.classList.add('active');
}

function closeAnnouncementModal() {
    var modal = document.getElementById('announcementModal');
    if (modal) modal.classList.remove('active');
}

function copyChromeFlagUrl(btn) {
    var text = 'chrome://flags/#unsafely-treat-insecure-origin-as-secure';
    
    // Check if clipboard API is supported
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text)
            .then(function() {
                showCopySuccess(btn);
            })
            .catch(function(err) {
                fallbackCopyText(text, btn);
            });
    } else {
        fallbackCopyText(text, btn);
    }
}

function showCopySuccess(btn) {
    var icon = btn.querySelector('i');
    if (icon) {
        icon.setAttribute('data-lucide', 'check');
        icon.style.color = '#10b981'; // Green success color
        if (window.lucide) window.lucide.createIcons({ nodes: [btn] });
        setTimeout(function() {
            icon.setAttribute('data-lucide', 'copy');
            icon.style.color = '';
            if (window.lucide) window.lucide.createIcons({ nodes: [btn] });
        }, 2000);
    }
}

function fallbackCopyText(text, btn) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        var successful = document.execCommand('copy');
        if (successful) {
            showCopySuccess(btn);
        }
    } catch (err) {
        console.error('Fallback copy failed', err);
    }
    document.body.removeChild(textArea);
}