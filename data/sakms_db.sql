-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 12, 2026 at 04:56 PM
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
-- Database: `sakms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `at_risk_notifications`
--

CREATE TABLE `at_risk_notifications` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `staff_code` varchar(20) NOT NULL,
  `staff_name` varchar(255) NOT NULL,
  `kpi_score` decimal(5,4) NOT NULL,
  `period_id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_period`
--

CREATE TABLE `evaluation_period` (
  `period_id` int(11) NOT NULL,
  `period_label` varchar(50) NOT NULL,
  `year` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_period`
--

INSERT INTO `evaluation_period` (`period_id`, `period_label`, `year`, `start_date`, `end_date`) VALUES
(1, 'Year 2022', 2022, '2022-01-01', '2022-12-31'),
(2, 'Year 2023', 2023, '2023-01-01', '2023-12-31'),
(3, 'Year 2024', 2024, '2024-01-01', '2024-12-31'),
(4, 'Year 2025', 2025, '2025-01-01', '2025-12-31');

-- --------------------------------------------------------

--
-- Table structure for table `kpi_group`
--

CREATE TABLE `kpi_group` (
  `kpi_group_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `weight_percentage` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kpi_group`
--

INSERT INTO `kpi_group` (`kpi_group_id`, `section_id`, `group_name`, `weight_percentage`) VALUES
(1, 1, 'Competency', 25.00),
(2, 2, 'Daily Sales Operations', 15.00),
(3, 2, 'Customer Service Quality', 15.00),
(4, 2, 'Sales Target Contribution', 15.00),
(5, 2, 'Training, Learning & Team Contribution', 10.00),
(6, 2, 'Inventory & Cost Control', 5.00),
(7, 2, 'Store Operations Support', 15.00);

-- --------------------------------------------------------

--
-- Table structure for table `kpi_item`
--

CREATE TABLE `kpi_item` (
  `kpi_item_id` int(11) NOT NULL,
  `kpi_code` varchar(20) NOT NULL,
  `kpi_group_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kpi_item`
--

INSERT INTO `kpi_item` (`kpi_item_id`, `kpi_code`, `kpi_group_id`, `description`) VALUES
(1, '1.1.1', 2, 'Accurate sales transaction processing'),
(2, '1.1.2', 2, 'Correct handling of cash, card, and e-wallet payments'),
(3, '1.1.3', 2, 'Compliance with opening and closing procedures'),
(4, '1.1.4', 2, 'Sales floor kept organised and presentable'),
(5, '1.2.1', 3, 'Accurate information provided to customers'),
(6, '1.2.2', 3, 'Complaints handled professionally and escalated'),
(7, '1.2.3', 3, 'Compliance with service standards'),
(8, '2.1.1', 4, 'Achievement of assigned sales targets'),
(9, '2.1.2', 4, 'Participation in sales campaigns and promotions'),
(10, '3.1.1', 5, 'Attendance at required training programmes'),
(11, '3.1.2', 5, 'Applies learning to daily sales work'),
(12, '3.1.3', 5, 'Supports team operations during peak periods'),
(13, '3.1.4', 5, 'Participates in team activities or briefings'),
(14, '4.1.1', 6, 'Proper inventory handling'),
(15, '4.1.2', 6, 'Minimisation of stock loss or damage'),
(16, '4.2.1', 7, 'Stock receiving and shelving support'),
(17, '4.2.2', 7, 'Promotion and display setup support'),
(18, '4.2.3', 7, 'Compliance with SOP and safety rules'),
(19, 'S1.1', 1, 'Initiative'),
(20, 'S1.2', 1, 'Professional Conduct'),
(21, 'S1.3', 1, 'Reliability & Accountability');

-- --------------------------------------------------------

--
-- Table structure for table `kpi_score`
--

CREATE TABLE `kpi_score` (
  `score_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `kpi_item_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `score` int(11) NOT NULL CHECK (`score` between 1 and 5),
  `date_recorded` date NOT NULL,
  `evaluated_by` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kpi_score`
--

INSERT INTO `kpi_score` (`score_id`, `staff_id`, `kpi_item_id`, `period_id`, `score`, `date_recorded`, `evaluated_by`) VALUES
(1, 1, 1, 3, 2, '2024-12-30', 1),
(2, 1, 2, 3, 5, '2024-12-30', 1),
(3, 1, 3, 3, 5, '2024-12-30', 1),
(4, 1, 4, 3, 5, '2024-12-30', 1),
(5, 1, 5, 3, 4, '2024-12-30', 1),
(6, 1, 6, 3, 5, '2024-12-30', 1),
(7, 1, 7, 3, 5, '2024-12-30', 1),
(8, 1, 8, 3, 1, '2024-12-30', 1),
(9, 1, 9, 3, 2, '2024-12-30', 1),
(10, 1, 10, 3, 5, '2024-12-30', 1),
(11, 1, 11, 3, 4, '2024-12-30', 1),
(12, 1, 12, 3, 2, '2024-12-30', 1),
(13, 1, 13, 3, 2, '2024-12-30', 1),
(14, 1, 14, 3, 5, '2024-12-30', 1),
(15, 1, 15, 3, 4, '2024-12-30', 1),
(16, 1, 16, 3, 3, '2024-12-30', 1),
(17, 1, 17, 3, 3, '2024-12-30', 1),
(18, 1, 18, 3, 5, '2024-12-30', 1),
(19, 1, 19, 3, 3, '2024-12-30', 1),
(20, 1, 20, 3, 1, '2024-12-30', 1),
(21, 1, 21, 3, 5, '2024-12-30', 1),
(22, 2, 1, 3, 4, '2024-12-30', 1),
(23, 2, 2, 3, 4, '2024-12-30', 1),
(24, 2, 3, 3, 1, '2024-12-30', 1),
(25, 2, 4, 3, 5, '2024-12-30', 1),
(26, 2, 5, 3, 4, '2024-12-30', 1),
(27, 2, 6, 3, 4, '2024-12-30', 1),
(28, 2, 7, 3, 2, '2024-12-30', 1),
(29, 2, 8, 3, 4, '2024-12-30', 1),
(30, 2, 9, 3, 4, '2024-12-30', 1),
(31, 2, 10, 3, 1, '2024-12-30', 1),
(32, 2, 11, 3, 2, '2024-12-30', 1),
(33, 2, 12, 3, 5, '2024-12-30', 1),
(34, 2, 13, 3, 5, '2024-12-30', 1),
(35, 2, 14, 3, 1, '2024-12-30', 1),
(36, 2, 15, 3, 3, '2024-12-30', 1),
(37, 2, 16, 3, 2, '2024-12-30', 1),
(38, 2, 17, 3, 5, '2024-12-30', 1),
(39, 2, 18, 3, 4, '2024-12-30', 1),
(40, 2, 19, 3, 2, '2024-12-30', 1),
(41, 2, 20, 3, 5, '2024-12-30', 1),
(42, 2, 21, 3, 5, '2024-12-30', 1),
(43, 3, 1, 3, 2, '2024-11-29', 1),
(44, 3, 2, 3, 5, '2024-11-29', 1),
(45, 3, 3, 3, 4, '2024-11-29', 1),
(46, 3, 4, 3, 5, '2024-11-29', 1),
(47, 3, 5, 3, 4, '2024-11-29', 1),
(48, 3, 6, 3, 2, '2024-11-29', 1),
(49, 3, 7, 3, 1, '2024-11-29', 1),
(50, 3, 8, 3, 3, '2024-11-29', 1),
(51, 3, 9, 3, 5, '2024-11-29', 1),
(52, 3, 10, 3, 1, '2024-11-29', 1),
(53, 3, 11, 3, 3, '2024-11-29', 1),
(54, 3, 12, 3, 5, '2024-11-29', 1),
(55, 3, 13, 3, 4, '2024-11-29', 1),
(56, 3, 14, 3, 2, '2024-11-29', 1),
(57, 3, 15, 3, 2, '2024-11-29', 1),
(58, 3, 16, 3, 5, '2024-11-29', 1),
(59, 3, 17, 3, 3, '2024-11-29', 1),
(60, 3, 18, 3, 5, '2024-11-29', 1),
(61, 3, 19, 3, 2, '2024-11-29', 1),
(62, 3, 20, 3, 3, '2024-11-29', 1),
(63, 3, 21, 3, 1, '2024-11-29', 1),
(64, 4, 1, 3, 5, '2024-11-28', 1),
(65, 4, 2, 3, 5, '2024-11-28', 1),
(66, 4, 3, 3, 5, '2024-11-28', 1),
(67, 4, 4, 3, 5, '2024-11-28', 1),
(68, 4, 5, 3, 5, '2024-11-28', 1),
(69, 4, 6, 3, 5, '2024-11-28', 1),
(70, 4, 7, 3, 5, '2024-11-28', 1),
(71, 4, 8, 3, 5, '2024-11-28', 1),
(72, 4, 9, 3, 5, '2024-11-28', 1),
(73, 4, 10, 3, 5, '2024-11-28', 1),
(74, 4, 11, 3, 5, '2024-11-28', 1),
(75, 4, 12, 3, 5, '2024-11-28', 1),
(76, 4, 13, 3, 5, '2024-11-28', 1),
(77, 4, 14, 3, 5, '2024-11-28', 1),
(78, 4, 15, 3, 5, '2024-11-28', 1),
(79, 4, 16, 3, 5, '2024-11-28', 1),
(80, 4, 17, 3, 5, '2024-11-28', 1),
(81, 4, 18, 3, 5, '2024-11-28', 1),
(82, 4, 19, 3, 5, '2024-11-28', 1),
(83, 4, 20, 3, 5, '2024-11-28', 1),
(84, 4, 21, 3, 5, '2024-11-28', 1),
(85, 5, 1, 3, 5, '2024-10-05', 1),
(86, 5, 2, 3, 1, '2024-10-05', 1),
(87, 5, 3, 3, 1, '2024-10-05', 1),
(88, 5, 4, 3, 3, '2024-10-05', 1),
(89, 5, 5, 3, 1, '2024-10-05', 1),
(90, 5, 6, 3, 3, '2024-10-05', 1),
(91, 5, 7, 3, 3, '2024-10-05', 1),
(92, 5, 8, 3, 3, '2024-10-05', 1),
(93, 5, 9, 3, 5, '2024-10-05', 1),
(94, 5, 10, 3, 3, '2024-10-05', 1),
(95, 5, 11, 3, 2, '2024-10-05', 1),
(96, 5, 12, 3, 4, '2024-10-05', 1),
(97, 5, 13, 3, 2, '2024-10-05', 1),
(98, 5, 14, 3, 5, '2024-10-05', 1),
(99, 5, 15, 3, 2, '2024-10-05', 1),
(100, 5, 16, 3, 1, '2024-10-05', 1),
(101, 5, 17, 3, 4, '2024-10-05', 1),
(102, 5, 18, 3, 1, '2024-10-05', 1),
(103, 5, 19, 3, 3, '2024-10-05', 1),
(104, 5, 20, 3, 1, '2024-10-05', 1),
(105, 5, 21, 3, 1, '2024-10-05', 1),
(106, 6, 1, 3, 4, '2024-11-05', 1),
(107, 6, 2, 3, 1, '2024-11-05', 1),
(108, 6, 3, 3, 1, '2024-11-05', 1),
(109, 6, 4, 3, 5, '2024-11-05', 1),
(110, 6, 5, 3, 3, '2024-11-05', 1),
(111, 6, 6, 3, 3, '2024-11-05', 1),
(112, 6, 7, 3, 5, '2024-11-05', 1),
(113, 6, 8, 3, 5, '2024-11-05', 1),
(114, 6, 9, 3, 1, '2024-11-05', 1),
(115, 6, 10, 3, 2, '2024-11-05', 1),
(116, 6, 11, 3, 2, '2024-11-05', 1),
(117, 6, 12, 3, 5, '2024-11-05', 1),
(118, 6, 13, 3, 3, '2024-11-05', 1),
(119, 6, 14, 3, 4, '2024-11-05', 1),
(120, 6, 15, 3, 2, '2024-11-05', 1),
(121, 6, 16, 3, 5, '2024-11-05', 1),
(122, 6, 17, 3, 1, '2024-11-05', 1),
(123, 6, 18, 3, 4, '2024-11-05', 1),
(124, 6, 19, 3, 5, '2024-11-05', 1),
(125, 6, 20, 3, 1, '2024-11-05', 1),
(126, 6, 21, 3, 2, '2024-11-05', 1),
(127, 7, 1, 3, 5, '2024-12-05', 1),
(128, 7, 2, 3, 5, '2024-12-05', 1),
(129, 7, 3, 3, 2, '2024-12-05', 1),
(130, 7, 4, 3, 5, '2024-12-05', 1),
(131, 7, 5, 3, 2, '2024-12-05', 1),
(132, 7, 6, 3, 3, '2024-12-05', 1),
(133, 7, 7, 3, 5, '2024-12-05', 1),
(134, 7, 8, 3, 3, '2024-12-05', 1),
(135, 7, 9, 3, 4, '2024-12-05', 1),
(136, 7, 10, 3, 1, '2024-12-05', 1),
(137, 7, 11, 3, 2, '2024-12-05', 1),
(138, 7, 12, 3, 5, '2024-12-05', 1),
(139, 7, 13, 3, 1, '2024-12-05', 1),
(140, 7, 14, 3, 3, '2024-12-05', 1),
(141, 7, 15, 3, 2, '2024-12-05', 1),
(142, 7, 16, 3, 2, '2024-12-05', 1),
(143, 7, 17, 3, 5, '2024-12-05', 1),
(144, 7, 18, 3, 5, '2024-12-05', 1),
(145, 7, 19, 3, 2, '2024-12-05', 1),
(146, 7, 20, 3, 1, '2024-12-05', 1),
(147, 7, 21, 3, 1, '2024-12-05', 1),
(148, 8, 1, 3, 2, '2024-12-06', 1),
(149, 8, 2, 3, 4, '2024-12-06', 1),
(150, 8, 3, 3, 4, '2024-12-06', 1),
(151, 8, 4, 3, 4, '2024-12-06', 1),
(152, 8, 5, 3, 1, '2024-12-06', 1),
(153, 8, 6, 3, 3, '2024-12-06', 1),
(154, 8, 7, 3, 3, '2024-12-06', 1),
(155, 8, 8, 3, 1, '2024-12-06', 1),
(156, 8, 9, 3, 2, '2024-12-06', 1),
(157, 8, 10, 3, 1, '2024-12-06', 1),
(158, 8, 11, 3, 2, '2024-12-06', 1),
(159, 8, 12, 3, 1, '2024-12-06', 1),
(160, 8, 13, 3, 4, '2024-12-06', 1),
(161, 8, 14, 3, 2, '2024-12-06', 1),
(162, 8, 15, 3, 1, '2024-12-06', 1),
(163, 8, 16, 3, 1, '2024-12-06', 1),
(164, 8, 17, 3, 4, '2024-12-06', 1),
(165, 8, 18, 3, 2, '2024-12-06', 1),
(166, 8, 19, 3, 5, '2024-12-06', 1),
(167, 8, 20, 3, 4, '2024-12-06', 1),
(168, 8, 21, 3, 1, '2024-12-06', 1),
(169, 9, 1, 3, 5, '2024-12-07', 1),
(170, 9, 2, 3, 3, '2024-12-07', 1),
(171, 9, 3, 3, 2, '2024-12-07', 1),
(172, 9, 4, 3, 5, '2024-12-07', 1),
(173, 9, 5, 3, 4, '2024-12-07', 1),
(174, 9, 6, 3, 2, '2024-12-07', 1),
(175, 9, 7, 3, 2, '2024-12-07', 1),
(176, 9, 8, 3, 2, '2024-12-07', 1),
(177, 9, 9, 3, 4, '2024-12-07', 1),
(178, 9, 10, 3, 3, '2024-12-07', 1),
(179, 9, 11, 3, 5, '2024-12-07', 1),
(180, 9, 12, 3, 2, '2024-12-07', 1),
(181, 9, 13, 3, 4, '2024-12-07', 1),
(182, 9, 14, 3, 2, '2024-12-07', 1),
(183, 9, 15, 3, 2, '2024-12-07', 1),
(184, 9, 16, 3, 2, '2024-12-07', 1),
(185, 9, 17, 3, 2, '2024-12-07', 1),
(186, 9, 18, 3, 4, '2024-12-07', 1),
(187, 9, 19, 3, 5, '2024-12-07', 1),
(188, 9, 20, 3, 2, '2024-12-07', 1),
(189, 9, 21, 3, 4, '2024-12-07', 1),
(190, 10, 1, 3, 1, '2024-12-08', 1),
(191, 10, 2, 3, 1, '2024-12-08', 1),
(192, 10, 3, 3, 1, '2024-12-08', 1),
(193, 10, 4, 3, 1, '2024-12-08', 1),
(194, 10, 5, 3, 1, '2024-12-08', 1),
(195, 10, 6, 3, 1, '2024-12-08', 1),
(196, 10, 7, 3, 1, '2024-12-08', 1),
(197, 10, 8, 3, 1, '2024-12-08', 1),
(198, 10, 9, 3, 1, '2024-12-08', 1),
(199, 10, 10, 3, 5, '2024-12-08', 1),
(200, 10, 11, 3, 5, '2024-12-08', 1),
(201, 10, 12, 3, 5, '2024-12-08', 1),
(202, 10, 13, 3, 5, '2024-12-08', 1),
(203, 10, 14, 3, 5, '2024-12-08', 1),
(204, 10, 15, 3, 5, '2024-12-08', 1),
(205, 10, 16, 3, 5, '2024-12-08', 1),
(206, 10, 17, 3, 5, '2024-12-08', 1),
(207, 10, 18, 3, 5, '2024-12-08', 1),
(208, 10, 19, 3, 1, '2024-12-08', 1),
(209, 10, 20, 3, 1, '2024-12-08', 1),
(210, 10, 21, 3, 1, '2024-12-08', 1),
(211, 11, 1, 3, 1, '2024-12-09', 1),
(212, 11, 2, 3, 1, '2024-12-09', 1),
(213, 11, 3, 3, 1, '2024-12-09', 1),
(214, 11, 4, 3, 1, '2024-12-09', 1),
(215, 11, 5, 3, 1, '2024-12-09', 1),
(216, 11, 6, 3, 1, '2024-12-09', 1),
(217, 11, 7, 3, 1, '2024-12-09', 1),
(218, 11, 8, 3, 1, '2024-12-09', 1),
(219, 11, 9, 3, 1, '2024-12-09', 1),
(220, 11, 10, 3, 1, '2024-12-09', 1),
(221, 11, 11, 3, 1, '2024-12-09', 1),
(222, 11, 12, 3, 1, '2024-12-09', 1),
(223, 11, 13, 3, 1, '2024-12-09', 1),
(224, 11, 14, 3, 1, '2024-12-09', 1),
(225, 11, 15, 3, 1, '2024-12-09', 1),
(226, 11, 16, 3, 1, '2024-12-09', 1),
(227, 11, 17, 3, 1, '2024-12-09', 1),
(228, 11, 18, 3, 1, '2024-12-09', 1),
(229, 11, 19, 3, 1, '2024-12-09', 1),
(230, 11, 20, 3, 1, '2024-12-09', 1),
(231, 11, 21, 3, 1, '2024-12-09', 1),
(232, 1, 1, 4, 3, '2025-12-31', 1),
(233, 1, 2, 4, 5, '2025-12-31', 1),
(234, 1, 3, 4, 2, '2025-12-31', 1),
(235, 1, 4, 4, 3, '2025-12-31', 1),
(236, 1, 5, 4, 2, '2025-12-31', 1),
(237, 1, 6, 4, 5, '2025-12-31', 1),
(238, 1, 7, 4, 2, '2025-12-31', 1),
(239, 1, 8, 4, 5, '2025-12-31', 1),
(240, 1, 9, 4, 3, '2025-12-31', 1),
(241, 1, 10, 4, 4, '2025-12-31', 1),
(242, 1, 11, 4, 4, '2025-12-31', 1),
(243, 1, 12, 4, 5, '2025-12-31', 1),
(244, 1, 13, 4, 5, '2025-12-31', 1),
(245, 1, 14, 4, 4, '2025-12-31', 1),
(246, 1, 15, 4, 2, '2025-12-31', 1),
(247, 1, 16, 4, 5, '2025-12-31', 1),
(248, 1, 17, 4, 3, '2025-12-31', 1),
(249, 1, 18, 4, 5, '2025-12-31', 1),
(250, 1, 19, 4, 4, '2025-12-31', 1),
(251, 1, 20, 4, 5, '2025-12-31', 1),
(252, 1, 21, 4, 4, '2025-12-31', 1),
(253, 2, 1, 4, 5, '2025-12-31', 1),
(254, 2, 2, 4, 4, '2025-12-31', 1),
(255, 2, 3, 4, 5, '2025-12-31', 1),
(256, 2, 4, 4, 3, '2025-12-31', 1),
(257, 2, 5, 4, 1, '2025-12-31', 1),
(258, 2, 6, 4, 1, '2025-12-31', 1),
(259, 2, 7, 4, 3, '2025-12-31', 1),
(260, 2, 8, 4, 5, '2025-12-31', 1),
(261, 2, 9, 4, 1, '2025-12-31', 1),
(262, 2, 10, 4, 4, '2025-12-31', 1),
(263, 2, 11, 4, 5, '2025-12-31', 1),
(264, 2, 12, 4, 4, '2025-12-31', 1),
(265, 2, 13, 4, 5, '2025-12-31', 1),
(266, 2, 14, 4, 4, '2025-12-31', 1),
(267, 2, 15, 4, 4, '2025-12-31', 1),
(268, 2, 16, 4, 2, '2025-12-31', 1),
(269, 2, 17, 4, 1, '2025-12-31', 1),
(270, 2, 18, 4, 2, '2025-12-31', 1),
(271, 2, 19, 4, 3, '2025-12-31', 1),
(272, 2, 20, 4, 1, '2025-12-31', 1),
(273, 2, 21, 4, 2, '2025-12-31', 1),
(274, 3, 1, 4, 3, '2025-11-30', 1),
(275, 3, 2, 4, 5, '2025-11-30', 1),
(276, 3, 3, 4, 5, '2025-11-30', 1),
(277, 3, 4, 4, 1, '2025-11-30', 1),
(278, 3, 5, 4, 1, '2025-11-30', 1),
(279, 3, 6, 4, 1, '2025-11-30', 1),
(280, 3, 7, 4, 5, '2025-11-30', 1),
(281, 3, 8, 4, 4, '2025-11-30', 1),
(282, 3, 9, 4, 1, '2025-11-30', 1),
(283, 3, 10, 4, 3, '2025-11-30', 1),
(284, 3, 11, 4, 4, '2025-11-30', 1),
(285, 3, 12, 4, 5, '2025-11-30', 1),
(286, 3, 13, 4, 2, '2025-11-30', 1),
(287, 3, 14, 4, 2, '2025-11-30', 1),
(288, 3, 15, 4, 4, '2025-11-30', 1),
(289, 3, 16, 4, 3, '2025-11-30', 1),
(290, 3, 17, 4, 4, '2025-11-30', 1),
(291, 3, 18, 4, 5, '2025-11-30', 1),
(292, 3, 19, 4, 4, '2025-11-30', 1),
(293, 3, 20, 4, 1, '2025-11-30', 1),
(294, 3, 21, 4, 2, '2025-11-30', 1),
(295, 4, 1, 4, 2, '2025-11-29', 1),
(296, 4, 2, 4, 4, '2025-11-29', 1),
(297, 4, 3, 4, 3, '2025-11-29', 1),
(298, 4, 4, 4, 4, '2025-11-29', 1),
(299, 4, 5, 4, 4, '2025-11-29', 1),
(300, 4, 6, 4, 5, '2025-11-29', 1),
(301, 4, 7, 4, 2, '2025-11-29', 1),
(302, 4, 8, 4, 2, '2025-11-29', 1),
(303, 4, 9, 4, 2, '2025-11-29', 1),
(304, 4, 10, 4, 3, '2025-11-29', 1),
(305, 4, 11, 4, 1, '2025-11-29', 1),
(306, 4, 12, 4, 5, '2025-11-29', 1),
(307, 4, 13, 4, 4, '2025-11-29', 1),
(308, 4, 14, 4, 3, '2025-11-29', 1),
(309, 4, 15, 4, 2, '2025-11-29', 1),
(310, 4, 16, 4, 1, '2025-11-29', 1),
(311, 4, 17, 4, 1, '2025-11-29', 1),
(312, 4, 18, 4, 5, '2025-11-29', 1),
(313, 4, 19, 4, 4, '2025-11-29', 1),
(314, 4, 20, 4, 2, '2025-11-29', 1),
(315, 4, 21, 4, 5, '2025-11-29', 1),
(316, 5, 1, 4, 1, '2025-10-06', 1),
(317, 5, 2, 4, 4, '2025-10-06', 1),
(318, 5, 3, 4, 2, '2025-10-06', 1),
(319, 5, 4, 4, 4, '2025-10-06', 1),
(320, 5, 5, 4, 2, '2025-10-06', 1),
(321, 5, 6, 4, 1, '2025-10-06', 1),
(322, 5, 7, 4, 3, '2025-10-06', 1),
(323, 5, 8, 4, 2, '2025-10-06', 1),
(324, 5, 9, 4, 1, '2025-10-06', 1),
(325, 5, 10, 4, 3, '2025-10-06', 1),
(326, 5, 11, 4, 2, '2025-10-06', 1),
(327, 5, 12, 4, 2, '2025-10-06', 1),
(328, 5, 13, 4, 1, '2025-10-06', 1),
(329, 5, 14, 4, 4, '2025-10-06', 1),
(330, 5, 15, 4, 4, '2025-10-06', 1),
(331, 5, 16, 4, 2, '2025-10-06', 1),
(332, 5, 17, 4, 1, '2025-10-06', 1),
(333, 5, 18, 4, 2, '2025-10-06', 1),
(334, 5, 19, 4, 2, '2025-10-06', 1),
(335, 5, 20, 4, 1, '2025-10-06', 1),
(336, 5, 21, 4, 2, '2025-10-06', 1),
(337, 6, 1, 4, 2, '2025-11-06', 1),
(338, 6, 2, 4, 5, '2025-11-06', 1),
(339, 6, 3, 4, 5, '2025-11-06', 1),
(340, 6, 4, 4, 5, '2025-11-06', 1),
(341, 6, 5, 4, 1, '2025-11-06', 1),
(342, 6, 6, 4, 3, '2025-11-06', 1),
(343, 6, 7, 4, 1, '2025-11-06', 1),
(344, 6, 8, 4, 5, '2025-11-06', 1),
(345, 6, 9, 4, 1, '2025-11-06', 1),
(346, 6, 10, 4, 3, '2025-11-06', 1),
(347, 6, 11, 4, 1, '2025-11-06', 1),
(348, 6, 12, 4, 4, '2025-11-06', 1),
(349, 6, 13, 4, 5, '2025-11-06', 1),
(350, 6, 14, 4, 3, '2025-11-06', 1),
(351, 6, 15, 4, 2, '2025-11-06', 1),
(352, 6, 16, 4, 5, '2025-11-06', 1),
(353, 6, 17, 4, 5, '2025-11-06', 1),
(354, 6, 18, 4, 2, '2025-11-06', 1),
(355, 6, 19, 4, 5, '2025-11-06', 1),
(356, 6, 20, 4, 1, '2025-11-06', 1),
(357, 6, 21, 4, 1, '2025-11-06', 1),
(358, 7, 1, 4, 1, '2025-12-06', 1),
(359, 7, 2, 4, 5, '2025-12-06', 1),
(360, 7, 3, 4, 3, '2025-12-06', 1),
(361, 7, 4, 4, 1, '2025-12-06', 1),
(362, 7, 5, 4, 3, '2025-12-06', 1),
(363, 7, 6, 4, 5, '2025-12-06', 1),
(364, 7, 7, 4, 2, '2025-12-06', 1),
(365, 7, 8, 4, 2, '2025-12-06', 1),
(366, 7, 9, 4, 5, '2025-12-06', 1),
(367, 7, 10, 4, 2, '2025-12-06', 1),
(368, 7, 11, 4, 1, '2025-12-06', 1),
(369, 7, 12, 4, 1, '2025-12-06', 1),
(370, 7, 13, 4, 2, '2025-12-06', 1),
(371, 7, 14, 4, 5, '2025-12-06', 1),
(372, 7, 15, 4, 5, '2025-12-06', 1),
(373, 7, 16, 4, 1, '2025-12-06', 1),
(374, 7, 17, 4, 4, '2025-12-06', 1),
(375, 7, 18, 4, 3, '2025-12-06', 1),
(376, 7, 19, 4, 2, '2025-12-06', 1),
(377, 7, 20, 4, 4, '2025-12-06', 1),
(378, 7, 21, 4, 5, '2025-12-06', 1),
(379, 8, 1, 4, 3, '2025-12-07', 1),
(380, 8, 2, 4, 5, '2025-12-07', 1),
(381, 8, 3, 4, 4, '2025-12-07', 1),
(382, 8, 4, 4, 4, '2025-12-07', 1),
(383, 8, 5, 4, 3, '2025-12-07', 1),
(384, 8, 6, 4, 3, '2025-12-07', 1),
(385, 8, 7, 4, 1, '2025-12-07', 1),
(386, 8, 8, 4, 4, '2025-12-07', 1),
(387, 8, 9, 4, 1, '2025-12-07', 1),
(388, 8, 10, 4, 1, '2025-12-07', 1),
(389, 8, 11, 4, 2, '2025-12-07', 1),
(390, 8, 12, 4, 3, '2025-12-07', 1),
(391, 8, 13, 4, 3, '2025-12-07', 1),
(392, 8, 14, 4, 3, '2025-12-07', 1),
(393, 8, 15, 4, 3, '2025-12-07', 1),
(394, 8, 16, 4, 4, '2025-12-07', 1),
(395, 8, 17, 4, 5, '2025-12-07', 1),
(396, 8, 18, 4, 5, '2025-12-07', 1),
(397, 8, 19, 4, 3, '2025-12-07', 1),
(398, 8, 20, 4, 4, '2025-12-07', 1),
(399, 8, 21, 4, 5, '2025-12-07', 1),
(400, 9, 1, 4, 2, '2025-12-08', 1),
(401, 9, 2, 4, 4, '2025-12-08', 1),
(402, 9, 3, 4, 2, '2025-12-08', 1),
(403, 9, 4, 4, 5, '2025-12-08', 1),
(404, 9, 5, 4, 3, '2025-12-08', 1),
(405, 9, 6, 4, 2, '2025-12-08', 1),
(406, 9, 7, 4, 1, '2025-12-08', 1),
(407, 9, 8, 4, 1, '2025-12-08', 1),
(408, 9, 9, 4, 1, '2025-12-08', 1),
(409, 9, 10, 4, 2, '2025-12-08', 1),
(410, 9, 11, 4, 4, '2025-12-08', 1),
(411, 9, 12, 4, 4, '2025-12-08', 1),
(412, 9, 13, 4, 4, '2025-12-08', 1),
(413, 9, 14, 4, 2, '2025-12-08', 1),
(414, 9, 15, 4, 3, '2025-12-08', 1),
(415, 9, 16, 4, 3, '2025-12-08', 1),
(416, 9, 17, 4, 2, '2025-12-08', 1),
(417, 9, 18, 4, 5, '2025-12-08', 1),
(418, 9, 19, 4, 2, '2025-12-08', 1),
(419, 9, 20, 4, 1, '2025-12-08', 1),
(420, 9, 21, 4, 5, '2025-12-08', 1),
(421, 12, 1, 4, 5, '2025-12-09', 1),
(422, 12, 2, 4, 5, '2025-12-09', 1),
(423, 12, 3, 4, 5, '2025-12-09', 1),
(424, 12, 4, 4, 5, '2025-12-09', 1),
(425, 12, 5, 4, 5, '2025-12-09', 1),
(426, 12, 6, 4, 5, '2025-12-09', 1),
(427, 12, 7, 4, 5, '2025-12-09', 1),
(428, 12, 8, 4, 5, '2025-12-09', 1),
(429, 12, 9, 4, 5, '2025-12-09', 1),
(430, 12, 10, 4, 1, '2025-12-09', 1),
(431, 12, 11, 4, 5, '2025-12-09', 1),
(432, 12, 12, 4, 3, '2025-12-09', 1),
(433, 12, 13, 4, 4, '2025-12-09', 1),
(434, 12, 14, 4, 3, '2025-12-09', 1),
(435, 12, 15, 4, 1, '2025-12-09', 1),
(436, 12, 16, 4, 4, '2025-12-09', 1),
(437, 12, 17, 4, 1, '2025-12-09', 1),
(438, 12, 18, 4, 2, '2025-12-09', 1),
(439, 12, 19, 4, 1, '2025-12-09', 1),
(440, 12, 20, 4, 2, '2025-12-09', 1),
(441, 12, 21, 4, 3, '2025-12-09', 1),
(442, 13, 1, 4, 5, '2025-12-10', 1),
(443, 13, 2, 4, 2, '2025-12-10', 1),
(444, 13, 3, 4, 2, '2025-12-10', 1),
(445, 13, 4, 4, 1, '2025-12-10', 1),
(446, 13, 5, 4, 5, '2025-12-10', 1),
(447, 13, 6, 4, 1, '2025-12-10', 1),
(448, 13, 7, 4, 4, '2025-12-10', 1),
(449, 13, 8, 4, 5, '2025-12-10', 1),
(450, 13, 9, 4, 2, '2025-12-10', 1),
(451, 13, 10, 4, 5, '2025-12-10', 1),
(452, 13, 11, 4, 5, '2025-12-10', 1),
(453, 13, 12, 4, 5, '2025-12-10', 1),
(454, 13, 13, 4, 5, '2025-12-10', 1),
(455, 13, 14, 4, 5, '2025-12-10', 1),
(456, 13, 15, 4, 5, '2025-12-10', 1),
(457, 13, 16, 4, 5, '2025-12-10', 1),
(458, 13, 17, 4, 5, '2025-12-10', 1),
(459, 13, 18, 4, 5, '2025-12-10', 1),
(460, 13, 19, 4, 1, '2025-12-10', 1),
(461, 13, 20, 4, 1, '2025-12-10', 1),
(462, 13, 21, 4, 5, '2025-12-10', 1),
(463, 1, 1, 2, 3, '2023-12-08', 1),
(464, 1, 2, 2, 3, '2023-12-08', 1),
(465, 1, 3, 2, 3, '2023-12-08', 1),
(466, 1, 4, 2, 3, '2023-12-08', 1),
(467, 1, 5, 2, 3, '2023-12-08', 1),
(468, 1, 6, 2, 3, '2023-12-08', 1),
(469, 1, 7, 2, 3, '2023-12-08', 1),
(470, 1, 8, 2, 3, '2023-12-08', 1),
(471, 1, 9, 2, 3, '2023-12-08', 1),
(472, 1, 10, 2, 3, '2023-12-08', 1),
(473, 1, 11, 2, 3, '2023-12-08', 1),
(474, 1, 12, 2, 3, '2023-12-08', 1),
(475, 1, 13, 2, 3, '2023-12-08', 1),
(476, 1, 14, 2, 3, '2023-12-08', 1),
(477, 1, 15, 2, 3, '2023-12-08', 1),
(478, 1, 16, 2, 3, '2023-12-08', 1),
(479, 1, 17, 2, 3, '2023-12-08', 1),
(480, 1, 18, 2, 3, '2023-12-08', 1),
(481, 1, 19, 2, 3, '2023-12-08', 1),
(482, 1, 20, 2, 3, '2023-12-08', 1),
(483, 1, 21, 2, 3, '2023-12-08', 1),
(484, 2, 1, 2, 4, '2023-12-08', 1),
(485, 2, 2, 2, 4, '2023-12-08', 1),
(486, 2, 3, 2, 4, '2023-12-08', 1),
(487, 2, 4, 2, 4, '2023-12-08', 1),
(488, 2, 5, 2, 4, '2023-12-08', 1),
(489, 2, 6, 2, 4, '2023-12-08', 1),
(490, 2, 7, 2, 4, '2023-12-08', 1),
(491, 2, 8, 2, 4, '2023-12-08', 1),
(492, 2, 9, 2, 4, '2023-12-08', 1),
(493, 2, 10, 2, 4, '2023-12-08', 1),
(494, 2, 11, 2, 4, '2023-12-08', 1),
(495, 2, 12, 2, 4, '2023-12-08', 1),
(496, 2, 13, 2, 4, '2023-12-08', 1),
(497, 2, 14, 2, 4, '2023-12-08', 1),
(498, 2, 15, 2, 4, '2023-12-08', 1),
(499, 2, 16, 2, 4, '2023-12-08', 1),
(500, 2, 17, 2, 4, '2023-12-08', 1),
(501, 2, 18, 2, 4, '2023-12-08', 1),
(502, 2, 19, 2, 4, '2023-12-08', 1),
(503, 2, 20, 2, 4, '2023-12-08', 1),
(504, 2, 21, 2, 4, '2023-12-08', 1),
(505, 3, 1, 2, 4, '2023-11-07', 1),
(506, 3, 2, 2, 3, '2023-11-07', 1),
(507, 3, 3, 2, 4, '2023-11-07', 1),
(508, 3, 4, 2, 5, '2023-11-07', 1),
(509, 3, 5, 2, 5, '2023-11-07', 1),
(510, 3, 6, 2, 5, '2023-11-07', 1),
(511, 3, 7, 2, 4, '2023-11-07', 1),
(512, 3, 8, 2, 5, '2023-11-07', 1),
(513, 3, 9, 2, 5, '2023-11-07', 1),
(514, 3, 10, 2, 4, '2023-11-07', 1),
(515, 3, 11, 2, 3, '2023-11-07', 1),
(516, 3, 12, 2, 5, '2023-11-07', 1),
(517, 3, 13, 2, 3, '2023-11-07', 1),
(518, 3, 14, 2, 3, '2023-11-07', 1),
(519, 3, 15, 2, 3, '2023-11-07', 1),
(520, 3, 16, 2, 4, '2023-11-07', 1),
(521, 3, 17, 2, 3, '2023-11-07', 1),
(522, 3, 18, 2, 4, '2023-11-07', 1),
(523, 3, 19, 2, 5, '2023-11-07', 1),
(524, 3, 20, 2, 5, '2023-11-07', 1),
(525, 3, 21, 2, 5, '2023-11-07', 1),
(526, 4, 1, 2, 1, '2023-11-06', 1),
(527, 4, 2, 2, 1, '2023-11-06', 1),
(528, 4, 3, 2, 1, '2023-11-06', 1),
(529, 4, 4, 2, 1, '2023-11-06', 1),
(530, 4, 5, 2, 1, '2023-11-06', 1),
(531, 4, 6, 2, 1, '2023-11-06', 1),
(532, 4, 7, 2, 1, '2023-11-06', 1),
(533, 4, 8, 2, 3, '2023-11-06', 1),
(534, 4, 9, 2, 4, '2023-11-06', 1),
(535, 4, 10, 2, 5, '2023-11-06', 1),
(536, 4, 11, 2, 5, '2023-11-06', 1),
(537, 4, 12, 2, 5, '2023-11-06', 1),
(538, 4, 13, 2, 5, '2023-11-06', 1),
(539, 4, 14, 2, 2, '2023-11-06', 1),
(540, 4, 15, 2, 3, '2023-11-06', 1),
(541, 4, 16, 2, 1, '2023-11-06', 1),
(542, 4, 17, 2, 1, '2023-11-06', 1),
(543, 4, 18, 2, 1, '2023-11-06', 1),
(544, 4, 19, 2, 5, '2023-11-06', 1),
(545, 4, 20, 2, 5, '2023-11-06', 1),
(546, 4, 21, 2, 5, '2023-11-06', 1),
(547, 1, 1, 1, 5, '2022-11-13', 1),
(548, 1, 2, 1, 5, '2022-11-13', 1),
(549, 1, 3, 1, 5, '2022-11-13', 1),
(550, 1, 4, 1, 5, '2022-11-13', 1),
(551, 1, 5, 1, 5, '2022-11-13', 1),
(552, 1, 6, 1, 5, '2022-11-13', 1),
(553, 1, 7, 1, 5, '2022-11-13', 1),
(554, 1, 8, 1, 1, '2022-11-13', 1),
(555, 1, 9, 1, 1, '2022-11-13', 1),
(556, 1, 10, 1, 1, '2022-11-13', 1),
(557, 1, 11, 1, 1, '2022-11-13', 1),
(558, 1, 12, 1, 1, '2022-11-13', 1),
(559, 1, 13, 1, 1, '2022-11-13', 1),
(560, 1, 14, 1, 5, '2022-11-13', 1),
(561, 1, 15, 1, 5, '2022-11-13', 1),
(562, 1, 16, 1, 5, '2022-11-13', 1),
(563, 1, 17, 1, 5, '2022-11-13', 1),
(564, 1, 18, 1, 5, '2022-11-13', 1),
(565, 1, 19, 1, 5, '2022-11-13', 1),
(566, 1, 20, 1, 5, '2022-11-13', 1),
(567, 1, 21, 1, 5, '2022-11-13', 1),
(568, 2, 1, 1, 5, '2022-11-13', 1),
(569, 2, 2, 1, 5, '2022-11-13', 1),
(570, 2, 3, 1, 5, '2022-11-13', 1),
(571, 2, 4, 1, 5, '2022-11-13', 1),
(572, 2, 5, 1, 5, '2022-11-13', 1),
(573, 2, 6, 1, 5, '2022-11-13', 1),
(574, 2, 7, 1, 5, '2022-11-13', 1),
(575, 2, 8, 1, 5, '2022-11-13', 1),
(576, 2, 9, 1, 5, '2022-11-13', 1),
(577, 2, 10, 1, 5, '2022-11-13', 1),
(578, 2, 11, 1, 5, '2022-11-13', 1),
(579, 2, 12, 1, 5, '2022-11-13', 1),
(580, 2, 13, 1, 5, '2022-11-13', 1),
(581, 2, 14, 1, 1, '2022-11-13', 1),
(582, 2, 15, 1, 1, '2022-11-13', 1),
(583, 2, 16, 1, 1, '2022-11-13', 1),
(584, 2, 17, 1, 1, '2022-11-13', 1),
(585, 2, 18, 1, 1, '2022-11-13', 1),
(586, 2, 19, 1, 5, '2022-11-13', 1),
(587, 2, 20, 1, 5, '2022-11-13', 1),
(588, 2, 21, 1, 5, '2022-11-13', 1);

-- --------------------------------------------------------

--
-- Table structure for table `kpi_section`
--

CREATE TABLE `kpi_section` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `weight_percentage` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kpi_section`
--

INSERT INTO `kpi_section` (`section_id`, `section_name`, `weight_percentage`) VALUES
(1, 'Core Competencies', 25.00),
(2, 'KPI Achievement', 75.00);

-- --------------------------------------------------------

--
-- Table structure for table `performance_summary`
--

CREATE TABLE `performance_summary` (
  `summary_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `section1_score` decimal(5,4) NOT NULL,
  `section2_score` decimal(5,4) NOT NULL,
  `final_score` decimal(5,4) NOT NULL,
  `grade_label` varchar(50) NOT NULL,
  `interpretation_id` int(11) DEFAULT NULL,
  `config_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `performance_summary`
--

INSERT INTO `performance_summary` (`summary_id`, `staff_id`, `period_id`, `section1_score`, `section2_score`, `final_score`, `grade_label`, `interpretation_id`, `config_id`) VALUES
(1, 1, 1, 1.2500, 2.7500, 4.0000, 'Good', 2, NULL),
(2, 1, 2, 0.7500, 2.2500, 3.0000, 'Satisfactory', 3, NULL),
(3, 1, 3, 0.7500, 2.6625, 3.4125, 'Satisfactory', 3, NULL),
(4, 1, 4, 1.0833, 2.7875, 3.8708, 'Satisfactory', 3, NULL),
(5, 2, 1, 1.2500, 2.9500, 4.2000, 'Good', 2, NULL),
(6, 2, 2, 1.0000, 3.0000, 4.0000, 'Good', 2, NULL),
(7, 2, 3, 1.0000, 2.6000, 3.6000, 'Satisfactory', 3, NULL),
(8, 2, 4, 0.5000, 2.2375, 2.7375, 'Satisfactory', 3, NULL),
(9, 3, 2, 1.2500, 3.1250, 4.3750, 'Satisfactory', 3, NULL),
(10, 3, 3, 0.5000, 2.6250, 3.1250, 'Satisfactory', 3, NULL),
(11, 3, 4, 0.5833, 2.3500, 2.9333, 'Poor', 4, NULL),
(12, 4, 2, 1.2500, 1.6000, 2.8500, 'Poor', 4, NULL),
(13, 4, 3, 1.2500, 3.7500, 5.0000, 'Excellent', 1, NULL),
(14, 4, 4, 0.9167, 2.1375, 3.0542, 'Satisfactory', 3, NULL),
(15, 5, 3, 0.4167, 2.0750, 2.4917, 'Poor', 4, NULL),
(16, 5, 4, 0.4167, 1.5875, 2.0042, 'Poor', 4, NULL),
(17, 6, 3, 0.6667, 2.3625, 3.0292, 'Satisfactory', 3, NULL),
(18, 6, 4, 0.5833, 2.3875, 2.9708, 'Poor', 4, NULL),
(19, 7, 3, 0.3333, 2.6125, 2.9458, 'Poor', 4, NULL),
(20, 7, 4, 0.9167, 2.2000, 3.1167, 'Satisfactory', 3, NULL),
(21, 8, 3, 0.8333, 1.7250, 2.5583, 'Poor', 4, NULL),
(22, 8, 4, 1.0000, 2.4000, 3.4000, 'Satisfactory', 3, NULL),
(23, 9, 3, 0.9167, 2.2625, 3.1792, 'Satisfactory', 3, NULL),
(24, 9, 4, 0.6667, 1.9125, 2.5792, 'Poor', 4, NULL),
(25, 10, 3, 0.2500, 1.9500, 2.2000, 'Poor', 4, NULL),
(26, 11, 3, 0.2500, 0.7500, 1.0000, 'Very Poor', 5, NULL),
(27, 12, 4, 0.5000, 3.0250, 3.5250, 'Satisfactory', 3, NULL),
(28, 13, 4, 0.5833, 2.9000, 3.4833, 'Satisfactory', 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `score_interpretation`
--

CREATE TABLE `score_interpretation` (
  `interpretation_id` int(11) NOT NULL,
  `min_score` decimal(4,2) NOT NULL,
  `max_score` decimal(4,2) NOT NULL,
  `grade_label` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `score_interpretation`
--

INSERT INTO `score_interpretation` (`interpretation_id`, `min_score`, `max_score`, `grade_label`) VALUES
(1, 5.00, 5.00, 'Excellent'),
(2, 4.00, 4.99, 'Good'),
(3, 3.00, 3.99, 'Satisfactory'),
(4, 2.00, 2.99, 'Poor'),
(5, 0.00, 1.99, 'Very Poor');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `staff_code` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `staff_code`, `full_name`, `role`, `status`) VALUES
(1, 'SA001', 'Ali', 'Sales Assistant', 'Active'),
(2, 'SA002', 'Sally', 'Senior Sales Associate', 'Active'),
(3, 'SA003', 'John', 'Sales Assistant', 'Active'),
(4, 'SA004', 'Lisa', 'Sales Assistant', 'Active'),
(5, 'SA005', 'Alex', 'Sales Assistant', 'Active'),
(6, 'SA006', 'Daniel', 'Sales Assistant', 'Active'),
(7, 'SA007', 'Aisyah', 'Sales Assistant', 'Active'),
(8, 'SA008', 'Kelvin', 'Sales Assistant', 'Active'),
(9, 'SA009', 'Farah', 'Sales Assistant', 'Active'),
(10, 'SA010', 'Marcus', 'Senior Sales Associate', 'Active'),
(11, 'SA011', 'Adam', 'Sales Assistant', 'Active'),
(12, 'SA012', 'Susan', 'Senior Sales Associate', 'Active'),
(13, 'SA013', 'Kamal', 'Sales Assistant', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `supervisor_feedback`
--

CREATE TABLE `supervisor_feedback` (
  `feedback_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL DEFAULT 1,
  `supervisor_name` varchar(100) DEFAULT NULL,
  `supervisor_comments` text DEFAULT NULL,
  `training_recommendations` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisor_feedback`
--

INSERT INTO `supervisor_feedback` (`feedback_id`, `staff_id`, `period_id`, `supervisor_id`, `supervisor_name`, `supervisor_comments`, `training_recommendations`) VALUES
(1, 1, 1, 1, 'Emily Tan', 'Demonstrated reliable task execution and met most assigned targets. Shows consistency in daily responsibilities.', 'Time management and productivity enhancement workshop'),
(2, 2, 1, 1, 'Emily Tan', 'Strong performance with good attention to detail and positive engagement with colleagues.', 'Advanced communication and leadership fundamentals'),
(3, 1, 2, 1, 'Emily Tan', 'Performance dipped slightly due to workload adjustments but remained cooperative and responsive to feedback.', 'Stress management and workload prioritisation training'),
(4, 3, 2, 1, 'Emily Tan', 'Consistently exceeded expectations and contributed proactively during team discussions.', 'Leadership development and mentoring skills'),
(5, 4, 2, 1, 'Emily Tan', 'Met basic requirements but required frequent supervision to complete tasks accurately.', 'Technical skills refresher and task planning workshop'),
(6, 2, 2, 1, 'Emily Tan', 'Maintained steady performance and supported team operations effectively.', 'Process improvement and efficiency training'),
(7, 1, 3, 1, 'Emily Tan', 'Showed improvement compared to previous year and handled responsibilities with better confidence.', 'Intermediate leadership and decision-making course'),
(8, 3, 3, 1, 'Emily Tan', 'Performance was satisfactory, though opportunities exist to improve initiative and follow-through.', 'Proactive problem-solving training'),
(9, 4, 3, 1, 'Emily Tan', 'Delivered strong results and handled complex tasks independently with minimal supervision.', 'Advanced technical upskilling programme'),
(10, 2, 3, 1, 'Emily Tan', 'Consistent contributor with dependable output across assigned duties.', 'Communication effectiveness and collaboration workshop'),
(11, 5, 3, 1, 'Emily Tan', 'Struggled to meet performance expectations and required frequent guidance.', 'Fundamental skills and performance coaching'),
(12, 6, 3, 1, 'Emily Tan', 'Maintained acceptable performance levels and completed tasks as assigned.', 'Time optimisation and task prioritisation training'),
(13, 7, 3, 1, 'Emily Tan', 'Demonstrated good learning attitude and adapted well to assigned responsibilities.', 'Professional development and confidence-building course'),
(14, 8, 3, 1, 'Emily Tan', 'Performance below average with challenges in meeting deadlines.', 'Core competency strengthening and mentoring support'),
(15, 9, 3, 1, 'Emily Tan', 'Performed steadily and showed willingness to take on additional tasks when required.', 'Advanced teamwork and collaboration skills'),
(16, 10, 3, 1, 'Emily Tan', 'Met minimum expectations but could improve consistency and accuracy.', 'Attention-to-detail and quality control training'),
(17, 11, 3, 1, 'Emily Tan', 'Performance was significantly below expectations and lacked consistency.', 'Performance improvement plan and foundational training'),
(18, 1, 4, 1, 'Emily Tan', 'Maintained stable performance and demonstrated reliability in assigned roles.', 'Continuous improvement and productivity tools training'),
(19, 3, 4, 1, 'Emily Tan', 'Performance remained consistent with good teamwork and task ownership.', 'Leadership pipeline and supervisory skills training'),
(20, 4, 4, 1, 'Emily Tan', 'Showed stable contribution with room for further skill enhancement.', 'Advanced process optimisation workshop'),
(21, 2, 4, 1, 'Emily Tan', 'Dependable performer with balanced contribution across responsibilities.', 'Cross-functional skills development'),
(22, 5, 4, 1, 'Emily Tan', 'Improvement noted compared to previous year but still requires supervision.', 'Skill reinforcement and coaching sessions'),
(23, 6, 4, 1, 'Emily Tan', 'Consistent and dependable performance throughout the evaluation period.', 'Professional growth and efficiency training'),
(24, 7, 4, 1, 'Emily Tan', 'Demonstrated steady performance and positive learning attitude.', 'Career development and advanced skill training'),
(25, 8, 4, 1, 'Emily Tan', 'Improved output and better adherence to deadlines compared to previous year.', 'Intermediate technical competency programme'),
(26, 9, 4, 1, 'Emily Tan', 'Performance was acceptable but lacked strong initiative.', 'Initiative-building and problem-solving workshop'),
(27, 12, 4, 1, 'Emily Tan', 'Delivered strong results with good organisational and coordination skills.', 'Leadership and strategic thinking training'),
(28, 13, 4, 1, 'Emily Tan', 'Performed very well with strong ownership and accountability.', 'Advanced leadership and succession planning programme');

-- --------------------------------------------------------

--
-- Table structure for table `supervisor_profile`
--

CREATE TABLE `supervisor_profile` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisor_profile`
--

INSERT INTO `supervisor_profile` (`id`, `name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Supervisor Account', 'supervisor@sakms.com', 'supervisor123', '2026-04-12 08:56:31');

-- --------------------------------------------------------

--
-- Table structure for table `weight_config`
--

CREATE TABLE `weight_config` (
  `config_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT 1,
  `s1_weights_json` text NOT NULL,
  `s2_weights_json` text NOT NULL,
  `saved_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `at_risk_notifications`
--
ALTER TABLE `at_risk_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_atrisk_staff` (`staff_id`),
  ADD KEY `fk_atrisk_period` (`period_id`);

--
-- Indexes for table `evaluation_period`
--
ALTER TABLE `evaluation_period`
  ADD PRIMARY KEY (`period_id`),
  ADD UNIQUE KEY `uq_year` (`year`);

--
-- Indexes for table `kpi_group`
--
ALTER TABLE `kpi_group`
  ADD PRIMARY KEY (`kpi_group_id`),
  ADD KEY `fk_kpigroup_section` (`section_id`);

--
-- Indexes for table `kpi_item`
--
ALTER TABLE `kpi_item`
  ADD PRIMARY KEY (`kpi_item_id`),
  ADD UNIQUE KEY `uq_kpi_code` (`kpi_code`),
  ADD KEY `fk_kpiitem_group` (`kpi_group_id`);

--
-- Indexes for table `kpi_score`
--
ALTER TABLE `kpi_score`
  ADD PRIMARY KEY (`score_id`),
  ADD UNIQUE KEY `uq_score` (`staff_id`,`kpi_item_id`,`period_id`),
  ADD KEY `fk_kpiscore_item` (`kpi_item_id`),
  ADD KEY `fk_kpiscore_period` (`period_id`),
  ADD KEY `fk_kpiscore_evaluator` (`evaluated_by`);

--
-- Indexes for table `kpi_section`
--
ALTER TABLE `kpi_section`
  ADD PRIMARY KEY (`section_id`);

--
-- Indexes for table `performance_summary`
--
ALTER TABLE `performance_summary`
  ADD PRIMARY KEY (`summary_id`),
  ADD UNIQUE KEY `uq_summary` (`staff_id`,`period_id`),
  ADD KEY `fk_summary_period` (`period_id`),
  ADD KEY `fk_summary_interp` (`interpretation_id`),
  ADD KEY `fk_summary_config` (`config_id`);

--
-- Indexes for table `score_interpretation`
--
ALTER TABLE `score_interpretation`
  ADD PRIMARY KEY (`interpretation_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `uq_staff_code` (`staff_code`);

--
-- Indexes for table `supervisor_feedback`
--
ALTER TABLE `supervisor_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD UNIQUE KEY `uq_feedback` (`staff_id`,`period_id`),
  ADD KEY `fk_feedback_staff` (`staff_id`),
  ADD KEY `fk_feedback_period` (`period_id`),
  ADD KEY `fk_feedback_supervisor` (`supervisor_id`);

--
-- Indexes for table `supervisor_profile`
--
ALTER TABLE `supervisor_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`);

--
-- Indexes for table `weight_config`
--
ALTER TABLE `weight_config`
  ADD PRIMARY KEY (`config_id`),
  ADD KEY `fk_weightcfg_supervisor` (`supervisor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `at_risk_notifications`
--
ALTER TABLE `at_risk_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluation_period`
--
ALTER TABLE `evaluation_period`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `kpi_group`
--
ALTER TABLE `kpi_group`
  MODIFY `kpi_group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `kpi_item`
--
ALTER TABLE `kpi_item`
  MODIFY `kpi_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `kpi_score`
--
ALTER TABLE `kpi_score`
  MODIFY `score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=589;

--
-- AUTO_INCREMENT for table `kpi_section`
--
ALTER TABLE `kpi_section`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `performance_summary`
--
ALTER TABLE `performance_summary`
  MODIFY `summary_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `score_interpretation`
--
ALTER TABLE `score_interpretation`
  MODIFY `interpretation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `supervisor_feedback`
--
ALTER TABLE `supervisor_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `supervisor_profile`
--
ALTER TABLE `supervisor_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `weight_config`
--
ALTER TABLE `weight_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `at_risk_notifications`
--
ALTER TABLE `at_risk_notifications`
  ADD CONSTRAINT `fk_atrisk_period` FOREIGN KEY (`period_id`) REFERENCES `evaluation_period` (`period_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_atrisk_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kpi_group`
--
ALTER TABLE `kpi_group`
  ADD CONSTRAINT `kpi_group_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `kpi_section` (`section_id`);

--
-- Constraints for table `kpi_item`
--
ALTER TABLE `kpi_item`
  ADD CONSTRAINT `kpi_item_ibfk_1` FOREIGN KEY (`kpi_group_id`) REFERENCES `kpi_group` (`kpi_group_id`);

--
-- Constraints for table `kpi_score`
--
ALTER TABLE `kpi_score`
  ADD CONSTRAINT `fk_kpiscore_evaluated_by` FOREIGN KEY (`evaluated_by`) REFERENCES `supervisor_profile` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `kpi_score_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`),
  ADD CONSTRAINT `kpi_score_ibfk_2` FOREIGN KEY (`kpi_item_id`) REFERENCES `kpi_item` (`kpi_item_id`),
  ADD CONSTRAINT `kpi_score_ibfk_3` FOREIGN KEY (`period_id`) REFERENCES `evaluation_period` (`period_id`);

--
-- Constraints for table `performance_summary`
--
ALTER TABLE `performance_summary`
  ADD CONSTRAINT `fk_summary_weight_config` FOREIGN KEY (`config_id`) REFERENCES `weight_config` (`config_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `performance_summary_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`),
  ADD CONSTRAINT `performance_summary_ibfk_2` FOREIGN KEY (`period_id`) REFERENCES `evaluation_period` (`period_id`),
  ADD CONSTRAINT `performance_summary_ibfk_3` FOREIGN KEY (`interpretation_id`) REFERENCES `score_interpretation` (`interpretation_id`);

--
-- Constraints for table `supervisor_feedback`
--
ALTER TABLE `supervisor_feedback`
  ADD CONSTRAINT `fk_feedback_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `supervisor_profile` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `supervisor_feedback_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`),
  ADD CONSTRAINT `supervisor_feedback_ibfk_2` FOREIGN KEY (`period_id`) REFERENCES `evaluation_period` (`period_id`);

--
-- Constraints for table `weight_config`
--
ALTER TABLE `weight_config`
  ADD CONSTRAINT `fk_weightcfg_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `supervisor_profile` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
