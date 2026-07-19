# REFACTOR-020 — Melhorar a leitura da trilha de auditoria

## Identificação

- Branch: `feature/improve-audit-log-readability`
- Base: `develop` após a conclusão da feature 019
- Estado: concluída
- Feature anterior: `REFACTOR-019-FIX-FINANCE-CONTEXT-PRECEDENCE.md`

## Problema

A auditoria apresentava os payloads técnicos como se todos fossem alterações de
campos. Eventos como `GLOBAL_DASHBOARD_ACCESS` apareciam como “Evento: Não
informado → GLOBAL_DASHBOARD_ACCESS”, dificultando a leitura para pessoas sem
conhecimento técnico.

## Implementado

- A listagem foi reorganizada em colunas: data, responsável, tipo de evento,
  ação realizada e informações.
- Códigos conhecidos são traduzidos para títulos operacionais em português.
- Eventos de dashboard global, modo suporte, cobrança e contratos mensalistas
  possuem descrições específicas.
- Metadados (`event`, navegador e contexto de suporte) deixaram de ser exibidos
  como alterações de negócio.
- Campos sem valor anterior não exibem mais a comparação artificial com “Não
  informado”.
- Cada linha possui um único controle “Ver detalhes”, sem dependência de
  JavaScript.
- O painel técnico informa ação técnica, rota, responsável, e-mail, empresa,
  IP, navegador, request ID e registro afetado.
- O JSON original continua disponível em “Ver dados brutos”, formatado e com
  escapamento HTML na renderização.
- As consultas de auditoria agora carregam também o nome da empresa.
- A visualização permanece responsiva para telas menores.

## Segurança

- Todos os valores continuam escapados com `htmlspecialchars` e `ENT_QUOTES`.
- O payload bruto nunca é interpretado como HTML.
- Nenhuma informação original do log foi alterada ou removida do banco.
- A mudança é somente de consulta e apresentação; não há migração.

## Validação automatizada

```bash
php tests/AuditLogPresenterTest.php
php tests/SecurityTestSuite.php
php -l app/Views/Audit/index.php
php -l app/Services/AuditLogPresenter.php
php -l app/Repositories/AuditLogRepository.php
```

## Validação manual

1. Entrar como Super-Admin e acessar `/audit`.
2. Confirmar que o evento de dashboard aparece como “Acesso ao painel global”.
3. Confirmar que modo suporte exibe empresa e perfil em linguagem clara.
4. Abrir “Ver detalhes” e conferir rota, IP, e-mail, empresa e request ID.
5. Abrir “Ver dados brutos” e conferir o payload original.
6. Repetir a verificação em viewport mobile.

## Próximo passo exato

Validar visualmente a listagem com eventos reais e, se necessário, ampliar o
dicionário de tradução quando novos códigos de evento forem introduzidos.
