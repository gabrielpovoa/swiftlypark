SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS companies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_companies_slug (slug)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_user (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    user_id INT NOT NULL,
    role_id BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_company_user_company_user (company_id, user_id),
    CONSTRAINT fk_company_user_company FOREIGN KEY (company_id)
        REFERENCES companies (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_company_user_user FOREIGN KEY (user_id)
        REFERENCES usuario (id_usuario) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Estratégia segura para adicionar company_id às tabelas operacionais:
-- 1. adicionar como nullable;
-- 2. popular com o ID da empresa padrão para registros legados;
-- 3. definir como NOT NULL quando o volume de dados estiver estabilizado.

ALTER TABLE audit_logs
    ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER user_id;

ALTER TABLE vagas_disponiveis
    ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER status;

ALTER TABLE vagas_preenchidas
    ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER tipo_veiculo;

ALTER TABLE transacoes
    ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER created_by;

-- Popular registros legados com a empresa padrão.
INSERT INTO companies (name, slug)
SELECT 'Default Company', 'default-company'
WHERE NOT EXISTS (SELECT 1 FROM companies LIMIT 1);

SET @default_company_id := (SELECT id FROM companies ORDER BY id ASC LIMIT 1);

UPDATE vagas_disponiveis SET company_id = @default_company_id WHERE company_id IS NULL;
UPDATE vagas_preenchidas SET company_id = @default_company_id WHERE company_id IS NULL;
UPDATE transacoes SET company_id = @default_company_id WHERE company_id IS NULL;

ALTER TABLE vagas_disponiveis
    MODIFY COLUMN company_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE vagas_preenchidas
    MODIFY COLUMN company_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE transacoes
    MODIFY COLUMN company_id BIGINT UNSIGNED NOT NULL;

INSERT INTO company_user (company_id, user_id, role_id, created_at)
SELECT @default_company_id, u.id_usuario, NULL, NOW()
FROM usuario u
WHERE NOT EXISTS (
    SELECT 1
    FROM company_user existing
    WHERE existing.user_id = u.id_usuario
);

ALTER TABLE audit_logs
    ADD CONSTRAINT fk_audit_logs_company
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE vagas_disponiveis
    ADD CONSTRAINT fk_vagas_disponiveis_company
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE vagas_preenchidas
    ADD CONSTRAINT fk_vagas_preenchidas_company
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE transacoes
    ADD CONSTRAINT fk_transacoes_company
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT;
