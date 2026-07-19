# REFACTOR-022 — Grid de usuários ativos na Gestão de Identidade

## Identificação

- Branch: `feature/improve-identity-user-grid`
- Base: `develop` após a conclusão da feature 021
- Estado: concluída
- Feature anterior: `REFACTOR-021-CENTRALIZE-USER-PROVISIONING.md`

## Problema

A Gestão de Identidade iniciava exibindo usuários ativos e revogados juntos e
organizava os cards em uma lista vertical. Com muitos registros, a visão ficava
longa e os acessos válidos não eram priorizados.

## Implementado

- O status padrão de `/identity` passou de `all` para `active`.
- Acessar `/identity` ou limpar os filtros exibe inicialmente apenas usuários
  ativos.
- Os filtros explícitos `status=all` e `status=revoked` continuam disponíveis.
- A paginação preserva corretamente o escopo “Todos”; o status padrão ativo pode
  ser omitido da URL.
- A listagem recebeu um título contextual: “Usuários ativos”, “Usuários
  revogados” ou “Todos os usuários”.
- Foi incluída a quantidade de resultados da página atual.
- Os cards foram organizados em grid responsivo: uma coluna em telas menores e
  duas colunas no desktop.
- Cards não são esticados artificialmente pela altura do card vizinho.
- E-mails longos quebram linha sem causar overflow.
- Ações ocupam a largura do card quando necessário.
- A grade interna de permissões foi reduzida para até duas colunas, adequada à
  nova largura dos cards.

## Segurança e domínio

- Nenhuma regra de autorização, revogação ou reativação foi modificada.
- O filtro continua parametrizado pelo repository.
- Usuários revogados continuam acessíveis por seleção explícita.
- Não há alteração de banco ou migração.

## Validação automatizada

```bash
php tests/IdentityUserGridTest.php
php tests/IdentityProvisioningViewTest.php
php tests/SecurityTestSuite.php
php -l app/Identity/Presentation/IdentityManagementController.php
php -l app/Views/Identity/index.php
```

## Validação manual

1. Acessar `/identity` sem query string.
2. Confirmar “Ativos” selecionado e ausência de usuários revogados.
3. Selecionar “Todos” e confirmar ativos e revogados.
4. Navegar entre páginas e confirmar que o filtro escolhido permanece.
5. Selecionar “Revogados” e testar a ação de reativação.
6. Conferir uma coluna no mobile e duas colunas em desktop.
7. Expandir permissões em cards vizinhos e conferir que não há deformação do
   grid.

## Próximo passo exato

Avaliar a exibição do total geral filtrado, além da quantidade limitada à página
atual, nos indicadores do cabeçalho.
