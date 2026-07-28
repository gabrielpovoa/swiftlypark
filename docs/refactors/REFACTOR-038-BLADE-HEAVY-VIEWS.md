# REFACTOR-038 — Blade nas views pesadas

## Controle

- Branch: `feature/blade-heavy-views`
- Base: `develop`
- Data: 28/07/2026
- Estado: concluído e integrado à `develop`

## Objetivo

Introduzir Blade de forma incremental, preservando o MVC e as views PHP
existentes, e migrar primeiro as telas mais extensas dos contextos de empresas,
tarifários e vagas.

## Implementação

- Adicionados `illuminate/view` e `illuminate/events` 12.x, compatíveis com o
  PHP 8.2 atual.
- Criado `Core\View\BladeViewRenderer` com compilador, resolução de views e
  cache próprios, sem introduzir o framework Laravel completo.
- O `Controller` agora procura primeiro por `<view>.blade.php` e mantém o
  fallback transparente para `<view>.php`.
- O layout PHP atual continua ativo, permitindo migrar uma tela por vez.
- Migradas para Blade:
  - diretório de empresas;
  - cadastro, identidade e pricing da empresa;
  - disponibilidade, check-in, gestão, criação e indisponibilidade de vagas.
- Listas de empresas, tipos de veículo, tarifas e opções de criação já utilizam
  diretivas como `@if`, `@foreach`, `@continue`, `@selected` e `@checked`.
- Saídas convertidas usam `{{ }}` por padrão, mantendo escape automático contra
  XSS. Blocos PHP ainda existentes nas telas migradas podem ser convertidos
  gradualmente sem alterar rotas ou controllers.
- Criado cache em `storage/framework/views`; o Docker prepara o diretório com
  permissão de escrita na inicialização.

## Compatibilidade

- Nomes passados pelos controllers não mudaram.
- Rotas e payloads dos formulários não mudaram.
- Views legadas continuam sendo renderizadas normalmente.
- Os testes que inspecionavam diretamente os antigos arquivos `.php` foram
  atualizados para os equivalentes `.blade.php`.

## Segurança

- O teste do renderer confirma que `{{ }}` neutraliza tags HTML e scripts.
- O teste das telas pesadas usa um nome de empresa potencialmente malicioso e
  confirma o escape no HTML final.
- Nenhuma saída foi convertida para `{!! !!}`.
- Tokens CSRF e validações existentes permanecem inalterados.

## Testes

```bash
php tests/BladeViewRendererTest.php
php tests/BladeHeavyViewsTest.php
bash tests/ci/run-unit.sh
bash tests/ci/run-static-analysis.sh
composer audit --no-dev
docker compose exec -T app php tests/BladeHeavyViewsTest.php
```

Resultados:

- 37 testes funcionais e de segurança aprovados;
- análise estática aprovada;
- nenhuma vulnerabilidade conhecida nas dependências;
- compilação das sete views migradas aprovada dentro do container.

## Validação manual

1. Executar `docker compose up -d app`.
2. Acessar `/admin/companies` e abrir uma empresa.
3. Alterar dados cadastrais e conferir os toggles e tarifários.
4. Acessar `/vacancy`, iniciar um check-in e abrir `/vacancy/manage`.
5. Criar uma vaga e validar o cenário de tipo indisponível.
6. Confirmar que mensagens, nomes e valores continuam sendo exibidos e que os
   formulários salvam normalmente.

## Próximo passo recomendado

Extrair componentes Blade reutilizáveis para alertas, campos de formulário,
cards de empresa e cards de tarifário. Depois disso, migrar o layout principal e
as demais views por módulo, sem conversão em massa.

## Retomada

A feature foi finalizada com a tag `refactor-038-blade-heavy-views`. A próxima
alteração deve partir de `develop`; `main` permanece intocada.
