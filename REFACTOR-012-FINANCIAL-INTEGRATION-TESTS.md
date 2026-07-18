# REFACTOR-012 — Testes financeiros de integração

## Identificação

- Branch: `feature/add-financial-integration-tests`
- Base: `develop` após a conclusão da feature 011
- Estado: concluída
- Feature anterior: `REFACTOR-011-FINANCIAL-LEDGER.md`

## Objetivo

Validar fluxos financeiros críticos contra MySQL real, incluindo constraints,
transações, isolamento por empresa, ledger e reporting.

## Estratégia

- Testes ficam em `tests/Integration` e não entram acidentalmente na suíte
  unitária rápida.
- Cada cenário inicia transação e executa rollback no `finally`.
- Nenhum fixture permanente é criado.
- O runner verifica migrations antes de executar os testes.

## Casos obrigatórios

1. Append de crédito e débito.
2. Saldo correto no período.
3. Chave de idempotência rejeita origem duplicada.
4. Repository de relatório lê o ledger.
5. Reconciliação do backfill está consistente.
6. Empresa diferente não enxerga lançamentos do tenant testado.

## Implementado

- Criado `FinancialLedgerIntegrationTest.php`, executado contra MySQL pelo
  container da aplicação.
- O teste cria crédito de `39,56` e débito de `7,00`, validando delta líquido
  de `32,56`.
- Constraint única rejeita a segunda gravação da mesma origem com SQLSTATE
  `23000`.
- `FinancialReportRepository` lê os créditos temporários diretamente do ledger.
- Consulta de outra empresa não inclui os lançamentos do tenant testado.
- `finally` executa rollback e uma asserção posterior confirma zero fixtures
  residuais.
- Reconciliação do backfill é validada após o rollback.
- `tests/run-integration.sh` aplica migrations antes do cenário.

## Evidência local

```text
Banco atualizado; nenhuma migration pendente.
Financial ledger MySQL integration test passed
Financial ledger reconciliation passed
```

## Próximo passo exato

1. Atualizar o roadmap.
2. Finalizar via Git Flow.
3. Iniciar a feature 013 para testes de concorrência.
