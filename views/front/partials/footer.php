<?php

use App\Core\View;

View::render('partials/modals/create-channel-modal.php');
View::render('partials/panels/profile-panel.php');

$currentUser = \App\Core\Session::user();
?>
<script>
    window.CHATROX = <?php echo json_encode([
        'integrations' => $integrations ?? [],
        'user' => [
            'id' => $currentUser['id'] ?? null,
            'workspace_member_id' => $currentUser['workspace_member_id'] ?? null,
            'workspace_id' => $currentUser['workspace_id'] ?? null,
            'session_token' => $currentUser['session_token'] ?? null,
            'username' => $currentUser['username'] ?? null,
            'first_name' => $currentUser['first_name'] ?? null,
            'last_name' => $currentUser['last_name'] ?? null,
            'avatar' => $currentUser['avatar_path'] ?? null
        ],
        'baseUrl' => BASE_URL,
        'wsPort' => $_ENV['WS_PORT'] ?? 8080
    ], JSON_UNESCAPED_SLASHES); ?>;
</script>
<?php foreach ($page_scripts as $script): ?>
<script src="<?php echo View::asset($script); ?>" defer></script>
<?php endforeach; ?>
</body>

</html>
