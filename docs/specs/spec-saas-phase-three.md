# Spec-SAAS-008-Phase-3: Tenant-Aware Audit & Governance

## 1. Overview

Esta especificação define a terceira fase da arquitetura **Multi-tenant** do SwiftlyPark, implementando a segregação dos registros de auditoria por empresa.

O objetivo é garantir que todos os eventos registrados pelo sistema sejam vinculados ao tenant correspondente e que a visualização dos logs respeite rigorosamente o escopo de acesso dos usuários, diferenciando permissões entre perfis **ADMIN** e **MASTER**.

---

## 2. Objetivos

- Tornar o sistema de auditoria Multi-tenant.
- Associar automaticamente todos os logs ao tenant ativo.
- Garantir isolamento de auditoria entre empresas.
- Permitir visão consolidada apenas para usuários MASTER.
- Otimizar consultas através de índices específicos.

---

## 3. Modelagem de Dados

### Alteração da Tabela `audit_logs`

Adicionar a seguinte coluna:

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `company_id` | FK, Integer (Nullable) | Empresa responsável pelo evento registrado |

---

### Índice de Performance

Criar um índice composto para otimizar consultas por tenant.

```text
(company_id, created_at)
```

Esse índice será utilizado principalmente em consultas por período dentro do módulo de auditoria.

---

## 4. Refatoração do AuditService

O `AuditService` deverá integrar-se automaticamente ao `TenantContext`.

Durante a criação de qualquer registro de auditoria, o serviço deverá recuperar o tenant ativo e preencher automaticamente o campo:

```text
company_id
```

O desenvolvedor não deverá informar manualmente esse valor.

Fluxo esperado:

```text
TenantContext
        ↓
AuditService
        ↓
audit_logs.company_id
```

---

## 5. Visualização dos Logs

### Perfil ADMIN

Usuários ADMIN deverão visualizar apenas os registros pertencentes à empresa atualmente ativa.

O `AuditRepository` deverá herdar automaticamente o comportamento definido na **Phase 2**, aplicando o filtro:

```sql
WHERE company_id = :tenant
```

---

### Perfil MASTER

Usuários MASTER poderão consultar logs de qualquer empresa.

O repositório deverá disponibilizar métodos específicos para essa finalidade.

Exemplos:

```php
getGlobalLogs()
```

```php
getLogsByCompany(int $companyId)
```

Esses métodos deverão ignorar o filtro automático de tenant, respeitando apenas as permissões do usuário MASTER.

---

## 6. Regras de Negócio

### Associação Automática

Todo evento operacional deverá ser automaticamente associado ao tenant ativo através do `TenantContext`.

---

### Eventos Globais

Eventos relacionados à autenticação ou segurança que ocorram antes da definição do tenant deverão possuir:

```text
company_id = NULL
```

Exemplos:

- login;
- senha incorreta;
- conta bloqueada;
- tentativa de autenticação;
- recuperação de senha.

Esses registros representam eventos globais da plataforma.

---

### Segregação de Dados

O `AuditRepository` deverá impedir que usuários de uma empresa consultem registros pertencentes a outra.

Exemplo:

```text
Empresa A
        ↓
AuditRepository
        ↓
Retorna apenas logs da Empresa A
```

Tentativas de acesso cruzado entre tenants deverão ser bloqueadas.

---

## 7. Fluxo de Execução

### Etapa 1

O `TenantMiddleware` inicializa o `TenantContext`.

---

### Etapa 2

Uma operação da aplicação dispara um evento de auditoria.

---

### Etapa 3

O `AuditService` consulta automaticamente o `TenantContext`.

---

### Etapa 4

O registro é persistido contendo o `company_id` correspondente.

---

### Etapa 5

Durante consultas:

- ADMIN recebe apenas registros do tenant atual.
- MASTER pode consultar registros globais ou de qualquer empresa.

---

## 8. Regras de Segurança

### Isolamento

Nenhum usuário ADMIN poderá visualizar registros de auditoria pertencentes a outro tenant.

---

### Governança

Somente usuários MASTER poderão executar consultas globais ou selecionar empresas específicas para análise.

---

### Integridade

O `company_id` deverá ser preenchido automaticamente pelo sistema.

Não será permitido informar esse valor manualmente durante a criação dos registros de auditoria.

---

## 9. Critérios de Aceitação

- [ ] A tabela `audit_logs` possui a coluna `company_id`.
- [ ] Existe um índice composto `(company_id, created_at)`.
- [ ] O `AuditService` obtém automaticamente o tenant através do `TenantContext`.
- [ ] Desenvolvedores não precisam informar manualmente o `company_id`.
- [ ] Usuários ADMIN visualizam apenas logs da empresa ativa.
- [ ] Usuários MASTER possuem acesso global aos registros de auditoria.
- [ ] O `AuditRepository` disponibiliza métodos `getGlobalLogs()` e `getLogsByCompany()`.
- [ ] Eventos de autenticação anteriores ao carregamento do tenant são registrados com `company_id = NULL`.
- [ ] O sistema impede acesso cruzado entre tenants.
- [ ] Toda consulta de auditoria respeita o escopo de segurança definido pela arquitetura Multi-tenant.