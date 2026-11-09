-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : sam. 05 juil. 2025 à 11:42
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `school_schedule`
--

-- --------------------------------------------------------

--
-- Structure de la table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
CREATE TABLE IF NOT EXISTS `assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `type` enum('homework','exam','project') NOT NULL,
  `due_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `subject_id` (`subject_id`),
  KEY `teacher_id` (`teacher_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `assignments`
--

INSERT INTO `assignments` (`id`, `class_id`, `subject_id`, `teacher_id`, `title`, `description`, `type`, `due_date`, `created_at`) VALUES
(5, 3, 4, 4, 'test', 'test', 'homework', '0000-00-00 00:00:00', '2025-07-05 11:13:20');

-- --------------------------------------------------------

--
-- Structure de la table `assignment_attachments`
--

DROP TABLE IF EXISTS `assignment_attachments`;
CREATE TABLE IF NOT EXISTS `assignment_attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assignment_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `assignment_id` (`assignment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `assignment_submissions`
--

DROP TABLE IF EXISTS `assignment_submissions`;
CREATE TABLE IF NOT EXISTS `assignment_submissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assignment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `comment` text,
  `grade` decimal(4,2) DEFAULT NULL,
  `feedback` text,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `graded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assignment_id` (`assignment_id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `classes`
--

DROP TABLE IF EXISTS `classes`;
CREATE TABLE IF NOT EXISTS `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `classes`
--

INSERT INTO `classes` (`id`, `name`, `created_at`) VALUES
(1, 'Prépa 1', '2025-06-23 21:58:11'),
(2, 'Prépa 2', '2025-06-23 21:58:11'),
(3, 'ING 1', '2025-06-23 21:58:11'),
(4, 'ING 2', '2025-06-23 21:58:11'),
(5, 'ING 3', '2025-06-23 21:58:11');

-- --------------------------------------------------------

--
-- Structure de la table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `class_id` int NOT NULL,
  `room_id` int NOT NULL,
  `day_of_week` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `class_id` (`class_id`),
  KEY `room_id` (`room_id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `courses`
--

INSERT INTO `courses` (`id`, `subject_id`, `teacher_id`, `class_id`, `room_id`, `day_of_week`, `start_time`, `end_time`, `created_at`) VALUES
(11, 8, 11, 2, 2, 'Mercredi', '08:00:00', '12:00:00', '2025-07-04 13:44:37'),
(15, 16, 18, 2, 3, 'Mercredi', '08:00:00', '12:00:00', '2025-07-05 09:19:10'),
(19, 1, 1, 4, 3, 'Lundi', '08:00:00', '12:00:00', '2025-07-05 11:08:51'),
(12, 8, 11, 2, 2, 'Mercredi', '08:00:00', '12:00:00', '2025-07-04 13:45:24'),
(13, 8, 11, 4, 2, 'Mercredi', '08:00:00', '12:00:00', '2025-07-04 13:46:03'),
(17, 4, 19, 4, 1, 'Lundi', '12:00:00', '14:00:00', '2025-07-05 09:25:01'),
(18, 4, 22, 4, 2, 'Jeudi', '14:00:00', '17:00:00', '2025-07-05 09:25:23');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `capacity` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `capacity`, `created_at`) VALUES
(1, 'Salle 1', 30, '2025-06-23 22:12:20'),
(2, 'Salle 2', 30, '2025-06-23 22:12:20'),
(3, 'Salle 3', 30, '2025-06-23 22:12:20'),
(4, 'Salle 4', 30, '2025-06-23 22:12:20'),
(5, 'Salle 5', 30, '2025-06-23 22:12:20');

-- --------------------------------------------------------

--
-- Structure de la table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
CREATE TABLE IF NOT EXISTS `schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `room_id` int NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `subject_id` (`subject_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `room_id` (`room_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `student_classes`
--

DROP TABLE IF EXISTS `student_classes`;
CREATE TABLE IF NOT EXISTS `student_classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `student_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `created_at`) VALUES
(1, 'Analyse 1', '2025-06-23 22:12:20'),
(2, 'Analyse 2', '2025-06-23 22:12:20'),
(3, 'Algèbre 1', '2025-06-23 22:12:20'),
(4, 'Algèbre 2', '2025-06-23 22:12:20'),
(5, 'circuit logique', '2025-07-04 13:32:00'),
(6, 'Langage C', '2025-07-04 13:32:00'),
(7, 'Atelier de Langage C', '2025-07-04 13:32:00'),
(8, 'Economie et Gestion', '2025-07-04 13:32:00'),
(9, 'Sytèeme d\'exploitation MS-DOS', '2025-07-04 13:32:00'),
(10, 'Système d\'exploitation Linux', '2025-07-04 13:32:00'),
(11, 'Optique', '2025-07-04 13:32:00'),
(12, 'Electricité', '2025-07-04 13:32:00'),
(13, 'Anglais', '2025-07-04 13:32:00'),
(14, 'Tehcnique d\'expression', '2025-07-04 13:32:00'),
(15, 'Electromagnétisme', '2025-07-04 13:32:00'),
(16, 'Algorithme', '2025-07-04 13:32:00'),
(17, 'Base de donnée', '2025-07-04 13:32:00'),
(18, 'Maintenance', '2025-07-04 13:32:00'),
(19, 'Merise', '2025-07-04 13:32:00'),
(20, 'Micro-processeur', '2025-07-04 13:32:00');

-- --------------------------------------------------------

--
-- Structure de la table `submission_attachments`
--

DROP TABLE IF EXISTS `submission_attachments`;
CREATE TABLE IF NOT EXISTS `submission_attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `submission_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `submission_id` (`submission_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `teachers`
--

INSERT INTO `teachers` (`id`, `name`, `created_at`) VALUES
(1, 'Enseignant 1', '2025-07-04 13:37:07'),
(2, 'Enseignant 2', '2025-07-04 13:37:07'),
(3, 'Enseignant 3', '2025-07-04 13:37:07'),
(4, 'Enseignant 4', '2025-07-04 13:37:07'),
(5, 'Enseignant 5', '2025-07-04 13:37:07'),
(6, 'Enseignant 6', '2025-07-04 13:37:07'),
(7, 'Enseignant 7', '2025-07-04 13:37:07'),
(8, 'Enseignant 8', '2025-07-04 13:37:07'),
(9, 'Enseignant 9', '2025-07-04 13:37:07'),
(10, 'Enseignant 10', '2025-07-04 13:37:07'),
(11, 'Enseignant 11', '2025-07-04 13:37:07'),
(12, 'Enseignant 12', '2025-07-04 13:37:07'),
(13, 'Enseignant 13', '2025-07-04 13:37:07'),
(14, 'Enseignant 14', '2025-07-04 13:37:07'),
(15, 'Enseignant 15', '2025-07-04 13:37:07'),
(16, 'Enseignant 16', '2025-07-04 13:37:07'),
(17, 'Enseignant 17', '2025-07-04 13:37:07'),
(18, 'Enseignant 18', '2025-07-04 13:37:07'),
(19, 'Enseignant 19', '2025-07-04 13:37:07'),
(20, 'Enseignant 20', '2025-07-04 13:37:07'),
(21, 'Enseignant 21', '2025-07-04 13:37:07'),
(22, 'Enseignant 22', '2025-07-04 13:37:07'),
(23, 'Enseignant 23', '2025-07-04 13:37:07'),
(24, 'Enseignant 24', '2025-07-04 13:37:07');

-- --------------------------------------------------------

--
-- Structure de la table `teacher_availabilities`
--

DROP TABLE IF EXISTS `teacher_availabilities`;
CREATE TABLE IF NOT EXISTS `teacher_availabilities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `class_id` int DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `username`, `password`, `first_name`, `last_name`, `role`, `class_id`, `status`, `created_at`) VALUES
(1, 'admin@example.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'System', 'admin', NULL, 'active', '2025-06-17 10:21:54');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
