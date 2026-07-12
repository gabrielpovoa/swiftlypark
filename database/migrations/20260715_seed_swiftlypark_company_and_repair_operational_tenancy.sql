SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @existing_swiftlypark_company_id := (
    SELECT id FROM companies WHERE slug = 'swiftlypark' LIMIT 1
);

SET @first_company_id := (
    SELECT id FROM companies ORDER BY id ASC LIMIT 1
);

UPDATE companies
SET name = 'SwiftlyPark',
    slug = 'swiftlypark',
    updated_at = NOW()
WHERE @existing_swiftlypark_company_id IS NULL
  AND @first_company_id IS NOT NULL
  AND id = @first_company_id;

INSERT INTO companies (name, slug, created_at, updated_at)
SELECT 'SwiftlyPark', 'swiftlypark', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM companies WHERE slug = 'swiftlypark'
);

SET @swiftlypark_company_id := (
    SELECT id FROM companies WHERE slug = 'swiftlypark' LIMIT 1
);

SET @add_vagas_disponiveis_company_id := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE vagas_disponiveis ADD COLUMN company_id BIGINT UNSIGNED NULL',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vagas_disponiveis'
      AND COLUMN_NAME = 'company_id'
);
PREPARE add_vagas_disponiveis_company_id_stmt FROM @add_vagas_disponiveis_company_id;
EXECUTE add_vagas_disponiveis_company_id_stmt;
DEALLOCATE PREPARE add_vagas_disponiveis_company_id_stmt;

SET @add_vagas_preenchidas_company_id := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE vagas_preenchidas ADD COLUMN company_id BIGINT UNSIGNED NULL',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vagas_preenchidas'
      AND COLUMN_NAME = 'company_id'
);
PREPARE add_vagas_preenchidas_company_id_stmt FROM @add_vagas_preenchidas_company_id;
EXECUTE add_vagas_preenchidas_company_id_stmt;
DEALLOCATE PREPARE add_vagas_preenchidas_company_id_stmt;

SET @add_transacoes_company_id := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE transacoes ADD COLUMN company_id BIGINT UNSIGNED NULL',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'transacoes'
      AND COLUMN_NAME = 'company_id'
);
PREPARE add_transacoes_company_id_stmt FROM @add_transacoes_company_id;
EXECUTE add_transacoes_company_id_stmt;
DEALLOCATE PREPARE add_transacoes_company_id_stmt;

UPDATE vagas_disponiveis
SET company_id = @swiftlypark_company_id
WHERE company_id IS NULL;

UPDATE vagas_preenchidas
SET company_id = @swiftlypark_company_id
WHERE company_id IS NULL;

UPDATE transacoes
SET company_id = @swiftlypark_company_id
WHERE company_id IS NULL;

ALTER TABLE vagas_disponiveis
    MODIFY COLUMN company_id BIGINT UNSIGNED NOT NULL;

ALTER TABLE vagas_preenchidas
    MODIFY COLUMN company_id BIGINT UNSIGNED NOT NULL;

ALTER TABLE transacoes
    MODIFY COLUMN company_id BIGINT UNSIGNED NOT NULL;

INSERT INTO company_user (company_id, user_id, role_id, created_at)
SELECT @swiftlypark_company_id, u.id_usuario, NULL, NOW()
FROM usuario u
WHERE u.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1
      FROM company_user existing
      WHERE existing.user_id = u.id_usuario
  );

SET @add_vagas_disponiveis_company_index := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE vagas_disponiveis ADD INDEX idx_vagas_disponiveis_company_id (company_id)',
        'SELECT 1'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vagas_disponiveis'
      AND INDEX_NAME = 'idx_vagas_disponiveis_company_id'
);
PREPARE add_vagas_disponiveis_company_index_stmt FROM @add_vagas_disponiveis_company_index;
EXECUTE add_vagas_disponiveis_company_index_stmt;
DEALLOCATE PREPARE add_vagas_disponiveis_company_index_stmt;

SET @add_vagas_preenchidas_company_index := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE vagas_preenchidas ADD INDEX idx_vagas_preenchidas_company_id (company_id)',
        'SELECT 1'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vagas_preenchidas'
      AND INDEX_NAME = 'idx_vagas_preenchidas_company_id'
);
PREPARE add_vagas_preenchidas_company_index_stmt FROM @add_vagas_preenchidas_company_index;
EXECUTE add_vagas_preenchidas_company_index_stmt;
DEALLOCATE PREPARE add_vagas_preenchidas_company_index_stmt;

SET @add_transacoes_company_index := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE transacoes ADD INDEX idx_transacoes_company_id (company_id)',
        'SELECT 1'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'transacoes'
      AND INDEX_NAME = 'idx_transacoes_company_id'
);
PREPARE add_transacoes_company_index_stmt FROM @add_transacoes_company_index;
EXECUTE add_transacoes_company_index_stmt;
DEALLOCATE PREPARE add_transacoes_company_index_stmt;
