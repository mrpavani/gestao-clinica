<?php
// scripts/add_branches.php
// Migration script to add multi-branch support

require_once __DIR__ . '/../src/Database.php';

echo "Starting multi-branch migration...\n";

$pdo = Database::getInstance()->getConnection();

try {
    // 1. Create branches table
    echo "Creating branches table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS branches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        address VARCHAR(255),
        phone VARCHAR(50),
        active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Insert default branch if none exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM branches");
    if ($stmt->fetchColumn() == 0) {
        echo "Inserting default 'Matriz' branch...\n";
        $pdo->exec("INSERT INTO branches (name) VALUES ('Matriz')");
    }

    // Get the default branch ID
    $stmt = $pdo->query("SELECT id FROM branches LIMIT 1");
    $defaultBranchId = $stmt->fetchColumn();
    echo "Default branch ID: $defaultBranchId\n";

    // 3. Add branch_id to therapies
    echo "Adding branch_id to therapies...\n";
    try {
        $pdo->exec("ALTER TABLE therapies ADD COLUMN branch_id INT DEFAULT $defaultBranchId");
        $pdo->exec("ALTER TABLE therapies ADD FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "Note: " . $e->getMessage() . "\n";
        }
    }

    // 4. Add branch_id to patients
    echo "Adding branch_id to patients...\n";
    try {
        $pdo->exec("ALTER TABLE patients ADD COLUMN branch_id INT DEFAULT $defaultBranchId");
        $pdo->exec("ALTER TABLE patients ADD FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "Note: " . $e->getMessage() . "\n";
        }
    }

    // 5. Add branch_id to professionals
    echo "Adding branch_id to professionals...\n";
    try {
        $pdo->exec("ALTER TABLE professionals ADD COLUMN branch_id INT DEFAULT $defaultBranchId");
        $pdo->exec("ALTER TABLE professionals ADD FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "Note: " . $e->getMessage() . "\n";
        }
    }

    // 6. Add branch_id to appointments
    echo "Adding branch_id to appointments...\n";
    try {
        $pdo->exec("ALTER TABLE appointments ADD COLUMN branch_id INT DEFAULT $defaultBranchId");
        $pdo->exec("ALTER TABLE appointments ADD FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "Note: " . $e->getMessage() . "\n";
        }
    }

    // 7. Update existing records to use default branch
    echo "Updating existing records with default branch...\n";
    $pdo->exec("UPDATE therapies SET branch_id = $defaultBranchId WHERE branch_id IS NULL");
    $pdo->exec("UPDATE patients SET branch_id = $defaultBranchId WHERE branch_id IS NULL");
    $pdo->exec("UPDATE professionals SET branch_id = $defaultBranchId WHERE branch_id IS NULL");
    $pdo->exec("UPDATE appointments SET branch_id = $defaultBranchId WHERE branch_id IS NULL");

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
