# Spec-AUTH-004: Implementação da Camada de Autorização Action-Based

## 1. Overview

Esta especificação define a implementação de uma camada de autorização baseada em ações, migrando a validação simples de autenticação para um modelo onde cada usuário precisa possuir permissão explícita para executar uma operação específica.

O objetivo é aumentar a segurança da aplicação, garantir controle granular de acesso e integrar tentativas de acesso negado à trilha de auditoria.

---

## 2. Objetivo

Introduzir o conceito de **Permissões de Ação**, permitindo validações como:

```php
$auth->can('vehicle.checkout');
```

A autorização deverá responder à seguinte pergunta:

```text
O usuário autenticado tem permissão para executar esta ação?
```

---

## 3. Arquitetura de Autorização

### 3.1 IdentityMiddleware

O `IdentityMiddleware` será estendido para carregar o contexto completo do usuário autenticado.

Além de dados básicos, como nome e e-mail, a sessão deverá conter uma lista de permissões.

Exemplo:

```php
[
    'user_id' => 1,
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'permissions' => [
        'vehicle.checkin',
        'vehicle.checkout',
        'report.view'
    ]
]
```

---

### 3.2 AuthorizationService

O `AuthorizationService` será responsável por centralizar as regras de autorização.

Responsabilidades:

- consultar permissões carregadas na sessão;
- validar se o usuário pode executar determinada ação;
- lançar exceções quando o acesso for negado;
- apoiar verificações de propriedade do recurso quando necessário.

Exemplo de uso:

```php
$auth->can('vehicle.checkout');
```

Retorno esperado:

```php
true // permitido
false // negado
```

Também poderá existir um método mais restritivo:

```php
$auth->check('vehicle.checkout');
```

Comportamento esperado:

- se permitido, continua o fluxo;
- se negado, lança `ForbiddenException`.

---

### 3.3 RBAC Middleware

O middleware de autorização deverá interceptar a requisição antes de chegar ao Controller.

Ele deverá:

1. Ler a permissão exigida pela rota.
2. Consultar o `AuthorizationService`.
3. Permitir a requisição se o usuário possuir a permissão.
4. Bloquear a requisição caso contrário.

Exemplo de rota:

```php
Route::post(
    '/vehicle/checkout',
    'CheckoutController@handle',
    ['permission' => 'vehicle.checkout']
);
```

---

## 4. Níveis de Verificação

O sistema deverá validar três camadas de autorização.

### 4.1 Permissão Global

Verifica se o usuário possui a permissão necessária para executar a ação.

Exemplo:

```text
vehicle.checkout
```

---

### 4.2 Permissão de Propriedade

Quando o recurso estiver associado a uma unidade, empresa ou usuário específico, o sistema deverá validar se o usuário possui acesso ao escopo do registro.

Exemplo:

```text
O gerente da unidade X só pode editar registros pertencentes à unidade X.
```

Essa validação deverá ser realizada pelo `AuthorizationService` ou pelo Service específico da funcionalidade.

---

### 4.3 Registro de Evento

Toda tentativa de acesso negado deverá gerar um evento de auditoria.

Exemplo de action:

```text
UNAUTHORIZED_ACCESS_ATTEMPT
```

---

## 5. Fluxo de Execução

### Passo A — Proteção de Rotas

As rotas deverão declarar explicitamente a permissão exigida.

Exemplo:

```php
Route::post(
    '/vehicle/checkout',
    'CheckoutController@handle',
    ['permission' => 'vehicle.checkout']
);
```

O middleware deverá ler o metadado `permission`.

Caso o usuário não possua a permissão, a requisição será bloqueada antes de chegar ao Controller.

---

### Passo B — Proteção no Service Layer

Além da proteção por rota, métodos críticos deverão possuir validação interna no Service.

Exemplo:

```php
public function checkout(array $data): void
{
    $this->authorization->check('vehicle.checkout');

    // lógica de checkout
}
```

Essa abordagem garante **Defesa em Profundidade**, protegendo o sistema mesmo quando uma rota for configurada incorretamente.

---

### Passo C — Auditoria de Acesso Negado

Quando uma `ForbiddenException` for lançada, o sistema deverá registrar o evento no `audit_logs`.

Dados mínimos:

