# Roadmap da Refatoração Arquitetural

## Propósito

Este arquivo é o índice permanente da migração do SwiftlyPark para um monólito
modular. Ele deve permitir que uma nova janela de contexto descubra o estado do
trabalho sem depender do histórico da conversa.

## Estado de referência

- Branch protegida de integração: `develop`.
- Commit de origem: `7a1d629` (`feat: implementa cobrança híbrida, contratos mensalistas e BI consolidado`).
- Estratégia: Git Flow, uma `feature/*` por modificação arquitetural.
- Regra: nenhuma implementação deve ser feita diretamente em `develop`.
- Arquitetura atual: monólito MVC com Service Layer, repositories e módulos
  parciais de Authorization, Identity e Finance.
- Arquitetura alvo: monólito modular organizado por contexto de negócio e por
  camadas `Domain`, `Application`, `Infrastructure` e `Presentation`.

## Invariantes que não podem regredir

1. Toda operação deve permanecer isolada por `company_id`.
2. Super-admin global não pode assumir um papel de tenant implicitamente.
3. Checkout, pagamento, liberação da vaga e auditoria devem continuar atômicos.
4. Contrato vigente classifica a estadia como `MONTHLY`; veículo avulso como
   `ROTATING`.
5. Receita mensalista é reconhecida na contratação/renovação, nunca novamente
   no checkout.
6. Inputs livres permanecem sanitizados e toda saída HTML permanece escapada.
7. Cada feature deve manter compatibilidade com as rotas existentes, salvo
   mudança explicitamente documentada.

## Sequência das features

| Ordem | Branch Git Flow | Resultado esperado | Estado |
|---|---|---|---|
| 001 | `feature/modular-architecture-foundation` | Convenções, mapa e handoff da arquitetura | Concluída |
| 002 | `feature/centralize-database-migrations` | DDL removido do ciclo normal de bootstrap | Pendente |
| 003 | `feature/split-admin-provisioning-controller` | Casos administrativos separados por responsabilidade | Pendente |
| 004 | `feature/extract-company-module` | Contexto Companies modularizado | Pendente |
| 005 | `feature/extract-identity-module` | Contexto Identity modularizado | Pendente |
| 006 | `feature/extract-parking-module` | Check-in, ocupação e checkout modularizados | Pendente |
| 007 | `feature/extract-billing-module` | Tarifários e contratos no contexto Billing | Pendente |
| 008 | `feature/extract-finance-module` | Finance completo e limites de reporting definidos | Pendente |
| 009 | `feature/add-domain-value-objects` | Money, Plate, períodos e enums tipados | Pendente |
| 010 | `feature/add-repository-contracts` | Domínio desacoplado de implementações MySQL | Pendente |
| 011 | `feature/add-financial-ledger` | Livro financeiro unificado e auditável | Pendente |
| 012 | `feature/add-financial-integration-tests` | Fluxos críticos validados contra MySQL | Pendente |
| 013 | `feature/add-concurrency-tests` | Check-in, checkout e renovação concorrentes cobertos | Pendente |
| 014 | `feature/add-background-job-queue` | E-mails e trabalhos pesados fora da requisição | Pendente |

## Estrutura alvo

```text
app/
├── Companies/
├── Identity/
├── Parking/
├── Billing/
├── Finance/
└── Shared/
    ├── Domain/
    ├── Application/
    ├── Infrastructure/
    └── Presentation/
```

Cada contexto poderá usar somente:

- suas próprias camadas internas;
- contratos públicos de outro contexto;
- componentes explicitamente compartilhados em `Shared`.

`Domain` não poderá depender de HTTP, sessão, PDO, views ou implementações de
framework. `Application` orquestrará casos de uso. `Infrastructure` implementará
persistência e integrações. `Presentation` adaptará HTTP, CLI e views.

## Processo obrigatório por feature

1. Partir de `develop` limpa e atualizada.
2. Executar `git flow feature start <nome>`.
3. Criar `REFACTOR-NNN-<TEMA>.md` na raiz.
4. Registrar estado inicial, decisões, arquivos e riscos.
5. Implementar uma única mudança arquitetural limitada.
6. Executar testes proporcionais ao risco.
7. Atualizar o handoff com resultados e próximo passo exato.
8. Revisar `git diff --check` e o escopo do diff.
9. Commitar na feature; não fazer push ou merge remoto implicitamente.
10. Finalizar pelo Git Flow somente após validação da etapa.

## Como retomar em um novo contexto

1. Ler este arquivo por completo.
2. Ler o último `REFACTOR-NNN-*.md` marcado como concluído ou em andamento.
3. Executar `git status --short --branch`.
4. Confirmar que a branch atual corresponde ao handoff.
5. Executar os testes listados no handoff antes de alterar código.
6. Continuar a partir da seção **Próximo passo exato**.
