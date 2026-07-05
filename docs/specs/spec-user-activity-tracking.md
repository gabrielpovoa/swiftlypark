# Spec-AUDIT-001 — Rastreabilidade de operações e logs de auditoria

## 1. Objetivo

Esta especificação define a infraestrutura de rastreabilidade do SwiftlyPark para identificar quem criou, alterou ou excluiu registros operacionais. A primeira aplicação cobre especialmente a entrada e a saída de veículos, a alteração do estado de vagas e a criação de transações financeiras.

A solução deve registrar operações de criação, atualização e exclusão automaticamente, sem lógica de auditoria nos Controllers e sem confiar em identificadores enviados pelo navegador.

Esta etapa descreve arquitetura, contratos e fluxo. Ela não disponibiliza consulta de auditoria ao usuário e não adiciona dados de sessão às respostas, Views ou formulários.

## 2. Diagnóstico da estrutura atual

O login já armazena o identificador, e-mail, nome e foto do usuário na sessão do servidor. As rotas privadas verificam apenas se existe um identificador autenticado antes de executar o Controller.

Os Models operacionais abrem suas próprias conexões e controlam diretamente suas transações. Entrada de veículo, criação da transação e mudança do estado da vaga já podem ocorrer juntas, mas não recebem um contexto de identidade e não deixam uma trilha que relacione cada mudança ao usuário responsável.

Também não existe uma camada real de Middleware. A função de proteção declarada nas rotas combina inicialização de sessão, autenticação, redirecionamento e execução do Controller. Ela deverá evoluir para uma cadeia explícita de Middleware.

O relatório operacional existente não equivale a auditoria. Ele apresenta dados do estacionamento, mas não preserva necessariamente o estado anterior, o estado posterior e a identidade responsável por cada mudança.

## 3. Princípios adotados

- A identidade é obtida exclusivamente da sessão no servidor.
- O cliente nunca informa `user_id`, `created_by` ou `updated_by`.
- O contexto autenticado é imutável durante a requisição.
- A operação principal e seus logs utilizam a mesma conexão e a mesma transação.
- Falha na auditoria provoca rollback da operação principal.
- Logs confirmados são somente de inserção e não podem ser alterados pela aplicação.
- Campos sensíveis são removidos ou mascarados antes da serialização.
- Controllers coordenam HTTP; Services coordenam casos de uso; Repositories persistem entidades.
- A infraestrutura de auditoria não contém regra de negócio de estacionamento.

## 4. Contexto de identidade

### 4.1 IdentityMiddleware

O `IdentityMiddleware` será executado antes do Controller em todas as rotas privadas e mutáveis. Suas responsabilidades são:

- iniciar ou reutilizar a sessão com configuração segura;
- verificar a presença do identificador autenticado;
- validar que o identificador possui formato e valor aceitáveis;
- construir o contexto da requisição;
- disponibilizar o contexto às dependências do caso de uso;
- interromper a cadeia com `UnauthorizedException` quando a sessão for inválida.

O Middleware não consulta nem grava entidades operacionais e não produz logs de auditoria.

### 4.2 RequestIdentity

O contexto será representado por um objeto imutável chamado `RequestIdentity`. Ele contém somente os dados necessários à infraestrutura:

- identificador interno do usuário;
- e-mail autenticado, para facilitar investigação e preservar referência histórica;
- endereço IP normalizado;
- identificador único da requisição;
- instante de início da requisição em UTC.

O identificador interno continua sendo a referência principal. O e-mail será armazenado como uma fotografia do ator no momento da ação, evitando que uma futura alteração do cadastro elimine a informação histórica.

### 4.3 Disponibilização segura

A opção preferencial é injetar `RequestIdentity` nos Services e componentes transacionais criados para a requisição. Isso torna a dependência explícita e simplifica testes.

Como o Router atual instancia Controllers diretamente, poderá existir temporariamente um `IdentityContext` com escopo de requisição. Esse Registry:

- aceita inicialização uma única vez pelo Middleware;
- não permite substituir a identidade durante a requisição;
- lança `UnauthorizedException` quando acessado sem identidade;
- é limpo ao fim da execução;
- não expõe setters públicos para Controllers ou payloads.

Ele não deve se tornar um Singleton de identidade compartilhada entre requisições em servidores persistentes. A evolução desejada é um contêiner com escopo de requisição e injeção explícita.

### 4.4 Endereço IP

Por padrão será usado o endereço fornecido diretamente pelo servidor web. Cabeçalhos de proxy somente poderão ser considerados quando a aplicação estiver atrás de proxies previamente confiáveis. Isso impede que o cliente falsifique o IP auditado.

