# Spec-SaaS-008: SwiftlyPark Multi-tenant Platform

## 1. Overview

Esta especificação define a evolução do **SwiftlyPark** para uma arquitetura **SaaS Multi-tenant**, permitindo que múltiplas empresas utilizem a mesma aplicação com isolamento completo de dados.

O objetivo é garantir que cada empresa possua seu próprio contexto operacional, mantendo governança centralizada para usuários MASTER e administração delegada para usuários ADMIN.

---

## 2. Objetivos

- Transformar a aplicação em uma plataforma Multi-tenant.
- Garantir isolamento total dos dados por `company_id`.
- Implementar governança centralizada através do papel MASTER.
- Permitir administração independente para cada empresa.
- Preparar a arquitetura para crescimento horizontal do sistema.

---

## 3. Modelagem de Dados

### Tabela `companies`

Representa cada empresa (tenant) cadastrada na plataforma.

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `id` | PK, Integer | Identificador da empresa |
| `name` | String | Nome da empresa |
| `slug` | String | Identificador único da empresa |
| `created_at` | Timestamp | Data de criação |

---

### Isolamento por Tenant

Todas as entidades transacionais e operacionais deverão possuir a coluna:

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `company_id` | FK, Integer | Empresa proprietária do registro |

Exemplos:

- checkins;
- veículos;
- transações financeiras;
- logs;
- usuários operacionais;
- demais entidades de negócio.

---

### Tabela `company_user`

Responsável por vincular usuários às empresas.

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `user_id` | FK | Usuário |
| `company_id` | FK | Empresa |
| `role_id` | FK | Papel exercido naquela empresa |

Essa estrutura permite que um mesmo usuário possua diferentes papéis em empresas distintas.

---

### Soft Delete

Todas as entidades críticas deverão possuir:

| Coluna | Tipo |
|---------|------|
| `deleted_at` | Timestamp (Nullable) |

---

## 4. Arquitetura de Acesso

### TenantContext

Implementar um componente responsável por armazenar o contexto da empresa ativa durante toda a requisição.

Responsabilidades:

- armazenar o `company_id`;
- disponibilizar o contexto para Services e Repositories;
- impedir vazamento de dados entre empresas.

---

### BaseRepository

Todos os repositórios deverão herdar de um repositório base.

Para usuários ADMIN, as consultas deverão aplicar automaticamente:

```sql
WHERE company_id = ?
```

Esse filtro deverá ocorrer de forma transparente para as camadas superiores da aplicação.

---

### Permissões do MASTER

Usuários com papel MASTER poderão alterar o contexto do `TenantContext`.

Isso permitirá:

- visualizar dados de qualquer empresa;
- administrar empresas;
- prestar suporte;
- realizar auditorias globais.

---

## 5. Fluxo de Identidade

### Onboarding

As rotas públicas de cadastro deverão ser removidas.

Exemplo:

```text
/CreateAcc
```

A criação de contas passará a ser controlada exclusivamente pela administração da plataforma.

---

### Provisionamento

Novos usuários deverão ser criados apenas por usuários MASTER.

Endpoints previstos:

```http
POST /admin/users/create
```

```http
POST /admin/companies/create
```

O processo deverá incluir:

- criação da empresa;
- criação do usuário administrador;
- vinculação entre usuário, empresa e papel.

---

### Context Switcher

Usuários MASTER ou usuários vinculados a múltiplas empresas deverão visualizar um seletor de empresa na interface.

Esse componente permitirá alternar dinamicamente o contexto ativo (`company_id`) da sessão.

---

## 6. Regras de Negócio

### Isolamento de Dados

Usuários ADMIN somente poderão acessar registros pertencentes à empresa atualmente selecionada.

Em nenhuma circunstância um ADMIN poderá visualizar dados de outro tenant.

---

### Governança Centralizada

Usuários MASTER poderão:

- criar empresas;
- criar administradores;
- alternar entre tenants;
- visualizar informações globais;
- administrar a plataforma.

---

### Papéis por Empresa

Um mesmo usuário poderá possuir papéis distintos dependendo da empresa.

Exemplo:

| Empresa | Papel |
|----------|--------|
| Empresa A | ADMIN |
| Empresa B | OPERATOR |

A autorização deverá considerar sempre o papel associado ao tenant atualmente selecionado.

---

## 7. Critérios de Aceitação

- [ ] Existe uma tabela `companies`.
- [ ] Existe uma tabela `company_user`.
- [ ] Todas as entidades transacionais possuem `company_id`.
- [ ] Todas as entidades críticas possuem `deleted_at`.
- [ ] O `TenantContext` mantém o contexto da empresa durante a requisição.
- [ ] O `BaseRepository` aplica automaticamente o filtro por `company_id`.
- [ ] Usuários ADMIN acessam apenas dados do seu tenant.
- [ ] Usuários MASTER podem alternar o contexto da empresa.
- [ ] As rotas públicas de cadastro foram removidas.
- [ ] Apenas usuários MASTER podem criar empresas e administradores.
- [ ] Usuários vinculados a múltiplos tenants possuem um seletor de empresa na interface.
- [ ] Um usuário pode possuir papéis diferentes em empresas distintas.

