# Spec-AUTH-003: Controle de Acesso Baseado em Papéis (RBAC)

## 1. Overview

Esta especificação define a implementação do modelo **RBAC (Role-Based Access Control)** para gerenciamento de autorização da aplicação.

O objetivo é centralizar o controle de permissões através de papéis (Roles), substituindo validações individuais por uma arquitetura escalável, segura e desacoplada.

A autorização passará a seguir a cadeia:

```text
Usuário → Papéis (Roles) → Permissões (Permissions)
```

---

## 2. Objetivos

- Centralizar a gestão de permissões através de papéis.
- Permitir que um usuário possua um ou mais papéis.
- Permitir que cada papel possua múltiplas permissões.
- Reduzir consultas repetitivas utilizando cache de permissões.
- Integrar o RBAC ao `AuthorizationService`.
- Manter compatibilidade com a camada de auditoria.

---

## 3. Modelagem de Dados

### Tabela `roles`

Armazena todos os papéis disponíveis no sistema.

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `id` | PK, Integer | Identificador do papel |
| `name` | String | Nome amigável do papel |
| `slug` | String | Identificador único |
| `description` | Text | Descrição do papel |
| `is_system` | Boolean | Indica se o papel pertence ao núcleo do sistema |

---

### Tabela `permissions`

Catálogo contendo todas as permissões disponíveis na aplicação.

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `id` | PK, Integer | Identificador da permissão |
| `name` | String | Nome da permissão |
| `slug` | String | Identificador único da permissão |

---

### Tabela `role_permissions`

Relaciona papéis às permissões.

| Coluna | Tipo |
|---------|------|
| `role_id` | FK |
| `permission_id` | FK |

#### Constraint

```text
UNIQUE(role_id, permission_id)
```

---

### Tabela `user_roles`

Relaciona usuários aos papéis.

| Coluna | Tipo |
|---------|------|
| `user_id` | FK |
| `role_id` | FK |

#### Constraint

```text
UNIQUE(user_id, role_id)
```

---

## 4. Arquitetura

### 4.1 AuthorizationService

O `AuthorizationService` continuará sendo responsável por validar permissões, porém deixará de utilizar uma lista simples carregada na sessão.

As permissões deverão ser resolvidas automaticamente através dos papéis atribuídos ao usuário.

Exemplo:

```php
$auth->can('vehicle.checkout');
```

ou

```php
$auth->check('audit.view');
```

---

### 4.2 Service Provider de Acesso

Durante o processo de autenticação, um componente específico deverá resolver todas as permissões do usuário.

Fluxo:

```text
Usuário
    ↓
Papéis
    ↓
Permissões
    ↓
Sessão / Cache
```

Após a autenticação, todas as permissões deverão permanecer disponíveis em memória durante a sessão do usuário.

---

### 4.3 Metadata Resolver

As permissões exigidas por cada rota deverão ser definidas através de metadados.

Exemplo:

```php
Route::post(
    '/vehicle/checkin',
    CheckinController::class,
    [
        'permission' => 'vehicle.checkin'
    ]
);
```

O Middleware de autorização será responsável por interpretar esse metadado.

---

## 5. Fluxo de Autorização

### Etapa 1 — Autenticação

Após o login, o sistema resolve:

```text
User
    ↓
Roles
    ↓
Permissions
```

---

### Etapa 2 — Construção da Sessão

As permissões resultantes deverão ser carregadas na sessão ou cache.

Exemplo:

```php
[
    'vehicle.checkin',
    'vehicle.checkout',
    'report.view',
    'audit.view'
]
```

---

### Etapa 3 — Validação

Quando uma rota protegida for acessada:

1. O Middleware identifica a permissão exigida.
2. O `AuthorizationService` consulta a coleção carregada.
3. Caso exista, o fluxo continua.
4. Caso contrário, uma `ForbiddenException` deverá ser lançada.

---

## 6. Múltiplos Papéis

Um usuário poderá possuir mais de um papel.

Nesse cenário será utilizada a estratégia:

```text
Union of Permissions
```

Ou seja:

```text
Role A

- vehicle.checkin
- vehicle.checkout

Role B

- report.view
- audit.view
```

Resultado:

```text
vehicle.checkin
vehicle.checkout
report.view
audit.view
```

O usuário possuirá a união de todas as permissões.

---

## 7. Regras de Segurança

### Deny by Default

Caso uma permissão não esteja associada ao papel do usuário, o acesso deverá ser negado automaticamente.

---

### Menor Privilégio

