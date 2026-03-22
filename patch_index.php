<?php
$content = file_get_contents('index.php');

$search1 = <<<STRING
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
STRING;

$replace1 = <<<STRING
    \$hideNavPages = ['login', 'select_branch', 'forgot_password', 'reset_password'];
?>
<?php if (!in_array(\$page, \$hideNavPages)): ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
STRING;

$content = str_replace($search1, $replace1, $content);

$search2 = <<<STRING
    <?php \$hideNavPages = ['login', 'select_branch', 'forgot_password', 'reset_password']; ?>
    <?php if (!in_array(\$page, \$hideNavPages)): ?>
    <nav class="sidebar">
STRING;

$replace2 = <<<STRING
    <nav class="sidebar">
STRING;

$content = str_replace($search2, $replace2, $content);

$search3 = <<<STRING
        ?>
    <?php if (!in_array(\$page, \$hideNavPages)): ?>
    </main>
    <?php endif; ?>
    
    <script src="public/assets/js/ui-helper.js?v=<?=time()?>"></script>
    <?php
    // Only emit UI notifications for pages that share the index.php layout.
    // Auth pages (login, forgot_password, etc.) have their own full HTML + scripts.
    if (!in_array(\$page, \$hideNavPages)) {
        if (isset(\$_SESSION['success_msg'])) {
            echo "<script>document.addEventListener('DOMContentLoaded', () => UI.showToast('" . addslashes(\$_SESSION['success_msg']) . "', 'success'));</script>";
            unset(\$_SESSION['success_msg']);
        }
        if (isset(\$_SESSION['error_msg'])) {
            echo "<script>document.addEventListener('DOMContentLoaded', () => UI.showToast('" . addslashes(\$_SESSION['error_msg']) . "', 'error'));</script>";
            unset(\$_SESSION['error_msg']);
        }
    }
    ?>
</body>
</html>
STRING;

$replace3 = <<<STRING
        ?>
    </main>
    
    <script src="public/assets/js/ui-helper.js?v=<?=time()?>"></script>
    <?php
        if (isset(\$_SESSION['success_msg'])) {
            echo "<script>document.addEventListener('DOMContentLoaded', () => UI.showToast('" . addslashes(\$_SESSION['success_msg']) . "', 'success'));</script>";
            unset(\$_SESSION['success_msg']);
        }
        if (isset(\$_SESSION['error_msg'])) {
            echo "<script>document.addEventListener('DOMContentLoaded', () => UI.showToast('" . addslashes(\$_SESSION['error_msg']) . "', 'error'));</script>";
            unset(\$_SESSION['error_msg']);
        }
    ?>
</body>
</html>
<?php endif; ?>
STRING;

$content = str_replace($search3, $replace3, $content);
file_put_contents('index.php', $content);
echo "patched index.php";
