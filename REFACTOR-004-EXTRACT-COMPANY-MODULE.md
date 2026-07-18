# REFACTOR-004 — Extração do módulo Companies

## Identificação

- Branch: `feature/extract-company-module`
- Base: `develop` após a conclusão da feature 003
- Estado: em andamento
- Feature anterior: `REFACTOR-003-SPLIT-ADMIN-PROVISIONING-CONTROLLER.md`

## Objetivo

Concentrar cadastro, consulta e governança de empresas em um módulo vertical,
sem alterar rotas, tenancy ou contratos de cobrança.

## Estrutura alvo desta feature

```text
app/Companies/
├── Application/
├── Domain/
├── Infrastructure/
└── Presentation/
```

## Inventário inicial

- `app/Models/Company.php` representa empresa, mas está no agrupamento genérico.
- `AdminCompanyController` concentra governança e SQL direto.
- `AdminCompanyBillingController` mistura dados cadastrais com Billing; nesta
  feature apenas a obtenção/alteração cadastral será isolada por uma fronteira
  de aplicação, sem mover regras tarifárias antes da feature 007.
- `TenantRepository` e `TenantContext` são consumidores do cadastro de empresa,
  não pertencem ao módulo Companies.
- Identity e dashboards consultam empresas como consumidores externos.

## Invariantes

- `company_id` continua obrigatório em operações tenant-scoped.
- Empresas inativas não podem virar tenant ativo.
- Slug permanece único e normalizado.
- Rotas administrativas permanecem idênticas.
- Nenhuma regra de tarifário ou contrato mensalista será antecipadamente movida
  para Companies.

## Implementado até agora

- Criada a entidade `Companies\Domain\Company`, responsável por normalizar e
  validar nome e slug.
- Criado o contrato `CompanyRepository` e a implementação
  `PdoCompanyRepository`.
- Criado o caso de uso `RegisterCompany`, mantendo criação, vínculo do ator e
  auditoria na mesma transação.
- A criação de empresa foi removida de `Identity\UserProvisioningService`.
- O diretório administrativo e a contagem de empresas deixaram de executar SQL
  no controller e agora usam o repository de Companies.
- Removido o modelo genérico e sem consumidores `app/Models/Company.php`.
- O teste de governança foi adaptado para o novo caso de uso e foi criado
  `tests/CompanyDomainTest.php`.

## Próximo passo exato

1. Mover edição e inativação para casos de uso em Companies.
2. Remover do controller os helpers SQL remanescentes de cadastro.
3. Criar teste de fronteira modular para impedir dependência Companies ->
   Identity.
4. Executar a suíte completa e finalizar a feature via Git Flow.
