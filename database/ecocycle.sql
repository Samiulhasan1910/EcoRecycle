-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 02, 2026 at 09:23 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `project`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `name`, `email`, `password`) VALUES
(1, 'Admin User', 'admin@ecocycle.com', 'admin123');

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `delivery_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `delivery_type` varchar(20) NOT NULL DEFAULT 'delivery',
  `status` varchar(30) NOT NULL DEFAULT 'Pending',
  `assigned_delivery_man_id` int(11) DEFAULT NULL,
  `proposed_charge` decimal(10,2) DEFAULT NULL,
  `accepted_charge` decimal(10,2) DEFAULT NULL,
  `picked_up_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_man`
--

CREATE TABLE `delivery_man` (
  `delivery_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `location` varchar(255) NOT NULL,
  `verification_document` varchar(255) DEFAULT NULL,
  `verification_status` varchar(20) NOT NULL DEFAULT 'Pending',
  `status` varchar(20) NOT NULL DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_man`
--

INSERT INTO `delivery_man` (`delivery_id`, `name`, `email`, `password`, `phone`, `location`, `verification_document`, `verification_status`, `status`, `created_at`) VALUES
(1, 'b', 'b@gmail.com', '$2y$10$OGBmuS74wUj7/xvL9/tKM.rDOQSaGwQZLBO/nizN3.Lr1xcEXIxLS', '01700000000', 'Brahmanbaria', 'uploads/delivery_documents/delivery_1788334938_6a97d35aeab94.png', 'Pending', 'Available', '2026-09-02 07:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `item_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ngo_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `price` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `image` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL,
  `payment_method` varchar(20) DEFAULT NULL,
  `payment_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`item_id`, `user_id`, `ngo_id`, `title`, `category`, `type`, `price`, `location`, `image`, `status`, `payment_method`, `payment_number`) VALUES
(4, 8, NULL, 'study table', 'Furniture', 'Sell', '2000', 'keraniganj,dhaka', '1788187919_6a95950fb84f6.png', 'Sold', 'Bkash', '01811111122'),
(6, 8, NULL, 'dress', 'Clothing', 'Donate', '0', 'Dhanmondi,Dhaka', '1788287648_6a971aa057e15.png', 'Donated', '', ''),
(7, 9, NULL, 'table', 'Furniture', 'Sell', '2000', 'keraniganj,dhaka', '1788296431_6a973cef82bc9.png', 'Sold', 'Bkash', '01811111111'),
(8, 8, NULL, 'table', 'Furniture', 'Sell', '3000', 'keraniganj,dhaka', '1788300116_6a974b5468bda.png', 'Sold', 'Bkash', 'Bkash: 01819191919'),
(9, 9, NULL, 'sofa', 'Furniture', 'Sell', '15000', 'keraniganj,dhaka', '1788320785_6a979c116c961.png', 'Sold', 'Nagad', 'Nagad: 01719056342'),
(11, 9, NULL, 'fan', 'Electronics', 'Donate', '0', 'Dhanmondi,Dhaka', '1788350317_6a980f6db8268.png', 'Available', '', ''),
(12, 9, NULL, 'abc', 'Home Appliances', 'Sell', '200', 'wdwd', '1788356926_6a98293e60a3d.png', 'Available', 'Bkash', 'Bkash: 01819231919'),
(13, 13, NULL, 'jhud', 'Clothing', 'Sell', '100', 'wsw', '1788356994_6a9829828a465.png', 'Sold', 'Nagad', 'Nagad: 01922887677'),
(14, 9, NULL, 'kkk', 'Home Appliances', 'Donate', '0', 'huh', '1788360889_6a9838b9c26b6.png', 'Available', '', ''),
(15, 13, NULL, 'jhj', 'Sports', 'Donate', '0', 'Dhanmondi,Dhaka', '1788362031_6a983d2f9f0a1.png', 'Available', '', ''),
(16, 9, NULL, 'jhgu', 'Books', 'Donate', '0', 'Dhanmondi,Dhaka', '1788364982_6a9848b69e05b.png', 'Available', '', ''),
(17, 9, NULL, 'jh', 'Home Appliances', 'Donate', '0', 'jhiu', '1788365302_6a9849f6dbf85.png', 'Available', '', ''),
(18, 13, NULL, 'aa', 'Furniture', 'Donate', '0', 'aa', '1788370579_6a985e9367edd.png', 'Available', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `item_requests`
--

CREATE TABLE `item_requests` (
  `request_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `request_type` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_requests`
--

INSERT INTO `item_requests` (`request_id`, `item_id`, `requester_id`, `owner_id`, `request_type`, `status`, `created_at`) VALUES
(5, 6, 9, 8, 'Donate', 'Accepted', '2026-09-01 21:12:23'),
(6, 7, 8, 9, 'Sell', 'Paid', '2026-09-01 21:20:44'),
(7, 8, 9, 8, 'Sell', 'Paid', '2026-09-01 22:14:08'),
(8, 9, 8, 9, 'Sell', 'Paid', '2026-09-02 03:48:11'),
(9, 11, 13, 9, 'Donate', 'Pending', '2026-09-02 12:07:55'),
(10, 14, 13, 9, 'Donate', 'Pending', '2026-09-02 15:09:46'),
(11, 15, 9, 13, 'Donate', 'Pending', '2026-09-02 15:14:27'),
(12, 16, 13, 9, 'Donate', 'Pending', '2026-09-02 16:04:36'),
(13, 17, 13, 9, 'Donate', 'Pending', '2026-09-02 16:08:44');

-- --------------------------------------------------------

--
-- Table structure for table `ngos`
--

CREATE TABLE `ngos` (
  `ngo_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `location` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `notification_type` varchar(30) NOT NULL DEFAULT 'request',
  `message` varchar(200) NOT NULL,
  `status` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `request_id`, `notification_type`, `message`, `status`) VALUES
