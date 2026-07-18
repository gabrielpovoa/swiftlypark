SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE vagas_preenchidas
    ADD COLUMN billing_model ENUM('MONTHLY', 'ROTATING') NOT NULL DEFAULT 'ROTATING'
        AFTER tipo_veiculo,
    ADD INDEX idx_vagas_preenchidas_billing_model (company_id, billing_model);

-- Preserva o comportamento das estadias abertas antes desta migration.
UPDATE vagas_preenchidas vp
INNER JOIN companies c ON c.id = vp.company_id
SET vp.billing_model = 'MONTHLY'
WHERE vp.hora_saida IS NULL
  AND c.is_mensalista = 1;
