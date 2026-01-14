-- Phase 3 Updates

-- Add therapy_id to patient_planning
ALTER TABLE patient_planning ADD COLUMN therapy_id INT AFTER patient_id;

-- Since we are in dev/prototype mode and might have dirty data, let's just clear the table to avoid constraint issues, 
-- or we can try to update it. For now, let's assume we can just add the column and then the constraint.
-- Ideally we would migrate, but since we just built it, truncation is safer for a clean state if permitted, 
-- but I will try to make it nullable first then foreign key.

ALTER TABLE patient_planning ADD FOREIGN KEY (therapy_id) REFERENCES therapies(id) ON DELETE CASCADE;

-- Ensure uniqueness per patient/therapy/year
ALTER TABLE patient_planning ADD UNIQUE KEY unique_pei (patient_id, therapy_id, year);
