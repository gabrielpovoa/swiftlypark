# Handoff de Implementação SaaS — Fases 1 a 4

Este documento registra o estado da implementação SaaS/multi-tenant do SwiftlyPark até a Fase 4, para que uma nova janela de contexto saiba por onde começar sem reabrir toda a investigação.

## Estado Geral

O SwiftlyPark saiu de um modelo single-tenant para uma base SaaS com:

- contexto de tenant por requisição;
- schema com empresas e vínculos de usuários por empresa;
- isolamento automático em repositórios operacionais;
- auditoria tenant-aware;
- provisionamento administrativo centralizado pelo papel MASTER;
- cadastro público removido;
- criação inicial de MASTER via CLI.

O `public/index.php` chama `TenantBootstrap` antes das rotas. Esse bootstrap faz reparos defensivos de schema em ambientes onde migrations não foram aplicadas na ordem ideal.

## Fase 1 — Foundation & Tenancy Context

Arquivos principais:

- `app/Bootstrap/TenantBootstrap.php`
- `app/Context/TenantContext.php`
- `app/Models/Company.php`
- `app/Middleware/TenantMiddleware.php`
- `app/Repositories/TenantRepository.php`
- `app/Exceptions/TenantNotSetException.php`
- `database/migrations/20260713_create_tenant_foundation.sql`

O que foi implementado:

- tabela `companies`;
- tabela `company_user`;
- coluna `company_id` em tabelas operacionais;
- empresa padrão `SwiftlyPark` com slug `swiftlypark`;
- associação automática de usuários ativos à `SwiftlyPark`;
- `TenantContext` singleton com `setCompany()`, `getCompany()`, `getCompanyId()`, `clear()`;
- `TenantMiddleware` integrado em `authRequired()`;
- resolução de tenant por headers `X-Company-Id` / `X-Tenant-Id`, sessão, subdomínio e fallback;
- validação de vínculo em `company_user`;
- fallback prioriza a empresa `swiftlypark`.

Observação importante:

- O bootstrap promove a primeira empresa existente para `SwiftlyPark` caso ainda não exista uma empresa com slug `swiftlypark`. Isso evita que dados legados fiquem presos em `Default Company`/`Standard Company`.

## Fase 2 — Repository Layer Isolation

Arquivos principais:

- `app/Repositories/BaseRepository.php`
- `app/Repositories/VagasRepository.php`
- `app/Repositories/TransacaoRepository.php`
- `app/Repositories/AuditLogRepository.php`
- `app/Finance/Repositories/FinancialReportRepository.php`
- `app/Finance/Repositories/FinancialAdjustmentRepository.php`
- `tests/RepositoryTenantIsolationTest.php`

O que foi implementado/corrigido:

- `BaseRepository` recebe `TenantContext` e conexão `PDO`;
- `applyTenantFilter()` lança `TenantNotSetException` quando não há tenant;
- filtro aceita alias de coluna, por exemplo `al.company_id`, `t.company_id`, `vp.company_id`;
- filtro é inserido antes de `GROUP BY`, `ORDER BY`, `LIMIT` e `FOR UPDATE`;
- consultas financeiras, auditoria e vagas foram escopadas por tenant;
- modelos legados com SQL direto também passaram a consultar/gravar `company_id`:
  - `app/Models/VacancyModel.php`
  - `app/Models/CreateVacancyModel.php`
  - `app/Models/HomeDashModel.php`
  - `app/Models/LogsModel.php`

Problemas corrigidos nesta fase:

- SQL inválido causado por append de `AND company_id = ...` depois de `ORDER BY`/`LIMIT`;
- uso ambíguo de `company_id` em queries com join;
- inserts operacionais sem `company_id`;
- consultas de dashboard/logs retornando dados cross-tenant.

## Fase 3 — Tenant-Aware Audit & Governance

Arquivos principais:

- `app/Services/AuditService.php`
- `app/Services/SecurityAuditService.php`
- `app/Finance/Services/FinancialAuditService.php`
- `app/Identity/Services/IdentityManagementService.php`
- `app/Repositories/AuditLogRepository.php`
- `app/Controllers/AuditController.php`
- `database/migrations/20260714_create_tenant_aware_audit_governance.sql`
- `database/migrations/20260714_repair_financial_adjustments_tenancy.sql`

O que foi implementado:

