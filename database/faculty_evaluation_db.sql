-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2026 at 04:49 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `faculty_evaluation_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `add_categories`
--

CREATE TABLE `add_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `section_number` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `weight` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_categories`
--

INSERT INTO `add_categories` (`id`, `category_name`, `section_number`, `created_at`, `updated_at`, `weight`) VALUES
(46, 'Mastery of Subject Matter', '2', '2026-10-21 20:33:54', '2026-10-21 20:33:54', 20.00),
(50, 'Teaching Strategies and Methods', '1', '2026-10-21 20:34:35', '2026-10-21 20:34:35', 20.00),
(52, 'Communication Skills', '3', '2026-10-21 20:37:02', '2026-10-21 20:37:02', 20.00),
(55, 'Classroom Management And Professionalism', '4', '2026-10-21 20:40:54', '2026-10-21 20:50:38', 20.00),
(60, 'Assessment and Student Support', '5', '2026-10-21 20:58:08', '2026-10-21 20:58:08', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `add_classes`
--

CREATE TABLE `add_classes` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `year_level` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL DEFAULT '1st Semester',
  `block` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_classes`
--

INSERT INTO `add_classes` (`id`, `program_id`, `year_level`, `semester`, `block`, `created_at`, `updated_at`) VALUES
(23, 111, '3rd Year', '1st Semester', 'C', '2026-10-28 01:53:00', '2026-10-28 01:53:00'),
(24, 111, '3rd Year', '1st Semester', 'a', '2026-10-28 14:49:47', '2026-10-28 14:49:47'),
(25, 111, '3rd Year', '1st Semester', 'b', '2026-10-28 14:50:19', '2026-10-28 14:50:19');

-- --------------------------------------------------------

--
-- Table structure for table `add_faculties`
--

