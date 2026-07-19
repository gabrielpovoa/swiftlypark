# REFACTOR-034 — Persistência do favicon da empresa

## Controle

- Branch: `feature/fix-tenant-favicon-persistence`
- Base: `develop`
- Data: 19/07/2026
- Estado: concluído e integrado à `develop`

## Problema

Empresas com logo podiam exibi-la brevemente no favicon e, em seguida, voltar
ao favicon padrão da SwiftlyPark. O fluxo aplicava o fallback antes de cada
conversão assíncrona e não distinguia respostas de atualizações concorrentes.

## Implementação

- A logo da empresa é aplicada diretamente enquanto o canvas gera o favicon.
- O fallback é aplicado imediatamente somente quando não existe logo.
- Cada atualização recebe uma sequência; resultados assíncronos antigos não
  podem mais sobrescrever o favicon solicitado mais recentemente.
- Uma falha de carregamento somente aplica o fallback se ainda pertencer à
  atualização atual.

## Segurança e isolamento

O favicon continua aceitando apenas logos resolvidas na mesma origem da
aplicação. URLs externas não são desenhadas no canvas nem aplicadas ao `head`.

## Testes

```bash
php tests/CompanyFaviconTest.php
```

Validação manual:

1. Entrar em uma empresa que possua logo.
2. Navegar entre Dashboard, Usuários e Financeiro.
3. Confirmar que o favicon permanece com a identidade da empresa.
4. Ativar o contexto global ou uma empresa sem logo e confirmar o favicon
   padrão da SwiftlyPark.
5. Alternar rapidamente entre empresas e confirmar que uma resposta anterior
   não substitui o favicon da empresa atual.

## Retomada

Após validar, finalizar a feature com Git Flow, criar a tag
`refactor-034-tenant-favicon-persistence` e permanecer em `develop`.
