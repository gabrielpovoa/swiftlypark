# Spec-AUTH-002: Fluxo Seguro de Recuperação de Senha via OTP

## 1. Overview

Esta especificação define o fluxo seguro de recuperação de senha utilizando **OTP (One-Time Password)**, com o objetivo de proteger o processo de redefinição de credenciais contra ataques de enumeração de usuários, força bruta e reutilização de códigos.

O fluxo será dividido em três etapas: solicitação de recuperação, validação do código OTP e redefinição da senha.

---

## 2. Modelagem de Dados

Será criada uma tabela dedicada para armazenar solicitações de recuperação de senha.

### Tabela `password_resets`

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `id` | PK, Integer | Identificador da solicitação |
| `email` | String | E-mail utilizado na recuperação |
| `otp_hash` | String | Hash do código OTP |
| `expires_at` | Timestamp | Data e hora de expiração do código |
| `attempts` | Integer | Quantidade de tentativas de validação |
| `created_at` | Timestamp | Data de criação da solicitação |

### Regras

- O código OTP nunca deverá ser armazenado em texto puro.
- Apenas o hash do código será persistido.
- O registro deverá ser invalidado ou removido após uso bem-sucedido ou expiração.

---

## 3. Fluxo de Recuperação

### 3.1 Etapa I — Solicitação de Recuperação

O usuário informa seu endereço de e-mail para iniciar o processo de recuperação.

#### Fluxo

1. Receber o e-mail informado.
2. Gerar um código OTP numérico de **5 dígitos**.
3. Armazenar apenas o hash do código.
4. Definir um tempo de expiração (TTL), por exemplo, **10 minutos**.
5. Enviar o código original para o e-mail do usuário.

#### Blind Response

Independentemente da existência do e-mail cadastrado, a resposta da API deverá ser sempre a mesma.

Exemplo:

```text
Se o e-mail existir em nossa base de dados, um código de recuperação foi enviado.
```

Essa abordagem evita ataques de enumeração de usuários.

---

### 3.2 Etapa II — Validação do OTP

O usuário informa o código recebido por e-mail.

O sistema deverá validar:

- existência de uma solicitação para o e-mail informado;
- validade do tempo de expiração;
- quantidade máxima de tentativas permitidas;
- correspondência entre o código informado e o hash armazenado.

Caso todas as validações sejam aprovadas, deverá ser criada uma autorização temporária para redefinição da senha.

---

### 3.3 Etapa III — Redefinição da Senha

O formulário de redefinição somente poderá ser acessado quando existir uma autorização temporária válida emitida na etapa anterior.

Após o envio da nova senha, o sistema deverá:

1. Atualizar a senha do usuário.
2. Invalidar a autorização temporária.
3. Remover ou invalidar o registro correspondente na tabela `password_resets`.

Essa medida impede a reutilização do código OTP.

---

## 4. Requisitos de Segurança

### Rate Limiting

O sistema deverá aplicar limitação de tentativas para impedir ataques de força bruta.

Exemplo:

- máximo de 3 tentativas consecutivas;
- bloqueio temporário após exceder o limite.

---

### Segurança de Sessão

O formulário de redefinição de senha não poderá ser acessado diretamente por URL.

O acesso deverá depender obrigatoriamente da autorização temporária emitida após a validação bem-sucedida do OTP.

---

### Armazenamento Seguro

O código OTP deverá ser armazenado exclusivamente na forma de hash.

Em nenhuma circunstância o código original deverá ser persistido no banco de dados.

---

### Housekeeping

O sistema deverá possuir uma rotina automática para remoção de registros expirados da tabela `password_resets`.

Essa rotina deverá:

- eliminar solicitações expiradas;
- reduzir crescimento desnecessário da tabela;
- manter a performance do banco de dados.

---

## 5. Critérios de Aceitação

- [ ] Existe uma tabela dedicada para recuperação de senha (`password_resets`).
- [ ] O código OTP é armazenado apenas em formato hash.
- [ ] O OTP possui tempo de expiração configurável.
- [ ] O sistema retorna uma resposta genérica independentemente da existência do e-mail informado.
- [ ] O código é enviado por e-mail ao usuário.
- [ ] O sistema valida expiração, tentativas e integridade do OTP antes da liberação da redefinição.
- [ ] Após a redefinição da senha, o OTP é invalidado e não pode ser reutilizado.
- [ ] Existe limitação de tentativas para proteção contra força bruta.
- [ ] O formulário de redefinição exige autorização temporária válida.
- [ ] Registros expirados são removidos automaticamente por uma rotina de limpeza.