# REFACTOR-033 — Visão financeira para administrador do tenant

## Objetivo

Permitir que o papel `admin` consulte o BI financeiro completo da empresa ativa,
sem conceder consolidação global nem poderes de ajuste.

## Implementação

- a migration `20260801_grant_admin_finance_view.sql` concede `finance.view` ao
  papel `admin`;
- o menu **Relatórios Financeiros** passa a aparecer pelo RBAC existente;
- administrador pode abrir dashboard, carregar dados, exportar CSV e imprimir;
- `FinancialScope::company()` continua obrigatório para usuários que não sejam
  super-admin;
- repositories financeiros mantêm `company_id` em todas as consultas;
- `finance.adjust` não foi concedida, portanto o formulário e o endpoint de
  estorno permanecem indisponíveis ao administrador.

## Escopos

- `admin`: somente empresa ativa;
- `super-admin`: consolidado global ou filtro por empresa;
- estorno: exige `finance.adjust` e contexto específico de empresa.

## Validação

```bash
php tests/TenantAdminFinanceOverviewTest.php
php tests/FinancialScopeTest.php
php tests/FinanceContextPrecedenceTest.php
php tests/SecurityTestSuite.php
```

## Retomada

- branch: `feature/tenant-admin-finance-overview`;
- base: `develop`;
- migration: `20260801_grant_admin_finance_view.sql`.
