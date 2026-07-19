# REFACTOR-025 — Remover borda branca do favicon

## Identificação

- Branch: `feature/remove-favicon-white-border`
- Base: `develop` após a conclusão da feature 024
- Estado: concluída
- Feature anterior: `REFACTOR-024-TENANT-DYNAMIC-FAVICON.md`

## Problema

O favicon padrão possuía uma margem branca ampla ao redor do bloco escuro. Em
tamanhos pequenos, essa margem reduzia a área útil e deixava a marca menor na aba
do navegador.

## Implementado

- Removida a moldura branca externa de `public/images/favicons-swiftlypark.png`.
- O fundo escuro e os cantos arredondados agora ocupam todo o quadro do asset.
- Símbolo central, cores, gradientes, iluminação e proporções foram preservados.
- O arquivo permanece quadrado, em PNG RGB, com 1254 × 1254 px.
- Nenhuma referência de código precisou ser alterada; o favicon global e o
  fallback das empresas passam a usar automaticamente o asset atualizado.

## Processo

A alteração foi realizada com a skill `imagegen`, em modo de edição do asset
existente, limitando a mudança à área branca externa.

## Validação

```bash
file public/images/favicons-swiftlypark.png
php tests/CompanyFaviconTest.php
node --check public/js/CompanyFavicon.js
```

## Validação manual

1. Abrir a aplicação em contexto global ou em empresa sem logo.
2. Forçar recarregamento sem cache no navegador.
3. Confirmar que o bloco escuro ocupa melhor o espaço disponível na aba.
4. Confirmar que nenhuma borda branca aparece ao redor do favicon.
5. Conferir que empresas com logo continuam recebendo o favicon dinâmico.

## Próximo passo exato

Caso o navegador mantenha o asset anterior, limpar o cache de favicons ou testar
em uma janela anônima. Favicons costumam ter cache independente dos demais
recursos da página.
