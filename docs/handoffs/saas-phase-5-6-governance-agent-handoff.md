# Handoff de Implementação SaaS — Fases 5, 6 e Governança Master

Este documento registra as mudanças feitas após o handoff `saas-phase-1-to-4-implementation-handoff.md`.
Ele serve para um novo agente ou desenvolvedor continuar a implementação sem reabrir toda a investigação.

## Escopo Entregue

Foram implementadas três frentes principais:

- Fase 5: UI/UX multi-tenant, troca de empresa e navegação dinâmica.
- Governança master: diretório de empresas, edição, inativação, auditoria por empresa e permissões do próprio master.
- Branding por tenant: upload de logo por empresa, exibição em administração, sidebar e relatórios.

Também foram aplicadas melhorias do roadmap SaaS:

- seleção explícita de empresa ativa;
- autorização por papel no tenant via `company_user.role_id`;
- manutenção de papéis globais `master` e `super-admin` via `user_roles`.

## Estado Atual de Tenant e Sessão

O tenant ativo é persistido em sessão:

```php
$_SESSION['company_id']
```

Em cada request protegida:

1. `IdentityMiddleware` cria `RequestIdentity`.
2. `TenantMiddleware` resolve e valida a empresa ativa.
3. `TenantContext` recebe um `Company`.
4. Ao fim da request, `TenantContext` é limpo.

O `Company` agora carrega:

- `id`
- `name`
- `slug`
- `logo_path`

Arquivos envolvidos:

- `app/Context/TenantContext.php`
- `app/Models/Company.php`
- `app/Middleware/TenantMiddleware.php`
- `app/Repositories/TenantRepository.php`
- `app/Middleware/IdentityMiddleware.php`

## Endpoints de Contexto SaaS

Foi criado o controller:

- `app/Controllers/ApiTenantController.php`

Rotas:

```text
GET  /api/v1/user/tenants
POST /api/v1/tenant/switch
GET  /api/v1/permissions/context
```

### GET `/api/v1/user/tenants`

Retorna empresas vinculadas ao usuário autenticado.

Formato:

```json
{
  "current_company_id": 1,
  "can_switch": true,
  "tenants": [
    {
      "id": 1,
      "name": "SwiftlyPark",
      "slug": "swiftlypark",
      "logo_path": "companies/swiftlypark-logo.svg",
      "role": {
        "slug": "admin",
        "label": "Administrador"
      }
    }
  ]
}
```

### POST `/api/v1/tenant/switch`

Payload:

```json
{
  "company_id": 2
}
```

Regras:

- valida se o usuário pertence à empresa em `company_user`;
- bloqueia empresa inativa;
- retorna `403` se não houver vínculo;
- atualiza `$_SESSION['company_id']`;
- recalcula permissões e papéis para o tenant novo;
- atualiza `TenantContext` para a request atual.

### GET `/api/v1/permissions/context`

Retorna:

- empresa atual;
- papel;
- roles;
- permissões;
- menu permitido.

O menu vem de `NavigationService::defaultItems()` filtrado por `AuthorizationService`.

## Frontend de Tenant

Foi criado:

- `public/js/TenantContext.js`

Responsabilidades:

- mantém espelho local do `current_company_id` em `localStorage`;
- intercepta `window.fetch`;
- instala interceptor axios quando `window.axios` existir;
- adiciona `X-Company-ID` em chamadas subsequentes;
- executa `POST /api/v1/tenant/switch`;
- mostra loading no switcher;
- faz `window.location.reload()` após troca bem-sucedida.

O script é carregado em:

- `app/Views/partials/footer.php`

## TenantSwitcher e Sidebar

Alterado:

- `app/Views/partials/header.php`

O switcher aparece quando:

- usuário tem `master`;
- usuário tem `super-admin`;
- usuário possui vínculo com mais de uma empresa.

O header/sidebar agora também mostra a logo da empresa ativa ao lado da marca SwiftlyPark quando `logo_path` existe.

Observação visual:

- a marca SwiftlyPark permanece como plataforma;
- a logo do tenant aparece como marca da empresa operacional ativa.

## Navegação Dinâmica

Alterado:

- `app/Authorization/Services/NavigationService.php`

O menu padrão foi centralizado no serviço para que a view e o endpoint de permissões usem a mesma fonte.

