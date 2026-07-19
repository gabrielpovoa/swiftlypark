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
20. [REFACTOR-020 — Leitura da trilha de auditoria](REFACTOR-020-IMPROVE-AUDIT-LOG-READABILITY.md)
21. [REFACTOR-021 — Provisionamento centralizado de usuários](REFACTOR-021-CENTRALIZE-USER-PROVISIONING.md)
22. [REFACTOR-022 — Grid de usuários ativos](REFACTOR-022-IDENTITY-ACTIVE-USER-GRID.md)
23. [REFACTOR-023 — Logos maiores das empresas](REFACTOR-023-INCREASE-COMPANY-LOGO-SIZE.md)
24. [REFACTOR-024 — Favicon dinâmico por empresa](REFACTOR-024-TENANT-DYNAMIC-FAVICON.md)
25. [REFACTOR-025 — Favicon sem borda branca](REFACTOR-025-REMOVE-FAVICON-WHITE-BORDER.md)
26. [REFACTOR-026 — Entrega confiável de e-mails](REFACTOR-026-RELIABLE-EMAIL-DELIVERY.md)
27. [REFACTOR-027 — Dashboard administrativo por empresa](REFACTOR-027-SCOPE-ADMIN-DASHBOARD-BY-COMPANY.md)
28. [REFACTOR-028 — Remoção do papel Master](REFACTOR-028-REMOVE-MASTER-ROLE.md)
29. [REFACTOR-029 — Administração da empresa por tenant](REFACTOR-029-TENANT-COMPANY-ADMINISTRATION.md)
30. [REFACTOR-030 — Proteção da identidade do Super-Admin](REFACTOR-030-PROTECT-SUPER-ADMIN-IDENTITY.md)
31. [REFACTOR-031 — Governança SaaS para administradores](REFACTOR-031-TENANT-ADMIN-GOVERNANCE.md)
32. [REFACTOR-032 — Ciclo de usuários pelo administrador](REFACTOR-032-TENANT-ADMIN-USER-LIFECYCLE.md)
33. [REFACTOR-033 — Visão financeira do administrador](REFACTOR-033-TENANT-ADMIN-FINANCE-OVERVIEW.md)
34. [REFACTOR-034 — Persistência do favicon da empresa](REFACTOR-034-TENANT-FAVICON-PERSISTENCE.md)

## Retomada rápida

1. Leia o documento mais recente.
2. Execute `git status --short --branch`.
3. Confirme a branch indicada no handoff.
4. Rode os testes registrados antes de continuar.
