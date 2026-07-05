# Spec-FIN-001: Refatoração do Motor de Arrecadação e Relatórios

## 1. Overview

Esta especificação define a refatoração da camada financeira responsável pelo cálculo de arrecadação e geração de relatórios.

O objetivo é desacoplar os indicadores financeiros das métricas operacionais, garantindo que a receita seja contabilizada exclusivamente pela data do pagamento, permitindo relatórios financeiros precisos, conciliação de caixa e análises históricas.

---

## 2. Contexto

Atualmente, o dashboard apresenta indicadores financeiros e operacionais utilizando eventos de entrada e saída dos veículos.

O comportamento esperado é que:

- a receita seja calculada pela data em que o pagamento foi efetivamente realizado;
- métricas operacionais sejam calculadas pelas datas de entrada e saída;
- ambas as informações permaneçam independentes.

---

## 3. Problema

A "Receita do Dia" está sendo calculada com base nas movimentações do estacionamento, gerando inconsistências financeiras.

Exemplos:

- Um veículo entra ontem e realiza o pagamento hoje. A receita pertence ao dia do pagamento.
- Um veículo entra hoje, mas efetua o pagamento amanhã. A receita não deve ser contabilizada no dia da entrada.

Esse comportamento compromete:

- fechamento de caixa;
- indicadores financeiros;
- relatórios gerenciais;
- auditorias financeiras.

---

## 4. Causa Raiz

### Acoplamento entre Domínios

A camada financeira encontra-se fortemente acoplada às entidades responsáveis pela operação do estacionamento (`Booking` ou `ParkingSlot`), dificultando a separação entre eventos operacionais e financeiros.

---

### Modelo de Dados

A entidade responsável pelas transações financeiras não possui um campo específico para representar a data efetiva do pagamento.

Atualmente, o sistema depende indiretamente das datas de entrada ou saída do veículo para calcular receitas.

---

## 5. Modelagem de Dados

A tabela de transações deverá ser expandida com um novo campo.

| Coluna | Tipo | Descrição |
|---------|------|-----------|
| `payment_date` | DATETIME | Data e hora em que o pagamento foi efetivamente processado |

Esse campo será utilizado como referência oficial para todas as consultas financeiras.

---

## 6. Requisitos Funcionais

### 6.1 Dashboard Financeiro

A receita diária deverá ser calculada utilizando exclusivamente:

```sql
SUM(amount)
WHERE payment_date = DATA_ALVO
```

Nenhuma regra financeira deverá depender das datas de entrada ou saída dos veículos.

---

### 6.2 Dashboard Operacional

Os indicadores operacionais permanecerão independentes.

#### Entradas

```sql
COUNT(...)
WHERE entry_date = DATA_ALVO
```

#### Saídas

```sql
COUNT(...)
WHERE exit_date = DATA_ALVO
```

#### Ocupação

A taxa de ocupação continuará sendo calculada conforme o estado atual das vagas.

---

### 6.3 Relatórios Financeiros

Criar um endpoint que permita consultas por intervalo de datas.

#### Endpoint

```http
GET /reports/financial
```

#### Query Parameters

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `date_start` | Date | Data inicial da consulta |
| `date_end` | Date | Data final da consulta |

O endpoint deverá retornar todas as transações liquidadas dentro do período informado.

---

## 7. Evoluções Futuras

A separação entre eventos financeiros e operacionais permitirá implementar novos recursos sem necessidade de alterar o modelo atual.

### Histórico Financeiro

Possibilitar geração de:

- arrecadação diária;
- arrecadação semanal;
- arrecadação mensal;
- arrecadação anual;
- gráficos financeiros.

---

### Conciliação Financeira

Permitir a identificação de inconsistências operacionais através de um relatório de pendências.

Exemplo:

- reservas finalizadas (`exit_time` preenchido);
- ausência de transação financeira vinculada.

Esse relatório auxilia na identificação de:

- falhas operacionais;
- pagamentos não registrados;
- inconsistências sistêmicas.

---

### Fechamento de Caixa

O uso de `payment_date` com precisão de data e hora permitirá implementar fechamento por turno de trabalho.

Exemplos:

- Caixa da Manhã;
- Caixa da Tarde;
- Caixa da Noite.

Também será possível calcular a arrecadação individual por operador.

---

### Relatórios Gerenciais

A separação da camada financeira permitirá análises históricas como:

- sazonalidade da arrecadação;
- comparação entre meses;
- comparação entre anos;
- dias de maior faturamento;
- ticket médio por período;
- evolução financeira do estacionamento.

