# Spec-AUTH-002 — Implementação da recuperação de senha via OTP

## 1. Objetivo e estado final

O fluxo anterior permitia a redefinição direta da senha após a confirmação da existência de um e-mail. Além de expor a existência da conta, ele não comprovava que a pessoa controlava o endereço informado. A implementação final substitui esse comportamento por um processo em três etapas: solicitação do código, validação do OTP e redefinição autorizada da senha.

A solução segue PSR-1, PSR-4 e PSR-12. Classes e namespaces respeitam o mapeamento de autoload do Composer, cada classe possui uma responsabilidade definida e as dependências seguem a direção controlador, serviço e repositórios, sem referências circulares.

## 2. Arquitetura

### 2.1 Camada HTTP

O controlador de recuperação recebe e valida os dados básicos das requisições, mantém o estado temporário da autorização na sessão, seleciona a etapa apresentada na interface e traduz falhas de domínio em mensagens apropriadas. Ele não gera códigos, não produz hashes, não executa consultas e não conhece detalhes do protocolo SMTP.

As rotas públicas são separadas por ação:

- apresentação da solicitação;
- envio da solicitação;
- validação do OTP;
- apresentação do formulário autorizado;
- efetivação da nova senha.

O formulário de nova senha não é disponibilizado apenas por conhecimento da URL. Sua apresentação e seu processamento exigem uma autorização ativa na sessão.

### 2.2 Camada de serviço

O serviço de OTP coordena geração, expiração, validação, limites de uso, emissão da autorização temporária e consumo definitivo da recuperação. A geração utiliza uma fonte criptograficamente segura e mantém o formato numérico de cinco dígitos definido na especificação original.

O serviço também atua como limite transacional do último estágio. A atualização da senha e a exclusão da solicitação são confirmadas em uma única transação. Uma falha em qualquer uma dessas operações reverte ambas, impedindo estado parcial.

O envio de mensagens depende de um contrato próprio. O adaptador SMTP implementa esse contrato, de modo que transporte de e-mail, testes e futuras integrações possam mudar sem alterar a regra de OTP.

### 2.3 Camada de acesso a dados

O repositório de recuperação é o único componente responsável pelas operações na tabela `password_resets`. Ele cria ou substitui solicitações, localiza autorizações, contabiliza tentativas, registra a autorização temporária, consome registros e remove dados expirados.

O repositório de usuário possui somente as operações de conta necessárias ao caso de uso: verificar a existência pelo e-mail e atualizar o hash da senha. Essa separação evita que detalhes da tabela de usuários contaminem a persistência do OTP.

### 2.4 Persistência

A tabela `password_resets` mantém um registro ativo por e-mail. Além dos campos originalmente especificados, foram adicionados dados para controlar a janela de solicitações, a verificação do OTP e a autorização de redefinição:

- contador e início da janela de solicitações;
- instante de validação do OTP;
- hash e expiração do token temporário de redefinição.

Os índices de expiração dão suporte à rotina de limpeza. A unicidade do e-mail evita múltiplos códigos ativos concorrentes para a mesma conta. A migração está isolada no diretório de migrações do projeto.

Todos os instantes do fluxo de recuperação são persistidos e comparados em UTC. Essa decisão evita expiração antecipada ou tardia quando aplicação, banco de dados e infraestrutura utilizam fusos horários diferentes.

## 3. Fluxo de dados

### 3.1 Solicitação

O controlador valida apenas o formato do e-mail e entrega o valor normalizado ao serviço. Para uma conta existente e não limitada, o serviço gera o OTP, cria seu hash, substitui qualquer solicitação anterior e envia o valor original por e-mail. O valor original existe somente em memória durante essa operação.

A interface sempre apresenta a mesma confirmação para um endereço sintaticamente válido, exista ou não uma conta, tenha ocorrido limitação ou o envio tenha falhado. Falhas operacionais são destinadas ao log e não alteram a resposta pública.

### 3.2 Validação

O serviço consulta a solicitação pelo e-mail, verifica expiração e limite de tentativas e contabiliza cada tentativa antes de comparar o código. A comparação é feita contra o hash persistido. Erros de inexistência, expiração, bloqueio e divergência resultam na mesma mensagem pública.

