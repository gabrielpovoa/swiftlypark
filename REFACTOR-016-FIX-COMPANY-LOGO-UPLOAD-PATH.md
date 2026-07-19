# REFACTOR-016 — Corrigir caminho de upload da logo empresarial

## Identificação

- Branch: `feature/fix-company-logo-upload-path`
- Base: `develop` após a conclusão da feature 015
- Estado: concluída
- Feature anterior: `REFACTOR-015-CENTRALIZE-COMPANY-MANAGEMENT.md`

## Problema

Ao alterar a logo pela página individual da empresa, o sistema retornava:

```text
Não foi possível preparar o diretório de logos.
```

Após a modularização, `AdminCompanyBillingController` passou a residir em
`app/Billing/Presentation`. O cálculo `dirname(__DIR__, 2)` passou a resolver a
raiz como `app/`, produzindo o caminho inexistente
`app/public/uploads/companies`.

## Implementado

- Ajustado o cálculo para `dirname(__DIR__, 3)`.
- O destino volta a ser `<raiz>/public/uploads/companies`.
- Mantidas validações de MIME, limite de 2 MB e nome aleatório do arquivo.
- Teste de regressão garante que o controller continue apontando para o
  diretório público da raiz mesmo após futuras movimentações.

## Evidência local

```text
/var/www/html/app/public: inexistente
/var/www/html/public/uploads/companies: existente e gravável
Company management view test passed
```

## Validação manual

1. Acessar `/admin/companies/company_id=5`.
2. Expandir “Dados cadastrais”.
3. Selecionar ou arrastar uma imagem PNG, JPG ou WEBP com até 2 MB.
4. Clicar em “Salvar dados da empresa”.
5. Confirmar a mensagem de sucesso e a nova logo no cabeçalho.

## Próximo passo exato

1. Validar o upload autenticado no navegador.
2. Criar o commit da feature após aprovação.
3. Finalizar a feature via Git Flow.
