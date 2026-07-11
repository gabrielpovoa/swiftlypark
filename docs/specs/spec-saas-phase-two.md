# Spec-SAAS-008-Phase-2: Repository Layer Isolation

## 1. Overview

Esta especificação define a segunda fase da arquitetura **Multi-tenant** do SwiftlyPark, implementando o isolamento automático de dados na camada de persistência.

O objetivo é garantir que todas as consultas realizadas pelos repositórios respeitem automaticamente o contexto da empresa (`TenantContext`), eliminando o risco de vazamento de informações entre tenants.

---

## 2. Objetivos

- Implementar isolamento automático na camada de repositórios.
- Centralizar a lógica de filtro por empresa.
- Eliminar duplicação de código nas consultas.
- Impedir acesso acidental a dados de outros tenants.
- Garantir uma arquitetura escalável e segura.

---

## 3. Arquitetura da Camada de Repositórios

### BaseRepository

Criar um repositório base responsável por compartilhar a lógica de isolamento entre todos os repositórios da aplicação.

O `BaseRepository` deverá receber uma instância do `TenantContext`.

Responsabilidades:

- recuperar o `company_id` ativo;
- aplicar filtros automáticos nas consultas;
- impedir consultas sem contexto válido.

---

### TenantContext

O `TenantContext`, implementado na Fase 1, será utilizado pelo `BaseRepository` para recuperar a empresa ativa da requisição.

Exemplo:

```php
$this->tenantContext->getCompanyId();
```

---

## 4. Isolamento Automático

Todos os métodos de leitura deverão aplicar automaticamente o filtro:

```sql
WHERE company_id = :company_id
```

O filtro deverá ser transparente para as camadas superiores da aplicação.

Exemplos de métodos protegidos:

- `find()`;
- `findById()`;
- `all()`;
- `where()`;
- `paginate()`;
- `search()`;
- demais consultas operacionais.

Nenhum Service deverá ser responsável por adicionar manualmente esse filtro.

---

## 5. Repositórios Afetados

Os seguintes repositórios deverão herdar do `BaseRepository`:

- `VagasRepository`;
- `TransacaoRepository`;
- `AuditLogRepository`;
- `FinanceRepository`;
- `RelatorioRepository`.

Outros repositórios operacionais deverão seguir o mesmo padrão.

---

## 6. Estratégia de Segurança

### TenantNotSetException

Caso o `TenantContext` não esteja inicializado, nenhuma consulta deverá ser executada.

O sistema deverá lançar uma exceção específica:

```text
TenantNotSetException
```

Essa abordagem evita comportamentos inseguros, como:

- retorno de dados de todas as empresas;
- consultas sem escopo;
- falhas silenciosas.

---

### Fail-Safe

A arquitetura deverá seguir o princípio **Fail-Safe**.

Na ausência de um contexto válido:

- nenhuma consulta será executada;
- nenhuma informação será retornada;
- a aplicação deverá interromper imediatamente o fluxo.

---

## 7. Fluxo de Execução

### Etapa 1

O `TenantMiddleware` inicializa o `TenantContext`.

---

### Etapa 2

O Service solicita dados ao repositório.

---

### Etapa 3

O `BaseRepository` consulta o `TenantContext`.

---

### Etapa 4

Caso exista um `company_id` válido, o filtro é aplicado automaticamente:

```sql
WHERE company_id = :company_id
```

---

### Etapa 5

A consulta é executada retornando apenas dados pertencentes ao tenant ativo.

Caso não exista contexto válido, uma `TenantNotSetException` deverá ser lançada.

---

## 8. Benefícios

A centralização do filtro no `BaseRepository` proporciona:

- isolamento automático de dados;
- redução de código duplicado;
- menor risco de erro humano;
- proteção contra **Cross-Tenant Data Leakage**;
- facilidade para expansão da arquitetura Multi-tenant.

---

## 9. Critérios de Aceitação

- [ ] Existe um `BaseRepository` compartilhado por todos os repositórios operacionais.
- [ ] O `BaseRepository` recebe uma instância do `TenantContext`.
- [ ] Todos os métodos de leitura aplicam automaticamente o filtro por `company_id`.
- [ ] Nenhum Service precisa adicionar manualmente filtros de tenant.
- [ ] `VagasRepository` herda do `BaseRepository`.
- [ ] `TransacaoRepository` herda do `BaseRepository`.
- [ ] `AuditLogRepository` herda do `BaseRepository`.
- [ ] `FinanceRepository` herda do `BaseRepository`.
- [ ] `RelatorioRepository` herda do `BaseRepository`.
- [ ] Consultas sem `TenantContext` válido lançam `TenantNotSetException`.
- [ ] Nenhuma consulta pode retornar dados de múltiplos tenants por erro de configuração.
- [ ] A arquitetura protege automaticamente contra **Cross-Tenant Data Leakage**.