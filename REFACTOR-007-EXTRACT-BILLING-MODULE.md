# REFACTOR-007 — Extração do módulo Billing

## Identificação

- Branch: `feature/extract-billing-module`
- Base: `develop` após a conclusão da feature 006
- Estado: concluída
- Feature anterior: `REFACTOR-006-EXTRACT-PARKING-MODULE.md`

## Objetivo

Tornar Billing responsável por tarifários, cálculo dinâmico e contratos
mensalistas, deixando Finance responsável apenas por receita, ajustes e
relatórios.

## Inventário inicial

- `Finance\Services\PriceCalculator` implementa a política de preço rotativo.
- `Finance\Repositories\PricingRepository` consulta empresa e tarifários.
- `Finance\Repositories\MonthlyContractRepository` consulta contratos.
- `AdminCompanyBillingController` administra cadastro/tarifários.
- `AdminMonthlyContractController` cria, renova e cancela contratos/pagamentos.
- Parking consome cálculo e classificação mensalista no check-in/checkout.
- Dashboards consomem dados agregados, mas não são donos dessas regras.

## Estrutura alvo

```text
app/Billing/
├── Application/
├── Domain/
├── Infrastructure/
└── Presentation/
```

## Invariantes

- Cálculo rotativo preserva base, tolerância, adicional e frequência.
- Contrato mensal vigente evita cobrança no checkout.
- Renovação e pagamento permanecem na mesma transação.
- Toda consulta mantém `company_id` explícito.
- Rotas e payloads administrativos permanecem iguais.

## Implementado

- `PriceCalculator` movido para `Billing\Application`.
- Repositories de tarifários e contratos movidos para
  `Billing\Infrastructure`.
- Controllers administrativos movidos para `Billing\Presentation` com URLs
  preservadas.
- Criado `Billing\Domain\Tariff`; a política de tolerância, períodos adicionais
  e aritmética em centavos saiu do serviço de aplicação.
- Parking agora consome Billing explicitamente no check-in e checkout.
- Criados testes de domínio e fronteira, incluindo a regressão conhecida de
  113 minutos do caminhão: `25,00 + 2 × 7,28 = 39,56`.
- Smoke tests sem sessão das páginas administrativas retornaram `302`,
  confirmando a preservação da proteção das rotas.

## Próximo passo exato

1. Executar a suíte completa e smoke tests administrativos.
2. Atualizar o roadmap e finalizar via Git Flow.
3. Iniciar a feature 008 para completar o módulo Finance.
