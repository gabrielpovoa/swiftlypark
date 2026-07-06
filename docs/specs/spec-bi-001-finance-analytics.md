# Spec-BI-001: Módulo de Business Intelligence e Controle Financeiro

## 1. Overview

Esta especificação define a implementação de um módulo de **Business Intelligence (BI)** voltado ao acompanhamento financeiro e operacional da aplicação.

O objetivo é disponibilizar um dashboard analítico para usuários com permissões financeiras, centralizando indicadores de desempenho (KPIs), conciliação financeira, análises temporais e ferramentas de gestão.

---

## 2. Objetivos

- Disponibilizar indicadores financeiros em tempo real.
- Centralizar análises operacionais e financeiras.
- Permitir exportação de relatórios.
- Implementar controle de ajustes financeiros integrado à auditoria.
- Restringir o acesso ao módulo através de permissões específicas.

---

## 3. Escopo da Funcionalidade

### 3.1 Painel Executivo (KPIs)

O dashboard deverá apresentar indicadores estratégicos em tempo real.

KPIs mínimos:

- Faturamento Bruto;
- Ticket Médio;
- Taxa de Ocupação;
- Volume de Ajustes Financeiros.

---

### 3.2 Inteligência de Dados

#### Conciliação Financeira

O sistema deverá apresentar comparativos entre:

- valores registrados;
- formas de pagamento;
- movimentações financeiras.

---

#### Análise Temporal

O módulo deverá disponibilizar gráficos interativos para análise histórica.

Visualizações sugeridas:

- gráfico de linhas;
- gráfico de barras.

Filtros disponíveis:

- Mensal;
- Anual;
- Comparativo YoY (Year-over-Year).

---

#### Distribuição de Receita

O dashboard deverá apresentar a distribuição da receita por forma de pagamento.

Visualizações sugeridas:

- gráfico de pizza;
- gráfico do tipo donut.

---

### 3.3 Ferramentas de Gestão

#### Exportação

O sistema deverá permitir exportação dos dados filtrados.

Formatos suportados:

- CSV;
- PDF.

---

#### Ajustes Financeiros

O módulo deverá disponibilizar uma interface para registro de:

- estornos;
- ajustes financeiros.

Toda operação deverá exigir uma justificativa obrigatória.

---

## 4. Arquitetura de Dados

### Authorization Policy

O acesso ao módulo deverá utilizar permissões específicas.

| Permissão | Descrição |
|-----------|-----------|
| `finance.view` | Permite visualizar o dashboard financeiro |
| `finance.adjust` | Permite registrar ajustes financeiros |

---

### Aggregation Service

Todos os cálculos dos KPIs deverão ser executados por uma camada dedicada de agregação.

O Controller não deverá executar cálculos financeiros complexos.

Fluxo esperado:

```text
Controller
      ↓
AggregationService
      ↓
Repositories
      ↓
Banco de Dados
```

---

### Auditoria

Toda operação financeira que altere dados deverá gerar um evento específico.

Exemplo:

```text
FinanceAdjustmentEvent
```

O evento deverá ser processado pelo `AuditService`, registrando obrigatoriamente:

- usuário responsável;
- valor anterior;
- novo valor;
- motivo da alteração;
- data da operação.

---

## 5. Fluxo de Visibilidade

### Navegação

O menu do módulo de BI deverá ser exibido apenas para usuários com a permissão:

```text
finance.view
```

Exemplo de diretiva:

```php
@can('finance.view')
```

---

### Proteção de Rotas

Caso um usuário sem permissão tente acessar diretamente a URL do módulo, o sistema deverá retornar:

```http
403 Forbidden
```

Perfis como `OPERATOR` não deverão possuir acesso ao dashboard financeiro.

---

## 6. Regras de Negócio

### Imutabilidade Financeira

Dados financeiros consolidados referentes a períodos encerrados não poderão ser alterados diretamente.

Correções deverão ocorrer exclusivamente através da criação de lançamentos de ajuste.

O histórico original deverá permanecer preservado.

---

### Comparativo Year-over-Year (YoY)

O sistema deverá buscar automaticamente os dados do mesmo período do ano anterior para alimentar os componentes comparativos.

Caso não existam dados suficientes (por exemplo, sistemas com menos de um ano de operação), o componente deverá tratar essa condição sem gerar erros, apresentando uma mensagem apropriada ou indicadores vazios.

---

## 7. Critérios de Aceitação

- [ ] O dashboard apresenta KPIs financeiros em tempo real.
- [ ] Existe comparação entre registros financeiros e formas de pagamento.
- [ ] O sistema disponibiliza gráficos temporais mensais, anuais e comparativos YoY.
- [ ] A distribuição da receita pode ser visualizada por meio de gráficos de pizza ou donut.
- [ ] Os dados podem ser exportados em CSV e PDF.
- [ ] Ajustes financeiros exigem justificativa obrigatória.
- [ ] O módulo é protegido pela permissão `finance.view`.
- [ ] A funcionalidade de ajustes é protegida pela permissão `finance.adjust`.
- [ ] Os cálculos são executados pela camada `AggregationService`.
- [ ] Toda alteração financeira gera um `FinanceAdjustmentEvent`.
- [ ] O `AuditService` registra automaticamente todos os ajustes financeiros.
- [ ] Usuários sem permissão recebem HTTP 403 ao tentar acessar o módulo.
- [ ] Dados financeiros consolidados não podem ser alterados diretamente.
- [ ] O comparativo Year-over-Year trata corretamente cenários com ausência de dados históricos.