-- --------------------------------------------------------
-- Servidor:                     127.0.0.1
-- Versão do servidor:           Compatível com MariaDB/MySQL 5.7+
-- OS do Servidor:               Linux
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Criando banco de dados parking
CREATE DATABASE IF NOT EXISTS `parking`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE `parking`;

-- Tabela: login
CREATE TABLE IF NOT EXISTS `login` (
                                       `id_login` int NOT NULL AUTO_INCREMENT,
                                       `email` varchar(255) NOT NULL,
    `senha` varchar(255) NOT NULL,
    PRIMARY KEY (`id_login`),
    UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela: transacoes
CREATE TABLE IF NOT EXISTS `transacoes` (
<<<<<<< HEAD
                                            `id_transacao` int NOT NULL AUTO_INCREMENT,
                                            `id_vaga_preenchida` int NOT NULL,
                                            `valor` decimal(10,2) NOT NULL,
    PRIMARY KEY (`id_transacao`),
    KEY `fk_transacoes_vaga_preenchida` (`id_vaga_preenchida`),
    CONSTRAINT `fk_transacoes_vaga_preenchida` FOREIGN KEY (`id_vaga_preenchida`) REFERENCES `vagas_preenchidas` (`id_vaga_preenchida`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
=======
  `id_transacao` int NOT NULL AUTO_INCREMENT,
  `id_vaga_preenchida` int NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_transacao` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transacao`),
  KEY `fk_transacoes_vaga_preenchida` (`id_vaga_preenchida`),
  CONSTRAINT `fk_transacoes_vaga_preenchida` FOREIGN KEY (`id_vaga_preenchida`) REFERENCES `vagas_preenchidas` (`id_vaga_preenchida`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
>>>>>>> origin/develop

-- Tabela: usuario
CREATE TABLE IF NOT EXISTS `usuario` (
                                         `id_usuario` int NOT NULL AUTO_INCREMENT,
                                         `id_login` int NOT NULL,
                                         `nome` varchar(100) NOT NULL,
    `foto_perfil` varchar(255) DEFAULT NULL,
    `email` varchar(255) NOT NULL,
    `senha_hash` varchar(255) NOT NULL,
    PRIMARY KEY (`id_usuario`),
    UNIQUE KEY `email` (`email`),
    KEY `fk_usuario_login` (`id_login`),
    CONSTRAINT `fk_usuario_login` FOREIGN KEY (`id_login`) REFERENCES `login` (`id_login`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela: vagas_disponiveis
CREATE TABLE IF NOT EXISTS `vagas_disponiveis` (
<<<<<<< HEAD
                                                   `id_vaga` int NOT NULL AUTO_INCREMENT,
                                                   `categoria` enum('carro','moto','caminhao','app') NOT NULL,
    `status` enum('livre','reservada') NOT NULL DEFAULT 'livre',
    PRIMARY KEY (`id_vaga`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
=======
  `id_vaga` int NOT NULL AUTO_INCREMENT,
  `categoria` enum('carro','moto','caminhao','app') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `status` enum('livre','reservada') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'livre',
  PRIMARY KEY (`id_vaga`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
>>>>>>> origin/develop

-- Tabela: vagas_preenchidas
CREATE TABLE IF NOT EXISTS `vagas_preenchidas` (
<<<<<<< HEAD
                                                   `id_vaga_preenchida` int NOT NULL AUTO_INCREMENT,
                                                   `id_vaga` int NOT NULL,
                                                   `hora_entrada` datetime NOT NULL,
                                                   `hora_saida` datetime DEFAULT NULL,
                                                   `tempo_total` time DEFAULT NULL,
                                                   PRIMARY KEY (`id_vaga_preenchida`),
    KEY `fk_vagas_preenchidas_vaga` (`id_vaga`),
    CONSTRAINT `fk_vagas_preenchidas_vaga` FOREIGN KEY (`id_vaga`) REFERENCES `vagas_disponiveis` (`id_vaga`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
=======
  `id_vaga_preenchida` int NOT NULL AUTO_INCREMENT,
  `id_vaga` int NOT NULL,
  `hora_entrada` datetime NOT NULL,
  `hora_saida` datetime DEFAULT NULL,
  `tempo_total` time DEFAULT NULL,
  `nome_cliente` varchar(100) NOT NULL,
  `telefone` varchar(50) NOT NULL DEFAULT '0',
  `placa` varchar(10) NOT NULL,
  `valor_pago` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id_vaga_preenchida`),
  KEY `fk_vagas_preenchidas_vaga` (`id_vaga`),
  CONSTRAINT `fk_vagas_preenchidas_vaga` FOREIGN KEY (`id_vaga`) REFERENCES `vagas_disponiveis` (`id_vaga`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Exportação de dados foi desmarcado.
>>>>>>> origin/develop

-- Restaurando variáveis
/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
