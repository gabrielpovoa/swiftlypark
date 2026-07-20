# REFACTOR-036 — Fundação de CI/CD com Git Flow

## Controle

- Branch: `feature/ci-delivery-foundation`
- Base: `develop`
- Data: 20/07/2026
- Estado: concluído e integrado à `develop`

## Objetivo

Automatizar a validação e o empacotamento do SwiftlyPark sem alterar `main`, sem
publicar imagens e sem executar deploy em produção.

## Implementação

- Criado CI para branches Git Flow e Pull Requests destinadas a `develop` ou
  `main`.
- PRs para `main` são aceitas somente a partir de `release/*` e `hotfix/*`.
- Separados jobs de qualidade, testes principais, segurança, integração MySQL e
  build Docker.
- Criado workflow de entrega que roda somente após CI aprovado em `develop` ou
  `main` e armazena uma imagem por sete dias.
- Nenhum workflow possui permissão de escrita, credencial de registry ou etapa
  de deploy.
- Adicionados runners locais em `tests/ci` para reproduzir as verificações.
- O bootstrap de integração usa MySQL descartável e compatibilidade documentada
  para a migration histórica `20260719`.
- A imagem Docker agora contém as dependências de produção do Composer.
- `.dockerignore` impede a inclusão de ambiente, Git, IDE, testes, documentação
  e uploads na imagem.
- `composer.json` declara licença proprietária e passa na validação estrita.

## Segurança

- Workflows usam somente `permissions: contents: read`.
- Nenhum segredo real está definido nos arquivos de CI.
- Credenciais MySQL são efêmeras e exclusivas do service container.
- O job falha caso encontre arquivos típicos de segredo versionados.
- A imagem não recebe `.env` nem uploads do workspace.
- A entrega depende do sucesso do CI e reconstrói o SHA validado.

## Limite deliberado

Proteções de branch precisam ser ativadas nas configurações do GitHub. Os
arquivos do projeto não conseguem impedir push direto por alguém que mantenha
permissão de bypass no repositório.

O guia completo está em `docs/operations/ci-cd-gitflow.md`.

## Evidências locais

- Composer validate: aprovado;
- Composer audit: nenhuma vulnerabilidade conhecida;
- lint PHP e JavaScript: aprovado;
- 36 testes principais: aprovados;
- suíte de segurança: aprovada;
- migrations e três integrações contra MySQL 8 descartável: aprovadas;
- build Docker autocontido: aprovado;
- sintaxe YAML e shell: aprovada.

## Retomada

1. Finalizar a feature no Git Flow e criar a tag
   `refactor-036-ci-cd-gitflow-foundation`.
2. Publicar `develop` e a tag somente com autorização explícita.
3. Abrir uma Pull Request da feature se o fluxo remoto for adotado antes da
   finalização local.
4. Configurar as regras de proteção descritas no guia operacional.
5. Observar a primeira execução no GitHub Actions e corrigir particularidades
   do runner, se surgirem.
