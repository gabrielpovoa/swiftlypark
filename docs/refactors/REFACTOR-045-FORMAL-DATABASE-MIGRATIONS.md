# REFACTOR-045 — Migrations formais

## Controle

- Branch: `feature/formal-database-migrations`
- Base: `develop`
- Data: 31/07/2026
- Estado: concluído e integrado à `develop`

## Objetivo

Substituir o runner SQL artesanal por uma ferramenta de migrations com padrão
de mercado, instalação limpa, adoção segura de bancos existentes, status,
rollback e proteção contra execução concorrente.

## Implementação

- Adicionado Phinx `0.16.12` como dependência operacional.
- Criado `phinx.php`, configurado exclusivamente por variáveis de ambiente.
- O estado formal passou a ser registrado em `phinxlog`.
- Criado o baseline `20260701000000_baseline_current_schema.php`.
- Removidos `MigrationRunner` e `tests/ci/migrate.php`.
- `cli/migrate.php` agora encapsula os comandos `migrate`, `status`, `rollback`
  e `create` do Phinx.
- `migrate` e `rollback` usam `GET_LOCK`/`RELEASE_LOCK` do MySQL para impedir
  concorrência entre deploys.
- `create-super-admin.php` exige que o baseline formal tenha sido registrado.
- O histórico em `database/migrations` foi congelado e permanece somente como
  fonte do baseline.

## Estratégia de baseline

### Banco vazio

- lê somente o schema do dump legado, ignorando inserts e locks de dados;
- aplica a evolução SQL histórica congelada;
- formaliza os papéis `super-admin` e `finance`, que existiam no banco atual mas
  não estavam declarados em migration;
- mantém papéis, permissões e grants de referência;
- remove empresas, usuários e movimentações de demonstração;
- reinicia os principais `AUTO_INCREMENT` em `1`;
- valida todas as tabelas e colunas obrigatórias antes de concluir.

### Banco existente

- não reaplica o histórico SQL;
- exige tabelas, colunas, papéis e permissões do schema atual;
- recusa bancos parciais;
- registra o baseline somente após a validação;
- não altera dados operacionais durante a adoção.

O banco local foi adotado mantendo exatamente as mesmas contagens de empresas,
usuários, transações e eventos de auditoria antes e depois.

## Rollback

Novas migrations podem usar `change()` ou implementar `up()`/`down()`. O
baseline é propositalmente irreversível, pois seu rollback apagaria o schema
completo. Retorno anterior ao baseline exige restauração de backup.

MySQL possui commit implícito para diversos comandos DDL; por isso o runbook não
trata migrations de schema como atomicamente reversíveis.

## CI e fixtures

- O job de integração agora parte de um banco MySQL vazio.
- Executa `php cli/migrate.php migrate` com ambiente `testing`.
- Carrega dados mínimos e não sensíveis de
  `tests/Fixtures/integration-database-seed.sql`.
- Executa testes de ledger, concorrência e fila sobre o schema recém-criado.

## Segurança

- Nenhuma credencial foi adicionada ao repositório.
- O parser SQL preserva ponto e vírgula dentro de strings e rejeita
  `DELIMITER`, evitando execução ambígua.
- Result sets são consumidos e fechados antes do próximo comando.
- O dump legado não injeta usuários ou movimentações na instalação limpa.
- O lock possui timeout configurável por `MIGRATION_LOCK_TIMEOUT`.
- Banco parcial não é marcado falsamente como atualizado.

## Testes executados

```bash
php tests/SqlScriptExecutorTest.php
php tests/FormalDatabaseMigrationsTest.php
bash tests/ci/run-unit.sh
bash tests/ci/run-static-analysis.sh
composer audit --no-dev
```

Também foram validados em MySQL 8:

- instalação partindo de banco vazio;
- ausência de empresas, usuários e transações de exemplo;
- cinco papéis e quatorze permissões de referência;
- `AUTO_INCREMENT` inicial da empresa igual a `1`;
- segunda execução idempotente;
- recusa de banco parcial;
- adoção do banco local sem alteração de dados;
- bloqueio de duas migrations concorrentes;
- rollback seguro recusado no baseline;
- testes de integração de ledger, concorrência e fila.

## Operação

O runbook completo está em
`docs/operations/database-migrations.md`. O fluxo normal é:

```bash
docker compose exec app php cli/migrate.php status
docker compose exec app php cli/migrate.php migrate
docker compose exec app php cli/migrate.php create NomeDaMigration
```

## Próximo passo

Executar o REFACTOR-047 para robustez da fila, criando estados terminais,
idempotência, recuperação de jobs abandonados, backoff e dead-letter queue.

## Retomada

A feature foi finalizada com a tag
`refactor-045-formal-database-migrations`. A próxima alteração deve partir de
`develop`; `main` permanece intocada.
