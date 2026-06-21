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


-- 2026-06-21 20:40:22 UTC
