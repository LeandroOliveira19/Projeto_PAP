-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 12-Maio-2025 às 18:11
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
(18, 'cycling', 'sdaasfaesdgdgsfdbefbdf', 2, '2025-05-13 16:00:00', 32, 10);

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
(2, 2, 1),
(3, 7, 22),
(4, 7, 4),
(5, 8, 4),
(6, 8, 22);

-- --------------------------------------------------------

--
-- Estrutura da tabela `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
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

INSERT INTO `clients` (`id`, `user_id`, `weight`, `height`, `activity_level`, `profession`, `gym_frequency`, `health_issues`, `water_percentage`, `idade_metabolica`, `morfotipo`, `ombros`, `peitoral_dorsal`, `cintura`, `abdominal`, `quadril`, `braco_direito`, `braco_esquerdo`, `coxa_direita`, `coxa_esquerda`, `perna_direita`, `perna_esquerda`, `tricipital`, `subescapular`, `bicipital`, `peitoral`, `axilar_media`, `abdominal_plicometria`, `supra_iliaca`, `coxa`, `geminal`, `visceral_fat_index`, `bone_mass_kg`, `lean_mass_kg`, `muscle_mass_kg`, `proteinas`, `fat_mass_kg`, `fat_mass_percentage`, `gordura_subcutanea`, `lean_mass_percentage`, `last_assessment_date`) VALUES
(1, 1, 75.5, 1.8, 'Moderado', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 4, 85, 1.65, 'Leve', 'Mecânico', '3 vezes por semana', 'tenho a rotula desfrianbrada', 58.50, 28, 'Mesomorfo', 40.0, 30.0, 36.0, 123.0, 23.0, 12.0, 24.0, 13.0, 23.0, 324.0, 324.0, 22.0, 11.0, 33.0, 44.0, 23.0, 12.0, 32.0, 43.0, 32.0, 5, 2.80, 60.50, 55.20, 12.30, 15.80, 22.50, 18.20, 77.50, '2025-04-23'),
(3, 16, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 21, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 22, NULL, NULL, '', 'Encanador', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 23, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 24, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 0 COMMENT 'Indica se o plano de dieta está ativo (1=Sim, 0=Não)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `diet_plans`
--

INSERT INTO `diet_plans` (`id`, `client_id`, `nutritionist_id`, `plan_details`, `created_at`, `is_active`) VALUES
(1, 1, 3, 'Café da manhã: Omelete com vegetais; Almoço: Frango grelhado com arroz integral; Jantar: Salada de quinoa.', '2025-02-16 16:00:41', 1);

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
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `numero_telemovel` varchar(25) DEFAULT NULL COMMENT 'Número de telemóvel do utilizador',
  `password` varchar(255) NOT NULL,
  `role` enum('client','trainer','nutritionist','admin') NOT NULL,
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
(3, 'Carlos Oliveira', 'carlos@gmail.com', NULL, '$2y$10$ky3zxCNaI2/olPuWNG8icu7ihDtYj94JaBFMJFphOMc6vIWwtJNm.', 'nutritionist', NULL, NULL, NULL, 0, '2025-02-16 16:00:40', NULL),
(4, 'Nelo Rodrigues da silva chapeiro', 'Nelo@gmail.com', '917688818', '$2y$10$Ya4lZjtt5aMJi1nZm9VW4O9quz16SDR5okERVFHVfSLOgbIisYAwC', 'client', NULL, NULL, NULL, 0, '2025-02-16 16:42:55', 'user_4_1744899053.png'),
(6, 'Claudia', 'claudia@gmail.com', '913677215', '$2y$10$QrMlKOAR98ERl97o/rMoIenm5r7q0dGmnRa1WzQir5kHS.h6e0Mn2', 'trainer', NULL, NULL, NULL, 0, '2025-02-16 17:27:51', 'user_6_1744907164.png'),
(7, 'Silvia', 'silvia@gmail.com', '914123555', '$2y$10$sp7sMfxCBTptdgIKjLf0iead9WZQQvzYiW9GiiqhpuNI/qeHehSY6', 'nutritionist', NULL, NULL, NULL, 0, '2025-02-16 17:27:51', 'user_7_1744907663.png'),
(8, 'claudio', 'claudio@gmail.com', NULL, 'claudio123', 'trainer', NULL, NULL, NULL, 1, '2025-02-16 17:57:51', NULL),
(10, 'Personal Trainer', 'trainer@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', NULL, NULL, NULL, 1, '2025-02-16 18:23:39', NULL),
(11, 'Nutricionista', 'nutritionist@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nutritionist', NULL, NULL, NULL, 1, '2025-02-16 18:23:39', NULL),
(12, 'leandro', 'l2androliveir3@gmail.com', NULL, '$2y$10$GQ/F52GETp2VA3dYEKb55ODsmdzlz0Ue8oLVNdq5db7YklBEg/Hlq', 'admin', NULL, NULL, NULL, 0, '2025-02-22 10:30:35', NULL),
(16, 'Fabio', 'fabio.oliveira.1719@gmail.com', NULL, '$2y$10$kfsBwjbI1oyZRutY0rjXNOVAeYINuqInUr0eyEgtjfB4dNez/8D4S', 'client', '8f87cb65849c9b33b7a2b0a5f5849040f974bfd7335d8fe398d55ca847619b03864f276127ce05b47702f526ae36520f9506', NULL, NULL, 1, '2025-03-15 14:11:35', NULL),
(21, 'Marcelo', '2mapo.01.01.2007@gmail.com', NULL, '$2y$10$0QvpZM6lpJ3kKRFWU39E1O/tP4xbnnX8CT8oD6Jny7QOszZicpWfK', 'client', '1adb618811d0ad173cf0727a40561dfac3bda92241e5ebcf5fc924d01bfeb8cf6829d153d70dafb90dffd59ff2b7928da9b6', NULL, NULL, 1, '2025-03-15 14:53:00', NULL),
(22, 'Arbusto', 'arbustosr116@gmail.com', '921291191', '$2y$10$msl0AcXBrpFVOKSU7gVeg.hkpuqmUTYrw/VZxniRz9WyNvkqgn2qK', 'client', NULL, NULL, NULL, 0, '2025-03-15 14:55:43', 'user_22_1746634335.png'),
(23, 'Leonardo', 'leofsgomes.2007@gmail.com', NULL, '$2y$10$/UlDkdcXgPXOmtFHqE5e2OzsVyY5wk2np9Yvtw1Yyzj37f475jN5u', 'client', 'af6410bd5e87419eddb0bb9f789cbb9ad5cf69ce0d7f2dbf66d6650d83b52a48ec6361fa8870d069dd72f970a75aba99a537', NULL, NULL, 1, '2025-03-20 14:39:26', NULL),
(24, 'Hugo', 'hugotestes621@gmail.com', NULL, '$2y$10$A6Hth3D57BCox6Tiq1HA2u4xYYxiQi1m2xJi2AeUktqkQrd6ZhUI6', 'client', 'f1e949afc12cfd721ac34496600c93306a8fa54dff3702d8c27e8bce335c0d88458fd2ab4aa6d70118d4de9fb52ae5aed942', NULL, NULL, 1, '2025-05-02 14:11:43', NULL);

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
(14, 1, 6, '', '2025-04-17 15:53:08', 1);

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
(43, 11, 'Dia 1', NULL, 3),
(44, 11, 'Dia 2', NULL, 4),
(45, 11, 'Dia 3', NULL, 6),
(46, 11, 'Dia 4', NULL, 5),
(47, 11, 'Dia 5', NULL, 2),
(48, 11, 'Dia 6', NULL, 5),
(49, 11, 'Dia 7', NULL, 5),
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
(77, 14, 'Dia 7', '', 1);

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
(106, 11, 11),
(107, 11, 12),
(108, 11, 13),
(109, 11, 14),
(110, 11, 15),
(111, 11, 16),
(112, 11, 17),
(113, 11, 18),
(114, 11, 19),
(115, 11, 20),
(116, 11, 21),
(117, 11, 22),
(118, 11, 23),
(119, 11, 24),
(120, 11, 25),
(122, 11, 27),
(123, 11, 28),
(124, 11, 29),
(125, 11, 30),
(126, 11, 31),
(127, 11, 32),
(128, 11, 33),
(129, 11, 34),
(130, 11, 35),
(131, 11, 36),
(132, 11, 37),
(133, 11, 38),
(134, 11, 39),
(135, 11, 40),
(136, 11, 41),
(137, 11, 42),
(139, 11, 44),
(140, 11, 45),
(141, 11, 46),
(142, 11, 47),
(143, 11, 48),
(144, 11, 49),
(145, 13, 1),
(146, 13, 2),
(147, 13, 3),
(148, 13, 4),
(149, 13, 5);

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
-- AUTO_INCREMENT de tabela `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `class_participants`
--
ALTER TABLE `class_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `workout_plan_days`
--
ALTER TABLE `workout_plan_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de tabela `workout_plan_exercises`
--
ALTER TABLE `workout_plan_exercises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

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
  ADD CONSTRAINT `class_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
