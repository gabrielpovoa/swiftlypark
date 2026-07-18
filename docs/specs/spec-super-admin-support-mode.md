# Modo Suporte do Super-Admin

## Objetivo

Permitir que um usuario com papel global de `super-admin` acesse o ambiente de qualquer empresa para suporte, auditoria e validacao de fluxos, sem precisar criar vinculos permanentes em `company_user` e sem alterar suas permissoes globais reais.

O `super-admin` continua sendo uma identidade global da plataforma. Ao entrar no ambiente de uma empresa, ele passa a operar em um contexto temporario de suporte, podendo escolher uma role simulada ou um conjunto customizado de permissoes para reproduzir o comportamento de usuarios daquela empresa.

## Modelo Mental

Existem tres camadas diferentes:

1. Identidade real global

   Define quem o usuario e na plataforma. Exemplo: `super-admin`.

2. Contexto da empresa

   Define qual empresa esta sendo inspecionada. Exemplo: `SwiftlyPark`, `Estacionamento Jardim Europa`.

3. Perfil simulado

   Define como o `super-admin` quer visualizar/testar o ambiente daquela empresa. Exemplo: `operator`, `admin`, `auditor`, `finance`.

## Comportamento Esperado

Quando o usuario estiver no Dashboard Global, ele esta atuando como `super-admin` global e visualizando metricas agregadas da plataforma.

Ao escolher uma empresa, o sistema deve entrar em modo suporte sem alterar automaticamente o papel efetivo do `super-admin`. Nesse estado inicial, ele continua com acesso global e pode navegar por todas as views da empresa selecionada.

O perfil tenant so deve mudar depois de uma acao explicita no controle `Visualizar como`. Navegar para outra view, abrir `Usuarios` ou selecionar uma empresa nunca deve, por si so, converter o perfil efetivo para `master`, `admin` ou qualquer outro papel.

Depois disso, o usuario pode escolher explicitamente como quer visualizar aquela empresa:

- `Admin`
- `Operador`
- `Financeiro`
- `Auditor`
- `Master`
- `Permissoes customizadas`

O papel `super-admin` nao deve ser simulado dentro da empresa, porque ele representa um papel global da plataforma, nao um papel operacional tenant-scoped.

A opcao `Super-Admin - acesso global` no controle representa a ausencia de simulacao. Ela restaura o papel global real; nao cria uma simulacao tenant-scoped de `super-admin`.

## UX Proposta

O seletor principal deve deixar clara a diferenca entre voltar ao dashboard global e entrar em suporte:

- `Dashboard global`
- `SwiftlyPark`
- `Estacionamento Jardim Europa`
- outras empresas ativas

Depois de selecionar uma empresa, a interface pode exibir um segundo controle:

```text
Visualizar como: [Operador v]
```

Ou, no caso de permissoes customizadas:

```text
Visualizar como: Permissoes customizadas

[x] Gerenciar vagas
[x] Criar check-in
[ ] Ver financeiro
[ ] Gerenciar usuarios
[x] Ver auditoria
```

Enquanto o modo suporte estiver ativo, o layout deve exibir um banner persistente:

```text
Voce esta atuando como suporte na empresa SwiftlyPark, visualizando como Operador.
Todas as acoes serao auditadas.
```

## Sessao

O modo suporte deve ser temporario e salvo em sessao, nao como vinculo permanente no banco.

Exemplo:

```php
$_SESSION['support_impersonation'] = [
    'company_id' => 2,
    'company_name' => 'SwiftlyPark',
    'simulated_role' => 'operator',
    'extra_permissions' => [
        'vacancy.manage',
        'checkin.create',
    ],
];
```

Enquanto nenhuma opcao tenant tiver sido aplicada, a sessao deve manter `profile_selected = false`, `simulated_role = null` e a autorizacao efetiva global do `super-admin`.

Ao voltar para o Dashboard Global, o sistema deve limpar `support_impersonation`.

## RBAC

Durante o modo suporte, a autorizacao deve considerar:

- a identidade real global do usuario;
- a empresa selecionada;
- a role simulada;
- permissoes customizadas temporarias, quando existirem.

Para decisoes dentro do ambiente da empresa, menus, dashboards e rotas devem se comportar como se o usuario tivesse a role simulada.

Para decisoes globais da plataforma, o usuario continua sendo `super-admin`.

## Auditoria

Toda acao executada em modo suporte deve registrar:

- usuario real que executou a acao;
- empresa acessada;
- role simulada;
- permissoes customizadas, quando aplicavel;
- indicador de modo suporte ativo;
- acao executada;
- data/hora.

Exemplo conceitual:

```text
actor_user_id: 1
actor_email: joaopovoa6@gmail.com
company_id: 2
support_mode: true
simulated_role: operator
real_global_role: super-admin
action: vacancy.release
```

Isso permite auditar que a acao foi feita pelo Super-Admin, mas dentro de um contexto simulado de Operador.

## Regras de Seguranca

- O modo suporte nao deve criar registros em `company_user`.
- O modo suporte nao deve alterar roles reais do usuario.
- O modo suporte deve ser sempre visivel no layout por meio de banner.
- O modo suporte deve ser auditado em todas as acoes sensiveis.
- A role `super-admin` nao deve ser usada como role simulada tenant-scoped.
- A saida para o Dashboard Global deve encerrar o modo suporte.

## Beneficios

- Evita poluir o banco com vinculos artificiais.
- Permite testar rapidamente diferentes perfis de uma empresa.
- Mantem separacao clara entre poder global e permissao operacional.
- Facilita investigacao de bugs de permissao.
- Deixa a auditoria mais transparente e rastreavel.
