# REFACTOR-002 — Centralização das Migrations

## Identificação

- Branch: `feature/centralize-database-migrations`
- Base: `develop` em `d6882ce`
- Estado: concluído
- Feature anterior: `REFACTOR-001-MODULAR-ARCHITECTURE-FOUNDATION.md`

## Objetivo

Remover evolução de schema do ciclo HTTP e estabelecer migrations versionadas,
com checksum e registro em `schema_migrations`.

## Problema encontrado

`public/index.php` executa `TenantBootstrap::boot()` em toda requisição. O boot
consulta `information_schema`, executa `CREATE TABLE`, `ALTER TABLE`, backfills
e seeds. Isso aumenta latência, pode causar locks e mistura deploy com runtime.

## Implementação realizada

- Criado `MigrationRunner` em `app/Infrastructure/Database`.
- Criado `cli/migrate.php` com comandos `migrate`, `status` e `baseline`.
- Checksums impedem alteração silenciosa de migrations já aplicadas.
- `baseline` registra o estado de uma instalação existente sem reaplicar SQL.

## Regras operacionais

- Instalação existente e já atualizada: executar uma única vez
  `php cli/migrate.php baseline`.
- Instalação com `schema_migrations`: executar `php cli/migrate.php migrate`.
- `baseline` não executa SQL de negócio e não pode ser usado se já houver
  migrations registradas.
- A aplicação web não deve aplicar migrations automaticamente.

## Arquivos alterados

- Criado `app/Infrastructure/Database/MigrationRunner.php`.
- Criado `cli/migrate.php`.
- Removido `app/Bootstrap/TenantBootstrap.php`.
- Removida a execução de DDL em `public/index.php`.
- `cli/create-master.php` agora exige migrations registradas/aplicadas.
- Atualizado `README.md` com operação de migration e baseline.
- Atualizada a spec de pricing para remover a referência ao bootstrap defensivo.

## Compatibilidade e implantação

O banco local existente recebeu baseline das 21 migrations disponíveis em
18/07/2026. Nenhum SQL de negócio foi reaplicado. Em outro ambiente já
atualizado, o operador deve executar `baseline` uma única vez antes do deploy
que remove o bootstrap.

Um banco completamente vazio ainda precisa do schema legado inicial usado pelo
projeto antes da sequência incremental em `database/migrations`. A criação de
uma migration zero autocontida ficou fora desta etapa para não simular uma
instalação limpa sem validar todas as dependências históricas.

## Segurança

- Checksum SHA-256 bloqueia edição silenciosa de migration aplicada.
- Versões duplicadas são rejeitadas.
- Migration só é registrada depois que seu SQL termina sem exceção.
- A aplicação HTTP deixou de possuir permissão funcional para evoluir schema.

## Validações executadas

- Baseline real: 21 migrations registradas.
- `status`: todas as 21 migrations marcadas como aplicadas.
- Segunda execução de `migrate`: nenhuma migration reaplicada.
- `GET /login`: HTTP 200 sem `TenantBootstrap`.
- Lint de `public/index.php`, CLI e runner: aprovado.
- `git diff --check`: aprovado.
- Todos os testes PHP existentes: aprovados.

## Próximo passo exato

1. Commitar e finalizar esta feature pelo Git Flow.
2. Iniciar `git flow feature start split-admin-provisioning-controller`.
3. Ler este handoff e mapear os métodos públicos do controller por contexto.
4. Extrair primeiro as operações de contratos mensalistas, preservando rotas.
