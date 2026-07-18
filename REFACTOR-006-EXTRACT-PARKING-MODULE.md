# REFACTOR-006 — Extração do módulo Parking

## Identificação

- Branch: `feature/extract-parking-module`
- Base: `develop` após a conclusão da feature 005
- Estado: em andamento
- Feature anterior: `REFACTOR-005-EXTRACT-IDENTITY-MODULE.md`

## Objetivo

Concentrar vagas, ocupações, check-in e checkout em um módulo vertical Parking,
mantendo Billing responsável pelo cálculo e Finance pelo reconhecimento da
receita.

## Inventário inicial

### Presentation

- `VacancyController`: consulta, aplicação/ocupação e checkout.
- `CreateVacancy`: cadastro de vagas.
- Views em `app/Views/Vacancy`.

### Persistência e regras misturadas

- `VacancyModel`: fluxo de ocupação e fechamento.
- `CreateVacancyModel`: criação de vaga.
- `VagasRepository`: acesso tenant-scoped às vagas.
- `TransacaoRepository`: persistência financeira chamada pelo checkout.

### Consumidores externos

- `HomeDashModel` e `GlobalDashboardRepository` leem ocupação para KPIs.
- `FinancialReportRepository` lê estadias e transações para relatórios.
- `PriceCalculator` e repositories de Pricing/Monthly Contracts são
  dependências de Billing, não devem ser absorvidos por Parking.

## Estrutura alvo

```text
app/Parking/
├── Application/
├── Domain/
├── Infrastructure/
└── Presentation/
```

## Invariantes

- Toda vaga e estadia permanece isolada por `company_id`.
- Check-in classifica a estadia como `MONTHLY` ou `ROTATING` uma única vez.
- Checkout e gravação da transação continuam atômicos.
- Mensalista não gera cobrança duplicada no checkout.
- Parking solicita cálculo a Billing e registro financeiro a Finance por
  fronteiras explícitas.
- Rotas e views permanecem compatíveis nesta feature.

## Próximo passo exato

1. Mapear métodos públicos e dependências de `VacancyModel` e
   `CreateVacancyModel`.
2. Separar entidade/estado de vaga e estadia em Domain.
3. Mover casos de uso para Application e SQL para Infrastructure.
4. Mover controllers para Presentation e atualizar rotas.
5. Criar testes de fronteira e regressão do fluxo.
