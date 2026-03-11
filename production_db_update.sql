-- -----------------------------------------------------------------------------
-- SCRIPT DE ATUALIZAÇÃO DO BANCO DE DADOS (USO EM PRODUÇÃO)
-- 
-- Este script foi criado de forma idempotente:
-- 1. Cria todas as tabelas (base + novas) apenas se não existirem.
-- 2. Adiciona colunas novas apenas se não existirem.
-- -----------------------------------------------------------------------------

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- 1. CRIAÇÃO DAS TABELAS BASE (do database.sql original)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `patients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `dob` DATE,
    `guardian_name` VARCHAR(100),
    `contact_info` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `therapies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `default_duration_minutes` INT DEFAULT 60,
    `color` VARCHAR(7) DEFAULT '#3B82F6'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `professionals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `specialty` VARCHAR(100),
    `max_weekly_hours` INT DEFAULT 40
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `professional_therapies` (
    `professional_id` INT,
    `therapy_id` INT,
    PRIMARY KEY (`professional_id`, `therapy_id`),
    FOREIGN KEY (`professional_id`) REFERENCES `professionals`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`therapy_id`) REFERENCES `therapies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `patient_packages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT,
    `start_date` DATE,
    `end_date` DATE,
    `active` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `package_items` (
    `package_id` INT,
    `therapy_id` INT,
    `sessions_per_month` INT,
    FOREIGN KEY (`package_id`) REFERENCES `patient_packages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`therapy_id`) REFERENCES `therapies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT,
    `professional_id` INT,
    `therapy_id` INT,
    `start_time` DATETIME,
    `end_time` DATETIME,
    `status` ENUM('scheduled', 'completed', 'cancelled', 'noshow') DEFAULT 'scheduled',
    `notes` TEXT,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`professional_id`) REFERENCES `professionals`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`therapy_id`) REFERENCES `therapies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================================
