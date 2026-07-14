-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2026 at 05:37 PM
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_categories`
--

INSERT INTO `add_categories` (`id`, `category_name`, `section_number`, `created_at`, `updated_at`) VALUES
(17, 'TEACHING EFFECTIVENESS', '1', '2026-05-13 11:37:45', '2026-05-13 11:37:45'),
(19, 'PROFESSIONALISM', '2', '2026-05-13 11:37:45', '2026-05-13 11:37:45'),
(39, 'Student Engagement', '3', '2026-05-13 11:37:45', '2026-05-13 11:37:45');

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
(7, 93, '3rd Year', '2nd Semester', 'C', '2026-05-13 11:31:11', '2026-05-14 04:56:18');

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
  `status` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(93, 'BSIT\n  ', 'Bachelor of Science in IT', '2026-05-13 11:31:11', '2026-05-13 11:31:11'),
(101, 'BSCRIM', 'Bachelor of Science in Criminology', '2026-05-13 11:31:11', '2026-05-13 11:31:11'),
(102, 'BSCS', 'Bachelor of Science in CS', '2026-05-13 11:31:11', '2026-05-13 11:31:11');

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
(18, 17, 'The instructor explain the lessons accurately.', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(19, 17, 'The instructor gives relevant examples', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(20, 17, 'The instructor encourages critical thinking.', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(21, 19, 'The faculty is punctual in class session', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(22, 17, 'The faculty uses teaching strategies that enhance student learning.', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(23, 17, 'The faculty maintains student interest throughout the class.', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(28, 19, 'The faculty maintains professional conduct.', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(29, 19, 'The faculty follows institutional policies.', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(30, 19, 'The faculty shows commitment to teaching.', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(31, 19, 'The faculty maintains fairness in grading.', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(32, 39, 'The faculty encourages active participation.', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(33, 39, 'The faculty fosters collaborative learning', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(34, 39, 'The faculty responds to student inquiries promptly.', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(35, 39, 'The faculty promotes inclusive classroom practices.', '2026-05-13 11:37:29', '2026-05-13 11:37:29'),
(36, 39, 'The faculty motivates students to excel.', '2026-05-13 11:37:29', '2026-05-13 11:37:29');

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
(39, 93, 'CC106', 'ADET', '3rd Year', '2nd Semester', '2026-05-13 11:31:11', '2026-05-15 13:42:54'),
(40, 93, 'ITC16', 'IT Project Management', '3rd Year', '2nd Semester', '2026-05-13 11:31:11', '2026-05-13 11:31:11'),
(41, 93, 'ITC15', 'Capstone Project and Research 1', '3rd Year', '2nd Semester', '2026-05-13 11:31:11', '2026-05-13 11:31:11'),
(42, 93, 'ITC14', 'Information Assurance and Security 1', '3rd Year', '2nd Semester', '2026-05-13 11:31:11', '2026-05-13 11:31:11'),
(43, 93, 'ITE2', 'System Integration and Architecture 2', '3rd Year', '2nd Semester', '2026-05-13 11:31:11', '2026-05-13 11:31:11'),
(44, 93, 'GEELC2', 'The Entrepreneurial Mind', '3rd Year', '2nd Semester', '2026-05-13 11:31:11', '2026-05-13 11:31:11');

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
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `year_level` varchar(20) NOT NULL,
  `block` varchar(10) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `program_id`, `section_name`, `year_level`, `block`, `status`, `created_at`, `updated_at`) VALUES
(1, 79, 'A', '3rd Year', 'A', 'active', '2026-05-03 06:37:31', '2026-05-13 11:38:10');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 1, 18, 5, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(2, 1, 19, 4, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(3, 1, 20, 3, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(4, 1, 22, 2, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(5, 1, 23, 1, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(6, 1, 21, 5, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(7, 1, 28, 4, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(8, 1, 29, 3, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(9, 1, 30, 2, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(10, 1, 31, 1, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(11, 1, 32, 5, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(12, 1, 33, 4, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(13, 1, 34, 3, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(14, 1, 35, 2, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(15, 1, 36, 1, '2026-05-06 14:02:12', '2026-05-13 11:39:08'),
(16, 2, 18, 5, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(17, 2, 19, 4, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(18, 2, 20, 3, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(19, 2, 22, 2, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(20, 2, 23, 1, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(21, 2, 21, 5, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(22, 2, 28, 5, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(23, 2, 29, 5, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(24, 2, 30, 5, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(25, 2, 31, 2, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(26, 2, 32, 2, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(27, 2, 33, 3, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(28, 2, 34, 4, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(29, 2, 35, 5, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(30, 2, 36, 1, '2026-05-06 14:33:29', '2026-05-13 11:39:08'),
(31, 3, 18, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(32, 3, 19, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(33, 3, 20, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(34, 3, 22, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(35, 3, 23, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(36, 3, 21, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(37, 3, 28, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(38, 3, 29, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(39, 3, 30, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(40, 3, 31, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(41, 3, 32, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(42, 3, 33, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(43, 3, 34, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(44, 3, 35, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(45, 3, 36, 5, '2026-05-06 16:15:30', '2026-05-13 11:39:08'),
(46, 4, 18, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(47, 4, 19, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(48, 4, 20, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(49, 4, 22, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(50, 4, 23, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(51, 4, 21, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(52, 4, 28, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(53, 4, 29, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(54, 4, 30, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(55, 4, 31, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(56, 4, 32, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(57, 4, 33, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(58, 4, 34, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(59, 4, 35, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(60, 4, 36, 5, '2026-05-06 16:17:11', '2026-05-13 11:39:08'),
(61, 5, 18, 5, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(62, 5, 19, 5, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(63, 5, 20, 5, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(64, 5, 22, 5, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(65, 5, 23, 5, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(66, 5, 21, 4, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(67, 5, 28, 4, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(68, 5, 29, 4, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(69, 5, 30, 4, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(70, 5, 31, 4, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(71, 5, 32, 3, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(72, 5, 33, 3, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(73, 5, 34, 3, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(74, 5, 35, 3, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(75, 5, 36, 3, '2026-05-06 16:22:23', '2026-05-13 11:39:08'),
(76, 6, 18, 5, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(77, 6, 19, 4, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(78, 6, 20, 5, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(79, 6, 22, 4, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(80, 6, 23, 5, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(81, 6, 21, 5, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(82, 6, 28, 4, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(83, 6, 29, 4, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(84, 6, 30, 4, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(85, 6, 31, 5, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(86, 6, 32, 4, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(87, 6, 33, 4, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(88, 6, 34, 4, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(89, 6, 35, 4, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(90, 6, 36, 4, '2026-05-06 16:22:52', '2026-05-13 11:39:08'),
(91, 7, 18, 5, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(92, 7, 19, 5, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(93, 7, 20, 3, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(94, 7, 22, 3, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(95, 7, 23, 4, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(96, 7, 21, 4, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(97, 7, 28, 4, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(98, 7, 29, 4, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(99, 7, 30, 4, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(100, 7, 31, 4, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(101, 7, 32, 3, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(102, 7, 33, 4, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(103, 7, 34, 3, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(104, 7, 35, 5, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(105, 7, 36, 5, '2026-05-07 16:37:55', '2026-05-13 11:39:08'),
(106, 8, 18, 5, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(107, 8, 19, 4, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(108, 8, 20, 4, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(109, 8, 22, 4, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(110, 8, 23, 4, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(111, 8, 21, 4, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(112, 8, 28, 4, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(113, 8, 29, 4, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(114, 8, 30, 4, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(115, 8, 31, 4, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(116, 8, 32, 5, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(117, 8, 33, 5, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(118, 8, 34, 5, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(119, 8, 35, 5, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(120, 8, 36, 5, '2026-05-08 05:31:01', '2026-05-13 11:39:08'),
(121, 9, 18, 5, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(122, 9, 19, 5, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(123, 9, 20, 4, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(124, 9, 22, 3, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(125, 9, 23, 2, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(126, 9, 21, 4, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(127, 9, 28, 4, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(128, 9, 29, 4, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(129, 9, 30, 4, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(130, 9, 31, 4, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(131, 9, 32, 3, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(132, 9, 33, 3, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(133, 9, 34, 4, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(134, 9, 35, 5, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(135, 9, 36, 5, '2026-05-08 06:02:33', '2026-05-13 11:39:08'),
(136, 10, 18, 5, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(137, 10, 19, 5, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(138, 10, 20, 5, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(139, 10, 22, 5, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(140, 10, 23, 5, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(141, 10, 21, 1, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(142, 10, 28, 2, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(143, 10, 29, 3, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(144, 10, 30, 4, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(145, 10, 31, 5, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(146, 10, 32, 4, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(147, 10, 33, 4, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(148, 10, 34, 3, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(149, 10, 35, 3, '2026-05-08 12:35:11', '2026-05-13 11:39:08'),
(150, 10, 36, 3, '2026-05-08 12:35:11', '2026-05-13 11:39:08');

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
(31, 'FC-0001', '$2y$10$buY8aSjBQ7uTzdvVz7rNBuyHO3F4JkV4n4WLw778zxue9tNlaU4aq');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_subjects`
--

