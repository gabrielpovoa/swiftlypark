-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: parking
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `login`
--

DROP TABLE IF EXISTS `login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login` (
  `id_login` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_login`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login`
--

LOCK TABLES `login` WRITE;
/*!40000 ALTER TABLE `login` DISABLE KEYS */;
INSERT INTO `login` VALUES (1,'joaopovoa6@gmail.com','$2y$10$QCej2tXQdewTV1XGT7seIOhXC0aJpV75mpXkNOzzGeEety7F5eoxG'),(2,'paulo@example.com','$2y$10$t7rAI53PL7cNQcSCVqawy./iO7YKXPkIkiPkyjIh/ImETGAlwVs/K'),(3,'rodrigo-nogueira90@engemed.com','$2y$10$uWoW7Uce1f9409YaYYQVz.SBz1bbWB53jl7i03339n8EKHAxEe7Cu');
/*!40000 ALTER TABLE `login` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transacoes`
--

DROP TABLE IF EXISTS `transacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transacoes` (
  `id_transacao` int NOT NULL AUTO_INCREMENT,
  `id_vaga_preenchida` int NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_transacao` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transacao`),
  KEY `idx_transacoes_vaga` (`id_vaga_preenchida`),
  CONSTRAINT `fk_transacoes_vaga_preenchida` FOREIGN KEY (`id_vaga_preenchida`) REFERENCES `vagas_preenchidas` (`id_vaga_preenchida`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transacoes`
--

LOCK TABLES `transacoes` WRITE;
/*!40000 ALTER TABLE `transacoes` DISABLE KEYS */;
INSERT INTO `transacoes` VALUES (1,1,25.00,'2026-04-26 15:14:24'),(2,2,22.00,'2026-05-26 23:52:44'),(3,3,22.00,'2026-05-27 00:01:57'),(4,5,89.00,'2026-06-21 14:44:37'),(5,6,12.00,'2026-06-21 14:46:11'),(6,7,12.00,'2026-07-05 19:57:40'),(7,8,180.00,'2026-07-05 20:01:26');
/*!40000 ALTER TABLE `transacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `id_login` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `senha_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_usuario_login` (`id_login`),
  CONSTRAINT `fk_usuario_login` FOREIGN KEY (`id_login`) REFERENCES `login` (`id_login`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,1,'joao gabriel povoa','user_1_5e3463ee7d4e639ace56d58a47069160.png','joaopovoa6@gmail.com','$2y$10$QCej2tXQdewTV1XGT7seIOhXC0aJpV75mpXkNOzzGeEety7F5eoxG'),(2,2,'Paulo Sil','user_2_1779840029.png','paulo@example.com','$2y$10$10u6N7sleknVpmnAzU05rOSybbUWZuwyK4YekA5MvzS7hKfb6IVmK'),(3,3,'Rodrigo Mateus Nogueira',NULL,'rodrigo-nogueira90@engemed.com','$2y$10$8aXCwGrnIJMbARlYmb.Xiujmd7BHYiMz5xtGUsIUWkQWm9tG4B9Xe');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vagas_disponiveis`
--

DROP TABLE IF EXISTS `vagas_disponiveis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vagas_disponiveis` (
  `id_vaga` int NOT NULL AUTO_INCREMENT,
  `categoria` enum('carro','moto','caminhao','app') COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('livre','reservada') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'livre',
  PRIMARY KEY (`id_vaga`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vagas_disponiveis`
--

LOCK TABLES `vagas_disponiveis` WRITE;
/*!40000 ALTER TABLE `vagas_disponiveis` DISABLE KEYS */;
INSERT INTO `vagas_disponiveis` VALUES (1,'carro','livre'),(2,'carro','livre'),(3,'caminhao','reservada'),(4,'moto','livre'),(5,'moto','livre'),(6,'moto','livre'),(7,'moto','livre'),(8,'moto','livre'),(9,'app','livre'),(10,'app','livre'),(11,'app','livre'),(12,'app','livre'),(13,'app','livre'),(14,'app','livre'),(15,'app','livre'),(16,'app','livre'),(17,'app','livre'),(18,'app','livre'),(19,'caminhao','livre'),(20,'caminhao','livre');
/*!40000 ALTER TABLE `vagas_disponiveis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vagas_preenchidas`
--

DROP TABLE IF EXISTS `vagas_preenchidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vagas_preenchidas` (
  `id_vaga_preenchida` int NOT NULL AUTO_INCREMENT,
  `id_vaga` int NOT NULL,
  `hora_entrada` datetime NOT NULL,
  `hora_saida` datetime DEFAULT NULL,
  `tempo_total` time DEFAULT NULL,
  `nome_cliente` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `telefone` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `placa` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `valor_pago` decimal(10,2) DEFAULT '0.00',
  `tipo_veiculo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_vaga_preenchida`),
  KEY `fk_vagas_preenchidas_vaga` (`id_vaga`),
  CONSTRAINT `fk_vagas_preenchidas_vaga` FOREIGN KEY (`id_vaga`) REFERENCES `vagas_disponiveis` (`id_vaga`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vagas_preenchidas`
--

LOCK TABLES `vagas_preenchidas` WRITE;
/*!40000 ALTER TABLE `vagas_preenchidas` DISABLE KEYS */;
INSERT INTO `vagas_preenchidas` VALUES (1,4,'2026-04-26 12:14:00','2026-04-26 12:32:00','00:18:00','Diogo Marques Pereira','51990140347','DIO-7415',25.00,'moto'),(2,3,'2026-05-26 20:51:00','2026-05-26 21:00:00','00:09:00','Джоао Габриел','41990140347','ERR1572',22.00,'caminhao'),(3,9,'2026-05-26 21:01:00','2026-05-27 20:29:00','23:28:00','Джоао Габриел','13231231','312FD',22.00,'app'),(4,1,'2026-05-27 20:48:00','2026-05-28 11:01:00','14:13:00','Джоао Габриел','41990140347','12DSD',0.00,'carro'),(5,4,'2026-06-21 11:42:00','2026-06-21 11:45:00','00:03:00','Джоао Габриел','(51) 99014-0347','DIO-7415',89.00,'moto'),(6,1,'2026-06-21 11:46:00','2026-06-21 11:48:00','00:02:00','Джоао Габриел','(51) 99014-0347','ERR1572',12.00,'carro'),(7,4,'2026-07-05 16:56:00','2026-07-05 16:59:00','00:03:00','Diogo Marques Pereira','51990140347','ERR1572',12.00,'moto'),(8,3,'2026-07-05 14:37:00',NULL,NULL,'Carlos Eduardo Martins','(51) 99874-3621','JKL-4H82',180.00,'caminhao');
/*!40000 ALTER TABLE `vagas_preenchidas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'parking'
--

--
-- Dumping routines for database 'parking'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-05 20:09:19
