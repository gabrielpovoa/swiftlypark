# REFACTOR-030 — Proteção da identidade do Super-Admin

> Atualização: o REFACTOR-031 reabriu `/admin` para administradores, mantendo a
> exclusão de super-admins e aplicando escopo obrigatório da empresa ativa.

## Objetivo

Impedir que usuários sem papel global `super-admin` visualizem ou executem ações
sobre o perfil de um super-admin, inclusive por manipulação direta de `user_id`.

## Implementação

- listagens de `/identity` excluem contas com papel global `super-admin` quando o
  solicitante não é super-admin;
- o endpoint de senha temporária valida o usuário-alvo antes de gerar senha,
  alterar hashes ou enfileirar e-mail;
- acesso e atualização direta de `/Profile?user_id=...` passam pelo mesmo guard;
- revogação, reativação e alterações de permissões bloqueiam super-admin como
  alvo de administradores de tenant;
- o guard também exige vínculo do usuário-alvo com a empresa ativa;
- a tela legada `/admin` fica exclusiva do super-admin; administradores utilizam
  `/identity` e a página da própria empresa.

## Garantias

Um administrador não recebe nome, e-mail, perfil ou ações do super-admin na
listagem. Mesmo conhecendo seu ID, recebe `403` antes de qualquer escrita ou job
de e-mail. O super-admin continua podendo administrar todas as identidades.

## Validação

```bash
php tests/UserAccessGuardTest.php
php tests/CompanyAccessGuardTest.php
php tests/SecurityTestSuite.php
```

## Retomada

- branch: `feature/protect-super-admin-identity`;
- base: `develop`;
- não exige migration de banco.