Cada item pode ter:

- `href`
- `icon`
- `label`
- `permission`
- `scope`
- `class`
- `id`

Filtro:

```php
$navigation->allowedItems($navigation->defaultItems())
```

## Autorização por Tenant

Alterados:

- `app/Authorization/Contracts/RbacRepositoryInterface.php`
- `app/Authorization/Contracts/RolePermissionResolverInterface.php`
- `app/Authorization/Repositories/RbacRepository.php`
- `app/Authorization/Services/RolePermissionResolver.php`
- `app/Middleware/IdentityMiddleware.php`
- `app/Controllers/LoginController.php`

`RolePermissionResolver::resolve()` agora aceita:

```php
resolve(int $userId, ?int $companyId = null)
```

Quando existe `company_id`, permissões vêm de:

- `company_user.role_id` para permissões tenant-scoped;
- `user_roles` apenas para papéis globais `master` e `super-admin`.

O fallback global por `user_roles` continua existindo quando ainda não há tenant ativo.

## Governança Master

Controller principal:

- `app/Controllers/AdminProvisioningController.php`

Views:

- `app/Views/Admin/provisioning.php`
- `app/Views/Admin/companies.php`

Rotas:

```text
GET  /admin
GET  /admin/companies
POST /admin/companies/create
POST /admin/companies/update
POST /admin/companies/deactivate
POST /admin/users/create
```

### `/admin`

Agora foca em:

- criar empresa;
- adicionar usuário;
- diretório enxuto de usuários.

Usuários:

- por padrão mostra os 3 últimos usuários ativos;
- quando há filtro, mostra até 20;
- filtros por nome/e-mail, empresa e troca de senha pendente.

### `/admin/companies`

Diretório próprio de empresas.

Recursos:

- filtro por nome/slug;
- filtro por status: ativas, inativas, todas;
- cards com:
  - logo;
  - status;
  - vínculos;
  - usuários ativos;
  - pendências de senha;
  - perfis vinculados;
  - resumo de usuários ativos;
- edição inline de nome, slug e logo;
- botão de auditoria da empresa;
- inativação da empresa.

### Inativação de Empresa

A inativação é soft-delete:

```sql
companies.deleted_at
```

Não há exclusão física da empresa.

Fluxo em `deactivateCompany()`:

1. bloqueia a empresa com `FOR UPDATE`;
2. impede inativar a última empresa ativa do usuário atual;
3. marca `companies.deleted_at`;
4. remove vínculos em `company_user` para aquela empresa;
5. revoga `usuario.deleted_at` para usuários que ficaram sem nenhuma empresa ativa;
6. grava auditoria em `audit_logs`.

Decisão importante:

- preservar histórico operacional e auditoria é mais seguro que cascade delete físico.

## Permissões do Próprio Master

Alterados:

- `app/Identity/Services/IdentityManagementService.php`
- `app/Views/Identity/index.php`

Antes, ninguém podia alterar as próprias permissões extras.

Agora:

- `master` e `super-admin` podem alterar as próprias permissões extras;
- auto-revogação continua bloqueada;
- perfis não globais continuam impedidos de alterar as próprias permissões.

## Logos por Empresa

Schema:

```sql
companies.logo_path VARCHAR(255) NULL
```

Arquivos:

- `app/Bootstrap/TenantBootstrap.php`
- `database/migrations/20260717_company_governance_hardening.sql`
- `app/Identity/Services/UserProvisioningService.php`
- `app/Controllers/AdminProvisioningController.php`
- `app/Models/Company.php`
- `app/Repositories/TenantRepository.php`

Upload:

- via `POST /admin/companies/create`;
- via `POST /admin/companies/update`;
- campo de formulário `logo`;
- `multipart/form-data`;
- formatos aceitos: JPG, PNG, WEBP;
- limite: 2 MB;
- destino: `public/uploads/companies`;
- caminho salvo no banco: `companies/<arquivo>`.

Observação:

- O upload valida MIME real com `finfo(FILEINFO_MIME_TYPE)`.
- O cliente não informa `logo_path` diretamente.

## Logo SwiftlyPark Criada

Foi criado o arquivo:

- `public/uploads/companies/swiftlypark-logo.svg`

Banco atualizado diretamente no container:

