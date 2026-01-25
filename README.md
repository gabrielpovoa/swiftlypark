# 🏎️ SwiftlyPark - Estacionamento Inteligente

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Architecture](https://img.shields.io/badge/Architecture-MVC-green?style=for-the-badge)

O **SwiftlyPark** é um sistema de gestão de estacionamentos focado em agilidade e controle financeiro. Desenvolvido com arquitetura MVC, o projeto oferece uma interface moderna para controle de vagas, entradas, saídas e métricas de faturamento em tempo real.

🔗 **Repositório:** [github.com/gabrielpovoa/swiftlypark](https://github.com/gabrielpovoa/swiftlypark)

---

## 🚀 Funcionalidades Implementadas

| ID | Funcionalidade | Descrição | Status | Prioridade |
| :--- | :--- | :--- | :--- | :--- |
| **RQ-01** | Login de Usuário | Autenticação segura com e-mail e senha. | ✅ Concluído | Alta |
| **RQ-02** | Cadastro de Conta | Criação de novos perfis vinculados ao sistema. | ✅ Concluído | Alta |
| **RQ-03** | Gerenciar Vagas | Controle de disponibilidade (Carro, Moto, Caminhão, App). | ✅ Concluído | Alta |
| **RQ-04** | Dashboard | Métricas de faturamento, tempo médio e ocupação. | 🔄 Em Dev | Média |

---

## 🛠️ Tecnologias e Infraestrutura

O projeto foi migrado de um ambiente local (Laragon) para uma infraestrutura robusta baseada em **Docker**, garantindo que o sistema rode exatamente da mesma forma em qualquer máquina.

- **Backend:** PHP 8.2 (MVC)
- **Banco de Dados:** MySQL 8.0
- **Servidor Web:** Apache (configurado para `public/`)
- **Containerização:** Docker & Docker Compose
- **Interface DB:** phpMyAdmin incluído no ambiente

---

## 📂 Estrutura do Projeto

```text
swiftlypark/
├── app/                # Core do sistema (Models, Views, Controllers)
├── config/             # Configurações de banco de dados e ambiente
├── core/               # Motor do Framework (Roteamento e Base)
├── public/             # Ponto de entrada (index.php) e assets (CSS/JS)
├── routes/             # Definição de rotas do sistema
├── Dockerfile          # Configuração da imagem PHP/Apache
└── docker-compose.yml  # Orquestração dos serviços (App, DB, phpMyAdmin)
```

---
## 🐳 Como Rodar o Projeto com Docker

Para iniciar o ambiente de desenvolvimento, certifique-se de ter o **Docker Desktop** instalado em sua máquina.

### 1️⃣ Clonar o Repositório
Abra o seu terminal e execute os comandos abaixo para baixar o projeto e acessar a pasta raiz:
``` bash
    git clone [https://github.com/gabrielpovoa/swiftlypark.git](https://github.com/gabrielpovoa/swiftlypark.git)
cd swiftlypark
```

---

## 2️⃣ Subir os Containers
Utilize o Docker Compose para construir as imagens e iniciar os serviços (PHP, MySQL e phpMyAdmin):

``` bash
    docker-compose up -d --build
```

---

## 3️⃣ Acessar o Sistema
Após o carregamento, os serviços estarão disponíveis nos seguintes endereços:

- 🌐 Aplicação: http://localhost:8080
- 🗄️ phpMyAdmin: http://localhost:8081

``` mysql
    Usuário: root
    Senha: root
```

💡 Dica: No primeiro acesso, utilize o phpMyAdmin para importar o arquivo .sql que acompanha o projeto para criar as tabelas e popular os dados iniciais.

---

## 🗄️ Modelagem do Banco de Dados
- A arquitetura do banco de dados foi projetada com foco em integridade referencial e automação de processos via deleção em cascata (ON DELETE CASCADE):

- Login & Usuário: Estrutura normalizada com separação entre credenciais de acesso e informações de perfil.

- Gestão de Vagas: Sistema dinâmico que sincroniza a disponibilidade em tempo real entre vagas livres e registros de ocupação.

- Transações: Módulo financeiro integrado, registrando valores e métricas no momento exato da saída dos veículos.

---

## 🔄 Próximos Passos

Acompanhe o que ainda está por vir no desenvolvimento do **SwiftlyPark**:

- [ ] **Dashboard Analytics:** Implementação de gráficos interativos para visualização de performance.
- [ ] **Relatórios PDF:** Exportação de fechamento de caixa e histórico de movimentações.
- [ ] **Sistema de Auditoria (Logs):** Registro de logs de atividades (quem alterou valores, horários ou excluiu registros) para maior transparência e segurança.

---

<p align="center">
  Developed with ❤️ by <strong>Gabriel Povoa</strong>
</p>