CREATE TABLE `add_faculties` (
  `id` int(11) NOT NULL,
  `faculty_id` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `photo` longtext DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_faculties`
--

INSERT INTO `add_faculties` (`id`, `faculty_id`, `email`, `firstname`, `lastname`, `suffix`, `photo`, `password`, `status`, `created_at`, `updated_at`) VALUES
(88, 'FC-000001', 'clarusbright08@gmail.com', 'Juan', 'Bacislao', '', '', '$2y$10$By0fydOxDS0F2qO.xHutQ.2pGlkmhOjc5geoW1Oo0YhUXn/GSY.WW', NULL, '2026-10-27 15:03:50', '2026-10-27 15:03:50'),
(89, 'FC-000002', 'cherryannlique1@gmail.com', 'Daniel', 'Dela Cruz', '', '', '$2y$10$HcePiRjtyu3BkV5.REhWve5tdXc7ro90qgbFnVIb.kUKgna1gj5EW', 'archived', '2026-10-27 15:05:40', '2026-10-28 15:18:57');

-- --------------------------------------------------------

--
-- Table structure for table `add_programs`
--

CREATE TABLE `add_programs` (
  `id` int(11) NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_programs`
--

INSERT INTO `add_programs` (`id`, `program_code`, `program_name`, `created_at`, `updated_at`) VALUES
(110, 'BSA', 'Bachelor of Science in Accountancy', '2026-10-21 11:02:15', '2026-10-21 11:02:15'),
(111, 'BSIT', 'Bachelor of Science in Information Technology', '2026-10-21 11:02:44', '2026-10-21 11:02:44'),
(112, 'BSBA', 'Bachelor of Science in Business Administration', '2026-10-21 11:03:07', '2026-10-21 11:03:07'),
(114, 'BSCS', 'Bachelor of Science in Computer Science', '2026-10-21 11:03:42', '2026-10-21 11:03:42'),
(115, 'BSCRIM', 'Bachelor of Science in Criminology', '2026-10-21 11:03:52', '2026-10-21 11:03:52'),
(116, 'BSED', 'Bachelor of Secondary Education', '2026-10-21 21:07:29', '2026-10-21 21:07:29'),
(117, 'BSTM', 'Bachelor of Science in Tourism Management', '2026-10-21 21:08:42', '2026-10-21 21:08:42');

-- --------------------------------------------------------

--
-- Table structure for table `add_questions`
--

CREATE TABLE `add_questions` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_questions`
--

INSERT INTO `add_questions` (`id`, `category_id`, `question_text`, `created_at`, `updated_at`) VALUES
(38, 50, 'Demonstrates a strong understanding of the subject.', '2026-10-21 20:53:42', '2026-10-21 20:53:42'),
(39, 50, 'Explains lessons clearly and accurately.', '2026-10-21 20:53:51', '2026-10-21 20:53:51'),
(40, 50, 'Provides relevant examples during discussions.', '2026-10-21 20:54:00', '2026-10-21 20:54:00'),
(41, 50, 'Relates lessons to real-life situations.', '2026-10-21 20:54:06', '2026-10-21 20:54:06'),
(45, 50, 'Answers students\' questions effectively.', '2026-10-21 20:58:35', '2026-10-21 20:58:35'),
(46, 46, 'Uses effective teaching methods.', '2026-10-21 20:58:44', '2026-10-21 20:58:44'),
(47, 46, 'Encourages active student participation.', '2026-10-21 20:58:51', '2026-10-21 20:58:51'),
(48, 46, 'Uses appropriate instructional materials.', '2026-10-21 20:58:57', '2026-10-21 20:58:57'),
(49, 46, 'Makes lessons interesting and engaging.', '2026-10-21 20:59:05', '2026-10-21 20:59:05'),
(50, 46, 'Provides activities that enhance student learning.', '2026-10-21 20:59:11', '2026-10-21 20:59:11'),
(51, 52, 'Communicates ideas clearly and effectively.', '2026-10-21 20:59:20', '2026-10-21 20:59:20'),
(52, 52, 'Gives clear instructions for activities and assignments.', '2026-10-21 20:59:26', '2026-10-21 20:59:26'),
(53, 52, 'Listens to students\' opinions and concerns.', '2026-10-21 20:59:31', '2026-10-21 20:59:31'),
(54, 52, 'Explains difficult concepts in an understandable way.', '2026-10-21 20:59:35', '2026-10-21 20:59:35'),
(55, 52, 'Communicates respectfully with students.', '2026-10-21 20:59:41', '2026-10-21 20:59:41'),
(56, 55, 'Manages classroom time effectively.', '2026-10-21 20:59:50', '2026-10-21 20:59:50'),
(57, 55, 'Maintains a positive learning environment.', '2026-10-21 20:59:59', '2026-10-21 20:59:59'),
(58, 55, 'Treats all students fairly and respectfully.', '2026-10-21 21:00:05', '2026-10-21 21:00:05'),
(59, 55, 'Demonstrates punctuality and preparedness.', '2026-10-21 21:00:12', '2026-10-21 21:00:12'),
(60, 55, 'Maintains professional behavior in class.', '2026-10-21 21:00:18', '2026-10-21 21:00:18'),
(61, 60, 'Provides fair and appropriate examinations.', '2026-10-21 21:00:26', '2026-10-21 21:00:26'),
(62, 60, 'Explains grading criteria clearly.', '2026-10-21 21:00:31', '2026-10-21 21:00:31'),
(63, 60, 'Provides timely feedback on student performance.', '2026-10-21 21:00:37', '2026-10-21 21:00:37'),
(64, 60, 'Assists students who experience learning difficulties.', '2026-10-21 21:00:42', '2026-10-21 21:00:42'),
(65, 60, 'Provides guidance to help students improve academically.', '2026-10-21 21:00:47', '2026-10-21 21:00:47');

-- --------------------------------------------------------

--
-- Table structure for table `add_students`
--

CREATE TABLE `add_students` (
  `id` int(11) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `program` varchar(50) DEFAULT NULL,
  `yearlevel` varchar(20) DEFAULT NULL,
  `section` varchar(20) DEFAULT NULL,
  `student_type` varchar(20) DEFAULT 'regular',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_students`
--

INSERT INTO `add_students` (`id`, `student_number`, `email`, `firstname`, `lastname`, `suffix`, `program`, `yearlevel`, `section`, `student_type`, `status`, `password`, `created_at`, `updated_at`) VALUES
(145, 'GC-230539', 'cherryannlique1@gmail.com', 'Cherry Ann', 'Lique', '', '111', '3', 'C', 'regular', 'active', '$2y$10$U47Tmoeu2OQOoEb1qjK9t.P64Y2WKMyDWyeDQlXRlH4UkO378usZu', '2026-10-27 14:12:50', '2026-10-27 14:22:01');

-- --------------------------------------------------------

--
-- Table structure for table `add_subjects`
--

CREATE TABLE `add_subjects` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_desc` varchar(150) NOT NULL,
  `year_level` varchar(10) NOT NULL,
  `semester` enum('1st Semester','2nd Semester') DEFAULT '2nd Semester',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_subjects`
--

INSERT INTO `add_subjects` (`id`, `program_id`, `subject_code`, `subject_desc`, `year_level`, `semester`, `created_at`, `updated_at`) VALUES
(54, 111, 'ITC8', 'Systems Administration and Maintenance', '3rd Year', '1st Semester', '2026-10-25 21:12:52', '2026-10-25 21:12:52'),
(55, 111, 'ITC9', 'Systems Integration and Architecture 1', '3rd Year', '1st Semester', '2026-10-25 21:13:23', '2026-10-25 21:13:23'),
(56, 111, 'ITC10', 'Systems Analysis and Design', '3rd Year', '1st Semester', '2026-10-25 21:14:28', '2026-10-25 21:14:28'),
(57, 111, 'ITC11', 'Introduction to Human-Computer Interaction', '3rd Year', '1st Semester', '2026-10-25 21:15:33', '2026-10-25 21:15:33'),
(58, 111, 'ITC12', 'Integrative Programming and Technologies 1', '3rd Year', '1st Semester', '2026-10-25 21:15:52', '2026-10-25 21:15:52'),
(59, 111, 'ITC13', 'Quantitative Methods (incl. Modeling & Simulation)', '3rd Year', '1st Semester', '2026-10-25 21:16:12', '2026-10-25 21:16:12'),
(60, 117, 'GE1', 'Purposive Communication', '1st Year', '1st Semester', '2026-10-25 21:18:46', '2026-10-25 21:18:46'),
(61, 117, 'G32', 'Readings in the Philippine History', '1st Year', '1st Semester', '2026-10-25 21:19:18', '2026-10-25 21:19:18'),
(62, 117, 'GE3', 'Mathematics in the Modern World', '1st Year', '1st Semester', '2026-10-25 21:19:36', '2026-10-25 21:19:36'),
(63, 117, 'THC1', 'Tourism and Hospitality', '1st Year', '1st Semester', '2026-10-25 21:19:53', '2026-10-25 21:19:53'),
(64, 117, 'THC2', 'Risk Management as Applied Safety Security and Sanitation', '1st Year', '1st Semester', '2026-10-25 21:20:23', '2026-10-25 21:20:23'),
(65, 117, 'GVAL1', 'Granby Values 1', '1st Year', '1st Semester', '2026-10-25 21:20:48', '2026-10-25 21:20:48'),
(66, 117, 'PATHFIT1', 'Human Enhancement', '1st Year', '1st Semester', '2026-10-25 21:21:06', '2026-10-25 21:21:06'),
(67, 117, 'NSTP1', 'National Service Training Program-1', '1st Year', '1st Semester', '2026-10-25 21:21:33', '2026-10-25 21:21:33');

-- --------------------------------------------------------

--
-- Table structure for table `admin_login`
--

CREATE TABLE `admin_login` (
  `admin_id` int(11) NOT NULL,
  `admin_username` varchar(50) NOT NULL,
  `admin_password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_login`
--

INSERT INTO `admin_login` (`admin_id`, `admin_username`, `admin_password`) VALUES
(1, 'ADM-0001', '$2y$10$o1tFhqdY6p9Z0rw/Px4Hk.6MCuZ7p025FwzHuiqatzmS0Zxc4FfQa');

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_subjects`
--

INSERT INTO `class_subjects` (`id`, `class_id`, `subject_id`, `faculty_id`) VALUES
(179, 23, 56, 88),
(180, 23, 57, 88),
(181, 23, 58, 88),
(182, 23, 59, 89),
(183, 23, 54, 89),
(184, 23, 55, 88),
(185, 24, 56, 88),
(186, 24, 57, 88),
(187, 24, 58, 89),
(188, 24, 59, 89),
(189, 24, 54, 89),
(190, 24, 55, 88),
(191, 25, 56, 88),
(192, 25, 57, 88),
(193, 25, 58, 88),
(194, 25, 59, 89),
(195, 25, 54, 89),
(196, 25, 55, 88);

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL DEFAULT 0,
  `class_id` int(11) NOT NULL DEFAULT 0,
  `overall_rating` decimal(4,2) NOT NULL DEFAULT 0.00,
  `feedback` text DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL DEFAULT '',
  `semester` varchar(50) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluations`
--

INSERT INTO `evaluations` (`id`, `student_id`, `faculty_id`, `subject_id`, `class_id`, `overall_rating`, `feedback`, `academic_year`, `semester`, `created_at`, `updated_at`) VALUES
(20, 145, 88, 56, 23, 4.72, 'Explains the lessons clearly, and provides helpful examples that make the subject easier to understand. He also encourages students to participate and ensures that everyone has the opportunity to learn and improve', '2026-2027', '1st Semester', '2026-10-28 02:15:46', '2026-10-28 14:55:10'),
(21, 145, 88, 57, 23, 4.16, 'fdfdsffddf', '2026-2027', '1st Semester', '2026-10-28 06:18:27', '2026-10-28 14:55:10');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_answers`
--

CREATE TABLE `evaluation_answers` (
  `id` int(11) NOT NULL,
  `evaluation_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_answers`
--

INSERT INTO `evaluation_answers` (`id`, `evaluation_id`, `question_id`, `rating`, `created_at`, `updated_at`) VALUES
(446, 20, 38, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(447, 20, 39, 4, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(448, 20, 40, 4, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(449, 20, 41, 4, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(450, 20, 45, 4, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(451, 20, 46, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(452, 20, 47, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(453, 20, 48, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(454, 20, 49, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(455, 20, 50, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(456, 20, 51, 4, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(457, 20, 52, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(458, 20, 53, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(459, 20, 54, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(460, 20, 55, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(461, 20, 56, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(462, 20, 57, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(463, 20, 58, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(464, 20, 59, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(465, 20, 60, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(466, 20, 61, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(467, 20, 62, 4, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(468, 20, 63, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(469, 20, 64, 4, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(470, 20, 65, 5, '2026-10-28 02:15:46', '2026-10-28 02:15:46'),
(471, 21, 38, 5, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(472, 21, 39, 4, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(473, 21, 40, 4, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(474, 21, 41, 3, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(475, 21, 45, 3, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(476, 21, 46, 5, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(477, 21, 47, 5, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(478, 21, 48, 5, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(479, 21, 49, 5, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(480, 21, 50, 5, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(481, 21, 51, 4, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(482, 21, 52, 4, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(483, 21, 53, 4, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(484, 21, 54, 4, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(485, 21, 55, 3, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(486, 21, 56, 4, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(487, 21, 57, 3, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(488, 21, 58, 3, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(489, 21, 59, 3, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(490, 21, 60, 3, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(491, 21, 61, 5, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(492, 21, 62, 5, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(493, 21, 63, 5, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(494, 21, 64, 5, '2026-10-28 06:18:27', '2026-10-28 06:18:27'),
(495, 21, 65, 5, '2026-10-28 06:18:27', '2026-10-28 06:18:27');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_periods`
--

CREATE TABLE `evaluation_periods` (
  `id` int(11) NOT NULL,
  `ay` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `closure_notified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_periods`
--

INSERT INTO `evaluation_periods` (`id`, `ay`, `semester`, `start_date`, `end_date`, `is_active`, `is_archived`, `closure_notified`, `created_at`, `updated_at`) VALUES
(18, '2026-2027', '1st Semester', '2026-10-28', '2026-10-29', 0, 0, 0, '2026-10-28 01:51:38', '2026-10-28 15:31:42');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_settings`
--

CREATE TABLE `evaluation_settings` (
  `id` int(11) NOT NULL,
  `evaluation_open` tinyint(1) NOT NULL DEFAULT 0,
  `active_period_id` int(11) DEFAULT NULL,
  `selected_ay` varchar(20) DEFAULT NULL,
  `selected_semester` varchar(20) DEFAULT NULL,
  `display_ay` varchar(20) DEFAULT NULL,
  `display_semester` varchar(20) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_settings`
--

INSERT INTO `evaluation_settings` (`id`, `evaluation_open`, `active_period_id`, `selected_ay`, `selected_semester`, `display_ay`, `display_semester`, `updated_at`) VALUES
(1, 0, NULL, '2026-2027', '1st Semester', '2026-2027', '1st Semester', '2026-10-28 06:21:06');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_login`
--

CREATE TABLE `faculty_login` (
  `faculty_id` int(11) NOT NULL,
  `faculty_username` varchar(100) NOT NULL,
  `faculty_password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_login`
--

INSERT INTO `faculty_login` (`faculty_id`, `faculty_username`, `faculty_password`) VALUES
(88, 'FC-000001', '$2y$10$A.WiA1fjJiSgvpMOH8VhCORxSvylQtiDWuM8xDSZlOxy8Ok0ZkXIG'),
(89, 'FC-000002', '$2y$10$HcePiRjtyu3BkV5.REhWve5tdXc7ro90qgbFnVIb.kUKgna1gj5EW');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_subjects`
--

CREATE TABLE `faculty_subjects` (
  `id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_subjects`
--

INSERT INTO `faculty_subjects` (`id`, `faculty_id`, `subject_id`) VALUES
(363, 89, 58),
(364, 89, 59),
(365, 89, 54),
(366, 88, 56),
(367, 88, 57),
(368, 88, 58),
(369, 88, 55);

-- --------------------------------------------------------

--
-- Table structure for table `faculty_uploaded_reports`
--

CREATE TABLE `faculty_uploaded_reports` (
  `id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_codes`
--

CREATE TABLE `password_reset_codes` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_login`
--

CREATE TABLE `student_login` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_login`
--

INSERT INTO `student_login` (`id`, `username`, `password`) VALUES
(223, 'GC-230539', '$2y$10$U47Tmoeu2OQOoEb1qjK9t.P64Y2WKMyDWyeDQlXRlH4UkO378usZu');

-- --------------------------------------------------------

--
-- Table structure for table `student_subjects`
--

CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_subjects`
--

INSERT INTO `student_subjects` (`id`, `student_id`, `subject_id`) VALUES
(3089, 145, 54),
(3090, 145, 55),
(3091, 145, 56),
(3092, 145, 57),
(3093, 145, 58),
(3094, 145, 59);

-- --------------------------------------------------------

--
-- Table structure for table `student_subject_classes`
--

CREATE TABLE `student_subject_classes` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_subject_classes`
--

INSERT INTO `student_subject_classes` (`id`, `student_id`, `subject_id`, `class_id`) VALUES
(1413, 145, 54, 23),
(1414, 145, 55, 23),
(1415, 145, 56, 23),
(1416, 145, 57, 23),
(1417, 145, 58, 23),
(1418, 145, 59, 23);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `add_categories`
--
ALTER TABLE `add_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_number` (`section_number`);

--
-- Indexes for table `add_classes`
--
ALTER TABLE `add_classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `add_faculties`
--
ALTER TABLE `add_faculties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `add_programs`
--
ALTER TABLE `add_programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD UNIQUE KEY `program_code_2` (`program_code`);

--
-- Indexes for table `add_questions`
--
ALTER TABLE `add_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `add_students`
--
ALTER TABLE `add_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `add_subjects`
--
ALTER TABLE `add_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `admin_login`
--
ALTER TABLE `admin_login`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `admin_username` (`admin_username`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_class_subject` (`class_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_student_faculty_subject_class_term` (`student_id`,`faculty_id`,`subject_id`,`class_id`,`academic_year`,`semester`),
  ADD KEY `idx_eval_faculty` (`faculty_id`),
  ADD KEY `idx_eval_student` (`student_id`),
  ADD KEY `idx_eval_subject` (`subject_id`),
  ADD KEY `idx_eval_class` (`class_id`);

--
-- Indexes for table `evaluation_answers`
--
ALTER TABLE `evaluation_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_eval_question` (`evaluation_id`,`question_id`),
  ADD KEY `idx_answer_eval` (`evaluation_id`),
  ADD KEY `idx_answer_question` (`question_id`);

--
-- Indexes for table `evaluation_periods`
--
ALTER TABLE `evaluation_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `evaluation_settings`
--
ALTER TABLE `evaluation_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active_period` (`active_period_id`);

--
-- Indexes for table `faculty_login`
--
ALTER TABLE `faculty_login`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `faculty_username` (`faculty_username`);

--
-- Indexes for table `faculty_subjects`
--
ALTER TABLE `faculty_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `faculty_uploaded_reports`
--
ALTER TABLE `faculty_uploaded_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_faculty` (`faculty_id`);

--
-- Indexes for table `password_reset_codes`
--
ALTER TABLE `password_reset_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `student_login`
--
ALTER TABLE `student_login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `student_subject_classes`
--
ALTER TABLE `student_subject_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_subject_class` (`student_id`,`subject_id`,`class_id`),
  ADD KEY `idx_ssc_student` (`student_id`),
  ADD KEY `idx_ssc_class` (`class_id`),
  ADD KEY `idx_ssc_subject` (`subject_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `add_categories`
--
ALTER TABLE `add_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `add_classes`
--
ALTER TABLE `add_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `add_faculties`
--
ALTER TABLE `add_faculties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `add_programs`
--
ALTER TABLE `add_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `add_questions`
--
ALTER TABLE `add_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `add_students`
--
ALTER TABLE `add_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT for table `add_subjects`
--
ALTER TABLE `add_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `admin_login`
--
ALTER TABLE `admin_login`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=197;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `evaluation_answers`
--
ALTER TABLE `evaluation_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=496;

--
-- AUTO_INCREMENT for table `evaluation_periods`
--
ALTER TABLE `evaluation_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `faculty_login`
--
ALTER TABLE `faculty_login`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `faculty_subjects`
--
ALTER TABLE `faculty_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=370;

--
-- AUTO_INCREMENT for table `faculty_uploaded_reports`
--
ALTER TABLE `faculty_uploaded_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `password_reset_codes`
--
ALTER TABLE `password_reset_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `student_login`
--
ALTER TABLE `student_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=233;

--
-- AUTO_INCREMENT for table `student_subjects`
--
ALTER TABLE `student_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3095;

--
-- AUTO_INCREMENT for table `student_subject_classes`
--
ALTER TABLE `student_subject_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1419;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `add_classes`
--
ALTER TABLE `add_classes`
  ADD CONSTRAINT `add_classes_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `add_programs` (`id`);

--
-- Constraints for table `add_questions`
--
ALTER TABLE `add_questions`
  ADD CONSTRAINT `add_questions_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `add_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `add_subjects`
--
ALTER TABLE `add_subjects`
  ADD CONSTRAINT `add_subjects_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `add_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `add_classes` (`id`),
  ADD CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `add_subjects` (`id`),
  ADD CONSTRAINT `class_subjects_ibfk_3` FOREIGN KEY (`faculty_id`) REFERENCES `add_faculties` (`id`);

--
-- Constraints for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `fk_evaluations_class` FOREIGN KEY (`class_id`) REFERENCES `add_classes` (`id`),
  ADD CONSTRAINT `fk_evaluations_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `add_faculties` (`id`),
  ADD CONSTRAINT `fk_evaluations_student` FOREIGN KEY (`student_id`) REFERENCES `add_students` (`id`),
  ADD CONSTRAINT `fk_evaluations_subject` FOREIGN KEY (`subject_id`) REFERENCES `add_subjects` (`id`);

--
-- Constraints for table `evaluation_answers`
--
ALTER TABLE `evaluation_answers`
  ADD CONSTRAINT `fk_answers_evaluation` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `add_questions` (`id`);

--
-- Constraints for table `evaluation_settings`
--
ALTER TABLE `evaluation_settings`
  ADD CONSTRAINT `fk_settings_active_period` FOREIGN KEY (`active_period_id`) REFERENCES `evaluation_periods` (`id`);

--
-- Constraints for table `faculty_login`
--
ALTER TABLE `faculty_login`
  ADD CONSTRAINT `fk_faculty_login_profile` FOREIGN KEY (`faculty_id`) REFERENCES `add_faculties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty_subjects`
--
ALTER TABLE `faculty_subjects`
  ADD CONSTRAINT `faculty_subjects_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `add_faculties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `faculty_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `add_subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty_uploaded_reports`
--
ALTER TABLE `faculty_uploaded_reports`
  ADD CONSTRAINT `fk_reports_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `add_faculties` (`id`);

--
-- Constraints for table `student_login`
--
ALTER TABLE `student_login`
  ADD CONSTRAINT `fk_student_login_profile` FOREIGN KEY (`username`) REFERENCES `add_students` (`student_number`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD CONSTRAINT `student_subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `add_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `add_subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_subject_classes`
--
ALTER TABLE `student_subject_classes`
  ADD CONSTRAINT `fk_ssc_class` FOREIGN KEY (`class_id`) REFERENCES `add_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ssc_student` FOREIGN KEY (`student_id`) REFERENCES `add_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ssc_subject` FOREIGN KEY (`subject_id`) REFERENCES `add_subjects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