```json
{
  "id": 1,
  "name": "SwiftlyPark",
  "slug": "swiftlypark",
  "logo_path": "companies/swiftlypark-logo.svg"
}
```

Comando usado, em resumo:

```bash
docker compose exec app php -r '... UPDATE companies SET logo_path = "companies/swiftlypark-logo.svg" WHERE slug = "swiftlypark" ...'
```

Também foi copiado o arquivo para o volume Docker:

```text
/var/www/html/public/uploads/companies/swiftlypark-logo.svg
```

Importante:

- O `compose.yaml` monta `public/uploads` como volume nomeado `swiftly-uploads`.
- Por isso, além de existir no host, o SVG precisou ser copiado para dentro do volume Docker com `docker compose cp`.

## Exibição de Branding

Arquivos alterados:

- `app/Views/partials/header.php`
- `app/Views/Admin/companies.php`
- `app/Views/Finance/index.php`
- `app/Views/Finance/print.php`
- `app/Views/Logs/print.php`
- `app/Controllers/FinanceController.php`
- `app/Controllers/LogsController.php`

Locais onde a logo da empresa aparece:

- sidebar/header ao lado da marca SwiftlyPark;
- diretório de empresas;
- BI financeiro;
- PDF financeiro;
- relatório operacional de logs.

Controllers passam:

```php
'companyBrand' => TenantContext::instance()->getCompany()?->toArray()
```

## Auditoria de Empresas

Alterado:

- `app/Services/AuditLogPresenter.php`
- `app/Controllers/AdminProvisioningController.php`

Eventos de empresa usam:

```text
entity = companies
action = CREATE | UPDATE | DELETE
```

O presenter sabe exibir descrições para:

- empresa criada;
- empresa atualizada;
- empresa inativada.

Campos adicionados ao presenter:

- `company_id`
- `company_name`
- `company_slug`
- `old_name`
- `old_slug`
- `new_name`
- `new_slug`
- `revoked_company_memberships`
- `revoked_user_ids`

O botão **Auditar empresa** aponta para:

```text
/audit?company_id=<id>
```

`AuditController` já suporta `company_id` para master.

## Schema e Migration Nova

Arquivo:

- `database/migrations/20260717_company_governance_hardening.sql`

Adiciona defensivamente:

- `companies.logo_path`;
- `companies.deleted_at`;
- índice `idx_companies_deleted_at`.

`TenantBootstrap` também garante esses campos em runtime para ambientes sem migrations aplicadas.

## Correções Feitas Durante a Iteração

### Warning em `/admin/companies`

Erro:

```text
Warning: Undefined array key "status" in AdminProvisioningController.php
```

Causa:

- ternário acessava `$_GET['status']` diretamente no ramo verdadeiro.

Correção:

- normalização prévia em `companyFilters()`:

```php
$status = (string) ($_GET['status'] ?? 'active');
$status = in_array($status, ['active', 'inactive', 'all'], true)
    ? $status
    : 'active';
```

### Inputs Estourando Cards

Correções na view:

- card com `min-w-0 overflow-hidden`;
- formulário de edição em grid vertical;
- inputs com `min-w-0 w-full`;
- layout responsivo dentro dos cards.

## Arquivos Mais Importantes Para Continuação

Backend:

- `routes/web.php`
- `app/Controllers/ApiTenantController.php`
- `app/Controllers/AdminProvisioningController.php`
- `app/Middleware/IdentityMiddleware.php`
- `app/Middleware/TenantMiddleware.php`
- `app/Repositories/TenantRepository.php`
- `app/Authorization/Repositories/RbacRepository.php`
- `app/Authorization/Services/RolePermissionResolver.php`
- `app/Identity/Services/UserProvisioningService.php`
- `app/Identity/Services/IdentityManagementService.php`
- `app/Bootstrap/TenantBootstrap.php`

Frontend/views:

- `app/Views/partials/header.php`
- `app/Views/partials/footer.php`
- `public/js/TenantContext.js`
- `app/Views/Admin/provisioning.php`
- `app/Views/Admin/companies.php`
- `app/Views/Identity/index.php`
- `app/Views/Finance/index.php`
- `app/Views/Finance/print.php`
- `app/Views/Logs/print.php`

Banco/migrations:

