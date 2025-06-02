-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 29, 2024 at 07:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `it107_security_sql`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `id_no` varchar(30) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `middlename` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `sex` varchar(10) NOT NULL,
  `purok` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) NOT NULL,
  `municipality` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reenterpassword` varchar(255) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_no`, `firstname`, `middlename`, `lastname`, `suffix`, `sex`, `purok`, `barangay`, `municipality`, `province`, `country`, `zipcode`, `email`, `username`, `password`, `reenterpassword`, `birthdate`, `age`) VALUES
(65, '1111-1111', 'Benjamin', 'E', 'Abamonga', 'jr', 'male', 'Purok-5', 'Marcos', 'Magallanes', 'Agusan Del Norte', 'Philippines', '8604', 'kingbenjamintheancient@gmail.com', 'kingbenjamin', '$2y$10$61dGgiadHBxclz6PRvjeO..hN062xndwUvB./G4tKt3UyBwXrAdEO', '', '2000-01-04', 24),
(80, '1111-1114', 'Johan Rey Excel', 'B', 'Liebert', '', 'male', 'Purok-5', 'Marcos', 'Magallanes', 'Agusan Del Norte', 'Germany', '7670', 'johnreyexcel@gmail.com', 'johnrey.rementizo', '$2y$10$u7NRe3eB/NqK8l2sZTuOPOQKoH5FkaXFrl.DTSGSZTvBO9HbuQye.', '', '2023-10-24', 0),
(81, '1224-9974', 'John', '', 'Rebfxgjs', 'Sr.', 'female', 'Examples', 'Examples', 'Examples', 'Examples', 'Examples', '4500', 'johnrey@gmail.com', 'johnyuri', '$2y$10$ToBkUlfh1ve07uGhDXwCueUXNhov52cqWqDLnqf2mIPoTPxq2TuNy', '', '1989-02-07', 35);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
