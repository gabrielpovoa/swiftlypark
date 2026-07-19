# REFACTOR-018 — Visão financeira global do Super-Admin

## Identificação

- Branch: `feature/add-global-finance-view`
- Base: `develop` após a conclusão da feature 017
- Estado: concluída
- Feature anterior: `REFACTOR-017-ORGANIZE-PROJECT-DOCUMENTATION.md`

## Objetivo

Entregar ao Super-Admin uma visão financeira consolidada de todas as empresas,
com filtro opcional por tenant, sem enfraquecer o isolamento financeiro dos
demais perfis.

## Invariantes de segurança

- Somente o papel global `super-admin` pode criar um escopo financeiro global.
- Usuários comuns continuam presos ao `TenantContext` ativo.
- Empresa informada no filtro precisa existir e estar ativa.
- Repository recebe um `FinancialScope` explícito; ausência de escopo não é aceita.
- Estornos não ficam disponíveis no consolidado global nem em empresa diferente
  do tenant operacional ativo.

## Implementado

- Value Object `FinancialScope` representa escopo global ou empresa específica.
- Dashboard do Super-Admin abre em “Todas as empresas” por padrão.
- Filtro de empresa incluído no BI sem alterar o tenant operacional da sessão.
- KPIs, receita diária, formas de pagamento, ocupação, ranking e transações
  recentes respeitam o escopo escolhido.
- CSV e impressão recebem o mesmo filtro do dashboard.
- Transações globais identificam a empresa de origem.
- Header representa “Dashboard global” durante a consulta consolidada.
- Testes de domínio e integração atualizados para exigir escopo explícito.

## Evidência local

```text
global gross_revenue=2417.59
company#1 gross_revenue=626.25
Financial scope test passed
```

## Validação manual

1. Entrar como Super-Admin e acessar `/finance`.
2. Confirmar “Todas as empresas” e o contexto global no sidebar.
3. Selecionar cada empresa e comparar KPIs, gráficos e transações.
4. Exportar CSV e imprimir PDF em modo global e filtrado.
5. Entrar como usuário não global e confirmar ausência do filtro global.
6. Confirmar que um usuário comum não consulta outra empresa via query string.

## Próximo passo exato

1. Validar visualmente o Finance autenticado.
2. Revisar dados consolidados contra consultas SQL.
3. Commitar e finalizar a feature pelo Git Flow após aprovação.
