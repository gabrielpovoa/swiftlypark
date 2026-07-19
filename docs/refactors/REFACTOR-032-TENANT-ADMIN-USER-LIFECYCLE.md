# REFACTOR-032 — Ciclo de usuários pelo administrador do tenant

## Objetivo

Formalizar no RBAC que o papel `admin` pode visualizar usuários e administrar o
ciclo de acesso dentro da empresa vinculada.

## Implementação

- a migration `20260731_grant_admin_identity_management.sql` concede ao papel
  `admin` as permissões `identity.view` e `identity.manage`;
- removido o bypass especial de `identity.manage` do `AuthorizationService`;
- o acesso passa a depender exclusivamente das permissões persistidas no RBAC;
- `/identity` oferece criação, revogação e reativação ao administrador;
- empresa inicial do novo usuário é forçada ao tenant ativo;
- listagens mostram somente usuários vinculados à empresa ativa;
- contas `super-admin` permanecem ocultas e protegidas;
- tentativa de enviar outra empresa ou atribuir `super-admin` continua bloqueada.

## Revogação

O administrador pode revogar usuários exclusivos de sua empresa. Se o usuário
também possuir vínculo com outra empresa, a revogação global é bloqueada para não
interromper o acesso de outro tenant.

## Validação

```bash
php tests/TenantAdminUserLifecycleTest.php
php tests/TenantAdminGovernanceTest.php
php tests/UserAccessGuardTest.php
php tests/SecurityTestSuite.php
```

## Retomada

- branch: `feature/tenant-admin-user-lifecycle`;
- base: `develop`;
- migration: `20260731_grant_admin_identity_management.sql`.
