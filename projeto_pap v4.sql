-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 02-Abr-2025 às 11:48
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `projeto_pap`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `schedule` datetime NOT NULL,
  `duration` int(11) NOT NULL,
  `max_participants` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `classes`
--

INSERT INTO `classes` (`id`, `name`, `description`, `trainer_id`, `schedule`, `duration`, `max_participants`) VALUES
(1, 'Yoga', 'Aula de yoga para iniciantes e avançados.', 2, '2025-03-01 10:00:00', 60, 20),
(2, 'Cycling', 'Treino com alta intensidade sem colocar stress extra nas suas articulações e ossos Esta atividade é essencialmente cardiovascular, podendo alterar o seu nível de resistência consoante a sua capacidade física.', 2, '2025-03-02 18:00:00', 90, 25);

-- --------------------------------------------------------

--
-- Estrutura da tabela `class_participants`
--

CREATE TABLE `class_participants` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `class_participants`
--

INSERT INTO `class_participants` (`id`, `class_id`, `user_id`) VALUES
(1, 1, 1),
(2, 2, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `weight` float DEFAULT NULL,
  `height` float DEFAULT NULL,
  `activity_level` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `clients`
--

INSERT INTO `clients` (`id`, `user_id`, `weight`, `height`, `activity_level`) VALUES
(1, 1, 75.5, 1.8, 'Moderado'),
(2, 4, NULL, NULL, NULL),
(3, 16, NULL, NULL, NULL),
(4, 21, NULL, NULL, NULL),
(5, 22, NULL, NULL, NULL),
(6, 23, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `day_options`
--

CREATE TABLE `day_options` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `day_options`
--

INSERT INTO `day_options` (`id`, `name`) VALUES
(1, 'Peito e Costas'),
(2, 'Pernas e Ombros'),
(3, 'Bíceps e Tríceps'),
(4, 'Cardio'),
(5, 'Descanso'),
(6, 'Corpo Inteiro'),
(7, 'Abdominais e Lombar');

-- --------------------------------------------------------

--
-- Estrutura da tabela `diet_plans`
--

CREATE TABLE `diet_plans` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `nutritionist_id` int(11) NOT NULL,
  `plan_details` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `diet_plans`
--

INSERT INTO `diet_plans` (`id`, `client_id`, `nutritionist_id`, `plan_details`, `created_at`) VALUES
(1, 1, 3, 'Café da manhã: Omelete com vegetais; Almoço: Frango grelhado com arroz integral; Jantar: Salada de quinoa.', '2025-02-16 16:00:41');

-- --------------------------------------------------------

--
-- Estrutura da tabela `exercises`
--

CREATE TABLE `exercises` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `muscle_group` varchar(255) DEFAULT NULL,
  `equipment` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `exercises`
--

INSERT INTO `exercises` (`id`, `name`, `description`, `muscle_group`, `equipment`, `image`, `video_url`) VALUES
(1, 'Bench Press', 'Lie on a bench and press a barbell or dumbbells upwards.', 'Chest', 'Barbell, Bench', NULL, NULL),
(2, 'Squats', 'Stand with feet shoulder-width apart and lower your hips as if sitting in a chair.', 'Legs', 'Barbell, Rack', NULL, NULL),
(3, 'Deadlifts', 'Lift a barbell from the floor to a standing position.', 'Back', 'Barbell', NULL, NULL),
(4, 'Push-ups', 'Start in a plank position and lower your body towards the floor.', 'Chest', 'Bodyweight', NULL, NULL),
(5, 'Pull-ups', 'Hang from a bar and pull yourself up until your chin is over the bar.', 'Back', 'Pull-up Bar', NULL, NULL),
(6, 'Overhead Press', 'Stand with feet shoulder-width apart and press a barbell or dumbbells upwards.', 'Shoulders', 'Barbell, Dumbbells', NULL, NULL),
(7, 'Bicep Curls', 'Stand with feet shoulder-width apart and curl a barbell or dumbbells upwards.', 'Biceps', 'Barbell, Dumbbells', NULL, NULL),
(8, 'Tricep Extensions', 'Stand with feet shoulder-width apart and extend a barbell or dumbbells upwards.', 'Triceps', 'Barbell, Dumbbells', NULL, NULL),
(9, 'Lunges', 'Step forward with one leg and lower your hips until both knees are bent at about a 90-degree angle.', 'Legs', 'Bodyweight, Dumbbells', NULL, NULL),
(10, 'Plank', 'Hold a push-up position with your forearms on the ground.', 'Core', 'Bodyweight', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `generic_workout_plans`
--

CREATE TABLE `generic_workout_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `generic_workout_plans`
--

INSERT INTO `generic_workout_plans` (`id`, `name`, `description`) VALUES
(1, 'Plano Iniciante - 3 Dias/Semana', 'Dia 1: Corpo Inteiro\r\nAgachamento: 3x10\r\nSupino: 3x10\r\nRemada Curvada: 3x10\r\nPrancha: 3x30 segundos\r\nCardio: 20 minutos (caminhada rápida ou bicicleta)\r\n\r\nDia 2: Descanso ou Atividade Leve (caminhada, alongamento)\r\n\r\nDia 3: Corpo Inteiro\r\nLevantamento Terra: 3x10\r\nDesenvolvimento de Ombros: 3x10\r\nFlexões: 3x12\r\nAbdominais: 3x15\r\nCardio: 20 minutos\r\n\r\nDia 4: Descanso\r\n\r\nDia 5: Corpo Inteiro\r\nAfundos: 3x10 (por perna)\r\nPull-ups (ou Lat Pulldown): 3x8\r\nElevações Laterais: 3x12\r\nPrancha Lateral: 3x20 segundos (cada lado)\r\nCardio: 20 minutos\r\n\r\nDias 6 e 7: Descanso ou Atividade Leve'),
(2, 'Plano Iniciante', 'Dia 1: Aquecimento, Agachamentos, Flexões, Abdominais\nDia 2: Aquecimento, Corrida Leve, Alongamentos\nDia 3: Descanso\nDia 4: Aquecimento, Agachamentos, Flexões, Abdominais\nDia 5: Aquecimento, Corrida Leve, Alongamentos\nDia 6: Descanso\nDia 7: Descanso'),
(3, 'Plano Intermediário', 'Dia 1: Aquecimento, Agachamentos com Peso, Flexões, Abdominais\nDia 2: Aquecimento, Corrida Moderada, Alongamentos\nDia 3: Descanso\nDia 4: Aquecimento, Agachamentos com Peso, Flexões, Abdominais\nDia 5: Aquecimento, Corrida Moderada, Alongamentos\nDia 6: Descanso\nDia 7: Descanso'),
(4, 'Plano Avançado', 'Dia 1: Aquecimento, Agachamentos com Peso, Flexões, Abdominais\nDia 2: Aquecimento, Corrida Intensa, Alongamentos\nDia 3: Descanso\nDia 4: Aquecimento, Agachamentos com Peso, Flexões, Abdominais\nDia 5: Aquecimento, Corrida Intensa, Alongamentos\nDia 6: Descanso\nDia 7: Descanso');

-- --------------------------------------------------------

--
-- Estrutura da tabela `recipes`
--

CREATE TABLE `recipes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `ingredients` text NOT NULL,
  `instructions` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `recipes`
--

INSERT INTO `recipes` (`id`, `name`, `ingredients`, `instructions`, `created_at`) VALUES
(1, 'Omelete de Vegetais', '2 ovos, 1/2 xícara de espinafre, 1/4 xícara de tomate picado, 1/4 xícara de cebola picada, sal e pimenta a gosto', '1. Bata os ovos em uma tigela. 2. Aqueça uma frigideira antiaderente e adicione os vegetais. 3. Despeje os ovos sobre os vegetais e cozinhe até firmar. 4. Sirva quente.', '2025-02-26 11:10:17'),
(2, 'Smoothie de Frutas', '1 banana, 1/2 xícara de morangos, 1/2 xícara de iogurte natural, 1/2 xícara de leite de amêndoa, 1 colher de sopa de mel', '1. Coloque todos os ingredientes no liquidificador. 2. Bata até obter uma mistura homogênea. 3. Sirva gelado.', '2025-02-26 11:10:17'),
(3, 'Salada de Quinoa', '1 xícara de quinoa cozida, 1/2 xícara de pepino picado, 1/2 xícara de tomate cereja, 1/4 xícara de queijo feta, azeite e limão a gosto', '1. Misture todos os ingredientes em uma tigela. 2. Tempere com azeite e limão. 3. Sirva frio.', '2025-02-26 11:10:17');

-- --------------------------------------------------------

--
-- Estrutura da tabela `recommendations`
--

CREATE TABLE `recommendations` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `carbohydrates` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `recommendations`
--

INSERT INTO `recommendations` (`id`, `title`, `description`, `carbohydrates`, `created_at`) VALUES
(1, 'Pequeno Almoço', 'Omelete de vegetais, aveia com frutas, pão integral com abacate.', '1 fatia de pão integral (15g de HC), 1/2 xícara de aveia (30g de HC)', '2025-02-26 11:13:08'),
(2, 'Durante a Manhã', 'Frutas frescas (maçã, banana, laranja), iogurte natural com granola, nozes e sementes.', '1 maçã (20g de HC), 1/4 xícara de granola (20g de HC)', '2025-02-26 11:13:08'),
(3, 'Almoço', 'Peixe grelhado com legumes, quinoa ou arroz integral, salada verde com azeite.', '1/2 xícara de quinoa (20g de HC), 1/2 xícara de arroz integral (22g de HC)', '2025-02-26 11:13:08'),
(4, 'Antes do Treino', 'Banana com manteiga de amendoim, smoothie de frutas, barras de cereais.', '1 banana (27g de HC), 1 barra de cereais (20g de HC)', '2025-02-26 11:13:08'),
(5, 'Depois do Treino', 'Batata-doce assada, frango grelhado, smoothie de proteína.', '1 batata-doce média (30g de HC), 1 xícara de smoothie de proteína (25g de HC)', '2025-02-26 11:13:08');

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('client','trainer','nutritionist','admin') NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `first_login` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `token`, `first_login`, `created_at`) VALUES
(1, 'João Silva', 'joao@gmail.com', '$2y$10$lh.W5KbiZP/pR26lenTsaO/B5fR542orbtAlxfaWk/y16UgrPsIp2', 'client', NULL, 0, '2025-02-16 16:00:40'),
(2, 'Maria Souza', 'maria@gmail.com', '6b08d780140e292a4af8ba3f2333fc1357091442d7e807c6cad92e8dcd0240b7', 'trainer', NULL, 1, '2025-02-16 16:00:40'),
(3, 'Carlos Oliveira', 'carlos@gmail.com', 'b578dc5fcbfabbc7e96400601d0858c951f04929faef033bbbc117ab935c6ae9', 'nutritionist', NULL, 1, '2025-02-16 16:00:40'),
(4, 'Nelo', 'Nelo@gmail.com', '$2y$10$Ya4lZjtt5aMJi1nZm9VW4O9quz16SDR5okERVFHVfSLOgbIisYAwC', 'client', NULL, 0, '2025-02-16 16:42:55'),
(6, 'Claudia ', 'claudia@gmail.com', '$2y$10$QrMlKOAR98ERl97o/rMoIenm5r7q0dGmnRa1WzQir5kHS.h6e0Mn2', 'trainer', NULL, 0, '2025-02-16 17:27:51'),
(7, 'Silvia', 'silvia@gmail.com', 'silvia123', 'nutritionist', NULL, 1, '2025-02-16 17:27:51'),
(8, 'claudio', 'claudio@gmail.com', 'claudio123', 'trainer', NULL, 1, '2025-02-16 17:57:51'),
(10, 'Personal Trainer', 'trainer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', NULL, 1, '2025-02-16 18:23:39'),
(11, 'Nutricionista', 'nutritionist@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nutritionist', NULL, 1, '2025-02-16 18:23:39'),
(12, 'leandro', 'leandro@gmail.com', '$2y$10$2Hn16xy2XOlFAFvi37sQ3uPRSb.icWRJ1/iQPAdKtwg15Deh.TbgK', 'admin', NULL, 0, '2025-02-22 10:30:35'),
(16, 'Fabio', 'fabio.oliveira.1719@gmail.com', '$2y$10$kfsBwjbI1oyZRutY0rjXNOVAeYINuqInUr0eyEgtjfB4dNez/8D4S', 'client', '8f87cb65849c9b33b7a2b0a5f5849040f974bfd7335d8fe398d55ca847619b03864f276127ce05b47702f526ae36520f9506', 1, '2025-03-15 14:11:35'),
(21, 'Marcelo', '2mapo.01.01.2007@gmail.com', '$2y$10$0QvpZM6lpJ3kKRFWU39E1O/tP4xbnnX8CT8oD6Jny7QOszZicpWfK', 'client', '1adb618811d0ad173cf0727a40561dfac3bda92241e5ebcf5fc924d01bfeb8cf6829d153d70dafb90dffd59ff2b7928da9b6', 1, '2025-03-15 14:53:00'),
(22, 'Arbusto', 'arbustosr116@gmail.com', '$2y$10$msl0AcXBrpFVOKSU7gVeg.hkpuqmUTYrw/VZxniRz9WyNvkqgn2qK', 'client', NULL, 0, '2025-03-15 14:55:43'),
(23, 'Leonardo', 'leofsgomes.2007@gmail.com', '$2y$10$/UlDkdcXgPXOmtFHqE5e2OzsVyY5wk2np9Yvtw1Yyzj37f475jN5u', 'client', 'af6410bd5e87419eddb0bb9f789cbb9ad5cf69ce0d7f2dbf66d6650d83b52a48ec6361fa8870d069dd72f970a75aba99a537', 1, '2025-03-20 14:39:26');

-- --------------------------------------------------------

--
-- Estrutura da tabela `workout_history`
--

CREATE TABLE `workout_history` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `workout_date` date NOT NULL,
  `workout_details` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `workout_plans`
--

CREATE TABLE `workout_plans` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `plan_details` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `workout_plans`
--

INSERT INTO `workout_plans` (`id`, `client_id`, `trainer_id`, `plan_details`, `created_at`, `is_active`) VALUES
(1, 1, 2, 'Treino A: Segunda-feira - Musculação; Quarta-feira - Cardio; Sexta-feira - Funcional.', '2025-02-16 16:00:40', 0),
(8, 1, 6, 'Dia 1: Aquecimento, Agachamentos, Flexões, Abdominais\r\nDia 2: Aquecimento, Corrida Leve, Alongamentos\r\nDia 3: Descanso\r\nDia 4: Aquecimento, Agachamentos, Flexões, Abdominais\r\nDia 5: Aquecimento, Corrida Leve, Alongamentos\r\nDia 6: Descanso\r\nDia 7: Descanso\r\naaaa', '2025-03-20 15:52:11', 0),
(9, 2, 6, 'Plano Genérico:\nDia 1: Aquecimento, Agachamentos com Peso, Flexões, Abdominais\nDia 2: Aquecimento, Corrida Intensa, Alongamentos\nDia 3: Descanso\nDia 4: Aquecimento, Agachamentos com Peso, Flexões, Abdominais\nDia 5: Aquecimento, Corrida Intensa, Alongamentos\nDia 6: Descanso\nDia 7: Descanso\n\nDetalhes Personalizados:\naa', '2025-03-20 15:52:40', 0),
(10, 1, 6, 'precisa de comer muita sopa', '2025-04-01 14:03:11', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `workout_plan_days`
--

CREATE TABLE `workout_plan_days` (
  `id` int(11) NOT NULL,
  `workout_plan_id` int(11) DEFAULT NULL,
  `day_name` varchar(255) NOT NULL,
  `day_details` text DEFAULT NULL,
  `day_option_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `workout_plan_days`
--

INSERT INTO `workout_plan_days` (`id`, `workout_plan_id`, `day_name`, `day_details`, `day_option_id`) VALUES
(8, 10, 'Dia 1', '', 2),
(9, 10, 'Dia 2', '', 3),
(10, 10, 'Dia 3', '', 1),
(11, 10, 'Dia 4', '', 4),
(12, 10, 'Dia 5', '', 7),
(13, 10, 'Dia 6', '', 5),
(14, 10, 'Dia 7', '', 5);

-- --------------------------------------------------------

--
-- Estrutura da tabela `workout_plan_exercises`
--

CREATE TABLE `workout_plan_exercises` (
  `id` int(11) NOT NULL,
  `workout_plan_id` int(11) DEFAULT NULL,
  `exercise_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `workout_plan_exercises`
--

INSERT INTO `workout_plan_exercises` (`id`, `workout_plan_id`, `exercise_id`) VALUES
(31, 8, 1),
(32, 8, 2),
(33, 8, 3),
(34, 8, 4),
(35, 8, 5),
(36, 8, 6),
(37, 8, 7),
(38, 8, 8),
(39, 8, 9),
(40, 8, 10),
(44, 10, 1),
(45, 10, 2),
(46, 10, 3);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Índices para tabela `class_participants`
--
ALTER TABLE `class_participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices para tabela `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices para tabela `day_options`
--
ALTER TABLE `day_options`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `diet_plans`
--
ALTER TABLE `diet_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `nutritionist_id` (`nutritionist_id`);

--
-- Índices para tabela `exercises`
--
ALTER TABLE `exercises`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `generic_workout_plans`
--
ALTER TABLE `generic_workout_plans`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `recommendations`
--
ALTER TABLE `recommendations`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `workout_history`
--
ALTER TABLE `workout_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Índices para tabela `workout_plans`
--
ALTER TABLE `workout_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Índices para tabela `workout_plan_days`
--
ALTER TABLE `workout_plan_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workout_plan_id` (`workout_plan_id`),
  ADD KEY `day_option_id` (`day_option_id`);

--
-- Índices para tabela `workout_plan_exercises`
--
ALTER TABLE `workout_plan_exercises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workout_plan_id` (`workout_plan_id`),
  ADD KEY `exercise_id` (`exercise_id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `class_participants`
--
ALTER TABLE `class_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `day_options`
--
ALTER TABLE `day_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `diet_plans`
--
ALTER TABLE `diet_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `exercises`
--
ALTER TABLE `exercises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `generic_workout_plans`
--
ALTER TABLE `generic_workout_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `recommendations`
--
ALTER TABLE `recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `workout_history`
--
ALTER TABLE `workout_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `workout_plans`
--
ALTER TABLE `workout_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `workout_plan_days`
--
ALTER TABLE `workout_plan_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `workout_plan_exercises`
--
ALTER TABLE `workout_plan_exercises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `class_participants`
--
ALTER TABLE `class_participants`
  ADD CONSTRAINT `class_participants_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `diet_plans`
--
ALTER TABLE `diet_plans`
  ADD CONSTRAINT `diet_plans_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `diet_plans_ibfk_2` FOREIGN KEY (`nutritionist_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `workout_history`
--
ALTER TABLE `workout_history`
  ADD CONSTRAINT `workout_history_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `workout_plans`
--
ALTER TABLE `workout_plans`
  ADD CONSTRAINT `workout_plans_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workout_plans_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `workout_plan_days`
--
ALTER TABLE `workout_plan_days`
  ADD CONSTRAINT `workout_plan_days_ibfk_1` FOREIGN KEY (`workout_plan_id`) REFERENCES `workout_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workout_plan_days_ibfk_2` FOREIGN KEY (`day_option_id`) REFERENCES `day_options` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `workout_plan_exercises`
--
ALTER TABLE `workout_plan_exercises`
  ADD CONSTRAINT `workout_plan_exercises_ibfk_1` FOREIGN KEY (`workout_plan_id`) REFERENCES `workout_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workout_plan_exercises_ibfk_2` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
