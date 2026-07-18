# SwiftlyPark

Sistema SaaS multiempresa para gestão de estacionamentos, com operação de vagas, check-in/check-out, financeiro, auditoria, RBAC, governança de usuários e dashboard global para Super-Admin.

O projeto usa PHP em arquitetura MVC, MySQL e Docker. A aplicação roda com Apache apontando para `public/`.

## O Que Existe Hoje

- Autenticação com login, logout, recuperação de senha por OTP e troca obrigatória de senha.
- Gestão operacional de vagas, check-in e checkout de veículos.
- Dashboard operacional por empresa.
- Relatórios de movimentações.
- Financeiro com visão analítica, exportação CSV, impressão e ajustes/reembolsos.
- Auditoria por empresa e auditoria global para perfis de plataforma.
- Multi-tenancy com isolamento por `company_id`.
- Troca de empresa pelo Tenant Switcher.
- Governança de usuários, empresas, vínculos e permissões.
- RBAC com roles e permissions.
- Permissões extras por vínculo usuário-empresa.
- Dashboard Global para Super-Admin com KPIs agregados.
- Modo suporte/impersonation para Super-Admin acessar ambientes de empresas.
- Upload de foto de perfil e logo de empresa.

## Papéis e Contextos

O sistema separa três conceitos:

- **Papel global:** define poderes na plataforma, como `super-admin` e `master`.
- **Papel na empresa:** define o que o usuário pode fazer dentro de uma empresa, como `admin`, `operator`, `finance` ou `auditor`.
- **Contexto ativo:** define qual empresa está sendo usada na sessão atual.

Um Super-Admin pode acessar o Dashboard Global e também entrar no contexto de uma empresa para suporte. Quando estiver atuando em uma empresa, o sistema deve deixar isso visível por banner e auditar as ações.

Mais detalhes estão em:

- [spec-saas-eight.md](docs/specs/spec-saas-eight.md)
- [spec-super-admin-support-mode.md](docs/specs/spec-super-admin-support-mode.md)
- [spec-multi-tenancy.md](docs/specs/spec-multi-tenancy.md)
- [spec-auth-rebac-governance.md](docs/specs/spec-auth-rebac-governance.md)

## Stack

- PHP 8.2
- MySQL 8.0
- Apache
- Docker / Docker Compose
- Composer
- PHPMailer
- PhpSpreadsheet
- Tailwind via CDN/markup nas views
- Lucide icons nas views

## Estrutura

```text
swiftlypark/
├── app/
│   ├── Authorization/       # RBAC, roles e permissions
│   ├── Bootstrap/           # Bootstrap de tenant/schema
│   ├── Context/             # IdentityContext e TenantContext
│   ├── Controllers/         # Controllers MVC
│   ├── Middleware/          # Auth, tenant e autorização
│   ├── Repositories/        # Acesso a dados
│   ├── Services/            # Serviços de domínio e segurança
│   └── Views/               # Views PHP
├── config/                  # Configuração de banco
├── core/                    # Router e Controller base
├── database/migrations/     # Migrações incrementais
├── docs/specs/              # Especificações funcionais/técnicas
├── heidSQL/parking.sql      # Dump/base inicial
├── public/                  # index.php, assets e uploads
├── routes/web.php           # Rotas HTTP
├── tests/                   # Testes de segurança, tenant e governança
├── compose.yaml             # Ambiente Docker
├── Dockerfile
└── composer.json
```

## Como Rodar

Pré-requisitos:

- Docker
- Docker Compose

Suba o ambiente:

```bash
docker compose up -d --build
```

Acesse:

- Aplicação: http://localhost:8080
- phpMyAdmin: http://localhost:8081

Banco local:

```text
Host: db
Database: parking
User: root
Password: root
```

Para acessar pelo host, use:

```text
Host: 127.0.0.1
Port: 3306
Database: parking
User: root
Password: root
```

Se o banco estiver vazio, importe o dump:

```text
heidSQL/parking.sql
```

O bootstrap da aplicação também cria/ajusta parte da infraestrutura SaaS automaticamente quando o app inicia, como tabelas de tenant, vínculos e colunas `company_id` em tabelas operacionais existentes.

## Rotas Principais

