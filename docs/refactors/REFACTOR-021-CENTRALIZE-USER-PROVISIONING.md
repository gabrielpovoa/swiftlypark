# REFACTOR-021 — Centralizar o provisionamento de usuários

## Identificação

- Branch: `feature/centralize-user-provisioning`
- Base: `develop` após a conclusão da feature 020
- Estado: concluída
- Feature anterior: `REFACTOR-020-IMPROVE-AUDIT-LOG-READABILITY.md`

## Problema

A criação de usuários ficava na Governança SaaS, enquanto consulta, revogação e
permissões ficavam em `/identity`. Essa separação obrigava o administrador a
procurar uma ação de identidade em uma tela diferente daquela usada para gerir
os usuários.

## Regra adotada

`/identity` é o diretório central para o ciclo de vida do usuário. A Governança
SaaS permanece voltada à visão de governança e aos vínculos por tenant.

## Implementado

- Formulário “Adicionar novo usuário” movido para `/identity`.
- Seção recolhível mantém a tela compacta e oferece uma ação clara “Novo
  usuário”.
- Novo endpoint `POST /identity/create`, protegido por `identity.manage`.
- O formulário só é renderizado para quem possui permissão de gestão.
- Empresas inativas não podem ser selecionadas no provisionamento.
- Somente Super-Admin pode atribuir papéis privilegiados (`master` e
  `super-admin`), preservando a regra anterior.
- A criação reutiliza `UserProvisioningService`, incluindo transação, validação,
  auditoria, geração de senha temporária e envio por e-mail.
- CSRF próprio da área de identidade é validado antes da operação.
- Em caso de erro, a seção reabre e preserva os campos preenchidos, sempre com
  escapamento na view.
- A caixa “Adicionar novo usuário” foi removida da Governança SaaS.
- A descrição da Governança deixou de anunciar o provisionamento de usuários.
- O endpoint legado permanece disponível para compatibilidade com integrações
  existentes, mas não é mais utilizado pela interface.

## Segurança

- Autorização aplicada na rota e novamente pelo serviço de aplicação.
- Proteção CSRF obrigatória no formulário HTML.
- Valores reapresentados depois de erro passam por `htmlspecialchars`.
- O e-mail e o nome continuam validados e sanitizados no serviço existente.
- Regras contra autoelevação e atribuição de papéis privilegiados foram
  preservadas.

## Validação automatizada

```bash
php tests/IdentityProvisioningViewTest.php
php tests/GovernanceUserManagementTest.php
php tests/SecurityTestSuite.php
php -l app/Identity/Presentation/IdentityManagementController.php
php -l app/Identity/Infrastructure/IdentityManagementRepository.php
php -l app/Views/Identity/index.php
php -l app/Views/Admin/provisioning.php
php -l routes/web.php
```

## Validação manual

1. Entrar com um usuário que possua `identity.manage`.
2. Acessar `/identity` e abrir “Novo usuário”.
3. Informar nome, e-mail, empresa e perfil e concluir a criação.
4. Confirmar a mensagem de sucesso e o usuário na listagem.
5. Confirmar o recebimento da senha temporária e a exigência de troca.
6. Forçar uma validação inválida e confirmar que o formulário reabre com os
   campos preservados.
7. Acessar `/admin` e confirmar que a caixa de criação não existe mais.
8. Entrar com acesso apenas `identity.view` e confirmar que o formulário não é
   exibido.

## Próximo passo exato

Avaliar em uma feature posterior se envio de senha temporária, vínculos e
permissões por empresa também devem sair da Governança e ser incorporados aos
cards individuais de `/identity`.
