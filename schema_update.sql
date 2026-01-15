-- Schema Update SQL for Authentication & Data Integrity
-- Run this on your existing database to add new features
-- Last updated: 2026-01-14

-- 1. Add email field to professionals
ALTER TABLE professionals 
ADD COLUMN email VARCHAR(100) UNIQUE AFTER specialty;

-- 2. Create users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'professional') NOT NULL DEFAULT 'professional',
    professional_id INT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create professional_skills table for multiple specialties/skills
CREATE TABLE IF NOT EXISTS professional_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professional_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    skill_type ENUM('specialty', 'knowledge', 'certification') NOT NULL DEFAULT 'specialty',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE CASCADE,
    UNIQUE KEY unique_skill (professional_id, skill_name, skill_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Add unique constraints to prevent duplicates
-- Note: If you have existing duplicates, you'll need to clean them up first

-- For patients: combination of name and date of birth should be unique
ALTER TABLE patients 
ADD UNIQUE INDEX unique_patient (name, dob);

-- For therapies: name should be unique
ALTER TABLE therapies 
ADD UNIQUE INDEX unique_therapy_name (name);

-- For professionals: name should be unique
ALTER TABLE professionals 
ADD UNIQUE INDEX unique_professional_name (name);

-- 5. Insert default admin user
-- Password is 'admin123' (PLEASE CHANGE IMMEDIATELY AFTER FIRST LOGIN)
INSERT INTO users (username, password_hash, role, professional_id) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL);

-- 6. Add index for faster authentication queries
CREATE INDEX idx_username ON users(username);
CREATE INDEX idx_professional_id ON users(professional_id);

-- Verification queries (optional - run these to verify the changes)
-- SHOW COLUMNS FROM professionals;
-- SHOW COLUMNS FROM users;
-- SHOW COLUMNS FROM professional_skills;
-- SELECT * FROM users WHERE username = 'admin';
