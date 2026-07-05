# Bug-Fix: Falha de Permissão em Upload — Tratativas Realizadas

Arquivo-base:
`docs/specs/spec-bugfix-permission-mv-upload.md`

Escopo deste documento: registrar somente medidas já presentes no projeto em
05/07/2026. A presença de uma medida não significa que o bug esteja resolvido.

## 1. ID/Título do Bug

**BUG-UPLOAD-001 — Falha de permissão em `move_uploaded_file()`**

## 2. Contexto

O método `ProfileController::uploadPhoto()` recebe a foto do usuário autenticado,
move o arquivo para `public/uploads/` e atualiza o nome da foto no MySQL.

## 3. Problema

O retorno de `move_uploaded_file()` é verificado. Quando a operação falha, o fluxo
é interrompido com uma mensagem genérica:

```php
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    die("Erro ao mover arquivo de upload.");
}
```

Essa medida evita a atualização do banco após a falha, mas não impede a emissão
dos warnings do PHP e não registra o diagnóstico em log.

## 4. Causa Raiz Identificada

O projeto já contém ajuste de propriedade durante o build:

```dockerfile
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/public
```

Entretanto, o `compose.yaml` monta `.:/var/www/html`. Esse bind mount substitui
as permissões da imagem pelas permissões do host. Na verificação realizada,
`public/uploads/` estava como `775 devops:devops`, sem escrita disponível ao
usuário `www-data`.

## 5. Tratativas Realizadas

- [x] Definição do caminho de destino em `public/uploads/`.
- [x] Verificação da existência de `public/uploads/` antes do upload.
- [x] Tentativa de criação do diretório quando ele não existe.
- [x] Verificação do código de erro informado por `$_FILES`.
- [x] Restrição de extensões para JPG, JPEG, PNG e WEBP.
- [x] Geração de nome no servidor com ID do usuário e timestamp.
- [x] Verificação do retorno de `move_uploaded_file()`.
- [x] Atualização do MySQL somente após o arquivo ser movido.
- [x] Aplicação de `chown` para `www-data` durante o build da imagem.
- [x] Volume nomeado `swiftly-uploads` dedicado ao armazenamento das fotos.
- [x] Inicialização do volume como `750 www-data:www-data`.
- [x] Validação de escrita com `is_writable()`.
- [x] Validação do arquivo temporário com `is_uploaded_file()`.
- [x] Limite de arquivo de 5 MB.
- [x] Validação do MIME real com `finfo`.
- [x] Nome aleatório gerado com `random_bytes()`.
- [x] Supressão controlada do warning e registro do detalhe com `error_log()`.
- [x] Remoção do arquivo quando a atualização no MySQL falha.
- [x] Teste de escrita no volume executado como `www-data`.

## 6. Implementação Existente

```php
$uploadDir = dirname(__DIR__, 2) . '/public/uploads';

if ((!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true) && !is_dir($uploadDir))
    || !is_writable($uploadDir)
) {
    error_log("Diretório de upload indisponível ou sem escrita: {$uploadDir}");
    http_response_code(500);
    exit("Não foi possível salvar a foto.");
}

$destPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

if (!@move_uploaded_file($file['tmp_name'], $destPath)) {
    $lastError = error_get_last();
    error_log('Falha ao mover foto: ' . ($lastError['message'] ?? 'erro desconhecido'));
    http_response_code(500);
    exit("Não foi possível salvar a foto.");
}
```

## 7. Pendências Fora do Escopo deste Registro

As seguintes ações permanecem pendentes e, por isso, não foram apresentadas como
tratativas realizadas:

- adicionar e validar proteção CSRF;
- comprovar o fluxo completo com usuário autenticado pela interface;
- comprovar o upload em execução local fora do Docker.
