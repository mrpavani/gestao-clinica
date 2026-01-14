<?php
// config/config.php

// Detect environment based on server name (or you can use an ENV variable if available)
$isLocal = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1');

if ($isLocal) {
    // Local Environment (XAMPP/MAMP/Built-in PHP)
    return [
        'db_host' => 'localhost',
        'db_name' => 'clinic_db',
        'db_user' => 'root',
        'db_pass' => '', // Adjust if your local setup has a password
        'db_charset' => 'utf8mb4'
    ];
} else {
    // Production Environment (Hostinger)
    return [
        'db_host' => 'localhost',
        'db_name' => 'u182367286_dbclinic',
        'db_user' => 'u182367286_dbclinic',
        'db_pass' => 'm@P599152', 
        'db_charset' => 'utf8mb4'
    ];
}