- `audit_logs.company_id` nullable;
- índice composto `idx_audit_logs_company_created (company_id, created_at)`;
- `audit_logs.action` ampliado para `VARCHAR(64)`;
- `AuditService` injeta/usa `TenantContext`;
- `AuditService::log(string $action, array $data)` adiciona `company_id` automaticamente;
- callers não devem enviar `company_id` manualmente nos logs;
- eventos globais sem tenant ativo ficam com `company_id = NULL`;
- `AuditLogRepository` estende `BaseRepository`;
- consultas normais de auditoria filtram pelo tenant ativo;
- métodos globais para MASTER:
  - `getGlobalLogs()`
  - `getLogsByCompany(int $companyId)`
- `AuditController` só usa métodos globais quando a identidade tem papel `master`;
- `financial_adjustments` recebeu `company_id`, backfill, índice e FK;
- dashboard financeiro tolera schema parcial de `financial_adjustments`; se a tabela/coluna ainda não estiver pronta, ajustes são tratados como `0`.

Problemas corrigidos nesta fase:

- inserts em `audit_logs` quebrando após `company_id NOT NULL`;
- logs sem tenant indevidamente impossíveis;
- finance sem gráficos quando `financial_adjustments.company_id` ainda não existia;
- `FinanceDashboard.js` falhava silenciosamente; agora renderiza erro visível no painel.

## Fase 4 — Admin Onboarding & Governance

Arquivos principais:

- `app/Identity/Services/UserProvisioningService.php`
- `app/Controllers/AdminProvisioningController.php`
- `app/Middleware/RegistrationBlockedMiddleware.php`
- `app/Services/NotificationService.php`
- `app/Controllers/LoginController.php`
- `app/Models/LoginModel.php`
- `app/Repositories/UserRepository.php`
- `app/Views/Login/login.php`
- `app/Views/Login/RecoveryPassword.php`
- `cli/create-master.php`
- `database/migrations/20260716_create_admin_onboarding_governance.sql`

Remoção do cadastro público:

- removido `app/Controllers/CreateAccController.php`;
- removido `app/Models/CreateAccModel.php`;
- removida `app/Views/CreateAcc/createacc.php`;
- removidos links de cadastro nas views de login/recuperação;
- rotas legadas continuam registradas apenas para retornar 404 via `RegistrationBlockedMiddleware`:
  - `GET /CreateAcc`
  - `GET /CreateAcc/create`
  - `POST /CreateAcc/create`

Provisionamento administrativo:

- endpoint `POST /admin/users/create`;
- endpoint `POST /admin/companies/create`;
- ambos protegidos por autenticação, tenant e validação de papel no backend;
- apenas `master` ou `super-admin` podem provisionar;
- `master` pode criar `admin`, `auditor`, `operator`;
- `master` não pode criar `master` ou `super-admin`;
- somente `super-admin` poderá conceder papel equivalente/superior quando esse papel existir.

`UserProvisioningService` orquestra:

```text
login
  ↓
usuario
  ↓
user_roles
  ↓
company_user
  ↓
notification
  ↓
audit
```

Parâmetros aceitos:

- `name`
- `email`
- `password`
- `company_id`
- `role_id`

Regras implementadas:

- `name` e `email` obrigatórios;
- `password` mínimo de 12 caracteres;
- `company_id` obrigatório e deve existir;
- `role_id` obrigatório e deve existir/estar ativo;
- e-mail precisa estar disponível;
- novo usuário recebe `password_reset_required = 1`;
- novo usuário é vinculado à empresa em `company_user`;
- novo usuário recebe papel em `user_roles`;
- não há e-mail genérico; existe `NotificationService::accountCreated()`;
- auditoria registra mensagem do tipo: `Master X criou usuário Y na empresa Z com papel W`.

Primeiro login com senha obrigatória:

- `LoginModel` carrega `password_reset_required`;
- `LoginController` redireciona para `/login/password-required`;
- a view de reset existente é reutilizada;
- após atualização, `password_reset_required` volta para `0`;
- reset via OTP também zera a flag em `UserRepository::updatePasswordByEmail()`.

Bootstrap CLI para primeiro MASTER:

- arquivo: `cli/create-master.php`
- uso:

```bash
php cli/create-master.php \
  --name="Master" \
  --email="master@swiftlypark.com" \
  --password="SenhaForte123!"
```

O comando:

- executa `TenantBootstrap`;
- aborta se já existir MASTER ativo;
- cria/usa empresa `SwiftlyPark`;
- busca papel `master`;
- usa `UserProvisioningService`;
- evita reabrir cadastro público.

