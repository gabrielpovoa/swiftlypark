# REFACTOR-008 — Extração do módulo Finance

## Identificação

- Branch: `feature/extract-finance-module`
- Base: `develop` após a conclusão da feature 007
- Estado: concluída
- Feature anterior: `REFACTOR-007-EXTRACT-BILLING-MODULE.md`

## Objetivo

Organizar relatórios, ajustes, auditoria e reconhecimento de receitas em um
módulo Finance vertical, deixando regras de preço e contrato em Billing.

## Inventário inicial

```text
app/Finance/
├── Repositories/
│   ├── FinancialAdjustmentRepository.php
│   └── FinancialReportRepository.php
└── Services/
    ├── FinancialAdjustmentService.php
    ├── FinancialAuditService.php
    └── FinancialReportService.php
```

- `FinanceController` ainda está no diretório global de controllers.
- Billing já foi removido de Finance na feature 007.
- Dashboard global usa repository agregado próprio; nesta feature ele continua
  consumidor de dados financeiros, sem absorver regras de Finance.

## Estrutura alvo

```text
app/Finance/
├── Application/
├── Domain/
├── Infrastructure/
└── Presentation/
```

## Invariantes

- Receita rotativa vem de transações concluídas.
- Receita mensalista vem do pagamento da contratação/renovação.
- Ajustes mantêm motivo, ator e trilha de auditoria.
- Relatórios continuam isolados por empresa e período.
- Rotas, filtros, exportações e KPIs permanecem compatíveis.

## Implementado

- Serviços movidos para `Finance\Application`.
- Repositories PDO movidos para `Finance\Infrastructure`.
- Controller movido para `Finance\Presentation` e rotas atualizadas sem mudar
  URLs.
- Criados `RevenueSource` e `AdjustmentType` em Domain; reembolso usa o enum em
  vez de literal livre.
- Diretórios técnicos antigos removidos.
- Criados testes de domínio e fronteira, inclusive uma proteção para impedir o
  retorno de Pricing/Monthly Contracts a Finance.
- Smoke tests sem autenticação de `/finance`, `/finance/data`,
  `/finance/export` e `/finance/print` retornaram `302`, preservando os guards.

## Próximo passo exato

1. Executar a suíte completa e smoke tests HTTP.
2. Atualizar o roadmap e finalizar via Git Flow.
3. Iniciar a feature 009 para value objects compartilhados.
