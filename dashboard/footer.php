</main>
</div>

<script src="js/script.js"></script>
<?php if (basename($_SERVER['PHP_SELF']) == 'profile.php'): ?>
    <script src="js/profile.js"></script>
<?php endif; ?>
<?php if (basename($_SERVER['PHP_SELF']) == 'analytics.php'): ?>
    <script src="js/analytics.js"></script>
<?php endif; ?>
<?php if (basename($_SERVER['PHP_SELF']) == 'activity.php'): ?>
    <script src="js/activity.js"></script>
<?php endif; ?>
<?php if (basename($_SERVER['PHP_SELF']) == 'channels.php'): ?>
    <script src="js/channels.js"></script>
<?php endif; ?>
<script>
    // Re-initialize Lucide icons if they were loaded dynamically (though here they are static)
    lucide.createIcons();
</script>
</body>

</html>