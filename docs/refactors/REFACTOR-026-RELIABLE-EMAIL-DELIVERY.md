# REFACTOR-026 — Entrega confiável de e-mails assíncronos

## Contexto

Criação e reativação de usuários, emissão de senha temporária e recuperação por
OTP gravavam jobs na tabela `background_jobs`, mas o ambiente Docker não possuía
um processo consumidor. A interface confirmava envio mesmo quando apenas o
enfileiramento havia ocorrido.

## Diagnóstico confirmado

- os jobs permaneciam `PENDING`, com zero tentativas;
- `cli/worker.php` não carregava `config/env.php` nem o `.env`;
- não existia serviço `worker` no `compose.yaml`;
- as credenciais SMTP, embora presentes no `.env`, não chegavam ao processo CLI;
- horários de infraestrutura são persistidos em UTC; `14h UTC` corresponde a
  `11h` em `America/Sao_Paulo` na data investigada.

## Implementação

- adicionado serviço Docker `worker`, persistente e reiniciado automaticamente;
- o worker carrega `config/env.php` e chama `loadEnv()` para reutilizar o `.env`;
- SMTP passou a respeitar `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`,
  `MAIL_PASSWORD`, `MAIL_FROM_NAME` e `MAIL_ENCRYPTION`;
- configuração SMTP incompleta gera falha explícita no job, sem expor segredos;
- templates agora são enviados corretamente como HTML, mantendo `AltBody`;
- confirmações da interface agora informam que a mensagem foi adicionada à fila;
- adicionado teste de regressão da infraestrutura de entrega.

## Segurança e operação

- nenhuma credencial foi copiada para código, Compose, teste ou documentação;
- payloads sensíveis continuam criptografados na fila;
- jobs concluídos continuam tendo o payload apagado;
- datas da fila e do OTP continuam em UTC, evitando ambiguidades entre processos.

## Como ativar

```bash
docker compose up -d --build worker
docker compose ps worker
docker compose logs --tail=100 worker
```

Ao iniciar, o worker consumirá também os jobs pendentes existentes. Antes disso,
é recomendável invalidar ou remover operacionalmente mensagens antigas cujas
senhas já tenham sido substituídas.

## Validação

```bash
php tests/ReliableEmailDeliveryTest.php
php tests/BackgroundJobQueueTest.php
php tests/GovernanceUserManagementTest.php
php tests/SecurityTestSuite.php
```

O worker não foi iniciado durante a validação automática para evitar o envio não
solicitado das credenciais que já estavam pendentes.

## Retomada

- branch de implementação: `feature/reliable-email-delivery`;
- base: `develop`;
- próximo passo operacional: decidir o destino dos jobs antigos e então iniciar
  o serviço `worker`.
