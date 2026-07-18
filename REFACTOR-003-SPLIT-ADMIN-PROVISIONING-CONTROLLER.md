# REFACTOR-003 — Divisão do AdminProvisioningController

## Identificação

- Branch: `feature/split-admin-provisioning-controller`
- Base: `develop` em `eaae93f`
- Estado: concluída
- Feature anterior: `REFACTOR-002-DATABASE-MIGRATIONS.md`

## Objetivo

Reduzir as 1.563 linhas e as múltiplas responsabilidades de
`AdminProvisioningController` sem alterar URLs, permissões, payloads ou mensagens
visíveis.

## Inventário inicial

### Provisionamento de usuários

- `index`
- `sendTemporaryPassword`
- `createUser`
- `linkExistingUser`
- `removeCompanyAccess`
- `syncCompanyPermissions`

### Governança de empresas

- `createCompany`
- `companiesIndex`
- `updateCompany`
- `deactivateCompany`

### Cobrança da empresa

- `companyPricing`
- `updateCompanyPricing`

### Contratos mensalistas

- `createMonthlyContract`
- `renewMonthlyContract`
- `cancelMonthlyContract`

## Estratégia

Extrair primeiro contratos mensalistas para um controller dedicado, porque as
três rotas formam um limite coeso e possuem menor acoplamento com a listagem de
usuários. Helpers compartilhados não serão copiados indiscriminadamente: regras
de negócio irão para Application Services quando houver consumidor concreto.

Controllers alvo:

```text
AdminUserProvisioningController
AdminCompanyController
AdminCompanyBillingController
AdminMonthlyContractController
```

## Invariantes

- Rotas atuais permanecem iguais.
- Middleware e permissões permanecem iguais.
- CSRF continua obrigatório.
- Criação/renovação e pagamento permanecem na mesma transação.
- Auditoria permanece dentro da transação.
- Toda consulta mantém filtro explícito por empresa.

## Implementado até agora

### Contratos mensalistas

- Criado `app/Controllers/AdminMonthlyContractController.php`.
- Movidos os casos de uso HTTP de criação, renovação e cancelamento.
- Movidos os validadores exclusivos de placa, veículo, valor, data e forma de
  pagamento.
- Preservada a transação única entre contrato, pagamento e auditoria.
- A empresa é bloqueada com `SELECT ... FOR UPDATE` e precisa estar ativa.
- Mantidos os mesmos redirects, mensagens de sessão e tratamento da restrição
  de placa duplicada.
- As URLs não mudaram; somente os callbacks em `routes/web.php` passaram a
  instanciar o controller dedicado.
- Removidas 238 linhas de responsabilidade mensalista do controller original.

### Cobrança e tarifários

- Criado `app/Controllers/AdminCompanyBillingController.php`.
- As duas rotas GET da página da empresa e as duas rotas POST de atualização
  agora usam `show` e `update` no controller dedicado.
- Preservados leitura dos tarifários, contratos mensalistas, mensagens, CSRF,
  autorização, upload seguro da logo e validação dos quatro tipos de veículo.
- Atualização da empresa, upsert dos tarifários e auditoria continuam atômicos,
  com lock pessimista da empresa.
- O teste de fronteira passou a verificar também o roteamento de cobrança.
- Removidos do controller legado os dois endpoints, o validador exclusivo e o
  import de repository que ficaram sem consumidores (204 linhas adicionais).

### Empresas e usuários

- Criado `AdminCompanyController` para criação, diretório, edição e inativação
  de empresas.
- Criado `AdminUserProvisioningController` para usuários, vínculos, senhas
  temporárias e permissões adicionais por empresa.
- Removido definitivamente `AdminProvisioningController`.
- Todas as rotas públicas foram preservadas e apontam para os novos limites.
- O teste de fronteira falha se o controller legado voltar a existir ou se uma
  responsabilidade de empresas retornar ao controller de usuários.

### Validação executada

```text
php -l app/Controllers/AdminMonthlyContractController.php
php -l app/Controllers/AdminProvisioningController.php
php -l routes/web.php
php tests/TenantContextTest.php
php tests/RepositoryTenantIsolationTest.php
php tests/SecurityTestSuite.php
php tests/GovernanceUserManagementTest.php
php tests/PriceCalculatorTest.php
php tests/AdminControllerBoundaryTest.php
```

Todos os comandos passaram. O autoload PSR-4 do novo controller também foi
verificado diretamente.

Foi adicionado `tests/AdminControllerBoundaryTest.php` para proteger o limite
recém-criado: as três rotas precisam continuar no controller mensalista, os
métodos antigos não podem retornar ao controller legado e a implementação deve
manter transação, rollback, lock e auditoria.

## Decisão temporária conhecida

Autorização administrativa, CSRF, redirect e gravação da auditoria estão
encapsulados no novo controller para que a extração não altere o contrato das
rotas. A consolidação desses aspectos em serviços de aplicação será avaliada
depois que os quatro limites administrativos existirem; criar uma hierarquia de
controllers agora aumentaria o acoplamento durante a separação.

## Próximo passo exato

1. Finalizar a branch com Git Flow.
2. Iniciar `feature/extract-company-module` a partir de `develop`.
3. Consultar `REFACTOR-004-EXTRACT-COMPANY-MODULE.md` ao retomar o trabalho.
