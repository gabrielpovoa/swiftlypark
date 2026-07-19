# Contexto de Implementação — Multi-tenancy SwiftlyPark

Este documento registra o contexto arquitetural atual do SwiftlyPark e o plano de migração para uma arquitetura multi-tenant. O objetivo é permitir que um próximo agente de IA ou desenvolvedor humano saiba exatamente por onde começar, quais decisões já foram tomadas e quais fases devem ser seguidas.

## Estado atual do projeto

O SwiftlyPark hoje é uma aplicação PHP MVC com camadas adicionais de Service, Repository, Middleware, RBAC, auditoria e BI financeiro.

As principais áreas já implementadas são:

- autenticação por sessão;
- recuperação de senha por OTP;
- RBAC com papéis e permissões;
- gestão de identidade pelo papel MASTER;
- auditoria de operações CREATE, UPDATE, DELETE e eventos de segurança;
- dashboard financeiro com permissões `finance.view` e `finance.adjust`;
- rastreabilidade via `created_by` e `updated_by` em tabelas operacionais;
- sidebar dinâmica com base em permissões;
- footer/layout responsivo alinhado ao novo design system.

Hoje o sistema ainda opera como single-tenant. Ou seja, os dados de vagas, ocupações, transações, auditoria e usuários não estão isolados por empresa.

## Inventário do que já foi implementado

Esta seção resume as entregas recentes que já fazem parte da base atual e que devem ser consideradas antes de iniciar o multi-tenancy.

### Recuperação de senha por OTP

Foi implementado fluxo de recuperação de senha baseado em OTP.

Arquivos principais:

- `app/Controllers/PasswordRecController.php`;
- `app/Services/OtpService.php`;
- `app/Services/PasswordRecoveryMailer.php`;
- `app/Repositories/PasswordResetRepository.php`;
- `app/Repositories/UserRepository.php`;
- `app/Views/Login/RecoveryPassword.php`;
- `database/migrations/20260705_create_password_resets.sql`;
- `bin/purge-expired-password-resets.php`;
- `docs/specs/spec-auth-password-recovery-otp.md`.

Características:

- OTP temporário;
- hash do OTP persistido;
- limite de tentativas;
- contagem de solicitações;
- expiração;
- limpeza de registros expirados;
- reset de senha com atualização do hash do usuário.

Impacto no multi-tenancy:

- avaliar se `password_resets` deve receber `company_id`;
- se o e-mail de um usuário puder existir em múltiplas empresas no futuro, o fluxo de recuperação precisará considerar o tenant;
- se o e-mail continuar globalmente único, `password_resets` pode permanecer global.

### Auditoria e rastreabilidade

Foi implementada infraestrutura de auditoria para registrar operações operacionais e eventos de segurança.

Arquivos principais:

- `app/Context/IdentityContext.php`;
- `app/Context/RequestIdentity.php`;
- `app/Middleware/IdentityMiddleware.php`;
- `app/Services/AuditService.php`;
- `app/Services/SecurityAuditService.php`;
- `app/Services/AuditLogPresenter.php`;
- `app/Repositories/AuditLogRepository.php`;
- `app/Repositories/Decorators/TransactionalAuditDecorator.php`;
- `app/Transactions/TransactionManager.php`;
- `app/Controllers/AuditController.php`;
- `app/Views/Audit/index.php`;
- `database/migrations/20260706_create_audit_infrastructure.sql`;
- `database/migrations/20260707_extend_audit_security_events.sql`;
- `docs/specs/spec-user-activity-tracking.md`.

Características:

- logs em `audit_logs`;
- registro de `actor_email`;
- registro de `user_id`;
- registro de `request_id`;
- registro de IP;
- `old_values` e `new_values` em JSON;
- tela de auditoria mais amigável, sem depender apenas do JSON técnico;
- filtros por ator e data;
- registros de tentativas de acesso não autorizado;
- colunas `created_by` e `updated_by` em tabelas operacionais.

Impacto no multi-tenancy:

- `audit_logs` deverá receber `company_id`;
- `AuditService` e `SecurityAuditService` deverão receber `TenantContext`;
- os logs operacionais deverão sempre carregar empresa;
- logs globais de MASTER poderão aceitar `company_id = NULL`;
- filtros da tela de auditoria deverão permitir visão por empresa.

### RBAC e autorização por permissão

Foi implementado controle de acesso baseado em papéis e permissões.

Arquivos principais:

- `app/Services/AuthorizationService.php`;
- `app/Middleware/AuthorizeMiddleware.php`;
- `app/Exceptions/ForbiddenException.php`;
- `app/Authorization/DTO/RoleMetadata.php`;
- `app/Authorization/DTO/ResolvedAuthorizationContext.php`;
- `app/Authorization/Contracts/RbacRepositoryInterface.php`;
- `app/Authorization/Contracts/RolePermissionResolverInterface.php`;
- `app/Authorization/Repositories/RbacRepository.php`;
- `app/Authorization/Services/RolePermissionResolver.php`;
- `app/Authorization/Services/NavigationService.php`;
- `database/migrations/20260708_create_rbac_governance.sql`;
- `database/migrations/20260710_repair_rbac_utf8.sql`;
- `docs/specs/spec-auth-rebac-init.md`;
- `docs/specs/spec-auth-rebac-governance.md`.

Características:

- permissões como `vehicle.view`, `vehicle.checkin`, `finance.view`, `identity.manage`;
- `AuthorizationService::can()` e `AuthorizationService::check()`;
- rotas protegidas por `permissionRequired()`;
- sidebar renderizada por permissões;
- tela 403 customizada;
- auditoria de tentativas negadas.

Impacto no multi-tenancy:

- autorização deverá considerar o tenant ativo;
- um usuário poderá ter papéis diferentes por empresa;
- `RolePermissionResolver` precisará considerar `company_user` ou estrutura equivalente;
- o `MASTER` poderá manter permissões globais;
- `ADMIN`, `OPERATOR`, `AUDITOR` e `FINANCE` deverão operar dentro da empresa ativa.

### Gestão de identidade pelo MASTER

Foi implementado painel de gestão de identidade para usuários com permissão de gerenciamento.

Arquivos principais:

- `app/Controllers/IdentityManagementController.php`;
- `app/Identity/Repositories/IdentityManagementRepository.php`;
- `app/Identity/Repositories/UserAccessRepository.php`;
- `app/Identity/Services/IdentityManagementService.php`;
- `app/Views/Identity/index.php`;
- `database/migrations/20260709_create_master_identity_management.sql`;
- `docs/specs/spec-auth-005-master-identity-management.md`.

Características:

- role `master`;
- `deleted_at` em `usuario` para revogação de acesso;
- tabela `user_permissions` para permissões extras;
- revogação de acesso;
- proteção contra lock-out do próprio MASTER;
- atualização de permissões extras;
- auditoria de alterações de permissão.

Impacto no multi-tenancy:

- a criação de usuários deve migrar para esse painel;
- usuários deverão ser vinculados a empresas durante criação;
- revogação pode ser global ou por empresa, dependendo da regra definida;
- `company_user` será parte central do painel;
- a tela deverá exibir empresas vinculadas e papel por empresa.

### BI financeiro

Foi implementado módulo de BI financeiro.

Arquivos principais:

- `app/Controllers/FinanceController.php`;
- `app/Finance/Repositories/FinancialReportRepository.php`;
- `app/Finance/Repositories/FinancialAdjustmentRepository.php`;
- `app/Finance/Services/FinancialReportService.php`;
- `app/Finance/Services/FinancialAdjustmentService.php`;
- `app/Finance/Services/FinancialAuditService.php`;
- `app/Views/Finance/index.php`;
- `app/Views/Finance/print.php`;
- `public/js/FinanceDashboard.js`;
- `database/migrations/20260711_create_finance_analytics.sql`;
- `database/migrations/20260712_backfill_payment_methods.sql`;
- `docs/specs/spec-bi-001-finance-analytics.md`.

Características:

- permissão `finance.view`;
- permissão `finance.adjust`;
- KPIs financeiros;
- receita bruta;
- receita líquida;
- ticket médio;
- taxa de ocupação;
- comparativo anual;
- ajustes financeiros;
- gráfico de faturamento diário;
- gráfico por forma de pagamento;
- exportação CSV;
- relatório imprimível/salvável como PDF;
- estorno auditado;
- ranking de operadores que mais estacionaram veículos;
- backfill sintético de `payment_method` para dados legados.

Impacto no multi-tenancy:

- `transacoes` deverá receber `company_id`;
- `financial_adjustments` deverá receber `company_id`;
- todos os relatórios financeiros deverão filtrar por empresa ativa;
- ranking de operadores deverá considerar apenas check-ins da empresa ativa;
- exportações CSV/PDF devem respeitar `TenantContext`;
- estornos devem ser bloqueados se a transação não pertencer ao tenant ativo.

### Check-in, pagamentos e rastreio operacional

O fluxo de check-in foi atualizado para capturar forma de pagamento e alimentar o BI.

Arquivos principais:

- `app/Controllers/VacancyController.php`;
- `app/Models/VacancyModel.php`;
- `app/Views/Vacancy/apply.php`;
- `app/Services/AuditService.php`.

Características:

- captura `payment_method`;
- opções `PIX`, `CARD`, `CASH`;
- modal de conferência exibe forma de pagamento;
- transação salva com `payment_date`;
- auditoria inclui campos financeiros relevantes;
- `created_by` e `updated_by` rastreiam quem operou.

