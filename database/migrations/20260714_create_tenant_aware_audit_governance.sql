SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE audit_logs
    MODIFY COLUMN action VARCHAR(64) NOT NULL;

SET @add_audit_company_id := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE audit_logs ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER user_id',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'audit_logs'
      AND COLUMN_NAME = 'company_id'
);
PREPARE add_audit_company_id_stmt FROM @add_audit_company_id;
EXECUTE add_audit_company_id_stmt;
DEALLOCATE PREPARE add_audit_company_id_stmt;

ALTER TABLE audit_logs
    MODIFY COLUMN company_id BIGINT UNSIGNED NULL;

SET @add_audit_company_created_index := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE audit_logs ADD INDEX idx_audit_logs_company_created (company_id, created_at)',
        'SELECT 1'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'audit_logs'
      AND INDEX_NAME = 'idx_audit_logs_company_created'
);
PREPARE add_audit_company_created_index_stmt FROM @add_audit_company_created_index;
EXECUTE add_audit_company_created_index_stmt;
DEALLOCATE PREPARE add_audit_company_created_index_stmt;
