CREATE DATABASE IF NOT EXISTS dormitory_db;
USE dormitory_db;
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2025 at 06:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dormitory_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `building_id` int(4) NOT NULL,
  `building_name` varchar(100) NOT NULL,
  `total_floors` int(4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`building_id`, `building_name`, `total_floors`, `created_at`) VALUES
(1, 'อาคารหอพักชาย 1', 5, '2025-03-23 10:38:12'),
(2, 'อาคารหอพักชาย 2', 5, '2025-03-23 10:38:12'),
(3, 'อาคารหอพักหญิง 1', 5, '2025-03-23 10:38:12'),
(4, 'อาคารหอพักหญิง 2', 5, '2025-03-23 10:38:12');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(4) NOT NULL,
  `request_id` int(4) DEFAULT NULL,
  `type_id` int(4) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_recipients`
--

CREATE TABLE `notification_recipients` (
  `recipient_id` int(4) NOT NULL,
  `notification_id` int(4) DEFAULT NULL,
  `user_id` int(4) DEFAULT NULL,
  `contact_id` int(4) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_time` timestamp NULL DEFAULT NULL,
  `created_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_types`
--

CREATE TABLE `notification_types` (
  `type_id` int(4) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `public_repair_contacts`
--

CREATE TABLE `public_repair_contacts` (
  `contact_id` int(4) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `email` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `repair_item_name` varchar(200) NOT NULL,
  `repair_description` text NOT NULL,
  `repair_location` varchar(200) NOT NULL,
  `repair_priority` enum('ต่ำ','ปานกลาง','สูง') DEFAULT 'ปานกลาง',
  `preferred_contact_time` varchar(100) DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `public_repair_contacts`
--

INSERT INTO `public_repair_contacts` (`contact_id`, `full_name`, `phone_number`, `email`, `address`, `repair_item_name`, `repair_description`, `repair_location`, `repair_priority`, `preferred_contact_time`, `additional_notes`, `created_at`) VALUES
(1, 'นภา สุขสันต์', '0812345704', 'napa@pbru.ac.th', NULL, '', '', '', 'ปานกลาง', NULL, NULL, '2025-03-23 10:38:12'),
(2, 'ธนวัฒน์ ใจดี', '0812345705', 'thanawat@pbru.ac.th', NULL, '', '', '', 'ปานกลาง', NULL, NULL, '2025-03-23 10:38:12'),
(3, 'กมลวรรณ รักเรียน', '0812345706', 'kamolwan@pbru.ac.th', NULL, '', '', '', 'ปานกลาง', NULL, NULL, '2025-03-23 10:38:12'),
(4, 'ศุภชัย มั่งมี', '0812345707', 'suppachai@pbru.ac.th', NULL, '', '', '', 'ปานกลาง', NULL, NULL, '2025-03-23 10:38:12'),
(5, 'รัตนา สุขใจ', '0812345708', 'rattana@pbru.ac.th', NULL, '', '', '', 'ปานกลาง', NULL, NULL, '2025-03-23 10:38:12'),
(6, 'สมชาย ร้านอาหาร', '0812345709', 'somchai.food@gmail.com', NULL, '', '', '', 'ปานกลาง', NULL, NULL, '2025-03-23 10:38:12'),
(7, 'สมหญิง ร้านเครื่องดื่ม', '0812345710', 'somying.drink@gmail.com', NULL, '', '', '', 'ปานกลาง', NULL, NULL, '2025-03-23 10:38:12'),
(8, 'วิชัย ร้านหนังสือ', '0812345711', 'wichai.book@gmail.com', NULL, '', '', '', 'ปานกลาง', NULL, NULL, '2025-03-23 10:38:12'),
(9, 'วิชุดา ร้านเครื่องเขียน', '0812345712', 'wichuda.stationery@gmail.com', NULL, '', '', '', 'ปานกลาง', NULL, NULL, '2025-03-23 10:38:12'),
(10, 'สมศรี ร้านถ่ายเอกสาร', '0812345713', 'somsri.copy@gmail.com', NULL, '', '', '', 'ปานกลาง', NULL, NULL, '2025-03-23 10:38:12');

-- --------------------------------------------------------

--
-- Table structure for table `repair_categories`
--

CREATE TABLE `repair_categories` (
  `category_id` int(4) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repair_categories`
--

INSERT INTO `repair_categories` (`category_id`, `category_name`, `description`, `created_time`) VALUES
(1, 'ระบบไฟฟ้า', 'อุปกรณ์และระบบไฟฟ้าต่างๆ', '2025-03-23 10:38:12'),
(2, 'ระบบประปา', 'อุปกรณ์และระบบน้ำประปาต่างๆ', '2025-03-23 10:38:12'),
(3, 'เฟอร์นิเจอร์', 'โต๊ะ เก้าอี้ ตู้ต่างๆ', '2025-03-23 10:38:12'),
(4, 'อาคารสถานที่', 'ส่วนประกอบของอาคาร', '2025-03-23 10:38:12'),
(5, 'อุปกรณ์อื่นๆ', 'อุปกรณ์อื่นๆ ที่ไม่เข้าข่ายหมวดหมู่ข้างต้น', '2025-03-23 10:38:12');

-- --------------------------------------------------------

--
-- Table structure for table `repair_history`
--

CREATE TABLE `repair_history` (
  `history_id` int(4) NOT NULL,
  `request_id` int(4) DEFAULT NULL,
  `admin_id` int(4) DEFAULT NULL,
  `action` enum('มอบหมายงาน','เริ่มดำเนินการ','เสร็จสิ้น','ยกเลิก') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repair_history`
--

INSERT INTO `repair_history` (`history_id`, `request_id`, `admin_id`, `action`, `notes`, `created_time`) VALUES
(1, 32, 1, 'มอบหมายงาน', 'รับเรื่องแจ้งซ่อม', '2025-03-23 09:06:45'),
(2, 33, 1, 'มอบหมายงาน', 'รับเรื่องแจ้งซ่อม', '2025-03-23 09:06:51'),
(3, 34, NULL, '', 'แจ้งซ่อมโดยนักศึกษา: สมชาย ใจดี', '2025-03-23 11:22:23'),
(4, 35, NULL, '', 'แจ้งซ่อมโดยนักศึกษา: สมชาย ใจดี', '2025-03-23 11:23:18');

-- --------------------------------------------------------

--
-- Table structure for table `repair_images`
--

CREATE TABLE `repair_images` (
  `image_id` int(4) NOT NULL,
  `request_id` int(4) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `image_description` text DEFAULT NULL,
  `created_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repair_images`
--

INSERT INTO `repair_images` (`image_id`, `request_id`, `image_path`, `image_description`, `created_time`) VALUES
(1, 33, 'uploads/repairs/repair_33_67e0238b954f8.png', NULL, '2025-03-23 09:06:51'),
(2, 35, 'uploads/repairs/repair_35_67e04386ba2dd.jpg', NULL, '2025-03-23 11:23:18');

-- --------------------------------------------------------

--
-- Table structure for table `repair_items`
--

CREATE TABLE `repair_items` (
  `item_id` int(4) NOT NULL,
  `category_id` int(4) DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `description_th` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repair_items`
--

INSERT INTO `repair_items` (`item_id`, `category_id`, `item_name`, `description_th`, `created_at`) VALUES
(1, 1, 'หลอดไฟ', 'หลอดไฟต่างๆ ในหอพัก', '2025-03-23 10:38:12'),
(2, 1, 'ปลั๊กไฟ', 'ปลั๊กไฟและเต้ารับต่างๆ', '2025-03-23 10:38:12'),
(3, 1, 'สวิตช์ไฟ', 'สวิตช์เปิด-ปิดไฟ', '2025-03-23 10:38:12'),
(4, 2, 'ก๊อกน้ำ', 'ก๊อกน้ำประปาต่างๆ', '2025-03-23 10:38:12'),
(5, 2, 'ท่อน้ำ', 'ท่อน้ำประปาและท่อน้ำทิ้ง', '2025-03-23 10:38:12'),
(6, 2, 'ชักโครก', 'ชักโครกและอุปกรณ์ในห้องน้ำ', '2025-03-23 10:38:12'),
(7, 3, 'เตียงนอน', 'เตียงนอนและที่นอน', '2025-03-23 10:38:12'),
(8, 3, 'ตู้เสื้อผ้า', 'ตู้เก็บเสื้อผ้า', '2025-03-23 10:38:12'),
(9, 3, 'โต๊ะเขียนหนังสือ', 'โต๊ะสำหรับเขียนหนังสือ', '2025-03-23 10:38:12'),
(10, 4, 'ประตู', 'ประตูห้องพักและประตูต่างๆ', '2025-03-23 10:38:12'),
(11, 4, 'หน้าต่าง', 'หน้าต่างและบานเลื่อน', '2025-03-23 10:38:12'),
(12, 4, 'ผนัง', 'ผนังและพื้น', '2025-03-23 10:38:12'),
(13, 5, 'พัดลม', 'พัดลมติดผนังและพัดลมตั้งพื้น', '2025-03-23 10:38:12'),
(14, 5, 'แอร์คอนดิชันเนอร์', 'เครื่องปรับอากาศ', '2025-03-23 10:38:12'),
(15, 5, 'ตู้เย็น', 'ตู้เย็นในห้องพัก', '2025-03-23 10:38:12');

-- --------------------------------------------------------

--
-- Table structure for table `repair_locations`
--

CREATE TABLE `repair_locations` (
  `location_id` int(4) NOT NULL,
  `location_name_th` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `building_id` int(4) DEFAULT NULL,
  `floor_number` int(4) DEFAULT NULL,
  `is_public_area` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repair_locations`
--

INSERT INTO `repair_locations` (`location_id`, `location_name_th`, `description`, `building_id`, `floor_number`, `is_public_area`, `created_at`) VALUES
(1, 'ห้องน้ำชาย ชั้น 1', 'ห้องน้ำชายอาคารหอพักชาย 1 ชั้น 1', 1, 1, 1, '2025-03-23 10:38:12'),
(2, 'ห้องน้ำชาย ชั้น 2', 'ห้องน้ำชายอาคารหอพักชาย 1 ชั้น 2', 1, 2, 1, '2025-03-23 10:38:12'),
(3, 'ห้องน้ำชาย ชั้น 3', 'ห้องน้ำชายอาคารหอพักชาย 1 ชั้น 3', 1, 3, 1, '2025-03-23 10:38:12'),
(4, 'ห้องน้ำหญิง ชั้น 1', 'ห้องน้ำหญิงอาคารหอพักหญิง 1 ชั้น 1', 3, 1, 1, '2025-03-23 10:38:12'),
(5, 'ห้องน้ำหญิง ชั้น 2', 'ห้องน้ำหญิงอาคารหอพักหญิง 1 ชั้น 2', 3, 2, 1, '2025-03-23 10:38:12'),
(6, 'ลานซักล้าง', 'ลานซักล้างกลางหอพัก', 1, 1, 1, '2025-03-23 10:38:12'),
(7, 'ลานจอดรถ', 'ลานจอดรถหอพัก', 1, 1, 1, '2025-03-23 10:38:12'),
(8, 'ห้องโถง', 'ห้องโถงกลางหอพัก', 1, 1, 1, '2025-03-23 10:38:12');

-- --------------------------------------------------------

--
-- Table structure for table `repair_requests`
--

CREATE TABLE `repair_requests` (
  `request_id` int(4) NOT NULL,
  `user_id` int(4) DEFAULT NULL,
  `contact_id` int(4) DEFAULT NULL,
  `room_id` int(4) DEFAULT NULL,
  `location_id` int(4) DEFAULT NULL,
  `item_id` int(4) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `status` enum('รอดำเนินการ','กำลังดำเนินการ','เสร็จสิ้น','ยกเลิก') DEFAULT 'รอดำเนินการ',
  `created_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repair_requests`
--

INSERT INTO `repair_requests` (`request_id`, `user_id`, `contact_id`, `room_id`, `location_id`, `item_id`, `title`, `description`, `status`, `created_time`, `updated_time`) VALUES
(1, 2, NULL, 1, 1, 1, 'หลอดไฟห้องน้ำเสีย', 'หลอดไฟในห้องน้ำชายชั้น 1 ไม่ติด', 'รอดำเนินการ', '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(2, 3, NULL, 1, 2, 4, 'ก๊อกน้ำรั่ว', 'ก๊อกน้ำในห้องน้ำชายชั้น 2 รั่ว', 'กำลังดำเนินการ', '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(3, 4, NULL, 1, 3, 7, 'เตียงนอนหัก', 'เตียงนอนหักที่ขา', 'เสร็จสิ้น', '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(4, 5, NULL, 1, 4, 10, 'ประตูห้องพักล็อคไม่ได้', 'ประตูห้องพักล็อคไม่ได้', 'รอดำเนินการ', '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(5, 6, NULL, 2, 5, 13, 'พัดลมไม่ทำงาน', 'พัดลมติดผนังไม่ทำงาน', 'กำลังดำเนินการ', '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(6, 2, NULL, 1, 1, 1, 'หลอดไฟห้องน้ำเสีย', 'หลอดไฟในห้องน้ำชายชั้น 1 ไม่ติด', 'รอดำเนินการ', '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(7, 3, NULL, 1, 2, 4, 'ก๊อกน้ำรั่ว', 'ก๊อกน้ำในห้องน้ำชายชั้น 2 รั่ว', 'กำลังดำเนินการ', '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(8, 4, NULL, 1, 3, 7, 'เตียงนอนหัก', 'เตียงนอนหักที่ขา', 'เสร็จสิ้น', '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(9, 5, NULL, 1, 4, 10, 'ประตูห้องพักล็อคไม่ได้', 'ประตูห้องพักล็อคไม่ได้', 'รอดำเนินการ', '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(10, 6, NULL, 2, 5, 13, 'พัดลมไม่ทำงาน', 'พัดลมติดผนังไม่ทำงาน', 'กำลังดำเนินการ', '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(21, NULL, 1, NULL, 6, 2, 'ปลั๊กไฟลานซักล้างเสีย', 'ปลั๊กไฟในลานซักล้างเสีย ใช้งานไม่ได้', 'รอดำเนินการ', '2025-03-23 10:40:03', '2025-03-23 10:40:03'),
(22, NULL, 2, NULL, 6, 4, 'ก๊อกน้ำลานซักล้างรั่ว', 'ก๊อกน้ำในลานซักล้างรั่ว', 'กำลังดำเนินการ', '2025-03-23 10:40:03', '2025-03-23 10:40:03'),
(23, NULL, 3, NULL, 6, 5, 'ท่อน้ำลานซักล้างตัน', 'ท่อน้ำในลานซักล้างตัน', 'รอดำเนินการ', '2025-03-23 10:40:03', '2025-03-23 10:40:03'),
(24, NULL, 4, NULL, 7, 12, 'พื้นลานจอดรถแตกร้าว', 'พื้นลานจอดรถแตกร้าว ต้องการซ่อม', 'กำลังดำเนินการ', '2025-03-23 10:40:03', '2025-03-23 10:40:03'),
(25, NULL, 5, NULL, 7, 1, 'หลอดไฟลานจอดรถเสีย', 'หลอดไฟในลานจอดรถเสีย', 'รอดำเนินการ', '2025-03-23 10:40:03', '2025-03-23 10:40:03'),
(26, NULL, 6, NULL, 8, 1, 'หลอดไฟห้องโถงเสีย', 'หลอดไฟในห้องโถงเสีย', 'กำลังดำเนินการ', '2025-03-23 10:40:03', '2025-03-23 10:40:03'),
(27, NULL, 7, NULL, 8, 3, 'สวิตช์ไฟห้องโถงเสีย', 'สวิตช์ไฟในห้องโถงเสีย', 'รอดำเนินการ', '2025-03-23 10:40:03', '2025-03-23 10:40:03'),
(28, NULL, 8, NULL, 8, 12, 'ผนังห้องโถงแตกร้าว', 'ผนังห้องโถงแตกร้าว ต้องการซ่อม', 'กำลังดำเนินการ', '2025-03-23 10:40:03', '2025-03-23 10:40:03'),
(29, NULL, 9, NULL, 8, 10, 'ประตูห้องโถงล็อคไม่ได้', 'ประตูห้องโถงล็อคไม่ได้', 'เสร็จสิ้น', '2025-03-23 10:40:03', '2025-03-23 10:40:03'),
(30, NULL, 10, NULL, 8, 11, 'หน้าต่างห้องโถงปิดไม่สนิท', 'หน้าต่างห้องโถงปิดไม่สนิท มีลมรั่ว', 'รอดำเนินการ', '2025-03-23 10:40:03', '2025-03-23 10:40:03'),
(32, 1, NULL, NULL, 8, NULL, 'ไฟดับ', 'จำเป็นต้องใช้งานไฟฟ้าเร่งด่วย', 'รอดำเนินการ', '2025-03-23 09:06:45', '2025-03-23 15:06:45'),
(33, 1, NULL, NULL, 8, NULL, 'ไฟดับ', 'จำเป็นต้องใช้งานไฟฟ้าเร่งด่วย', 'รอดำเนินการ', '2025-03-23 09:06:51', '2025-03-23 15:06:51'),
(34, 2, NULL, 1, 7, NULL, 'ไฟดับ', 'ไฟดับ', 'รอดำเนินการ', '2025-03-23 11:22:23', '2025-03-23 17:22:23'),
(35, 2, NULL, 1, 1, NULL, 'ไฟดับ', 'มีไฟกระดับเหมือนจะดับ', 'รอดำเนินการ', '2025-03-23 11:23:18', '2025-03-23 17:23:18');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(4) NOT NULL,
  `building_id` int(4) DEFAULT NULL,
  `room_number` varchar(10) NOT NULL,
  `floor_number` int(4) NOT NULL,
  `max_capacity` int(4) NOT NULL DEFAULT 4,
  `current_occupancy` int(4) DEFAULT 0,
  `created_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `building_id`, `room_number`, `floor_number`, `max_capacity`, `current_occupancy`, `created_time`) VALUES
(1, 1, '101', 1, 4, 4, '2025-03-23 10:38:12'),
(2, 1, '102', 1, 4, 4, '2025-03-23 10:38:12'),
(3, 1, '103', 1, 4, 4, '2025-03-23 10:38:12'),
(4, 1, '104', 1, 4, 4, '2025-03-23 10:38:12'),
(5, 1, '105', 1, 4, 4, '2025-03-23 10:38:12'),
(6, 1, '201', 2, 4, 4, '2025-03-23 10:38:12'),
(7, 1, '202', 2, 4, 4, '2025-03-23 10:38:12'),
(8, 1, '203', 2, 4, 4, '2025-03-23 10:38:12'),
(9, 1, '204', 2, 4, 4, '2025-03-23 10:38:12'),
(10, 1, '205', 2, 4, 4, '2025-03-23 10:38:12'),
(11, 1, '301', 3, 4, 4, '2025-03-23 10:38:12'),
(12, 1, '302', 3, 4, 4, '2025-03-23 10:38:12'),
(13, 1, '303', 3, 4, 4, '2025-03-23 10:38:12'),
(14, 1, '304', 3, 4, 4, '2025-03-23 10:38:12'),
(15, 1, '305', 3, 4, 4, '2025-03-23 10:38:12'),
(16, 2, '101', 1, 4, 4, '2025-03-23 10:38:12'),
(17, 2, '102', 1, 4, 4, '2025-03-23 10:38:12'),
(18, 2, '103', 1, 4, 4, '2025-03-23 10:38:12'),
(19, 2, '104', 1, 4, 4, '2025-03-23 10:38:12'),
(20, 2, '105', 1, 4, 4, '2025-03-23 10:38:12'),
(21, 2, '201', 2, 4, 4, '2025-03-23 10:38:12'),
(22, 2, '202', 2, 4, 4, '2025-03-23 10:38:12'),
(23, 2, '203', 2, 4, 4, '2025-03-23 10:38:12'),
(24, 2, '204', 2, 4, 4, '2025-03-23 10:38:12'),
(25, 2, '205', 2, 4, 4, '2025-03-23 10:38:12'),
(26, 3, '101', 1, 4, 4, '2025-03-23 10:38:12'),
(27, 3, '102', 1, 4, 4, '2025-03-23 10:38:12'),
(28, 3, '103', 1, 4, 4, '2025-03-23 10:38:12'),
(29, 3, '104', 1, 4, 4, '2025-03-23 10:38:12'),
(30, 3, '105', 1, 4, 4, '2025-03-23 10:38:12'),
(31, 3, '201', 2, 4, 4, '2025-03-23 10:38:12'),
(32, 3, '202', 2, 4, 4, '2025-03-23 10:38:12'),
(33, 3, '203', 2, 4, 4, '2025-03-23 10:38:12'),
(34, 3, '204', 2, 4, 4, '2025-03-23 10:38:12'),
(35, 3, '205', 2, 4, 4, '2025-03-23 10:38:12'),
(36, 4, '101', 1, 4, 4, '2025-03-23 10:38:12'),
(37, 4, '102', 1, 4, 4, '2025-03-23 10:38:12'),
(38, 4, '103', 1, 4, 4, '2025-03-23 10:38:12'),
(39, 4, '104', 1, 4, 4, '2025-03-23 10:38:12'),
(40, 4, '105', 1, 4, 4, '2025-03-23 10:38:12'),
(41, 4, '201', 2, 4, 4, '2025-03-23 10:38:12'),
(42, 4, '202', 2, 4, 4, '2025-03-23 10:38:12'),
(43, 4, '203', 2, 4, 4, '2025-03-23 10:38:12'),
(44, 4, '204', 2, 4, 4, '2025-03-23 10:38:12'),
(45, 4, '205', 2, 4, 4, '2025-03-23 10:38:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(4) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(30) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `role` enum('ผู้ดูแลระบบ','นักศึกษา') NOT NULL,
  `room_id` int(4) DEFAULT NULL,
  `created_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `phone_number`, `role`, `room_id`, `created_time`, `updated_time`) VALUES
(1, 'admin', '123456', 'admin@pbru.ac.th', 'ผู้ดูแลระบบ', '0812345678', 'ผู้ดูแลระบบ', NULL, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(2, '674244101', '674244101', '674244101@pbru.ac.th', 'สมชาย ใจดี', '0812345679', 'นักศึกษา', 1, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(3, '674244102', '674244102', '674244102@pbru.ac.th', 'สมหญิง รักเรียน', '0812345680', 'นักศึกษา', 1, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(4, '674244103', '674244103', '674244103@pbru.ac.th', 'วิชัย มั่งมี', '0812345681', 'นักศึกษา', 1, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(5, '674244104', '674244104', '674244104@pbru.ac.th', 'วิชุดา สุขใจ', '0812345682', 'นักศึกษา', 1, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(6, '674244105', '674244105', '674244105@pbru.ac.th', 'สมศรี เก่งกาจ', '0812345683', 'นักศึกษา', 2, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(7, '674244106', '674244106', '674244106@pbru.ac.th', 'วิชัย ใจกล้า', '0812345684', 'นักศึกษา', 2, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(8, '674244107', '674244107', '674244107@pbru.ac.th', 'สมพร รักดี', '0812345685', 'นักศึกษา', 2, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(9, '674244108', '674244108', '674244108@pbru.ac.th', 'วิชุดา สุขสันต์', '0812345686', 'นักศึกษา', 2, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(10, '674244109', '674244109', '674244109@pbru.ac.th', 'สมชาย เก่งกาจ', '0812345687', 'นักศึกษา', 3, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(11, '674244110', '674244110', '674244110@pbru.ac.th', 'วิชัย ใจดี', '0812345688', 'นักศึกษา', 3, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(12, '674244111', '674244111', '674244111@pbru.ac.th', 'สมหญิง รักดี', '0812345689', 'นักศึกษา', 3, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(13, '674244112', '674244112', '674244112@pbru.ac.th', 'วิชุดา สุขใจ', '0812345690', 'นักศึกษา', 3, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(14, '674244113', '674244113', '674244113@pbru.ac.th', 'สมศรี เก่งกาจ', '0812345691', 'นักศึกษา', 4, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(15, '674244114', '674244114', '674244114@pbru.ac.th', 'วิชัย ใจกล้า', '0812345692', 'นักศึกษา', 4, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(16, '674244115', '674244115', '674244115@pbru.ac.th', 'สมพร รักดี', '0812345693', 'นักศึกษา', 4, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(17, '674244116', '674244116', '674244116@pbru.ac.th', 'วิชุดา สุขสันต์', '0812345694', 'นักศึกษา', 4, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(18, '674244117', '674244117', '674244117@pbru.ac.th', 'สมชาย เก่งกาจ', '0812345695', 'นักศึกษา', 5, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(19, '674244118', '674244118', '674244118@pbru.ac.th', 'วิชัย ใจดี', '0812345696', 'นักศึกษา', 5, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(20, '674244119', '674244119', '674244119@pbru.ac.th', 'สมหญิง รักดี', '0812345697', 'นักศึกษา', 5, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(21, '674244120', '674244120', '674244120@pbru.ac.th', 'วิชุดา สุขใจ', '0812345698', 'นักศึกษา', 5, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(22, '674244121', '674244121', '674244121@pbru.ac.th', 'สมศรี เก่งกาจ', '0812345699', 'นักศึกษา', 6, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(23, '674244122', '674244122', '674244122@pbru.ac.th', 'วิชัย ใจกล้า', '0812345700', 'นักศึกษา', 6, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(24, '674244123', '674244123', '674244123@pbru.ac.th', 'สมพร รักดี', '0812345701', 'นักศึกษา', 6, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(25, '674244124', '674244124', '674244124@pbru.ac.th', 'วิชุดา สุขสันต์', '0812345702', 'นักศึกษา', 6, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(26, '674244125', '674244125', '674244125@pbru.ac.th', 'สมชาย เก่งกาจ', '0812345703', 'นักศึกษา', 7, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(27, '674244126', '674244126', '674244126@pbru.ac.th', 'วิชัย ใจดี', '0812345704', 'นักศึกษา', 7, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(28, '674244127', '674244127', '674244127@pbru.ac.th', 'สมหญิง รักดี', '0812345705', 'นักศึกษา', 7, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(29, '674244128', '674244128', '674244128@pbru.ac.th', 'วิชุดา สุขใจ', '0812345706', 'นักศึกษา', 7, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(30, '674244129', '674244129', '674244129@pbru.ac.th', 'สมศรี เก่งกาจ', '0812345707', 'นักศึกษา', 8, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(31, '674244130', '674244130', '674244130@pbru.ac.th', 'วิชัย ใจกล้า', '0812345708', 'นักศึกษา', 8, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(32, '674244131', '674244131', '674244131@pbru.ac.th', 'สมพร รักดี', '0812345709', 'นักศึกษา', 8, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(33, '674244132', '674244132', '674244132@pbru.ac.th', 'วิชุดา สุขสันต์', '0812345710', 'นักศึกษา', 8, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(34, '674244133', '674244133', '674244133@pbru.ac.th', 'สมชาย เก่งกาจ', '0812345711', 'นักศึกษา', 9, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(35, '674244134', '674244134', '674244134@pbru.ac.th', 'วิชัย ใจดี', '0812345712', 'นักศึกษา', 9, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(36, '674244135', '674244135', '674244135@pbru.ac.th', 'สมหญิง รักดี', '0812345713', 'นักศึกษา', 9, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(37, '674244136', '674244136', '674244136@pbru.ac.th', 'วิชุดา สุขใจ', '0812345714', 'นักศึกษา', 9, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(38, '674244137', '674244137', '674244137@pbru.ac.th', 'สมศรี เก่งกาจ', '0812345715', 'นักศึกษา', 10, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(39, '674244138', '674244138', '674244138@pbru.ac.th', 'วิชัย ใจกล้า', '0812345716', 'นักศึกษา', 10, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(40, '674244139', '674244139', '674244139@pbru.ac.th', 'สมพร รักดี', '0812345717', 'นักศึกษา', 10, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(41, '674244140', '674244140', '674244140@pbru.ac.th', 'วิชุดา สุขสันต์', '0812345718', 'นักศึกษา', 10, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(42, '674244141', '674244141', '674244141@pbru.ac.th', 'สมชาย เก่งกาจ', '0812345719', 'นักศึกษา', 11, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(43, '674244142', '674244142', '674244142@pbru.ac.th', 'วิชัย ใจดี', '0812345720', 'นักศึกษา', 11, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(44, '674244143', '674244143', '674244143@pbru.ac.th', 'สมหญิง รักดี', '0812345721', 'นักศึกษา', 11, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(45, '674244144', '674244144', '674244144@pbru.ac.th', 'วิชุดา สุขใจ', '0812345722', 'นักศึกษา', 11, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(46, '674244145', '674244145', '674244145@pbru.ac.th', 'สมศรี เก่งกาจ', '0812345723', 'นักศึกษา', 12, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(47, '674244146', '674244146', '674244146@pbru.ac.th', 'วิชัย ใจกล้า', '0812345724', 'นักศึกษา', 12, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(48, '674244147', '674244147', '674244147@pbru.ac.th', 'สมพร รักดี', '0812345725', 'นักศึกษา', 12, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(49, '674244148', '674244148', '674244148@pbru.ac.th', 'วิชุดา สุขสันต์', '0812345726', 'นักศึกษา', 12, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(50, '674244149', '674244149', '674244149@pbru.ac.th', 'สมชาย เก่งกาจ', '0812345727', 'นักศึกษา', 13, '2025-03-23 10:38:12', '2025-03-23 10:38:12'),
(51, '674244150', '674244150', '674244150@pbru.ac.th', 'วิชัย ใจดี', '0812345728', 'นักศึกษา', 13, '2025-03-23 10:38:12', '2025-03-23 10:38:12');

-- --------------------------------------------------------

--
-- Table structure for table `utility_bills`
--

CREATE TABLE `utility_bills` (
  `bill_id` int(4) NOT NULL,
  `room_id` int(4) DEFAULT NULL,
  `bill_type` enum('น้ำ','ไฟฟ้า') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reading_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('รอดำเนินการ','ชำระแล้ว','เลยกำหนด') DEFAULT 'รอดำเนินการ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utility_bills`
--

INSERT INTO `utility_bills` (`bill_id`, `room_id`, `bill_type`, `amount`, `reading_time`, `due_date`, `status`, `created_at`) VALUES
(1, 1, 'น้ำ', 160.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'ชำระแล้ว', '2025-03-23 10:40:15'),
(2, 1, 'ไฟฟ้า', 480.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'ชำระแล้ว', '2025-03-23 10:40:15'),
(3, 2, 'น้ำ', 140.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'รอดำเนินการ', '2025-03-23 10:40:15'),
(4, 2, 'ไฟฟ้า', 420.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'รอดำเนินการ', '2025-03-23 10:40:15'),
(5, 3, 'น้ำ', 170.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'ชำระแล้ว', '2025-03-23 10:40:15'),
(6, 3, 'ไฟฟ้า', 510.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'ชำระแล้ว', '2025-03-23 10:40:15'),
(7, 4, 'น้ำ', 130.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'รอดำเนินการ', '2025-03-23 10:40:15'),
(8, 4, 'ไฟฟ้า', 390.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'รอดำเนินการ', '2025-03-23 10:40:15'),
(9, 5, 'น้ำ', 190.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'ชำระแล้ว', '2025-03-23 10:40:15'),
(10, 5, 'ไฟฟ้า', 570.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'ชำระแล้ว', '2025-03-23 10:40:15'),
(11, 6, 'น้ำ', 150.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'รอดำเนินการ', '2025-03-23 10:40:15'),
(12, 6, 'ไฟฟ้า', 450.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'รอดำเนินการ', '2025-03-23 10:40:15'),
(13, 7, 'น้ำ', 180.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'ชำระแล้ว', '2025-03-23 10:40:15'),
(14, 7, 'ไฟฟ้า', 540.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'ชำระแล้ว', '2025-03-23 10:40:15'),
(15, 8, 'น้ำ', 140.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'รอดำเนินการ', '2025-03-23 10:40:15'),
(16, 8, 'ไฟฟ้า', 420.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'รอดำเนินการ', '2025-03-23 10:40:15'),
(17, 9, 'น้ำ', 160.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'ชำระแล้ว', '2025-03-23 10:40:15'),
(18, 9, 'ไฟฟ้า', 480.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'ชำระแล้ว', '2025-03-23 10:40:15'),
(19, 10, 'น้ำ', 170.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'รอดำเนินการ', '2025-03-23 10:40:15'),
(20, 10, 'ไฟฟ้า', 510.00, '2024-02-29 17:00:00', '2024-03-14 17:00:00', 'รอดำเนินการ', '2025-03-23 10:40:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`building_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `idx_notification_read` (`is_read`),
  ADD KEY `idx_notification_type` (`type_id`);

--
-- Indexes for table `notification_recipients`
--
ALTER TABLE `notification_recipients`
  ADD PRIMARY KEY (`recipient_id`),
  ADD KEY `notification_id` (`notification_id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `idx_notification_recipient_read` (`is_read`),
  ADD KEY `idx_notification_recipient_user` (`user_id`);

--
-- Indexes for table `notification_types`
--
ALTER TABLE `notification_types`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `public_repair_contacts`
--
ALTER TABLE `public_repair_contacts`
  ADD PRIMARY KEY (`contact_id`),
  ADD KEY `idx_repair_priority` (`repair_priority`);

--
-- Indexes for table `repair_categories`
--
ALTER TABLE `repair_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `repair_history`
--
ALTER TABLE `repair_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `repair_images`
--
ALTER TABLE `repair_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `repair_items`
--
ALTER TABLE `repair_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `repair_locations`
--
ALTER TABLE `repair_locations`
  ADD PRIMARY KEY (`location_id`),
  ADD KEY `building_id` (`building_id`);

--
-- Indexes for table `repair_requests`
--
ALTER TABLE `repair_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `idx_repair_status` (`status`),
  ADD KEY `idx_repair_location` (`location_id`),
  ADD KEY `idx_repair_item` (`item_id`),
  ADD KEY `idx_repair_contact` (`contact_id`),
  ADD KEY `idx_repair_user` (`user_id`),
  ADD KEY `idx_repair_created_time` (`created_time`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `unique_room` (`building_id`,`room_number`),
  ADD KEY `idx_room_building` (`building_id`),
  ADD KEY `idx_room_floor` (`floor_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `idx_user_role` (`role`);

--
-- Indexes for table `utility_bills`
--
ALTER TABLE `utility_bills`
  ADD PRIMARY KEY (`bill_id`),
  ADD KEY `idx_bill_status` (`status`),
  ADD KEY `idx_bill_room` (`room_id`),
  ADD KEY `idx_bill_due_date` (`due_date`),
  ADD KEY `idx_utility_bill_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `building_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(4) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_recipients`
--
ALTER TABLE `notification_recipients`
  MODIFY `recipient_id` int(4) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_types`
--
ALTER TABLE `notification_types`
  MODIFY `type_id` int(4) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `public_repair_contacts`
--
ALTER TABLE `public_repair_contacts`
  MODIFY `contact_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `repair_categories`
--
ALTER TABLE `repair_categories`
  MODIFY `category_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `repair_history`
--
ALTER TABLE `repair_history`
  MODIFY `history_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `repair_images`
--
ALTER TABLE `repair_images`
  MODIFY `image_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `repair_items`
--
ALTER TABLE `repair_items`
  MODIFY `item_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `repair_locations`
--
ALTER TABLE `repair_locations`
  MODIFY `location_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `repair_requests`
--
ALTER TABLE `repair_requests`
  MODIFY `request_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `utility_bills`
--
ALTER TABLE `utility_bills`
  MODIFY `bill_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `repair_requests` (`request_id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `notification_types` (`type_id`);

--
-- Constraints for table `notification_recipients`
--
ALTER TABLE `notification_recipients`
  ADD CONSTRAINT `notification_recipients_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`),
  ADD CONSTRAINT `notification_recipients_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `notification_recipients_ibfk_3` FOREIGN KEY (`contact_id`) REFERENCES `public_repair_contacts` (`contact_id`);

--
-- Constraints for table `repair_history`
--
ALTER TABLE `repair_history`
  ADD CONSTRAINT `repair_history_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `repair_requests` (`request_id`),
  ADD CONSTRAINT `repair_history_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `repair_images`
--
ALTER TABLE `repair_images`
  ADD CONSTRAINT `repair_images_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `repair_requests` (`request_id`);

--
-- Constraints for table `repair_items`
--
ALTER TABLE `repair_items`
  ADD CONSTRAINT `repair_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `repair_categories` (`category_id`);

--
-- Constraints for table `repair_locations`
--
ALTER TABLE `repair_locations`
  ADD CONSTRAINT `repair_locations_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`building_id`);

--
-- Constraints for table `repair_requests`
--
ALTER TABLE `repair_requests`
  ADD CONSTRAINT `repair_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `repair_requests_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `public_repair_contacts` (`contact_id`),
  ADD CONSTRAINT `repair_requests_ibfk_3` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`),
  ADD CONSTRAINT `repair_requests_ibfk_4` FOREIGN KEY (`location_id`) REFERENCES `repair_locations` (`location_id`),
  ADD CONSTRAINT `repair_requests_ibfk_5` FOREIGN KEY (`item_id`) REFERENCES `repair_items` (`item_id`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`building_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);

--
-- Constraints for table `utility_bills`
--
ALTER TABLE `utility_bills`
  ADD CONSTRAINT `utility_bills_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
