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

## Próximo passo exato

1. Mapear a entidade `Company` e seus consumidores.
2. Criar repository de Companies com operações cadastrais atualmente em SQL
   direto no controller.
3. Criar casos de uso de aplicação para consultar, editar e inativar empresa.
4. Adaptar controllers sem alterar as rotas.
5. Adicionar testes do limite modular e atualizar este handoff.
