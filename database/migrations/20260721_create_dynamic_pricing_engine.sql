SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @add_companies_is_mensalista := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE companies ADD COLUMN is_mensalista TINYINT(1) NOT NULL DEFAULT 0 AFTER logo_path',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'companies'
      AND COLUMN_NAME = 'is_mensalista'
);
PREPARE add_companies_is_mensalista_stmt FROM @add_companies_is_mensalista;
EXECUTE add_companies_is_mensalista_stmt;
DEALLOCATE PREPARE add_companies_is_mensalista_stmt;

CREATE TABLE IF NOT EXISTS tarifarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    tipo_veiculo VARCHAR(30) NOT NULL,
    valor_base DECIMAL(10, 2) NOT NULL,
    valor_adicional DECIMAL(10, 2) NOT NULL,
    tolerancia_minutos INT UNSIGNED NOT NULL DEFAULT 0,
    frequencia_adicional INT UNSIGNED NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_tarifarios_company_vehicle (company_id, tipo_veiculo),
    KEY idx_tarifarios_company (company_id),
    CONSTRAINT fk_tarifarios_company FOREIGN KEY (company_id)
        REFERENCES companies (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT chk_tarifarios_tipo_veiculo CHECK (
        tipo_veiculo IN ('carro', 'moto', 'caminhao', 'app')
    ),
    CONSTRAINT chk_tarifarios_valor_base CHECK (valor_base >= 0),
    CONSTRAINT chk_tarifarios_valor_adicional CHECK (valor_adicional >= 0),
    CONSTRAINT chk_tarifarios_frequencia CHECK (frequencia_adicional > 0)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