- `database/migrations/20260717_company_governance_hardening.sql`
- `database/migrations/20260713_create_tenant_foundation.sql`
- `database/migrations/20260716_create_admin_onboarding_governance.sql`

Assets:

- `public/uploads/companies/swiftlypark-logo.svg`

## Como Testar

Subir aplicação:

```bash
docker compose up --build
```

Acessos:

```text
Aplicação: http://localhost:8080
phpMyAdmin: http://localhost:8081
```

Testes rápidos:

```bash
php -l app/Controllers/AdminProvisioningController.php
php -l app/Controllers/ApiTenantController.php
php -l app/Middleware/TenantMiddleware.php
php -l app/Views/Admin/companies.php
php -l app/Views/partials/header.php
php -l public/js/TenantContext.js
php tests/TenantContextTest.php
php tests/RepositoryTenantIsolationTest.php
```

Observação:

- `php -l public/js/TenantContext.js` não é válido por ser JS.
- Para JS, usar:

```bash
node --check public/js/TenantContext.js
```

Fluxos manuais:

1. Login como `master`.
2. Abrir `/admin`.
3. Criar empresa com logo.
4. Abrir `/admin/companies`.
5. Filtrar por nome/slug/status.
6. Editar nome/slug/logo.
7. Clicar em **Auditar empresa**.
8. Trocar tenant no sidebar.
9. Conferir se `X-Company-ID` aparece nas chamadas em DevTools.
10. Conferir logo no BI financeiro e relatórios.

## Atenções e Pendências

### Uploads em Docker

`public/uploads` é volume Docker. Arquivos criados no host podem não aparecer dentro do container se o volume já existe.

Para copiar manualmente:

```bash
docker compose cp public/uploads/companies/arquivo.svg app:/var/www/html/public/uploads/companies/arquivo.svg
docker compose exec app chown www-data:www-data /var/www/html/public/uploads/companies/arquivo.svg
```

### Inativação de Empresa

Atualmente:

- inativa empresa;
- remove vínculos `company_user`;
- revoga usuários sem outras empresas ativas.

Não restaura vínculos automaticamente. Reativação ainda não foi implementada.

### Exclusão Física

Não implementar delete físico sem revisar FKs e histórico:

- `vagas_disponiveis`
- `vagas_preenchidas`
- `transacoes`
- `financial_adjustments`
- `audit_logs`

Preferir soft-delete.

### Segurança de Permissões

`master` pode alterar as próprias permissões extras.

Auto-revogação segue bloqueada.

Se for implementar `super-admin`, revisar seeds. O papel é previsto no código, mas não necessariamente seedado.

### Hardening Fase 6

`docs/specs/spec-saas-six.md` fala de fail-closed query guard e security test suite. Ainda não há um mecanismo global de inspeção automática de queries além de `BaseRepository::applyTenantFilter()` e exceções por ausência de `TenantContext`.

Próximo agente deve começar por:

1. Mapear queries legadas fora de `BaseRepository`.
2. Adicionar testes de isolamento cross-tenant para controllers/flows.
3. Criar monitoramento explícito de falhas `TenantNotSetException`.
4. Registrar eventos críticos de tentativa cross-tenant.

## Estado de Validação Já Executado

Foram executados durante a implementação:

```bash
php -l app/Controllers/AdminProvisioningController.php
php -l app/Views/Admin/companies.php
php -l app/Views/Admin/provisioning.php
php -l app/Views/partials/header.php
php -l app/Views/Finance/index.php
php -l app/Views/Finance/print.php
php -l app/Views/Logs/print.php
php -l app/Controllers/FinanceController.php
php -l app/Controllers/LogsController.php
php -l app/Controllers/ApiTenantController.php
php -l app/Middleware/TenantMiddleware.php
php -l app/Models/Company.php
php -l app/Repositories/TenantRepository.php
php -l app/Bootstrap/TenantBootstrap.php
php -l app/Identity/Services/UserProvisioningService.php
php tests/TenantContextTest.php
php tests/RepositoryTenantIsolationTest.php
node --check public/js/TenantContext.js
```

Também foi verificado no banco via Docker:

```json
{
  "id": 1,
  "name": "SwiftlyPark",
  "slug": "swiftlypark",
  "logo_path": "companies/swiftlypark-logo.svg"
}
```