---

## 8. Contexto Atual do SwiftlyPark

O SwiftlyPark atualmente opera como uma aplicação single-tenant.

Já existem implementações relevantes que devem ser preservadas durante a migração:

- autenticação por sessão;
- recuperação de senha por OTP;
- RBAC com papéis e permissões;
- papel MASTER com painel de gestão de identidade;
- auditoria de ações operacionais e eventos de segurança;
- BI financeiro com permissões `finance.view` e `finance.adjust`;
- rastreabilidade por `created_by` e `updated_by`;
- sidebar dinâmica baseada em permissões.

As principais tabelas operacionais atuais ainda não possuem isolamento por empresa:

- `vagas_disponiveis`;
- `vagas_preenchidas`;
- `transacoes`;
- `financial_adjustments`;
- `audit_logs`.

Essas tabelas deverão receber `company_id`.

---

## 9. Decisões Arquiteturais

### 9.1 Fail-Closed

O sistema deve negar acesso quando não houver contexto de empresa válido para rotas operacionais.

Regra:

```text
sem company_id em rota tenant-scoped = acesso negado
```

Exceção:

```text
MASTER em rota explicitamente global
```

---

### 9.2 Ordem dos Middlewares

A ordem recomendada é:

```text
IdentityMiddleware
      ↓
TenantMiddleware
      ↓
AuthorizeMiddleware
      ↓
Controller
```

Motivo:

- primeiro identifica o usuário;
- depois resolve o tenant;
- depois valida permissões;
- por fim executa o controller.

---

### 9.3 TenantContext

O `TenantContext` deverá ser implementado em:

```text
app/Context/TenantContext.php
```

Responsabilidades:

- armazenar `company_id` da requisição;
- armazenar dados mínimos da empresa ativa;
- indicar se a requisição está em escopo global;
- expor empresas disponíveis para o usuário;
- negar acesso quando o contexto obrigatório estiver ausente.

O `TenantContext` deve ser injetado em Services e Repositories.

---

### 9.4 BaseRepository

O `BaseRepository` deverá ser implementado em:

```text
app/Repositories/BaseRepository.php
```

Responsabilidades:

- centralizar helpers de filtro por tenant;
- aplicar `company_id` nas queries operacionais;
- impedir execução tenant-scoped sem empresa ativa;
- permitir bypass apenas para MASTER em rotas globais.

O filtro não deve ser responsabilidade dos controllers.

---

## 10. Esquemas SQL Propostos

### 10.1 Tabela `companies`

