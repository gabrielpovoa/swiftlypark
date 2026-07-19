# REFACTOR-024 — Favicon dinâmico por empresa

## Identificação

- Branch: `feature/tenant-dynamic-favicon`
- Base: `develop` após a conclusão da feature 023
- Estado: concluída
- Feature anterior: `REFACTOR-023-INCREASE-COMPANY-LOGO-SIZE.md`

## Objetivo

Usar a identidade visual da empresa ativa também na aba do navegador. Empresas
com logo recebem um favicon derivado dela; empresas sem logo e páginas em escopo
global usam o favicon oficial da SwiftlyPark.

## Implementado

- Adicionado o favicon-base `public/images/favicons-swiftlypark.png` ao projeto.
- O head global declara imediatamente o favicon SwiftlyPark e o
  `apple-touch-icon`, evitando uma aba sem ícone durante o carregamento.
- Criado `public/js/CompanyFavicon.js` com API pública `window.CompanyFavicon`.
- `CompanyFavicon.create(logoPath)` transforma a logo em favicon PNG por canvas.
- O canvas usa 128 × 128 px, fundo `#11151e`, cantos arredondados, margem interna
  e redimensionamento proporcional com comportamento equivalente a
  `object-contain`.
- `CompanyFavicon.refresh(logoPath)` permite atualizar o favicon manualmente.
- A inicialização automática encontra a logo pela empresa ativa no
  `SwiftlyParkTenant`.
- O contexto do header agora expõe também `currentCompany` com sua logo.
- Financeiro e impressão de movimentações inicializam o favicon mesmo fora do
  layout principal.
- Favicons processados são armazenados em `sessionStorage` para evitar repetir o
  canvas durante a mesma sessão.

## Fallback

O favicon SwiftlyPark é mantido quando:

- não existe empresa ativa;
- a empresa não possui logo;
- a imagem não pode ser carregada;
- o navegador não fornece contexto 2D de canvas;
- ocorre qualquer falha durante a conversão.

## Segurança

- Somente logos resolvidas no mesmo `origin` da aplicação são processadas.
- Caminhos são normalizados para `/uploads/`.
- Nenhum HTML é criado a partir de valores da empresa.
- O processamento ocorre localmente no navegador e não altera o arquivo de logo.
- O cache é limitado à sessão do navegador.

## Validação automatizada

```bash
php tests/CompanyFaviconTest.php
node --check public/js/CompanyFavicon.js
php tests/SecurityTestSuite.php
php -l app/Views/partials/head.php
php -l app/Views/partials/header.php
php -l app/Views/partials/footer.php
php -l app/Views/Finance/print.php
php -l app/Views/Logs/print.php
```

## Validação manual

1. Entrar em uma empresa com logo e confirmar o ícone personalizado na aba.
2. Trocar para outra empresa com logo e confirmar a mudança após a navegação.
3. Selecionar uma empresa sem logo e confirmar o favicon SwiftlyPark.
4. Abrir o dashboard global e confirmar o fallback SwiftlyPark.
5. Abrir as impressões financeira e de movimentações.
6. Testar uma URL de logo inválida e confirmar que a página mantém o fallback.
7. Conferir no DevTools que uma segunda página da mesma empresa reutiliza o
   favicon em `sessionStorage`.

## Próximo passo exato

Validar a legibilidade de logos muito horizontais em 16 × 16 px. Caso necessário,
permitir que a empresa envie no futuro um ícone quadrado separado da logo
institucional.
