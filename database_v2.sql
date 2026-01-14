-- Phase 2 Updates

CREATE TABLE IF NOT EXISTS patient_planning (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    year INT NOT NULL,
    goals TEXT,
    status ENUM('active', 'completed', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

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
);
