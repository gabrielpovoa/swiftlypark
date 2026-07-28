# REFACTOR-040 — Conclusão da migração das views para Blade

## Controle

- Branch: `feature/blade-final-views`
- Base: `develop`
- Data: 28/07/2026
- Estado: concluído e integrado à `develop`

## Objetivo

Finalizar a adoção do Blade em `app/Views`, migrando as duas views
administrativas e os cinco partials compartilhados que ainda utilizavam arquivos
PHP puros.

## Escopo migrado

- `Admin/dashboard-global`;
- `Admin/provisioning`;
- `partials/head`;
- `partials/header`;
- `partials/footer`;
- `partials/role-badge`;
- `partials/security-alerts-widget`.

Após esta alteração, não existe nenhum arquivo de view `.php` puro em
`app/Views`. Todas as views usam `.blade.php`.

## Implementação

- `Controller::renderPartial()` agora procura primeiro pelo partial Blade e
  mantém fallback para PHP por compatibilidade arquitetural.
- O header renderiza o componente de papel por meio do resolvedor de partials.
- O dashboard global renderiza o widget de alertas de segurança pelo mesmo
  mecanismo.
- O título compartilhado do documento utiliza escape automático do Blade.
- O `role-badge` deixou de fazer escape manual antecipado e utiliza `{{ }}`,
  evitando inconsistência e dupla codificação.
- Testes que inspecionavam os antigos arquivos PHP foram atualizados para os
  caminhos Blade.

## Segurança

- Dados do título, nome do papel e ícone passam pelo escape padrão do Blade.
- Partials são resolvidos somente no diretório interno `app/Views/partials`.
- O conteúdo HTML não escapado permanece restrito à composição de templates e
  partials já renderizados pela própria aplicação.
- O teste final usa conteúdo contendo `<script>` para confirmar o escape no
  `head` e no `role-badge`.

## Testes

```bash
php tests/BladeFinalViewsTest.php
bash tests/ci/run-unit.sh
bash tests/ci/run-static-analysis.sh
composer audit --no-dev
curl http://localhost:8080/login
curl http://localhost:8080/rota-final-inexistente
```

Resultados:

- todas as views Blade foram compiladas e validadas com `php -l`;
- nenhuma view PHP legada foi encontrada em `app/Views`;
- suíte completa funcional e de segurança aprovada;
- análise estática aprovada;
- nenhuma vulnerabilidade conhecida nas dependências;
- `/login` respondeu HTTP 200;
- a página de rota inexistente respondeu HTTP 404.

## Validação manual

1. Entrar no sistema e conferir header, tenant switcher, perfil e footer.
2. Abrir `/admin/dashboard` como Super-Admin e conferir alertas de segurança.
3. Abrir a governança SaaS e testar filtros e formulários.
4. Trocar o contexto de empresa e confirmar nome, logo e favicon.
5. Abrir o perfil e conferir o badge do papel atual.
6. Sair da aplicação e validar login, 403 e 404.

## Próximo passo recomendado

Agora que a resolução Blade está completa, extrair componentes reutilizáveis
para campos, alertas, cards, badges e modais. A conversão dos blocos PHP internos
para diretivas Blade pode continuar gradualmente, protegida pelos testes atuais,
sem nova troca de extensão.

## Retomada

A feature foi finalizada com a tag `refactor-040-blade-final-views`. A próxima
alteração deve partir de `develop`; `main` permanece intocada.
