# Operação de migrations com Phinx

## Princípios

- migrations são executadas somente por CLI, nunca durante requisições HTTP;
- credenciais vêm das variáveis `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
  `DB_USERNAME` e `DB_PASSWORD`;
- o estado formal é registrado em `phinxlog`;
- o histórico SQL anterior ao REFACTOR-045 está congelado;
- novas alterações são classes Phinx em `database/phinx`;
- duas execuções concorrentes são bloqueadas com `GET_LOCK` do MySQL.

## Comandos

No Docker:

```bash
docker compose exec app php cli/migrate.php status
docker compose exec app php cli/migrate.php migrate
docker compose exec app php cli/migrate.php rollback
docker compose exec app php cli/migrate.php create AddExampleColumn
```

O ambiente pode ser selecionado com `PHINX_ENVIRONMENT`, usando
`development`, `testing` ou `production`. Todos leem credenciais do ambiente;
nenhuma senha é versionada em `phinx.php`.

## Instalação vazia

Em um banco sem tabelas da aplicação:

```bash
docker compose exec app php cli/migrate.php migrate
```

O baseline cria o schema atual, mantém somente os papéis/permissões de
referência e elimina empresas, usuários e movimentações de demonstração do dump
histórico. Depois, crie o primeiro Super-Admin:

```bash
docker compose exec app php cli/create-super-admin.php \
  --name="Administrador da Plataforma" \
  --email="admin@example.com"
```

## Adoção por banco existente

O mesmo comando `migrate` detecta um schema existente. Antes de registrar o
baseline, ele exige todas as tabelas, colunas, papéis e permissões considerados
obrigatórios. Um banco parcial é recusado; o comando não tenta completar um
schema desconhecido nem apagar dados.

Faça backup antes da primeira adoção. Compare contagens essenciais antes e
depois, especialmente empresas, usuários, transações e auditoria.

## Nova migration

```bash
docker compose exec app php cli/migrate.php create AddTimezoneToCompanies
```

Regras:

1. prefira `change()` para operações reversíveis suportadas pelo Phinx;
2. use `up()` e `down()` quando a reversão exigir lógica explícita;
3. migrations de dados devem ser determinísticas e idempotentes;
4. nunca altere uma migration já aplicada;
5. valide a instalação vazia e a atualização de um banco existente;
6. documente operações irreversíveis e exija backup validado.

## Rollback

```bash
docker compose exec app php cli/migrate.php rollback
docker compose exec app php cli/migrate.php rollback --target=YYYYMMDDHHMMSS
```

O baseline é propositalmente irreversível: revertê-lo apagaria o schema
completo. Para voltar a uma versão anterior ao baseline, restaure um backup.

MySQL executa muitos comandos DDL com commit implícito. Portanto, mesmo quando
uma migration usa transação, não presuma rollback atômico para toda alteração de
schema.

## Lock e falhas

`migrate` e `rollback` adquirem o lock
`swiftlypark:migrations:<database>`. O tempo máximo de espera é controlado por
`MIGRATION_LOCK_TIMEOUT` e vale 10 segundos por padrão.

Se o processo falhar:

1. não edite manualmente `phinxlog`;
2. leia a migration e o erro do MySQL;
3. compare o schema com o backup;
4. corrija por uma nova migration ou restaure o backup;
5. execute `status` antes de tentar novamente.

## CI

O job de integração começa com um banco MySQL vazio, executa o baseline formal,
carrega somente `tests/Fixtures/integration-database-seed.sql` e então roda os
testes de ledger, concorrência e fila. Isso garante que o projeto não dependa de
um banco pré-configurado.
