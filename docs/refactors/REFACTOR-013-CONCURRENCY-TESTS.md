# REFACTOR-013 — Testes de concorrência

## Identificação

- Branch: `feature/add-concurrency-tests`
- Base: `develop` após a conclusão da feature 012
- Estado: concluída
- Feature anterior: `REFACTOR-012-FINANCIAL-INTEGRATION-TESTS.md`

## Objetivo

Provar que check-in, checkout e renovação mensalista não produzem estados
duplicados quando duas requisições disputam o mesmo registro.

## Estratégia

- Duas conexões PDO reais e independentes.
- `innodb_lock_wait_timeout = 1` na conexão concorrente.
- Transações explícitas e rollback no `finally`.
- Constraints de banco complementam locks pessimistas.

## Risco encontrado

Checkout e renovação já bloqueiam registros com `FOR UPDATE`. O check-in
selecionava uma vaga livre antes de abrir sua transação e não possuía constraint
que impedisse duas estadias simultaneamente abertas para a mesma vaga.

## Correção

A migration `20260726_enforce_single_active_stay_per_vacancy.sql` cria a tabela
append/delete `parking_active_stays`, cuja chave primária é
`(company_id, vacancy_id)`. O check-in adquire esse lock na mesma transação e o
checkout o libera. O backfill falha se já houver duas estadias abertas para a
mesma vaga.

## Implementado e validado

- Gateway de Parking adquire o lock ativo depois de criar a estadia e o libera
  antes de marcar a vaga como livre, tudo na mesma transação.
- Teste de check-in usa duas conexões: a segunda aquisição da mesma chave é
  bloqueada/rejeitada.
- Teste de checkout prova contenção no `FOR UPDATE` da estadia.
- Teste de renovação prova contenção no `FOR UPDATE` do contrato mensalista.
- `innodb_lock_wait_timeout = 1` mantém a suíte determinística e rápida.
- Todas as transações de teste executam rollback.

## Evidência local

```text
Concurrent check-in protection passed
Concurrent checkout lock passed
Concurrent monthly renewal lock passed
```

## Próximo passo exato

1. Executar suíte unitária e integração completas.
2. Atualizar o roadmap e finalizar via Git Flow.
3. Iniciar a feature 014 para fila de jobs.
