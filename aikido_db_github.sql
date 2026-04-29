-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 29/04/2026 às 09:01
-- Versão do servidor: 8.0.40
-- Versão do PHP: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `aikido_db`
--

DELIMITER $$
--
-- Procedimentos
--
$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos`
--

CREATE TABLE `alunos` (
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `endereco` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `data_inicio_treinos` date DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `graduacao` int DEFAULT NULL,
  `numero_treinos` int DEFAULT '0',
  `dojo_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `dojos`
--

CREATE TABLE `dojos` (
  `id` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `endereco` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` varchar(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sensei_id` int DEFAULT NULL,
  `dojoLatitude` decimal(16,14) DEFAULT NULL,
  `dojoLongitude` decimal(16,14) DEFAULT NULL,
  `distanciaPermitida` decimal(5,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `dojos`
--

INSERT INTO `dojos` (`id`, `nome`, `endereco`, `cidade`, `estado`, `sensei_id`, `dojoLatitude`, `dojoLongitude`, `distanciaPermitida`) VALUES
(1, 'Dojo Exemplo', 'Endereço Exemplo', 'Cidade Exemplo', 'PR', NULL, NULL, NULL, 0.150);

-- --------------------------------------------------------

--
-- Estrutura para tabela `exames`
--

CREATE TABLE `exames` (
  `id` int NOT NULL,
  `aluno_id` int NOT NULL,
  `dojo_id` int NOT NULL,
  `data_exame` date NOT NULL,
  `id_faixa` int NOT NULL,
  `resalvas` int DEFAULT '0',
  `situacao` enum('aprovado','reprovado') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'aprovado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `faixas`
--

CREATE TABLE `faixas` (
  `id` int NOT NULL,
  `dojo_id` int NOT NULL DEFAULT '1',
  `faixa_cor` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `tempo_minimo_meses` int NOT NULL,
  `treinos` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `faixas`
--

INSERT INTO `faixas` (`id`, `dojo_id`, `faixa_cor`, `tempo_minimo_meses`, `treinos`) VALUES
(1, 1, 'Branca', 4, 40),
(2, 1, 'Laranja', 4, 40),
(3, 1, 'Amarela', 4, 60),
(4, 1, 'Roxa', 8, 70),
(5, 1, 'Verde', 8, 80),
(6, 1, 'Azul', 8, 90),
(7, 1, 'Marrom', 12, 100),
(8, 1, 'Preta - Shodan', 12, 100),
(9, 1, 'Preta - Nidan', 12, 200),
(10, 1, 'Preta - Sandan', 12, 300),
(11, 1, 'Preta - Yondan', 144, 400),
(12, 1, 'Preta - Godan', 144, 500),
(13, 1, 'Preta - Rokudan', 144, 0),
(14, 1, 'Preta - Hachidan', 144, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `faixas_treinos`
--

CREATE TABLE `faixas_treinos` (
  `id` int NOT NULL,
  `aluno_id` int NOT NULL,
  `faixa` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `numero_treinos` int DEFAULT '0',
  `data_inicio` date NOT NULL,
  `id_faixa` int NOT NULL,
  `dojo_id` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensalidades`
--

CREATE TABLE `mensalidades` (
  `id` int NOT NULL,
  `aluno_id` int NOT NULL,
  `mes_ano` varchar(7) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `status` enum('Paga','Em Aberto','Ausente') DEFAULT 'Em Aberto',
  `data_pagamento` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `presencas`
--

CREATE TABLE `presencas` (
  `id` int NOT NULL,
  `aluno_id` int DEFAULT NULL,
  `dojo_id` int NOT NULL DEFAULT '1',
  `treino_id` int NOT NULL,
  `data_presenca` date DEFAULT NULL,
  `status` enum('presente','falta') COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Acionadores `presencas`
--
DELIMITER $$
CREATE TRIGGER `trg_decrementa_treinos` AFTER DELETE ON `presencas` FOR EACH ROW BEGIN
            DECLARE faixa_max INT;
            SELECT MAX(id_faixa) INTO faixa_max FROM faixas_treinos WHERE aluno_id = OLD.aluno_id;
            IF faixa_max IS NOT NULL THEN
                UPDATE faixas_treinos SET numero_treinos = GREATEST(0, numero_treinos - 1)
                WHERE aluno_id = OLD.aluno_id AND id_faixa = faixa_max;
            END IF;
        END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_incrementa_treinos` AFTER INSERT ON `presencas` FOR EACH ROW BEGIN
            DECLARE faixa_max INT;
            SELECT MAX(id_faixa) INTO faixa_max FROM faixas_treinos WHERE aluno_id = NEW.aluno_id;
            IF faixa_max IS NOT NULL THEN
                UPDATE faixas_treinos SET numero_treinos = numero_treinos + 1
                WHERE aluno_id = NEW.aluno_id AND id_faixa = faixa_max;
            END IF;
        END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `treinos_horarios`
--

CREATE TABLE `treinos_horarios` (
  `id` int NOT NULL,
  `dojo_id` int NOT NULL DEFAULT '1',
  `dia_semana` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `horario` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `treinos_horarios`
--

INSERT INTO `treinos_horarios` (`id`, `dojo_id`, `dia_semana`, `horario`) VALUES
(104, 1, 'Quarta', '18:00:00'),
(102, 1, 'Quinta', '18:00:00'),
(2, 1, 'Quinta', '19:00:00'),
(3, 1, 'Sábado', '15:00:00'),
(4, 1, 'Sábado', '16:00:00'),
(103, 1, 'Segunda', '18:00:00'),
(105, 1, 'Sexta', '18:00:00'),
(101, 1, 'Terça', '18:00:00'),
(1, 1, 'Terça', '19:00:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `senha` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `senha_adm` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_usuario` enum('admin','sensei','aluno') COLLATE utf8mb4_general_ci DEFAULT 'aluno',
  `dojo_id` int DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado') COLLATE utf8mb4_general_ci DEFAULT 'pendente',
  `reset_token` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_expira` datetime DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alunos`
--
ALTER TABLE `alunos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `fk_alunos_faixas` (`graduacao`),
  ADD KEY `dojo_id` (`dojo_id`);

--
-- Índices de tabela `dojos`
--
ALTER TABLE `dojos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sensei_id` (`sensei_id`);

--
-- Índices de tabela `exames`
--
ALTER TABLE `exames`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `id_faixa` (`id_faixa`),
  ADD KEY `fk_exames_dojo` (`dojo_id`);

--
-- Índices de tabela `faixas`
--
ALTER TABLE `faixas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_faixas_dojo` (`dojo_id`);

--
-- Índices de tabela `faixas_treinos`
--
ALTER TABLE `faixas_treinos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aluno_id` (`aluno_id`,`faixa`),
  ADD KEY `fk_faixa` (`id_faixa`),
  ADD KEY `fk_faixas_treinos_dojo` (`dojo_id`);

--
-- Índices de tabela `mensalidades`
--
ALTER TABLE `mensalidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `presencas`
--
ALTER TABLE `presencas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `fk_presencas_dojo` (`dojo_id`);

--
-- Índices de tabela `treinos_horarios`
--
ALTER TABLE `treinos_horarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_unico_dia_horario_dojo` (`dia_semana`,`horario`,`dojo_id`),
  ADD KEY `fk_treinos_horarios_dojo` (`dojo_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_usuarios_dojos` (`dojo_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alunos`
--
ALTER TABLE `alunos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT de tabela `dojos`
--
ALTER TABLE `dojos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `exames`
--
ALTER TABLE `exames`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT de tabela `faixas`
--
ALTER TABLE `faixas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `faixas_treinos`
--
ALTER TABLE `faixas_treinos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT de tabela `mensalidades`
--
ALTER TABLE `mensalidades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=271;

--
-- AUTO_INCREMENT de tabela `presencas`
--
ALTER TABLE `presencas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1918;

--
-- AUTO_INCREMENT de tabela `treinos_horarios`
--
ALTER TABLE `treinos_horarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `alunos`
--
ALTER TABLE `alunos`
  ADD CONSTRAINT `alunos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `alunos_ibfk_2` FOREIGN KEY (`dojo_id`) REFERENCES `dojos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_alunos_faixas` FOREIGN KEY (`graduacao`) REFERENCES `faixas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `dojos`
--
ALTER TABLE `dojos`
  ADD CONSTRAINT `dojos_ibfk_1` FOREIGN KEY (`sensei_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `exames`
--
ALTER TABLE `exames`
  ADD CONSTRAINT `exames_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`),
  ADD CONSTRAINT `exames_ibfk_2` FOREIGN KEY (`id_faixa`) REFERENCES `faixas` (`id`),
  ADD CONSTRAINT `fk_exames_dojo` FOREIGN KEY (`dojo_id`) REFERENCES `dojos` (`id`);

--
-- Restrições para tabelas `faixas`
--
ALTER TABLE `faixas`
  ADD CONSTRAINT `fk_faixas_dojo` FOREIGN KEY (`dojo_id`) REFERENCES `dojos` (`id`);

--
-- Restrições para tabelas `faixas_treinos`
--
ALTER TABLE `faixas_treinos`
  ADD CONSTRAINT `faixas_treinos_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_faixa` FOREIGN KEY (`id_faixa`) REFERENCES `faixas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_faixas_treinos_dojo` FOREIGN KEY (`dojo_id`) REFERENCES `dojos` (`id`);

--
-- Restrições para tabelas `mensalidades`
--
ALTER TABLE `mensalidades`
  ADD CONSTRAINT `mensalidades_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`);

--
-- Restrições para tabelas `presencas`
--
ALTER TABLE `presencas`
  ADD CONSTRAINT `fk_presencas_dojo` FOREIGN KEY (`dojo_id`) REFERENCES `dojos` (`id`),
  ADD CONSTRAINT `presencas_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`);

--
-- Restrições para tabelas `treinos_horarios`
--
ALTER TABLE `treinos_horarios`
  ADD CONSTRAINT `fk_treinos_horarios_dojo` FOREIGN KEY (`dojo_id`) REFERENCES `dojos` (`id`);

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_dojos` FOREIGN KEY (`dojo_id`) REFERENCES `dojos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
