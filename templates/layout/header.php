<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexo System</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="icon" type="image/png" href="public/assets/img/logo.png">
    <?php
    $cssVersion = @filemtime(__DIR__ . '/../../public/assets/css/style.css') ?: '1';
    $notifVersion = @filemtime(__DIR__ . '/../../public/assets/css/notifications.css') ?: '1';
    ?>
    <link rel="stylesheet" href="public/assets/css/style.css?v=<?= $cssVersion ?>">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/assets/css/notifications.css?v=<?= $notifVersion ?>">
</head>
<body>
    <!-- Notification Container -->
    <div id="notification-container"></div>