Impacto no multi-tenancy:

- `vagas_preenchidas`, `vagas_disponiveis` e `transacoes` deverão herdar `company_id`;
- check-in deve salvar `company_id` da empresa ativa;
- não deve ser possível registrar entrada sem tenant;
- disponibilidade de vagas deve ser calculada por empresa.

### UI e layout

Foram feitos ajustes de UI/UX para alinhar o sistema ao novo layout.

Arquivos principais:

- `app/Views/partials/header.php`;
- `app/Views/partials/footer.php`;
- `app/Views/partials/role-badge.php`;
- `app/Views/403/403.php`;
- `app/Views/Home/home.php`;
- `app/Views/Profile/profile.php`;
- `app/Views/Vacancy/vacancy.php`.

Características:

- sidebar dinâmica por permissões;
- role badge;
- tela 403 customizada com GIF;
- footer responsivo com suporte à sidebar;
- dashboard/home com layout ajustado;
- perfil enriquecido com role e informações de segurança.

Impacto no multi-tenancy:

- header/sidebar devem exibir empresa ativa;
- company switcher deve entrar no header/sidebar;
- menus operacionais devem desaparecer quando tenant estiver ausente;
- `role-badge` pode precisar considerar papel por empresa;
- telas públicas não devem receber recuo de sidebar.

### Cadastro público atual

O fluxo de criação pública de conta ainda existe.

Arquivos principais:

- `app/Controllers/CreateAccController.php`;
- `app/Models/CreateAccModel.php`;
- `app/Views/CreateAcc/createacc.php`;
- rotas `GET /CreateAcc` e `POST /CreateAcc/create`.

Comportamento atual:

- qualquer pessoa pode criar conta;
- usuário nasce ativo;
- usuário recebe role `operator`;
- registros são criados em `login`, `usuario` e `user_roles`.

Impacto no multi-tenancy:

- esse fluxo deverá ser desabilitado ou convertido para solicitação de acesso;
- criação de usuário ativo deverá ocorrer pelo MASTER;
- novo usuário deverá nascer vinculado a uma empresa;
- criação deverá ser auditada;
- não deve existir usuário operacional sem empresa.

## Problema que a migração resolve

O objetivo do multi-tenancy é permitir que várias empresas usem a mesma aplicação, garantindo que cada empresa veja apenas seus próprios dados operacionais.

O isolamento será feito por `company_id`.

O sistema deve ser fail-closed:

- sem empresa ativa para usuário operacional, o acesso deve ser negado;
- usuário ADMIN só pode acessar dados da empresa atual;
- usuário MASTER pode operar em escopo global ou selecionar uma empresa específica.

## Conceitos centrais

### Company

Representa o tenant.

Exemplos:

- SwiftlyPark Default;
- Filial Centro;
- Estacionamento Cliente A.

### Company User

Representa o vínculo entre usuário e empresa.

Um mesmo usuário pode estar vinculado a mais de uma empresa.

### TenantContext

Objeto de contexto da requisição que guarda:

- empresa ativa;
- empresas disponíveis para o usuário;
- se o usuário está em escopo global;
- se o tenant é obrigatório para a rota atual.

### TenantMiddleware

Middleware responsável por:

- carregar vínculos entre usuário e empresas;
- resolver empresa ativa;
- validar se o usuário pode operar naquela empresa;
- negar acesso se o contexto estiver ausente ou inválido.

### BaseRepository

Classe base para repositories operacionais.

Deve padronizar a aplicação de filtro por `company_id` em queries.

Regra geral:

- MASTER em rota global pode operar sem filtro de empresa;
- MASTER em empresa selecionada usa filtro por `company_id`;
- ADMIN, OPERATOR, AUDITOR e FINANCE sempre usam filtro por `company_id`;
- ausência de `company_id` em rota operacional deve lançar exceção.

## Situação atual da criação de conta

Atualmente existe cadastro público:

- `GET /CreateAcc`
- `POST /CreateAcc/create`

Arquivos envolvidos:

- `routes/web.php`
- `app/Controllers/CreateAccController.php`
- `app/Models/CreateAccModel.php`
- `app/Views/CreateAcc/createacc.php`

O fluxo atual:

1. usuário acessa `/CreateAcc`;
2. informa nome, e-mail e senha;
3. o sistema cria registros em `login` e `usuario`;
4. o sistema atribui automaticamente o papel `operator`;
5. o usuário nasce ativo.

Esse fluxo deve ser substituído para multi-tenancy.

Decisão arquitetural recomendada:

