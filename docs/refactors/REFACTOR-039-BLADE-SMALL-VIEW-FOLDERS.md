# REFACTOR-039 — Blade nas pastas menores de views

## Controle

- Branch: `feature/blade-small-view-folders`
- Base: `develop`
- Data: 28/07/2026
- Estado: concluído e integrado à `develop`

## Objetivo

Continuar a adoção incremental do Blade nas pastas que possuíam no máximo dois
arquivos de view, sem remover a compatibilidade com partials PHP ou alterar
rotas e contratos dos controllers.

## Escopo migrado

Foram migrados 15 arquivos distribuídos por 12 pastas:

- `403/403`;
- `404/404`;
- `About/about`;
- `Audit/index`;
- `Contact/contact` e `Contact/ticket`;
- `Finance/index` e `Finance/print`;
- `Home/home`;
- `HomeDash/homedash`;
- `Identity/index`;
- `Login/login` e `Login/RecoveryPassword`;
- `Logs/print`;
- `Profile/profile`.

Todas essas views agora utilizam a extensão `.blade.php`. As pastas `Admin`,
`Vacancy` e `partials` ficaram fora deste recorte por possuírem mais de dois
arquivos; as telas pesadas de Company, Pricing e Vacancy já haviam sido tratadas
no REFACTOR-038.

## Implementação

- O layout principal `Home/home` passou a ser renderizado pelo Blade.
- O conteúdo previamente renderizado é inserido no layout como HTML interno
  confiável; dados externos continuam sendo escapados dentro de cada view.
- Foi criada uma ponte controlada para reutilizar os partials PHP existentes
  (`head`, `header`, `footer` e `role-badge`) durante a migração.
- `Controller::renderTemplate()` centraliza a resolução Blade/PHP e pode ser
  reutilizado por controllers que precisam obter o HTML como string.
- `includeView()` passou a respeitar a mesma preferência por Blade.
- O template de e-mail de contato deixou de usar `include` por caminho físico e
  agora é renderizado por `renderTemplate('Contact/ticket', ...)`.
- As referências de testes aos nomes antigos `.php` foram atualizadas.

## Segurança

- Mensagens convertidas para `{{ }}` mantêm escape automático contra XSS.
- O único HTML não escapado no layout é o conteúdo já renderizado da view.
- Os partials são resolvidos por nome interno da aplicação, nunca por entrada do
  usuário.
- O teste dedicado confirma o escape de uma mensagem contendo `<script>`.

## Testes

```bash
php tests/BladeSmallViewFoldersTest.php
bash tests/ci/run-unit.sh
bash tests/ci/run-static-analysis.sh
composer audit --no-dev
curl http://localhost:8080/login
```

Resultados:

- os 15 templates foram compilados pelo Blade e validados com `php -l`;
- suíte completa de testes funcionais e de segurança aprovada;
- análise estática aprovada;
- nenhuma vulnerabilidade conhecida nas dependências;
- `/login` respondeu HTTP 200;
- uma rota inexistente renderizou a nova página Blade 404.

## Validação manual

1. Acessar `/login` e testar autenticação e recuperação de senha.
2. Autenticado, abrir About, Contact, Dashboard, Audit, Identity e Profile.
3. Abrir Finance e suas versões de impressão.
4. Enviar um contato e confirmar a renderização do e-mail de ticket.
5. Acessar uma rota inexistente e uma rota sem permissão para validar 404/403.

## Próximo passo recomendado

Migrar os partials compartilhados para componentes Blade. Depois, concluir as
duas views PHP restantes de `Admin` e extrair componentes repetidos antes de
converter mais expressões PHP para diretivas Blade.

## Retomada

A feature foi finalizada com a tag
`refactor-039-blade-small-view-folders`. A próxima alteração deve partir de
`develop`; `main` permanece intocada.
