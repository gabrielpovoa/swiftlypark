# REFACTOR-019 — Corrigir precedência do contexto financeiro

## Identificação

- Branch: `feature/fix-finance-scope-precedence`
- Base: `develop` após a conclusão da feature 018
- Estado: concluída
- Feature anterior: `REFACTOR-018-GLOBAL-SUPER-ADMIN-FINANCE.md`

## Problema

Ao selecionar uma empresa no tenant switcher e acessar `/finance`, a ausência do
parâmetro `company_id` era interpretada como escopo global. O sidebar voltava a
mostrar “Dashboard global” e o formulário de estorno permanecia oculto.

## Regra corrigida

1. `company_id=all` explícito sempre representa consolidado global.
2. `company_id=N` explícito representa a empresa validada.
3. Sem filtro, um `support_impersonation.company_id` ativo tem precedência.
4. Sem filtro e sem empresa de suporte, o Super-Admin recebe o consolidado.
5. Usuários comuns continuam obrigatoriamente no tenant ativo.

## Implementado

- Finance usa a empresa escolhida no switcher quando não há filtro explícito.
- Header mantém a empresa selecionada nessa situação.
- Selecionar “Dashboard global” agora chama endpoint autenticado que limpa
  `company_id` e `support_impersonation` da sessão e restaura RBAC global.
- O formulário de estorno aparece somente quando filtro e tenant operacional
  representam a mesma empresa.
- Teste de regressão cobre os marcadores de precedência e a troca global segura.

## Validação manual

1. Como Super-Admin, selecionar Jardim Europa no sidebar.
2. Acessar `/finance` sem query string.
3. Confirmar Jardim Europa no sidebar e no filtro do BI.
4. Confirmar que “Registrar estorno” está visível.
5. Selecionar “Todas as empresas” no BI e confirmar que o estorno desaparece.
6. Selecionar “Dashboard global” no sidebar e confirmar limpeza do tenant.

## Próximo passo exato

1. Validar o estorno com uma transação real da empresa selecionada.
2. Revisar auditoria e ledger após o ajuste.
3. Commitar e finalizar pelo Git Flow após aprovação.
