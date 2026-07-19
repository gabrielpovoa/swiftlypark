# Histórico de refatorações

Os documentos abaixo registram objetivo, decisões, evidências e o próximo passo
de cada alteração arquitetural. Novos handoffs devem ser criados neste diretório,
nunca na raiz do projeto.

## Sequência

1. [REFACTOR-001 — Fundação modular](REFACTOR-001-MODULAR-ARCHITECTURE-FOUNDATION.md)
2. [REFACTOR-002 — Migrações](REFACTOR-002-DATABASE-MIGRATIONS.md)
3. [REFACTOR-003 — Divisão do controller administrativo](REFACTOR-003-SPLIT-ADMIN-PROVISIONING-CONTROLLER.md)
4. [REFACTOR-004 — Módulo Companies](REFACTOR-004-EXTRACT-COMPANY-MODULE.md)
5. [REFACTOR-005 — Módulo Identity](REFACTOR-005-EXTRACT-IDENTITY-MODULE.md)
6. [REFACTOR-006 — Módulo Parking](REFACTOR-006-EXTRACT-PARKING-MODULE.md)
7. [REFACTOR-007 — Módulo Billing](REFACTOR-007-EXTRACT-BILLING-MODULE.md)
8. [REFACTOR-008 — Módulo Finance](REFACTOR-008-EXTRACT-FINANCE-MODULE.md)
9. [REFACTOR-009 — Value Objects](REFACTOR-009-DOMAIN-VALUE-OBJECTS.md)
10. [REFACTOR-010 — Contratos de repositories](REFACTOR-010-REPOSITORY-CONTRACTS.md)
11. [REFACTOR-011 — Ledger financeiro](REFACTOR-011-FINANCIAL-LEDGER.md)
12. [REFACTOR-012 — Testes financeiros](REFACTOR-012-FINANCIAL-INTEGRATION-TESTS.md)
13. [REFACTOR-013 — Testes de concorrência](REFACTOR-013-CONCURRENCY-TESTS.md)
14. [REFACTOR-014 — Fila de jobs](REFACTOR-014-BACKGROUND-JOB-QUEUE.md)
15. [REFACTOR-015 — Gestão de empresas](REFACTOR-015-CENTRALIZE-COMPANY-MANAGEMENT.md)
16. [REFACTOR-016 — Upload de logo](REFACTOR-016-FIX-COMPANY-LOGO-UPLOAD-PATH.md)
17. [REFACTOR-017 — Organização do projeto](REFACTOR-017-ORGANIZE-PROJECT-DOCUMENTATION.md)
18. [REFACTOR-018 — Finance global](REFACTOR-018-GLOBAL-SUPER-ADMIN-FINANCE.md)
19. [REFACTOR-019 — Precedência do contexto financeiro](REFACTOR-019-FIX-FINANCE-CONTEXT-PRECEDENCE.md)

## Retomada rápida

1. Leia o documento mais recente.
2. Execute `git status --short --branch`.
3. Confirme a branch indicada no handoff.
4. Rode os testes registrados antes de continuar.
