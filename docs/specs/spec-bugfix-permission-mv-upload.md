# Bug-Fix: Falha de Permissão em Upload de Arquivos

## 1. Overview

Esta especificação documenta a correção da falha de permissão durante o upload de arquivos para a aplicação.

O objetivo é garantir que arquivos enviados pelos usuários sejam armazenados corretamente no diretório público de uploads, tratando falhas de permissão de maneira segura e evitando mensagens de erro nativas do PHP.

---

## 2. Contexto

O sistema deve permitir o upload de arquivos (como fotos de perfil e documentos), salvando-os no diretório:

```text
/public/uploads/
```

Os arquivos enviados devem permanecer acessíveis publicamente após a conclusão do upload.

---

## 3. Problema

Atualmente, a aplicação lança o erro:

```text
Permission denied
```

durante a execução de:

```php
move_uploaded_file(...)
```

O erro ocorre porque o processo responsável pela execução do PHP não possui permissão de escrita no diretório de destino.

---

## 4. Causa Raiz

### Permissões do Sistema

O diretório `/public/uploads/` pertence a outro usuário (como `root`) ou não possui permissões adequadas para o usuário responsável pela execução do servidor web (por exemplo, `www-data`).

---

### Ambiente Docker

Quando executado em containers Docker, o volume montado pode preservar as permissões do host, impedindo que o processo interno do container grave arquivos no diretório compartilhado.

---

## 5. Plano de Ação

### 5.1 Correção Imediata

Ajustar o proprietário e as permissões do diretório responsável pelos uploads.

Objetivos:

- garantir permissão de escrita ao processo do PHP;
- manter acesso controlado ao diretório;
- evitar alterações manuais recorrentes após novos deployments.

---

### 5.2 Validação Antes do Upload

Antes da chamada para:

```php
move_uploaded_file(...)
```

o sistema deverá validar:

- existência do diretório;
- permissão de escrita;
- disponibilidade do arquivo temporário.

Caso alguma validação falhe, a operação deverá ser interrompida de forma controlada.

---

### 5.3 Tratamento de Erros

Falhas de upload não deverão expor warnings do PHP ao usuário.

O sistema deverá:

- capturar a exceção ou falha da operação;
- registrar o erro em log;
- retornar uma resposta apropriada para a API ou interface da aplicação.

---

## 6. Critérios de Aceitação

- [x] O diretório `/public/uploads/` possui permissões adequadas para gravação.
- [ ] O upload de arquivos é concluído com sucesso quando as permissões estão corretas.
- [x] O sistema valida as pré-condições antes da execução de `move_uploaded_file()`.
- [x] Falhas de permissão não exibem warnings do PHP diretamente ao usuário.
- [x] Erros são registrados em log para fins de auditoria e diagnóstico.
- [ ] A solução é compatível tanto com execução local quanto em ambientes Docker.

### Verificação da Implementação — 05/07/2026

Critérios marcados somente quando comprovados:

- o volume `swiftly-uploads` foi criado e montado em `public/uploads/`;
- dentro do container, o diretório está como `750 www-data:www-data`;
- `test -w`, executado como `www-data`, confirmou permissão de escrita;
- o controller valida diretório, escrita, arquivo temporário, tamanho e MIME real;
- warnings de `move_uploaded_file()` não são enviados na resposta e o detalhe é
  registrado com `error_log()`;
- a sintaxe PHP e a configuração do Compose foram validadas.

Permanecem pendentes o teste funcional autenticado pela interface e a
comprovação formal em execução local fora do Docker.
