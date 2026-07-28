SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE company_vehicle_types (
    company_id BIGINT UNSIGNED NOT NULL,
    vehicle_type ENUM('carro', 'moto', 'caminhao', 'app') NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (company_id, vehicle_type),
    CONSTRAINT fk_company_vehicle_types_company
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT chk_company_vehicle_types_enabled
        CHECK (is_enabled IN (0, 1)),
    KEY idx_company_vehicle_types_enabled (company_id, is_enabled, vehicle_type)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO company_vehicle_types (company_id, vehicle_type, is_enabled)
SELECT companies.id, vehicle_types.vehicle_type, 1
FROM companies
CROSS JOIN (
    SELECT 'carro' AS vehicle_type
    UNION ALL SELECT 'moto'
    UNION ALL SELECT 'caminhao'
    UNION ALL SELECT 'app'
) vehicle_types;