Cada papel deverá possuir apenas as permissões estritamente necessárias para desempenhar sua função.

---

### Auditoria

A permissão:

```text
audit.view
```

deverá ser exclusiva dos papéis:

- ADMIN
- AUDITOR

Usuários do papel:

```text
OPERATOR
```

não deverão conseguir visualizar informações de auditoria.

---

### Papéis do Sistema

Papéis estruturais deverão possuir:

```text
is_system = true
```

Exemplos:

- ADMIN
- OPERATOR
- AUDITOR

Esses registros não deverão ser removidos ou alterados livremente pela aplicação.

---

## 8. Performance

Para reduzir consultas ao banco de dados:

- as permissões deverão ser resolvidas apenas durante o login;
- deverão permanecer armazenadas em sessão ou cache;
- somente uma nova autenticação ou atualização dos papéis deverá reconstruir a coleção de permissões.

---

## 9. Critérios de Aceitação

- [ ] Existe uma tabela `roles`.
- [ ] Existe uma tabela `permissions`.
- [ ] Existe uma tabela `role_permissions`.
- [ ] Existe uma tabela `user_roles`.
- [ ] Usuários podem possuir múltiplos papéis.
- [ ] Papéis podem possuir múltiplas permissões.
- [ ] O `AuthorizationService` resolve permissões através dos papéis do usuário.
- [ ] As permissões são carregadas durante o login.
- [ ] O conjunto de permissões permanece armazenado em sessão ou cache.
- [ ] Usuários com múltiplos papéis recebem a união das permissões.
- [ ] O sistema segue o princípio **Deny by Default**.
- [ ] Apenas papéis autorizados possuem acesso à permissão `audit.view`.
- [ ] Papéis do sistema possuem a flag `is_system`.
- [ ] As rotas protegidas definem explicitamente a permissão necessária.

---

## 10. Diagnóstico e decisão arquitetural

O SwiftlyPark já possui:

- identidade imutável por requisição;
- coleção simples de permissões no `RequestIdentity`;
- `AuthorizationService` com comportamento fail-closed;
- `AuthorizeMiddleware`;
- auditoria independente de tentativas negadas.

A Fase 3 substitui a origem manual dessa coleção por um resolvedor RBAC. O `AuthorizationService` continuará verificando uma coleção pronta durante a requisição; ele não executará consultas de papéis a cada chamada. A resolução de papéis e permissões pertence ao login e ao mecanismo de renovação do contexto.

Essa separação evita que autorização, banco, HTTP e renderização da Sidebar sejam concentrados em uma única classe.

## 11. Modelo de dados definitivo

### 11.1 `roles`

| Coluna | Tipo proposto | Regra |
|---|---|---|
| `id` | BIGINT UNSIGNED | Chave primária |
| `slug` | VARCHAR(80) | Único, imutável e usado internamente |
| `name` | VARCHAR(120) | Nome administrativo |
| `label` | VARCHAR(80) | Rótulo seguro para a interface |
| `icon_slug` | VARCHAR(80) | Identificador permitido do Lucide |
| `description` | VARCHAR(255), anulável | Descrição administrativa |
| `display_priority` | SMALLINT | Define o papel principal de exibição |
| `is_system` | BOOLEAN | Protege papéis estruturais |
| `is_active` | BOOLEAN | Permite desativação sem exclusão |
| `created_at` | DATETIME(6) | UTC |
| `updated_at` | DATETIME(6) | UTC |

`slug` utiliza letras minúsculas, números, ponto, hífen ou sublinhado. `icon_slug` não é HTML; ele deve pertencer a uma lista de ícones aprovada no backend.

### 11.2 `permissions`

| Coluna | Tipo proposto | Regra |
|---|---|---|
| `id` | BIGINT UNSIGNED | Chave primária |
| `slug` | VARCHAR(120) | Único e utilizado por `can` |
| `name` | VARCHAR(120) | Nome administrativo |
| `description` | VARCHAR(255), anulável | Finalidade da permissão |
| `is_active` | BOOLEAN | Desativa concessões sem excluir histórico |
| `created_at` | DATETIME(6) | UTC |
| `updated_at` | DATETIME(6) | UTC |

Não haverá permissão curinga na primeira versão. Uma ação somente é permitida quando seu slug exato estiver no conjunto efetivo.

### 11.3 `role_permissions`

Embora o requisito mencione `role_permission`, o nome plural `role_permissions` será mantido por consistência com `user_roles`.