| Campo | Valor |
|------|-------|
| `action` | `UNAUTHORIZED_ACCESS_ATTEMPT` |
| `user_id` | Usuário autenticado |
| `entity` | Recurso ou módulo solicitado |
| `old_values` | `null` |
| `new_values` | Contexto da tentativa |
| `ip_address` | IP do solicitante |
| `created_at` | Data e hora da tentativa |

Exemplo de contexto:

```json
{
  "requested_action": "vehicle.checkout",
  "route": "/vehicle/checkout",
  "reason": "User does not have required permission"
}
```

---

## 6. Tratamento Global de Exceções

A aplicação deverá possuir um handler global para capturar `ForbiddenException`.

Comportamento esperado:

1. Capturar a exceção.
2. Acionar o `AuditService`.
3. Registrar a tentativa de acesso negado.
4. Retornar resposta HTTP apropriada.

Resposta esperada:

```http
403 Forbidden
```

Exemplo de body:

```json
{
  "message": "You do not have permission to perform this action."
}
```

---

## 7. Regras de Design

### Fail-Closed

Por padrão, nenhuma rota ou ação sensível deverá ser permitida sem permissão explícita.

Caso a rota não defina permissão e seja considerada protegida, o sistema deverá negar o acesso.

---

### Desacoplamento

Controllers não devem conter regras de autorização.

O Controller deve receber apenas requisições já autorizadas pelo middleware.

---

### Defesa em Profundidade

Funcionalidades críticas deverão validar permissão também no Service Layer.

Isso evita falhas caso:

- uma rota seja cadastrada sem middleware;
- a funcionalidade seja chamada por outro fluxo interno;
- ocorra erro de configuração de rota.

---

### Auditoria Independente

O log de tentativa de acesso negado deverá ser persistido independentemente da operação principal.

Mesmo quando a requisição for bloqueada, a auditoria deverá registrar o evento.

---

## 8. Critérios de Aceitação

- [ ] O `IdentityMiddleware` carrega as permissões do usuário na sessão.
- [ ] Existe um `AuthorizationService` responsável por validar permissões.
- [ ] O método `can('action')` retorna booleano.
- [ ] O método `check('action')` lança `ForbiddenException` quando a permissão é negada.
- [ ] As rotas protegidas declaram permissões explicitamente.
- [ ] O middleware bloqueia requisições sem permissão antes do Controller.
- [ ] Métodos críticos no Service Layer também validam permissões.
- [ ] Tentativas negadas geram registro no `audit_logs`.
- [ ] O IP, usuário e ação solicitada são registrados na auditoria.
- [ ] O sistema retorna HTTP 403 para acessos negados.
- [ ] O sistema segue o princípio Fail-Closed.
- [ ] Controllers permanecem desacoplados da lógica de autorização.

---

## 9. Plano de implementação inicial

### Fase 1 — Abstrações PSR-4

A primeira fase introduz os esqueletos executáveis sem alterar ainda o comportamento das rotas:

- `RequestIdentity` passa a transportar uma lista imutável de permissões;
- `IdentityMiddleware` lê, valida, normaliza e remove duplicidades da lista armazenada na sessão;
- `AuthorizationService` oferece as operações `can` e `check`;
- `ForbiddenException` transporta a permissão negada;
- `AuthorizeMiddleware` recebe a permissão e a identificação da rota;
- `SecurityAuditService` registra negações independentemente de uma operação de negócio;
- uma migração amplia `audit_logs` para o novo evento de segurança.

Permissões ausentes ou malformadas resultam em uma lista vazia. Não existe permissão implícita, curinga ou bypass por identificador de usuário.

### Fase 2 — Fonte de permissões

Antes de proteger as rotas, o processo de login deverá receber uma lista confiável de permissões. Nesta etapa inicial, essa lista poderá vir de configuração simples ou de um resolvedor injetado, sem criar ainda tabelas de papéis e relacionamentos.

O login deverá substituir integralmente a lista de permissões ao autenticar. O logout deverá destruir a sessão, como já ocorre. Alterações administrativas futuras deverão invalidar sessões antigas para evitar permissões obsoletas.

Não será permitido aceitar permissões em formulários, cookies próprios, query strings ou payloads.

### Fase 3 — Metadados das rotas

