-- =============================================================================
-- NEXO SYSTEM - SCRIPT SQL PARA PRODUÇÃO
-- Sistema: Gestão Clínica
-- Banco: u182367286_dbclinic | Usuário: u182367286_clinic
-- Gerado em: 2026-03-18
--
-- INSTRUÇÕES:
--   1. Acesse phpMyAdmin na Hostinger
--   2. Selecione o banco 'u182367286_dbclinic'
--   3. Aba SQL → Cole e execute este arquivo
--   Este script é IDEMPOTENTE: seguro em banco vazio OU já existente
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '-03:00';

-- =============================================================================
-- TABELAS
-- =============================================================================

-- branches (Filiais) — criada primeiro pois é referenciada por outras
CREATE TABLE IF NOT EXISTS `branches` (
  `id`         int            NOT NULL AUTO_INCREMENT,
  `name`       varchar(100)   NOT NULL,
  `address`    varchar(255)   DEFAULT NULL,
  `phone`      varchar(50)    DEFAULT NULL,
  `active`     tinyint(1)     DEFAULT '1',
  `created_at` timestamp      NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- specialties (Especialidades)
CREATE TABLE IF NOT EXISTS `specialties` (
  `id`   int          NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- therapies (Terapias)
CREATE TABLE IF NOT EXISTS `therapies` (
  `id`                       int          NOT NULL AUTO_INCREMENT,
  `name`                     varchar(100) NOT NULL,
  `default_duration_minutes` int          DEFAULT '60',
  `branch_id`                int          DEFAULT '1',
  `color`                    varchar(7)   DEFAULT '#3B82F6',
  PRIMARY KEY (`id`),
  KEY `idx_branch` (`branch_id`),
  CONSTRAINT `therapies_fk_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- professionals (Profissionais)
CREATE TABLE IF NOT EXISTS `professionals` (
  `id`        int          NOT NULL AUTO_INCREMENT,
  `name`      varchar(100) NOT NULL,
  `branch_id` int          DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_branch` (`branch_id`),
  CONSTRAINT `professionals_fk_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- users (Usuários do sistema)
CREATE TABLE IF NOT EXISTS `users` (
  `id`                   int          NOT NULL AUTO_INCREMENT,
  `username`             varchar(100) NOT NULL,
  `password_hash`        varchar(255) NOT NULL,
  `role`                 varchar(50)  DEFAULT 'user',
  `branch_id`            int          DEFAULT NULL,
  `professional_id`      int          DEFAULT NULL,
  `active`               tinyint(1)   DEFAULT '1',
  `last_login`           datetime     DEFAULT NULL,
  `created_at`           timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_token`          varchar(255) DEFAULT NULL,
  `reset_token_expires`  datetime     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_professional` (`professional_id`),
  KEY `idx_branch` (`branch_id`),
  CONSTRAINT `users_fk_professional` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_fk_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- patients (Pacientes)
CREATE TABLE IF NOT EXISTS `patients` (
  `id`            int          NOT NULL AUTO_INCREMENT,
  `name`          varchar(100) NOT NULL,
  `dob`           date         DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `contact_info`  varchar(100) DEFAULT NULL,
  `created_at`    timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  `branch_id`     int          DEFAULT '1',
  `status`        varchar(20)  DEFAULT 'active',
  `pause_reason`  text,
  PRIMARY KEY (`id`),
  KEY `idx_branch` (`branch_id`),
  CONSTRAINT `patients_fk_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- appointments (Agendamentos)
CREATE TABLE IF NOT EXISTS `appointments` (
  `id`                  int          NOT NULL AUTO_INCREMENT,
  `patient_id`          int          DEFAULT NULL,
  `professional_id`     int          DEFAULT NULL,
  `therapy_id`          int          DEFAULT NULL,
  `start_time`          datetime     DEFAULT NULL,
  `end_time`            datetime     DEFAULT NULL,
  `status`              enum('scheduled','completed','cancelled','noshow','canceled') DEFAULT 'scheduled',
  `notes`               text,
  `branch_id`           int          DEFAULT '1',
  `recurrence_group_id` varchar(36)  DEFAULT NULL COMMENT 'UUID de sessões recorrentes semanais',
  PRIMARY KEY (`id`),
  KEY `idx_patient`     (`patient_id`),
  KEY `idx_professional`(`professional_id`),
  KEY `idx_therapy`     (`therapy_id`),
  KEY `idx_branch`      (`branch_id`),
  KEY `idx_recurrence`  (`recurrence_group_id`),
  KEY `idx_start_time`  (`start_time`),
  CONSTRAINT `appointments_fk_patient`      FOREIGN KEY (`patient_id`)      REFERENCES `patients`     (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_fk_professional` FOREIGN KEY (`professional_id`) REFERENCES `professionals`(`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_fk_therapy`      FOREIGN KEY (`therapy_id`)      REFERENCES `therapies`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_fk_branch`       FOREIGN KEY (`branch_id`)       REFERENCES `branches`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- professional_therapies (Vínculo profissional ↔ terapia)
CREATE TABLE IF NOT EXISTS `professional_therapies` (
  `professional_id` int NOT NULL,
  `therapy_id`      int NOT NULL,
  PRIMARY KEY (`professional_id`, `therapy_id`),
  KEY `idx_therapy` (`therapy_id`),
  CONSTRAINT `pt_fk_professional` FOREIGN KEY (`professional_id`) REFERENCES `professionals`(`id`) ON DELETE CASCADE,
  CONSTRAINT `pt_fk_therapy`      FOREIGN KEY (`therapy_id`)      REFERENCES `therapies`    (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- professional_specialties (Vínculo profissional ↔ especialidade)
CREATE TABLE IF NOT EXISTS `professional_specialties` (
  `professional_id` int NOT NULL,
  `specialty_id`    int NOT NULL,
  PRIMARY KEY (`professional_id`, `specialty_id`),
  KEY `idx_specialty` (`specialty_id`),
  CONSTRAINT `ps_fk_professional` FOREIGN KEY (`professional_id`) REFERENCES `professionals`(`id`) ON DELETE CASCADE,
  CONSTRAINT `ps_fk_specialty`    FOREIGN KEY (`specialty_id`)    REFERENCES `specialties`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- professional_schedules (Horários dos profissionais)
CREATE TABLE IF NOT EXISTS `professional_schedules` (
  `id`              int  NOT NULL AUTO_INCREMENT,
  `professional_id` int  NOT NULL,
  `day_of_week`     int  NOT NULL COMMENT '0=Dom, 1=Seg, ..., 6=Sáb',
  `start_time`      time NOT NULL,
  `end_time`        time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_professional` (`professional_id`),
  CONSTRAINT `psch_fk_professional` FOREIGN KEY (`professional_id`) REFERENCES `professionals`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- professional_skills (Habilidades dos profissionais)
CREATE TABLE IF NOT EXISTS `professional_skills` (
  `id`              int          NOT NULL AUTO_INCREMENT,
  `professional_id` int          DEFAULT NULL,
  `skill_name`      varchar(255) DEFAULT NULL,
  `skill_type`      varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_professional` (`professional_id`),
  CONSTRAINT `psk_fk_professional` FOREIGN KEY (`professional_id`) REFERENCES `professionals`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- patient_packages (Pacotes de terapias dos pacientes)
CREATE TABLE IF NOT EXISTS `patient_packages` (
  `id`         int        NOT NULL AUTO_INCREMENT,
  `patient_id` int        DEFAULT NULL,
  `start_date` date       DEFAULT NULL,
  `end_date`   date       DEFAULT NULL,
  `active`     tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_patient` (`patient_id`),
  CONSTRAINT `pp_fk_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- package_items (Itens/terapias de cada pacote)
CREATE TABLE IF NOT EXISTS `package_items` (
  `package_id`         int DEFAULT NULL,
  `therapy_id`         int DEFAULT NULL,
  `sessions_per_month` int DEFAULT NULL,
  KEY `idx_package` (`package_id`),
  KEY `idx_therapy` (`therapy_id`),
  CONSTRAINT `pi_fk_package` FOREIGN KEY (`package_id`) REFERENCES `patient_packages`(`id`) ON DELETE CASCADE,
  CONSTRAINT `pi_fk_therapy` FOREIGN KEY (`therapy_id`) REFERENCES `therapies`       (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- therapy_documents (Documentos exigidos por terapia)
CREATE TABLE IF NOT EXISTS `therapy_documents` (
  `id`          int          NOT NULL AUTO_INCREMENT,
  `therapy_id`  int          DEFAULT NULL,
  `name`        varchar(255) NOT NULL,
  `is_required` tinyint(1)   DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_therapy` (`therapy_id`),
  CONSTRAINT `td_fk_therapy` FOREIGN KEY (`therapy_id`) REFERENCES `therapies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- patient_documents (Documentos enviados pelos pacientes)
CREATE TABLE IF NOT EXISTS `patient_documents` (
  `id`                   int          NOT NULL AUTO_INCREMENT,
  `patient_id`           int          DEFAULT NULL,
  `therapy_document_id`  int          DEFAULT NULL,
  `file_path`            varchar(255) NOT NULL,
  `uploaded_at`          timestamp    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_patient`          (`patient_id`),
  KEY `idx_therapy_document` (`therapy_document_id`),
  CONSTRAINT `pd_fk_patient`           FOREIGN KEY (`patient_id`)          REFERENCES `patients`         (`id`) ON DELETE CASCADE,
  CONSTRAINT `pd_fk_therapy_document`  FOREIGN KEY (`therapy_document_id`) REFERENCES `therapy_documents`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- patient_planning (Planejamento / PEI dos pacientes)
CREATE TABLE IF NOT EXISTS `patient_planning` (
  `id`         int         NOT NULL AUTO_INCREMENT,
  `patient_id` int         DEFAULT NULL,
  `year`       int         DEFAULT NULL,
  `therapy_id` int         DEFAULT NULL,
  `goals`      text,
  `status`     varchar(50) DEFAULT 'active',
  `created_at` timestamp   NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_therapy` (`therapy_id`),
  CONSTRAINT `pplan_fk_patient`  FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pplan_fk_therapy`  FOREIGN KEY (`therapy_id`) REFERENCES `therapies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- session_notes (Evoluções/notas de sessão)
CREATE TABLE IF NOT EXISTS `session_notes` (
  `id`              int         NOT NULL AUTO_INCREMENT,
  `appointment_id`  int         DEFAULT NULL,
  `professional_id` int         DEFAULT NULL,
  `patient_id`      int         DEFAULT NULL,
  `content`         text,
  `evolution_type`  varchar(50) DEFAULT NULL,
  `created_at`      timestamp   NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_appointment`  (`appointment_id`),
  KEY `idx_professional` (`professional_id`),
  KEY `idx_patient`      (`patient_id`),
  CONSTRAINT `sn_fk_appointment`  FOREIGN KEY (`appointment_id`)  REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_fk_professional` FOREIGN KEY (`professional_id`) REFERENCES `professionals`(`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_fk_patient`      FOREIGN KEY (`patient_id`)      REFERENCES `patients`     (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- DADOS INICIAIS (IDEMPOTENTES)
-- =============================================================================

-- Filial padrão (Matriz)
INSERT INTO `branches` (`id`, `name`, `address`, `phone`, `active`)
SELECT 1, 'Matriz', NULL, NULL, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `id` = 1);

-- Especialidades padrão
INSERT IGNORE INTO `specialties` (`name`) VALUES
  ('Fonoaudiologia'),
  ('Terapia Ocupacional'),
  ('Fisioterapia'),
  ('Psicologia'),
  ('Psicopedagogia'),
  ('Neuropsicologia'),
  ('Musicoterapia'),
  ('Equoterapia'),
  ('Educação Física');

-- Terapias padrão
INSERT INTO `therapies` (`id`, `name`, `default_duration_minutes`, `branch_id`, `color`)
SELECT 1, 'Fonoaudiologia',     50, 1, '#3B82F6' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 1) UNION ALL
SELECT 2, 'Terapia Ocupacional',50, 1, '#10B981' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 2) UNION ALL
SELECT 3, 'Fisioterapia',       50, 1, '#F59E0B' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 3) UNION ALL
SELECT 4, 'Psicologia',         50, 1, '#8B5CF6' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 4) UNION ALL
SELECT 5, 'Psicopedagogia',     50, 1, '#EC4899' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 5) UNION ALL
SELECT 6, 'Equoterapia',        60, 1, '#14B8A6' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 6) UNION ALL
SELECT 7, 'PTM',                50, 1, '#F97316' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 7);

-- =============================================================================
-- USUÁRIO ADMINISTRADOR INICIAL
-- Login: admin | Senha: Admin@123
-- !! TROQUE A SENHA IMEDIATAMENTE APÓS O PRIMEIRO LOGIN !!
-- =============================================================================

INSERT INTO `users` (`username`, `password_hash`, `role`, `active`)
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'admin');

-- =============================================================================
-- FIM DO SCRIPT — Sistema pronto para uso
-- Login: admin | Senha: Admin@123
-- =============================================================================
