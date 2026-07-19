# REFACTOR-035 — Recibo por e-mail no checkout

## Controle

- Branch: `feature/checkout-email-receipt`
- Base: `develop`
- Data: 19/07/2026
- Estado: concluído e integrado à `develop`

## Objetivo

Enviar um recibo operacional ao e-mail do usuário autenticado que concluiu a
saída de um veículo, tanto para estadias rotativas quanto mensalistas.

## Implementação

- O checkout monta o recibo com empresa, operador, cliente, placa, horários,
  duração, modelo de cobrança, pagamento e valor.
- O job `mail.checkout_receipt` é inserido na fila `mail` dentro da mesma
  transação que fecha a estadia e registra o pagamento.
- O worker processa o recibo de forma assíncrona usando o SMTP configurado no
  ambiente por `config/env.php`.
- O rotativo apresenta valor e forma de pagamento; o mensalista informa que a
  estadia está coberta pelo contrato e não cria uma nova cobrança.
- A interface confirma que o recibo foi encaminhado ao e-mail do operador.
- O recibo declara expressamente que não possui valor fiscal.

## Consistência

A inserção do job participa da transação do checkout. Portanto, um checkout que
sofra rollback não deixa um recibo pendente. Depois do commit, indisponibilidade
do SMTP não reabre a vaga: o worker realiza até cinco tentativas com backoff.

## Segurança

- O payload contendo e-mail e dados da estadia é cifrado com AES-256-GCM.
- O worker limpa o payload depois da entrega.
- O destinatário é validado com `FILTER_VALIDATE_EMAIL`.
- Todos os valores textuais são sanitizados e escapados antes de entrar no HTML.
- A mensagem não inclui telefone do cliente.

## Testes

```bash
php tests/CheckoutReceiptEmailTest.php
php tests/BackgroundJobQueueTest.php
php tests/SecurityTestSuite.php
```

Validação manual:

1. Confirmar que o serviço `worker` está ativo.
2. Entrar com um usuário que possua e-mail válido.
3. Finalizar uma estadia rotativa e conferir valor e forma de pagamento.
4. Finalizar uma estadia mensalista e conferir a indicação de contrato mensal.
5. Consultar `background_jobs` e confirmar o estado `COMPLETED`.
6. Conferir o recebimento no e-mail do usuário que realizou cada saída.

## Retomada

Após validar, finalizar a feature, criar a tag
`refactor-035-checkout-email-receipt` e permanecer em `develop`.
