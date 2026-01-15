-- Production Database Schema for Nexo System - Clinical Management
-- Including Authentication, Skills, and Data Integrity Features
-- Last updated: 2026-01-14

-- 1. Base Tables (No Foreign Keys)
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    dob DATE,
    guardian_name VARCHAR(100),
    contact_info VARCHAR(100),
    status ENUM('active', 'inactive', 'paused') DEFAULT 'active',
    pause_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_patient (name, dob)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professionals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialty VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    max_weekly_hours INT DEFAULT 40,
    UNIQUE KEY unique_professional_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS therapies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    default_duration_minutes INT DEFAULT 60,
    UNIQUE KEY unique_therapy_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Authentication & Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'professional') NOT NULL DEFAULT 'professional',
    professional_id INT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    KEY idx_username (username),
    KEY idx_professional_id (professional_id),
    FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Skills & Relationships
CREATE TABLE IF NOT EXISTS professional_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professional_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    skill_type ENUM('specialty', 'knowledge', 'certification') NOT NULL DEFAULT 'specialty',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_skill (professional_id, skill_name, skill_type),
    FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_therapies (
    professional_id INT NOT NULL,
    therapy_id INT NOT NULL,
    PRIMARY KEY (professional_id, therapy_id),
    FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE CASCADE,
    FOREIGN KEY (therapy_id) REFERENCES therapies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Patient Contract & Packages
CREATE TABLE IF NOT EXISTS patient_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    start_date DATE,
    end_date DATE,
    active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS package_items (
    package_id INT,
    therapy_id INT,
    sessions_per_month INT,
    FOREIGN KEY (package_id) REFERENCES patient_packages(id) ON DELETE CASCADE,
    FOREIGN KEY (therapy_id) REFERENCES therapies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Clinical Planning (PEI)
CREATE TABLE IF NOT EXISTS patient_planning (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    therapy_id INT,
    year INT NOT NULL,
    goals TEXT,
    status ENUM('active', 'completed', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (therapy_id) REFERENCES therapies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_pei (patient_id, therapy_id, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Appointments & Evolution
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS session_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT UNIQUE,
    professional_id INT,
    patient_id INT,
    content TEXT,
    evolution_type ENUM('routine', 'evaluation', 'incident') DEFAULT 'routine',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Seed Data
-- Default Admin User (Password: admin123)
INSERT INTO users (username, password_hash, role) 
VALUES ('admin', '$2y$12$IDqLOn6yU3Fe3U8zHsq7mO2dGD/puV9w3vRbua8vFMR9jzbJTBRvG', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- Initial Therapies (Optional)
INSERT INTO therapies (name) VALUES 
('Psicologia - ABA'), 
('Fonoaudiologia'), 
('Terapia Ocupacional'), 
('Psicopedagogia')
ON DUPLICATE KEY UPDATE name=name;
