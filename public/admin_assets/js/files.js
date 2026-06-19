document.addEventListener('DOMContentLoaded', () => {
    const fileSearch = document.getElementById('fileSearch');
    const filterPills = document.querySelectorAll('.filter-pill');
    const mediaSection = document.getElementById('mediaSection');
    const docsSection = document.getElementById('docsSection');
    const mediaItems = document.querySelectorAll('.media-item');
    const fileRows = document.querySelectorAll('.file-row');

    // Search and Filter Logic
    function applyFilters() {
        const searchTerm = fileSearch.value.toLowerCase();
        const activeFilter = document.querySelector('.filter-pill.active').dataset.filter;

        let visibleMediaCount = 0;
        let visibleDocsCount = 0;

        // Filter Media Grid
        mediaItems.forEach(item => {
            const fileName = item.querySelector('.file-name').textContent.toLowerCase();
            const category = item.dataset.category;
            const matchesSearch = fileName.includes(searchTerm);
            const matchesFilter = activeFilter === 'all' || 
                               (activeFilter === 'media' && (category === 'images' || category === 'videos')) || 
                               activeFilter === category;

            if (matchesSearch && matchesFilter) {
                item.style.display = 'block';
                visibleMediaCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Filter Docs Table
        fileRows.forEach(row => {
            const fileName = row.querySelector('.text-dark').textContent.toLowerCase();
            const category = row.dataset.category;
            const matchesSearch = fileName.includes(searchTerm);
            const matchesFilter = activeFilter === 'all' || 
                               (activeFilter === 'media' && (category === 'images' || category === 'videos')) || 
                               activeFilter === category;

            if (matchesSearch && matchesFilter) {
                row.style.display = 'table-row';
                visibleDocsCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Toggle Section Visibility based on content
        mediaSection.style.display = visibleMediaCount > 0 ? 'block' : 'none';
        docsSection.style.display = visibleDocsCount > 0 ? 'block' : 'none';
    }

    // Filter Pill Click
    filterPills.forEach(pill => {
        pill.addEventListener('click', () => {
            filterPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            applyFilters();
        });
    });

    // Search Input
    if (fileSearch) {
        fileSearch.addEventListener('input', applyFilters);
    }

    // Lightbox Implementation
    const lightbox = document.getElementById('mediaLightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    const lightboxVideo = document.getElementById('lightboxVideo');
    const lightboxThumbnails = document.getElementById('lightboxThumbnails');
    const prevBtn = document.getElementById('prevLightbox');
    const nextBtn = document.getElementById('nextLightbox');
    const closeBtn = document.getElementById('closeLightbox');
    const downloadBtn = document.getElementById('lightboxDownload');

    let currentMediaList = [];
    let currentIndex = 0;

    function openLightbox(index, list) {
        currentMediaList = list;
        currentIndex = index;
        updateLightboxContent();
        lightbox.classList.add('active');
        lightbox.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        if (!lightbox) return;
        lightbox.classList.remove('active');
        lightbox.setAttribute('hidden', '');
        document.body.style.overflow = '';
        if (lightboxVideo) {
            lightboxVideo.pause();
            lightboxVideo.src = '';
        }
    }

    function updateLightboxContent() {
        const item = currentMediaList[currentIndex];
        const isVideo = item.category === 'videos';

        if (isVideo) {
            if (lightboxImg) lightboxImg.setAttribute('hidden', '');
            if (lightboxVideo) {
                lightboxVideo.removeAttribute('hidden');
                lightboxVideo.src = item.src;
                lightboxVideo.load();
                lightboxVideo.play().catch(e => console.log("Auto-play blocked"));
            }
        } else {
            if (lightboxVideo) {
                lightboxVideo.setAttribute('hidden', '');
                lightboxVideo.pause();
                lightboxVideo.src = '';
            }
            if (lightboxImg) {
                lightboxImg.removeAttribute('hidden');
                lightboxImg.src = item.src;
            }
        }

        if (downloadBtn) {
            downloadBtn.href = item.src;
            downloadBtn.setAttribute('download', item.name);
        }

        // Update Thumbnails
        if (lightboxThumbnails) {
            lightboxThumbnails.innerHTML = '';
            currentMediaList.forEach((media, i) => {
                const thumb = document.createElement('img');
                thumb.src = media.src;
                thumb.className = `dm-msg-lightbox-thumb ${i === currentIndex ? 'active' : ''}`;
                thumb.addEventListener('click', (e) => {
                    e.stopPropagation();
                    currentIndex = i;
                    updateLightboxContent();
                });
                lightboxThumbnails.appendChild(thumb);
            });
        }

        // Update Nav Buttons
        if (prevBtn) {
            if (currentMediaList.length <= 1 || currentIndex === 0) {
                prevBtn.setAttribute('hidden', '');
            } else {
                prevBtn.removeAttribute('hidden');
            }
        }
        
        if (nextBtn) {
            if (currentMediaList.length <= 1 || currentIndex === currentMediaList.length - 1) {
                nextBtn.setAttribute('hidden', '');
            } else {
                nextBtn.removeAttribute('hidden');
            }
        }
    }

    // Global Click Listener for Actions and Previews
    document.addEventListener('click', (e) => {
        const actionBtn = e.target.closest('.action-btn');
        const mediaCard = e.target.closest('.media-item');
        
        if (actionBtn) {
            const fileName = actionBtn.closest('.file-card, .file-row').querySelector('.file-name, .text-dark').textContent;
            
            if (actionBtn.classList.contains('delete')) {
                window.ChatroxUtils.showToast(`${fileName} deleted successfully!`, 'success');
                return;
            }

            if (actionBtn.title === 'Preview') {
                const target = actionBtn.closest('.media-item');
                if (target) {
                    const allMedia = Array.from(document.querySelectorAll('.media-item'))
                        .filter(el => el.style.display !== 'none')
                        .map(el => ({
                            src: el.querySelector('img').src,
                            name: el.querySelector('.file-name').textContent,
                            category: el.dataset.category
                        }));
                    
                    const index = allMedia.findIndex(m => m.name === target.querySelector('.file-name').textContent);
                    if (index !== -1) {
                        openLightbox(index, allMedia);
                    }
                }
                return;
            }
            
            if (actionBtn.title === 'Download') {
                window.ChatroxUtils.showToast(`Starting download: ${fileName}`, 'info');
                return;
            }
        }

        // Click on the image card itself (outside buttons)
        if (mediaCard && !e.target.closest('.file-actions-overlay')) {
            const allMedia = Array.from(document.querySelectorAll('.media-item'))
                .filter(el => el.style.display !== 'none')
                .map(el => ({
                    src: el.querySelector('img').src,
                    name: el.querySelector('.file-name').textContent,
                    category: el.dataset.category
                }));
            
            const index = allMedia.findIndex(m => m.name === mediaCard.querySelector('.file-name').textContent);
            if (index !== -1) {
                openLightbox(index, allMedia);
            }
        }
    });

    if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
    if (lightbox) {
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) closeLightbox();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (currentIndex > 0) {
                currentIndex--;
                updateLightboxContent();
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (currentIndex < currentMediaList.length - 1) {
                currentIndex++;
                updateLightboxContent();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (lightbox && lightbox.classList.contains('active')) {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft' && !prevBtn.hasAttribute('hidden')) prevBtn.click();
            if (e.key === 'ArrowRight' && !nextBtn.hasAttribute('hidden')) nextBtn.click();
        }
    });

    // Initial Lucide Icons
    if (window.lucide) {
        window.lucide.createIcons();
    }
});
