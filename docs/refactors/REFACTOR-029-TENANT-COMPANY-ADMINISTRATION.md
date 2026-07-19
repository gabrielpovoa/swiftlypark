# REFACTOR-029 — Administração da empresa por tenant

## Objetivo

Permitir que usuários `admin` consultem e alterem dados, tarifários, contratos e
usuários da própria empresa, sem revelar ou permitir operações sobre outros
tenants. O `super-admin` mantém a visão global.

## Regras implementadas

- `super-admin` visualiza e gerencia todas as empresas;
- `admin` visualiza somente empresas presentes em seus vínculos `company_user`;
- edição exige que o vínculo do usuário com a empresa tenha papel `admin` ativo;
- criação e desativação de empresas continuam exclusivas do `super-admin`;
- alteração cadastral, logo, tarifários e contratos aceitam `admin` apenas no
  tenant vinculado;
- o diretório, seus KPIs e contagens são filtrados pelo usuário administrador;
- `/identity` força a empresa ativa como filtro para administradores;
- administradores só podem criar, revogar, reativar ou alterar permissões de
  usuários vinculados à mesma empresa;
- a lista de usuários externos disponíveis para vínculo só é exibida ao
  `super-admin`, evitando vazamento de nomes e e-mails entre tenants.

## Controle central

`CompanyAccessGuard` consulta os vínculos no banco e não confia apenas em sessão,
URL ou campos do formulário. Uma tentativa de enviar outro `company_id` resulta
em `ForbiddenException` e resposta `403`.

## Caso validado

O usuário `productive.mongoose.pjgr@hidepost.net`, administrador da SafePark,
pode abrir `/admin/companies` e a página da SafePark. Uma tentativa de acessar o
ID de outra empresa é bloqueada. O super-admin continua com acesso global.

## Validação

```bash
php tests/CompanyAccessGuardTest.php
php tests/SuperAdminCompanyGovernanceTest.php
php tests/GovernanceUserManagementTest.php
php tests/SecurityTestSuite.php
```

## Retomada

- branch: `feature/tenant-company-administration`;
- base: `develop`;
- não exige migration de banco.
