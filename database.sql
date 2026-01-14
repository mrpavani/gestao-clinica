CREATE DATABASE IF NOT EXISTS clinic_db;
USE clinic_db;

CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    dob DATE,
    guardian_name VARCHAR(100),
    contact_info VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS therapies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    default_duration_minutes INT DEFAULT 60
);

CREATE TABLE IF NOT EXISTS professionals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialty VARCHAR(100),
    max_weekly_hours INT DEFAULT 40
);

CREATE TABLE IF NOT EXISTS professional_therapies (
    professional_id INT,
    therapy_id INT,
    PRIMARY KEY (professional_id, therapy_id),
    FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE CASCADE,
    FOREIGN KEY (therapy_id) REFERENCES therapies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS patient_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    start_date DATE,
    end_date DATE,
    active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS package_items (
    package_id INT,
    therapy_id INT,
    sessions_per_month INT,
    FOREIGN KEY (package_id) REFERENCES patient_packages(id) ON DELETE CASCADE,
    FOREIGN KEY (therapy_id) REFERENCES therapies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    professional_id INT,
    therapy_id INT,
    start_time DATETIME,
    end_time DATETIME,
    status ENUM('scheduled', 'completed', 'cancelled', 'noshow') DEFAULT 'scheduled',
    notes TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE CASCADE,
    FOREIGN KEY (therapy_id) REFERENCES therapies(id) ON DELETE CASCADE
);