| Coluna | Tipo proposto | Regra |
|---|---|---|
| `role_id` | BIGINT UNSIGNED | FK para `roles` |
| `permission_id` | BIGINT UNSIGNED | FK para `permissions` |
| `created_at` | DATETIME(6) | UTC |
| `created_by` | INT | Usuário que concedeu a permissão |

A chave primária composta por `role_id` e `permission_id` impede duplicidade. Exclusões de papéis não sistêmicos podem remover vínculos em cascata; exclusões de permissões de sistema devem ser restritas.

### 11.4 `user_roles`

| Coluna | Tipo proposto | Regra |
|---|---|---|
| `user_id` | INT | FK para `usuario` |
| `role_id` | BIGINT UNSIGNED | FK para `roles` |
| `created_at` | DATETIME(6) | UTC |
| `created_by` | INT | Usuário que atribuiu o papel |

A chave primária composta por `user_id` e `role_id` impede atribuição repetida. Papéis inativos permanecem no histórico, mas não participam da autorização.

### 11.5 Integridade e índices

Serão necessários índices para:

- slug único de papéis;
- slug único de permissões;
- busca de papéis por usuário;
- busca de permissões por papel;
- busca reversa de usuários afetados por uma alteração de papel.

Todas as relações utilizam chaves estrangeiras. Papéis de sistema não podem ser removidos pela aplicação. Mudanças de atribuição e concessão também devem ser auditadas quando a interface administrativa for implementada.

## 12. União de múltiplos papéis

O `RbacRepository` executa uma consulta que retorna somente papéis e permissões ativos. O `RolePermissionResolver` agrupa o resultado e produz:

- coleção de papéis do usuário;
- conjunto deduplicado de slugs de permissão;
- papel principal de exibição;
- versão do contexto de autorização.

A permissão efetiva é a união dos papéis. Não existe precedência de negação na primeira versão: uma concessão explícita em qualquer papel ativo autoriza a ação.

O papel principal serve exclusivamente para metadados visuais e auditoria resumida. Ele é escolhido pela menor `display_priority`; empates são resolvidos pelo slug, garantindo resultado determinístico. Essa escolha não limita a união de permissões.

Exemplo conceitual: um usuário com OPERATOR e AUDITOR recebe permissões dos dois papéis, mas exibe apenas o badge definido pela prioridade. O contexto ainda preserva todos os slugs para investigação.

## 13. Sessão, cache e invalidação

### 13.1 Conteúdo da sessão

Após autenticação, o servidor armazena:

- ID e e-mail do usuário;
- slugs dos papéis ativos;
- slugs das permissões efetivas;
- metadados seguros do papel principal;
- versão do contexto;
- instante UTC em que o contexto foi resolvido.

Objetos de domínio e descrições administrativas não devem ser serializados na sessão.

### 13.2 Cache

Na primeira versão, a sessão PHP é suficiente. Se houver múltiplas instâncias, o contexto deverá migrar para Redis ou outro armazenamento compartilhado, usando uma chave baseada no ID do usuário e na versão de autorização.

O cache armazena o resultado da resolução, nunca substitui o banco como fonte de verdade.

### 13.3 Invalidação

Carregar permissões apenas no login cria uma janela de acesso obsoleto. Por isso, o contexto terá uma versão. Mudanças em `user_roles`, `role_permissions`, ativação de papel ou ativação de permissão incrementam a versão dos usuários afetados.

O `IdentityMiddleware` compara periodicamente a versão da sessão com a versão vigente. Quando houver divergência, reconstrói o contexto ou encerra a sessão. Revogações críticas devem invalidar imediatamente as sessões afetadas.

## 14. Interfaces de backend

### 14.1 `Role`

Objeto imutável com ID, slug, label, ícone, prioridade e indicação de papel de sistema. Não contém métodos de persistência.

### 14.2 `Permission`

Objeto imutável que representa o slug e seus metadados administrativos. A autorização utiliza apenas o slug.

### 14.3 `RbacRepositoryInterface`

Contrato de leitura responsável por carregar, em uma única unidade lógica, papéis e permissões ativos de um usuário. A implementação conhece SQL; o restante da aplicação depende da interface.

### 14.4 `RolePermissionResolverInterface`

Recebe um usuário e devolve um `ResolvedAuthorizationContext`. Ele deduplica permissões, seleciona o papel principal e calcula a versão. Não acessa sessão.

### 14.5 `ResolvedAuthorizationContext`

Objeto imutável com:

- todos os slugs de papéis;
- conjunto efetivo de permissões;
- `RoleMetadata` principal;
- versão;
- instante da resolução.

### 14.6 `AuthorizationContextStoreInterface`

Abstrai escrita, leitura e remoção do contexto na sessão ou cache. A implementação inicial utiliza a sessão PHP. Uma futura implementação Redis não altera o resolvedor nem o serviço de autorização.

### 14.7 `AuthorizationService`

Continua recebendo um contexto já resolvido e oferece:

- consulta booleana por permissão;
- verificação estrita que lança `ForbiddenException`;
- acesso somente leitura aos papéis efetivos quando necessário para escopo;
- metadados seguros para apresentação por meio de um colaborador próprio.

Ele não consulta banco, não monta menus e não grava auditoria.

### 14.8 `RoleMetadataProviderInterface`

Converte o papel principal em `RoleMetadata`, contendo somente:

- `role`;
- `label`;
- `icon`.

O provider valida o ícone contra uma allowlist. Não retorna permissões, IDs internos, descrição administrativa ou regras de acesso.

## 15. Auditoria de acesso negado

O `AuthorizeMiddleware` continua capturando `ForbiddenException` e solicita ao `SecurityAuditService` o registro independente da operação de negócio.

O evento mínimo contém:

- `user_id`;
- `role_slug` do papel principal;
- `role_slugs` efetivos para contexto de múltiplos papéis;
- `requested_permission`;
- padrão da rota;
- endereço IP;
- `request_id`;
- instante UTC;
- motivo normalizado.

O campo `role_slug` é uma fotografia histórica. O `SecurityAuditService` não consulta o papel posteriormente, pois atribuições podem mudar.

A resposta HTTP contém apenas status 403 e mensagem genérica. Papel, permissão ausente, SQL e conteúdo da sessão não são enviados ao cliente.

Falha na auditoria mantém a requisição negada. A política operacional deverá decidir entre retornar erro genérico de infraestrutura ou preservar o 403 e emitir alerta crítico; em nenhuma hipótese a falha do log concede acesso.

## 16. Metadados seguros para a interface

### 16.1 Papel da UI

A interface pode melhorar usabilidade, mas não participa da decisão de segurança. Ocultar um link não autoriza nem proíbe uma rota.

O backend fornece um `SidebarViewModel` contendo:

- nome curto do usuário;
- URL segura da foto;
- `RoleMetadata`;
- itens de navegação já filtrados.

A View não lê permissões diretamente da sessão e não contém condicionais baseadas em slugs de papel.

### 16.2 RoleMetadata

O objeto visual possui o formato lógico:

| Campo | Exemplo | Uso |
|---|---|---|
| `role` | `admin` | Identificador estável para estilo |
| `label` | `Administrador` | Texto exibido |
| `icon` | `shield-check` | Ícone Lucide permitido |

Esse objeto não expõe o conjunto de permissões. O frontend não precisa conhecer por que um item foi autorizado.

### 16.3 `RoleBadge`

Como o projeto usa Views PHP, o componente será um partial reutilizável, por exemplo em `app/Views/partials/role-badge.php`.

O partial recebe um `RoleMetadata` já validado e apenas renderiza label e ícone escapados. Ele não acessa sessão, Repository ou `AuthorizationService`.

Quando não houver papel ativo, o backend fornece um estado neutro sem privilégios, como “Sem papel”. Isso não cria permissão implícita.

## 17. Sidebar e segregação de visibilidade

O catálogo de navegação pertence ao backend e associa cada item a uma permissão:

| Item | Permissão |
|---|---|
| Gerenciar vagas | `vehicle.view` |
| Registrar entrada | `vehicle.checkin` |
| Relatórios | `report.view` |
| Auditoria | `audit.view` |

O `NavigationService` recebe o catálogo e `AuthorizationService`, filtra os itens e devolve objetos de apresentação. A Sidebar renderiza somente o resultado.

Para o papel AUDITOR:

- `audit.view` permite receber o item de auditoria;
- a rota de auditoria também exige `audit.view`;
- o Service de consulta também verifica `audit.view`.

Para o papel OPERATOR:

- o item de auditoria não é incluído no `SidebarViewModel`;
- nenhum HTML ou URL de auditoria é enviado na Sidebar;
- acesso manual à URL resulta em 403;
- a tentativa negada é auditada.

O item oculto é uma medida de segregação visual. Middleware e guard no Service são as barreiras de segurança.

## 18. Deny by Default

O sistema nega acesso quando:

