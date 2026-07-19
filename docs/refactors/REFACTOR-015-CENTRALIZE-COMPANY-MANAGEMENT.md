# REFACTOR-015 — Centralizar gestão de empresas

## Identificação

- Branch: `feature/refactor-company-management`
- Base: `develop` após a conclusão da feature 014
- Estado: concluída
- Feature anterior: `REFACTOR-014-BACKGROUND-JOB-QUEUE.md`

## Objetivo

Transformar `/admin/companies` e a página individual da empresa nos pontos
canônicos para criação, cadastro e gestão de vínculos, reduzindo a mistura de
responsabilidades existente na tela de Governança SaaS.

## Invariantes

- Somente identidades autorizadas por `identity.manage` acessam as telas.
- Controllers continuam validando papéis administrativos nas mutações.
- Vínculos sempre carregam `company_id` explícito; não dependem do tenant ativo.
- Toda mutação mantém CSRF e auditoria já existentes.
- A troca de logo é opcional e preserva a imagem atual quando nenhum arquivo é enviado.
- Nenhum parâmetro nomeado é reutilizado com prepared statements nativos do PDO.

## Implementado

- Item `Empresas` no menu lateral, protegido por `identity.manage`.
- Formulário recolhível de criação movido para o diretório `/admin/companies`.
- Card de criação removido da Governança; o diretório de empresas é o único ponto de criação.
- Dados cadastrais da empresa apresentados em dropdown no padrão da tela de perfil.
- Razão social, nome fantasia, slug, logo, cobrança e tarifários possuem salvamento explícito.
- Upload de logo usa área visual clicável/drag-and-drop e não exibe o texto nativo
  “nenhum arquivo selecionado”.
- Lista contextual de membros na página individual da empresa.
- Vínculo de pessoa existente com seleção de perfil dentro da própria empresa.
- Remoção contextual de vínculo com confirmação e retorno para a empresa de origem.
- Correção de `SQLSTATE[HY093]`: `name` e `trade_name` agora usam placeholders
  PDO distintos no `UPDATE` cadastral.
- Mensagens das ações de vínculo são exibidas na página individual após redirect.
- Teste de regressão `CompanyManagementViewTest.php` cobre presença dos fluxos,
  navegação e a correção do placeholder PDO.

## Evidência local

```text
Company management view test passed
Todos os testes unitários e de segurança passaram.
Todos os arquivos PHP passaram no php -l.
git diff --check sem erros.
```

## Validação manual

1. Acessar `/admin/companies` com usuário que possua `identity.manage`.
2. Abrir “Criar empresa”, preencher nome/slug, selecionar ou arrastar uma logo e salvar.
3. Abrir a empresa criada e expandir “Dados cadastrais”.
4. Alterar razão social, nome fantasia ou slug e confirmar que a tela recarrega com sucesso.
5. Selecionar uma nova logo e confirmar sua exibição no cabeçalho após salvar.
6. Em “Pessoas vinculadas”, adicionar uma pessoa e um perfil.
7. Remover o vínculo e confirmar que a operação retorna à mesma empresa.
8. Confirmar que usuários sem `identity.manage` não visualizam nem acessam o menu/rotas.

## Próximo passo exato

1. Executar a validação manual autenticada no navegador.
2. Revisar o diff e criar o commit da feature 015.
3. Finalizar via Git Flow somente após aprovação funcional.