- remover criação pública de usuários ativos;
- permitir criação de usuários apenas por MASTER;
- exigir vínculo com empresa na criação;
- registrar a ação em auditoria;
- opcionalmente transformar a tela pública em solicitação de acesso, mas não em criação ativa imediata.

## Tabelas que precisarão de `company_id`

As tabelas operacionais e auditáveis deverão receber `company_id`:

- `vagas_disponiveis`;
- `vagas_preenchidas`;
- `transacoes`;
- `financial_adjustments`;
- `audit_logs`.

Outras tabelas podem receber `company_id` em fases posteriores, dependendo da política:

- `password_resets`, caso recuperação de senha precise ser segregada por empresa;
- tabelas futuras de relatórios;
- tabelas futuras de convites/onboarding.

## Estratégia de migração de dados existentes

Para preservar o sistema atual, a migração deve criar uma empresa padrão e associar todos os dados existentes a ela.

Empresa inicial:

- nome: `SwiftlyPark Default`;
- slug: `swiftlypark-default`.

Estratégia:

1. criar tabela `companies`;
2. criar tabela `company_user`;
3. inserir a empresa padrão;
4. vincular todos os usuários atuais à empresa padrão;
5. adicionar `company_id` como `NULL` nas tabelas operacionais;
6. preencher `company_id` com a empresa padrão;
7. validar contagens;
8. mudar `company_id` para `NOT NULL` onde fizer sentido;
9. criar índices e foreign keys.

Essa abordagem evita quebrar dados legados.

## Middleware e ordem de execução

Ordem recomendada:

1. `IdentityMiddleware`
2. `TenantMiddleware`
3. `AuthorizeMiddleware`
4. Controller

Motivo:

- primeiro o sistema identifica o usuário;
- depois resolve a empresa ativa;
- depois valida permissão;
- só então executa a rota.

## Rotas globais e rotas tenant-scoped

Rotas globais permitidas ao MASTER:

- gestão de empresas;
- criação de administradores;
- auditoria global, se implementada;
- configurações globais;
- troca de tenant.

Rotas que devem exigir tenant:

- dashboard operacional;
- vagas;
- check-in;
- checkout;
- relatórios;
- BI financeiro;
- auditoria operacional;
- ajustes financeiros.

## Integração com auditoria

O `AuditService` deve receber `TenantContext` por injeção.

O `company_id` deve ser preenchido automaticamente em cada log, sem que controllers precisem mudar.

Regras:

- evento operacional exige `company_id`;
- evento global do MASTER pode aceitar `company_id = NULL`;
- tentativa de acesso negado deve registrar o `company_id` quando houver contexto;
- troca de empresa deve gerar evento auditável.

Eventos novos sugeridos:

- `TENANT_SWITCHED`;
- `COMPANY_CREATED`;
- `COMPANY_UPDATED`;
- `USER_CREATED`;
- `USER_COMPANY_LINKED`;
- `USER_COMPANY_UNLINKED`.

## UI e UX

O front-end deve receber um contexto enriquecido:

- usuário atual;
- papel atual;
- permissões;
- empresa ativa;
- empresas disponíveis.

Esse contexto deve alimentar:

- sidebar;
- role badge;
- seletor de empresas;
- mensagens de acesso negado;
- ocultação de menus operacionais quando não houver tenant.

O seletor de empresa deve aparecer apenas quando:

- usuário é MASTER;
- usuário possui vínculo com mais de uma empresa.

Fluxo do switcher:

1. MASTER escolhe empresa;
2. sistema valida vínculo;
3. grava `company_id` na sessão;
4. atualiza `TenantContext`;
5. registra auditoria;
6. redireciona para o dashboard.

## Riscos principais

- Queries antigas sem filtro de `company_id` podem vazar dados entre empresas.
- Relatórios financeiros precisam ser revisados com cuidado.
- Auditoria global e auditoria por tenant precisam ser claramente separadas.
- O cadastro público atual é incompatível com governança multi-tenant segura.
- MASTER em escopo global precisa ter rotas muito bem delimitadas.
- Migrações devem ser feitas em etapas para evitar perda de dados.

## Ordem recomendada para o próximo agente

O próximo agente deve começar pela Fase 1 da especificação:

1. ler `docs/specs/spec-multi-tenancy.md`;
2. verificar as migrations atuais;
3. criar migration `companies` e `company_user`;
4. criar empresa default;
5. vincular usuários atuais;
6. adicionar `company_id` nas tabelas operacionais como `NULL`;
7. fazer backfill;
8. só depois endurecer constraints.

Não começar refatorando repositories antes do banco e contexto existirem.

## Critério de sucesso inicial

Após a primeira fase, o sistema deve continuar funcionando exatamente como hoje, mas todos os dados existentes devem estar associados à empresa padrão.

Esse é o ponto seguro para iniciar a refatoração de código.
