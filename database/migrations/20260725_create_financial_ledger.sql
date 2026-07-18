SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE financial_ledger_entries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    source_type ENUM('ROTATING_PAYMENT', 'MONTHLY_PAYMENT', 'ADJUSTMENT') NOT NULL,
    source_id BIGINT UNSIGNED NOT NULL,
    entry_type ENUM('CREDIT', 'DEBIT') NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    occurred_at DATETIME(6) NOT NULL,
    description VARCHAR(500) NULL,
    created_by INT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_financial_ledger_source (company_id, source_type, source_id),
    KEY idx_financial_ledger_company_occurred (company_id, occurred_at),
    KEY idx_financial_ledger_company_type (company_id, entry_type, occurred_at),
    CONSTRAINT chk_financial_ledger_amount CHECK (amount > 0),
    CONSTRAINT fk_financial_ledger_company FOREIGN KEY (company_id) REFERENCES companies (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_financial_ledger_created_by FOREIGN KEY (created_by) REFERENCES usuario (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT IGNORE INTO financial_ledger_entries
    (company_id, source_type, source_id, entry_type, amount, occurred_at, description, created_by)
SELECT company_id, 'ROTATING_PAYMENT', id_transacao, 'CREDIT', valor,
       COALESCE(payment_date, data_transacao), 'Pagamento rotativo (backfill)', created_by
FROM transacoes
WHERE valor > 0;

INSERT IGNORE INTO financial_ledger_entries
    (company_id, source_type, source_id, entry_type, amount, occurred_at, description, created_by)
SELECT company_id, 'MONTHLY_PAYMENT', id, 'CREDIT', amount,
       payment_date, 'Pagamento mensalista (backfill)', created_by
FROM monthly_contract_payments
WHERE amount > 0;

INSERT IGNORE INTO financial_ledger_entries
    (company_id, source_type, source_id, entry_type, amount, occurred_at, description, created_by)
SELECT company_id, 'ADJUSTMENT', id, 'DEBIT', amount,
       created_at, LEFT(reason, 500), created_by
FROM financial_adjustments
WHERE amount > 0;
