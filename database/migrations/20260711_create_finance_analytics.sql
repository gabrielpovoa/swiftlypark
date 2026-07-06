SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE transacoes
    ADD COLUMN payment_method ENUM(
        'PIX',
        'CARD',
        'CASH',
        'UNKNOWN'
    ) NOT NULL DEFAULT 'UNKNOWN' AFTER valor,
    ADD INDEX idx_transacoes_method_date (payment_method, payment_date);

CREATE TABLE financial_adjustments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    adjustment_type ENUM('REFUND', 'CORRECTION') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    reason VARCHAR(500) NOT NULL,
    created_by INT NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    CONSTRAINT chk_financial_adjustments_amount CHECK (amount > 0),
    CONSTRAINT fk_financial_adjustments_transaction
        FOREIGN KEY (transaction_id) REFERENCES transacoes (id_transacao)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_financial_adjustments_user
        FOREIGN KEY (created_by) REFERENCES usuario (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    KEY idx_financial_adjustments_created_at (created_at),
    KEY idx_financial_adjustments_transaction (transaction_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (slug, name) VALUES
    ('finance.view', 'Visualizar BI financeiro'),
    ('finance.adjust', 'Realizar ajustes financeiros');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
LEFT JOIN role_permissions rp
       ON rp.role_id = r.id
      AND rp.permission_id = p.id
WHERE r.slug = 'master'
  AND p.slug IN ('finance.view', 'finance.adjust')
  AND rp.role_id IS NULL;

ALTER TABLE audit_logs
    DROP CHECK chk_audit_logs_action;

ALTER TABLE audit_logs
    ADD CONSTRAINT chk_audit_logs_action
        CHECK (
            action IN (
                'CREATE',
                'UPDATE',
                'DELETE',
                'UNAUTHORIZED_ACCESS_ATTEMPT',
                'ACCESS_REVOKED',
                'USER_PERMISSIONS_UPDATED',
                'FINANCIAL_ADJUSTMENT'
            )
        );
