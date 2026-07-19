# REFACTOR-011 — Ledger financeiro

## Identificação

- Branch: `feature/add-financial-ledger`
- Base: `develop` após a conclusão da feature 010
- Estado: concluída
- Feature anterior: `REFACTOR-010-REPOSITORY-CONTRACTS.md`

## Objetivo

Criar um livro financeiro append-only que unifique créditos rotativos, créditos
mensalistas e débitos de ajustes com origem rastreável e proteção contra
duplicidade.

## Modelo

- `source_type`: `ROTATING_PAYMENT`, `MONTHLY_PAYMENT` ou `ADJUSTMENT`.
- `entry_type`: `CREDIT` ou `DEBIT`.
- `amount`: sempre positivo; direção é definida por `entry_type`.
- Chave única `(company_id, source_type, source_id)`.
- `occurred_at` representa o reconhecimento financeiro; `created_at` representa
  a gravação técnica.

## Migração

`20260725_create_financial_ledger.sql` cria a tabela e executa backfill
idempotente das três fontes existentes com `INSERT IGNORE`.

## Invariantes

- Ledger nunca é atualizado ou apagado pelo fluxo normal.
- Cada origem gera no máximo um lançamento.
- Lançamento e sua origem são persistidos na mesma transação.
- Ajuste é débito; pagamentos são créditos.
- Relatórios antigos continuam disponíveis durante a transição.

## Implementado

- Criada a migration `20260725_create_financial_ledger.sql` com constraints,
  índices, chave de idempotência e backfill das três fontes.
- Criados `LedgerEntry`, `FinancialLedgerRepository`, implementação PDO e
  `FinancialLedgerService`.
- Checkout rotativo grava crédito imediatamente após a transação.
- Pagamento mensalista grava crédito imediatamente após a mensalidade.
- Ajuste financeiro grava débito imediatamente após o ajuste.
- As três gravações fazem parte da transação da origem; falha no ledger provoca
  rollback da operação completa.
- Resumo e gráfico diário de Finance passaram a consultar o ledger consolidado.
- Implementadas consultas de saldo e reconciliação por empresa.
- Migration aplicada no MySQL local; segunda execução não encontrou pendências.
- Backfill local reconciliou 28 créditos rotativos (`1.000,31`), 5 mensalistas
  (`1.520,00`) e 2 débitos de ajuste (`17,00`).

## Próximo passo exato

1. Executar a suíte completa e testes de domínio do ledger.
2. Atualizar o roadmap e finalizar via Git Flow.
3. Iniciar a feature 012 para testes de integração MySQL.
