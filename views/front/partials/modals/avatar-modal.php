<!-- Profile Picture Viewer Modal -->
<div class="modal-overlay modal-overlay--avatar" id="avatarViewerModal" style="display: none; align-items: center; justify-content: center; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); transition: all 0.3s ease;">
    <div class="avatar-viewer-container" style="position: relative; max-width: 450px; width: 90%; display: flex; flex-direction: column; align-items: center; animation: zoomIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);">
        <button type="button" class="avatar-viewer-close" id="avatarViewerClose" style="position: absolute; top: -52px; right: 0; background: rgba(255, 255, 255, 0.1); border: none; color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
            <i data-lucide="x" style="width: 24px; height: 24px;"></i>
        </button>
        <div style="width: 100%; aspect-ratio: 1; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.55); border: 2px solid rgba(255, 255, 255, 0.12); background: #1e293b; display: flex; align-items: center; justify-content: center;">
            <img id="avatarViewerImg" src="" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div id="avatarViewerName" style="color: #fff; font-size: 18px; font-weight: 700; margin-top: 18px; text-shadow: 0 2px 8px rgba(0,0,0,0.6); font-family: system-ui, -apple-system, sans-serif; letter-spacing: -0.3px;">Profile Picture</div>
    </div>
</div>

<style>
@keyframes zoomIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
#avatarViewerClose:hover {
    background: rgba(255, 255, 255, 0.2) !important;
    transform: rotate(90deg);
}
</style>