O Router deverá aceitar uma coleção de Middlewares ou metadados por rota. Rotas sensíveis declararão exatamente uma permissão de ação.

Mapeamento inicial sugerido:

| Operação | Permissão |
|---|---|
| Visualizar vagas | `vehicle.view` |
| Registrar entrada | `vehicle.checkin` |
| Finalizar ocupação | `vehicle.checkout` |
| Criar vagas | `vacancy.create` |
| Visualizar relatórios | `report.view` |
| Alterar a própria senha | `profile.password.update` |
| Alterar a própria foto | `profile.photo.update` |

O `IdentityMiddleware` sempre será executado antes do `AuthorizeMiddleware`. Uma rota autenticada considerada sensível e sem permissão declarada deverá falhar durante o registro ou inicialização da aplicação.

### Fase 4 — Defesa em profundidade

As operações de estacionamento deverão ser extraídas dos Models atuais para Services de caso de uso. O `OcupacaoService` receberá `AuthorizationService` por construtor.

Antes de iniciar a Unit of Work:

- registrar entrada exige `vehicle.checkin`;
- finalizar ocupação exige `vehicle.checkout`;
- criar vagas exige `vacancy.create`.

O Service chama `check` antes de acessar Repository ou iniciar transação. Assim, chamadas internas, tarefas futuras e rotas configuradas incorretamente continuam protegidas.

O guard do Service não substitui o Middleware. O Middleware evita trabalho desnecessário e entrega resposta HTTP; o Service protege a regra de negócio independentemente do transporte.

### Fase 5 — Tratamento HTTP

Um handler global converterá:

- `UnauthorizedException` em autenticação necessária;
- `ForbiddenException` em HTTP 403;
- `AuditLogException` em falha de infraestrutura correlacionada pelo `request_id`.

Controllers não capturam essas exceções e não chamam a auditoria.

## 10. Estrutura PSR-4

A estrutura inicial fica organizada assim:

- `app/Context`: identidade autenticada e contexto da requisição;
- `app/Services/AuthorizationService`: decisão de autorização;
- `app/Services/SecurityAuditService`: eventos de segurança;
- `app/Middleware/IdentityMiddleware`: autenticação e montagem do contexto;
- `app/Middleware/AuthorizeMiddleware`: autorização da rota;
- `app/Exceptions/ForbiddenException`: negação de permissão;
- `app/Repositories/AuditLogRepository`: persistência do evento;
- `database/migrations`: compatibilidade do esquema de auditoria.

Na evolução dos casos de uso, `app/Services/OcupacaoService` concentrará regras de entrada e saída e receberá dependências por injeção.

## 11. Contratos dos esqueletos

### RequestIdentity

Expõe a coleção de permissões normalizada, além do ID, e-mail, IP, `request_id` e instante já existentes. Não acessa sessão nem banco.

### AuthorizationService

`can` valida o identificador da permissão e retorna booleano por comparação estrita. `check` reutiliza `can` e lança `ForbiddenException` quando necessário. O serviço não escreve logs e não conhece HTTP.

### AuthorizeMiddleware

Recebe `AuthorizationService` e `SecurityAuditService` por construtor. Em cada execução recebe a permissão necessária, o nome seguro da rota e o próximo elemento da cadeia.

Quando a autorização falha, registra o evento e relança a mesma `ForbiddenException`. O handler HTTP, fora do Middleware, produz a resposta 403.

### SecurityAuditService

Registra `UNAUTHORIZED_ACCESS_ATTEMPT` em autocommit, pois uma operação negada não possui transação de negócio. O payload mínimo contém:

- ação solicitada;
- rota solicitada;
- motivo normalizado;
- ID e e-mail do ator;
- IP;
- `request_id`;
- data UTC.

O serviço não registra payload, parâmetros, cookies ou conteúdo da sessão. A rota utilizada no log deve ser o padrão cadastrado no Router, não a URL bruta fornecida pelo cliente.

## 12. Estado de ativação

Os esqueletos podem ser carregados pelo Composer e testados isoladamente. A autorização ainda não deve ser anexada às rotas até que o login carregue permissões confiáveis.

Esse estado intermediário evita duas falhas perigosas:

- conceder todas as permissões temporariamente para manter compatibilidade;
- ativar listas vazias e bloquear indistintamente todos os usuários.
