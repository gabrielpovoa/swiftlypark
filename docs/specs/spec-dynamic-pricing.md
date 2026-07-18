# Spec-FIN-002: Migração para Pricing Engine Dinâmico

## 1. Overview

Esta especificação define a migração do modelo atual de cobrança para um **Pricing Engine Dinâmico**, desacoplando o pagamento do momento do check-in para operações rotativas e introduzindo um mecanismo de tarifação baseado no tempo de permanência.

O sistema deverá suportar dois modelos distintos de operação:

- **Mensalista (pré-pago)**;
- **Rotativo (pós-pago)**.

### Status da implementação

Legenda:

- `[x]` concluído;
- `[~]` parcialmente concluído;
- `[ ]` pendente.

Estado geral: **Pricing Engine rotativo e ciclo operacional mensalista implementados**.

Principais entregas realizadas:

- migration de `companies.is_mensalista` e da tabela `tarifarios`;
- migrations versionadas registradas por `schema_migrations` e executadas via CLI;
- `PricingRepository` e `PriceCalculator`;
- check-in rotativo sem cobrança;
- checkout pós-pago com cálculo no backend;
- registro financeiro, atualização da ocupação, liberação da vaga e auditoria na mesma transação SQL;
- método de pagamento solicitado no checkout rotativo;
- sanitização dos inputs alterados por este fluxo e dos payloads de auditoria;
- proteção CSRF no checkout;
- página administrativa individual por empresa (`/admin/companies/company_id={id}`) para editar identidade cadastral, selecionar o modelo de cobrança e configurar tarifários;
- contratos mensalistas por empresa e placa, com contratação, renovação, cancelamento e histórico de pagamentos;
- validação de contrato ativo, vigente e compatível com o tipo de veículo durante o check-in mensalista;
- classificação da estadia como `MONTHLY` ou `ROTATING`, permitindo veículos avulsos em empresas com mensalistas;
- consolidação financeira de mensalidades e pagamentos rotativos, com KPIs e séries diárias separados por origem;
- teste automatizado da fórmula de tarifação.

Principais pendências:

- revisão global dos demais campos livres legados fora do fluxo alterado;
- teste integrado do checkout contra MySQL e teste end-to-end no navegador.

---

## 2. Objetivos

- Implementar um motor de tarifação baseado em tempo.
- Separar completamente o fluxo financeiro do check-in.
- Manter compatibilidade com empresas que operam exclusivamente com mensalistas.
- Permitir configuração individual de tarifários por empresa.
- Garantir integridade financeira durante o checkout.

---

## 3. Modelagem de Dados

### Alteração da Tabela `companies`

Adicionar o seguinte campo:

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `is_mensalista` | Boolean | Habilita contratos mensalistas; veículos sem contrato continuam no modelo rotativo |

---

### Nova Tabela `tarifarios`

Responsável por armazenar as regras de cobrança de cada empresa.

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `id` | PK | Identificador do tarifário |
| `company_id` | FK | Empresa proprietária da tabela de preços |
| `tipo_veiculo` | VARCHAR | Categoria do veículo (`carro`, `moto`, `caminhao`, `app`) |
| `valor_base` | DECIMAL | Valor cobrado pelo primeiro período |
| `valor_adicional` | DECIMAL | Valor cobrado por período adicional |
| `tolerancia_minutos` | Integer | Tempo de tolerância sem cobrança |
| `frequencia_adicional` | Integer | Intervalo, em minutos, para aplicação da cobrança adicional |

---

## 4. Fluxos de Negócio

### 4.1 Fluxo Mensalista

Quando:

```text
is_mensalista = true
```

o sistema deverá operar conforme o fluxo abaixo.

#### Check-in

- [x] validar se o veículo possui contrato ativo e vigente para a empresa, placa e tipo de veículo;
- [x] classificar contratos válidos como mensalistas e veículos sem contrato como rotativos.

> Implementado com `monthly_contracts` e `MonthlyContractRepository`. Contratos cancelados, ainda não iniciados ou vencidos não concedem o benefício mensalista; o veículo continua podendo entrar como rotativo pós-pago.

---

#### Pagamento

O pagamento será registrado durante:

- [x] contratação;
- [x] renovação do plano.

Os pagamentos são persistidos em `monthly_contract_payments` na mesma transação SQL da criação ou renovação do contrato.

- [x] Nenhum cálculo financeiro deverá ocorrer no checkout.

---

#### Checkout

- [x] O checkout apenas encerrará a ocupação da vaga.

- [x] Nenhuma nova transação financeira será criada.

---

### 4.2 Fluxo Rotativo

Quando:

```text
is_mensalista = false
```

o sistema utilizará cobrança pós-paga.

---

#### Check-in

Durante o check-in:

- [x] a vaga será marcada como ocupada;
- [x] nenhuma transação financeira será criada.

---

#### Checkout

Durante o checkout o sistema deverá:

1. [x] calcular o tempo de permanência;
2. [x] calcular o valor devido;
3. [x] solicitar o método de pagamento;
4. [x] registrar a transação financeira;
5. [x] liberar a vaga.

---

## 5. Cálculo da Tarifa

A duração da permanência será calculada através da expressão:

```text
Δt = HoraSaída - HoraEntrada
```

