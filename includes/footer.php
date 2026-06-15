<?php include __DIR__ . '/modals/create-channel-modal.php'; ?>
<?php include __DIR__ . '/panels/profile-panel.php'; ?>
<script src="script.js"></script>
<script src="includes/panels/js/profile.js"></script>
<script src="includes/modals/js/create-channel.js"></script>
<script src="includes/tabs/home/js/home-modals.js"></script>
<?php
// Since index.php already defines $active_tab and populates $_GET params, 
// we use the global $active_tab for script inclusion.
if ($active_tab === 'home') {
    echo '<script src="includes/tabs/home/js/clocks.js"></script>';
    echo '<script src="includes/tabs/home/js/focus-timer.js"></script>';
} elseif ($active_tab === 'channels' && !empty($_GET['id'])) {
    echo '<script src="includes/tabs/channels/js/chat.js"></script>';
} elseif ($active_tab === 'dms') {
    echo '<script src="includes/tabs/dms/js/chat.js"></script>';
} elseif ($active_tab === 'activity') {
    echo '<script src="includes/tabs/activity/js/activity.js"></script>';
}
?>
</body>

</html>