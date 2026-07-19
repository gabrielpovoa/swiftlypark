# REFACTOR-017 — Organizar documentação e raiz do projeto

## Identificação

- Branch: `feature/organize-project-documentation`
- Base: `develop` após a conclusão da feature 016
- Estado: concluída
- Feature anterior: `REFACTOR-016-FIX-COMPANY-LOGO-UPLOAD-PATH.md`

## Objetivo

Reduzir ruído na raiz, estabelecer destinos previsíveis para documentação e
remover configurações locais/sensíveis do índice Git sem apagar arquivos locais.

## Implementado

- Handoffs `REFACTOR-*` movidos para `docs/refactors/` e indexados em um README.
- Roadmap, roteiro de testes e overview movidos para `docs/architecture/`.
- Design system consolidado em `docs/design-system/`.
- Handoffs SaaS, operação e roadmaps separados por finalidade.
- SQL histórico movido de `heidSQL/` para `database/legacy/heidisql/`.
- `.idea/` removido do índice e ignorado como configuração local de IDE.
- `.env copy` removido do índice e coberto por `.env*`, preservando `.env.example`.
- README principal e referências internas atualizados para os novos caminhos.
- `README.md` criado em `docs/` e `docs/refactors/` para descoberta rápida.

## Invariantes

- `README.md`, arquivos de build, manifests e configurações de execução ficam na raiz.
- Novos handoffs são criados em `docs/refactors/`, nunca em `./`.
- Arquivos `.env*` reais não são versionados; somente `.env.example` pode ser rastreado.
- Mover documentação não altera comportamento da aplicação.

## Validação

1. Executar `git diff --check`.
2. Confirmar que a raiz contém apenas o Markdown principal.
3. Procurar referências aos caminhos antigos com `rg`.
4. Executar a suíte unitária para garantir ausência de dependências acidentais.

## Próximo passo exato

1. Revisar a árvore final apresentada no handoff.
2. Commitar a feature 017.
3. Finalizar pelo Git Flow após aprovação.
