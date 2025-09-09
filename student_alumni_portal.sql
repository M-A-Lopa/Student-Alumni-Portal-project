-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 09, 2025 at 03:59 PM
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
-- Database: `student_alumni_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `Admin ID` varchar(50) NOT NULL,
  `Rank` int(50) NOT NULL,
  `Contact No` int(11) NOT NULL,
  `Po ID` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`Admin ID`, `Rank`, `Contact No`, `Po ID`) VALUES
('Levi', 1, 1732511182, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `alumni profile`
--

CREATE TABLE `alumni profile` (
  `Alumni ID` varchar(50) NOT NULL,
  `Graduation Year` year(4) NOT NULL,
  `Degree` varchar(50) NOT NULL,
  `Bio` text NOT NULL,
  `Current Job` varchar(200) NOT NULL,
  `Linkedin Profile` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alumni profile`
--

INSERT INTO `alumni profile` (`Alumni ID`, `Graduation Year`, `Degree`, `Bio`, `Current Job`, `Linkedin Profile`) VALUES
('onebloodyscythe', '2024', 'CS', '', 'Software Enginner', '');

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `Comment ID` varchar(50) NOT NULL,
  `Date of Comment` date NOT NULL,
  `Comment` text NOT NULL,
  `Pos ID` varchar(50) NOT NULL,
  `Use ID` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email`
--

CREATE TABLE `email` (
  `US ID` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email`
--

INSERT INTO `email` (`US ID`, `Email`) VALUES
('evergarden', 'violet@gmail.com'),
('Levi', 'mehrin77@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `Event ID` varchar(50) NOT NULL,
  `Title` varchar(100) NOT NULL,
  `Description` text NOT NULL,
  `Date` date NOT NULL,
  `Approved By` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`Event ID`, `Title`, `Description`, `Date`, `Approved By`) VALUES
('post_68b4c05d3eee1', 'Project', 'Showcasing', '2025-09-30', 'Levi'),
('post_68b57f69a6ef8', '423', 'ddvfgb', '2025-09-30', 'Levi'),
('post_68b5830031a92', 'emb', 'dfghjkl', '2025-09-24', 'Levi'),
('post_68b5edbd4b4ec', 'Stationary', 'Buy themmmmmmmmmmmmmmmmm', '2025-09-10', 'Levi');

-- --------------------------------------------------------

--
-- Table structure for table `mentorship`
--