## 5. Componentes

### 5.1 AuditEntry

`AuditEntry` será um objeto imutável que representa uma intenção de auditoria. Ele contém:

- ator e e-mail do ator;
- ação;
- entidade e identificador da entidade;
- estado anterior e posterior já normalizados;
- IP e identificador da requisição;
- instante em UTC.

Esse objeto não conhece banco, sessão, HTTP ou regras de estacionamento.

### 5.2 AuditSanitizer

O `AuditSanitizer` define uma lista permitida de campos por entidade. Ele remove credenciais, hashes, tokens, segredos, conteúdo de sessão e outros dados que não devem entrar no histórico.

Essa filtragem acontece antes da codificação JSON. A lista permitida é preferível a uma lista de bloqueio porque novos campos sensíveis não passam a ser gravados automaticamente.

### 5.3 AuditService

O `AuditService` recebe o contexto de identidade e os estados fornecidos pela persistência. Ele:

- valida ação, entidade e identificador;
- normaliza tipos e datas;
- aplica o `AuditSanitizer`;
- calcula o conjunto efetivamente alterado em operações de atualização;
- cria a entrada de auditoria;
- solicita sua inserção ao `AuditLogRepository`.

O serviço não abre nem confirma transações. Também não consulta novamente a entidade para descobrir o estado anterior; essa leitura pertence ao Repository da entidade, que já conhece sua tabela e chave.

Em uma atualização, o serviço registra preferencialmente apenas os campos cujo valor mudou. Isso reduz volume e torna o de-para mais legível.

### 5.4 AuditLogRepository

O `AuditLogRepository` possui uma única operação de escrita: inserir uma entrada. A aplicação não terá operações de atualização ou exclusão para `audit_logs`.

Ele recebe a conexão transacional ativa. Não pode criar uma segunda conexão, iniciar uma transação independente ou confirmar a transação.

Falhas de preparação, serialização ou inserção são convertidas em `AuditLogException`, preservando a exceção original como causa.

### 5.5 Repositories operacionais

Cada entidade operacional terá um contrato de Repository. O Repository concreto conhece SQL, chave primária, mapeamento de dados e bloqueio concorrente, mas não acessa sessão.

Nas escritas ele recebe o identificador do ator por meio do contexto transacional e preenche:

- `created_by` e `updated_by` na criação;
- `updated_by` na atualização;
- nenhum campo recebido do formulário para essas colunas.

Para exclusão, o estado anterior e o usuário ficam preservados no log mesmo que o registro deixe de existir.

### 5.6 Transactional Audit Decorator

A auditoria transparente será aplicada por Decorators dos Repositories operacionais. O Decorator implementa o mesmo contrato do Repository e envolve as operações mutáveis.

Suas responsabilidades são:

- obter o estado anterior por meio do Repository;
- delegar a persistência;
- obter ou receber o estado final confirmado pela escrita;
- enviar os estados ao `AuditService`;
- manter as leituras comuns sem efeitos colaterais.

O Repository base continua focado em persistência. O Decorator acrescenta rastreabilidade sem obrigar Controllers ou Services de negócio a chamar auditoria manualmente.

Não será utilizado um gatilho global implícito em uma classe base de Repository, pois entidades distintas possuem campos sensíveis, chaves e regras de snapshot diferentes.

### 5.7 TransactionManager e Unit of Work

O `TransactionManager` é o único responsável por iniciar, confirmar e reverter a transação. Ele fornece a mesma conexão aos Repositories operacionais e ao `AuditLogRepository`.

O Service de caso de uso funciona como Unit of Work no nível da operação. Por exemplo, “registrar entrada” agrupa:

- criação da ocupação;
- criação da transação financeira;
- mudança do estado da vaga;
- criação dos logs correspondentes.

Cada mutação gera sua própria entrada de auditoria e todas compartilham o mesmo `request_id`. Isso permite reconstruir a ação do usuário como uma operação composta.

Somente depois de todas as persistências e auditorias terem sucesso o `TransactionManager` confirma a transação.

## 6. Esquema de dados

### 6.1 Tabela `audit_logs`

| Coluna | Tipo proposto | Regra |
|---|---|---|
| `id` | BIGINT UNSIGNED | Chave primária incremental |
| `user_id` | INT | Identificador do ator |
| `actor_email` | VARCHAR(255) | Fotografia do e-mail no instante da ação |
| `action` | VARCHAR(10) | `CREATE`, `UPDATE` ou `DELETE` |
| `entity` | VARCHAR(100) | Nome lógico estável da entidade |
| `entity_id` | VARCHAR(64) | Identificador convertido para texto |
| `old_values` | JSON, anulável | Estado anterior sanitizado |
| `new_values` | JSON, anulável | Estado posterior sanitizado |
| `ip_address` | VARCHAR(45) | IPv4 ou IPv6 normalizado |
| `request_id` | CHAR(36) | Correlação das alterações da mesma requisição |
| `created_at` | DATETIME(6) | Instante UTC gerado no servidor |

