<?php
// scripts/check_db.php
require_once __DIR__ . '/../src/Database.php';

$pdo = Database::getInstance()->getConnection();

echo "Tables:\n";
$stmt = $pdo->query("SHOW TABLES");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

echo "\nTherapies (checking renames):\n";
$stmt = $pdo->query("SELECT id, name FROM therapies WHERE name LIKE '%PTM%' OR name LIKE '%Equoterapia%' OR name LIKE '%PDI%' OR name LIKE '%Ecoterapia%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nChecking therapy_documents table:\n";
try {
    $stmt = $pdo->query("DESCRIBE therapy_documents");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "therapy_documents table does not exist.\n";
}

echo "\nChecking patient_documents table:\n";
try {
    $stmt = $pdo->query("DESCRIBE patient_documents");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "patient_documents table does not exist.\n";
}
