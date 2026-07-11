# Spec-SAAS-008-Phase-1: Foundation & Tenancy Context

## 1. Overview

Esta especificação define a primeira fase da implementação da arquitetura **Multi-tenant** do SwiftlyPark.

O objetivo é estruturar o banco de dados para suportar múltiplas empresas (tenants) e implementar a infraestrutura responsável por identificar, validar e disponibilizar o contexto da empresa durante o ciclo de vida de cada requisição.

---

## 2. Objetivos

- Estruturar o banco de dados para suporte Multi-tenant.
- Implementar o contexto da empresa (`TenantContext`).
- Criar o middleware responsável pelo isolamento entre empresas.
- Garantir que usuários acessem apenas empresas às quais estejam vinculados.
- Preparar a arquitetura para as próximas fases do modelo SaaS.

---

## 3. Modelagem de Dados

### Tabela `companies`

Representa cada empresa (tenant) da plataforma.

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `id` | UUID ou BigInt (PK) | Identificador único da empresa |
| `name` | VARCHAR | Nome da empresa |
| `slug` | VARCHAR (Unique) | Identificador único utilizado em URLs amigáveis |
| `created_at` | Timestamp | Data de criação |
| `updated_at` | Timestamp | Data da última atualização |

---

### Tabela `company_user`

Responsável por relacionar usuários, empresas e papéis.

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `id` | PK | Identificador do vínculo |
| `company_id` | FK | Empresa vinculada |
| `user_id` | FK | Usuário |
| `role_id` | FK | Papel exercido pelo usuário naquela empresa |

Essa estrutura permite que um mesmo usuário possua papéis distintos em diferentes empresas.

---

### Alteração das Tabelas Operacionais

As seguintes entidades deverão receber a coluna:

| Coluna | Tipo |
|---------|------|
| `company_id` | FK |

Aplicar em:

- `checkins`;
- `veiculos`;
- `financeiro`;
- `audit_logs`.

---

### Migração de Dados Legados

Durante a migração, todos os registros existentes deverão ser associados automaticamente a uma empresa padrão.

Exemplo:

```text
Default Company
```

Essa estratégia garante compatibilidade com os dados já existentes na aplicação.

---

## 4. Infraestrutura de Contexto

### TenantContext

Implementar um serviço responsável por armazenar a empresa ativa durante toda a requisição.

O componente deverá funcionar como um Singleton ou serviço registrado no container da aplicação.

Responsabilidades:

- armazenar a empresa ativa;
- disponibilizar o `company_id` para Services e Repositories;
- impedir vazamento de dados entre tenants.

Interface sugerida:

```php
setCompany(Company $company): void
```

```php
getCompanyId(): int
```

---

### TenantMiddleware

O middleware deverá ser responsável por inicializar o contexto da empresa.

Fluxo:

1. Interceptar a requisição.
2. Identificar a empresa.
3. Validar o vínculo do usuário.
4. Popular o `TenantContext`.
5. Liberar ou bloquear a requisição.

---

## 5. Identificação da Empresa

O middleware deverá permitir identificar o tenant através de uma das seguintes estratégias:

- Subdomínio;
- Header HTTP;
- Sessão do usuário.

A estratégia utilizada poderá variar conforme a configuração da aplicação.

---

## 6. Validação de Acesso

Após identificar a empresa, o middleware deverá verificar se o usuário autenticado possui vínculo na tabela:

```text
company_user
```

Caso exista vínculo válido:

```text
Requisição autorizada.
```

Caso contrário:

```http
403 Forbidden
```

---

## 7. Fluxo de Execução

### Etapa 1

O `TenantMiddleware` intercepta a requisição.

---

### Etapa 2

O middleware identifica a empresa utilizando:

- subdomínio;
- header;
- sessão.

---

### Etapa 3

O vínculo entre usuário e empresa é validado na tabela `company_user`.

---

### Etapa 4

O `TenantContext` é inicializado com a empresa correspondente.

---

### Etapa 5

A requisição segue para a camada de aplicação.

Caso a validação falhe, o middleware retorna:

```http
403 Forbidden
```

---

## 8. Critérios de Aceitação

- [ ] Existe uma tabela `companies`.
- [ ] Existe uma tabela `company_user`.
- [ ] Todas as tabelas operacionais possuem a coluna `company_id`.
- [ ] Os dados legados são migrados para uma empresa padrão.
- [ ] Existe um `TenantContext` responsável pelo contexto da empresa.
- [ ] O `TenantContext` disponibiliza os métodos `setCompany()` e `getCompanyId()`.
- [ ] O `TenantMiddleware` identifica corretamente o tenant da requisição.
- [ ] O middleware valida o vínculo entre usuário e empresa.
- [ ] O `TenantContext` é populado antes da execução da lógica da aplicação.
- [ ] Usuários sem vínculo válido recebem HTTP `403 Forbidden`.