Para `CREATE`, `old_values` será nulo. Para `DELETE`, `new_values` será nulo. Para `UPDATE`, ambos existirão e conterão o de-para dos campos efetivamente modificados.

Os índices recomendados são:

- usuário e data;
- entidade, identificador e data;
- identificador da requisição;
- data para consultas operacionais e políticas de retenção.

`user_id` deve permanecer obrigatório. A política de exclusão de usuários deve restringir a remoção física ou preservar o usuário como inativo, evitando perda de integridade histórica. O e-mail duplicado no log é intencional e não substitui a referência por ID.

### 6.2 Colunas nas tabelas operacionais

Na primeira fase, recebem `created_by` e `updated_by`:

- `vagas_disponiveis`;
- `vagas_preenchidas`;
- `transacoes`.

As duas colunas referenciam o usuário responsável. `created_by` é obrigatório para novos registros criados após a migração. `updated_by` poderá iniciar nulo e será preenchido na primeira alteração.

Para dados legados, a migração deve ocorrer em etapas:

1. adicionar colunas anuláveis;
2. definir um usuário técnico identificado como migração ou legado;
3. preencher registros existentes;
4. aplicar as restrições definitivas.

O e-mail não deve ser repetido em todas as tabelas operacionais. Essas tabelas usam o ID; o `actor_email` fica no log como fotografia histórica.

## 7. Atomicidade e concorrência

### 7.1 Ordem da atualização

Dentro da transação, o Repository lê o registro com bloqueio de escrita, preserva o snapshot anterior, executa a alteração com `updated_by`, confirma a quantidade de linhas afetadas e obtém o estado posterior.

O Decorator então solicita a auditoria. Se o log for inserido, a Unit of Work pode continuar. Se qualquer etapa falhar, todo o conjunto é revertido.

O bloqueio impede que duas operações concorrentes capturem o mesmo estado anterior e produzam um histórico incoerente.

### 7.2 Falha da operação principal

Se a entidade não puder ser persistida, a auditoria não é solicitada e a transação é revertida. Portanto, não existem logs de alterações que nunca ocorreram.

### 7.3 Falha da auditoria

Se a entidade for alterada temporariamente, mas a auditoria falhar antes do commit, `AuditLogException` sobe até o limite transacional. O `TransactionManager` executa rollback, desfazendo também a alteração da entidade.

A política é fail-closed: uma operação auditável não é confirmada sem seu log.

## 8. Exceções e respostas

### 8.1 UnauthorizedException

Representa ausência ou invalidade da identidade autenticada. É lançada antes do Controller pelo `IdentityMiddleware`. A camada HTTP converte essa falha em redirecionamento para login ou resposta HTTP 401, conforme o tipo da rota.

### 8.2 AuditLogException

Representa falha específica na preparação ou persistência da auditoria. Ela contém uma mensagem operacional segura, a causa técnica e o `request_id` para correlação.

Detalhes de banco, SQL e valores auditados não são enviados ao usuário. A camada HTTP retorna uma mensagem genérica e o erro técnico segue para o log interno da aplicação.

### 8.3 Persistência e conflito

Falhas da entidade permanecem exceções de persistência. Registro inexistente ou alterado concorrentemente deve gerar uma exceção de conflito própria. A auditoria não mascara essas causas.

## 9. Privacidade, imutabilidade e acesso

Não serão criados Controller, rota, menu ou View de consulta de `audit_logs` nesta fase. Os dados também não serão anexados à sessão. A sessão fornece somente a identidade de origem durante a requisição.

Mesmo usuários autenticados não conseguem consultar a trilha pelo sistema. Uma futura tela de auditoria exigirá permissão administrativa específica, paginação, filtros, mascaramento e registro da própria consulta.

A credencial usada pela aplicação deve possuir permissão de inserção e leitura controlada na tabela, mas não de atualização ou exclusão. Quando possível, essa garantia será reforçada por um usuário de banco dedicado e por política de retenção externa à aplicação.

Nunca serão auditados:

- hashes ou textos de senha;
- OTPs e tokens de redefinição;
- cookies e identificadores de sessão;
- credenciais SMTP ou banco;
- conteúdo integral de cabeçalhos HTTP;
- dados sensíveis não necessários à investigação.