O valor final deverá seguir a fórmula:

```text
ValorTotal =
ValorBase +
(
ceil(
max(0, Δt - Tolerancia)
/
FrequenciaAdicional
)
× ValorAdicional
)
```

Onde:

- **ValorBase** representa a cobrança inicial;
- **Tolerância** representa o período gratuito;
- **FrequênciaAdicional** representa o intervalo de cobrança;
- **ValorAdicional** representa o valor cobrado a cada intervalo adicional.

---

## 6. Segurança e Integridade

### Sanitização de Entradas

Todos os campos de texto livres deverão ser sanitizados antes da persistência.

Exemplos:

- observações;
- justificativas;
- motivos de ajustes;
- comentários.

O backend deverá remover conteúdo potencialmente malicioso utilizando mecanismos apropriados, como:

- `strip_tags()`;
- escape do framework;
- filtros equivalentes.

---

### Proteção contra XSS

Durante a exibição de informações provenientes do banco de dados, especialmente da tabela `audit_logs`, todo conteúdo deverá ser tratado como texto simples.

Em nenhuma circunstância o sistema deverá executar conteúdo HTML ou JavaScript armazenado nos registros.

---

### Integridade Transacional

O checkout deverá ocorrer dentro de uma única transação SQL.

Fluxo esperado:

1. BEGIN;
2. Cálculo da tarifa;
3. Registro da transação financeira;
4. Atualização da vaga;
5. Registro da auditoria;
6. COMMIT.

Caso qualquer etapa falhe:

- executar `ROLLBACK`;
- manter a vaga ocupada;
- impedir conclusão do checkout.

---

## 7. Auditoria

Cada checkout deverá gerar automaticamente um registro na tabela `audit_logs`.

Informações mínimas:

| Campo | Descrição |
|---------|-----------|
| Operador | Usuário responsável pelo checkout |
| Valor Base | Tarifa inicial utilizada |
| Frequência Adicional | Intervalo de cobrança aplicado |
| Hora de Entrada | Timestamp do check-in |
| Hora de Saída | Timestamp do checkout |
| Valor Calculado | Valor final da cobrança |

Esses registros deverão permitir rastrear completamente o cálculo realizado pelo Pricing Engine.

---

## 8. Critérios de Aceitação

- [x] A tabela `companies` possui o campo `is_mensalista`.
- [x] Existe uma tabela `tarifarios`.
- [x] Empresas mensalistas utilizam fluxo pré-pago.
  - contratação e primeira mensalidade são atômicas;
  - renovação cria uma nova vigência e registra o pagamento;
  - cancelamento bloqueia novos check-ins;
  - checkout encerra a ocupação sem nova cobrança.
- [x] Empresas com mensalistas também aceitam veículos avulsos no fluxo rotativo pós-pago.
- [x] Dashboard, BI, CSV e impressão consolidam receitas rotativas e mensalistas sem gerar cobrança no checkout mensalista.
- [x] Empresas rotativas utilizam cobrança pós-paga.
- [x] O check-in rotativo não gera transação financeira.
- [x] O checkout calcula automaticamente o valor devido.
- [x] O cálculo utiliza os parâmetros definidos no tarifário da empresa.
- [x] Cada empresa possui uma página própria em `/admin/companies/company_id={id}` para editar razão social/nome fantasia, selecionar o modelo mensalista/rotativo e configurar os tarifários por tipo de veículo.
- [x] A transação financeira é criada somente após confirmação do método de pagamento pelo operador.
  - não existe confirmação por gateway externo; os métodos continuam sendo registros operacionais.
- [~] Todos os campos livres são sanitizados antes da persistência.
  - concluído para check-in, checkout, justificativas financeiras e payloads de auditoria alterados nesta fase;
  - pendente uma revisão global dos fluxos legados não relacionados ao Pricing Engine.
- [x] Os dashboards e a auditoria exibem registros tratados como texto e protegidos contra XSS.
- [x] O checkout e o registro financeiro ocorrem na mesma transação SQL.
- [x] Falhas durante o cálculo impedem a liberação da vaga.
- [x] Cada checkout gera automaticamente um registro de auditoria contendo os parâmetros utilizados no cálculo.

---

## 9. Evidências técnicas

Arquivos implementados ou alterados:

- `database/migrations/20260721_create_dynamic_pricing_engine.sql`;
- `app/Infrastructure/Database/MigrationRunner.php` e `cli/migrate.php`;
- `app/Finance/Repositories/PricingRepository.php`;
- `app/Finance/Services/PriceCalculator.php`;
- `app/Security/InputSanitizer.php`;
- `app/Controllers/VacancyController.php`;
- `app/Models/VacancyModel.php`;
- `app/Views/Vacancy/apply.php`;
- `app/Views/Vacancy/manage.php`;
- `app/Views/Admin/company-pricing.php`;
- `public/js/FinishVacancy.js`;
- `tests/PriceCalculatorTest.php`.

Validações executadas:

- lint PHP dos arquivos da aplicação e testes;
- validação sintática de `FinishVacancy.js`;
- `PriceCalculatorTest.php`;
- `TenantContextTest.php`;
- `RepositoryTenantIsolationTest.php`;
- `GovernanceUserManagementTest.php`;
- `SecurityTestSuite.php`.