## Rotas Relevantes

Cadastro público bloqueado:

- `GET /CreateAcc` → 404
- `GET /CreateAcc/create` → 404
- `POST /CreateAcc/create` → 404

Admin onboarding:

- `POST /admin/companies/create`
- `POST /admin/users/create`

Troca obrigatória de senha:

- `GET /login/password-required`
- `POST /login/password-required`

Finance:

- `GET /finance`
- `GET /finance/data`
- `GET /finance/export`
- `GET /finance/print`
- `POST /finance/refund`

Auditoria:

- `GET /audit`

## Exemplo de Payloads

Criar empresa:

```json
{
  "name": "Empresa Cliente",
  "slug": "empresa-cliente"
}
```

Criar usuário:

```json
{
  "name": "Ana Operadora",
  "email": "ana@example.com",
  "password": "SenhaInicial123!",
  "company_id": 1,
  "role_id": 3
}
```

## Migrations SaaS Criadas

- `database/migrations/20260713_create_tenant_foundation.sql`
- `database/migrations/20260714_create_tenant_aware_audit_governance.sql`
- `database/migrations/20260714_repair_financial_adjustments_tenancy.sql`
- `database/migrations/20260715_seed_swiftlypark_company_and_repair_operational_tenancy.sql`
- `database/migrations/20260716_create_admin_onboarding_governance.sql`

Observação:

- O projeto não aparenta ter runner formal de migrations. Por isso `TenantBootstrap` também faz reparos defensivos no boot para evitar erros em ambiente Docker/dev quando o banco está parcialmente migrado.

## Comandos de Validação Rodados

Lint geral:

```bash
for file in $(find app cli -name '*.php' -print); do php -l "$file" >/dev/null || exit 1; done
```

Testes simples:

```bash
php tests/TenantContextTest.php
php tests/RepositoryTenantIsolationTest.php
```

Ambos passaram.

## Pontos de Atenção Para a Próxima Janela

1. O worktree tem muitas mudanças ainda não commitadas.
   Antes de refatorar, rode:

```bash
git status --short
```

2. O `TenantBootstrap` faz alterações de schema no boot.
   Isso foi intencional para estabilizar o ambiente atual, mas em produção o ideal é migrar para um runner formal de migrations.

3. O painel visual para criar usuários/empresas ainda não foi construído.
   Existem endpoints JSON, mas não há UI de onboarding no painel `Identity`.

4. A hierarquia `super-admin` foi prevista, mas o papel ainda não é seedado nas migrations atuais.
   Hoje o backend só permite MASTER provisionar papéis abaixo de MASTER.

5. `company_user.role_id` e `user_roles.role_id` são preenchidos em conjunto.
   A autorização atual ainda resolve permissões via `user_roles`; a tabela `company_user` é o vínculo tenant/papel para governança multi-tenant.

6. A auditoria global para MASTER existe no repositório/controller, mas a UI ainda não oferece controles completos de escopo por empresa.

7. Testar no navegador:

- login com usuário existente;
- `/finance/data` deve retornar `200` com JSON;
- `/CreateAcc` deve retornar 404;
- criar usuário via `POST /admin/users/create`;
- login do usuário criado deve exigir troca de senha;
- verificar `company_user`, `user_roles`, `usuario.password_reset_required` e `audit_logs`.

## Consultas SQL Úteis

Empresas:

```sql
SELECT id, name, slug FROM companies;
```

Vínculos:

```sql
SELECT company_id, user_id, role_id FROM company_user ORDER BY company_id, user_id;
```

Papéis:

```sql
SELECT id, slug, name, display_priority FROM roles ORDER BY display_priority;
```

Usuários provisionados:

```sql
SELECT id_usuario, nome, email, deleted_at, password_reset_required
FROM usuario
ORDER BY id_usuario DESC;
```

Auditoria:

```sql
SELECT id, company_id, actor_email, action, entity, entity_id, created_at
FROM audit_logs
ORDER BY id DESC
LIMIT 20;
```

Dados operacionais por tenant:

```sql
SELECT company_id, COUNT(*) FROM vagas_disponiveis GROUP BY company_id;
SELECT company_id, COUNT(*) FROM vagas_preenchidas GROUP BY company_id;
SELECT company_id, COUNT(*) FROM transacoes GROUP BY company_id;
```