## 10. Fluxos operacionais

### 10.1 Entrada de veículo

O caso de uso “registrar entrada” constitui uma única Unit of Work. Ele cria a ocupação, cria a transação e altera a vaga de livre para reservada. Os três registros recebem os campos de autoria adequados e originam três logs correlacionados pelo mesmo `request_id`.

Se um desses registros ou logs falhar, nenhum deles é confirmado.

### 10.2 Saída de veículo

O caso de uso “registrar saída” atualiza a ocupação com horário de saída e tempo total e altera a vaga de reservada para livre. Ambas as mudanças recebem `updated_by` e geram logs correlacionados.

O estado anterior permite verificar quem estava estacionado, quando entrou e qual era o estado da vaga. O estado posterior registra a conclusão da permanência e a liberação da vaga.

## 11. Fluxo detalhado de UPDATE de veículo

1. O Router encontra a rota privada de atualização.
2. O `IdentityMiddleware` inicia a sessão e valida o usuário.
3. O Middleware cria `RequestIdentity` com ID, e-mail, IP, `request_id` e instante UTC.
4. O contexto é anexado ao escopo interno da requisição, sem ser enviado à View ou ao navegador.
5. O Controller valida formato e presença dos dados HTTP.
6. O Controller chama o Service de caso de uso sem informar identidade, autoria ou auditoria manualmente.
7. O Service aplica as regras de negócio e solicita ao `TransactionManager` uma Unit of Work.
8. O `TransactionManager` inicia a transação e fornece a mesma conexão a todos os Repositories participantes.
9. O Decorator do Repository de veículo solicita o estado atual com bloqueio.
10. O Repository base atualiza apenas os campos permitidos e preenche `updated_by` a partir de `RequestIdentity`.
11. O Repository devolve o estado persistido e o identificador da entidade.
12. O Decorator entrega estado anterior, estado novo e metadados ao `AuditService`.
13. O `AuditService` remove campos sensíveis, calcula o de-para e cria a entrada imutável.
14. O `AuditLogRepository` insere o log usando a mesma conexão e transação.
15. A Unit of Work executa outras alterações relacionadas, repetindo o processo quando necessário.
16. O `TransactionManager` confirma tudo somente após o sucesso de todas as escritas e auditorias.
17. O Controller devolve apenas o resultado funcional da operação.
18. Nenhum dado de auditoria ou conteúdo da sessão é exibido ao usuário.

## 12. Organização planejada

A implementação deverá introduzir componentes nas seguintes áreas conceituais:

- Middleware: autenticação e criação do contexto;
- Context: identidade imutável da requisição;
- Services: auditoria, sanitização e casos de uso;
- Contracts: interfaces de Repositories, transação e contexto;
- Repositories: entidades operacionais e inserção dos logs;
- Decorators: auditoria transparente de Repositories mutáveis;
- Exceptions: autenticação, auditoria, persistência e conflito;
- Database: migrações da auditoria e das colunas de autoria.

Não deve existir dependência de Controller dentro de Service ou Repository. `AuditService` não depende de Repositories operacionais. Repositories operacionais não dependem de sessão.

## 13. Estratégia de implantação

A implantação deve ser incremental:

1. criar tabela, índices e usuário técnico para legado;
2. adicionar colunas de autoria sem quebrar registros existentes;
3. introduzir contexto de identidade e Middleware;
4. extrair interfaces dos Repositories operacionais;
5. introduzir `TransactionManager`;
6. auditar primeiro entrada e saída de veículos;
7. validar atomicidade, concorrência e sanitização;
8. expandir para criação de vagas e demais operações mutáveis;
9. endurecer permissões do banco;
10. somente depois avaliar uma interface administrativa de consulta.

## 14. Critérios de aceitação

- Existe uma tabela `audit_logs` somente de inserção para a aplicação.
- Toda ação auditável contém ID e fotografia do e-mail do usuário.
- A identidade provém exclusivamente da sessão no servidor.
- Entrada e saída de veículos preenchem autoria automaticamente.
- Operações compostas compartilham um único `request_id`.
- Estados anterior e posterior são JSON válido e sanitizado.
- Operação principal e auditoria utilizam a mesma conexão e transação.
- Falha no log reverte a operação principal.
- Falha na operação principal não cria log.
- Atualizações concorrentes preservam um de-para coerente.
- Dados sensíveis e dados de sessão não são auditados.
- Não existe rota ou View de auditoria acessível ao usuário nesta fase.
- Falhas de auditoria utilizam `AuditLogException`.
- Ausência de identidade utiliza `UnauthorizedException`.
- Controllers não chamam `AuditService`.
