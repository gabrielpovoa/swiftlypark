# REFACTOR-028 — Remoção do papel Master

## Contexto

O papel `master` sobrepunha responsabilidades do `super-admin`, gerava ambiguidade
na interface e já havia causado promoção indevida de usuários empresariais. A
gestão do diretório de empresas também estava acessível por permissão genérica,
sem exigir explicitamente administração global.

## Modelo final

- `super-admin`: único administrador global da plataforma;
- `admin`: administrador limitado às empresas vinculadas;
- demais papéis continuam restritos às permissões e empresas associadas;
- o papel `master` deixa de existir.

## Implementação

- todas as rotas GET e POST sob `/admin/companies` exigem `super-admin`;
- o item **Empresas** do menu só aparece para `super-admin`;
- o fluxo de provisionamento não oferece nem reconhece `master`;
- criação de empresa vincula somente o `super-admin` responsável;
- proteção contra revogação agora preserva o último `super-admin` ativo;
- alertas críticos são direcionados ao `SUPER_ADMIN`;
- o utilitário legado `cli/create-master.php` foi substituído por
  `cli/create-super-admin.php`.

## Migração de dados

`20260730_remove_master_role.sql` executa, nesta ordem:

1. converte vínculos empresariais `master` para `admin`;
2. remove atribuições globais do papel;
3. remove suas permissões associadas;
4. remove o registro da tabela `roles`.

No banco local, o vínculo `master` existente na SafePark passa a `admin`. O
super-admin principal mantém seu papel global `super-admin`.

## Segurança

Ocultar o menu é apenas uma melhoria visual. A barreira efetiva está no router:
requisições diretas às páginas, uploads, tarifários, contratos, atualizações e
desativação de empresas também recebem `403` quando o usuário não é super-admin.

## Validação

```bash
php tests/SuperAdminCompanyGovernanceTest.php
php tests/GovernanceUserManagementTest.php
php tests/AdminDashboardTenantScopeTest.php
php tests/SecurityTestSuite.php
```

## Retomada

- branch: `feature/remove-master-role`;
- base: `develop`;
- migration: `20260730_remove_master_role.sql`.