- o usuário não possui papel ativo;
- a permissão não está presente na união;
- a permissão ou papel está inativo;
- o contexto está ausente, inválido ou vencido;
- a versão do contexto está obsoleta e não pode ser renovada;
- uma rota sensível não declara permissão;
- o identificador da permissão é malformado.

ADMIN não terá bypass programático. O papel recebe explicitamente seu conjunto de permissões no banco. Isso mantém auditoria, testes e comportamento previsíveis.

## 19. Hierarquia PSR-4 planejada

- `app/Authorization/Entities`: `Role` e `Permission`;
- `app/Authorization/DTO`: `ResolvedAuthorizationContext` e `RoleMetadata`;
- `app/Authorization/Contracts`: interfaces de Repository, resolver, store e metadata provider;
- `app/Authorization/Repositories`: implementação SQL do RBAC;
- `app/Authorization/Services`: resolvedor, provider de metadados e navegação;
- `app/Services`: serviço de autorização usado pela aplicação;
- `app/Middleware`: identidade e autorização HTTP;
- `app/ViewModels`: `SidebarViewModel` e itens de navegação;
- `app/Views/partials`: componente visual `RoleBadge`;
- `database/migrations`: esquema, constraints, índices e dados iniciais.

Os namespaces seguem diretamente o mapeamento PSR-4 `App\\` para `app/`.

## 20. Fluxo completo

1. O usuário envia suas credenciais.
2. O login autentica a senha.
3. O `RolePermissionResolver` consulta o `RbacRepository`.
4. Papéis e permissões ativos são deduplicados.
5. O papel principal de exibição é selecionado.
6. O contexto resolvido é armazenado na sessão ou cache.
7. Em cada requisição, o `IdentityMiddleware` valida identidade e versão.
8. O `AuthorizeMiddleware` lê a permissão declarada pela rota.
9. O `AuthorizationService` aplica comparação explícita.
10. Se negado, o evento é auditado e a aplicação retorna 403.
11. Se permitido, o Controller chama o Service de negócio.
12. O Service repete a verificação nas operações críticas.
13. Para a UI, o `NavigationService` produz somente itens permitidos.
14. O `RoleMetadataProvider` fornece label e ícone seguros.
15. O `RoleBadge` e a Sidebar apenas renderizam o ViewModel.

## 21. Estratégia de implementação

1. Criar interfaces, objetos imutáveis e catálogo de permissões.
2. Criar migração RBAC e seed dos papéis estruturais.
3. Implementar Repository e resolvedor da união.
4. Implementar store de sessão e política de versão.
5. Integrar resolução ao login e limpeza ao logout.
6. Ampliar `RequestIdentity` com papéis e metadata.
7. Conectar metadados de permissão às rotas.
8. Introduzir guards nos Services críticos.
9. Criar `NavigationService`, `SidebarViewModel` e `RoleBadge`.
10. Remover leitura direta de sessão da Sidebar.
11. Ampliar auditoria negada com fotografia dos papéis.
12. Testar matriz ADMIN, OPERATOR, AUDITOR e múltiplos papéis.

## 22. Matriz inicial de papéis

| Permissão | ADMIN | OPERATOR | AUDITOR |
|---|---:|---:|---:|
| `vehicle.view` | Sim | Sim | Não |
| `vehicle.checkin` | Sim | Sim | Não |
| `vehicle.checkout` | Sim | Sim | Não |
| `vacancy.create` | Sim | Não | Não |
| `report.view` | Sim | Não | Sim |
| `audit.view` | Sim | Não | Sim |
| `profile.password.update` | Sim | Sim | Sim |
| `profile.photo.update` | Sim | Sim | Sim |

Essa matriz é dado inicial de governança, não lógica fixa do `AuthorizationService`. Mudanças futuras ocorrem nos vínculos de papéis e permissões.

## 23. Testes exigidos antes da ativação

- união e deduplicação de permissões para múltiplos papéis;
- usuário sem papel e papel inativo;
- permissão inativa;
- ausência de bypass para ADMIN;
- invalidação de sessão após revogação;
- rota sensível sem metadata;
- Middleware e guard interno negando a mesma ação;
- auditoria com papel, permissão, IP e `request_id`;
- Sidebar do OPERATOR sem auditoria;
- tentativa manual do OPERATOR retornando 403;
- Sidebar do AUDITOR com auditoria;
- ícone fora da allowlist substituído por fallback;
- saída escapada no `RoleBadge`;
- contexto obsoleto ou corrompido seguindo deny by default.
