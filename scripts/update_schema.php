<?php
// scripts/update_schema.php
require_once __DIR__ . '/../src/Database.php';

echo "Starting database update...\n";

$pdo = Database::getInstance()->getConnection();

try {
    $pdo->beginTransaction();

    // 1. Rename PDI/Ecoterapia
    echo "Renaming therapies...\n";
    $pdo->exec("UPDATE therapies SET name = REPLACE(name, 'PDI', 'PTM') WHERE name LIKE '%PDI%'");
    $pdo->exec("UPDATE therapies SET name = REPLACE(name, 'Ecoterapia', 'Equoterapia') WHERE name LIKE '%Ecoterapia%'");

    // 2. Create therapy_documents table
    echo "Creating therapy_documents table...\n";
    $sqlTherapyDocs = "CREATE TABLE IF NOT EXISTS therapy_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        therapy_id INT,
        name VARCHAR(255) NOT NULL,
        is_required BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (therapy_id) REFERENCES therapies(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlTherapyDocs);

    // 3. Create patient_documents table
    echo "Creating patient_documents table...\n";
    $sqlPatientDocs = "CREATE TABLE IF NOT EXISTS patient_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT,
        therapy_document_id INT,
        file_path VARCHAR(255) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
        FOREIGN KEY (therapy_document_id) REFERENCES therapy_documents(id) ON DELETE SET NULL
    )";
    $pdo->exec($sqlPatientDocs);

    $pdo->commit();
    echo "Database updated successfully!\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error updating database: " . $e->getMessage() . "\n";
    exit(1);
}