Quando o OTP é válido, o serviço gera um token aleatório de alta entropia. Somente seu hash é persistido. O token original, o identificador da solicitação e a expiração ficam na sessão do usuário. A sessão recebe um novo identificador após a validação.

### 3.3 Redefinição

O processamento exige simultaneamente a autorização na sessão, sua validade temporal e a correspondência com o hash armazenado. A nova senha deve possuir ao menos doze caracteres e coincidir com a confirmação.

Depois da atualização transacional, o registro de recuperação é removido, a autorização é apagada da sessão e o identificador da sessão é renovado. Assim, OTP e token não podem ser reutilizados.

## 4. Controles de segurança

### 4.1 Proteção contra enumeração

A existência da conta nunca é revelada na solicitação. A confirmação pública também não distingue limitação, falha de transporte ou endereço inexistente. O tempo de resposta ainda pode variar conforme o envio SMTP; em ambientes com maior exposição, recomenda-se mover o envio para uma fila assíncrona para uniformizar latência e aumentar resiliência.

### 4.2 Proteção do OTP e do token

O OTP é armazenado com o mecanismo de hash de senha padrão da plataforma. O token de redefinição possui alta entropia, é armazenado como resumo criptográfico e comparado de forma resistente a diferenças de tempo. Nenhum segredo recuperável é gravado.

O OTP expira em dez minutos e aceita no máximo três tentativas. A autorização de redefinição também expira em dez minutos.

### 4.3 Rate limiting

Uma nova mensagem não é emitida antes de sessenta segundos. Cada e-mail pode originar no máximo três envios dentro de uma janela de quinze minutos. A limitação é aplicada silenciosamente para preservar a resposta cega.

Esse controle protege a conta e o canal de e-mail. Para implantação pública em múltiplas instâncias, recomenda-se complementar a regra com limitação por endereço IP em armazenamento compartilhado ou no gateway HTTP.

### 4.4 Sessão e requisições

Todas as operações mutáveis exigem token CSRF vinculado à sessão. A autorização de redefinição nunca é recebida de campos editáveis do formulário. Recomenda-se configurar cookies de sessão com as opções `HttpOnly`, `Secure` e `SameSite` no ambiente de produção e servir o fluxo exclusivamente por HTTPS.

### 4.5 Senha e atomicidade

A nova senha é protegida pelo algoritmo padrão da plataforma, permitindo evolução segura do algoritmo conforme a versão do PHP. Atualização da credencial e consumo da recuperação são atômicos.

## 5. Tratamento de falhas

Falhas esperadas de OTP e autorização possuem exceções de domínio específicas. O controlador fornece mensagens neutras ao usuário. Exceções de infraestrutura não são expostas e são encaminhadas ao log.

O serviço reverte a transação em qualquer falha durante a redefinição. A dependência do serviço de e-mail por interface permite substituir o SMTP por um adaptador falso em testes sem acesso à rede.

## 6. Housekeeping e operação

Existe uma rotina de linha de comando dedicada à exclusão de solicitações e autorizações expiradas. Ela deve ser agendada pelo ambiente operacional, preferencialmente a cada cinco minutos. A rotina é idempotente e informa apenas a quantidade de registros removidos.

Antes da disponibilização da funcionalidade, a migração da tabela `password_resets` deve ser executada. O ambiente também precisa fornecer credenciais SMTP válidas e executar a rotina de limpeza de forma recorrente.

## 7. Requisitos funcionais atendidos

- recuperação dividida em solicitação, validação e redefinição;
- resposta cega para e-mails válidos;
- OTP numérico de cinco dígitos com expiração;
- persistência exclusiva do hash do OTP;
- limite de tentativas de validação e de solicitações;
- autorização temporária obrigatória;
- senha com hash seguro;
- consumo único e transacional da recuperação;
- limpeza automatizável de registros expirados;
- responsabilidades separadas entre HTTP, serviço, transporte e persistência.

## 8. Decisões para evolução

O tamanho de cinco dígitos foi mantido por compatibilidade estrita com a especificação original. Caso não exista essa restrição de produto, seis dígitos aumentam o espaço de busca e são preferíveis.

Os limites estão centralizados no serviço. Uma evolução recomendada é transferi-los para configuração validada no início da aplicação. Para escala horizontal, sessão, filas e rate limiting devem utilizar serviços compartilhados.
