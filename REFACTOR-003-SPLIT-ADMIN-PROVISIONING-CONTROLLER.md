# REFACTOR-003 — Divisão do AdminProvisioningController

## Identificação

- Branch: `feature/split-admin-provisioning-controller`
- Base: `develop` em `eaae93f`
- Estado: em andamento
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

1. Extrair `companyPricing` e `updateCompanyPricing` para
   `AdminCompanyBillingController`.
2. Manter a leitura de contratos mensalistas na página de cobrança por meio do
   repository existente.
3. Atualizar somente as rotas de visualização e atualização de cobrança.
4. Executar novamente lint, suíte completa e verificação do autoload.
5. Depois separar governança de empresas e provisionamento de usuários.
