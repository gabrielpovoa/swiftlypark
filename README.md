### 1. 📌 **Página principal do projeto**

- Nome do projeto: **SwiftlyPark**
- Link para o GitHub: *https://github.com/gabrielpovoa/swiftlypark*
- Descrição resumida: objetivo, linguagem, arquitetura (MVC em PHP + MySQL), e breve visão geral.

---

### 2. ✅ **Requisitos e Funcionalidades**

Crie uma tabela (database no Notion) com colunas:

- **ID** (Ex: RQ-01, RQ-02)
- **Título** (Ex: "Login de usuário", "Cadastro de conta")
- **Descrição**
- **Status** (Pendente, Em desenvolvimento, Concluído)
- **Prioridade** (Alta, Média, Baixa)

---

| ID | TITLE | DESCRIPTION | STATUS | PRIORITY |
| --- | --- | --- | --- | --- |
| RQ-01 | Login de usuário | Login com e-mail e senha | Pendente | Alta |
| RQ-02 | Cadastro de conta | Criação de conta vinculada à tabela de login | Pendente | Alta |
| RQ-03 | Gerenciar vagas | Controlar vagas disponíveis e preenchidas | Pendente | Alta |
|  | Dashboard | Mostrar faturamento diário, média de veículos e tempo médio de estadia | Pendente | Média |

---

3. 🗄 **Modelagem do Banco de Dados**

Crie um **diagrama visual** (pode ser no Whimsical, Draw.io, Excalidraw, ou até dentro do Notion usando embed) mostrando:

- **Tabela `login`**
    - id_login (PK)
    - email
    - senha
- **Tabela `usuario`**
    - id_usuario (PK)
    - id_login (FK → login.id_login)
    - nome
    - foto_perfil
    - e-mail
    - senha (hash)
- **Tabela `vagas_disponiveis`**
    - id_vaga (PK)
    - categoria (carro, moto, caminhão, app)
    - status (livre, reservada)
- **Tabela `vagas_preenchidas`**
    - id_vaga_preenchida (PK)
    - id_vaga (FK → vagas_disponiveis.id_vaga)
    - hora_entrada
    - hora_saida
    - tempo_total
- **Tabela `transacoes`**
    - id_transacao (PK)
    - id_vaga_preenchida (FK)
    - valor
---

### 4. 📂 **Arquitetura e Estrutura do Código**

- **MVC (Model–View–Controller)**
    - **/app**
        - /controllers
        - /models
        - /views
    - **/config** (conexão com banco)
    - **/public** (arquivos acessíveis ao navegador)
    - **/routes** (caso queira separar)
- Descrever **como as partes se comunicam**
- Indicar onde ficarão as regras de negócio (Models) e a camada de apresentação (Views)

---

### 5. 🔄 **Fluxos do sistema**

No Notion, crie **fluxogramas** para:

- **Login**
- **Cadastro de conta**
- **Entrada/Saída de veículo**
- **Cálculo de faturamento**
- **Dashboard**

---

### 6. 📊 **Métricas e Dashboard**

Página para listar quais métricas o backend precisa fornecer:

- Faturamento diário
- Média de veículos estacionados
- Tempo médio de permanência
- Quantidade de vagas ocupadas/livres por categoria

---
