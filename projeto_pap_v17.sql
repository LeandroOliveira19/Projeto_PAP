-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 03-Jun-2025 às 15:41
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
-- Estrutura da tabela `access_delegations`
--

CREATE TABLE `access_delegations` (
  `id` int(11) NOT NULL,
  `granting_admin_id` int(11) NOT NULL COMMENT 'ID of the admin granting access (users.id)',
  `delegated_user_id` int(11) NOT NULL COMMENT 'ID of the user receiving access (users.id)',
  `expires_at` datetime NOT NULL COMMENT 'Timestamp when the delegation expires',
  `delegated_permissions` text DEFAULT NULL COMMENT 'JSON array of permission keys',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Whether the delegation is currently active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When the delegation was created',
  `revoked_at` datetime DEFAULT NULL COMMENT 'When the delegation was manually revoked'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `access_delegations`
--

INSERT INTO `access_delegations` (`id`, `granting_admin_id`, `delegated_user_id`, `expires_at`, `delegated_permissions`, `is_active`, `created_at`, `revoked_at`) VALUES
(1, 12, 26, '2025-06-02 16:41:16', NULL, 0, '2025-06-02 14:36:16', '2025-06-02 16:08:59'),
(2, 12, 26, '2025-06-02 17:14:18', '[\"manage_trainers\",\"manage_nutritionists\"]', 0, '2025-06-02 15:09:18', '2025-06-02 16:23:01'),
(3, 12, 26, '2025-06-02 17:28:15', '[\"manage_trainers\",\"manage_nutritionists\",\"manage_receptionists\"]', 0, '2025-06-02 15:23:15', '2025-06-02 16:34:12'),
(4, 12, 26, '2025-06-02 16:40:36', '[\"manage_trainers\",\"manage_nutritionists\",\"manage_receptionists\"]', 0, '2025-06-02 15:35:36', '2025-06-02 16:43:50'),
(5, 12, 26, '2025-06-02 16:48:50', '[\"manage_trainers\",\"manage_nutritionists\",\"manage_receptionists\"]', 0, '2025-06-02 15:43:50', '2025-06-02 16:49:15'),
(6, 12, 26, '2025-06-02 16:54:15', '[\"manage_trainers\",\"manage_nutritionists\",\"manage_receptionists\"]', 0, '2025-06-02 15:49:15', '2025-06-02 16:54:39'),
(7, 12, 26, '2025-06-02 16:59:39', '[\"manage_trainers\",\"manage_nutritionists\",\"manage_receptionists\"]', 0, '2025-06-02 15:54:39', '2025-06-02 17:00:21'),
(8, 12, 26, '2025-06-02 17:05:21', '[\"manage_trainers\",\"manage_nutritionists\",\"manage_receptionists\"]', 1, '2025-06-02 16:00:21', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `achievements`
--

CREATE TABLE `achievements` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-medal',
  `points` int(11) DEFAULT 10,
  `criteria_type` varchar(50) DEFAULT NULL,
  `criteria_value` varchar(255) DEFAULT NULL,
  `is_manual_claim` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 se a conquista pode ser reivindicada manualmente pelo cliente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `achievements`
--

INSERT INTO `achievements` (`id`, `name`, `description`, `icon`, `points`, `criteria_type`, `criteria_value`, `is_manual_claim`, `created_at`) VALUES
(1, 'Pioneiro Fitness', 'Completou o seu primeiro treino na plataforma!', 'fas fa-flag-checkered', 10, 'first_workout_completed', '1', 0, '2025-05-23 10:21:20'),
(2, 'Maratonista de Treinos', 'Completou 10 treinos.', 'fas fa-running', 25, 'workouts_completed_count', '10', 0, '2025-05-23 10:21:20'),
(3, 'Mestre Culinário', 'Seguiu o plano alimentar por 7 dias consecutivos.', 'fas fa-utensils', 20, 'diet_streak_days', '7', 0, '2025-05-23 10:21:20'),
(4, 'Presença Assídua', 'Fez check-in em 5 aulas de grupo.', 'fas fa-calendar-check', 15, 'class_checkins_count', '5', 0, '2025-05-23 10:21:20'),
(5, 'Determinação de Aço', 'Manteve uma frequência de login por 30 dias.', 'fas fa-user-clock', 30, 'consecutive_logins', '30', 0, '2025-05-23 10:21:20'),
(6, 'Leitor Atento', 'Confirmo que li e compreendi os Termos e Condições da plataforma.', 'fas fa-file-contract', 5, 'manual_task_trust_based', NULL, 1, '2025-05-23 11:11:20'),
(7, 'Explorador Informado', 'Assisti ao vídeo tutorial de introdução à plataforma.', 'fas fa-play-circle', 5, 'manual_task_trust_based', NULL, 1, '2025-05-23 11:11:20'),
(8, 'Planeador de Sucesso', 'Defini as minhas metas iniciais de fitness no meu perfil.', 'fas fa-bullseye', 10, 'initial_goals_set', NULL, 1, '2025-05-23 11:11:20'),
(9, 'Perfil Completo', 'Preenchi todas as informações essenciais do meu perfil.', 'fas fa-user-check', 10, 'profile_info_completed', NULL, 1, '2025-05-23 11:11:20'),
(10, 'Voz Ativa', 'Enviei o meu feedback sobre a plataforma através do formulário de sugestões.', 'fas fa-comments', 15, 'feedback_form_submitted', NULL, 1, '2025-05-23 11:11:20');

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
(2, 'Cycling', 'Treino com alta intensidade sem colocar stress extra nas suas articulações e ossos Esta atividade é essencialmente cardiovascular, podendo alterar o seu nível de resistência consoante a sua capacidade física.', 2, '2025-03-02 18:00:00', 90, 25),
(3, 'Pilates', 'Pilates é uma forma de exercício, desenvolvido por Joseph Pilates, que enfatiza o desenvolvimento equilibrado do corpo através dos núcleos força, flexibilidade e consciência, a fim de apoiar movimentos graciosos e eficientes.', 2, '2025-04-10 17:00:00', 60, 25),
(4, 'Yoga', 'Aula de yoga para iniciantes e avançados.', 6, '2025-04-11 16:00:00', 60, 1),
(5, 'Yoga', 'asassasasas', 2, '2025-04-12 17:10:00', 40, 2),
(6, 'yoga', 'esticar a patas', 8, '2025-04-25 17:15:00', 45, 2),
(7, 'cycling', 'aquecer a rotula', 2, '2025-05-07 17:45:00', 45, 4),
(8, 'yoga', 'addadadadad', 6, '2025-05-30 17:15:00', 45, 2),
(9, 'yoga', 'djasidjaiodjasiojdi', 8, '2025-05-09 18:20:00', 45, 2),
(10, 'cycling', 'asdasibfafifainfaifniok', 6, '2025-05-09 20:20:00', 35, 4),
(11, 'Pilates', 'dsfadbsfabsijfenofkjn', 2, '2025-05-09 15:20:00', 35, 6),
(12, 'bumbum', 'askjakjbfaakj', 6, '2025-05-09 10:45:00', 34, 12),
(13, 'Zumba', 'asbfjabfabifjabf', 2, '2025-05-09 20:59:00', 12, 32),
(14, 'hip-hop', 'saldadanadiod', 2, '2025-05-09 17:54:00', 45, 20),
(15, 'Localizada', 'safasfafnhasijifj', 6, '2025-05-09 17:41:00', 35, 12),
(16, 'Localizada', 'sdadsdadasdasdd', 8, '2025-05-13 18:00:00', 35, 12),
(17, 'yoga', 'asdadsadddad', 6, '2025-05-13 17:00:00', 12, 4),
(18, 'cycling', 'sdaasfaesdgdgsfdbefbdf', 2, '2025-05-13 16:00:00', 32, 10),
(19, 'cycling', 'asdadsadasda', 2, '2025-05-22 18:00:00', 25, 40),
(20, 'Localizada', 'sdsadadasdasd', 6, '2025-05-22 17:00:00', 45, 12),
(21, 'yoga', 'dsdasddadd', 6, '2025-05-26 19:00:00', 35, 20),
(22, 'yoga', 'asdadasdda', 6, '2025-05-28 16:00:00', 35, 20),
(23, 'cycling', 'dasddaddasd', 2, '2025-05-28 20:00:00', 45, 20),
(24, 'Localizada', 'asdasffadasfad', 3, '2025-05-28 15:25:00', 35, 15),
(25, 'yoga', 'saasdaadasda', 2, '2025-05-29 17:00:00', 40, 15),
(26, 'cycling', 'dsadasdsafasdaf', 2, '2025-05-29 19:30:00', 25, 20);

-- --------------------------------------------------------

--
-- Estrutura da tabela `class_participants`
--

CREATE TABLE `class_participants` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `checked_in_at` datetime DEFAULT NULL COMMENT 'Timestamp do check-in',
  `checked_in_by_user_id` int(11) DEFAULT NULL COMMENT 'ID do funcionário que fez o check-in'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `class_participants`
--

INSERT INTO `class_participants` (`id`, `class_id`, `user_id`, `checked_in_at`, `checked_in_by_user_id`) VALUES
(1, 1, 1, NULL, NULL),
(2, 2, 1, NULL, NULL),
(3, 7, 22, NULL, NULL),
(4, 7, 4, NULL, NULL),
(5, 8, 4, '2025-05-19 16:54:30', 26),
(6, 8, 22, '2025-05-19 16:54:29', 26),
(7, 17, 4, NULL, NULL),
(9, 16, 4, NULL, NULL),
(10, 21, 4, '2025-05-26 11:31:12', 26),
(11, 22, 4, '2025-05-26 11:31:21', 26),
(12, 23, 4, '2025-05-26 11:31:23', 26),
(13, 25, 4, '2025-05-26 11:31:29', 26),
(14, 26, 4, '2025-05-26 11:50:02', 26);

-- --------------------------------------------------------

--
-- Estrutura da tabela `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `height` float DEFAULT NULL,
  `activity_level` varchar(50) DEFAULT NULL,
  `profession` varchar(100) DEFAULT NULL,
  `gym_frequency` varchar(100) DEFAULT NULL,
  `health_issues` text DEFAULT NULL,
  `water_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Percentagem de Água Corporal',
  `idade_metabolica` int(11) DEFAULT NULL COMMENT 'Idade Metabólica (anos)',
  `morfotipo` varchar(50) DEFAULT NULL COMMENT 'Ectomorfo, Mesomorfo, Endomorfo',
  `ombros` decimal(5,1) DEFAULT NULL COMMENT 'Perímetro Ombros (cm)',
  `peitoral_dorsal` decimal(5,1) DEFAULT NULL COMMENT 'Perímetro Peitoral/Dorsal (cm)',
  `cintura` decimal(5,1) DEFAULT NULL COMMENT 'Perímetro Cintura (cm)',
  `abdominal` decimal(5,1) DEFAULT NULL COMMENT 'Perímetro Abdominal (cm)',
  `quadril` decimal(5,1) DEFAULT NULL COMMENT 'Perímetro Quadril (cm)',
  `braco_direito` decimal(5,1) DEFAULT NULL COMMENT 'Perímetro Braço Direito (cm)',
  `braco_esquerdo` decimal(5,1) DEFAULT NULL COMMENT 'Perímetro Braço Esquerdo (cm)',
  `coxa_direita` decimal(5,1) DEFAULT NULL COMMENT 'Perímetro Coxa Direita (cm)',
  `coxa_esquerda` decimal(5,1) DEFAULT NULL COMMENT 'Perímetro Coxa Esquerda (cm)',
  `perna_direita` decimal(5,1) DEFAULT NULL COMMENT 'Perímetro Perna Direita (cm)',
  `perna_esquerda` decimal(5,1) DEFAULT NULL COMMENT 'Perímetro Perna Esquerda (cm)',
  `tricipital` decimal(4,1) DEFAULT NULL COMMENT 'Prega Tricipital (mm)',
  `subescapular` decimal(4,1) DEFAULT NULL COMMENT 'Prega Subescapular (mm)',
  `bicipital` decimal(4,1) DEFAULT NULL COMMENT 'Prega Bicipital (mm)',
  `peitoral` decimal(4,1) DEFAULT NULL COMMENT 'Prega Peitoral (mm)',
  `axilar_media` decimal(4,1) DEFAULT NULL COMMENT 'Prega Axilar Média (mm)',
  `abdominal_plicometria` decimal(4,1) DEFAULT NULL COMMENT 'Prega Abdominal (mm)',
  `supra_iliaca` decimal(4,1) DEFAULT NULL COMMENT 'Prega Supra-ilíaca (mm)',
  `coxa` decimal(4,1) DEFAULT NULL COMMENT 'Prega Coxa (mm)',
  `geminal` decimal(4,1) DEFAULT NULL COMMENT 'Prega Geminal/Perna (mm)',
  `visceral_fat_index` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Índice de Gordura Visceral (1-59)',
  `bone_mass_kg` decimal(4,2) DEFAULT NULL COMMENT 'Massa Óssea em Kg',
  `lean_mass_kg` decimal(5,2) DEFAULT NULL COMMENT 'Massa Magra em Kg',
  `muscle_mass_kg` decimal(5,2) DEFAULT NULL COMMENT 'Massa Muscular em Kg',
  `proteinas` decimal(5,2) DEFAULT NULL COMMENT 'Proteínas (Kg)',
  `fat_mass_kg` decimal(5,2) DEFAULT NULL COMMENT 'Massa Gorda em Kg',
  `fat_mass_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Percentagem de Massa Gorda',
  `gordura_subcutanea` decimal(5,2) DEFAULT NULL COMMENT '% Gordura Subcutânea',
  `lean_mass_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Percentagem de Massa Magra',
  `last_assessment_date` date DEFAULT NULL COMMENT 'Data da Última Avaliação Corporal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `clients`
--

INSERT INTO `clients` (`id`, `user_id`, `date_of_birth`, `weight`, `height`, `activity_level`, `profession`, `gym_frequency`, `health_issues`, `water_percentage`, `idade_metabolica`, `morfotipo`, `ombros`, `peitoral_dorsal`, `cintura`, `abdominal`, `quadril`, `braco_direito`, `braco_esquerdo`, `coxa_direita`, `coxa_esquerda`, `perna_direita`, `perna_esquerda`, `tricipital`, `subescapular`, `bicipital`, `peitoral`, `axilar_media`, `abdominal_plicometria`, `supra_iliaca`, `coxa`, `geminal`, `visceral_fat_index`, `bone_mass_kg`, `lean_mass_kg`, `muscle_mass_kg`, `proteinas`, `fat_mass_kg`, `fat_mass_percentage`, `gordura_subcutanea`, `lean_mass_percentage`, `last_assessment_date`) VALUES
(1, 1, NULL, 75.5, 1.8, 'Moderado', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 4, '1979-08-19', 85, 1.65, 'Leve', 'Mecânico', '3 vezes por semana', 'tenho a rotula desfrianbrada', 58.50, 28, 'Mesomorfo', 40.0, 30.0, 36.0, 123.0, 23.0, 12.0, 24.0, 13.0, 23.0, 324.0, 324.0, 22.0, 11.0, 33.0, 44.0, 23.0, 12.0, 32.0, 43.0, 32.0, 5, 2.80, 60.50, 55.20, 12.30, 15.80, 22.50, 18.20, 77.50, '2025-04-23'),
(3, 16, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 21, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 22, NULL, NULL, NULL, '', 'Encanador', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 23, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 24, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `client_achievements`
--

CREATE TABLE `client_achievements` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `achievement_id` int(11) NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `client_achievements`
--

INSERT INTO `client_achievements` (`id`, `client_id`, `achievement_id`, `earned_at`) VALUES
(1, 2, 9, '2025-05-23 11:46:46'),
(2, 2, 6, '2025-05-23 13:56:28'),
(3, 2, 7, '2025-05-26 10:24:32');

-- --------------------------------------------------------

--
-- Estrutura da tabela `client_goals`
--

CREATE TABLE `client_goals` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `goal_description` text NOT NULL,
  `target_date` date DEFAULT NULL,
  `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `finished_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `client_goals`
--

INSERT INTO `client_goals` (`id`, `client_id`, `goal_description`, `target_date`, `status`, `created_at`, `updated_at`, `finished_at`) VALUES
(1, 2, 'correr 10 minutos na esteira', '2025-05-30', 'cancelled', '2025-05-26 14:50:58', '2025-05-26 14:54:42', '2025-05-26 14:54:42'),
(2, 2, 'correr 10 minutos na esteira', '2025-05-30', 'cancelled', '2025-05-26 14:54:42', '2025-05-26 15:55:38', '2025-05-26 15:55:38'),
(3, 2, 'sassasaas', '2025-05-29', 'active', '2025-05-26 15:55:38', '2025-05-26 15:55:38', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `daily_tips`
--

CREATE TABLE `daily_tips` (
  `id` int(11) NOT NULL,
  `tip_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `daily_tips`
--

INSERT INTO `daily_tips` (`id`, `tip_text`, `created_at`) VALUES
(1, 'Lembre-se de se hidratar bem e manter uma postura correta durante os exercícios!', '2025-05-22 14:38:36'),
(2, 'Uma boa noite de sono é crucial para a recuperação muscular e bem-estar.', '2025-05-22 14:38:36'),
(3, 'Não se esqueça de aquecer antes de cada treino e alongar depois.', '2025-05-22 14:38:36'),
(4, 'Varie seus treinos para desafiar diferentes grupos musculares.', '2025-05-22 14:38:36'),
(5, 'A consistência é mais importante que a intensidade no início. Crie o hábito!', '2025-05-22 14:38:36'),
(6, 'Ouça o seu corpo. Descanse quando precisar.', '2025-05-22 14:38:36'),
(7, 'Pequenas escolhas saudáveis ao longo do dia fazem uma grande diferença.', '2025-05-22 14:38:36'),
(8, 'Defina metas realistas e celebre cada conquista no seu percurso.', '2025-05-22 14:38:36'),
(9, 'Inclua frutas e vegetais coloridos na sua alimentação diária.', '2025-05-22 14:38:36'),
(10, 'Lembre-se da importância da postura, tanto nos treinos como no dia a dia.', '2025-05-22 14:38:36'),
(11, 'Experimente uma nova receita saudável esta semana.', '2025-05-22 14:38:36'),
(12, 'Faça pausas ativas durante o trabalho ou estudo para se movimentar.', '2025-05-22 14:38:36'),
(13, 'Encontre um parceiro de treino para manter a motivação.', '2025-05-22 14:38:36'),
(14, 'Não compare o seu progresso com o dos outros. Cada jornada é única.', '2025-05-22 14:38:36'),
(15, 'Planeie as suas refeições com antecedência para evitar escolhas menos saudáveis.', '2025-05-22 14:38:36'),
(16, 'Beba um copo de água assim que acordar para começar o dia hidratado.', '2025-05-22 14:38:36'),
(17, 'Substitua refrigerantes por água com limão ou chás sem açúcar.', '2025-05-22 14:38:36'),
(18, 'Adicione mais fibras à sua dieta com grãos integrais, frutas e legumes.', '2025-05-22 14:38:36'),
(19, 'Pratique a atenção plena (mindfulness) por alguns minutos todos os dias.', '2025-05-22 14:38:36'),
(20, 'Mantenha um diário alimentar para ter mais consciência do que consome.', '2025-05-22 14:38:36'),
(21, 'Desafie-se a experimentar um novo vegetal ou fruta a cada semana.', '2025-05-22 14:38:36'),
(22, 'Prepare lanches saudáveis para ter à mão quando a fome apertar.', '2025-05-22 14:38:36'),
(23, 'Aprenda a ler os rótulos dos alimentos para fazer escolhas mais informadas.', '2025-05-22 14:38:36'),
(24, 'Caminhe ou use a bicicleta para trajetos curtos em vez do carro.', '2025-05-22 14:38:36'),
(25, 'Encontre uma atividade física que realmente goste, assim será mais fácil manter-se ativo.', '2025-05-22 14:38:36'),
(26, 'Celebre as pequenas vitórias, elas são importantes para manter o ânimo.', '2025-05-22 14:38:36'),
(27, 'Não tenha medo de pedir ajuda a profissionais para atingir os seus objetivos.', '2025-05-22 14:38:36'),
(28, 'Lembre-se que o descanso é tão importante quanto o treino.', '2025-05-22 14:38:36'),
(29, 'Varie as fontes de proteína na sua alimentação (carne, peixe, ovos, leguminosas, tofu).', '2025-05-22 14:38:36'),
(30, 'Evite alimentos processados e dê preferência a alimentos frescos e naturais.', '2025-05-22 14:38:36'),
(31, 'Aquece bem antes de cada treino para evitar lesões.', '2025-05-22 15:48:01'),
(32, 'Inclui exercícios compostos como agachamento e supino na tua rotina.', '2025-05-22 15:48:01'),
(33, 'Mantém a forma correta em todos os exercícios, mesmo com menos peso.', '2025-05-22 15:48:01'),
(34, 'Descansa entre séries, mas não mais do que o necessário.', '2025-05-22 15:48:01'),
(35, 'Treina diferentes grupos musculares ao longo da semana.', '2025-05-22 15:48:01'),
(36, 'Não ignores os dias de perna!', '2025-05-22 15:48:01'),
(37, 'Aumenta gradualmente o peso para estimular o crescimento muscular.', '2025-05-22 15:48:01'),
(38, 'Inclui cardio moderado para melhorar a saúde cardiovascular.', '2025-05-22 15:48:01'),
(39, 'Hidrata-te antes, durante e depois do treino.', '2025-05-22 15:48:01'),
(40, 'Come proteína após o treino para ajudar na recuperação muscular.', '2025-05-22 15:48:01'),
(41, 'Usa um diário de treino para acompanhar a tua evolução.', '2025-05-22 15:48:01'),
(42, 'Faz alongamentos após os treinos para melhorar a flexibilidade.', '2025-05-22 15:48:01'),
(43, 'Ouvir música pode aumentar a tua motivação durante o treino.', '2025-05-22 15:48:01'),
(44, 'Não treines o mesmo músculo dois dias seguidos.', '2025-05-22 15:48:01'),
(45, 'Prioriza qualidade de movimento em vez de quantidade.', '2025-05-22 15:48:01'),
(46, 'Utiliza o tempo sob tensão para maximizar os resultados.', '2025-05-22 15:48:01'),
(47, 'Treina com um parceiro para manter a motivação alta.', '2025-05-22 15:48:01'),
(48, 'Inclui treino de core para melhorar a estabilidade corporal.', '2025-05-22 15:48:01'),
(49, 'Varie os exercícios para evitar platôs.', '2025-05-22 15:48:01'),
(50, 'Inclui exercícios com o peso do corpo como flexões e pranchas.', '2025-05-22 15:48:01'),
(51, 'Durma bem para otimizar os ganhos musculares.', '2025-05-22 15:48:01'),
(52, 'Come hidratos de carbono complexos antes do treino.', '2025-05-22 15:48:01'),
(53, 'A proteína whey pode ser útil no pós-treino.', '2025-05-22 15:48:01'),
(54, 'Aprende a escutar o teu corpo e respeitar os limites.', '2025-05-22 15:48:01'),
(55, 'Não ignores a dor – pode ser sinal de lesão.', '2025-05-22 15:48:01'),
(56, 'Evita treinar em jejum se te sentes fraco.', '2025-05-22 15:48:01'),
(57, 'Mantém uma alimentação equilibrada durante todo o dia.', '2025-05-22 15:48:01'),
(58, 'Foca-te na progressão e não na perfeição.', '2025-05-22 15:48:01'),
(59, 'Adiciona HIIT se queres queimar gordura de forma eficiente.', '2025-05-22 15:48:01'),
(60, 'Inclui o treino de mobilidade na tua rotina semanal.', '2025-05-22 15:48:01'),
(61, 'Não uses sempre as mesmas máquinas – varia os estímulos.', '2025-05-22 15:48:01'),
(62, 'Treinar menos dias com mais intensidade pode ser eficaz.', '2025-05-22 15:48:01'),
(63, 'Controla o movimento na descida (fase excêntrica) do exercício.', '2025-05-22 15:48:01'),
(64, 'Faz um plano de treino e segue-o com disciplina.', '2025-05-22 15:48:01'),
(65, 'Não te compares com os outros no ginásio – foca-te no teu progresso.', '2025-05-22 15:48:01'),
(66, 'Treinar em jejum não é ideal para todos – experimenta e avalia.', '2025-05-22 15:48:01'),
(67, 'Usa roupas confortáveis e adequadas ao tipo de treino.', '2025-05-22 15:48:01'),
(68, 'Não te esqueças de respirar corretamente durante os exercícios.', '2025-05-22 15:48:01'),
(69, 'Inclui treino de resistência para fortalecer tendões e ligamentos.', '2025-05-22 15:48:01'),
(70, 'Se não estás a ver resultados, revê a tua dieta.', '2025-05-22 15:48:01'),
(71, 'Faz um dia de descanso ativo em vez de parar completamente.', '2025-05-22 15:48:01'),
(72, 'Começa com cargas leves se estás a voltar ao ginásio após pausa.', '2025-05-22 15:48:01'),
(73, 'Faz exercícios unilaterais para corrigir desequilíbrios.', '2025-05-22 15:48:01'),
(74, 'Pede ajuda a um instrutor se tiveres dúvidas sobre algum exercício.', '2025-05-22 15:48:01'),
(75, 'Inclui superséries para treinos mais intensos e rápidos.', '2025-05-22 15:48:01'),
(76, 'O agachamento é um dos melhores exercícios para o corpo todo.', '2025-05-22 15:48:01'),
(77, 'Planeia os teus treinos com antecedência.', '2025-05-22 15:48:01'),
(78, 'Treina a mente junto com o corpo – a disciplina é chave.', '2025-05-22 15:48:01'),
(79, 'Mantém-te consistente – resultados levam tempo.', '2025-05-22 15:48:01'),
(80, 'Celebra os pequenos progressos. Cada repetição conta!', '2025-05-22 15:48:01'),
(81, 'Não treines só o que gostas – trabalha os músculos negligenciados.', '2025-05-22 15:49:37'),
(82, 'A consistência é mais poderosa do que a motivação momentânea.', '2025-05-22 15:49:37'),
(83, 'Inclui exercícios de tração como remadas para equilíbrio muscular.', '2025-05-22 15:49:37'),
(84, 'Faz pausas estratégicas na tua rotina para evitar o overtraining.', '2025-05-22 15:49:37'),
(85, 'Aprende a técnica de cada exercício antes de adicionar peso.', '2025-05-22 15:49:37'),
(86, 'Treinar com elásticos pode ser tão eficaz quanto com pesos.', '2025-05-22 15:49:37'),
(87, 'Uma boa execução vale mais do que muitas repetições mal feitas.', '2025-05-22 15:49:37'),
(88, 'Trabalha a postura com exercícios como face pulls e pranchas.', '2025-05-22 15:49:37'),
(89, 'Aumenta o desafio com tempo de descanso reduzido entre séries.', '2025-05-22 15:49:37'),
(90, 'Treina com intenção – foca-te no músculo que estás a ativar.', '2025-05-22 15:49:37'),
(91, 'Usa exercícios isométricos para fortalecer articulações e tendões.', '2025-05-22 15:49:37'),
(92, 'Aumentar o número de repetições pode ser tão eficaz quanto aumentar peso.', '2025-05-22 15:49:37'),
(93, 'Treina a pegada para melhorares o desempenho em puxadas e levantamento.', '2025-05-22 15:49:37'),
(94, 'Acorda um pouco mais cedo e começa o dia com exercício leve.', '2025-05-22 15:49:37'),
(95, 'Reavalia a tua rotina a cada 4-6 semanas para continuar a progredir.', '2025-05-22 15:49:37'),
(96, 'Treinos em circuito são ótimos para trabalhar força e cardio ao mesmo tempo.', '2025-05-22 15:49:37'),
(97, 'A mente cansada também afeta o rendimento físico – dorme bem.', '2025-05-22 15:49:37'),
(98, 'Inclui exercícios de rotação externa para proteger os ombros.', '2025-05-22 15:49:37'),
(99, 'Evita conversar demais durante o treino – mantém o foco.', '2025-05-22 15:49:37'),
(100, 'Treina com progressão inteligente, não com pressa.', '2025-05-22 15:49:37');

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
  `title` varchar(255) DEFAULT NULL,
  `plan_details` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 0 COMMENT 'Indica se o plano de dieta está ativo (1=Sim, 0=Não)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `diet_plans`
--

INSERT INTO `diet_plans` (`id`, `client_id`, `nutritionist_id`, `title`, `plan_details`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 1, 3, NULL, 'Café da manhã: Omelete com vegetais; Almoço: Frango grelhado com arroz integral; Jantar: Salada de quinoa.', '2025-02-16 16:00:41', '2025-05-16 11:24:56', 1),
(3, 2, 7, 'Plano de perde de massa', 'adssadadsdadasdasdad', '2025-05-16 10:53:12', '2025-05-16 11:46:45', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `diet_plan_meals`
--

CREATE TABLE `diet_plan_meals` (
  `id` int(11) NOT NULL,
  `diet_plan_id` int(11) NOT NULL,
  `meal_name` varchar(255) NOT NULL COMMENT 'Ex: Dia 1 - Pequeno-almoço, Dia 1 - Almoço',
  `meal_order` int(11) DEFAULT 0 COMMENT 'Ordem da refeição dentro do plano',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `diet_plan_meals`
--

INSERT INTO `diet_plan_meals` (`id`, `diet_plan_id`, `meal_name`, `meal_order`, `created_at`) VALUES
(7, 3, 'Dia 1 - Pequeno-almoço', 0, '2025-05-16 11:49:23'),
(8, 3, 'Dia 1 - Lanche Manhã', 1, '2025-05-16 11:49:23'),
(9, 3, 'Dia 1 - Almoço', 2, '2025-05-16 11:49:23'),
(10, 3, 'Dia 1 - Lanche Tarde', 3, '2025-05-16 11:49:23'),
(11, 3, 'Dia 1 - Jantar', 4, '2025-05-16 11:49:23'),
(12, 3, 'Dia 1 - Ceia', 5, '2025-05-16 11:49:23');

-- --------------------------------------------------------

--
-- Estrutura da tabela `diet_plan_meal_foods`
--

CREATE TABLE `diet_plan_meal_foods` (
  `id` int(11) NOT NULL,
  `diet_plan_meal_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL COMMENT 'Notas específicas para este alimento nesta refeição',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `diet_plan_meal_foods`
--

INSERT INTO `diet_plan_meal_foods` (`id`, `diet_plan_meal_id`, `food_id`, `quantity`, `notes`, `created_at`) VALUES
(7, 7, 19, 2.00, '', '2025-05-16 11:49:23'),
(8, 8, 2, 1.00, '', '2025-05-16 11:49:23'),
(9, 9, 14, 2.00, '', '2025-05-16 11:49:23'),
(10, 10, 14, 1.00, '', '2025-05-16 11:49:23'),
(11, 11, 16, 2.00, '', '2025-05-16 11:49:23'),
(12, 12, 17, 1.00, '', '2025-05-16 11:49:23');

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
(1, 'Bench Press', 'Lie on a bench and press a barbell or dumbbells upwards.', 'Chest', 'Barbell, Bench', 'BenchPress.jpg', 'https://www.youtube.com/watch?v=SCVCLChPQFY'),
(2, 'Squats', 'Stand with feet shoulder-width apart and lower your hips as if sitting in a chair.', 'Legs', 'Barbell, Rack', 'Squats.jpg', 'https://www.youtube.com/watch?v=5YXfqBuDA40'),
(3, 'Deadlifts', 'Lift a barbell from the floor to a standing position.', 'Back', 'Barbell', 'Deadlifts.jpg', 'https://www.youtube.com/watch?v=op9kVnSso6Q'),
(4, 'Push-ups', 'Start in a plank position and lower your body towards the floor.', 'Chest', 'Bodyweight', 'Push-ups.jpg', 'https://www.youtube.com/watch?v=_l3ySVKYVJ8'),
(5, 'Pull-ups', 'Hang from a bar and pull yourself up until your chin is over the bar.', 'Back', 'Pull-up Bar', 'Pull-ups.jpg', 'https://www.youtube.com/watch?v=aAggnpPyR6E'),
(6, 'Overhead Press', 'Stand with feet shoulder-width apart and press a barbell or dumbbells upwards.', 'Shoulders', 'Barbell, Dumbbells', 'Overhead-Press.jpg', 'https://www.youtube.com/watch?v=AqzDJHxynwo'),
(7, 'Bicep Curls', 'Stand with feet shoulder-width apart and curl a barbell or dumbbells upwards.', 'Biceps', 'Barbell, Dumbbells', 'Bicep-Curls.jpg', 'https://www.youtube.com/watch?v=Nkl8WnH6tDU'),
(8, 'Tricep Extensions', 'Stand with feet shoulder-width apart and extend a barbell or dumbbells upwards.', 'Triceps', 'Barbell, Dumbbells', 'Tricep-Extensions.jpg', 'https://www.youtube.com/watch?v=nRiJVZDpdL0'),
(9, 'Lunges', 'Step forward with one leg and lower your hips until both knees are bent at about a 90-degree angle.', 'Legs', 'Bodyweight, Dumbbells', 'Lunges.jpg', 'https://www.youtube.com/watch?v=ReNofXwy_js'),
(10, 'Plank', 'Hold a push-up position with your forearms on the ground.', 'Core', 'Bodyweight', 'Plank.jpg', 'https://www.youtube.com/watch?v=sZxrs3C209k'),
(11, 'Barbell Bench Press - Incline', 'Incline bench press using a barbell.', 'Chest', 'Barbell', 'barbell_bench_press_incline.jpg', 'https://www.youtube.com/watch?v=11gY7Q5D5wo'),
(12, 'Dumbbell Bench Press - Decline', 'Decline bench press using dumbbells.', 'Chest', 'Dumbbells', 'dumbbell_bench_press_decline.jpg', 'https://www.youtube.com/watch?v=mlXPNdw2Sns'),
(13, 'Pull-Ups - Wide Grip', 'Pull-ups with a wider grip.', 'Back', 'Pull-Up Bar', 'pull_ups_wide_grip.jpg', 'https://www.youtube.com/watch?v=iywjqUo5nmU'),
(14, 'Push-Ups - Diamond', 'Push-ups with hands close together in a diamond shape.', 'Chest', 'Bodyweight', 'push_ups_diamond.jpg', 'https://www.youtube.com/watch?v=J0DnG1_S92I'),
(15, 'Squats - Front', 'Front squats with a barbell.', 'Legs', 'Barbell', 'squats_front.jpg', 'https://www.youtube.com/watch?v=uYumuL_G_V0'),
(16, 'Deadlift - Romanian', 'Romanian deadlifts with a barbell.', 'Back', 'Barbell', 'deadlift_romanian.jpg', 'https://www.youtube.com/watch?v=2SHsk9AzdjA'),
(17, 'Overhead Press - Dumbbell', 'Overhead press using dumbbells.', 'Shoulders', 'Dumbbells', 'overhead_press_dumbbell.jpg', 'https://www.youtube.com/watch?v=M2rwvNhTOu0'),
(18, 'Bicep Curls - Hammer', 'Hammer curls using dumbbells.', 'Biceps', 'Dumbbells', 'bicep_curls_hammer.jpg', 'https://www.youtube.com/watch?v=zC3nLlEvin4'),
(19, 'Tricep Extensions - Overhead', 'Overhead tricep extensions using a dumbbell.', 'Triceps', 'Dumbbell', 'tricep_extensions_overhead.jpg', 'https://www.youtube.com/watch?v=5crTU1JiMfU'),
(20, 'Cable Flyes', 'Chest flyes using a cable machine.', 'Chest', 'Cable Machine', 'cable_flyes.jpg', 'https://www.youtube.com/watch?v=PRw7ieDBLl4'),
(21, 'Lateral Raises', 'Raising dumbbells to the side.', 'Shoulders', 'Dumbbells', 'lateral_raises.jpg', 'https://www.youtube.com/watch?v=3VcKaXpzqRo'),
(22, 'Leg Press', 'Pressing weight with your legs on a leg press machine.', 'Legs', 'Leg Press Machine', 'leg_press.jpg', 'https://www.youtube.com/watch?v=IZxyjW7MPJQ'),
(23, 'Leg Extensions', 'Extending your legs against resistance on a leg extension machine.', 'Legs', 'Leg Extension Machine', 'leg_extensions.jpg', 'https://www.youtube.com/watch?v=YyvSfVjQeL0'),
(24, 'Hamstring Curls', 'Curling your legs against resistance on a hamstring curl machine.', 'Legs', 'Hamstring Curl Machine', 'hamstring_curls.jpg', 'https://www.youtube.com/watch?v=1Tq3QdYUuHs'),
(25, 'Standing Calf Raises', 'Raising up on your toes to work your calves.', 'Legs', 'Bodyweight', 'Standing_calf_raises.jpg', 'https://www.youtube.com/watch?v=MAMzF7iZNkc'),
(27, 'Russian Twists', 'Twisting your torso with a weight to work your obliques.', 'Core', 'Dumbbell', 'russian_twists.jpg', 'https://www.youtube.com/watch?v=wkD8rjkodUI'),
(28, 'Bicycle Crunches', 'Alternating crunches to work your abs.', 'Core', 'Bodyweight', 'bicycle_crunches.jpg', 'https://www.youtube.com/watch?v=eqg47ZuGZXQ'),
(29, 'Face Pulls', 'Pulling a cable towards your face to work your rear delts.', 'Shoulders', 'Cable Machine', 'face_pulls.jpg', 'https://www.youtube.com/watch?v=V8dZ3pyiCBo'),
(30, 'Chest Dips', 'Dips to work your chest and triceps.', 'Chest', 'Dip Bars', 'Chest_Dips.jpg', 'https://www.youtube.com/watch?v=dX_nSOOJIsE'),
(31, 'Lat Pulldowns', 'Pulling down a bar to work your lats.', 'Back', 'Cable Machine', 'lat_Pulldowns.jpg', 'https://www.youtube.com/watch?v=AOpi-p0cJkc'),
(32, 'Seated Cable Rows', 'Pulling a cable towards your chest while seated.', 'Back', 'Cable Machine', 'seated_cable_rows.jpg', 'https://www.youtube.com/watch?v=GZbfZ033f74'),
(33, 'Dumbbell Rows', 'Rowing a dumbbell towards your chest.', 'Back', 'Dumbbell', 'dumbbell_rows.jpg', 'https://www.youtube.com/watch?v=pYcpY20QaE8'),
(34, 'Barbell Rows', 'Rowing a barbell towards your chest.', 'Back', 'Barbell', 'barbell_rows.jpg', 'https://www.youtube.com/watch?v=Nqh7q3zDCoQ'),
(35, 'Arnold Press', 'A variation of the overhead press that involves rotating the dumbbells.', 'Shoulders', 'Dumbbells', 'arnold_press.jpg', 'https://www.youtube.com/watch?v=3ml7BH7mNwQ'),
(36, 'Lateral Raises - Cable', 'Lateral raises using a cable machine.', 'Shoulders', 'Cable Machine', 'lateral_raises_cable.jpg', 'https://www.youtube.com/watch?v=f_OGBg2KxgY'),
(37, 'Front Raises', 'Raising dumbbells to the front.', 'Shoulders', 'Dumbbells', 'Dumbbell_front_raises.jpg', 'https://www.youtube.com/watch?v=-t7fuZ0KhDA'),
(38, 'Reverse Flyes', 'Reverse flyes using dumbbells.', 'Shoulders', 'Dumbbells', 'Dumbbell_reverse_flyes.jpg', 'https://www.youtube.com/watch?v=evXOlgLTPCw'),
(39, 'Concentration Curls', 'Bicep curls with a focus on isolation.', 'Biceps', 'Dumbbells', 'concentration_curls.jpg', 'https://www.youtube.com/watch?v=0AUGkch3tzc'),
(40, 'Preacher Curls', 'Bicep curls using a preacher bench.', 'Biceps', 'Dumbbells', 'preacher_curls.jpg', 'https://www.youtube.com/watch?v=fIWP-FRFNU0'),
(41, 'Skullcrushers', 'Lying tricep extensions.', 'Triceps', 'Barbell', 'skullcrushers.jpg', 'https://www.youtube.com/watch?v=d_KZxkY_0cM'),
(42, 'Tricep Pushdowns', 'Pushing down a cable to work your triceps.', 'Triceps', 'Cable Machine', 'tricep_pushdowns.jpg', 'https://www.youtube.com/watch?v=2-LAMcpzODU'),
(44, 'Glute Bridges', 'Lifting your hips off the ground to work your glutes.', 'Legs', 'Bodyweight', 'glute_bridges.jpg', 'https://www.youtube.com/watch?v=wPM8icPu6H8'),
(45, 'Hip Thrusts', 'Lifting your hips off the ground with a barbell.', 'Legs', 'Barbell', 'hip_thrusts.jpg', 'https://www.youtube.com/watch?v=SEdqd1n0cvg'),
(46, 'Good Mornings', 'Bending forward at the hips to work your hamstrings and lower back.', 'Legs', 'Barbell', 'good_mornings.jpg', 'https://www.youtube.com/watch?v=5Xj6XUa77qc'),
(47, 'Crunches', 'Curling your torso to work your abs.', 'Core', 'Bodyweight', 'crunches.jpg', 'https://www.youtube.com/watch?v=Xyd_fa5zoEU'),
(48, 'Leg Raises', 'Raising your legs to work your lower abs.', 'Core', 'Bodyweight', 'leg_raises.jpg', 'https://www.youtube.com/watch?v=JB2oyawG9KI'),
(49, 'Hanging Leg Raises', 'Raising your legs while hanging from a bar.', 'Core', 'Pull-Up Bar', 'hanging_leg_raises.jpg', 'https://www.youtube.com/watch?v=Nw0LOKe3_l8');

-- --------------------------------------------------------

--
-- Estrutura da tabela `foods`
--

CREATE TABLE `foods` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL COMMENT 'Nome do alimento',
  `calories` decimal(10,2) DEFAULT NULL COMMENT 'Calorias por porção (kcal)',
  `protein` decimal(10,2) DEFAULT NULL COMMENT 'Proteínas por porção (g)',
  `carbs` decimal(10,2) DEFAULT NULL COMMENT 'Carboidratos por porção (g)',
  `fat` decimal(10,2) DEFAULT NULL COMMENT 'Gorduras por porção (g)',
  `serving_size` varchar(50) DEFAULT NULL COMMENT 'Tamanho da porção (ex: 100, 1, 2)',
  `unit` varchar(50) DEFAULT NULL COMMENT 'Unidade da porção (ex: g, ml, unidade, fatia)',
  `category_id` int(11) DEFAULT NULL COMMENT 'FK para food_categories.id',
  `added_by_nutritionist_id` int(11) DEFAULT NULL COMMENT 'ID do nutricionista (users.id) que adicionou o alimento',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabela para armazenar alimentos e seus dados nutricionais';

--
-- Extraindo dados da tabela `foods`
--

INSERT INTO `foods` (`id`, `name`, `calories`, `protein`, `carbs`, `fat`, `serving_size`, `unit`, `category_id`, `added_by_nutritionist_id`, `created_at`, `updated_at`) VALUES
(1, 'bolinhos de bacalhau', 150.00, 300.00, 200.00, 100.00, '4', '2', NULL, 7, '2025-05-05 14:40:53', '2025-05-05 14:40:53'),
(2, 'Iogurte Natural Magro', 55.00, 5.00, 7.00, 0.50, '1', 'unidade (125g)', 1, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(3, 'Iogurte Grego Natural 0%', 70.00, 12.00, 4.00, 0.00, '1', 'unidade (150g)', 1, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(4, 'Iogurte Líquido Magro Frutos Vermelhos', 80.00, 4.00, 15.00, 0.20, '1', 'garrafa (180ml)', 1, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(5, 'Pão de Forma Integral', 75.00, 3.00, 13.00, 1.00, '1', 'fatia (30g)', 2, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(6, 'Pão de Centeio', 85.00, 3.50, 15.00, 1.20, '1', 'fatia (35g)', 2, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(7, 'Pão de Mistura', 90.00, 3.20, 17.00, 1.10, '1', 'unidade (40g)', 2, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(8, 'Maçã Fuji', 70.00, 0.30, 18.00, 0.20, '1', 'unidade média (150g)', 3, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(9, 'Banana', 105.00, 1.30, 27.00, 0.30, '1', 'unidade média (120g)', 3, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(10, 'Morangos', 32.00, 0.70, 7.70, 0.30, '100', 'g', 3, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(11, 'Alface Iceberg', 14.00, 0.90, 2.40, 0.10, '100', 'g', 4, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(12, 'Brócolos Cozidos', 55.00, 3.70, 11.20, 0.60, '1', 'chávena (150g)', 4, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(13, 'Cenoura Crua', 41.00, 0.90, 9.60, 0.20, '100', 'g', 4, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(14, 'Peito de Frango Grelhado', 165.00, 31.00, 0.00, 3.60, '100', 'g', 5, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(15, 'Bife de Vaca Magro Grelhado', 250.00, 36.00, 0.00, 10.00, '100', 'g', 5, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(16, 'Salmão Grelhado', 208.00, 20.00, 0.00, 13.00, '100', 'g', 6, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(17, 'Pescada Cozida', 80.00, 18.00, 0.00, 0.70, '100', 'g', 6, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(18, 'Barra Proteica Chocolate', 200.00, 20.00, 15.00, 8.00, '1', 'unidade (50g)', 7, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(19, 'Barra de Cereais Frutos Secos', 150.00, 3.00, 25.00, 5.00, '1', 'unidade (35g)', 7, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(20, 'Chocolate Negro 70%', 140.00, 2.00, 10.00, 11.00, '25', 'g (1/4 tablete)', 8, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(21, 'Chocolate de Leite', 135.00, 1.80, 15.00, 8.00, '25', 'g (1/4 tablete)', 8, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(22, 'Aveia em Flocos', 150.00, 5.00, 27.00, 2.50, '40', 'g (4 colheres sopa)', 9, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(23, 'Arroz Integral Cozido', 130.00, 2.70, 27.00, 1.00, '1', 'chávena (150g)', 9, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(24, 'Feijão Preto Cozido', 132.00, 8.90, 23.70, 0.50, '1', 'chávena (170g)', 10, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27'),
(25, 'Grão-de-bico Cozido', 164.00, 8.90, 27.40, 2.60, '1', 'chávena (160g)', 10, 2, '2025-05-05 14:57:27', '2025-05-05 14:57:27');

-- --------------------------------------------------------

--
-- Estrutura da tabela `food_categories`
--

CREATE TABLE `food_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Nome da categoria (ex: Iogurtes, Pães, Frutas)',
  `description` text DEFAULT NULL COMMENT 'Descrição opcional da categoria',
  `display_order` int(11) DEFAULT 0 COMMENT 'Ordem para exibição na página do cliente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Categorias para agrupar alimentos';

--
-- Extraindo dados da tabela `food_categories`
--

INSERT INTO `food_categories` (`id`, `name`, `description`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'Iogurtes', 'Produtos lácteos fermentados.', 10, '2025-05-05 14:56:49', '2025-05-05 14:56:49'),
(2, 'Pães', 'Alimentos à base de farinha, água e fermento.', 20, '2025-05-05 14:56:49', '2025-05-05 14:56:49'),
(3, 'Frutas', 'Frutos comestíveis de plantas.', 5, '2025-05-05 14:56:49', '2025-05-05 14:56:49'),
(4, 'Vegetais', 'Partes comestíveis de plantas (folhas, caules, raízes).', 6, '2025-05-05 14:56:49', '2025-05-05 14:56:49'),
(5, 'Carnes e Aves', 'Fontes de proteína animal.', 30, '2025-05-05 14:56:49', '2025-05-05 14:56:49'),
(6, 'Peixes', 'Fontes de proteína e ómega 3.', 35, '2025-05-05 14:56:49', '2025-05-05 14:56:49'),
(7, 'Barras Energéticas/Proteicas', 'Barras convenientes para lanches.', 40, '2025-05-05 14:56:49', '2025-05-05 14:56:49'),
(8, 'Chocolates', 'Produtos derivados do cacau.', 50, '2025-05-05 14:56:49', '2025-05-05 14:56:49'),
(9, 'Cereais e Grãos', 'Fontes de carboidratos complexos e fibras.', 15, '2025-05-05 14:56:49', '2025-05-05 14:56:49'),
(10, 'Leguminosas', 'Feijões, lentilhas, grão-de-bico, etc.', 25, '2025-05-05 14:56:49', '2025-05-05 14:56:49');

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
-- Estrutura da tabela `history_assessments`
--

CREATE TABLE `history_assessments` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL COMMENT 'FK para clients.id',
  `assessment_date` date NOT NULL COMMENT 'Data em que esta avaliação foi feita',
  `recorded_by_user_id` int(11) DEFAULT NULL COMMENT 'FK para users.id (quem registou)',
  `weight` decimal(5,1) DEFAULT NULL COMMENT 'Peso (Kg) na data da avaliação',
  `height` decimal(3,2) DEFAULT NULL COMMENT 'Altura (m) na data da avaliação',
  `fat_mass_percentage` decimal(5,2) DEFAULT NULL,
  `fat_mass_kg` decimal(5,2) DEFAULT NULL,
  `gordura_subcutanea` decimal(5,2) DEFAULT NULL,
  `visceral_fat_index` int(11) DEFAULT NULL,
  `muscle_mass_kg` decimal(5,2) DEFAULT NULL,
  `lean_mass_kg` decimal(5,2) DEFAULT NULL,
  `proteinas` decimal(5,2) DEFAULT NULL,
  `bone_mass_kg` decimal(5,2) DEFAULT NULL,
  `water_percentage` decimal(5,2) DEFAULT NULL,
  `idade_metabolica` int(11) DEFAULT NULL,
  `morfotipo` varchar(50) DEFAULT NULL,
  `lean_mass_percentage` decimal(5,2) DEFAULT NULL,
  `ombros` decimal(5,1) DEFAULT NULL,
  `peitoral_dorsal` decimal(5,1) DEFAULT NULL,
  `cintura` decimal(5,1) DEFAULT NULL,
  `abdominal` decimal(5,1) DEFAULT NULL,
  `quadril` decimal(5,1) DEFAULT NULL,
  `braco_direito` decimal(5,1) DEFAULT NULL,
  `braco_esquerdo` decimal(5,1) DEFAULT NULL,
  `coxa_direita` decimal(5,1) DEFAULT NULL,
  `coxa_esquerda` decimal(5,1) DEFAULT NULL,
  `perna_direita` decimal(5,1) DEFAULT NULL,
  `perna_esquerda` decimal(5,1) DEFAULT NULL,
  `tricipital` decimal(4,1) DEFAULT NULL,
  `subescapular` decimal(4,1) DEFAULT NULL,
  `bicipital` decimal(4,1) DEFAULT NULL,
  `peitoral` decimal(4,1) DEFAULT NULL,
  `axilar_media` decimal(4,1) DEFAULT NULL,
  `abdominal_plicometria` decimal(4,1) DEFAULT NULL,
  `supra_iliaca` decimal(4,1) DEFAULT NULL,
  `coxa` decimal(4,1) DEFAULT NULL,
  `geminal` decimal(4,1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data/Hora de inserção do registo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `history_assessments`
--

INSERT INTO `history_assessments` (`id`, `client_id`, `assessment_date`, `recorded_by_user_id`, `weight`, `height`, `fat_mass_percentage`, `fat_mass_kg`, `gordura_subcutanea`, `visceral_fat_index`, `muscle_mass_kg`, `lean_mass_kg`, `proteinas`, `bone_mass_kg`, `water_percentage`, `idade_metabolica`, `morfotipo`, `lean_mass_percentage`, `ombros`, `peitoral_dorsal`, `cintura`, `abdominal`, `quadril`, `braco_direito`, `braco_esquerdo`, `coxa_direita`, `coxa_esquerda`, `perna_direita`, `perna_esquerda`, `tricipital`, `subescapular`, `bicipital`, `peitoral`, `axilar_media`, `abdominal_plicometria`, `supra_iliaca`, `coxa`, `geminal`, `created_at`) VALUES
(1, 2, '2025-04-23', 6, 85.0, 9.99, 22.50, 15.80, 18.20, 5, 55.20, 60.50, 12.30, 2.80, 58.50, 28, NULL, 77.50, 40.0, 30.0, 36.0, 123.0, 23.0, 12.0, 24.0, 13.0, 23.0, 324.0, 324.0, 22.0, 11.0, 33.0, 44.0, 23.0, 12.0, 32.0, 43.0, 32.0, '2025-04-22 15:14:57'),
(2, 2, '2025-04-22', 6, 85.0, 1.65, 10.00, 20.00, 11.00, 21, 12.00, 22.00, 23.00, 14.00, 24.00, 53, 'Endomorfo', 13.00, 30.0, 40.0, 31.0, 41.0, 32.0, 42.0, 33.0, 43.0, 34.0, 44.0, 35.0, 21.0, 31.0, 22.0, 32.0, 23.0, 33.0, 24.0, 34.0, 25.0, '2025-04-22 15:26:26'),
(3, 2, '2025-04-23', 6, 85.0, 9.99, 22.50, 15.80, 18.20, 5, 55.20, 60.50, 12.30, 2.80, 58.50, 28, 'Endomorfo', 77.50, 40.0, 30.0, 36.0, 123.0, 23.0, 12.0, 24.0, 13.0, 23.0, 324.0, 324.0, 22.0, 11.0, 33.0, 44.0, 23.0, 12.0, 32.0, 43.0, 32.0, '2025-04-23 10:40:47'),
(4, 2, '2025-04-23', 6, 85.0, 9.99, 22.50, 15.80, 18.20, 5, 55.20, 60.50, 12.30, 2.80, 58.50, 28, 'Endomorfo', 77.50, 40.0, 30.0, 36.0, 123.0, 23.0, 12.0, 24.0, 13.0, 23.0, 324.0, 324.0, 22.0, 11.0, 33.0, 44.0, 23.0, 12.0, 32.0, 43.0, 32.0, '2025-04-23 10:41:02'),
(5, 2, '2025-04-23', 6, 85.0, 1.65, 22.50, 15.80, 18.20, 5, 55.20, 60.50, 12.30, 2.80, 58.50, 28, 'Endomorfo', 77.50, 40.0, 30.0, 36.0, 123.0, 23.0, 12.0, 24.0, 13.0, 23.0, 324.0, 324.0, 22.0, 11.0, 33.0, 44.0, 23.0, 12.0, 32.0, 43.0, 32.0, '2025-04-23 10:43:37');

-- --------------------------------------------------------

--
-- Estrutura da tabela `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_type` enum('text','image','audio','video') NOT NULL DEFAULT 'text',
  `message` text DEFAULT NULL,
  `file_url` varchar(512) DEFAULT NULL COMMENT 'Path or URL to the uploaded file',
  `file_name` varchar(255) DEFAULT NULL COMMENT 'Original name of the uploaded file',
  `file_size` int(11) UNSIGNED DEFAULT NULL COMMENT 'File size in bytes',
  `duration` int(11) UNSIGNED DEFAULT NULL COMMENT 'Duration in seconds for audio/video',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message_type`, `message`, `file_url`, `file_name`, `file_size`, `duration`, `timestamp`, `is_read`) VALUES
(1, 4, 6, 'text', 'ola , tenho uma duvida!', NULL, NULL, NULL, NULL, '2025-04-14 10:42:14', 0),
(2, 6, 4, 'text', 'ola!! ,', NULL, NULL, NULL, NULL, '2025-04-14 10:46:07', 0),
(3, 6, 4, 'text', 'qual a sua duvida?', NULL, NULL, NULL, NULL, '2025-04-14 10:46:13', 0),
(4, 22, 2, 'text', 'Ola Maria!!', NULL, NULL, NULL, NULL, '2025-04-14 10:52:10', 0),
(5, 2, 22, 'text', 'saudações Arbusto!!', NULL, NULL, NULL, NULL, '2025-04-14 10:53:16', 0),
(6, 4, 6, 'text', 'aqui esta um audio!!', NULL, NULL, NULL, NULL, '2025-04-15 11:13:16', 0),
(7, 4, 6, 'audio', 'aqui está o audio!!', '/Projeto_PAP/uploads/chat_media/audio/chat_4_67fe443b3f8d16.03378714.mp3', 'chico quer um beijinho.mp3', 150193, NULL, '2025-04-15 11:34:19', 0),
(8, 4, 6, 'image', 'foto apos fazer o cardio!!', '/Projeto_PAP/uploads/chat_media/images/chat_4_67fe448914dc93.57780185.png', 'pavlidis.png', 10740, NULL, '2025-04-15 11:35:37', 0),
(9, 4, 6, 'image', 'foto do tio da lucia', '/Projeto_PAP/uploads/chat_media/images/chat_4_67fe4561430cb2.77287236.png', 'taberna189696.png', 593417, NULL, '2025-04-15 11:39:13', 0),
(10, 4, 6, 'video', 'condução do ano!!', '/Projeto_PAP/uploads/chat_media/video/chat_4_67fe45d44a5498.29705031.mp4', 'Design sem nome.mp4', 8466488, NULL, '2025-04-15 11:41:08', 0),
(11, 6, 1, 'text', 'Olá João!!', NULL, NULL, NULL, NULL, '2025-04-22 14:35:14', 0),
(12, 4, 2, 'text', 'olá boa tarde tenho uma duvida sobre a aula!', NULL, NULL, NULL, NULL, '2025-05-07 15:33:15', 0),
(13, 2, 4, 'text', 'Que duvida tem?', NULL, NULL, NULL, NULL, '2025-05-07 15:40:15', 0),
(14, 4, 22, 'text', 'Olá hoje vais a aula?', NULL, NULL, NULL, NULL, '2025-05-07 16:25:24', 0),
(15, 22, 4, 'text', 'Vou sim e tu?', NULL, NULL, NULL, NULL, '2025-05-07 16:25:54', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `mood_logs`
--

CREATE TABLE `mood_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mood_value` tinyint(1) NOT NULL COMMENT '1:Péssimo, 2:Mau, 3:Neutro, 4:Bom, 5:Ótimo',
  `assessment_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `mood_logs`
--

INSERT INTO `mood_logs` (`id`, `user_id`, `mood_value`, `assessment_date`, `created_at`) VALUES
(1, 4, 5, '2025-05-22', '2025-05-22 16:23:49'),
(8, 4, 2, '2025-05-23', '2025-05-23 09:24:44'),
(22, 4, 5, '2025-05-26', '2025-05-26 14:38:56');

-- --------------------------------------------------------

--
-- Estrutura da tabela `motivational_quotes`
--

CREATE TABLE `motivational_quotes` (
  `id` int(11) NOT NULL,
  `quote_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `motivational_quotes`
--

INSERT INTO `motivational_quotes` (`id`, `quote_text`, `created_at`) VALUES
(1, 'Cada treino é um passo mais perto do teu objetivo!', '2025-05-22 14:38:36'),
(2, 'Não pares agora — o teu futuro eu vai agradecer!', '2025-05-22 14:38:36'),
(3, 'Acredite em si mesmo e tudo será possível.', '2025-05-22 14:38:36'),
(4, 'A persistência realiza o impossível.', '2025-05-22 14:38:36'),
(5, 'Transforma o \"não consigo\" em \"ainda não consigo\".', '2025-05-22 14:38:36'),
(6, 'O corpo alcança o que a mente acredita.', '2025-05-22 14:38:36'),
(7, 'A disciplina é a ponte entre metas e realizações.', '2025-05-22 14:38:36'),
(8, 'Pequenos progressos diários resultam em grandes conquistas.', '2025-05-22 14:38:36'),
(9, 'A dor que sentes hoje será a força que sentirás amanhã.', '2025-05-22 14:38:36'),
(10, 'Sê mais forte que a tua melhor desculpa.', '2025-05-22 14:38:36'),
(11, 'O único treino mau é aquele que não aconteceu.', '2025-05-22 14:38:36'),
(12, 'Acredite no processo. Os resultados virão.', '2025-05-22 14:38:36'),
(13, 'Supere-se a cada dia. Você é o seu maior desafio.', '2025-05-22 14:38:36'),
(14, 'A força não vem da capacidade física, mas de uma vontade indomável.', '2025-05-22 14:38:36'),
(15, 'Não espere por oportunidades, crie-as.', '2025-05-22 14:38:36'),
(16, 'O sucesso é a soma de pequenos esforços repetidos dia após dia.', '2025-05-22 14:38:36'),
(17, 'Se pode sonhar, pode realizar.', '2025-05-22 14:38:36'),
(18, 'A jornada de mil milhas começa com um único passo.', '2025-05-22 14:38:36'),
(19, 'Acredite que você pode, e você já está no meio do caminho.', '2025-05-22 14:38:36'),
(20, 'A melhor maneira de prever o futuro é criá-lo.', '2025-05-22 14:38:36'),
(21, 'A sua saúde é o seu maior investimento.', '2025-05-22 14:38:36'),
(22, 'Não se trata de ser o melhor. Trata-se de ser melhor do que eras ontem.', '2025-05-22 14:38:36'),
(23, 'A diferença entre o ordinário e o extraordinário é aquele pequeno extra.', '2025-05-22 14:38:36'),
(24, 'Seja a energia que você quer atrair.', '2025-05-22 14:38:36'),
(25, 'Acredite nos seus sonhos e eles se tornarão realidade. Acredite em você e você os tornará realidade.', '2025-05-22 14:38:36'),
(26, 'O caminho para o sucesso está sempre em construção.', '2025-05-22 14:38:36'),
(27, 'A motivação é o que te faz começar. O hábito é o que te faz continuar.', '2025-05-22 14:38:36'),
(28, 'Não conte os dias, faça os dias contarem.', '2025-05-22 14:38:36'),
(29, 'A vida é como andar de bicicleta. Para manter o equilíbrio, é preciso continuar em movimento.', '2025-05-22 14:38:36'),
(30, 'O seu corpo é o seu templo. Cuide bem dele.', '2025-05-22 14:38:36'),
(31, 'A consistência supera o talento quando o talento não é consistente.', '2025-05-22 16:01:41'),
(32, 'Não esperes pela motivação — age e ela virá contigo.', '2025-05-22 16:01:41'),
(33, 'O suor de hoje é o sucesso de amanhã.', '2025-05-22 16:01:41'),
(34, 'Treinar é difícil. Arrepender-se por não treinar é pior.', '2025-05-22 16:01:41'),
(35, 'Vence o cansaço com a força do teu propósito.', '2025-05-22 16:01:41'),
(36, 'Levanta-te. Treina. Supera-te.', '2025-05-22 16:01:41'),
(37, 'Um corpo forte começa com uma mente determinada.', '2025-05-22 16:01:41'),
(38, 'Cada repetição aproxima-te da tua melhor versão.', '2025-05-22 16:01:41'),
(39, 'Quando estiveres cansado, lembra-te por que começaste.', '2025-05-22 16:01:41'),
(40, 'Não desistas. O progresso está mais perto do que parece.', '2025-05-22 16:01:41'),
(41, 'A dor é temporária. O orgulho é para sempre.', '2025-05-22 16:01:41'),
(42, 'Transforma esforço em conquista.', '2025-05-22 16:01:41'),
(43, 'Disciplina: fazer o que tem de ser feito, mesmo sem vontade.', '2025-05-22 16:01:41'),
(44, 'Não há resultados sem esforço.', '2025-05-22 16:01:41'),
(45, 'Quanto mais suar no treino, menos sangrarás na batalha.', '2025-05-22 16:01:41'),
(46, 'O teu único limite és tu mesmo.', '2025-05-22 16:01:41'),
(47, 'A mente comanda o corpo. Fortalece a tua mente.', '2025-05-22 16:01:41'),
(48, 'Não há atalhos para um corpo forte e saudável.', '2025-05-22 16:01:41'),
(49, 'Acredita. Treina. Alcança.', '2025-05-22 16:01:41'),
(50, 'A motivação abre a porta. A disciplina mantém-te dentro.', '2025-05-22 16:01:41'),
(51, 'Cada treino é um voto de confiança em ti mesmo.', '2025-05-22 16:01:41'),
(52, 'Não é sobre ser o mais rápido, é sobre nunca parar.', '2025-05-22 16:01:41'),
(53, 'Treina duro, repousa bem, volta mais forte.', '2025-05-22 16:01:41'),
(54, 'Os resultados aparecem quando a dedicação se torna hábito.', '2025-05-22 16:01:41'),
(55, 'A superação começa com uma decisão.', '2025-05-22 16:01:41'),
(56, 'Desafia-te todos os dias a seres melhor do que ontem.', '2025-05-22 16:01:41'),
(57, 'Não pares até te orgulhares.', '2025-05-22 16:01:41'),
(58, 'Grandes mudanças exigem grandes esforços.', '2025-05-22 16:01:41'),
(59, 'A força física vem do treino. A força interior vem da vontade.', '2025-05-22 16:01:41'),
(60, 'Tu és mais forte do que pensas.', '2025-05-22 16:01:41'),
(61, 'Foco, força e fé — o trio do sucesso.', '2025-05-22 16:01:41'),
(62, 'Não deixes que o medo de falhar te impeça de tentar.', '2025-05-22 16:01:41'),
(63, 'Só não alcança quem desiste.', '2025-05-22 16:01:41'),
(64, 'Cada gota de suor é um passo para a vitória.', '2025-05-22 16:01:41'),
(65, 'Mente positiva, treino produtivo.', '2025-05-22 16:01:41'),
(66, 'Não é magia, é consistência.', '2025-05-22 16:01:41'),
(67, 'A tua única competição é com quem eras ontem.', '2025-05-22 16:01:41'),
(68, 'Treina o corpo, liberta a mente.', '2025-05-22 16:01:41'),
(69, 'Não te rendas: o início é sempre o mais difícil.', '2025-05-22 16:01:41'),
(70, 'Uma vida ativa é uma vida com mais energia e alegria.', '2025-05-22 16:01:41'),
(71, 'Quem quer, encontra um caminho. Quem não quer, arranja desculpas.', '2025-05-22 16:01:41'),
(72, 'Corpo saudável, mente em paz.', '2025-05-22 16:01:41'),
(73, 'Vai com tudo ou volta para começar de novo.', '2025-05-22 16:01:41'),
(74, 'Sacrifício hoje. Vitória amanhã.', '2025-05-22 16:01:41'),
(75, 'Não deixes a preguiça vencer o teu sonho.', '2025-05-22 16:01:41'),
(76, 'Cada treino conta, mesmo os difíceis.', '2025-05-22 16:01:41'),
(77, 'A excelência nasce da repetição.', '2025-05-22 16:01:41'),
(78, 'Treinar é investir em ti mesmo.', '2025-05-22 16:01:41'),
(79, 'Torna-te a tua prioridade.', '2025-05-22 16:01:41'),
(80, 'Constrói a tua confiança com ações diárias.', '2025-05-22 16:01:41'),
(81, 'O sucesso é feito de dias comuns com escolhas extraordinárias.', '2025-05-22 16:01:41'),
(82, 'Nunca subestimes o poder de um bom treino.', '2025-05-22 16:01:41'),
(83, 'A tua transformação começa onde a tua zona de conforto termina.', '2025-05-22 16:01:41'),
(84, 'Não pares até seres motivo de inspiração.', '2025-05-22 16:01:41'),
(85, 'Acorda com determinação. Dorme com satisfação.', '2025-05-22 16:01:41'),
(86, 'Desistir não é uma opção. Adapta-te.', '2025-05-22 16:01:41'),
(87, 'Treina com garra. Vive com orgulho.', '2025-05-22 16:01:41'),
(88, 'Cada esforço hoje é uma vitória futura.', '2025-05-22 16:01:41'),
(89, 'Transforma dor em poder.', '2025-05-22 16:01:41'),
(90, 'Move-te com propósito, não só com pressa.', '2025-05-22 16:01:41'),
(91, 'O esforço de hoje é o físico de amanhã.', '2025-05-22 16:01:41'),
(92, 'Quem treina com foco, conquista com mérito.', '2025-05-22 16:01:41'),
(93, 'Só melhora quem insiste.', '2025-05-22 16:01:41'),
(94, 'Faz do treino o teu hábito, não a tua exceção.', '2025-05-22 16:01:41'),
(95, 'Coragem é continuar mesmo quando tudo diz para parar.', '2025-05-22 16:01:41'),
(96, 'A tua meta só depende de ti.', '2025-05-22 16:01:41'),
(97, 'Persistência transforma objetivos em realidade.', '2025-05-22 16:01:41'),
(98, 'Hoje difícil, amanhã incrível.', '2025-05-22 16:01:41'),
(99, 'Treina em silêncio, brilha em público.', '2025-05-22 16:01:41'),
(100, 'Um dia sem treino é um dia sem progresso.', '2025-05-22 16:01:41'),
(101, 'Sem desculpas. Só resultados.', '2025-05-22 16:01:41'),
(102, 'Mantém-te firme. Os resultados chegam para quem espera... a treinar.', '2025-05-22 16:01:41');

-- --------------------------------------------------------

--
-- Estrutura da tabela `recipes`
--

CREATE TABLE `recipes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `ingredients` text NOT NULL,
  `instructions` text NOT NULL,
  `added_by_nutritionist_id` int(11) DEFAULT NULL COMMENT 'FK para users.id (nutricionista)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `recipes`
--

INSERT INTO `recipes` (`id`, `name`, `ingredients`, `instructions`, `added_by_nutritionist_id`, `created_at`) VALUES
(1, 'Omelete de Vegetais', '2 ovos, 1/2 xícara de espinafre, 1/4 xícara de tomate picado, 1/4 xícara de cebola picada, sal e pimenta a gosto', '1. Bata os ovos em uma tigela. 2. Aqueça uma frigideira antiaderente e adicione os vegetais. 3. Despeje os ovos sobre os vegetais e cozinhe até firmar. 4. Sirva quente.', 7, '2025-02-26 11:10:17'),
(2, 'Smoothie de Frutas', '1 banana, 1/2 xícara de morangos, 1/2 xícara de iogurte natural, 1/2 xícara de leite de amêndoa, 1 colher de sopa de mel', '1. Coloque todos os ingredientes no liquidificador. 2. Bata até obter uma mistura homogênea. 3. Sirva gelado.', 7, '2025-02-26 11:10:17'),
(3, 'Salada de Quinoa', '1 xícara de quinoa cozida, 1/2 xícara de pepino picado, 1/2 xícara de tomate cereja, 1/4 xícara de queijo feta, azeite e limão a gosto', '1. Misture todos os ingredientes em uma tigela. 2. Tempere com azeite e limão. 3. Sirva frio.', 7, '2025-02-26 11:10:17'),
(4, 'Arroz para tapar os buraquinhos da parede', 'arroz(quantidade a gosto), agua  e sal(quantidade a gosto).', 'pouca agua e muito tempo ao lume', 7, '2025-05-05 15:17:28');

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
-- Estrutura da tabela `shown_content_log`
--

CREATE TABLE `shown_content_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content_type` enum('tip','quote') NOT NULL,
  `content_id` int(11) NOT NULL,
  `shown_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `shown_content_log`
--

INSERT INTO `shown_content_log` (`id`, `user_id`, `content_type`, `content_id`, `shown_date`) VALUES
(1, 4, 'tip', 77, '2025-05-23'),
(2, 4, 'quote', 89, '2025-05-23');

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `numero_telemovel` varchar(25) DEFAULT NULL COMMENT 'Número de telemóvel do utilizador',
  `password` varchar(255) NOT NULL,
  `role` enum('client','trainer','nutritionist','admin','receptionist') DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `first_login` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `numero_telemovel`, `password`, `role`, `token`, `reset_token`, `reset_token_expires_at`, `first_login`, `created_at`, `profile_picture`) VALUES
(1, 'João Silva', 'joao@gmail.com', NULL, '$2y$10$lh.W5KbiZP/pR26lenTsaO/B5fR542orbtAlxfaWk/y16UgrPsIp2', 'client', NULL, NULL, NULL, 0, '2025-02-16 16:00:40', NULL),
(2, 'Maria Souza', 'maria@gmail.com', '91829181', '$2y$10$463JYEX113pg5x1.aUaMd.1nIjnnHxZJ/ziE5o18bXJ3Do2HmTeyy', 'trainer', NULL, NULL, NULL, 0, '2025-02-16 16:00:40', 'user_2_1746632583.png'),
(3, 'Carlos Oliveira', 'carlos@gmail.com', NULL, '$2y$10$ky3zxCNaI2/olPuWNG8icu7ihDtYj94JaBFMJFphOMc6vIWwtJNm.', 'trainer', NULL, NULL, NULL, 0, '2025-02-16 16:00:40', NULL),
(4, 'Nelo Rodrigues da silva chapeiro', 'Nelo@gmail.com', '917688818', '$2y$10$Ya4lZjtt5aMJi1nZm9VW4O9quz16SDR5okERVFHVfSLOgbIisYAwC', 'client', NULL, NULL, NULL, 0, '2025-02-16 16:42:55', 'user_4_1748255934.png'),
(6, 'Claudia', 'claudia@gmail.com', '913677215', '$2y$10$QrMlKOAR98ERl97o/rMoIenm5r7q0dGmnRa1WzQir5kHS.h6e0Mn2', 'trainer', NULL, NULL, NULL, 0, '2025-02-16 17:27:51', 'user_6_1744907164.png'),
(7, 'Silvia', 'silvia@gmail.com', '914123555', '$2y$10$sp7sMfxCBTptdgIKjLf0iead9WZQQvzYiW9GiiqhpuNI/qeHehSY6', 'nutritionist', NULL, NULL, NULL, 0, '2025-02-16 17:27:51', 'user_7_1744907663.png'),
(8, 'claudio', 'claudio@gmail.com', NULL, 'claudio123', '', NULL, NULL, NULL, 1, '2025-02-16 17:57:51', NULL),
(10, 'Personal Trainer', 'trainer@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', NULL, NULL, NULL, 1, '2025-02-16 18:23:39', NULL),
(11, 'Nutricionista', 'nutritionist@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nutritionist', NULL, NULL, NULL, 1, '2025-02-16 18:23:39', NULL),
(12, 'leandro', 'l2androliveir3@gmail.com', NULL, '$2y$10$J/H/UXhO4Zh7f34SCDlSou6fsqbq6xqoa3MbLUXUN0K2aZNFyaHJC', 'admin', NULL, NULL, NULL, 0, '2025-02-22 10:30:35', NULL),
(16, 'Fabio', 'fabio.oliveira.1719@gmail.com', NULL, '$2y$10$kfsBwjbI1oyZRutY0rjXNOVAeYINuqInUr0eyEgtjfB4dNez/8D4S', 'client', '8f87cb65849c9b33b7a2b0a5f5849040f974bfd7335d8fe398d55ca847619b03864f276127ce05b47702f526ae36520f9506', NULL, NULL, 1, '2025-03-15 14:11:35', NULL),
(21, 'Marcelo', '2mapo.01.01.2007@gmail.com', NULL, '$2y$10$0QvpZM6lpJ3kKRFWU39E1O/tP4xbnnX8CT8oD6Jny7QOszZicpWfK', 'client', '1adb618811d0ad173cf0727a40561dfac3bda92241e5ebcf5fc924d01bfeb8cf6829d153d70dafb90dffd59ff2b7928da9b6', NULL, NULL, 1, '2025-03-15 14:53:00', NULL),
(22, 'Arbusto', 'arbustosr116@gmail.com', '921291191', '$2y$10$msl0AcXBrpFVOKSU7gVeg.hkpuqmUTYrw/VZxniRz9WyNvkqgn2qK', 'client', NULL, NULL, NULL, 0, '2025-03-15 14:55:43', 'user_22_1746634335.png'),
(23, 'Leonardo', 'leofsgomes.2007@gmail.com', NULL, '$2y$10$/UlDkdcXgPXOmtFHqE5e2OzsVyY5wk2np9Yvtw1Yyzj37f475jN5u', 'client', 'af6410bd5e87419eddb0bb9f789cbb9ad5cf69ce0d7f2dbf66d6650d83b52a48ec6361fa8870d069dd72f970a75aba99a537', NULL, NULL, 1, '2025-03-20 14:39:26', NULL),
(24, 'Hugo', 'hugotestes621@gmail.com', NULL, '$2y$10$A6Hth3D57BCox6Tiq1HA2u4xYYxiQi1m2xJi2AeUktqkQrd6ZhUI6', 'client', 'f1e949afc12cfd721ac34496600c93306a8fa54dff3702d8c27e8bce335c0d88458fd2ab4aa6d70118d4de9fb52ae5aed942', NULL, NULL, 1, '2025-05-02 14:11:43', NULL),
(25, 'diogo', 'diogomicaelpinto6@gmail.com', NULL, '$2y$10$RsLAJ8V.idtdP2rrErNwPOIKWanIqyISoG4jhlcL9nOdBi2vYnsY.', 'receptionist', '157b221a72236923e7b47a11960aeb04546c8b2c21e2799cc494706978e04db2c6039aeece6f640e1b3cef9673c88fd476e4', NULL, NULL, 1, '2025-05-19 12:01:31', NULL),
(26, 'Orlando Rodrigues', 'papmywellness@gmail.com', NULL, '$2y$10$u/7WBmhrwRA01alOGXuineWdSHGIHpOHxwl7UdnoFGqPcut5x4yre', 'receptionist', NULL, NULL, NULL, 0, '2025-05-19 12:13:21', 'user_26_1747845622.png');

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_favorite_classes`
--

CREATE TABLE `user_favorite_classes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(10, 1, 6, 'precisa de comer muita sopa', '2025-04-01 14:03:11', 0),
(11, 2, 6, 'Deve beber mais líquidos , já que sente muita sede.', '2025-04-07 10:03:05', 1),
(12, 1, 6, '', '2025-04-14 10:28:20', 0),
(13, 5, 2, 'deverá deixar de camperar', '2025-04-14 10:51:27', 1),
(14, 1, 2, '', '2025-04-17 15:53:08', 0),
(15, 1, 2, '', '2025-05-14 11:48:25', 0),
(16, 1, 2, 'sadasadadsad', '2025-05-14 11:51:36', 1);

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
(22, 10, 'Dia 1', NULL, 2),
(23, 10, 'Dia 2', NULL, 3),
(24, 10, 'Dia 3', NULL, 1),
(25, 10, 'Dia 4', NULL, 4),
(26, 10, 'Dia 5', NULL, 7),
(27, 10, 'Dia 6', NULL, 5),
(28, 10, 'Dia 7', NULL, 5),
(50, 12, 'Dia 1', '', 1),
(51, 12, 'Dia 2', '', 1),
(52, 12, 'Dia 3', '', 1),
(53, 12, 'Dia 4', '', 1),
(54, 12, 'Dia 5', '', 1),
(55, 12, 'Dia 6', '', 1),
(56, 12, 'Dia 7', '', 1),
(57, 13, 'Dia 1', '', 4),
(58, 13, 'Dia 2', '', 3),
(59, 13, 'Dia 3', '', 2),
(60, 13, 'Dia 4', '', 1),
(61, 13, 'Dia 5', '', 6),
(62, 13, 'Dia 6', '', 5),
(63, 13, 'Dia 7', '', 5),
(71, 14, 'Dia 1', '', 1),
(72, 14, 'Dia 2', '', 1),
(73, 14, 'Dia 3', '', 1),
(74, 14, 'Dia 4', '', 1),
(75, 14, 'Dia 5', '', 1),
(76, 14, 'Dia 6', '', 1),
(77, 14, 'Dia 7', '', 1),
(92, 15, 'Dia 1', '', 1),
(93, 15, 'Dia 2', '', 1),
(94, 15, 'Dia 3', '', 1),
(95, 15, 'Dia 4', '', 1),
(96, 15, 'Dia 5', '', 1),
(97, 15, 'Dia 6', '', 1),
(98, 15, 'Dia 7', '', 1),
(99, 16, 'Dia 1', '', 1),
(100, 16, 'Dia 2', '', 1),
(101, 16, 'Dia 3', '', 1),
(102, 16, 'Dia 4', '', 1),
(103, 16, 'Dia 5', '', 1),
(104, 16, 'Dia 6', '', 1),
(105, 16, 'Dia 7', '', 1),
(113, 11, 'Dia 1', '[{\"type\":\"sets_reps\",\"data\":{\"sets\":\"3\",\"reps\":\"8-12\",\"notes\":\"Aumentar a cada rep o peso.\"}},{\"type\":\"rest_time\",\"data\":{\"time\":\"30\",\"unit\":\"segundos\"}}]', 3),
(114, 11, 'Dia 2', '[]', 5),
(115, 11, 'Dia 3', '[{\"type\":\"sets_reps\",\"data\":{\"sets\":\"3\",\"reps\":\"10-12\",\"notes\":\"Até a falha, com aumento de peso a cada rep\"}},{\"type\":\"rest_time\",\"data\":{\"time\":\"15\",\"unit\":\"segundos\"}}]', 2),
(116, 11, 'Dia 4', '[]', 6),
(117, 11, 'Dia 5', '[{\"type\":\"sets_reps\",\"data\":{\"sets\":\"3\",\"reps\":\"8-10\",\"notes\":\"Aumentar o peso gradualmente e na ultima ir ate a falha\"}}]', 1),
(118, 11, 'Dia 6', '[{\"type\":\"sets_reps\",\"data\":{\"sets\":\"5\",\"reps\":\"20-50\",\"notes\":\"Ate a falha\"}}]', 7),
(119, 11, 'Dia 7', '[]', 5);

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
(57, 10, 1),
(58, 10, 2),
(59, 10, 3),
(60, 10, 4),
(61, 10, 5),
(62, 10, 6),
(63, 10, 7),
(64, 10, 8),
(65, 10, 9),
(66, 10, 10),
(145, 13, 1),
(146, 13, 2),
(147, 13, 3),
(148, 13, 4),
(149, 13, 5),
(224, 15, 22),
(225, 15, 23),
(226, 15, 24),
(227, 15, 25),
(228, 15, 27),
(229, 16, 1),
(230, 16, 2),
(231, 16, 3),
(232, 16, 4),
(233, 16, 5),
(234, 16, 6),
(235, 16, 7),
(236, 16, 8),
(237, 16, 9),
(238, 16, 10),
(239, 16, 11),
(240, 16, 12),
(241, 16, 13),
(242, 16, 14),
(243, 16, 15),
(281, 11, 34),
(282, 11, 16),
(283, 11, 33),
(284, 11, 31),
(285, 11, 13),
(286, 11, 32),
(287, 11, 18),
(288, 11, 39),
(289, 11, 40),
(290, 11, 11),
(291, 11, 20),
(292, 11, 30),
(293, 11, 12),
(294, 11, 14),
(295, 11, 28),
(296, 11, 47),
(297, 11, 49),
(298, 11, 48),
(299, 11, 27),
(300, 11, 44),
(301, 11, 46),
(302, 11, 24),
(303, 11, 45),
(304, 11, 23),
(305, 11, 22),
(306, 11, 15),
(307, 11, 25),
(308, 11, 35),
(309, 11, 29),
(310, 11, 37),
(311, 11, 21),
(312, 11, 36),
(313, 11, 17),
(314, 11, 38),
(315, 11, 41),
(316, 11, 19),
(317, 11, 42);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `access_delegations`
--
ALTER TABLE `access_delegations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `granting_admin_id` (`granting_admin_id`),
  ADD KEY `delegated_user_id` (`delegated_user_id`);

--
-- Índices para tabela `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

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
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_checked_in_by` (`checked_in_by_user_id`);

--
-- Índices para tabela `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices para tabela `client_achievements`
--
ALTER TABLE `client_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_achievement` (`client_id`,`achievement_id`),
  ADD KEY `achievement_id_idx` (`achievement_id`);

--
-- Índices para tabela `client_goals`
--
ALTER TABLE `client_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id_idx` (`client_id`);

--
-- Índices para tabela `daily_tips`
--
ALTER TABLE `daily_tips`
  ADD PRIMARY KEY (`id`);

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
-- Índices para tabela `diet_plan_meals`
--
ALTER TABLE `diet_plan_meals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diet_plan_meals_diet_plan_id` (`diet_plan_id`);

--
-- Índices para tabela `diet_plan_meal_foods`
--
ALTER TABLE `diet_plan_meal_foods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diet_plan_meal_foods_meal_id` (`diet_plan_meal_id`),
  ADD KEY `idx_diet_plan_meal_foods_food_id` (`food_id`);

--
-- Índices para tabela `exercises`
--
ALTER TABLE `exercises`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name_unique` (`name`),
  ADD KEY `fk_food_added_by_nutritionist` (`added_by_nutritionist_id`),
  ADD KEY `idx_food_category` (`category_id`);

--
-- Índices para tabela `food_categories`
--
ALTER TABLE `food_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name_unique` (`name`);

--
-- Índices para tabela `generic_workout_plans`
--
ALTER TABLE `generic_workout_plans`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `history_assessments`
--
ALTER TABLE `history_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `recorded_by_user_id` (`recorded_by_user_id`);

--
-- Índices para tabela `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_message_sender` (`sender_id`),
  ADD KEY `idx_message_receiver` (`receiver_id`),
  ADD KEY `idx_message_conversation` (`sender_id`,`receiver_id`);

--
-- Índices para tabela `mood_logs`
--
ALTER TABLE `mood_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_date_mood` (`user_id`,`assessment_date`);

--
-- Índices para tabela `motivational_quotes`
--
ALTER TABLE `motivational_quotes`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recipe_added_by` (`added_by_nutritionist_id`);

--
-- Índices para tabela `recommendations`
--
ALTER TABLE `recommendations`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `shown_content_log`
--
ALTER TABLE `shown_content_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_content_date` (`user_id`,`content_type`,`shown_date`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `content_type` (`content_type`),
  ADD KEY `content_id` (`content_id`),
  ADD KEY `shown_date` (`shown_date`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `user_favorite_classes`
--
ALTER TABLE `user_favorite_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_class_unique` (`user_id`,`class_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `class_id` (`class_id`);

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
-- AUTO_INCREMENT de tabela `access_delegations`
--
ALTER TABLE `access_delegations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `class_participants`
--
ALTER TABLE `class_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `client_achievements`
--
ALTER TABLE `client_achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `client_goals`
--
ALTER TABLE `client_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `daily_tips`
--
ALTER TABLE `daily_tips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT de tabela `day_options`
--
ALTER TABLE `day_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `diet_plans`
--
ALTER TABLE `diet_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `diet_plan_meals`
--
ALTER TABLE `diet_plan_meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `diet_plan_meal_foods`
--
ALTER TABLE `diet_plan_meal_foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `exercises`
--
ALTER TABLE `exercises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de tabela `foods`
--
ALTER TABLE `foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `food_categories`
--
ALTER TABLE `food_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `generic_workout_plans`
--
ALTER TABLE `generic_workout_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `history_assessments`
--
ALTER TABLE `history_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `mood_logs`
--
ALTER TABLE `mood_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de tabela `motivational_quotes`
--
ALTER TABLE `motivational_quotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT de tabela `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `recommendations`
--
ALTER TABLE `recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `shown_content_log`
--
ALTER TABLE `shown_content_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `user_favorite_classes`
--
ALTER TABLE `user_favorite_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `workout_history`
--
ALTER TABLE `workout_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `workout_plans`
--
ALTER TABLE `workout_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `workout_plan_days`
--
ALTER TABLE `workout_plan_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT de tabela `workout_plan_exercises`
--
ALTER TABLE `workout_plan_exercises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=318;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `access_delegations`
--
ALTER TABLE `access_delegations`
  ADD CONSTRAINT `access_delegations_ibfk_1` FOREIGN KEY (`granting_admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `access_delegations_ibfk_2` FOREIGN KEY (`delegated_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `class_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_checked_in_by` FOREIGN KEY (`checked_in_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `client_achievements`
--
ALTER TABLE `client_achievements`
  ADD CONSTRAINT `fk_client_achievements_achievement` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_client_achievements_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `client_goals`
--
ALTER TABLE `client_goals`
  ADD CONSTRAINT `fk_client_goals_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `diet_plans`
--
ALTER TABLE `diet_plans`
  ADD CONSTRAINT `diet_plans_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `diet_plans_ibfk_2` FOREIGN KEY (`nutritionist_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `diet_plan_meals`
--
ALTER TABLE `diet_plan_meals`
  ADD CONSTRAINT `fk_diet_plan_meals_diet_plans` FOREIGN KEY (`diet_plan_id`) REFERENCES `diet_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `diet_plan_meal_foods`
--
ALTER TABLE `diet_plan_meal_foods`
  ADD CONSTRAINT `fk_diet_plan_meal_foods_foods` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_diet_plan_meal_foods_meals` FOREIGN KEY (`diet_plan_meal_id`) REFERENCES `diet_plan_meals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `foods`
--
ALTER TABLE `foods`
  ADD CONSTRAINT `fk_food_added_by_nutritionist` FOREIGN KEY (`added_by_nutritionist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_food_category` FOREIGN KEY (`category_id`) REFERENCES `food_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `history_assessments`
--
ALTER TABLE `history_assessments`
  ADD CONSTRAINT `history_assessments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `history_assessments_ibfk_2` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `mood_logs`
--
ALTER TABLE `mood_logs`
  ADD CONSTRAINT `mood_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `fk_recipe_added_by` FOREIGN KEY (`added_by_nutritionist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `user_favorite_classes`
--
ALTER TABLE `user_favorite_classes`
  ADD CONSTRAINT `user_favorite_classes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_favorite_classes_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

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
