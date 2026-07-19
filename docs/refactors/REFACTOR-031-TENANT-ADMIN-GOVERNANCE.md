# REFACTOR-031 — Governança SaaS para administradores de tenant

## Objetivo

Permitir acesso de usuários `admin` à tela `/admin` sem expor contas globais ou
dados de outras empresas.

## Comportamento

- `admin` acessa Governança SaaS por meio da permissão `identity.manage`;
- empresas, usuários, indicadores e filtros são forçados para a empresa ativa;
- contas que possuem papel global `super-admin` são excluídas de listagens,
  seletores e contagens apresentadas ao administrador;
- a lista de papéis atribuíveis ao `admin` não contém `super-admin`;
- envio de senha temporária continua protegido por `UserAccessGuard` antes de
  alterar senha ou criar job de e-mail;
- manipular `user_id`, `company_id` ou `role_id` não amplia o escopo;
- o `super-admin` mantém a visualização global completa.

## Caso reportado

O administrador `productive.mongoose.pjgr@hidepost.net` pode acessar `/admin` e
gerenciar usuários da SafePark, mas não visualiza nem consegue executar ações
sobre `joaopovoa6@gmail.com` ou qualquer outra conta `super-admin`.

## Validação

```bash
php tests/TenantAdminGovernanceTest.php
php tests/UserAccessGuardTest.php
php tests/SecurityTestSuite.php
```

## Retomada

- branch: `feature/tenant-admin-governance`;
- base: `develop`;
- não exige migration de banco.
