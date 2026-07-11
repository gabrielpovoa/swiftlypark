# Spec-SAAS-008-Phase-5: UI/UX & Tenant Management

## 1. Overview

Esta especificação define a quinta fase da arquitetura **Multi-tenant** do SwiftlyPark, responsável pela implementação da interface adaptativa e pelo gerenciamento do contexto de tenant na experiência do usuário.

O objetivo é garantir que a interface reflita dinamicamente o papel do usuário e a empresa atualmente selecionada, mantendo o `TenantContext` sincronizado entre frontend e backend.

---

## 2. Objetivos

- Implementar troca dinâmica de tenant.
- Adaptar a interface conforme permissões e contexto.
- Manter o `TenantContext` persistente durante a sessão.
- Garantir atualização automática da aplicação após troca de empresa.
- Melhorar a experiência para usuários MASTER e administradores multiempresa.

---

## 3. Componentes da Solução

### 3.1 Tenant Switcher

O sistema deverá disponibilizar um componente de seleção de empresa no cabeçalho da aplicação.

#### Visibilidade

O componente será exibido apenas quando:

- o usuário possuir o papel `MASTER`; ou
- estiver vinculado a mais de uma empresa.

A quantidade de empresas deverá ser obtida através da tabela:

```text
company_user
```

---

#### Endpoint de Consulta

```http
GET /api/v1/user/tenants
```

Retorna todas as empresas vinculadas ao usuário autenticado.

Exemplo de resposta:

```json
[
  {
    "id": 1,
    "name": "Empresa A"
  },
  {
    "id": 2,
    "name": "Empresa B"
  }
]
```

---

#### Troca de Tenant

Ao selecionar uma empresa, o frontend deverá enviar:

```http
POST /api/v1/tenant/switch
```

Payload esperado:

```json
{
  "company_id": 2
}
```

---

## 4. Navigation & Sidebar

A navegação da aplicação deverá ser construída dinamicamente conforme o contexto do usuário.

### Perfil MASTER

Exibir módulos globais, como:

- Gestão de Plataforma;
- Gestão de Empresas;
- Gestão de Usuários;
- Faturamento Consolidado;
- Auditoria Global;
- Business Intelligence Global.

---

### Perfil ADMIN

Exibir apenas funcionalidades relacionadas ao tenant ativo.

Exemplos:

- Check-in;
- Check-out;
- Financeiro;
- Relatórios;
- Gestão Operacional da Empresa.

---

### Construção do Menu

O frontend deverá consumir as permissões retornadas pela API.

As permissões poderão ser obtidas:

durante o login;

ou através do endpoint:

```http
GET /api/v1/permissions/context
```

O menu deverá ser renderizado dinamicamente com base nesse contexto.

---

## 5. Gerenciamento de Estado

### Persistência do Tenant

O `TenantContext` deverá permanecer armazenado no backend.

A persistência poderá utilizar:

- Sessão;
- JWT;
- outro mecanismo de autenticação utilizado pela aplicação.

---

### Sincronização

Após qualquer alteração de tenant, o backend deverá atualizar imediatamente o contexto ativo da sessão.

---

## 6. Fluxo de Troca de Tenant

### Etapa 1

O usuário seleciona uma empresa no Tenant Switcher.

---

### Etapa 2

O frontend envia:

```http
POST /api/v1/tenant/switch
```

---

### Etapa 3

O backend valida se o usuário possui vínculo com a empresa solicitada.

Caso não exista vínculo:

```http
403 Forbidden
```

---

### Etapa 4

Caso a validação seja bem-sucedida, o backend atualiza o `TenantContext`.

Resposta esperada:

```http
200 OK
```

---

### Etapa 5

Após a confirmação da troca, o frontend deverá atualizar completamente a aplicação.

A atualização poderá ocorrer através de:

```javascript
window.location.reload()
```

ou

- reinicialização completa do estado da SPA;
- re-renderização dos componentes;
- invalidação do cache de consultas.

---

## 7. Regras de Negócio

### Visibilidade Condicional

Usuários sem múltiplas empresas vinculadas não deverão visualizar o Tenant Switcher.

---

### Segurança

A troca de empresa somente poderá ocorrer quando existir vínculo válido na tabela `company_user`.

---

### Atualização de Contexto

Após a troca do tenant:

- menus;
- permissões;
- consultas;
- dashboard;
- indicadores;

deverão refletir imediatamente a nova empresa selecionada.

---

## 8. Critérios de Aceitação

- [ ] Existe um componente **Tenant Switcher** no cabeçalho da aplicação.
- [ ] O componente é exibido apenas para usuários MASTER ou vinculados a múltiplas empresas.
- [ ] Existe o endpoint `GET /api/v1/user/tenants`.
- [ ] Existe o endpoint `POST /api/v1/tenant/switch`.
- [ ] O backend valida o vínculo entre usuário e empresa antes da troca.
- [ ] O `TenantContext` é atualizado após a troca de empresa.
- [ ] O frontend atualiza completamente a interface após a alteração do tenant.
- [ ] O menu é renderizado dinamicamente conforme as permissões do usuário.
- [ ] Usuários MASTER visualizam funcionalidades globais.
- [ ] Usuários ADMIN visualizam apenas funcionalidades da empresa ativa.
- [ ] O contexto do tenant permanece persistente durante toda a sessão do usuário.