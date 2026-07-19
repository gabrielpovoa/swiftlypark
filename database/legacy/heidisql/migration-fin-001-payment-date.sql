-- FIN-001: desacoplamento entre receita e movimentação operacional.
-- Pré-requisito: backup validado em heidSQL/parking.sql.
-- Convenção: todos os DATETIME financeiros são persistidos em UTC.

USE `parking`;

-- Etapa 1: coluna anulável para permitir backfill sem inventar datas.
ALTER TABLE `transacoes`
    ADD COLUMN `payment_date` DATETIME NULL
    COMMENT 'Data efetiva do pagamento em UTC'
    AFTER `data_transacao`;

-- Etapa 2: preservar exatamente o timestamp histórico já registrado.
UPDATE `transacoes`
SET `payment_date` = `data_transacao`
WHERE `payment_date` IS NULL
  AND `data_transacao` IS NOT NULL;

-- Esta consulta deve retornar zero antes de executar a próxima etapa.
SELECT COUNT(*) AS `transactions_without_payment_date`
FROM `transacoes`
WHERE `payment_date` IS NULL;

-- Etapa 3: garantir a integridade dos próximos registros.
ALTER TABLE `transacoes`
    MODIFY COLUMN `payment_date` DATETIME NOT NULL
    DEFAULT CURRENT_TIMESTAMP
    COMMENT 'Data efetiva do pagamento em UTC',
    ADD INDEX `idx_transacoes_payment_date` (`payment_date`);

-- Índices das métricas operacionais, sem dependência da tabela financeira.
ALTER TABLE `vagas_preenchidas`
    ADD INDEX `idx_vagas_hora_entrada` (`hora_entrada`),
    ADD INDEX `idx_vagas_hora_saida` (`hora_saida`);

-- Auditoria pós-migração: totais devem ser iguais e sem data ausente.
SELECT
    COUNT(*) AS `total_transactions`,
    SUM(`payment_date` IS NULL) AS `without_payment_date`,
    MIN(`payment_date`) AS `first_payment`,
    MAX(`payment_date`) AS `last_payment`
FROM `transacoes`;
