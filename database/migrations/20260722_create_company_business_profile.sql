SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @add_companies_legal_name := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE companies ADD COLUMN legal_name VARCHAR(255) NULL AFTER name',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'legal_name'
);
PREPARE add_companies_legal_name_stmt FROM @add_companies_legal_name;
EXECUTE add_companies_legal_name_stmt;
DEALLOCATE PREPARE add_companies_legal_name_stmt;

SET @add_companies_trade_name := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE companies ADD COLUMN trade_name VARCHAR(255) NULL AFTER legal_name',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'trade_name'
);
PREPARE add_companies_trade_name_stmt FROM @add_companies_trade_name;
EXECUTE add_companies_trade_name_stmt;
DEALLOCATE PREPARE add_companies_trade_name_stmt;

SET @add_companies_cnpj := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE companies ADD COLUMN cnpj VARCHAR(18) NULL AFTER trade_name',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'cnpj'
);
PREPARE add_companies_cnpj_stmt FROM @add_companies_cnpj;
EXECUTE add_companies_cnpj_stmt;
DEALLOCATE PREPARE add_companies_cnpj_stmt;

UPDATE companies
SET trade_name = name
WHERE trade_name IS NULL OR trade_name = '';