(3, 8, 5, 'request', 'You accepted the request for dress. Requested by a.', 'Read'),
(4, 9, 5, 'request', 'Your request for dress has been accepted.', 'Read'),
(5, 9, 6, 'request', 'You accepted the request for table. Requested by Sadia Ahmed.', 'Read'),
(6, 8, 6, 'request', 'Your request for table has been accepted.', 'Read'),
(7, 9, 6, 'payment', 'Payment received for table. The item is now sold.', 'Read'),
(8, 8, 7, 'request', 'You accepted the request for table. Requested by a.', 'Read'),
(9, 9, 7, 'request', 'Your request for table has been accepted.', 'Read'),
(10, 8, 7, 'payment', 'Payment received for table. The item is now sold.', 'Read'),
(11, 9, 8, 'request', 'You accepted the request for sofa. Requested by Sadia Ahmed.', 'Read'),
(12, 8, 8, 'request', 'Your request for sofa has been accepted.', 'Read'),
(13, 9, 8, 'payment', 'Payment received for sofa. The item is now sold.', 'Read'),
(17, 8, 4, 'wanted_offer', 'a offered an item for your wanted post: Tv', 'Read'),
(21, 8, 6, 'wanted_offer', 'Your offer was accepted for wanted post: cycle', 'Read'),
(22, 9, 6, 'wanted_offer', 'Your offer was rejected for wanted post: cycle', 'Read'),
(23, 9, 9, 'request', 'New Donate request for your item: fan', 'Read'),
(24, 13, NULL, 'payment', 'a has paid for your item: jhud. The item is now sold.', 'Read'),
(25, 13, 7, 'request', 'a offered an item for your wanted post: akijsij', 'Read'),
(26, 9, 8, 'request', 'c offered an item for your wanted post: ting', 'Read'),
(27, 13, 8, 'wanted_offer', 'Your offer was accepted for wanted post: ting', 'Read'),
(28, 9, 7, 'wanted_offer', 'Your offer was rejected for wanted post: akijsij', 'Read'),
(29, 9, 10, 'request', 'Your item request for kkk has been made by c.', 'Read'),
(30, 13, 11, 'request', 'Your item request for jhj has been made by a.', 'Read'),
(31, 9, 12, 'request', 'Your item request for jhgu has been made by c.', 'Read'),
(32, 9, 13, 'request', 'Your item request for jh has been made by c.', 'Read'),
(33, 9, 9, 'request', 'c offered an item for your wanted post: AS', 'Read'),
(34, 13, 9, 'wanted_offer', 'Your offer was accepted for wanted post: AS', 'Read'),
(35, 9, 10, 'request', 'c offered an item for your wanted post: adjhe', 'Read'),
(36, 13, 10, 'wanted_offer', 'Your offer was accepted for wanted post: adjhe', 'Read'),
(37, 9, 11, 'wanted_offer', 'c offered an item for your wanted post: jgh', 'Read'),
(38, 13, 11, 'wanted_offer', 'Your offer was accepted for wanted post: jgh', 'Read'),
(39, 13, 11, 'payment', 'a has completed payment for your offer on \"jgh\". Amount: 200.00 BDT.', 'Read'),
(40, 9, 12, 'wanted_offer', 'c offered an item for your wanted post: iuwd', 'Read'),
(41, 13, 12, 'wanted_offer', 'Your offer was accepted for wanted post: iuwd', 'Read'),
(42, 13, 1, 'request', 'You accepted the request for aa. Requested by a.', 'Read'),
(43, 9, 1, 'request', 'Your request for aa has been accepted.', 'Read'),
(44, 13, 13, 'wanted_offer', 'a offered an item for your wanted post: hgfy', 'Read'),
(45, 9, 13, 'wanted_offer', 'Your offer was accepted for wanted post: hgfy', 'Read'),
(46, 9, 13, 'payment', 'c has completed payment for your offer on \"hgfy\". Amount: 99,898.00 BDT.', 'Read');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `offer_id` int(11) DEFAULT NULL,
  `amount` decimal(10,0) NOT NULL,
  `method` varchar(50) NOT NULL,
  `status` varchar(30) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `request_id`, `amount`, `method`, `status`, `created_at`) VALUES
