-- =============================================================================
-- SCRIPT SQL COMPLETO - PRODUÇÃO HOSTINGER
-- Sistema: Gestão Clínica
-- Banco de dados: u182367286_dbclinic
-- Usuário: u182367286_clinic
-- 
-- INSTRUÇÕES:
--   1. Acesse o phpMyAdmin na Hostinger
--   2. Selecione o banco 'u182367286_dbclinic'
--   3. Importe este arquivo ou cole e execute na aba SQL
--   4. Este script é IDEMPOTENTE: seguro para rodar em banco vazio OU existente
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- TABELA: branches (Filiais) — deve ser criada PRIMEIRO pois é referenciada
-- =============================================================================

CREATE TABLE IF NOT EXISTS `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: specialties (Especialidades)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `specialties` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: therapies (Terapias)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `therapies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `default_duration_minutes` int DEFAULT '60',
  `branch_id` int DEFAULT '1',
  `color` varchar(7) DEFAULT '#3B82F6',
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `therapies_ibfk_branches` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: professionals (Profissionais)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `professionals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `branch_id` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `professionals_ibfk_branches` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: users (Usuários do sistema)
-- =============================================================================

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: patients (Pacientes)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `patients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `dob` date DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `contact_info` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `branch_id` int DEFAULT '1',
  `status` varchar(20) DEFAULT 'active',
  `pause_reason` text,
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `patients_ibfk_branches` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: appointments (Agendamentos)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `professional_id` int DEFAULT NULL,
  `therapy_id` int DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','noshow') DEFAULT 'scheduled',
  `notes` text,
  `branch_id` int DEFAULT '1',
  `recurrence_group_id` varchar(36) DEFAULT NULL COMMENT 'UUID compartilhado entre sessões de uma mesma recorrência semanal',
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `professional_id` (`professional_id`),
  KEY `therapy_id` (`therapy_id`),
  KEY `branch_id` (`branch_id`),
  KEY `idx_recurrence_group` (`recurrence_group_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`therapy_id`) REFERENCES `therapies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_branches` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: professional_therapies (Relação Profissional x Terapia)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `professional_therapies` (
  `professional_id` int NOT NULL,
  `therapy_id` int NOT NULL,
  PRIMARY KEY (`professional_id`, `therapy_id`),
  KEY `therapy_id` (`therapy_id`),
  CONSTRAINT `professional_therapies_ibfk_1` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `professional_therapies_ibfk_2` FOREIGN KEY (`therapy_id`) REFERENCES `therapies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: professional_specialties (Relação Profissional x Especialidade)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `professional_specialties` (
  `professional_id` int NOT NULL,
  `specialty_id` int NOT NULL,
  PRIMARY KEY (`professional_id`, `specialty_id`),
  KEY `specialty_id` (`specialty_id`),
  CONSTRAINT `professional_specialties_ibfk_1` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `professional_specialties_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: professional_schedules (Horários de atendimento dos profissionais)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `professional_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `professional_id` int NOT NULL,
  `day_of_week` int NOT NULL COMMENT '0=Domingo, 1=Segunda, ..., 6=Sábado',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `professional_id` (`professional_id`),
  CONSTRAINT `professional_schedules_ibfk_1` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: professional_skills (Habilidades dos profissionais)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `professional_skills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `professional_id` int DEFAULT NULL,
  `skill_name` varchar(255) DEFAULT NULL,
  `skill_type` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `professional_id` (`professional_id`),
  CONSTRAINT `professional_skills_ibfk_1` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: patient_packages (Pacotes dos pacientes)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `patient_packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `patient_packages_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: package_items (Itens dos pacotes)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `package_items` (
  `package_id` int DEFAULT NULL,
  `therapy_id` int DEFAULT NULL,
  `sessions_per_month` int DEFAULT NULL,
  KEY `package_id` (`package_id`),
  KEY `therapy_id` (`therapy_id`),
  CONSTRAINT `package_items_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `patient_packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `package_items_ibfk_2` FOREIGN KEY (`therapy_id`) REFERENCES `therapies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: therapy_documents (Documentos exigidos por terapia)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `therapy_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `therapy_id` int DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `is_required` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `therapy_id` (`therapy_id`),
  CONSTRAINT `therapy_documents_ibfk_1` FOREIGN KEY (`therapy_id`) REFERENCES `therapies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: patient_documents (Documentos dos pacientes)
-- =============================================================================

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: patient_planning (Planejamento/PEI dos pacientes)
-- =============================================================================

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABELA: session_notes (Notas/Evoluções de sessão)
-- =============================================================================

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- DADOS INICIAIS OBRIGATÓRIOS
-- =============================================================================

-- Filial padrão (Matriz) — necessária como âncora das chaves estrangeiras
INSERT INTO `branches` (`id`, `name`, `address`, `phone`, `active`)
SELECT 1, 'Matriz', NULL, NULL, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `id` = 1);

-- Especialidades padrão do sistema
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

-- Terapias padrão do sistema
INSERT INTO `therapies` (`id`, `name`, `default_duration_minutes`, `branch_id`, `color`)
SELECT 1, 'Fonoaudiologia', 50, 1, '#3B82F6' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 1)
UNION ALL
SELECT 2, 'Terapia Ocupacional', 50, 1, '#10B981' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 2)
UNION ALL
SELECT 3, 'Fisioterapia', 50, 1, '#F59E0B' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 3)
UNION ALL
SELECT 4, 'Psicologia', 50, 1, '#8B5CF6' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 4)
UNION ALL
SELECT 5, 'Psicopedagogia', 50, 1, '#EC4899' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 5)
UNION ALL
SELECT 6, 'Equoterapia', 60, 1, '#14B8A6' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 6)
UNION ALL
SELECT 7, 'PTM', 50, 1, '#F97316' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `therapies` WHERE `id` = 7);

-- =============================================================================
-- USUÁRIO ADMINISTRADOR INICIAL
-- Usuário: admin
-- Senha: Admin@123  (hash bcrypt gerado com cost=10)
-- IMPORTANTE: Troque a senha após o primeiro login!
-- =============================================================================

INSERT INTO `users` (`username`, `password_hash`, `role`, `active`)
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'admin');

-- =============================================================================
-- FIM DO SCRIPT
-- Banco pronto para uso. Acesse o sistema com:
--   Usuário: admin
--   Senha:   Admin@123  (troque imediatamente após o primeiro login)
-- =============================================================================
