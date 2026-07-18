SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monthly_contracts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    customer_name VARCHAR(120) NOT NULL,
    vehicle_plate VARCHAR(10) NOT NULL,
    vehicle_type VARCHAR(30) NOT NULL,
    monthly_amount DECIMAL(10, 2) NOT NULL,
    starts_at DATE NOT NULL,
    expires_at DATE NOT NULL,
    status ENUM('ACTIVE', 'CANCELLED') NOT NULL DEFAULT 'ACTIVE',
    created_by INT NOT NULL,
    updated_by INT NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_monthly_contract_company_plate (company_id, vehicle_plate),
    KEY idx_monthly_contract_validity (company_id, status, starts_at, expires_at),
    CONSTRAINT fk_monthly_contract_company FOREIGN KEY (company_id)
        REFERENCES companies (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_monthly_contract_created_by FOREIGN KEY (created_by)
        REFERENCES usuario (id_usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_monthly_contract_updated_by FOREIGN KEY (updated_by)
        REFERENCES usuario (id_usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_monthly_contract_vehicle_type CHECK (
        vehicle_type IN ('carro', 'moto', 'caminhao', 'app')
    ),
    CONSTRAINT chk_monthly_contract_amount CHECK (monthly_amount > 0),
    CONSTRAINT chk_monthly_contract_dates CHECK (expires_at >= starts_at)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monthly_contract_payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    contract_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('PIX', 'CARD', 'CASH') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY idx_monthly_payment_company_date (company_id, payment_date),
    KEY idx_monthly_payment_contract (contract_id, payment_date),
    CONSTRAINT fk_monthly_payment_company FOREIGN KEY (company_id)
        REFERENCES companies (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_monthly_payment_contract FOREIGN KEY (contract_id)
        REFERENCES monthly_contracts (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_monthly_payment_created_by FOREIGN KEY (created_by)
        REFERENCES usuario (id_usuario) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_monthly_payment_amount CHECK (amount > 0),
    CONSTRAINT chk_monthly_payment_period CHECK (period_end >= period_start)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