CREATE TABLE `mentorship` (
  `S ID` varchar(50) NOT NULL,
  `Al ID` varchar(50) NOT NULL,
  `Approved or Rejected` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentorship_requests`
--

CREATE TABLE `mentorship_requests` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `alumni_id` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mentorship_requests`
--

INSERT INTO `mentorship_requests` (`id`, `student_id`, `alumni_id`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 'evergarden', 'onebloodyscythe', 'i want you to be my mentor', 'approved', '2025-09-01 18:12:39', '2025-09-03 21:39:48');

-- --------------------------------------------------------

--
-- Table structure for table `post`
--

CREATE TABLE `post` (
  `Post ID` varchar(50) NOT NULL,
  `Content` text NOT NULL,
  `Date of Post` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Title` varchar(100) NOT NULL,
  `File URL` varchar(300) NOT NULL,
  `Ur ID` varchar(50) NOT NULL,
  `Type` varchar(20) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post`
--

INSERT INTO `post` (`Post ID`, `Content`, `Date of Post`, `Title`, `File URL`, `Ur ID`, `Type`, `status`) VALUES
('post_68b4a377c7bba', 'I am Lopa', '2025-08-31 18:00:00', 'Heloo', '', 'Levi', 'general', NULL),
('post_68b4a3840a134', 'I am Lopa', '2025-08-31 18:00:00', 'Heloo', '', 'Levi', 'general', NULL),
('post_68b4a8cf18af3', 'Village', '2025-08-31 18:00:00', 'Fair', '', 'Levi', 'event', NULL),
('post_68b4ae3e4bd47', 'DFH', '2025-08-31 18:00:00', '370', 'uploads/68b4ae3e4baa3_CSE370_Lab_DatabaseChallenge02.docx', 'Levi', 'general', NULL),
('post_68b4c05d3eee1', 'Showcasing', '2025-08-31 18:00:00', 'Project', '', 'Levi', 'event', NULL),
('post_68b57f69a6ef8', 'ddvfgb', '2025-08-31 18:00:00', '423', '', 'Levi', 'event', NULL),
('post_68b5830031a92', 'dfghjkl', '2025-08-31 18:00:00', 'emb', '', 'Levi', 'event', NULL),
('post_68b5857104fa5', 'sdf', '2025-08-31 18:00:00', 'hilo', '', 'Levi', 'general', NULL),
('post_68b585b3d6ae8', 'craft your world', '2025-08-31 18:00:00', 'mine', '', 'Levi', 'announcement', NULL),
('post_68b599588edb5', 'sdfghjkl', '2025-09-01 13:02:16', 'facebook', '', 'evergarden', 'general', 'pending'),
('post_68b59c975b30e', 'sdfghj', '2025-09-01 18:34:17', 'phone', '', 'evergarden', 'general', 'approved'),
('post_68b5a09697608', 'ertyjkl;', '2025-09-01 15:16:15', 'switch', '', 'evergarden', 'general', 'rejected'),
('post_68b5b5988aba3', 'asdfghjkl;kjhgfdsfghjk', '2025-09-01 15:10:26', 'father', '', 'evergarden', 'general', 'approved'),
('post_68b5edbd4b4ec', 'Buy themmmmmmmmmmmmmmmmm', '2025-09-01 19:02:21', 'Stationary', '', 'Levi', 'event', NULL),
('post_68b5f13509b2a', 'sdfghjkl;kkjhgfdsfghjkkjhcvbn', '2025-09-01 19:17:09', 'hinloooooooooooooooooo', '', 'evergarden', 'general', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `student profile`
--

CREATE TABLE `student profile` (
  `Enrollment Year` year(4) NOT NULL,
  `Department` varchar(50) NOT NULL,
  `Semester` varchar(10) NOT NULL,
  `Bio` text NOT NULL,
  `Student ID` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student profile`
--

INSERT INTO `student profile` (`Enrollment Year`, `Department`, `Semester`, `Bio`, `Student ID`) VALUES
('2022', 'CS', '9th', '', 'evergarden');

-- --------------------------------------------------------

--
-- Table structure for table `ticket`
--

CREATE TABLE `ticket` (
  `E ID` varchar(50) NOT NULL,
  `Serial No` varchar(50) NOT NULL,
  `Date of Event` date NOT NULL,
  `U ID` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket`
--

INSERT INTO `ticket` (`E ID`, `Serial No`, `Date of Event`, `U ID`) VALUES
('post_68b4c05d3eee1', 'TKT_post_68b4c05d3eee1_Levi_1756726290_1', '2025-09-30', 'Levi'),
('post_68b4c05d3eee1', 'TKT_post_68b4c05d3eee1_Levi_1756726290_2', '2025-09-30', 'Levi'),
('post_68b4c05d3eee1', 'TKT_post_68b4c05d3eee1_Levi_1756726290_3', '2025-09-30', 'Levi'),
('post_68b57f69a6ef8', 'TKT_post_68b57f69a6ef8_Levi_1756740077_1', '2025-09-30', 'Levi'),
('post_68b5830031a92', 'TKT_post_68b5830031a92_Levi_1756726264_1', '2025-09-24', 'Levi'),
('post_68b5830031a92', 'TKT_post_68b5830031a92_Levi_1756726264_2', '2025-09-24', 'Levi'),
('post_68b5830031a92', 'TKT_post_68b5830031a92_Levi_1756726264_3', '2025-09-24', 'Levi');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `First Name` varchar(50) NOT NULL,
  `Last Name` varchar(50) NOT NULL,
  `User ID` varchar(50) NOT NULL,
  `Date of Birth` date NOT NULL,
  `Password` varchar(50) NOT NULL,
  `Created at` date NOT NULL,
  `Updated at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`First Name`, `Last Name`, `User ID`, `Date of Birth`, `Password`, `Created at`, `Updated at`) VALUES
('Violet', 'Evergarden', 'evergarden', '2003-10-27', 'evergarden', '2025-09-01', '2025-09-01'),
('Mehrin Afroz', 'Lopa', 'Levi', '2025-09-01', 'Levi', '2025-09-01', '2025-09-01'),
('Hasibul', 'Alam', 'onebloodyscythe', '2001-06-06', 'onebloodt', '2025-09-02', '2025-09-02'),
('Mehrin', 'Afroz', 'Ryuu123', '2025-09-01', '123', '2025-09-01', '2025-09-01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`Admin ID`),
  ADD KEY `Po ID` (`Po ID`);

--
-- Indexes for table `alumni profile`
--
ALTER TABLE `alumni profile`
  ADD PRIMARY KEY (`Alumni ID`);

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`Comment ID`),
  ADD KEY `comment_ibfk_1` (`Use ID`),
  ADD KEY `comment_ibfk_2` (`Pos ID`);

--
-- Indexes for table `email`
--
ALTER TABLE `email`
  ADD PRIMARY KEY (`US ID`,`Email`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`Event ID`),
  ADD KEY `fk_event_admin` (`Approved By`);

--
-- Indexes for table `mentorship`
--
ALTER TABLE `mentorship`
  ADD PRIMARY KEY (`S ID`,`Al ID`),
  ADD KEY `Al ID` (`Al ID`);

--
-- Indexes for table `mentorship_requests`
--
ALTER TABLE `mentorship_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `alumni_id` (`alumni_id`);

--
-- Indexes for table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`Post ID`),
  ADD KEY `post_ibfk_1` (`Ur ID`);

--
-- Indexes for table `student profile`
--
ALTER TABLE `student profile`
  ADD PRIMARY KEY (`Student ID`);

--
-- Indexes for table `ticket`
--
ALTER TABLE `ticket`
  ADD PRIMARY KEY (`E ID`,`Serial No`),
  ADD KEY `fk_ticket_user` (`U ID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`User ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mentorship_requests`
--
ALTER TABLE `mentorship_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`Po ID`) REFERENCES `post` (`Post ID`),
  ADD CONSTRAINT `fk_admin_user` FOREIGN KEY (`Admin ID`) REFERENCES `user` (`User ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `alumni profile`
--
ALTER TABLE `alumni profile`
  ADD CONSTRAINT `fk_alumni_user` FOREIGN KEY (`Alumni ID`) REFERENCES `user` (`User ID`);

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`Use ID`) REFERENCES `user` (`User ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `comment_ibfk_2` FOREIGN KEY (`Pos ID`) REFERENCES `post` (`Post ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `email`
--
ALTER TABLE `email`
  ADD CONSTRAINT `fk_email_user` FOREIGN KEY (`US ID`) REFERENCES `user` (`User ID`);

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `fk_event_admin` FOREIGN KEY (`Approved By`) REFERENCES `admin` (`Admin ID`),
  ADD CONSTRAINT `fk_event_post` FOREIGN KEY (`Event ID`) REFERENCES `post` (`Post ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mentorship`
--
ALTER TABLE `mentorship`
  ADD CONSTRAINT `mentorship_ibfk_1` FOREIGN KEY (`Al ID`) REFERENCES `alumni profile` (`Alumni ID`),
  ADD CONSTRAINT `mentorship_ibfk_2` FOREIGN KEY (`S ID`) REFERENCES `student profile` (`Student ID`);

--
-- Constraints for table `mentorship_requests`
--
ALTER TABLE `mentorship_requests`
  ADD CONSTRAINT `mentorship_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `user` (`User ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentorship_requests_ibfk_2` FOREIGN KEY (`alumni_id`) REFERENCES `user` (`User ID`) ON DELETE CASCADE;

--
-- Constraints for table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `post_ibfk_1` FOREIGN KEY (`Ur ID`) REFERENCES `user` (`User ID`);

--
-- Constraints for table `student profile`
--
ALTER TABLE `student profile`
  ADD CONSTRAINT `fk_student_user` FOREIGN KEY (`Student ID`) REFERENCES `user` (`User ID`);

--
-- Constraints for table `ticket`
--
ALTER TABLE `ticket`
  ADD CONSTRAINT `fk_event_ticket` FOREIGN KEY (`E ID`) REFERENCES `event` (`Event ID`),
  ADD CONSTRAINT `fk_ticket_user` FOREIGN KEY (`U ID`) REFERENCES `user` (`User ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
