# REFACTOR-023 — Aumentar logos das empresas

## Identificação

- Branch: `feature/increase-company-logo-size`
- Base: `develop` após a conclusão da feature 022
- Estado: concluída
- Feature anterior: `REFACTOR-022-IDENTITY-ACTIVE-USER-GRID.md`

## Problema

As logos cadastradas pelas empresas eram exibidas em dimensões pequenas em
relação aos demais elementos das views, especialmente no menu lateral, diretório
de empresas e cabeçalhos financeiros.

## Implementado

- Menu lateral: logo ampliada de 32 px para 48 px durante a expansão do menu.
- Diretório de empresas: logo ampliada para 64 px no mobile e 80 px a partir de
  telas médias.
- Tela individual da empresa: logo ampliada para 64/80 px e cabeçalho ajustado
  para acomodar a nova dimensão.
- BI Financeiro: logo ampliada para 80 px no mobile e 96 px no desktop.
- Impressão financeira e impressão de movimentações: logo ampliada para 80 px.
- Todas as imagens preservam `object-contain`, proporção original, fundo
  transparente e ausência de border-radius aplicado à imagem.
- A logo da tela individual recebeu texto alternativo descritivo.
- Fotos de usuários, imagens de veículos e ícones do sistema não foram alterados.

## Validação automatizada

```bash
php tests/CompanyLogoPresentationTest.php
php tests/CompanyManagementViewTest.php
php -l app/Views/partials/header.php
php -l app/Views/Admin/companies.php
php -l app/Views/Admin/company-pricing.php
php -l app/Views/Finance/index.php
php -l app/Views/Finance/print.php
php -l app/Views/Logs/print.php
```

## Validação manual

1. Selecionar uma empresa com logo e expandir o menu lateral.
2. Conferir a logo no diretório `/admin/companies`.
3. Conferir a tela individual `/admin/companies/company_id=N`.
4. Acessar `/finance` no contexto da empresa.
5. Abrir as impressões financeira e de movimentações.
6. Validar logos horizontais e verticais em viewport mobile e desktop.

## Próximo passo exato

Validar visualmente com logos de proporções distintas e avaliar, em feature
posterior, um tamanho máximo baseado em largura e altura para documentos
impressos.
