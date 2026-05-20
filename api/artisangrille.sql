-- Adminer 5.4.1 MySQL 9.4.0 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `admins` (`id`, `email`, `password`, `created_at`) VALUES
(1,	'BlackSSAMM@artisan.com',	'DEVvvssam091#',	'2026-01-18 21:10:32');

DROP TABLE IF EXISTS `booked_tables`;
CREATE TABLE `booked_tables` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table_id` int NOT NULL,
  `booked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `table_id` (`table_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `booked_tables` (`id`, `table_id`, `booked`) VALUES
(51,	556,	1),
(50,	116,	1),
(49,	114,	1),
(48,	77,	1),
(47,	1,	0),
(46,	112,	1),
(52,	117,	1),
(53,	2,	0),
(54,	118,	1),
(55,	6,	1),
(56,	445,	1),
(57,	56,	1),
(58,	113,	1),
(59,	123,	1),
(60,	5,	1),
(61,	12,	1),
(62,	1178,	1),
(63,	45,	1),
(64,	78,	1),
(65,	115,	1),
(66,	23,	1),
(67,	87,	1),
(68,	34,	1),
(69,	90,	1),
(70,	66,	1),
(71,	47,	1),
(72,	67,	1),
(73,	990,	1),
(74,	17,	1),
(75,	57,	1),
(76,	89,	1),
(77,	789,	1),
(78,	3,	1),
(79,	4,	0),
(80,	40,	1),
(81,	8,	1),
(82,	7,	1),
(83,	9,	1),
(84,	10,	1),
(85,	11,	1);

DROP TABLE IF EXISTS `login_verifications`;
CREATE TABLE `login_verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `code` varchar(4) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


SET NAMES utf8mb4;

DROP TABLE IF EXISTS `menu_stock`;
CREATE TABLE `menu_stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `menu_id` int NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `available` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menu_id` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `menu_stock` (`id`, `menu_id`, `stock`, `available`, `updated_at`) VALUES
(1,	1,	0,	0,	'2026-05-09 08:28:22'),
(2,	2,	6,	1,	'2026-05-17 13:07:14'),
(3,	3,	20,	1,	'2026-04-12 14:59:16'),
(4,	4,	13,	1,	'2026-05-03 13:07:34'),
(5,	5,	18,	1,	'2026-05-09 09:35:42'),
(6,	6,	20,	1,	'2026-04-12 14:59:16'),
(7,	7,	12,	1,	'2026-05-02 19:44:14'),
(8,	8,	15,	1,	'2026-05-01 23:38:57'),
(9,	9,	20,	1,	'2026-04-12 14:59:16'),
(10,	10,	19,	1,	'2026-05-01 23:38:59'),
(11,	11,	20,	1,	'2026-04-12 14:59:16'),
(12,	12,	20,	1,	'2026-04-12 14:59:16'),
(13,	13,	19,	1,	'2026-05-01 23:43:39'),
(14,	14,	19,	1,	'2026-05-01 15:32:18'),
(15,	15,	20,	1,	'2026-04-12 14:59:16'),
(16,	16,	18,	1,	'2026-05-05 11:45:57'),
(17,	17,	19,	1,	'2026-05-01 15:32:22'),
(18,	18,	20,	1,	'2026-04-12 14:59:16'),
(19,	19,	19,	1,	'2026-05-01 15:40:59'),
(20,	20,	18,	1,	'2026-05-02 12:43:13'),
(21,	21,	20,	1,	'2026-04-12 14:59:16'),
(22,	22,	19,	1,	'2026-05-01 15:41:04'),
(23,	23,	19,	1,	'2026-05-01 15:41:05'),
(24,	24,	19,	1,	'2026-05-01 15:41:09'),
(25,	25,	19,	1,	'2026-05-01 15:41:10'),
(26,	26,	20,	1,	'2026-04-12 14:59:16'),
(27,	27,	20,	1,	'2026-04-12 14:59:16'),
(28,	28,	19,	1,	'2026-05-05 11:32:19'),
(29,	29,	20,	1,	'2026-04-12 14:59:16');

DROP TABLE IF EXISTS `paid_order_items`;
CREATE TABLE `paid_order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `paid_order_id` int DEFAULT NULL,
  `menu_id` int DEFAULT NULL,
  `menu_name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `paid_order_id` (`paid_order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `paid_order_items` (`id`, `paid_order_id`, `menu_id`, `menu_name`, `price`, `quantity`) VALUES
(173,	183,	4,	'Assorted (Nigerian) Jollof Rice Supreme',	42.00,	1),
(172,	183,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(171,	183,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(170,	182,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(169,	181,	7,	'Jamaican Ackee & Saltfish',	28.00,	1),
(168,	181,	5,	'Nigerian Pepper Soup (Chicken / Goat)',	26.00,	1),
(167,	181,	4,	'Assorted (Nigerian) Jollof Rice Supreme',	42.00,	1),
(166,	181,	1,	'Authentic Nigerian Beef Suya',	1.00,	2),
(165,	181,	2,	'Smoked Turkey Wings (Signature)',	24.00,	2),
(164,	180,	1,	'Authentic Nigerian Beef Suya',	1.00,	2),
(163,	178,	4,	'Assorted (Nigerian) Jollof Rice Supreme',	42.00,	1),
(162,	178,	1,	'Authentic Nigerian Beef Suya',	1.00,	3),
(161,	177,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(160,	176,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(159,	175,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(158,	174,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(157,	173,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(156,	172,	13,	'Chocolate Soufflé',	16.00,	1),
(155,	172,	10,	'Spanish Patatas Bravas',	48.00,	1),
(154,	172,	8,	'Kenyan Ugali & Sukuma Wiki',	36.00,	1),
(153,	172,	7,	'Jamaican Ackee & Saltfish',	28.00,	1),
(151,	171,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(152,	172,	1,	'Authentic Nigerian Beef Suya',	1.00,	4),
(150,	171,	1,	'Authentic Nigerian Beef Suya',	1.00,	2),
(174,	184,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(175,	184,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(176,	185,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(177,	186,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(178,	186,	4,	'Assorted (Nigerian) Jollof Rice Supreme',	42.00,	1),
(179,	186,	20,	'Moët & Chandon Champagne',	6.00,	1),
(180,	187,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(181,	188,	1,	'Authentic Nigerian Beef Suya',	1.00,	2),
(182,	188,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(183,	189,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(184,	190,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(185,	191,	7,	'Jamaican Ackee & Saltfish',	28.00,	1),
(186,	192,	7,	'Jamaican Ackee & Saltfish',	28.00,	1),
(187,	193,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(188,	194,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(189,	195,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(190,	196,	4,	'Assorted (Nigerian) Jollof Rice Supreme',	42.00,	1),
(191,	197,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(192,	198,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(223,	227,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(194,	200,	4,	'Assorted (Nigerian) Jollof Rice Supreme',	42.00,	1),
(195,	201,	4,	'Assorted (Nigerian) Jollof Rice Supreme',	42.00,	1),
(196,	202,	1,	'Jollof Rice',	4.13,	2),
(197,	203,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(198,	204,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(199,	205,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(200,	206,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(201,	207,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(202,	208,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(203,	209,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(204,	210,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(205,	211,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(206,	212,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(207,	212,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(208,	213,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(209,	214,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(210,	215,	28,	'Kids Chicken Pie',	9.00,	1),
(211,	216,	28,	'Kids Chicken Pie',	9.00,	1),
(212,	217,	16,	'Yam & Egg Sauce',	14.00,	1),
(213,	218,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(214,	219,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(215,	220,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(216,	221,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(217,	222,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(218,	223,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(219,	224,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(220,	225,	1,	'Authentic Nigerian Beef Suya',	1.00,	1),
(221,	226,	2,	'Smoked Turkey Wings (Signature)',	24.00,	1),
(222,	226,	5,	'Nigerian Pepper Soup (Chicken / Goat)',	26.00,	1);

DROP TABLE IF EXISTS `paid_orders`;
CREATE TABLE `paid_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `table_no` varchar(10) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_ref` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `plate_order_no` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `order_type` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'table',
  `status` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'payment_pending',
  `full_address` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `order_status` enum('Order placed','Cooking','Cooking done','Out for delivery','Delivered','Served','Picked up') CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'Order placed',
  `user_id` int DEFAULT NULL,
  `pickup_time` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `session_code` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_order` (`user_id`),
  CONSTRAINT `fk_user_order` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `paid_orders` (`id`, `name`, `phone`, `table_no`, `total_amount`, `payment_ref`, `created_at`, `plate_order_no`, `order_type`, `status`, `full_address`, `order_status`, `user_id`, `pickup_time`, `session_code`) VALUES
(200,	'Sdk',	'+2347089913116',	'990',	50.25,	'10200869',	'2026-05-03 13:05:22',	'Artisan20260503GRILL37',	'table',	'paid',	'',	'Delivered',	47,	'',	NULL),
(201,	'Vvddx',	'+2347089913116',	'78',	50.25,	'10200872',	'2026-05-03 13:06:44',	'Artisan20260503GRILL73',	'table',	'paid',	'',	'Order placed',	47,	'',	NULL),
(202,	'Samson',	'+2347089913116',	'112',	8.26,	NULL,	'2026-05-03 15:29:07',	'Artisan20260503GRILL20',	'table',	'payment_pending',	'',	'Order placed',	47,	'',	NULL),
(203,	'Sam',	'+2342809943116',	'12',	4.13,	NULL,	'2026-05-03 16:17:04',	'Artisan20260503GRILL14',	'table',	'payment_pending',	'',	'Order placed',	NULL,	'',	NULL),
(204,	'Dam',	'+2347089913116',	'14',	4.13,	NULL,	'2026-05-03 16:31:03',	'Artisan20260503GRILL35',	'table',	'payment_pending',	'',	'Order placed',	47,	'',	NULL),
(205,	'Movo1',	'+2347089913116',	'78',	4.13,	NULL,	'2026-05-03 16:36:58',	'Artisan20260503GRILL52',	'table',	'payment_pending',	'',	'Order placed',	47,	'',	NULL),
(206,	'Movo1',	'+2347089913116',	'112',	4.13,	NULL,	'2026-05-03 16:52:19',	'Artisan20260503GRILL75',	'table',	'payment_pending',	'',	'Order placed',	47,	'',	NULL),
(207,	'Movo11',	'+2347089913116',	'17',	4.13,	'10201177',	'2026-05-03 17:11:20',	'Artisan20260503GRILL43',	'table',	'paid',	'',	'Order placed',	47,	'',	NULL),
(208,	'Movo12',	'+2347089913116',	'67',	4.13,	'10201179',	'2026-05-03 17:12:45',	'Artisan20260503GRILL15',	'table',	'paid',	'',	'Order placed',	47,	'',	NULL),
(209,	'Samlater',	'+2347089913116',	'57',	4.13,	'10203861',	'2026-05-05 06:02:38',	'Artisan20260505GRILL11',	'table',	'paid',	NULL,	'Order placed',	47,	NULL,	'TBL-57-ED74B'),
(210,	'Sam2later',	'+2347089913116',	'57',	4.13,	'10203864',	'2026-05-05 06:04:27',	'Artisan20260505GRILL97',	'table',	'paid',	NULL,	'Order placed',	47,	NULL,	'TBL-57-32BB4'),
(211,	'Blacklatwr',	'+2348096831043',	'57',	4.13,	'10203879',	'2026-05-05 06:05:45',	'Artisan20260505GRILL69',	'table',	'paid',	NULL,	'Order placed',	NULL,	NULL,	'TBL-57-77203'),
(212,	'Vvbbbblatwr',	'+2348096831043',	'89',	28.13,	'10203882',	'2026-05-05 06:16:44',	'Artisan20260505GRILL77',	'table',	'paid',	NULL,	'Order placed',	NULL,	NULL,	'TBL-89-55FF5'),
(213,	'Samsssssslqter',	'+2348096831043',	'89',	30.00,	'10203891',	'2026-05-05 06:18:31',	'Artisan20260505GRILL49',	'table',	'paid',	NULL,	'Order placed',	NULL,	NULL,	'TBL-89-1CC83'),
(214,	'Bbncosmo',	'+2348096831043',	'789',	30.00,	'10203892',	'2026-05-05 06:19:35',	'Artisan20260505GRILL21',	'table',	'paid',	NULL,	'Order placed',	NULL,	NULL,	'TBL-789-1138C'),
(215,	'Samson',	'+2347089913116',	'23',	13.13,	NULL,	'2026-05-05 11:31:20',	'Artisan20260505GRILL44',	'table',	'payment_pending',	'',	'Order placed',	47,	'',	NULL),
(216,	'Samson',	'+2347089913116',	'23',	13.13,	'10204557',	'2026-05-05 11:31:55',	'Artisan20260505GRILL55',	'table',	'paid',	'',	'Order placed',	47,	'',	NULL),
(217,	'Samson',	'+2347089913116',	'40',	18.75,	'10204586',	'2026-05-05 11:45:37',	'Artisan20260505GRILL67',	'table',	'paid',	'',	'Order placed',	47,	'',	NULL),
(218,	'Ls',	'+2347089913116',	'8',	4.13,	'10204848',	'2026-05-05 12:41:57',	'Artisan20260505GRILL04',	'table',	'paid',	'',	'Order placed',	47,	'',	NULL),
(219,	'Sam',	'+2347089913116',	'47',	4.13,	'10204959',	'2026-05-05 13:42:06',	'Artisan20260505GRILL91',	'table',	'paid',	'',	'Order placed',	47,	'',	NULL),
(220,	'Sam',	'+2347089913116',	'67',	4.13,	'10204964',	'2026-05-05 13:43:24',	'Artisan20260505GRILL69',	'table',	'paid',	NULL,	'Order placed',	47,	NULL,	'TBL-67-81240'),
(221,	'Vvb',	'+2347089913116',	'89',	4.13,	'10205075',	'2026-05-05 15:03:39',	'Artisan20260505GRILL67',	'table',	'paid',	'',	'Order placed',	47,	'',	NULL),
(222,	'sam',	'+2347089913116',	'113',	4.13,	NULL,	'2026-05-08 21:59:10',	'Artisan20260508GRILL70',	'table',	'payment_pending',	'',	'Order placed',	47,	'',	NULL),
(223,	'blackSams',	'+2347089913116',	'113',	4.13,	NULL,	'2026-05-08 22:01:12',	'Artisan20260508GRILL61',	'table',	'payment_pending',	'',	'Order placed',	47,	'',	NULL),
(224,	'vvc',	'+2348096831043',	'113',	30.00,	'10214141',	'2026-05-08 22:03:04',	'Artisan20260508GRILL87',	'table',	'paid',	'',	'Order placed',	NULL,	'',	NULL),
(225,	'ss',	'+2347089913116',	'6',	4.13,	'10214797',	'2026-05-09 08:27:47',	'Artisan20260509GRILL78',	'table',	'paid',	'',	'Order placed',	47,	'',	NULL),
(226,	'Lteer',	'+2347089913116',	'78',	59.25,	'10214982',	'2026-05-09 09:35:14',	'Artisan20260509GRILL81',	'table',	'paid',	'',	'Order placed',	47,	'',	NULL),
(227,	'Ddf',	'+2347089913116',	'56',	30.00,	'10234692',	'2026-05-17 13:06:50',	'Artisan20260517GRILL77',	'table',	'paid',	'',	'Cooking done',	47,	'',	NULL);

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `booking_date` datetime NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `status` int DEFAULT '1',
  `reservation_code` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `reservations` (`id`, `table_id`, `name`, `email`, `phone`, `booking_date`, `amount`, `transaction_id`, `status`, `reservation_code`, `created_at`) VALUES
(27,	1,	'Sams',	'Wsamson630@gmail.com',	'+2347089913116',	'2026-04-09 22:24:00',	3000.00,	'10144479',	1,	'RES-ART-28B8DA09',	'2026-04-07 22:25:26'),
(28,	3,	'Hhj',	'1@gmail.com',	'+2347089913446',	'2026-05-05 10:54:00',	3000.00,	'10204464',	1,	'RES-ART-8C836364',	'2026-05-05 10:55:19'),
(29,	4,	'2@gmail.com',	'2@gmail.com',	'+2348096831043',	'2026-05-05 10:56:00',	3000.00,	'10204467',	1,	'RES-ART-EFF62329',	'2026-05-05 10:56:38'),
(30,	7,	'Samson',	'Wsamson630@gmail.com',	'+2347089913116',	'2026-05-05 13:23:34',	5000.00,	'10204923',	1,	'RES-ART-D830F9B6',	'2026-05-05 13:24:20'),
(31,	9,	'Vc',	'noreply@wealthbridges.online',	'+2347089913116',	'2026-05-05 13:27:37',	5000.00,	'10204927',	1,	'RES-ART-5DFC818F',	'2026-05-05 13:28:21'),
(32,	10,	'Sam',	'noreply@wealthbridges.online',	'+2347089913116',	'2026-05-05 13:33:15',	5000.00,	'10204938',	1,	'RES-ART-F1FAF5B4',	'2026-05-05 13:33:50'),
(33,	11,	'Sqm',	'Wsamson630@gmail.com',	'+2347089913116',	'2026-05-05 13:44:00',	5000.00,	'10204968',	1,	'RES-ART-33B4EB2A',	'2026-05-05 13:45:15');

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `business_type` varchar(100) DEFAULT NULL,
  `business_name` varchar(150) DEFAULT NULL,
  `plan` varchar(200) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `transaction_id` varchar(200) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `renewal_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `subscription_code` varchar(21) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_code` (`subscription_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `subscriptions` (`id`, `fullname`, `username`, `email`, `phone`, `country`, `dob`, `gender`, `business_type`, `business_name`, `plan`, `amount`, `transaction_id`, `status`, `renewal_date`, `created_at`, `subscription_code`) VALUES
(8,	'Black sams',	'Black sams ',	'Wsamson630@gmail.com',	'1234',	'Port',	'2026-04-07',	'Male',	'Res',	'Artisan Restaurant',	'Annual Deal (WEB Only)',	300.00,	'10144437',	NULL,	'2027-04-07',	'2026-04-07 21:50:47',	'069958097590755040763'),
(9,	'Sams',	'Dsams',	'dswms@gmail.com',	'123',	'Llm',	'2026-04-07',	'Male',	'Hhe',	'Artisan Restaurant',	'Annual Deal (WEB Only)',	300.00,	'10144444',	NULL,	'2027-04-07',	'2026-04-07 21:58:11',	'061104405828746216603'),
(10,	'Gg',	'We',	'1@gmail.com',	'45',	'Hh',	'2026-04-07',	'Male',	'Bb',	'Artisan Restaurant',	'Monthly Deal Subscription (WEB Only)',	80.00,	'10144447',	NULL,	'2026-05-07',	'2026-04-07 21:59:27',	'105056409064402163880'),
(11,	'Bbs',	'Bbsms',	'Wsamson630@gmail.com',	'1234',	'Lla',	'2026-04-08',	'Male',	'Resd',	'Artisan Restaurant',	'Monthly Deal Subscription (WEB Only)',	80.00,	'10147641',	NULL,	'2026-05-08',	'2026-04-08 18:13:06',	'123659272390029030438'),
(12,	'Sam',	'Vvs',	'Iamjames@gmail.com',	'123',	'N8g',	'2026-04-14',	'Male',	'Res',	'Artisan Cafe',	'Monthly Deal Subscription (WEB Only)',	80.00,	'10160930',	NULL,	'2026-05-14',	'2026-04-14 14:46:36',	'822110414293730035478');

DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `expires_at`, `created_at`) VALUES
(46,	47,	'04af2ed068d3a2a4d89fdf5b6dda6f096eb44fb92d3df0f583b6513729a24742',	'2026-04-08 05:16:39',	'2026-04-08 04:46:39'),
(45,	47,	'552ca020ce35425ea26d9dd0ce66ad07a27946626dd6e18b52a427ba1652e8e1',	'2026-04-08 04:54:05',	'2026-04-08 04:24:05'),
(44,	47,	'44272fc9159be9806d3fbd0a7e621a8f858180a5373a9cbb91a6802a5d63e3ce',	'2026-04-08 00:10:32',	'2026-04-07 23:40:32'),
(41,	47,	'bc8e15ac18c78acc75f83596f3bf8a888f0f5f1319ee5fe56a005a0ddd95c0cf',	'2026-04-08 00:02:33',	'2026-04-07 23:32:33'),
(42,	47,	'9c84318e16fa5ba6a94df0467bcdecc1604ac27e1dbb464bba327bffce64f7bd',	'2026-04-08 00:03:11',	'2026-04-07 23:33:11'),
(43,	47,	'18d364a56cfa809a82cc338024b8bbfb62a198caf755ee6b58b8a3e98c73bb4d',	'2026-04-08 00:07:20',	'2026-04-07 23:37:20'),
(47,	47,	'035e466906e28b878e28fd568ada0c84f6745c508e008d6b814489c7e4241159',	'2026-04-08 05:39:48',	'2026-04-08 05:09:48'),
(48,	47,	'8b7c6f64c79c31cc1d50af2c364bceec0f4af2f36e6d4d7ac72da7abca9db60d',	'2026-04-08 05:41:12',	'2026-04-08 05:11:12'),
(49,	47,	'02ae40dc177d87abad558e47d7c1d0480ae6dfa6debc0f8485007a8bdfc3ee02',	'2026-05-05 13:25:42',	'2026-05-05 12:55:42');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('pending','active') DEFAULT 'pending',
  `verification_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `status`, `verification_token`, `created_at`) VALUES
(47,	'Samson',	'Wsamson630@gmail.com',	'+2347089913116',	'$2y$10$WaYtAAQPlOOv4VgnWZ.fPeXdwaICuOaYd.SZCG5nyHFW973VIkvsu',	'active',	NULL,	'2026-04-07 23:31:00');

-- 2026-05-20 10:14:40 UTC
