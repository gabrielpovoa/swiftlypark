# REFACTOR-027 — Escopo do dashboard administrativo por empresa

## Contexto

Um usuário criado como `master` e posteriormente vinculado à SafePark como
`admin` continuava exibindo o perfil `master`. O papel anterior permanecia em
`user_roles`, que era interpretado como global e tinha precedência sobre o papel
da tabela `company_user`.

Além disso, `/admin/dashboard` aceitava `master` como administrador global e
exibia dados consolidados de todas as empresas.

## Modelo adotado

- `super-admin`: papel de plataforma, global e com visão consolidada;
- `master`: gestão máxima dentro das empresas às quais está vinculado;
- `admin`: administração operacional dentro da empresa vinculada;
- `user_roles`: reservado a papéis de plataforma;
- `company_user`: fonte dos papéis e limites de cada tenant.

`master` e `super-admin` podem compartilhar permissões de ação, mas não o mesmo
escopo de dados. O primeiro permanece limitado ao tenant; o segundo pode operar
globalmente e entrar em contexto de suporte.

## Implementação

- novos usuários empresariais não recebem mais papel em `user_roles`;
- somente `super-admin` é persistido como papel global durante provisionamento;
- o middleware só remove o tenant do `/admin/dashboard` para `super-admin`;
- `/admin/dashboard` apresenta:
  - dashboard global para `super-admin`;
  - dashboard operacional filtrado pelo tenant para `master` e `admin`;
- troca de tenant, suporte e auditoria global agora reconhecem somente
  `super-admin` como papel de plataforma;
- migração remove papéis globais empresariais de usuários que já possuem vínculo
  em `company_user`, preservando contas `super-admin`.

## Correção do caso reportado

Após a migração, o usuário `productive.mongoose.pjgr@hidepost.net` mantém apenas
seu vínculo `admin` com a SafePark para fins de autorização. O antigo `master`
global é removido.

## Segurança

- um `admin` não consegue selecionar empresa sem vínculo;
- consultas do dashboard empresarial continuam dependendo de `TenantContext`;
- acesso cruzado continua gerando evento crítico de segurança;
- somente `super-admin` utiliza repositories de consolidação global.

## Validação

```bash
php tests/AdminDashboardTenantScopeTest.php
php tests/GovernanceUserManagementTest.php
php tests/SecurityTestSuite.php
```

## Retomada

- branch: `feature/scope-admin-dashboard-by-company`;
- base: `develop`;
- migration: `20260729_scope_platform_and_tenant_roles.sql`.