-- 2. CRIAÇÃO DAS NOVAS TABELAS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `specialties` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'user',
  `professional_id` int DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `professional_id` (`professional_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `professional_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `professional_id` int NOT NULL,
  `day_of_week` int NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `professional_id` (`professional_id`),
  CONSTRAINT `professional_schedules_ibfk_1` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `professional_skills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `professional_id` int DEFAULT NULL,
  `skill_name` varchar(255) DEFAULT NULL,
  `skill_type` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `professional_id` (`professional_id`),
  CONSTRAINT `professional_skills_ibfk_1` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `professional_specialties` (
  `professional_id` int NOT NULL,
  `specialty_id` int NOT NULL,
  PRIMARY KEY (`professional_id`,`specialty_id`),
  KEY `specialty_id` (`specialty_id`),
  CONSTRAINT `professional_specialties_ibfk_1` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `professional_specialties_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `therapy_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `therapy_id` int DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `is_required` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `therapy_id` (`therapy_id`),
  CONSTRAINT `therapy_documents_ibfk_1` FOREIGN KEY (`therapy_id`) REFERENCES `therapies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `patient_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `therapy_document_id` int DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `therapy_document_id` (`therapy_document_id`),
  CONSTRAINT `patient_documents_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_documents_ibfk_2` FOREIGN KEY (`therapy_document_id`) REFERENCES `therapy_documents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `patient_planning` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `year` int DEFAULT NULL,
  `therapy_id` int DEFAULT NULL,
  `goals` text,
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `therapy_id` (`therapy_id`),
  CONSTRAINT `patient_planning_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_planning_ibfk_2` FOREIGN KEY (`therapy_id`) REFERENCES `therapies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `session_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `appointment_id` int DEFAULT NULL,
  `professional_id` int DEFAULT NULL,
  `patient_id` int DEFAULT NULL,
  `content` text,
  `evolution_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `professional_id` (`professional_id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `session_notes_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `session_notes_ibfk_2` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `session_notes_ibfk_3` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================================
-- 3. DADOS INICIAIS OBRIGATÓRIOS
-- =============================================================================

-- Garante que exista pelo menos uma filial (Matriz) para as chaves estrangeiras
INSERT INTO `branches` (`name`)
SELECT 'Matriz' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `branches` LIMIT 1);


-- =============================================================================
-- 4. ADIÇÃO DE COLUNAS FALTANTES EM TABELAS EXISTENTES (Idempotente)
-- =============================================================================

DELIMITER $$

CREATE PROCEDURE AddColumnIfNotExists(
    IN dbName VARCHAR(255),
    IN tableName VARCHAR(255),
    IN columnName VARCHAR(255),
    IN columnDef TEXT
)
BEGIN
    DECLARE _count INT;
    SET _count = (  SELECT COUNT(*) 
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = dbName AND
                          TABLE_NAME = tableName AND
                          COLUMN_NAME = columnName);
    IF _count = 0 THEN
        SET @ddl = CONCAT('ALTER TABLE `', tableName, '` ADD COLUMN `', columnName, '` ', columnDef);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE PROCEDURE AddForeignIfNotExists(
    IN dbName VARCHAR(255),
    IN tableName VARCHAR(255),
    IN constraintName VARCHAR(255),
    IN fkDef TEXT
)
BEGIN
    DECLARE _count INT;
    SET _count = (  SELECT COUNT(*) 
                    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                    WHERE TABLE_SCHEMA = dbName AND
                          TABLE_NAME = tableName AND
                          CONSTRAINT_NAME = constraintName AND
                          CONSTRAINT_TYPE = 'FOREIGN KEY');
    IF _count = 0 THEN
        SET @ddl = CONCAT('ALTER TABLE `', tableName, '` ADD CONSTRAINT `', constraintName, '` ', fkDef);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;


-- Aplicar colunas e constraints ausentes:

-- ====================
-- TABELA: patients
-- ====================
CALL AddColumnIfNotExists(DATABASE(), 'patients', 'branch_id', 'int DEFAULT 1');
CALL AddColumnIfNotExists(DATABASE(), 'patients', 'status', "varchar(20) DEFAULT 'active'");
CALL AddColumnIfNotExists(DATABASE(), 'patients', 'pause_reason', 'text');
CALL AddForeignIfNotExists(DATABASE(), 'patients', 'patients_ibfk_branches', 'FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL');

-- ====================
-- TABELA: therapies
-- ====================
CALL AddColumnIfNotExists(DATABASE(), 'therapies', 'branch_id', 'int DEFAULT 1');
CALL AddForeignIfNotExists(DATABASE(), 'therapies', 'therapies_ibfk_branches', 'FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL');

-- ====================
-- TABELA: professionals
-- ====================
CALL AddColumnIfNotExists(DATABASE(), 'professionals', 'branch_id', 'int DEFAULT 1');
CALL AddForeignIfNotExists(DATABASE(), 'professionals', 'professionals_ibfk_branches', 'FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL');

-- ====================
-- TABELA: appointments
-- ====================
CALL AddColumnIfNotExists(DATABASE(), 'appointments', 'branch_id', 'int DEFAULT 1');
CALL AddForeignIfNotExists(DATABASE(), 'appointments', 'appointments_ibfk_branches', 'FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL');

-- ====================
-- TABELA: users (Garantir colunas extras se users já existia)
-- ====================
CALL AddColumnIfNotExists(DATABASE(), 'users', 'reset_token', 'varchar(255) DEFAULT NULL');
CALL AddColumnIfNotExists(DATABASE(), 'users', 'reset_token_expires', 'datetime DEFAULT NULL');


-- Limpeza de procedures que não serão mais utilizados
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;
DROP PROCEDURE IF EXISTS AddForeignIfNotExists;


-- =============================================================================
-- 5. ATUALIZAÇÃO E NORMALIZAÇÃO DE DADOS EXISTENTES
-- =============================================================================

-- Obter ID da matriz
SET @defaultBranchId = (SELECT id FROM branches LIMIT 1);

-- Atualiza registros órfãos que possivelmente não tenham um branch_id 
UPDATE patients SET branch_id = @defaultBranchId WHERE branch_id IS NULL;
UPDATE therapies SET branch_id = @defaultBranchId WHERE branch_id IS NULL;
UPDATE professionals SET branch_id = @defaultBranchId WHERE branch_id IS NULL;
UPDATE appointments SET branch_id = @defaultBranchId WHERE branch_id IS NULL;

-- Atualizar nomenclaturas (PDI -> PTM, Ecoterapia -> Equoterapia) se já existirem
UPDATE therapies SET name = REPLACE(name, 'PDI', 'PTM') WHERE name LIKE '%PDI%';
UPDATE therapies SET name = REPLACE(name, 'Ecoterapia', 'Equoterapia') WHERE name LIKE '%Ecoterapia%';

SET FOREIGN_KEY_CHECKS = 1;

-- FIM DO SCRIPT

-- ==============================================================
-- MIGRATION: Suporte a Agendamentos Recorrentes
-- Data: 2026-03-11
-- Descrição: Adiciona coluna para agrupar sessões recorrentes
-- ==============================================================

-- Adiciona coluna apenas se não existir (safe to re-run)
ALTER TABLE appointments 
    ADD COLUMN IF NOT EXISTS recurrence_group_id VARCHAR(36) NULL DEFAULT NULL
    COMMENT 'UUID compartilhado entre sessões de uma mesma recorrência semanal';

-- Índice para facilitar busca por grupo
ALTER TABLE appointments
    ADD INDEX IF NOT EXISTS idx_recurrence_group (recurrence_group_id);

-- FIM DA MIGRATION DE RECORRÊNCIA
