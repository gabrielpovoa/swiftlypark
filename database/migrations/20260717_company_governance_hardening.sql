SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @add_companies_logo_path := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE companies ADD COLUMN logo_path VARCHAR(255) NULL AFTER slug',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'companies'
      AND COLUMN_NAME = 'logo_path'
);
PREPARE add_companies_logo_path_stmt FROM @add_companies_logo_path;
EXECUTE add_companies_logo_path_stmt;
DEALLOCATE PREPARE add_companies_logo_path_stmt;

SET @add_companies_deleted_at := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE companies ADD COLUMN deleted_at DATETIME(6) NULL AFTER slug',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'companies'
      AND COLUMN_NAME = 'deleted_at'
);
PREPARE add_companies_deleted_at_stmt FROM @add_companies_deleted_at;
EXECUTE add_companies_deleted_at_stmt;
DEALLOCATE PREPARE add_companies_deleted_at_stmt;

SET @add_companies_deleted_at_index := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE companies ADD INDEX idx_companies_deleted_at (deleted_at)',
        'SELECT 1'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'companies'
      AND INDEX_NAME = 'idx_companies_deleted_at'
);
PREPARE add_companies_deleted_at_index_stmt FROM @add_companies_deleted_at_index;
EXECUTE add_companies_deleted_at_index_stmt;
DEALLOCATE PREPARE add_companies_deleted_at_index_stmt;