(2, 8, 15000, 'Nagad', 'Paid', '2026-09-02 04:05:31'),
(3, NULL, 100, 'Nagad', 'Paid', '2026-09-02 13:50:57'),
(5, 11, 200, 'Bkash', 'Paid', '2026-09-02 16:50:47'),
(6, 13, 99898, 'Nagad', 'Paid', '2026-09-02 18:27:09');

-- --------------------------------------------------------

--
-- Table structure for table `pickups`
--

CREATE TABLE `pickups` (
  `pickup_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `status` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `ngo_id` int(11) DEFAULT NULL,
  `request_type` varchar(50) NOT NULL,
  `status` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`request_id`, `user_id`, `item_id`, `ngo_id`, `request_type`, `status`) VALUES
(1, 9, 18, NULL, 'Donate', 'Accepted');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `payment_method` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `receiver_number` varchar(20) NOT NULL,
  `pin_last4` varchar(4) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `buyer_id`, `seller_id`, `item_id`, `request_id`, `payment_method`, `amount`, `receiver_number`, `pin_last4`, `status`, `created_at`) VALUES
(1, 9, 8, 8, 7, 'Bkash', 3000.00, '01819191919', '1111', 'Completed', '2026-09-01 22:22:31'),
(2, 8, 9, 9, 8, 'Nagad', 15000.00, '01719056342', '1111', 'Completed', '2026-09-02 04:05:31'),
(3, 9, 13, 13, NULL, 'Nagad', 100.00, '01922887677', '2222', 'Completed', '2026-09-02 13:50:57'),
(4, 9, 13, NULL, 11, 'Bkash', 200.00, '01989283272', '1111', 'Completed', '2026-09-02 16:50:47'),
(5, 13, 9, NULL, 13, 'Nagad', 99898.00, '01922887676', '9999', 'Completed', '2026-09-02 18:27:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `location` varchar(100) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `phone`, `location`, `role`) VALUES
(8, 'Sadia Ahmed', 'sadia@example.com', '$2y$10$gWWhl1zyl5O9Q6Ia2yZqfuJ4wQHC3qi01boFrZ50vQOnMUJ7Za5CG', '01700000000', 'keraniganj,Dhaka', 'user'),
(9, 'a', 'a@gmail.com', '$2y$10$IhlaEh/4GwiC4OQbE9OEuenK4b54WNR7cSY7ziud9YbmpIfc.CkR2', '01727267228', 'Brahmanbaria', 'user'),
(13, 'c', 'c@gmail.com', '$2y$10$inv10v1OEK5pdNLR378NbO5VpzUq4QEeMH1XvEmy9g1qr3qPfg3Ye', '01727267299', 'rajshahi', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `wanted_offers`
--

CREATE TABLE `wanted_offers` (
  `offer_id` int(11) NOT NULL,
  `wanted_id` int(11) NOT NULL,
  `offerer_user_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `is_free` tinyint(1) NOT NULL DEFAULT 0,
  `payment_method` varchar(20) DEFAULT NULL,
  `payment_number` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `payment_visible` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wanted_offers`
--

INSERT INTO `wanted_offers` (`offer_id`, `wanted_id`, `offerer_user_id`, `item_id`, `image`, `location`, `price`, `is_free`, `payment_method`, `payment_number`, `status`, `payment_visible`, `created_at`) VALUES
(5, 7, 9, NULL, 'offer_1788346176_6a97ff40ac360.png', NULL, 5000.00, 0, 'Bkash', 'Bkash: 01814192319', 'Rejected', 0, '2026-09-02 16:49:36'),
(6, 7, 8, NULL, 'offer_1788346248_6a97ff883727c.png', NULL, 3000.00, 0, 'Nagad', 'Nagad: 01719056358', 'Accepted', 1, '2026-09-02 16:50:48'),
(7, 9, 9, NULL, 'offer_1788357352_6a982ae80696d.png', NULL, 500.00, 0, 'Bkash, Nagad', 'Bkash: 01819231919, Nagad: 01922887677', 'Rejected', 0, '2026-09-02 19:55:52'),
(8, 10, 13, NULL, 'offer_1788358698_6a98302ac650f.png', 'hgahsg', 1000.00, 0, 'Bkash, Nagad', 'Bkash: 01819192387, Nagad: 01922887676', 'Accepted', 1, '2026-09-02 20:18:18'),
(9, 11, 13, NULL, 'offer_1788366232_6a984d9859cc4.png', 'ad', NULL, 1, NULL, NULL, 'Accepted', 1, '2026-09-02 22:23:52'),
(10, 12, 13, NULL, 'offer_1788366677_6a984f55ec084.png', 'uh', NULL, 1, NULL, NULL, 'Accepted', 1, '2026-09-02 22:31:17'),
(11, 13, 13, NULL, 'offer_1788367714_6a98536239923.png', 'a', 200.00, 0, 'Bkash', 'Bkash: 01989283272', 'Paid', 1, '2026-09-02 22:48:34'),
(12, 14, 13, NULL, 'offer_1788367905_6a9854215d1ff.png', 'sd', NULL, 1, NULL, NULL, 'Accepted', 1, '2026-09-02 22:51:45'),
(13, 15, 9, NULL, 'offer_1788373498_6a9869fa958a2.png', 'jguu', 99898.00, 0, 'Nagad', 'Nagad: 01922887676', 'Paid', 1, '2026-09-03 00:24:58');

-- --------------------------------------------------------

--
-- Table structure for table `wanted_posts`
--

CREATE TABLE `wanted_posts` (
  `wanted_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `category` varchar(60) NOT NULL,
  `location` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wanted_posts`
--

INSERT INTO `wanted_posts` (`wanted_id`, `user_id`, `title`, `category`, `location`, `description`, `status`, `image`) VALUES
(6, 8, 'Tv', 'Electronics', 'Brahmanbaria', 'i nedd a Walton 2nd hand tv in well condition', 'Accepted', '1788288942_6a971fae08c24.png'),
(7, 13, 'cycle', 'Others', 'rajshahi sodor', '2nd hand cycle', 'Accepted', '1788345535_6a97fcbfa2920.png'),
(8, 9, 'oven', 'Electronics', 'mirpur,dhaka', 'need a oven budget around 10k', 'Active', '1788350655_6a9810bf5147d.png'),
(9, 13, 'akijsij', 'Home Appliances', 'rajshahi', 'wqidowue', 'Active', '1788357107_6a9829f3e1e3b.png'),
(10, 9, 'ting', 'Books', 'Brahmanbaria', 'ytrtdff', 'Accepted', '1788358624_6a982fe0e3ae1.png'),
(11, 9, 'AS', 'Books', 'XS', 'XSX', 'Accepted', ''),
(12, 9, 'adjhe', 'Clothing', 'rajshahi', 'asds', 'Accepted', ''),
(13, 9, 'jgh', 'Clothing', 'yfg', 'ht', 'Accepted', ''),
(14, 9, 'iuwd', 'Others', 'ded', 'ed', 'Accepted', ''),
(15, 13, 'hgfy', 'Furniture', 'hg', 'hgy', 'Accepted', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `assigned_delivery_man_id` (`assigned_delivery_man_id`);

--
-- Indexes for table `delivery_man`
--
ALTER TABLE `delivery_man`
  ADD PRIMARY KEY (`delivery_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `item_requests`
--
ALTER TABLE `item_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `ngos`
--
ALTER TABLE `ngos`
  ADD PRIMARY KEY (`ngo_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `offer_id` (`offer_id`);

--
-- Indexes for table `pickups`
--
ALTER TABLE `pickups`
  ADD PRIMARY KEY (`pickup_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `ngo_id` (`ngo_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `wanted_offers`
--
ALTER TABLE `wanted_offers`
  ADD PRIMARY KEY (`offer_id`),
  ADD UNIQUE KEY `unique_wanted_offerer` (`wanted_id`,`offerer_user_id`),
  ADD KEY `wanted_id` (`wanted_id`),
  ADD KEY `offerer_user_id` (`offerer_user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `wanted_posts`
--
ALTER TABLE `wanted_posts`
  ADD PRIMARY KEY (`wanted_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pickups`
--
ALTER TABLE `pickups`
  MODIFY `pickup_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_man`
--
ALTER TABLE `delivery_man`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `item_requests`
--
ALTER TABLE `item_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `wanted_offers`
--
ALTER TABLE `wanted_offers`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `wanted_posts`
--
ALTER TABLE `wanted_posts`
  MODIFY `wanted_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `item_requests` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`offer_id`) REFERENCES `wanted_offers` (`offer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pickups`
--
ALTER TABLE `pickups`
  ADD CONSTRAINT `pickups_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`request_id`);

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`),
  ADD CONSTRAINT `requests_ibfk_3` FOREIGN KEY (`ngo_id`) REFERENCES `ngos` (`ngo_id`);

--
-- Constraints for table `wanted_posts`
--
ALTER TABLE `wanted_posts`
  ADD CONSTRAINT `wanted_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- --------------------------------------------------------
-- MERGED ADMIN / NGO / DELIVERY MODULE COMPATIBILITY
--
-- These non-destructive statements support the additional Admin, NGO and
-- delivery-management pages merged from web_merged.zip. They retain this
-- project's existing `delivery_man` and `deliveries` tables; the `delivery`
-- view below is a compatibility layer for the added module.
-- --------------------------------------------------------

-- Recreate the existing relation after making ngo_id auto-increment.
ALTER TABLE `requests`
  DROP FOREIGN KEY `requests_ibfk_3`;

ALTER TABLE `ngos`
  MODIFY `ngo_id` int(11) NOT NULL AUTO_INCREMENT,
  ADD COLUMN IF NOT EXISTS `organization_type` varchar(100) NULL,
  ADD COLUMN IF NOT EXISTS `established_year` year(4) NULL,
  ADD COLUMN IF NOT EXISTS `verification_document` varchar(255) NULL,
  ADD COLUMN IF NOT EXISTS `verification_status` varchar(30) NOT NULL DEFAULT 'Pending';

ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_3`
  FOREIGN KEY (`ngo_id`) REFERENCES `ngos` (`ngo_id`);

ALTER TABLE `delivery_man`
  ADD COLUMN IF NOT EXISTS `availability` varchar(20) NOT NULL DEFAULT 'Offline',
  ADD COLUMN IF NOT EXISTS `is_blocked` tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE `requests`
  ADD COLUMN IF NOT EXISTS `created_at` datetime NULL,
  ADD COLUMN IF NOT EXISTS `updated_at` datetime NULL,
  ADD COLUMN IF NOT EXISTS `delivery_id` int(11) NULL,
  ADD COLUMN IF NOT EXISTS `assigned_at` datetime NULL,
  ADD COLUMN IF NOT EXISTS `arrived_at` datetime NULL,
  ADD COLUMN IF NOT EXISTS `item_received` varchar(3) NULL,
  ADD COLUMN IF NOT EXISTS `pickup_note` text NULL,
  ADD COLUMN IF NOT EXISTS `picked_up_at` datetime NULL,
  ADD COLUMN IF NOT EXISTS `completion_note` text NULL,
  ADD COLUMN IF NOT EXISTS `completed_at` datetime NULL,
  ADD COLUMN IF NOT EXISTS `delivery_rejection_reason` text NULL;

ALTER TABLE `pickups`
  ADD COLUMN IF NOT EXISTS `delivery_id` int(11) NULL;

ALTER TABLE `notifications`
  MODIFY `user_id` int(11) NULL,
  ADD COLUMN IF NOT EXISTS `ngo_id` int(11) NULL,
  ADD COLUMN IF NOT EXISTS `item_id` int(11) NULL,
  ADD COLUMN IF NOT EXISTS `delivery_id` int(11) NULL,
  ADD COLUMN IF NOT EXISTS `pickup_id` int(11) NULL;

CREATE INDEX IF NOT EXISTS `idx_requests_delivery_status`
  ON `requests` (`delivery_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_requests_updated_at`
  ON `requests` (`updated_at`);
CREATE INDEX IF NOT EXISTS `idx_notifications_delivery_status`
  ON `notifications` (`delivery_id`, `status`);

CREATE OR REPLACE VIEW `delivery` AS
SELECT
  `delivery_id`, `name`, `email`, `password`, `phone`, `location`,
  `verification_document`, `verification_status`, `availability`, `is_blocked`
FROM `delivery_man`;

-- Shared delivery-charge workflow for Donate and Buy items.
ALTER TABLE `payments`
  ADD COLUMN IF NOT EXISTS `offer_id` int(11) NULL AFTER `request_id`;

ALTER TABLE `delivery_man`
  ADD COLUMN IF NOT EXISTS `delivery_payment_method` varchar(20) NULL,
  ADD COLUMN IF NOT EXISTS `delivery_payment_number` varchar(30) NULL;

ALTER TABLE `deliveries`
  ADD COLUMN IF NOT EXISTS `source_table` varchar(30) NOT NULL DEFAULT 'item_requests',
  ADD COLUMN IF NOT EXISTS `source_request_id` int(11) NULL,
  ADD COLUMN IF NOT EXISTS `item_id` int(11) NULL,
  ADD COLUMN IF NOT EXISTS `receiver_id` int(11) NULL,
  ADD COLUMN IF NOT EXISTS `charge_status` varchar(30) NOT NULL DEFAULT 'Not Proposed',
  ADD COLUMN IF NOT EXISTS `charge_payment_status` varchar(30) NOT NULL DEFAULT 'Unpaid',
  ADD COLUMN IF NOT EXISTS `charge_payment_method` varchar(20) NULL,
  ADD COLUMN IF NOT EXISTS `charge_paid_at` datetime NULL,
  ADD COLUMN IF NOT EXISTS `charge_accepted_at` datetime NULL,
  ADD COLUMN IF NOT EXISTS `charge_rejected_at` datetime NULL,
  ADD COLUMN IF NOT EXISTS `arrived_at` datetime NULL,
  ADD COLUMN IF NOT EXISTS `updated_at` datetime NULL;

CREATE TABLE IF NOT EXISTS `delivery_payments` (
  `delivery_payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_id` int(11) NOT NULL,
  `payer_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Completed',
  `paid_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`delivery_payment_id`),
  KEY `idx_delivery_payments_delivery` (`delivery_id`),
  KEY `idx_delivery_payments_payer` (`payer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `delivery_job_responses` (
  `response_id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_id` int(11) NOT NULL,
  `delivery_man_id` int(11) NOT NULL,
  `response_status` varchar(20) NOT NULL,
  `responded_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`response_id`),
  UNIQUE KEY `unique_delivery_response` (`delivery_id`, `delivery_man_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
