# CI/CD com Git Flow

## Estado atual

O projeto utiliza GitHub Actions para integração contínua e geração de
candidatos de entrega. Não existe deploy, acesso a servidor ou credencial de
produção configurada.

## Fluxo de branches

```text
feature/* ou bugfix/*
          |
          | Pull Request + CI aprovado
          v
       develop  (integração mais atual)
          |
          | git flow release start/finish + Pull Request
          v
        main    (versão estável)
```

- `develop` recebe funcionalidades concluídas e permanece como a versão mais
  atual do produto.
- `main` representa somente versões estáveis.
- O CI não realiza merge, commit, tag ou push.
- O workflow de entrega não publica imagem e não executa deploy.
- Pull Requests para `main` são aceitos pelo CI somente quando partem de
  `release/*` ou `hotfix/*`.

## Workflows

### CI

`.github/workflows/ci.yml` executa:

1. política de origem da Pull Request;
2. validação e auditoria do Composer;
3. lint de PHP e JavaScript;
4. detecção básica de arquivos sensíveis versionados;
5. validação do Docker Compose;
6. testes principais e suíte de segurança;
7. bootstrap de MySQL 8 e testes de integração/concorrência;
8. build da imagem Docker, sem `push`.

### Delivery candidate

`.github/workflows/delivery.yml` somente inicia após o CI terminar com sucesso
em `develop` ou `main`. O workflow:

1. reconstrói exatamente o commit aprovado;
2. exporta a imagem como `swiftlypark-image.tar.gz`;
3. mantém o artefato no GitHub Actions por sete dias;
4. não autentica em registry e não conhece ambiente de produção.

O workflow também pode ser iniciado manualmente em **Actions**, sem realizar
deploy.

## Proteções a configurar no GitHub

As regras abaixo são configurações do repositório e não podem ser ativadas por
um arquivo commitado:

### `develop`

- Require a pull request before merging;
- Require status checks to pass before merging;
- selecionar `Git Flow policy`, `Quality and security`, `Unit and security
  tests`, `MySQL integration tests` e `Docker build`;
- Require branches to be up to date before merging;
- bloquear force push e exclusão;
- não permitir bypass das regras.

### `main`

- Require a pull request before merging;
- exigir os mesmos status checks;
- Require branches to be up to date before merging;
- bloquear push direto, force push e exclusão;
- permitir entrada somente por `release/*` ou `hotfix/*`, validada pelo CI;
- manter merge como uma ação humana deliberada.

Para uma equipe de uma pessoa, a obrigatoriedade de aprovação por outro revisor
pode permanecer desativada; a exigência da Pull Request e dos checks ainda
impede integração acidental ou sem validação.

## Banco de integração

O CI cria um MySQL descartável e nunca acessa o banco local ou de produção. O
bootstrap parte do dump legado e aplica as migrations incrementais.

A migration histórica `20260719` contém um `DELETE` autocorrelacionado rejeitado
por instalações limpas do MySQL 8. Como migrations aplicadas são protegidas por
checksum, o CI usa uma versão funcionalmente equivalente somente na base
descartável. A migration original não foi modificada.

## Execução local

```bash
bash tests/ci/run-static-analysis.sh
bash tests/ci/run-unit.sh
docker build -t swiftlypark:local-ci .
```

O teste de integração requer um banco descartável já criado e as variáveis:

```bash
DB_HOST=127.0.0.1 \
DB_DATABASE=parking_ci \
DB_USERNAME=root \
DB_PASSWORD=senha \
JOB_QUEUE_KEY=chave-de-ci-com-32-caracteres \
bash tests/ci/run-integration.sh
```

Nunca aponte esse comando para `parking` ou para um banco persistente: o dump de
fixture recria tabelas.

## Próxima evolução

Quando existir homologação, criar um workflow separado com ambiente protegido e
aprovação manual. Produção deve permanecer ausente até que infraestrutura,
segredos, rollback e observabilidade estejam definidos.
