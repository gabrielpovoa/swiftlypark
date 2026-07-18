# REFACTOR-001 — Fundação da Arquitetura Modular

## Identificação

- Branch: `feature/modular-architecture-foundation`
- Base: `develop` em `7a1d629`
- Estado: concluído
- Dependências: nenhuma
- Próxima feature planejada: `feature/centralize-database-migrations`

## Objetivo

Definir a arquitetura alvo, as regras de dependência, o processo Git Flow e um
formato de handoff capaz de orientar uma nova janela de contexto. Esta etapa não
move regras de negócio nem altera comportamento em produção.

## Problema encontrado

O projeto possui bons componentes isolados, mas ainda mistura organização MVC
horizontal com módulos parciais. Controllers e Models acumulam orquestração,
persistência, validação e regras de domínio. Não havia um documento único que
definisse a ordem segura da migração nem as invariantes funcionais.

## Estado arquitetural observado

```text
app/Authorization  # já possui Contracts, DTO, Repositories e Services
app/Identity       # já possui Events, Repositories e Services
app/Finance        # já possui Repositories e Services
app/Controllers    # concentra múltiplos contextos e casos de uso
app/Models         # mistura modelo de tela, persistência e operação
app/Repositories   # contém repositories globais e de infraestrutura
app/Bootstrap      # também executa evolução defensiva de schema
```

O autoload PSR-4 já mapeia `App\\` para `app/`, portanto a modularização pode ser
incremental e não exige alteração no Composer nesta etapa.

## Decisões tomadas

1. Permanecer como monólito; microsserviços não fazem parte deste roadmap.
2. Organizar código novo por contexto de negócio.
3. Usar quatro camadas: `Domain`, `Application`, `Infrastructure` e
   `Presentation`.
4. Preservar rotas e schema enquanto cada módulo é extraído.
5. Migrar por fluxo vertical completo, nunca mover todos os arquivos de uma só
   vez.
6. Tratar `company_id`, autorização, auditoria e transações como invariantes
   verificáveis em toda feature.
7. Evitar interfaces sem consumidor real; contratos serão introduzidos junto
   ao primeiro caso de uso que necessitar inversão de dependência.

## Alterações realizadas

- Criado `ARCHITECTURE-REFACTOR-ROADMAP.md` como índice permanente.
- Criado este handoff autocontido.
- Formalizado o processo de uma feature Git Flow por modificação.
- Formalizadas as dependências permitidas entre camadas.
- Registrada a ordem inicial das features.

## Alterações deliberadamente não realizadas

- Nenhum namespace de produção foi movido.
- Nenhuma rota foi modificada.
- Nenhuma tabela foi alterada.
- Nenhuma abstração vazia foi adicionada ao código.
- Nenhum comportamento legado foi removido.

## Critérios de aceite

- [x] Feature criada pelo Git Flow a partir de `develop` limpa.
- [x] Roadmap autocontido criado na raiz.
- [x] Invariantes funcionais e de segurança documentadas.
- [x] Ordem das próximas features registrada.
- [x] Validações finais executadas.
- [x] Handoff marcado como concluído.

## Validações previstas

```bash
git diff --check
php tests/TenantContextTest.php
php tests/RepositoryTenantIsolationTest.php
php tests/SecurityTestSuite.php
php tests/GovernanceUserManagementTest.php
php tests/PriceCalculatorTest.php
```

## Riscos e rollback

Esta etapa adiciona somente documentação e não altera runtime. O rollback
consiste em reverter os dois arquivos Markdown criados nesta branch.

## Resultado das validações

Executado em 18/07/2026:

- `git diff --check`: aprovado;
- `TenantContextTest.php`: aprovado;
- `RepositoryTenantIsolationTest.php`: aprovado;
- `SecurityTestSuite.php`: aprovado;
- `GovernanceUserManagementTest.php`: aprovado;
- `PriceCalculatorTest.php`: aprovado.

## Próximo passo exato

1. Executar as validações previstas.
2. Atualizar este documento com os resultados.
3. Marcar a feature 001 como concluída no roadmap.
4. Commitar a documentação na feature.
5. Após integração, iniciar com Git Flow:

```bash
git flow feature start centralize-database-migrations
```