---

## 8. Critérios de Aceitação

- [x] A tabela de transações possui o campo `payment_date`.
- [x] A receita do dashboard utiliza exclusivamente `payment_date`.
- [x] Entradas e saídas permanecem independentes da arrecadação no modelo de dados.
- [x] O dashboard apresenta métricas operacionais e financeiras desacopladas.
- [ ] Existe um endpoint para consultas financeiras por intervalo de datas.
- [ ] Os relatórios utilizam `date_start` e `date_end`.
- [ ] É possível gerar relatórios mensais e anuais sem alteração da lógica de negócio.
- [ ] O sistema permite identificar reservas encerradas sem transação financeira correspondente.
- [ ] A arquitetura suporta futuras implementações de fechamento de caixa e indicadores financeiros avançados.

---

## 9. Decisões de Arquitetura

O nome físico atual da tabela será mantido como `transacoes`, evitando uma
renomeação incompatível com o código existente. Neste projeto:

| Conceito | Tabela/coluna oficial |
|----------|-----------------------|
| Receita efetiva | `transacoes.valor` por `transacoes.payment_date` |
| Entrada | `vagas_preenchidas.hora_entrada` |
| Saída | `vagas_preenchidas.hora_saida` |
| Veículo em aberto | `hora_saida IS NULL` |

`data_transacao` será mantida temporariamente para compatibilidade e auditoria,
mas não poderá ser usada em novas métricas financeiras. Após estabilização e
validação dos relatórios, sua remoção deverá ser tratada em outra migração.

O MySQL do projeto opera em UTC. `payment_date` também será armazenada em UTC.
Datas informadas pelo usuário em `America/Sao_Paulo` deverão ser convertidas no
Controller para limites UTC antes da consulta.

As colunas operacionais existentes foram gravadas pelo PHP no horário local e
não devem ser reinterpretadas como UTC. Até uma migração específica normalizar
essas colunas, as consultas operacionais devem receber limites locais. Misturar
os dois referenciais deslocaria entradas e saídas em três horas.

---

## 10. Migração e Backup

### 10.1 Backup pré-migração

O banco atual, incluindo estrutura e dados, foi salvo em:

```text
heidSQL/parking.sql
```

O arquivo foi gerado com transação consistente e inclui rotinas, triggers e
eventos. Esse arquivo representa o estado anterior à FIN-001 e não deve ser
sobrescrito pelo dump pós-migração.

### 10.2 Estratégia de backfill

Foram identificadas sete transações antigas e nenhuma possui
`data_transacao` nula. O backfill copia `data_transacao` para `payment_date`
sem converter o horário. Isso preserva exatamente a referência temporal
original registrada pelo MySQL em UTC.

A migração está em:

```text
heidSQL/migration-fin-001-payment-date.sql
```

Ordem obrigatória:

1. adicionar `payment_date` permitindo nulo;
2. copiar somente registros em que `payment_date` esteja nula;
3. confirmar que não restou transação sem data de pagamento;
4. tornar a coluna obrigatória;
5. criar os índices financeiros e operacionais;
6. comparar quantidade, menor data e maior data antes e depois.

Em caso de falha antes da restrição `NOT NULL`, o backfill pode ser repetido,
pois atualiza somente valores nulos. Como `ALTER TABLE` causa commit implícito
no MySQL, o rollback deve ser realizado restaurando `heidSQL/parking.sql`.

---

## 11. Consultas por Período

Todas as consultas deverão usar intervalo semiaberto:

```text
payment_date >= :start_utc AND payment_date < :end_utc
```

O limite final exclusivo evita erros com frações de segundo e registros no
último instante do dia ou mês.

### 11.1 Receita efetiva

```sql
SELECT
    COALESCE(SUM(t.valor), 0) AS total_revenue,
    COUNT(*) AS payment_count,
    COALESCE(AVG(t.valor), 0) AS average_ticket
FROM transacoes t
WHERE t.payment_date >= :start_utc
  AND t.payment_date < :end_utc;
```

### 11.2 Entradas

```sql
SELECT COUNT(*) AS entries
FROM vagas_preenchidas
WHERE hora_entrada >= :start_local
  AND hora_entrada < :end_local;
```

### 11.3 Saídas

```sql
SELECT COUNT(*) AS exits
FROM vagas_preenchidas
WHERE hora_saida >= :start_local
  AND hora_saida < :end_local;
```

