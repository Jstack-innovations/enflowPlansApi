-- Adminer 5.4.1 MySQL 8.0.36-28 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `enflow_settings`;
CREATE TABLE `enflow_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `enflow_settings` (`id`, `setting_key`, `setting_value`) VALUES
(1,	'trial_days',	'10');

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email_status` varchar(20) DEFAULT 'pending',
  `email_otp` varchar(6) DEFAULT NULL,
  `email_otp_expires` datetime DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `num_locations` int DEFAULT NULL,
  `num_staff` int DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `connected_tools` json DEFAULT NULL,
  `team_members` json DEFAULT NULL,
  `zara_brand_voice` varchar(100) DEFAULT NULL,
  `zara_primary_lang` varchar(50) DEFAULT NULL,
  `zara_also_speaks` json DEFAULT NULL,
  `zara_top_goals` json DEFAULT NULL,
  `zara_hours` json DEFAULT NULL,
  `onboarding_step` int DEFAULT '0',
  `dob` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `business_type` varchar(100) DEFAULT NULL,
  `business_subtype` varchar(100) DEFAULT NULL,
  `business_name` varchar(150) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `plan` varchar(200) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `transaction_id` varchar(200) DEFAULT NULL,
  `status` enum('trial','active','suspended','expired','cancelled') DEFAULT NULL,
  `renewal_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `subscription_code` varchar(21) NOT NULL,
  `zara_credits` int DEFAULT '0',
  `trial_started_at` datetime DEFAULT NULL,
  `trial_ends_at` datetime DEFAULT NULL,
  `onboarding_token` varchar(64) DEFAULT NULL,
  `zara_credits_used` int DEFAULT '0',
  `low_credit_alert_sent` tinyint(1) DEFAULT '0',
  `auth_token` varchar(64) DEFAULT NULL,
  `auth_token_expiry` datetime DEFAULT NULL,
  `local_server_url` varchar(255) DEFAULT NULL,
  `software_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_code` (`subscription_code`),
  KEY `idx_sub_email` (`email`),
  KEY `idx_sub_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `subscriptions` (`id`, `fullname`, `username`, `email`, `phone`, `password`, `email_status`, `email_otp`, `email_otp_expires`, `country`, `currency`, `num_locations`, `num_staff`, `logo_url`, `connected_tools`, `team_members`, `zara_brand_voice`, `zara_primary_lang`, `zara_also_speaks`, `zara_top_goals`, `zara_hours`, `onboarding_step`, `dob`, `gender`, `business_type`, `business_subtype`, `business_name`, `website`, `plan`, `amount`, `transaction_id`, `status`, `renewal_date`, `created_at`, `subscription_code`, `zara_credits`, `trial_started_at`, `trial_ends_at`, `onboarding_token`, `zara_credits_used`, `low_credit_alert_sent`, `auth_token`, `auth_token_expiry`, `local_server_url`, `software_url`) VALUES
(27,	'Kendrell',	'blackSams',	'Wsamson650@gmail.com',	'+234 8096831043',	'$2y$10$8.1/kqsdk.Grf/FGtEnyC.JeNWO3psvhKG810phMpMzSrmiZd9Mre',	'verified',	NULL,	NULL,	'Nigeria ',	'NGN',	2,	15,	'/uploads/logos/logo_TRIAL-B07FB584E8.jpg',	'{\"pos\": [\"square\", \"toast\"], \"social\": [\"instagram\", \"facebook\"], \"delivery\": [\"chowdeck\", \"glovo\"], \"whatsapp\": true, \"accounting\": [\"quickbooks\"]}',	'[{\"name\": \"Amaka Obi\", \"role\": \"manager\"}, {\"name\": \"Tunde Bello\", \"role\": \"cashier\"}, {\"name\": \"Ngozi Eze\", \"role\": \"kitchen_staff\"}]',	'friendly',	'English',	'[\"Yoruba\", \"Pidgin\"]',	'[\"increase_orders\", \"reduce_wait_time\", \"automate_whatsapp\"]',	'{\"friday\": {\"open\": \"08:00\", \"close\": \"23:00\"}, \"monday\": {\"open\": \"08:00\", \"close\": \"22:00\"}, \"sunday\": {\"open\": \"10:00\", \"close\": \"20:00\"}, \"tuesday\": {\"open\": \"08:00\", \"close\": \"22:00\"}, \"saturday\": {\"open\": \"09:00\", \"close\": \"23:00\"}, \"thursday\": {\"open\": \"08:00\", \"close\": \"22:00\"}, \"wednesday\": {\"open\": \"08:00\", \"close\": \"22:00\"}}',	9,	'2026-06-02',	'Prefer Not To Say',	'Lounge',	NULL,	'Tty',	'ccjitters.com',	'Web',	49000.00,	'10304279',	'active',	'2026-07-16',	'2026-06-14 08:07:06',	'SUB-A4D843DDF4',	4500,	'2026-06-14 08:07:06',	'2026-06-14 08:07:06',	NULL,	9,	0,	NULL,	NULL,	'https://artisangrills-production.up.railway.app',	'https://admin-artisangrilluxe.vercel.app');

DROP TABLE IF EXISTS `zara_topup_logs`;
CREATE TABLE `zara_topup_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(150) DEFAULT NULL,
  `transaction_id` varchar(200) DEFAULT NULL,
  `pack_id` varchar(50) DEFAULT NULL,
  `credits` int DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `zara_topup_logs` (`id`, `email`, `transaction_id`, `pack_id`, `credits`, `amount`, `created_at`) VALUES
(6,	'powells@ccjitters.com',	'10255700',	'starter',	500,	52250.00,	'2026-05-27 04:49:06'),
(7,	'powells@ccjitters.com',	'10264678',	'popular',	3000,	280500.00,	'2026-05-31 05:25:04'),
(8,	'powells@ccjitters.com',	'10264700',	'starter',	500,	52250.00,	'2026-05-31 06:05:40'),
(9,	'powells@ccjitters.com',	'10264810',	'starter',	500,	52250.00,	'2026-05-31 07:35:43'),
(10,	'Wsamson630@gmail.com',	'10265121',	'starter',	500,	52250.00,	'2026-05-31 11:00:36'),
(11,	'Wsamson630@gmail.com',	'10265153',	'starter',	500,	52250.00,	'2026-05-31 11:20:08'),
(12,	'powells@ccjitters.com',	'10277370',	'starter',	500,	52250.00,	'2026-06-05 14:30:00'),
(13,	'Wsamson630@gmail.com',	'10301022',	'starter',	500,	52250.00,	'2026-06-15 13:22:00'),
(14,	'Wsamson630@gmail.com',	'10301437',	'starter',	500,	52250.00,	'2026-06-15 16:48:40');

-- 2026-06-21 09:39:20 UTC
