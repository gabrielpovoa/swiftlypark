# Spec-SAAS-008-Phase-4: Admin Onboarding & Access Governance

## 1. Overview

Esta especificação define a quarta fase da arquitetura **Multi-tenant** do SwiftlyPark, centralizando completamente o processo de criação de empresas e usuários.

O objetivo é eliminar o cadastro público da aplicação, tornando o **MASTER** o único responsável pelo provisionamento de novos tenants, usuários e vínculos de acesso, fortalecendo a governança da plataforma.

---

## 2. Objetivos

- Remover o cadastro público de usuários.
- Centralizar o provisionamento de empresas e usuários.
- Garantir que todo usuário esteja vinculado a uma empresa.
- Controlar a atribuição de papéis através do painel administrativo.
- Reforçar a segurança do processo de onboarding.

---

## 3. Fluxo de Identidade

### Remoção do Cadastro Público

Todas as rotas e endpoints relacionados ao autoatendimento deverão ser removidos.

Exemplo:

```text
/CreateAcc
```

O sistema não deverá permitir criação espontânea de contas.

---

### Provisionamento de Empresas

Criar o endpoint:

```http
POST /admin/companies/create
```

Acesso permitido exclusivamente para usuários com papel **MASTER**.

O endpoint será responsável por criar novos tenants da plataforma.

---

### Provisionamento de Usuários

Criar o endpoint:

```http
POST /admin/users/create
```

A criação de usuários deverá obrigatoriamente informar:

- `company_id`;
- `role_id`.

Após a criação, o vínculo deverá ser persistido na tabela `company_user`.

---

## 4. Regras de Vínculo

Todo usuário criado deverá estar associado a uma empresa existente.

Fluxo esperado:

```text
Usuário
        ↓
Empresa
        ↓
Role
        ↓
company_user
```

Não será permitido criar usuários sem empresa vinculada.

---

## 5. Regras de Segurança

### Bloqueio das Rotas Antigas

Qualquer tentativa de acesso às antigas rotas de cadastro deverá retornar:

```http
404 Not Found
```

O objetivo é impedir que usuários descubram endpoints descontinuados.

---

### Defesa em Profundidade

Além da remoção das rotas, o controlador responsável pelo cadastro público deverá ser removido da aplicação.

Exemplo:

```text
CreateAccController
```

Essa medida elimina a possibilidade de utilização acidental ou indevida do fluxo antigo.

---

### Integridade de Papéis

Durante a criação de usuários, o sistema deverá validar a hierarquia de privilégios.

Um usuário MASTER não poderá atribuir permissões ou papéis superiores aos que possui.

Apenas um **SUPER-ADMIN** poderá conceder privilégios equivalentes ou superiores aos seus.

Essa validação deverá ocorrer obrigatoriamente no backend.

---

## 6. Fluxo de Execução

### Etapa 1

O usuário MASTER acessa o painel administrativo.

---

### Etapa 2

O MASTER cria uma nova empresa através do endpoint:

```http
POST /admin/companies/create
```

---

### Etapa 3

O MASTER cria um usuário utilizando:

```http
POST /admin/users/create
```

Informando:

- empresa;
- papel;
- dados cadastrais.

---

### Etapa 4

O sistema cria automaticamente o vínculo na tabela:

```text
company_user
```

---

### Etapa 5

O usuário passa a possuir acesso apenas ao tenant ao qual foi vinculado.

---

## 7. Regras de Governança

### Provisionamento Centralizado

A criação de empresas e usuários deverá ocorrer exclusivamente pelo painel administrativo.

Não haverá mecanismo de autoatendimento para criação de contas.

---

### Controle de Acesso

Somente usuários MASTER poderão:

- criar empresas;
- criar administradores;
- criar operadores;
- vincular usuários a empresas.

---

### Hierarquia de Privilégios

O sistema deverá impedir escalonamento indevido de privilégios.

Nenhum usuário poderá conceder permissões superiores às que possui, salvo quando possuir perfil **SUPER-ADMIN**.

---

## 8. Critérios de Aceitação

- [ ] As rotas públicas de cadastro foram removidas.
- [ ] Requisições para `/CreateAcc` retornam HTTP `404 Not Found`.
- [ ] O `CreateAccController` foi removido da aplicação.
- [ ] Existe o endpoint `POST /admin/companies/create`.
- [ ] Existe o endpoint `POST /admin/users/create`.
- [ ] Apenas usuários MASTER podem criar empresas.
- [ ] Todo usuário criado é vinculado a uma empresa através da tabela `company_user`.
- [ ] Não é possível criar usuários sem `company_id`.
- [ ] Não é possível criar usuários sem `role_id`.
- [ ] O backend valida a hierarquia de privilégios durante a atribuição de papéis.
- [ ] Apenas usuários SUPER-ADMIN podem conceder privilégios equivalentes ou superiores aos próprios.