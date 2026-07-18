# REFACTOR-006 — Extração do módulo Parking

## Identificação

- Branch: `feature/extract-parking-module`
- Base: `develop` após a conclusão da feature 005
- Estado: concluída
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

## Implementado

- Criados enums de domínio `VehicleType`, `BillingMode` e `VacancyStatus` com
  os valores persistidos compatíveis com o banco atual.
- Casos de uso públicos estão em `Parking\Application`.
- Toda implementação com SQL foi movida para `Parking\Infrastructure`.
- Controllers foram movidos para `Parking\Presentation` e as rotas atualizadas
  sem alterar URLs.
- Repositories de vagas e transações saíram do diretório global.
- Modelos e controllers legados foram removidos.
- Criados testes do domínio e da fronteira modular; o teste impede SQL em
  Application/Presentation e dependência de infraestrutura no Domain.
- Smoke tests HTTP de `/vacancy`, `/vacancy/apply`, `/vacancy/manage` e
  `/CreateVacancy` retornaram `302` para uma sessão não autenticada, preservando
  a proteção das rotas.

## Próximo passo exato

1. Executar a suíte completa e smoke tests das rotas.
2. Atualizar o roadmap e finalizar via Git Flow.
3. Iniciar a feature 007 para extrair Billing das classes Finance atuais.
