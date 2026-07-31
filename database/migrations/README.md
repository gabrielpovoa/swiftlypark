# Histórico SQL congelado

Estes arquivos representam a evolução legada aplicada antes da adoção do Phinx
no REFACTOR-045. Eles são consumidos exclusivamente pelo baseline de instalação
limpa e não devem receber novas migrations nem ser alterados.

Novas mudanças de schema devem ser criadas em `database/phinx`:

```bash
docker compose exec app php cli/migrate.php create NomeDaMigration
```

A rastreabilidade atual usa a tabela `phinxlog`. A antiga tabela
`schema_migrations` pode permanecer em instalações existentes apenas como
registro histórico.
