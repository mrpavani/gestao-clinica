-- =============================================================================
-- SCRIPT DE ATUALIZAÇÃO - AGENDAMENTO MULTI-DIAS E RECURRÊNCIA
-- Execute este script no phpMyAdmin para atualizar a base de produção
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Garante que a coluna de grupo de recorrência existe na tabela appointments
SET @dbname = DATABASE();
SET @tablename = "appointments";
SET @columnname = "recurrence_group_id";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = @tablename
     AND COLUMN_NAME = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE appointments ADD COLUMN recurrence_group_id varchar(36) DEFAULT NULL COMMENT 'UUID de sessões recorrentes';"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Garante que as colunas de Duração e Cor existem na tabela therapies
SET @columnname2 = "default_duration_minutes";
SET @preparedStatement2 = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = "therapies"
     AND COLUMN_NAME = @columnname2) > 0,
  "SELECT 1",
  "ALTER TABLE therapies ADD COLUMN default_duration_minutes int DEFAULT '60';"
));
PREPARE stmt2 FROM @preparedStatement2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SET @columnname3 = "color";
SET @preparedStatement3 = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = "therapies"
     AND COLUMN_NAME = @columnname3) > 0,
  "SELECT 1",
  "ALTER TABLE therapies ADD COLUMN color varchar(7) DEFAULT '#3B82F6';"
));
PREPARE stmt3 FROM @preparedStatement3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- 3. Adiciona índice para performance em buscas por recorrência
SET @preparedStatement4 = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = "appointments"
     AND INDEX_NAME = "idx_recurrence") > 0,
  "SELECT 1",
  "CREATE INDEX idx_recurrence ON appointments(recurrence_group_id);"
));
PREPARE stmt4 FROM @preparedStatement4;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

SET FOREIGN_KEY_CHECKS = 1;
