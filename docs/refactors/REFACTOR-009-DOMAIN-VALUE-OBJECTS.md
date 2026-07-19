# REFACTOR-009 — Value Objects de domínio

## Identificação

- Branch: `feature/add-domain-value-objects`
- Base: `develop` após a conclusão da feature 008
- Estado: concluída
- Feature anterior: `REFACTOR-008-EXTRACT-FINANCE-MODULE.md`

## Objetivo

Eliminar primitivos frágeis nos fluxos críticos por meio de objetos imutáveis
para dinheiro, placa, duração e período.

## Escopo

- `Money`: aritmética inteira em centavos, sem erro binário.
- `VehiclePlate`: normalização e validação centralizadas.
- `DurationMinutes`: duração não negativa.
- `DatePeriod`: início inclusivo e fim exclusivo, sempre ordenados.
- Integração inicial em Billing, Parking e Finance.

## Invariantes

- Dinheiro nunca usa float internamente.
- Placas são persistidas em maiúsculas e sem caracteres inesperados.
- Durações negativas são rejeitadas.
- Períodos vazios ou invertidos são rejeitados.
- Value objects não dependem de framework, PDO ou infraestrutura.

## Implementado

- Criados `Money`, `VehiclePlate`, `DurationMinutes` e `DatePeriod` em
  `Shared\Domain\ValueObject`.
- `Tariff` passou a calcular exclusivamente com `Money` e
  `DurationMinutes`; conversão para float ocorre apenas no DTO de saída.
- O gateway de Parking normaliza e valida placa antes de consultar ou persistir
  uma estadia.
- `FinancialReportService` representa o mês consultado como `DatePeriod`, com
  fim exclusivo.
- Testes cobrem os `3.956` centavos do cenário conhecido, equivalência de placas
  com/sem hífen, duração negativa e limites do período.

## Próximo passo exato

1. Executar a suíte completa e lint dos consumidores.
2. Atualizar o roadmap e finalizar via Git Flow.
3. Iniciar a feature 010 para contratos de repositories.
