# Hardening & Security Assurance Go-Live

## Alerta para MASTER

O `SecurityAuditService` registra `CROSS_TENANT_ACCESS_ATTEMPT` com severidade `CRITICAL` no payload do evento. Ao detectar 5 tentativas do mesmo usuário/IP em 10 minutos, ele registra `SYSTEMATIC_TENANT_SCAN_DETECTED`, também crítico, para aparecer na trilha global do MASTER.

Uma integração de notificação pode consumir esse evento e enviar e-mail, Slack ou painel interno para usuários com papel `master`.

## Contingência

Se uma implantação deixar dados operacionais sem `company_id`, interrompa a aplicação, faça backup e rode a correção abaixo apontando para a empresa padrão validada:

```sql
START TRANSACTION;
SET @company_id := 1;

UPDATE vagas_disponiveis SET company_id = @company_id WHERE company_id IS NULL;
UPDATE vagas_preenchidas SET company_id = @company_id WHERE company_id IS NULL;
UPDATE transacoes SET company_id = @company_id WHERE company_id IS NULL;
UPDATE financial_adjustments SET company_id = @company_id WHERE company_id IS NULL;

COMMIT;
```

Para reverter a migration de hardening de auditoria:

```sql
ALTER TABLE audit_logs DROP CHECK chk_audit_logs_action;
ALTER TABLE audit_logs ADD CONSTRAINT chk_audit_logs_action
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
```

## Checklist técnico

1. Executar `php tests/TenantContextTest.php`.
2. Executar `php tests/RepositoryTenantIsolationTest.php`.
3. Executar `php tests/SecurityTestSuite.php`.
4. Confirmar que eventos `CROSS_TENANT_ACCESS_ATTEMPT` e `SYSTEMATIC_TENANT_SCAN_DETECTED` aparecem em `audit_logs`.
5. Validar no banco que `vagas_disponiveis`, `vagas_preenchidas`, `transacoes` e `financial_adjustments` não possuem `company_id IS NULL`.
