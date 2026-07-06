# Spec-AUTH-005: Gestão Dinâmica de Usuários e Permissões (Painel Master)

## 1. Overview

Esta especificação define a implementação do **Painel Master de Gestão de Identidade**, responsável pelo gerenciamento do ciclo de vida dos usuários e pela atribuição dinâmica de permissões.

O objetivo é permitir que usuários com papel **MASTER** possam administrar acessos, revogar contas e conceder permissões adicionais sem alterar o papel principal do usuário, preservando a flexibilidade do modelo RBAC.

---

## 2. Objetivos

- Permitir administração centralizada de usuários.
- Implementar revogação de acesso utilizando Soft Delete.
- Permitir concessão de permissões extras além das herdadas pelo Role.
- Integrar todas as alterações à trilha de auditoria.
- Garantir proteção contra bloqueio administrativo do sistema.

---

## 3. Modelagem de Dados

### Tabela `user_permissions`

Responsável por armazenar permissões concedidas diretamente a usuários.

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `user_id` | FK, Integer | Usuário que receberá a permissão |
| `permission_id` | FK, Integer | Permissão concedida |
| `granted_at` | Timestamp | Data da concessão |

### Constraint

```text
UNIQUE(user_id, permission_id)
```

---

### Alteração da Tabela `users`

Adicionar o campo:

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `deleted_at` | Timestamp (Nullable) | Indica que o acesso do usuário foi revogado |

Quando este campo estiver preenchido, o usuário não poderá autenticar-se.

---

## 4. Funcionalidades do Painel Master

### 4.1 Listagem de Usuários

O painel deverá exibir uma tabela paginada contendo:

| Campo |
|--------|
| Nome |
| E-mail |
| Role Principal |
| Status |
| Ações |

---

### 4.2 Revogar Acesso

O MASTER poderá revogar o acesso de um usuário.

A operação deverá:

- preencher o campo `deleted_at`;
- impedir novos logins;
- invalidar sessões ativas;
- registrar a operação na auditoria.

Mensagem esperada:

```text
Acesso revogado com sucesso.
```

---

### 4.3 Gerenciar Permissões

O painel deverá permitir conceder permissões adicionais ao usuário sem alterar seu papel principal.

Essas permissões deverão ser persistidas em:

```text
user_permissions
```

Exemplo:

```text
Role: OPERATOR

Permissões Extras:

- financial.view
- report.export
```

---

## 5. Fluxo de Autorização

O `AuthorizationService` deverá validar permissões seguindo a seguinte ordem.

### Etapa 1 — Status do Usuário

Verificar se:

```text
deleted_at IS NULL
```

Caso contrário:

```text
Acesso negado.
```

---

### Etapa 2 — Permissões do Role

Verificar se o papel principal do usuário possui a permissão solicitada.

Se existir:

```text
Acesso permitido.
```

---

### Etapa 3 — Permissões Customizadas

Caso o Role não possua a permissão, consultar:

```text
user_permissions
```

Se existir uma permissão concedida diretamente ao usuário:

```text
Acesso permitido.
```

---

### Etapa 4 — Deny by Default

Caso nenhuma das validações anteriores conceda acesso:

```text
Acesso negado.
```

---

## 6. Regras de Negócio

### Revogação de Acesso

A revogação deverá utilizar Soft Delete.

O usuário permanecerá cadastrado para fins de histórico e auditoria.

---

### Permissões Extras

Permissões concedidas diretamente ao usuário deverão complementar as permissões herdadas pelo Role.

Nunca deverão substituir o papel principal.

---

### Invalidação de Sessões

Após a revogação do acesso, todas as sessões ou tokens ativos do usuário deverão ser invalidados imediatamente.

---

## 7. Regras de Segurança

### Imutabilidade do MASTER

O sistema deverá impedir:

- que um usuário MASTER revogue seu próprio acesso;
- que um usuário ADMIN altere permissões ou papéis de um usuário MASTER.

Além disso, caso exista apenas um único MASTER ativo, o sistema deverá impedir sua revogação para evitar bloqueio administrativo da aplicação.

---

### Auditoria

Toda alteração realizada através do Painel Master deverá gerar um registro em `audit_logs`.

Informações mínimas:

| Campo | Descrição |
|---------|-----------|
| `user_id` | MASTER responsável pela alteração |
| `target_user_id` | Usuário afetado |
| `permission_id` | Permissão concedida ou revogada |
| `action` | Operação executada |
| `created_at` | Data da alteração |

---

### Mensagem de Revogação

Usuários com acesso revogado não deverão receber mensagens genéricas de autenticação.

Mensagem esperada:

```text
Seu acesso foi revogado. Entre em contato com o administrador do sistema.
```

---

## 8. Requisitos Funcionais

### RF01

O sistema deverá listar todos os usuários em uma tabela paginada.

---

### RF02

O sistema deverá permitir revogar o acesso de usuários através de Soft Delete.

---

### RF03

O sistema deverá permitir conceder permissões adicionais sem alterar o Role principal.

---

### RF04

A revogação deverá invalidar imediatamente sessões ou tokens ativos do usuário.

---

## 9. Requisitos Não Funcionais

### RNF01

Toda alteração de permissão deverá gerar um registro de auditoria.

---

### RNF02

O sistema deverá impedir que o último MASTER ativo seja revogado.

---

### RNF03

Toda verificação de permissões customizadas deverá consultar o banco de dados considerando o status atual do usuário, impedindo que permissões armazenadas em sessão permitam acesso após uma revogação.

---

## 10. Critérios de Aceitação

- [ ] Existe uma tabela `user_permissions`.
- [ ] A tabela `users` possui o campo `deleted_at`.
- [ ] O Painel Master lista usuários de forma paginada.
- [ ] É possível revogar o acesso de usuários utilizando Soft Delete.
- [ ] A revogação invalida sessões ativas imediatamente.
- [ ] Usuários revogados não conseguem realizar login.
- [ ] O sistema retorna mensagem específica para usuários com acesso revogado.
- [ ] O Painel Master permite conceder permissões extras sem alterar o Role.
- [ ] O `AuthorizationService` valida permissões seguindo a ordem: status → Role → permissões customizadas → negação.
- [ ] Um MASTER não pode revogar seu próprio acesso.
- [ ] O último MASTER ativo não pode ser revogado.
- [ ] Usuários ADMIN não podem alterar contas MASTER.
- [ ] Toda alteração de permissões é registrada em `audit_logs`.

---

## 11. Decisões da implementação

O nome físico da tabela de usuários permanece `usuario`, respeitando o esquema existente. Ela recebe `deleted_at` e índice próprio.

`user_permissions` utiliza chave composta por usuário e permissão, além de registrar o usuário MASTER que realizou a concessão e o instante UTC. Permissões diretas complementam, mas não substituem, as permissões herdadas dos papéis.

A união efetiva é reconstruída pelo `RolePermissionResolver` a partir de:

- permissões dos papéis ativos;
- permissões diretas ativas;
- remoção de duplicidades por slug.

O `IdentityMiddleware` verifica o estado atual da conta e reconstrói essa união em toda requisição autenticada. Essa escolha privilegia revogação imediata e deny by default. A sessão mantém os dados para apresentação, mas não é a autoridade final.

## 12. Papel MASTER

O papel MASTER recebe explicitamente todas as permissões existentes no seed, inclusive:

- `identity.view`;
- `identity.manage`;
- `financial.view`.

Não existe bypass programático. O primeiro usuário histórico recebe MASTER durante a migração, preservando também seus papéis anteriores.

O painel impede o MASTER de:

- revogar a própria conta;
- alterar suas próprias permissões extras;
- revogar o último MASTER ativo.

ADMIN e demais papéis não recebem permissões de gestão de identidade e são bloqueados pela rota e pelo Service.

## 13. Atomicidade e auditoria

Revogação, sincronização de permissões extras e auditoria utilizam a mesma conexão e transação.

Os eventos são:

- `ACCESS_REVOKED`;
- `USER_PERMISSIONS_UPDATED`.

O payload registra ator, usuário alvo, e-mail alvo, permissões adicionadas, permissões removidas, IP, `request_id` e timestamp UTC. Falha na auditoria reverte a alteração administrativa.

## 14. Interface

O painel `/identity` é exibido apenas quando `AuthorizationService::can('identity.view')` retorna verdadeiro.

A listagem é paginada e apresenta:

- nome e e-mail;
- papéis;
- estado ativo ou revogado;
- permissões extras;
- ações permitidas.

Formulários mutáveis exigem CSRF. A própria conta do MASTER aparece bloqueada para ações administrativas irreversíveis.

O Sidebar existente recebe o item “Usuários” pelo mesmo `NavigationService` que filtra as demais funcionalidades. O `RoleBadge` continua usando os metadados resolvidos do papel principal.

## 15. Comportamento após revogação

Na próxima requisição do usuário revogado:

1. o Middleware consulta `usuario.deleted_at`;
2. a sessão local é destruída;
3. nenhuma permissão é avaliada;
4. o usuário é redirecionado para o login;
5. a mensagem específica de acesso revogado é apresentada.

Novas tentativas de login com credenciais válidas também exibem essa mensagem e não criam sessão autenticada.