```sql
CREATE TABLE companies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    document VARCHAR(40) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(40) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    deleted_at DATETIME(6) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_companies_slug (slug),
    KEY idx_companies_status (status),
    KEY idx_companies_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 10.2 Tabela `company_user`

```sql
CREATE TABLE company_user (
    company_id BIGINT UNSIGNED NOT NULL,
    user_id INT NOT NULL,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    created_by INT NULL,
    PRIMARY KEY (company_id, user_id),
    CONSTRAINT fk_company_user_company
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_company_user_user
        FOREIGN KEY (user_id) REFERENCES usuario (id_usuario)
        ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_company_user_created_by
        FOREIGN KEY (created_by) REFERENCES usuario (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    KEY idx_company_user_user (user_id),
    KEY idx_company_user_default (user_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 11. Estratégia de Migração de Dados

A migração deve preservar os dados atuais.

Estratégia:

1. criar empresa padrão;
2. vincular todos os usuários atuais à empresa padrão;
3. adicionar `company_id` como `NULL` nas tabelas operacionais;
4. preencher dados legados com o `company_id` da empresa padrão;
5. validar contagens;
6. alterar `company_id` para `NOT NULL` nas tabelas operacionais;
7. criar foreign keys e índices.

Empresa inicial:

```text
name: SwiftlyPark Default
slug: swiftlypark-default
```

O sistema deve continuar funcionando após essa fase, mesmo antes da refatoração completa dos repositories.

---

## 12. Governança de Onboarding

O fluxo público atual de cadastro deverá ser substituído.

Rotas atuais:

```text
GET  /CreateAcc
POST /CreateAcc/create
```

Essas rotas não devem criar usuários ativos em ambiente multi-tenant.

Novo fluxo recomendado:

```text
GET  /identity/create
POST /identity/create
```

Proteção:

```text
permission: identity.manage
role: MASTER
```

Ao criar usuário, o MASTER deverá informar:

- nome;
- e-mail;
- empresa;
- papel;
- senha temporária ou convite;
- permissões extras, quando necessário.

O sistema deverá criar:

- registro em `login`;
- registro em `usuario`;
- vínculo em `company_user`;
- vínculo em `user_roles`;
- auditoria da criação.

---

## 13. Integração com Auditoria

O `AuditService` deverá receber o `TenantContext`.

Cada registro em `audit_logs` deverá incluir `company_id`.

Regras:

- evento operacional exige `company_id`;
- evento global de MASTER pode ter `company_id = NULL`;
- tentativas de acesso negado devem registrar `company_id` quando houver;
- troca de empresa deve gerar evento próprio.

Eventos sugeridos:

- `TENANT_SWITCHED`;
- `COMPANY_CREATED`;
- `COMPANY_UPDATED`;
- `USER_CREATED`;
- `USER_COMPANY_LINKED`;
- `USER_COMPANY_UNLINKED`.

---

## 14. UI e UX Adaptativa

O front-end deve receber um contexto enriquecido contendo:

- usuário atual;
- role metadata;
- permissões;
- empresa ativa;
- empresas disponíveis.

Exemplo conceitual:

```json
{
  "tenant": {
    "company_id": 1,
    "name": "SwiftlyPark Default",
    "slug": "swiftlypark-default"
  },
  "available_companies": [
    {
      "id": 1,
      "name": "SwiftlyPark Default"
    }
  ]
}
```

O seletor de empresa deve aparecer apenas quando:

```text
usuário é MASTER
e possui mais de uma empresa disponível
```

Rotas operacionais devem ficar ocultas quando não houver empresa ativa.

---

## 15. Fases de Implementação

### Fase 1 — Fundação de Dados

Objetivo: preparar o banco sem quebrar o sistema atual.

Tarefas:

- criar migration `companies`;
- criar migration `company_user`;
- criar empresa padrão;
- vincular usuários atuais à empresa padrão;
- adicionar `company_id` como `NULL` em tabelas operacionais;
- executar backfill dos dados existentes;
- validar integridade;
- adicionar índices e constraints após validação.

Critério de saída:

```text
todos os dados existentes pertencem à empresa padrão
```

---

### Fase 2 — Contexto de Tenant

Objetivo: disponibilizar `company_id` durante a requisição.

Tarefas:

- criar `TenantContext`;
- criar `TenantMiddleware`;
- criar exceção `TenantRequiredException`;
- carregar empresas disponíveis do usuário;
- definir empresa ativa a partir da sessão ou empresa padrão;
- bloquear acesso tenant-scoped sem empresa.

Critério de saída:

```text
requisições autenticadas possuem contexto de empresa válido
```

---

### Fase 3 — Repositories Tenant-Aware

Objetivo: impedir vazamento de dados entre empresas.

Tarefas:

- criar `BaseRepository`;
- refatorar repositories de vagas;
- refatorar repositories financeiros;
- refatorar repositories de auditoria;
- refatorar relatórios;
- garantir filtros automáticos por `company_id`;
- revisar queries diretas em Models antigos.

Critério de saída:

```text
ADMIN, OPERATOR, AUDITOR e FINANCE acessam apenas dados da empresa ativa
```

---

### Fase 4 — Auditoria Multi-tenant

Objetivo: registrar `company_id` de forma transparente.

Tarefas:

- adicionar `company_id` em `audit_logs`;
- atualizar `AuditService`;
- atualizar `SecurityAuditService`;
- atualizar `AuditLogRepository`;
- atualizar filtros da tela de auditoria;
- registrar eventos de troca de tenant.

Critério de saída:

```text
todo evento operacional possui company_id
```

---

### Fase 5 — Onboarding Administrativo

Objetivo: substituir cadastro público por criação governada.

Tarefas:

- desabilitar criação pública ativa em `/CreateAcc`;
- criar fluxo de criação por MASTER;
- exigir empresa e papel;
- criar vínculo em `company_user`;
- auditar criação e vínculo;
- opcionalmente exibir tela pública como solicitação de acesso.

Critério de saída:

```text
nenhum usuário operacional nasce sem empresa
```

---

### Fase 6 — UI Multi-tenant

Objetivo: refletir contexto de empresa na interface.

Tarefas:

- exibir empresa ativa no header/sidebar;
- criar company switcher para MASTER;
- esconder menus operacionais sem tenant;
- atualizar role badge/contexto;
- exibir mensagens claras quando empresa não estiver definida.

Critério de saída:

```text
usuário entende em qual empresa está operando
```

---

### Fase 7 — Hardening e Testes

Objetivo: garantir isolamento e segurança.

Tarefas:

- testar acesso cruzado entre empresas;
- testar MASTER global;
- testar ADMIN sem empresa;
- testar BI financeiro por tenant;
- testar auditoria por tenant;
- revisar todas as queries sem `company_id`;
- adicionar logs de tentativas negadas.

Critério de saída:

```text
nenhum usuário não-MASTER consegue acessar dados de outro tenant
```

---

## 16. Documento de Handoff

O contexto detalhado para continuidade por outro agente está em:

```text
docs/handoffs/multi-tenancy-implementation-context.md
```

Esse arquivo deve ser lido antes de iniciar qualquer implementação.
