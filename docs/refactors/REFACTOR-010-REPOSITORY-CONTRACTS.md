# REFACTOR-010 — Contratos de repositories

## Identificação

- Branch: `feature/add-repository-contracts`
- Base: `develop` após a conclusão da feature 009
- Estado: concluída
- Feature anterior: `REFACTOR-009-DOMAIN-VALUE-OBJECTS.md`

## Objetivo

Fazer Application depender de abstrações do domínio, não de implementações PDO,
para permitir testes isolados e futura substituição do MySQL com impacto
controlado.

## Escopo

- Billing: consulta de tarifário.
- Parking: gateway de vagas/estadias e criação de vagas.
- Finance: consultas de relatório e persistência de ajustes.
- Companies já possui `CompanyRepository` desde a feature 004.

## Invariantes

- Interfaces não importam PDO.
- Implementações concretas permanecem em Infrastructure.
- Composition/wiring ocorre em Presentation ou bootstrap.
- Isolamento por tenant continua responsabilidade obrigatória da implementação.
- Nenhuma rota ou resposta pública muda.

## Implementado

- `Billing\Domain\PricingRepository` criado; implementação renomeada para
  `PdoPricingRepository`; `PriceCalculator` depende apenas do contrato.
- `ParkingGateway` e `VacancyCreator` criados no domínio; Application não cria
  mais gateways PDO e os controllers fazem a composição.
- Contratos de relatório e ajustes criados em `Finance\Domain`; services foram
  desacoplados das implementações.
- Implementações concretas declaram explicitamente os contratos implementados.
- Testes com fakes provam execução de Pricing e Parking sem banco de dados.
- Teste de fronteira impede imports Infrastructure em Application e PDO nos
  contratos.

## Próximo passo exato

1. Executar a suíte completa.
2. Atualizar o roadmap e finalizar via Git Flow.
3. Iniciar a feature 011 para o ledger financeiro.
