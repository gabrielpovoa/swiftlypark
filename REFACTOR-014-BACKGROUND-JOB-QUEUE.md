# REFACTOR-014 — Fila de jobs em background

## Identificação

- Branch: `feature/add-background-job-queue`
- Base: `develop` após a conclusão da feature 013
- Estado: concluída
- Feature anterior: `REFACTOR-013-CONCURRENCY-TESTS.md`

## Objetivo

Remover envio SMTP do ciclo HTTP usando uma fila durável MySQL, worker CLI,
retry e payload criptografado para OTP/senhas temporárias.

## Invariantes

- Requisição apenas enfileira; worker executa SMTP.
- Credenciais nunca são persistidas em texto puro.
- Reserva de job é atômica e segura para múltiplos workers.
- Falha incrementa tentativa e reagenda com backoff.
- Após `max_attempts`, job fica `FAILED` com erro sanitizado.
- Jobs concluídos não são executados novamente.

## Implementado

- Migration `20260727_create_background_jobs.sql` com estados, tentativas,
  disponibilidade, reserva e índices.
- `PdoJobQueue` implementa enqueue, reserva com `FOR UPDATE SKIP LOCKED`,
  conclusão e retry exponencial.
- `EncryptedPayload` usa AES-256-GCM com nonce aleatório, tag autenticada e
  chave derivada de `JOB_QUEUE_KEY`.
- `QueuedPasswordRecoveryMailer` substitui SMTP síncrono em criação,
  reativação, redefinição administrativa e OTP.
- `JobWorker` decripta e despacha para o mailer SMTP real; `cli/worker.php`
  suporta processo contínuo e `--once`.
- Ao concluir, o payload criptografado é apagado do banco.
- Erros persistidos passam por remoção de tags e limite de 1.000 caracteres.
- Compose recebe chave local explícita; produção deve obrigatoriamente definir
  `JOB_QUEUE_KEY` própria com ao menos 24 caracteres.
- Testes unitários cobrem cifra, adulteração e dispatch com fake.
- Teste MySQL cobre `SKIP LOCKED`, retry final, sanitização e limpeza do payload.

## Evidência local

```text
Background job queue test passed
Background job MySQL concurrency/retry test passed
Completed job payload cleanup passed
```

O roteiro completo de validação está em
`ARCHITECTURE-REFACTOR-TESTING.md`.

## Operação

```bash
# Worker contínuo
docker compose exec app php cli/worker.php

# Processar no máximo um job (cron/diagnóstico)
docker compose exec app php cli/worker.php --once
```

## Próximo passo exato

1. Executar suíte unitária e integração completas.
2. Atualizar roadmap e documentação final de testes.
3. Finalizar via Git Flow.
