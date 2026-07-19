# REFACTOR-005 — Extração do módulo Identity

## Identificação

- Branch: `feature/extract-identity-module`
- Base: `develop` após a conclusão da feature 004
- Estado: concluída
- Feature anterior: `REFACTOR-004-EXTRACT-COMPANY-MODULE.md`

## Objetivo

Organizar autenticação administrativa, usuários, vínculos, papéis e permissões
em camadas explícitas de Domain, Application e Infrastructure.

## Estado inicial

```text
app/Identity/
├── Events/UserCreatedEvent.php
├── Repositories/IdentityManagementRepository.php
└── Services/
    ├── IdentityManagementService.php
    └── UserProvisioningService.php
```

Os comportamentos já estão parcialmente agrupados, mas os diretórios e
namespaces ainda representam tipos técnicos (`Services`, `Repositories`) em vez
das camadas arquiteturais definidas no roadmap.

## Estrutura alvo

```text
app/Identity/
├── Application/
├── Domain/Events/
├── Infrastructure/
└── Presentation/
```

## Invariantes

- Autenticação e autorização continuam fail-closed.
- Um vínculo sempre contém `user_id`, `company_id` e papel ativo.
- Super-admin global não perde seu papel ao trocar o tenant observado.
- Criação e vínculo de usuário continuam transacionais e auditados.
- Rotas, payloads e mensagens públicas permanecem iguais.

## Implementado

- Eventos movidos para `Identity\Domain\Events`.
- Serviços de casos de uso movidos para `Identity\Application`.
- Repositories PDO movidos para `Identity\Infrastructure`.
- Controllers de gestão e provisionamento movidos para
  `Identity\Presentation`.
- Todos os imports de aplicação, CLI, middleware, rotas e testes foram
  atualizados; os diretórios técnicos antigos foram removidos.
- Criado `IdentityModuleBoundaryTest` para validar camadas, ausência dos
  diretórios legados, independência do domínio e wiring das rotas.

## Próximo passo exato

1. Executar a suíte completa.
2. Atualizar o roadmap e marcar a feature como concluída.
3. Finalizar via Git Flow e iniciar a feature 006 (Parking).