### 11.4 Veículos em aberto

Para o estado atual:

```sql
SELECT COUNT(*) AS open_vehicles
FROM vagas_preenchidas
WHERE hora_saida IS NULL;
```

Para saber quantos estavam em aberto no final de um período:

```sql
SELECT COUNT(*) AS open_vehicles_at_end
FROM vagas_preenchidas
WHERE hora_entrada < :end_local
  AND (hora_saida IS NULL OR hora_saida >= :end_local);
```

### 11.5 Pendências financeiras

```sql
SELECT v.id_vaga_preenchida, v.placa, v.hora_entrada, v.hora_saida
FROM vagas_preenchidas v
LEFT JOIN transacoes t
    ON t.id_vaga_preenchida = v.id_vaga_preenchida
WHERE v.hora_saida IS NOT NULL
  AND t.id_transacao IS NULL
ORDER BY v.hora_saida DESC;
```

---

## 12. Padrão para Controller e Model

O Controller deve aceitar `period=day`, `period=month` ou `period=custom`.
Para dia e mês, ele calcula os limites a partir de uma única data de referência.
Para o intervalo personalizado, exige `date_start` e `date_end`.

```php
$period = $_GET['period'] ?? 'month';
$timezone = new DateTimeZone('America/Sao_Paulo');
$utc = new DateTimeZone('UTC');

if ($period === 'custom') {
    $start = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $_GET['date_start'] ?? '',
        $timezone
    );
    $lastDay = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $_GET['date_end'] ?? '',
        $timezone
    );

    if (!$start || !$lastDay || $start > $lastDay) {
        http_response_code(422);
        exit('Período inválido.');
    }

    $end = $lastDay->modify('+1 day');
} else {
    $reference = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $_GET['date'] ?? date('Y-m-d'),
        $timezone
    );

    if (!$reference || !in_array($period, ['day', 'month'], true)) {
        http_response_code(422);
        exit('Período inválido.');
    }

    $start = $period === 'day'
        ? $reference
        : $reference->modify('first day of this month');
    $end = $period === 'day'
        ? $start->modify('+1 day')
        : $start->modify('first day of next month');
}

$startUtc = $start->setTimezone($utc)->format('Y-m-d H:i:s');
$endUtc = $end->setTimezone($utc)->format('Y-m-d H:i:s');
$startLocal = $start->format('Y-m-d H:i:s');
$endLocal = $end->format('Y-m-d H:i:s');

$report = (new FinancialReportModel())->getPeriodSummary(
    $startUtc,
    $endUtc
);

$movement = (new OperationalReportModel())->getPeriodSummary(
    $startLocal,
    $endLocal
);
```

No Model, os valores devem ser vinculados; datas nunca devem ser concatenadas
na SQL:

```php
$statement = $this->db->prepare($sql);
$statement->execute([
    'start_utc' => $startUtc,
    'end_utc' => $endUtc,
]);
```

Regras adicionais:

- rejeitar datas que não correspondam exatamente ao formato `Y-m-d`;
- limitar o intervalo personalizado, por exemplo, a 366 dias;
- utilizar lista fechada para `period`; nomes de coluna e ordenação não podem
  vir diretamente do usuário;
- não retornar mensagens de PDO ou SQL ao cliente;
- registrar falhas no servidor com identificador de correlação;
- exigir autenticação e autorização para acessar relatórios financeiros;
- aplicar limite e paginação na listagem analítica de transações.

---

## 13. Plano de Implementação

1. **Banco:** executar e auditar a migração FIN-001.
2. **Escrita:** alterar `insertTransacao()` para receber ou gerar explicitamente
   `payment_date` em UTC.
3. **Modelo financeiro:** criar `FinancialReportModel`, contendo apenas consultas
   sobre `transacoes`.
4. **Modelo operacional:** manter entradas, saídas e veículos em aberto em modelo
   separado, sem somar valores financeiros.
5. **Dashboard:** substituir a consulta atual que usa entrada/saída pela soma
   exclusiva de `payment_date`.
6. **Endpoint:** criar `GET /reports/financial` com filtros validados e prepared
   statements.
7. **Testes:** cobrir mudança de dia, virada de mês, ano, horário de verão,
   intervalo inválido, ausência de pagamentos e veículo que entra em um dia e
   paga ou sai em outro.
8. **Observabilidade:** comparar o relatório antigo e o novo durante um período
   controlado, documentando divergências esperadas antes de remover
   `data_transacao`.
