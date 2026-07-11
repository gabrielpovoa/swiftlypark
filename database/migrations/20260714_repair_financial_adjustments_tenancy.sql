SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @add_financial_adjustments_company_id := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE financial_adjustments ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER id',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'financial_adjustments'
      AND COLUMN_NAME = 'company_id'
);
PREPARE add_financial_adjustments_company_id_stmt FROM @add_financial_adjustments_company_id;
EXECUTE add_financial_adjustments_company_id_stmt;
DEALLOCATE PREPARE add_financial_adjustments_company_id_stmt;

UPDATE financial_adjustments fa
INNER JOIN transacoes t ON t.id_transacao = fa.transaction_id
SET fa.company_id = t.company_id
WHERE fa.company_id IS NULL;

ALTER TABLE financial_adjustments
    MODIFY COLUMN company_id BIGINT UNSIGNED NOT NULL;

SET @add_financial_adjustments_company_created_index := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE financial_adjustments ADD INDEX idx_financial_adjustments_company_created (company_id, created_at)',
        'SELECT 1'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'financial_adjustments'
      AND INDEX_NAME = 'idx_financial_adjustments_company_created'
);
PREPARE add_financial_adjustments_company_created_index_stmt FROM @add_financial_adjustments_company_created_index;
EXECUTE add_financial_adjustments_company_created_index_stmt;
DEALLOCATE PREPARE add_financial_adjustments_company_created_index_stmt;

SET @add_financial_adjustments_company_fk := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE financial_adjustments ADD CONSTRAINT fk_financial_adjustments_company FOREIGN KEY (company_id) REFERENCES companies (id) ON UPDATE RESTRICT ON DELETE RESTRICT',
        'SELECT 1'
    )
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'financial_adjustments'
      AND CONSTRAINT_NAME = 'fk_financial_adjustments_company'
);
PREPARE add_financial_adjustments_company_fk_stmt FROM @add_financial_adjustments_company_fk;
EXECUTE add_financial_adjustments_company_fk_stmt;
DEALLOCATE PREPARE add_financial_adjustments_company_fk_stmt;