CREATE TABLE `faculty_subjects` (
  `id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
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
(1, 'GC-230539', '$2y$10$uU9nG.K3eWhKPDebIuBvN.koL5NE9K/8PutgHlilvJZigIS9KnCtK'),
(38, 'GC-230540', '$2y$10$RO1d6k96HxZTOyQyRWVkxejT6ngL6/G4Z6mCULmRdnTXWaTbQrHeu');

-- --------------------------------------------------------

--
-- Table structure for table `student_subjects`
--

CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `subject_semesters`
--

CREATE TABLE `subject_semesters` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `semester` enum('1st Semester','2nd Semester') NOT NULL,
  `academic_year` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

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
  ADD UNIQUE KEY `uniq_student_faculty_subject_class` (`student_id`,`faculty_id`,`subject_id`,`class_id`),
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
  ADD KEY `idx_answer_eval` (`evaluation_id`);

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
  ADD UNIQUE KEY `unique_student_subject_class` (`student_id`,`subject_id`),
  ADD KEY `idx_ssc_student` (`student_id`),
  ADD KEY `idx_ssc_class` (`class_id`);

--
-- Indexes for table `subject_semesters`
--
ALTER TABLE `subject_semesters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `add_categories`
--
ALTER TABLE `add_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `add_classes`
--
ALTER TABLE `add_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `add_faculties`
--
ALTER TABLE `add_faculties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `add_programs`
--
ALTER TABLE `add_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `add_questions`
--
ALTER TABLE `add_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `add_students`
--
ALTER TABLE `add_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `add_subjects`
--
ALTER TABLE `add_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `admin_login`
--
ALTER TABLE `admin_login`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluation_answers`
--
ALTER TABLE `evaluation_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `faculty_login`
--
ALTER TABLE `faculty_login`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `faculty_subjects`
--
ALTER TABLE `faculty_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=230;

--
-- AUTO_INCREMENT for table `student_login`
--
ALTER TABLE `student_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `student_subjects`
--
ALTER TABLE `student_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1191;

--
-- AUTO_INCREMENT for table `student_subject_classes`
--
ALTER TABLE `student_subject_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `subject_semesters`
--
ALTER TABLE `subject_semesters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `faculty_subjects`
--
ALTER TABLE `faculty_subjects`
  ADD CONSTRAINT `faculty_subjects_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `add_faculties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `faculty_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `add_subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD CONSTRAINT `student_subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `add_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `add_subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_semesters`
--
ALTER TABLE `subject_semesters`
  ADD CONSTRAINT `subject_semesters_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `add_subjects` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
