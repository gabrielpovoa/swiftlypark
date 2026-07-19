# Guia de testes — Refatoração arquitetural 003–014

## Pré-requisitos

- Docker e Docker Compose.
- Portas `8080`, `8081` e `3306` disponíveis.
- `JOB_QUEUE_KEY` própria em produção, com ao menos 24 caracteres.
- SMTP configurado somente se o worker de e-mail for exercitado de verdade.

## 1. Subir o ambiente

```bash
docker compose up -d --build
docker compose ps
```

Esperado: `app`, `db` e `phpmyadmin` em estado `running`.

## 2. Aplicar e conferir migrations

```bash
docker compose exec app php cli/migrate.php migrate
docker compose exec app php cli/migrate.php status
```

Esperado:

- nenhuma migration pendente após o primeiro comando;
- migrations `20260725`, `20260726` e `20260727` marcadas com `[x]`;
- executar `migrate` novamente informa que o banco já está atualizado.

## 3. Suíte unitária e de fronteiras

```bash
for test in tests/*Test.php tests/SecurityTestSuite.php; do
  php "$test"
done
```

Essa suíte cobre:

- domínios Companies, Parking, Billing e Finance;
- Money, VehiclePlate, DurationMinutes e DatePeriod;
- isolamento tenant e segurança fail-closed;
- fronteiras entre Domain, Application, Infrastructure e Presentation;
- contratos de repositories com fakes sem PDO;
- cálculo tarifário, inclusive `39,56` para o cenário de caminhão;
- criptografia e dispatch da fila com doubles em memória.

## 4. Suíte de integração MySQL

```bash
docker compose exec app bash tests/run-integration.sh
```

Esperado:

```text
Financial ledger MySQL integration test passed
Financial ledger reconciliation passed
Concurrent check-in protection passed
Concurrent checkout lock passed
Concurrent monthly renewal lock passed
Background job MySQL concurrency/retry test passed
Completed job payload cleanup passed
```

Os testes usam transações/limpeza explícita e não deixam fixtures permanentes.

## 5. Smoke tests HTTP sem autenticação

```bash
for path in \
  /vacancy \
  /vacancy/manage \
  /CreateVacancy \
  /admin/companies \
  /admin/companies/company_id=1 \
  /finance \
  /finance/data \
  /finance/export \
  /finance/print
do
  curl -s -o /dev/null -w "$path %{http_code}\n" "http://localhost:8080$path"
done
```

Esperado: `302` para login/controle de acesso. Um `200` sem sessão em qualquer
rota protegida deve ser tratado como regressão.

## 6. Teste manual por perfil

### Super-admin global

1. Entrar com o super-admin.
2. Navegar por `/admin`, `/admin/companies`, `/identity` e `/finance`.
3. Confirmar que o papel global permanece `super-admin`.
4. Selecionar uma empresa no contexto de tenant sem escolher “Visualizar como”.
5. Confirmar que nenhuma role tenant substitui o papel global.
6. Ativar “Visualizar como” e escolher um papel tenant.
7. Confirmar que a simulação só permanece enquanto o modo de suporte estiver
   explicitamente ativo.

### Master/Admin da empresa

1. Confirmar acesso apenas às empresas vinculadas.
2. Criar/editar empresa e validar slug duplicado.
3. Configurar os quatro tarifários.
4. Criar, renovar e cancelar contrato mensalista.
5. Confirmar ausência de acesso a tenants não vinculados.

### Operador

1. Criar uma vaga de cada categoria com permissão apropriada.
2. Fazer check-in rotativo e mensalista.
3. Tentar repetir a mesma placa e a mesma vaga.
4. Finalizar rotativo com PIX/CARD/CASH e conferir valor.
5. Finalizar mensalista e confirmar valor zero no checkout.

## 7. Validação financeira e ledger

```bash
docker compose exec app php -r '
require "vendor/autoload.php";
$pdo=(new Config\Database())->connect();
$sql="SELECT company_id, source_type, entry_type, COUNT(*) quantity,
SUM(amount) total FROM financial_ledger_entries
GROUP BY company_id, source_type, entry_type ORDER BY company_id, source_type";
echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT), PHP_EOL;
'
```

Validar:

- checkout rotativo gera um `CREDIT/ROTATING_PAYMENT`;
- pagamento mensalista gera um `CREDIT/MONTHLY_PAYMENT`;
- reembolso gera um `DEBIT/ADJUSTMENT`;
- repetir a mesma origem viola a chave única;
- dashboard Finance apresenta bruto, mensalista, rotativo e ajustes coerentes.

## 8. Fila de e-mails

Enfileirar uma recuperação/criação de usuário e conferir sem revelar payload:

```bash
docker compose exec app php -r '
require "vendor/autoload.php";
$pdo=(new Config\Database())->connect();
echo json_encode($pdo->query("SELECT id, queue_name, job_type, status, attempts,
available_at, last_error FROM background_jobs ORDER BY id DESC LIMIT 10")
->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT), PHP_EOL;
'
```

Processar somente quando SMTP de teste estiver configurado:

```bash
docker compose exec app php cli/worker.php --once
```

Para execução contínua:

```bash
docker compose exec app php cli/worker.php
```

Confirmar que jobs concluídos ficam `COMPLETED` e com
`encrypted_payload = ''`. Nunca imprimir o payload criptografado ou credenciais
SMTP em logs/testes.

## 9. Verificação arquitetural e Git Flow

```bash
git status --short --branch
git log --oneline --decorate develop --max-count=30
```

Esperado:

- worktree limpo ao final;
- implementação integrada localmente em `develop` somente por
  `git flow feature finish`;
- handoffs `REFACTOR-003` até `REFACTOR-014` presentes em `docs/refactors/`;
- nenhum push remoto é necessário para a validação local.

## 10. Critérios de aceite

A refatoração é aprovada quando:

1. migrations e duas execuções idempotentes passam;
2. suíte unitária/fronteiras passa integralmente;
3. suíte MySQL passa integralmente;
4. smoke tests mantêm guards;
5. check-in/checkout/mensalista funcionam manualmente;
6. ledger reconcilia todas as origens;
7. worker processa e limpa payloads;
8. super-admin não perde o papel global sem ativar “Visualizar como”.
