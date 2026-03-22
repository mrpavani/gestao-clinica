    <?php $jsVersion = @filemtime(__DIR__ . '/../../public/assets/js/ui-helper.js') ?: '1'; ?>
    <script src="public/assets/js/ui-helper.js?v=<?= $jsVersion ?>"></script>
    <?php
        if (isset($_SESSION['success_msg'])) {
            $msg = json_encode($_SESSION['success_msg']);
            echo "<script>document.addEventListener('DOMContentLoaded', () => UI.showToast($msg, 'success'));</script>";
            unset($_SESSION['success_msg']);
        }
        if (isset($_SESSION['error_msg'])) {
            $msg = json_encode($_SESSION['error_msg']);
            echo "<script>document.addEventListener('DOMContentLoaded', () => UI.showToast($msg, 'error'));</script>";
            unset($_SESSION['error_msg']);
        }
    ?>
</body>
</html>
