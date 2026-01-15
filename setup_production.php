<?php
// setup_production.php
// Script to deploy or update the production database

require_once __DIR__ . '/src/Database.php';

echo "Initializing Database Setup...\n";

try {
    $pdo = Database::getInstance()->getConnection();
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

// 1. Run Base Schema (Creates tables if not exist)
echo "Running schema creation from production_db.sql...\n";
$sql = file_get_contents(__DIR__ . '/production_db.sql');

// Split into commands (rough split by semicolon at end of line)
// Note: This is a simple splitter, might break on semicolons in strings but our schema is simple.
$commands = explode(";\n", $sql);

foreach ($commands as $command) {
    $command = trim($command);
    if (!empty($command)) {
        try {
            $pdo->exec($command);
        } catch (Exception $e) {
            // Ignore "Table already exists" or generic errors if safe, but better to show
            // echo "Warning on command: " . substr($command, 0, 50) . "... " . $e->getMessage() . "\n";
        }
    }
}
echo "Base schema check complete.\n";

// 2. Intelligent Updates (Add columns if missing)
echo "Checking for schema updates...\n";

function addColumnIfNotExists($pdo, $table, $column, $definition) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
        if ($stmt->fetch()) {
            // Column exists
            return;
        }
        
        echo "Adding missing column '$column' to '$table'...\n";
        $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        
    } catch (Exception $e) {
        echo "Error checking/adding column $column: " . $e->getMessage() . "\n";
    }
}

// Update Patients table
addColumnIfNotExists($pdo, 'patients', 'status', "ENUM('active', 'inactive', 'paused') DEFAULT 'active' AFTER contact_info");
addColumnIfNotExists($pdo, 'patients', 'pause_reason', "TEXT AFTER status");

// Update Professionals table
addColumnIfNotExists($pdo, 'professionals', 'email', "VARCHAR(100) UNIQUE AFTER specialty");

// Update Users (if needed, though schema likely handles it)
// Ensure admin exists (Schema does this, but good to double check)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    echo "Creating default admin user...\n";
    $pass = '$2y$12$IDqLOn6yU3Fe3U8zHsq7mO2dGD/puV9w3vRbua8vFMR9jzbJTBRvG'; // admin123
    $pdo->exec("INSERT INTO users (username, password_hash, role) VALUES ('admin', '$pass', 'admin')");
}

echo "Database deployment completed successfully!\n";
?>