```text
/                         Redireciona conforme sessão e papel
/login                    Login
/operational/dashboard    Dashboard operacional da empresa ativa
/admin/dashboard          Dashboard global do Super-Admin
/admin                    Governança de usuários e permissões
/admin/companies          Diretório de empresas
/identity                 Gestão de identidades
/audit                    Trilha de auditoria
/vacancy                  Consulta operacional
/vacancy/apply            Check-in
/vacancy/manage           Gestão de vagas ocupadas
/finance                  Financeiro
/Profile                  Perfil do usuário
```

APIs internas usadas pela interface:

```text
GET  /api/v1/user/tenants
POST /api/v1/tenant/switch
GET  /api/v1/permissions/context
```

## Multi-Tenancy

O isolamento por empresa usa `company_id`.

Principais tabelas:

- `companies`
- `company_user`
- `company_user_permissions`
- `user_roles`
- `roles`
- `permissions`
- `role_permissions`
- `audit_logs`

Repositórios devem aplicar filtro de tenant por padrão. Quando uma consulta precisa ser global, como no Dashboard Global do Super-Admin, o acesso deve ser explícito com `withoutTenantFilter()`.

## RBAC e Permissões

O RBAC combina:

- roles globais da plataforma;
- roles por empresa;
- permissões herdadas da role;
- permissões extras por vínculo usuário-empresa.

Permissões por empresa devem afetar apenas aquele vínculo específico. Exemplo: um usuário pode ser `operator` na SwiftlyPark e ter outro papel/permissões em outra empresa.

## Auditoria

A auditoria registra ações sensíveis e pode ser consultada em três escopos:

- Global, para Super-Admin/Master.
- Empresa específica.
- Empresa atual da sessão.

Em modo suporte, a auditoria deve registrar o usuário real, a empresa acessada e o contexto usado na operação.

## Dashboard Global

O Dashboard Global é reservado para papéis globais de plataforma, como `super-admin` e `master`.

Ele mostra KPIs agregados, como:

- empresas ativas;
- check-ins;
- sessões ativas;
- faturamento total;
- alertas de segurança;
- ranking/resumo por empresa.

Essa tela não deve exibir formulário de check-in rápido para evitar operação acidental fora do contexto correto.

## Modo Suporte do Super-Admin

O Super-Admin pode acessar o ambiente de empresas para suporte. A proposta documentada é separar:

- empresa selecionada;
- perfil simulado;
- permissões customizadas temporárias;
- identidade global real.

Esse estado deve ser temporário, salvo em sessão e sempre visível no layout por meio de banner.

Documento de referência:

- [spec-super-admin-support-mode.md](docs/specs/spec-super-admin-support-mode.md)

## Uploads

Uploads ficam em:

```text
public/uploads/
public/uploads/companies/
```

No Docker, `compose.yaml` prepara o volume `swiftly-uploads` com permissão para o usuário do Apache (`www-data`).

## Testes

Testes disponíveis:

```bash
php tests/TenantContextTest.php
php tests/RepositoryTenantIsolationTest.php
php tests/SecurityTestSuite.php
php tests/GovernanceUserManagementTest.php
```

Lint rápido de um arquivo PHP:

```bash
php -l app/Controllers/AlgumController.php
```

## Migrations de banco

Migrations nunca são executadas durante requisições HTTP. Antes de publicar uma
nova versão, execute:

```bash
php cli/migrate.php status
php cli/migrate.php migrate
```

Para adotar o runner em uma instalação existente cujo schema já recebeu todas
as migrations antigas, registre o estado uma única vez, sem reaplicar SQL:

```bash
php cli/migrate.php baseline
```

Não execute `baseline` em banco vazio ou desatualizado. O comando apenas registra
as migrations existentes; ele não cria nem corrige tabelas.

## Comandos Úteis

Subir o ambiente:

```bash
docker compose up -d --build
```

Ver containers:

```bash
docker compose ps
```

Acessar o container da aplicação:

```bash
docker exec -it swiftlypark-app bash
```

Acessar o MySQL:

```bash
docker exec -it swiftlypark-db mysql -uroot -proot parking
```

Ver logs da aplicação:

```bash
docker logs swiftlypark-app
```

## Desenvolvimento

Ao alterar regras de tenant, RBAC, auditoria ou governança:

1. Verifique se o acesso continua respeitando `company_id`.
2. Confirme se Super-Admin global não depende da role tenant ativa.
3. Confirme se ações sensíveis geram auditoria.
4. Rode os testes relacionados.
5. Rode `php -l` nos arquivos alterados.

## Repositório

https://github.com/gabrielpovoa/swiftlypark
