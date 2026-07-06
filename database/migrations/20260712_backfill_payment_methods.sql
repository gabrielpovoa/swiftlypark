SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- SPEC-BI-001: Backfill sintético para registros financeiros históricos.
-- Observação: estes métodos de pagamento não representam informação real
-- coletada no momento da transação. O objetivo é alimentar os gráficos de BI
-- em bases legadas onde a coluna payment_method ainda estava como UNKNOWN.

SELECT
    payment_method,
    COUNT(*) AS total_before_backfill
FROM transacoes
GROUP BY payment_method
ORDER BY payment_method;

UPDATE transacoes
SET payment_method = CASE FLOOR(RAND() * 3)
    WHEN 0 THEN 'PIX'
    WHEN 1 THEN 'CARD'
    ELSE 'CASH'
END
WHERE payment_method = 'UNKNOWN';

SELECT
    payment_method,
    COUNT(*) AS total_after_backfill
FROM transacoes
GROUP BY payment_method
ORDER BY payment_method;
