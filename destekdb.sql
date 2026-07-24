-- --------------------------------------------------------
-- Sunucu:                       127.0.0.1
-- Sunucu sürümü:                8.4.3 - MySQL Community Server - GPL
-- Sunucu İşletim Sistemi:       Win64
-- HeidiSQL Sürüm:               12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- destek_as için veritabanı yapısı dökülüyor
CREATE DATABASE IF NOT EXISTS `destek_as` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `destek_as`;

-- tablo yapısı dökülüyor destek_as.agent_skills
CREATE TABLE IF NOT EXISTS `agent_skills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `skill_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proficiency_level` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `agent_skills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.agent_skills: ~10 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `agent_skills` (`id`, `user_id`, `skill_name`, `proficiency_level`) VALUES
	(9, 59, 'Veteriner Klinik Otomasyonu', 5),
	(10, 60, 'Restoran Sipariş Otomasyonu', 5),
	(11, 61, 'E-Ticaret Entegrasyon Sistemi', 5),
	(12, 62, 'Veteriner Klinik Otomasyonu', 5),
	(13, 63, 'Otel Rezervasyon Sistemi', 5),
	(14, 64, 'Restoran Sipariş Otomasyonu', 5),
	(18, 65, 'E-Ticaret Entegrasyon Sistemi', 1),
	(19, 66, 'Otel Rezervasyon Sistemi', 1),
	(20, 67, 'Veteriner Klinik Otomasyonu', 1),
	(21, 68, 'Restoran Sipariş Otomasyonu', 1);

-- tablo yapısı dökülüyor destek_as.announcements
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `target_audience` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'All',
  `target_ids` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.announcements: ~1 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `announcements` (`id`, `company_id`, `title`, `content`, `type`, `target_audience`, `target_ids`, `created_at`, `updated_at`) VALUES
	(1, 1, 'DENEME', 'selamlar deneme', 'General', 'Staff', NULL, '2026-07-22 10:57:05', '2026-07-22 10:57:05');

-- tablo yapısı dökülüyor destek_as.api_keys
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `api_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scopes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '*',
  `rate_limit` int DEFAULT '60',
  `ip_restrictions` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','passive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `api_keys_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.api_keys: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.article_feedback
CREATE TABLE IF NOT EXISTS `article_feedback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `customer_user_id` int DEFAULT NULL,
  `is_helpful` tinyint(1) NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`),
  KEY `user_id` (`user_id`),
  KEY `customer_user_id` (`customer_user_id`),
  CONSTRAINT `article_feedback_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `knowledge_base_articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `article_feedback_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `article_feedback_ibfk_3` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.article_feedback: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `ip_address` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `audit_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=453 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.audit_logs: ~451 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `audit_logs` (`id`, `company_id`, `user_id`, `action`, `record_type`, `record_id`, `ip_address`, `old_value`, `new_value`, `created_at`) VALUES
	(1, NULL, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-21 12:33:09'),
	(2, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-21 13:04:55'),
	(3, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-21 13:05:03'),
	(4, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-21 13:58:47'),
	(5, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-21 13:59:00'),
	(6, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-21 14:31:06'),
	(7, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-21 14:39:52'),
	(8, 1, 2, 'Sisteme giriş yapıldı', 'users', 2, '::1', NULL, NULL, '2026-07-21 14:40:03'),
	(9, 1, 2, 'Sistemden çıkış yapıldı', 'users', 2, '::1', NULL, NULL, '2026-07-21 14:42:40'),
	(10, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-21 14:42:49'),
	(11, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-21 14:42:50'),
	(12, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-21 14:42:55'),
	(13, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-21 14:47:52'),
	(14, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 06:06:23'),
	(15, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 06:06:32'),
	(16, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 06:06:36'),
	(17, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 06:14:01'),
	(18, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 06:14:03'),
	(19, 1, 3, 'Yeni destek talebi oluşturuldu: #YEB-2026-000001', 'tickets', 1, '::1', NULL, NULL, '2026-07-22 06:14:28'),
	(20, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 06:14:31'),
	(21, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 06:14:33'),
	(22, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 06:14:44'),
	(23, 1, 53, 'Sisteme giriş yapıldı', 'users', 53, '::1', NULL, NULL, '2026-07-22 06:26:01'),
	(24, 1, 53, 'Sistemden çıkış yapıldı', 'users', 53, '::1', NULL, NULL, '2026-07-22 06:26:39'),
	(25, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 06:26:40'),
	(26, 1, 1, 'Bilet yazışması eklendi. Tip: public', 'tickets', 1, '::1', NULL, NULL, '2026-07-22 06:27:46'),
	(27, 1, 1, 'Bilet yazışması eklendi. Tip: public', 'tickets', 1, '::1', NULL, NULL, '2026-07-22 06:29:24'),
	(28, 1, 1, 'Bilet yazışması eklendi. Tip: public', 'tickets', 1, '::1', NULL, NULL, '2026-07-22 06:29:51'),
	(29, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 06:29:57'),
	(30, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 06:29:58'),
	(31, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 06:30:58'),
	(32, 1, 53, 'Sisteme giriş yapıldı', 'users', 53, '::1', NULL, NULL, '2026-07-22 06:31:04'),
	(33, 1, 53, 'Sistemden çıkış yapıldı', 'users', 53, '::1', NULL, NULL, '2026-07-22 06:31:16'),
	(34, 1, 55, 'Sisteme giriş yapıldı', 'users', 55, '::1', NULL, NULL, '2026-07-22 06:31:39'),
	(35, 1, 55, 'Sistemden çıkış yapıldı', 'users', 55, '::1', NULL, NULL, '2026-07-22 06:31:55'),
	(36, 1, 56, 'Sisteme giriş yapıldı', 'users', 56, '::1', NULL, NULL, '2026-07-22 06:32:01'),
	(37, 1, 56, 'Sistemden çıkış yapıldı', 'users', 56, '::1', NULL, NULL, '2026-07-22 06:32:09'),
	(38, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 06:32:10'),
	(39, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 06:32:14'),
	(40, 1, 53, 'Sisteme giriş yapıldı', 'users', 53, '::1', NULL, NULL, '2026-07-22 06:33:55'),
	(41, 1, 53, 'Sistemden çıkış yapıldı', 'users', 53, '::1', NULL, NULL, '2026-07-22 06:33:58'),
	(42, 1, 55, 'Sisteme giriş yapıldı', 'users', 55, '::1', NULL, NULL, '2026-07-22 06:34:27'),
	(43, 1, 55, 'Sistemden çıkış yapıldı', 'users', 55, '::1', NULL, NULL, '2026-07-22 06:36:05'),
	(44, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 06:36:07'),
	(45, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 06:36:09'),
	(46, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 06:36:13'),
	(47, 1, 4, 'Yeni destek talebi oluşturuldu: #YEB-2026-000002', 'tickets', 2, '::1', NULL, NULL, '2026-07-22 06:36:57'),
	(48, 1, 4, 'Ticket otomatik olarak teknisyene atandı. Teknisyen ID: 53', 'tickets', 2, '::1', NULL, NULL, '2026-07-22 06:36:57'),
	(49, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 06:37:03'),
	(50, 1, 53, 'Sisteme giriş yapıldı', 'users', 53, '::1', NULL, NULL, '2026-07-22 06:37:06'),
	(51, 1, 53, 'Sistemden çıkış yapıldı', 'users', 53, '::1', NULL, NULL, '2026-07-22 06:38:49'),
	(52, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 06:51:18'),
	(53, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 06:51:43'),
	(54, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 06:51:52'),
	(55, 1, 9, 'Yeni destek talebi oluşturuldu: #YEB-2026-000003', 'tickets', 3, '::1', NULL, NULL, '2026-07-22 06:52:30'),
	(56, 1, 9, 'Ticket otomatik olarak teknisyene atandı. Teknisyen ID: 56', 'tickets', 3, '::1', NULL, NULL, '2026-07-22 06:52:30'),
	(57, 1, 9, 'Yeni destek talebi oluşturuldu: #YEB-2026-000004', 'tickets', 4, '::1', NULL, NULL, '2026-07-22 06:59:34'),
	(58, 1, 9, 'Ticket otomatik olarak teknisyene atandı. Teknisyen ID: 55', 'tickets', 4, '::1', NULL, NULL, '2026-07-22 06:59:34'),
	(59, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 07:00:55'),
	(60, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:00:57'),
	(61, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:20:37'),
	(62, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 07:20:41'),
	(63, 1, 3, 'Yeni destek talebi oluşturuldu: #YEB-2026-000005', 'tickets', 5, '::1', NULL, NULL, '2026-07-22 07:21:10'),
	(64, 1, 3, 'Ticket otomatik olarak teknisyene atandı. Teknisyen ID: 54', 'tickets', 5, '::1', NULL, NULL, '2026-07-22 07:21:10'),
	(65, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 07:21:13'),
	(66, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:21:17'),
	(67, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:21:57'),
	(68, 1, 57, 'Sisteme giriş yapıldı', 'users', 57, '::1', NULL, NULL, '2026-07-22 07:22:42'),
	(69, 1, 57, 'Sistemden çıkış yapıldı', 'users', 57, '::1', NULL, NULL, '2026-07-22 07:22:46'),
	(70, 1, 58, 'Sisteme giriş yapıldı', 'users', 58, '::1', NULL, NULL, '2026-07-22 07:22:57'),
	(71, 1, 58, 'Sistemden çıkış yapıldı', 'users', 58, '::1', NULL, NULL, '2026-07-22 07:23:05'),
	(72, 1, 53, 'Sisteme giriş yapıldı', 'users', 53, '::1', NULL, NULL, '2026-07-22 07:23:14'),
	(74, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:25:04'),
	(75, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:26:31'),
	(76, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:26:48'),
	(77, 1, 1, 'Talep tamamlandı olarak işaretlendi.', 'tickets', 5, '::1', NULL, NULL, '2026-07-22 07:27:00'),
	(78, 1, 1, 'Talep tamamlandı olarak işaretlendi.', 'tickets', 4, '::1', NULL, NULL, '2026-07-22 07:27:11'),
	(79, 1, 1, 'Talep tamamlandı olarak işaretlendi.', 'tickets', 3, '::1', NULL, NULL, '2026-07-22 07:27:18'),
	(80, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:27:19'),
	(81, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:27:21'),
	(82, 1, 1, 'Talep tamamlandı olarak işaretlendi.', 'tickets', 2, '::1', NULL, NULL, '2026-07-22 07:27:26'),
	(83, 1, 1, 'Talep tamamlandı olarak işaretlendi.', 'tickets', 1, '::1', NULL, NULL, '2026-07-22 07:27:31'),
	(84, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:27:46'),
	(85, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 07:27:51'),
	(86, 1, 9, 'Yeni destek talebi oluşturuldu: #YEB-2026-000006', 'tickets', 6, '::1', NULL, NULL, '2026-07-22 07:28:24'),
	(87, 1, 9, 'Ticket otomatik olarak teknisyene atandı. Teknisyen ID: 61', 'tickets', 6, '::1', NULL, NULL, '2026-07-22 07:28:24'),
	(88, 1, 9, 'Yeni destek talebi oluşturuldu: #YEB-2026-000007', 'tickets', 7, '::1', NULL, NULL, '2026-07-22 07:28:44'),
	(89, 1, 9, 'Ticket otomatik olarak teknisyene atandı. Teknisyen ID: 62', 'tickets', 7, '::1', NULL, NULL, '2026-07-22 07:28:44'),
	(90, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 07:28:47'),
	(91, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:28:50'),
	(92, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:38:20'),
	(93, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 07:39:10'),
	(94, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 07:39:43'),
	(95, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:39:44'),
	(96, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:39:54'),
	(97, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 07:39:56'),
	(98, 1, 4, 'Yeni destek talebi oluşturuldu: #YEB-2026-000008', 'tickets', 8, '::1', NULL, NULL, '2026-07-22 07:40:05'),
	(99, 1, 4, 'Ticket otomatik olarak teknisyene atandı. Teknisyen ID: 60', 'tickets', 8, '::1', NULL, NULL, '2026-07-22 07:40:05'),
	(100, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 07:40:08'),
	(101, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:40:12'),
	(102, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:40:27'),
	(103, 1, 59, 'Sisteme giriş yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-22 07:40:33'),
	(104, 1, 59, 'Sistemden çıkış yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-22 07:40:36'),
	(105, 1, 60, 'Sisteme giriş yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-22 07:40:37'),
	(106, 1, 60, 'Talep tamamlandı olarak işaretlendi ve müşteri onayına sunuldu.', 'tickets', 8, '::1', NULL, NULL, '2026-07-22 07:40:49'),
	(107, 1, 60, 'Bilet yazışması eklendi. Tip: public', 'tickets', 8, '::1', NULL, NULL, '2026-07-22 07:41:19'),
	(108, 1, 60, 'Sistemden çıkış yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-22 07:43:02'),
	(109, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 07:43:03'),
	(110, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 07:44:00'),
	(111, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 07:45:58'),
	(112, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 07:52:49'),
	(113, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:52:51'),
	(114, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:52:54'),
	(115, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 07:52:57'),
	(116, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 08:05:34'),
	(117, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 08:05:36'),
	(118, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 08:06:36'),
	(119, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 08:06:39'),
	(120, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 08:06:45'),
	(121, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 08:06:47'),
	(122, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 08:53:03'),
	(123, 1, 59, 'Sisteme giriş yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-22 08:53:06'),
	(124, 1, 59, 'Sistemden çıkış yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-22 08:57:40'),
	(125, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 08:57:42'),
	(126, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 09:00:09'),
	(127, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 09:08:57'),
	(128, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 10:56:26'),
	(129, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 10:56:28'),
	(130, 1, 1, 'Yeni duyuru yayınlandı: DENEME', 'announcements', 1, '::1', NULL, NULL, '2026-07-22 10:57:05'),
	(131, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 10:57:09'),
	(132, 1, 60, 'Sisteme giriş yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-22 10:57:11'),
	(133, 1, 60, 'Yöneticiye yeni personel raporu gönderildi: gün sonu', 'staff_reports', 1, '::1', NULL, NULL, '2026-07-22 11:00:39'),
	(134, 1, 60, 'Sistemden çıkış yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-22 11:17:21'),
	(135, 1, 5, 'Sisteme giriş yapıldı', 'users', 5, '::1', NULL, NULL, '2026-07-22 11:17:34'),
	(136, 1, 5, 'Sistemden çıkış yapıldı', 'users', 5, '::1', NULL, NULL, '2026-07-22 11:22:42'),
	(137, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 11:22:48'),
	(138, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 11:23:21'),
	(139, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 11:23:24'),
	(140, 1, 3, 'Yeni destek talebi oluşturuldu: #YEB-2026-000009', 'tickets', 9, '::1', NULL, NULL, '2026-07-22 11:39:31'),
	(141, 1, 3, 'Ticket otomatik olarak teknisyene atandı. Teknisyen ID: 63', 'tickets', 9, '::1', NULL, NULL, '2026-07-22 11:39:31'),
	(142, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 11:39:36'),
	(143, 1, 61, 'Sisteme giriş yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-22 11:39:43'),
	(144, 1, 61, 'Sistemden çıkış yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-22 11:39:47'),
	(145, 1, 62, 'Sisteme giriş yapıldı', 'users', 62, '::1', NULL, NULL, '2026-07-22 11:39:48'),
	(146, 1, 62, 'Sistemden çıkış yapıldı', 'users', 62, '::1', NULL, NULL, '2026-07-22 11:39:50'),
	(147, 1, 64, 'Sisteme giriş yapıldı', 'users', 64, '::1', NULL, NULL, '2026-07-22 11:39:53'),
	(148, 1, 64, 'Sistemden çıkış yapıldı', 'users', 64, '::1', NULL, NULL, '2026-07-22 11:39:55'),
	(149, 1, 61, 'Sisteme giriş yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-22 11:39:56'),
	(150, 1, 61, 'Sistemden çıkış yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-22 11:40:23'),
	(151, 1, 61, 'Sisteme giriş yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-22 11:40:25'),
	(152, 1, 61, 'Sistemden çıkış yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-22 11:40:32'),
	(153, 1, 62, 'Sisteme giriş yapıldı', 'users', 62, '::1', NULL, NULL, '2026-07-22 11:40:33'),
	(154, 1, 62, 'Sistemden çıkış yapıldı', 'users', 62, '::1', NULL, NULL, '2026-07-22 11:40:47'),
	(155, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 11:40:50'),
	(156, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 11:41:05'),
	(157, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 11:49:09'),
	(158, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 11:51:15'),
	(159, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 11:51:18'),
	(160, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 11:53:13'),
	(161, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 11:54:32'),
	(162, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 11:54:56'),
	(163, 1, 61, 'Sisteme giriş yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-22 11:54:58'),
	(164, 1, 61, 'Talep tamamlandı olarak işaretlendi ve müşteri onayına sunuldu.', 'tickets', 6, '::1', NULL, NULL, '2026-07-22 11:55:03'),
	(165, 1, 61, 'Talep meta verileri güncellendi.', 'tickets', 6, '::1', NULL, NULL, '2026-07-22 11:55:05'),
	(166, 1, 61, 'Sistemden çıkış yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-22 11:55:08'),
	(167, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 11:55:09'),
	(168, 1, 9, 'Müşteri çözümü onayladı ve talebi kapattı.', 'tickets', 6, '::1', NULL, NULL, '2026-07-22 11:55:17'),
	(169, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 11:55:23'),
	(170, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 11:55:28'),
	(171, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 11:55:31'),
	(172, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 11:56:21'),
	(173, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 11:56:49'),
	(174, 1, 63, 'Sisteme giriş yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-22 11:56:50'),
	(175, 1, 63, 'Bilet yazışması eklendi. Tip: public', 'tickets', 9, '::1', NULL, NULL, '2026-07-22 11:57:20'),
	(176, 1, 63, 'Sistemden çıkış yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-22 11:57:22'),
	(177, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 11:57:25'),
	(178, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 11:57:33'),
	(179, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 11:57:34'),
	(180, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 11:57:38'),
	(181, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 11:57:39'),
	(182, 1, 3, 'Bilet yazışması eklendi. Tip: public', 'tickets', 9, '::1', NULL, NULL, '2026-07-22 11:57:54'),
	(183, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 11:57:55'),
	(184, 1, 63, 'Sisteme giriş yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-22 11:58:00'),
	(185, 1, 63, 'Talep tamamlandı olarak işaretlendi ve müşteri onayına sunuldu.', 'tickets', 9, '::1', NULL, NULL, '2026-07-22 11:58:05'),
	(186, 1, 63, 'Bilet yazışması eklendi. Tip: public', 'tickets', 9, '::1', NULL, NULL, '2026-07-22 11:58:32'),
	(187, 1, 63, 'Sistemden çıkış yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-22 11:58:38'),
	(188, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 11:58:42'),
	(189, 1, 3, 'Müşteri çözümü onayladı ve talebi kapattı.', 'tickets', 9, '::1', NULL, NULL, '2026-07-22 11:59:06'),
	(190, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-22 12:01:46'),
	(191, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 12:02:07'),
	(192, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-22 12:02:09'),
	(193, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 12:02:12'),
	(194, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 12:12:13'),
	(195, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 12:12:15'),
	(196, 1, 4, 'Yeni destek talebi oluşturuldu: #YEB-2026-000010', 'tickets', 10, '::1', NULL, NULL, '2026-07-22 12:15:15'),
	(197, 1, 4, 'Ticket otomatik olarak teknisyene atandı. Teknisyen ID: 59', 'tickets', 10, '::1', NULL, NULL, '2026-07-22 12:15:15'),
	(198, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 12:17:08'),
	(199, 1, 63, 'Sisteme giriş yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-22 12:17:12'),
	(200, 1, 63, 'Sistemden çıkış yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-22 12:17:25'),
	(201, 1, 59, 'Sisteme giriş yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-22 12:17:30'),
	(202, 1, 59, 'Talep tamamlandı olarak işaretlendi ve müşteri onayına sunuldu.', 'tickets', 10, '::1', NULL, NULL, '2026-07-22 12:17:47'),
	(203, 1, 59, 'Talep tamamlandı olarak işaretlendi ve müşteri onayına sunuldu.', 'tickets', 10, '::1', NULL, NULL, '2026-07-22 12:17:49'),
	(204, 1, 59, 'Sistemden çıkış yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-22 12:18:12'),
	(205, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 12:18:18'),
	(206, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-22 12:19:32'),
	(207, 1, 59, 'Sisteme giriş yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-22 12:19:33'),
	(208, 1, 59, 'Sistemden çıkış yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-22 12:19:50'),
	(209, 1, 59, 'Sisteme giriş yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-22 12:19:53'),
	(210, 1, 59, 'Sistemden çıkış yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-22 12:59:01'),
	(211, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 12:59:02'),
	(212, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 13:25:08'),
	(213, 1, 60, 'Sisteme giriş yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-22 13:25:10'),
	(214, 1, 60, 'Sistemden çıkış yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-22 14:20:07'),
	(215, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-22 14:20:08'),
	(216, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-23 06:16:36'),
	(217, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-23 06:20:34'),
	(218, 1, 60, 'Sisteme giriş yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 06:20:35'),
	(219, 1, 60, 'Yeni otomatik mesaj şablonu eklendi: KARŞILAMA MESAJI', 'canned_responses', 1, '::1', NULL, NULL, '2026-07-23 06:21:05'),
	(220, 1, 60, 'Talep tamamlandı olarak işaretlendi ve müşteri onayına sunuldu.', 'tickets', 8, '::1', NULL, NULL, '2026-07-23 06:28:48'),
	(221, 1, 60, 'Sistemden çıkış yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 06:33:37'),
	(222, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 06:33:38'),
	(223, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 06:33:39'),
	(224, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-23 06:33:40'),
	(225, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-23 06:33:44'),
	(226, 1, 63, 'Sisteme giriş yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 06:33:51'),
	(227, 1, 63, 'Sistemden çıkış yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 06:33:56'),
	(228, 1, 60, 'Sisteme giriş yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 06:33:57'),
	(229, 1, 60, 'Bilet yazışması eklendi. Tip: public', 'tickets', 8, '::1', NULL, NULL, '2026-07-23 06:34:07'),
	(230, 1, 60, 'Sistemden çıkış yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 06:40:03'),
	(231, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 06:40:04'),
	(232, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 06:42:22'),
	(233, 1, 5, 'Sisteme giriş yapıldı', 'users', 5, '::1', NULL, NULL, '2026-07-23 06:42:24'),
	(234, 1, 5, 'Yeni destek talebi oluşturuldu: #YEB-2026-000011', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 06:43:01'),
	(235, 1, 5, 'Sistemden çıkış yapıldı', 'users', 5, '::1', NULL, NULL, '2026-07-23 06:43:04'),
	(236, 1, 61, 'Sisteme giriş yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-23 06:43:10'),
	(237, 1, 61, 'Sistemden çıkış yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-23 06:43:15'),
	(238, 1, 62, 'Sisteme giriş yapıldı', 'users', 62, '::1', NULL, NULL, '2026-07-23 06:43:17'),
	(239, 1, 62, 'Sistemden çıkış yapıldı', 'users', 62, '::1', NULL, NULL, '2026-07-23 06:43:19'),
	(240, 1, 64, 'Sisteme giriş yapıldı', 'users', 64, '::1', NULL, NULL, '2026-07-23 06:43:20'),
	(241, 1, 64, 'Sistemden çıkış yapıldı', 'users', 64, '::1', NULL, NULL, '2026-07-23 06:43:23'),
	(242, 1, 63, 'Sisteme giriş yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 06:43:24'),
	(243, 1, 63, 'Sistemden çıkış yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 06:43:33'),
	(244, 1, 60, 'Sisteme giriş yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 06:43:34'),
	(245, 1, 60, 'Sistemden çıkış yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 06:43:36'),
	(246, 1, 59, 'Sisteme giriş yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-23 06:43:37'),
	(247, 1, 59, 'Sistemden çıkış yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-23 06:43:45'),
	(248, 1, 5, 'Sisteme giriş yapıldı', 'users', 5, '::1', NULL, NULL, '2026-07-23 06:43:55'),
	(249, 1, 5, 'Sistemden çıkış yapıldı', 'users', 5, '::1', NULL, NULL, '2026-07-23 06:44:07'),
	(250, 1, 59, 'Sisteme giriş yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-23 06:44:10'),
	(251, 1, 59, 'Talep tamamlandı olarak işaretlendi ve müşteri onayına sunuldu.', 'tickets', 10, '::1', NULL, NULL, '2026-07-23 06:44:16'),
	(252, 1, 59, 'Sistemden çıkış yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-23 06:44:20'),
	(253, 1, 60, 'Sisteme giriş yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 06:44:22'),
	(254, 1, 60, 'Talep tamamlandı olarak işaretlendi ve müşteri onayına sunuldu.', 'tickets', 8, '::1', NULL, NULL, '2026-07-23 06:44:32'),
	(255, 1, 60, 'Sistemden çıkış yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 06:44:41'),
	(256, 1, 61, 'Sisteme giriş yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-23 06:44:43'),
	(257, 1, 61, 'Sistemden çıkış yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-23 06:44:49'),
	(258, 1, 62, 'Sisteme giriş yapıldı', 'users', 62, '::1', NULL, NULL, '2026-07-23 06:44:51'),
	(259, 1, 62, 'Sistemden çıkış yapıldı', 'users', 62, '::1', NULL, NULL, '2026-07-23 06:45:53'),
	(260, 1, 61, 'Sisteme giriş yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-23 06:45:55'),
	(261, 1, 61, 'Sistemden çıkış yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-23 06:45:57'),
	(262, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 06:45:58'),
	(263, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 06:46:08'),
	(264, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 06:46:09'),
	(265, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 06:46:15'),
	(266, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 06:46:17'),
	(267, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 07:00:42'),
	(268, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 07:00:43'),
	(269, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 07:00:46'),
	(270, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 07:21:04'),
	(271, 1, 1, 'Yeni kullanıcı eklendi: mehmet@gmail.com', 'users', 65, '::1', NULL, NULL, '2026-07-23 07:23:32'),
	(272, 1, 1, 'Yeni kullanıcı eklendi: ali@gmail.com', 'users', 66, '::1', NULL, NULL, '2026-07-23 07:24:22'),
	(273, 1, 1, 'Yeni kullanıcı eklendi: mustafa@gmail.com', 'users', 67, '::1', NULL, NULL, '2026-07-23 07:25:05'),
	(274, 1, 1, 'Kullanıcı güncellendi: mehmet@gmail.com', 'users', 65, '::1', NULL, NULL, '2026-07-23 07:25:11'),
	(275, 1, 1, 'Kullanıcı güncellendi: ali@gmail.com', 'users', 66, '::1', NULL, NULL, '2026-07-23 07:25:13'),
	(276, 1, 1, 'Kullanıcı güncellendi: mustafa@gmail.com', 'users', 67, '::1', NULL, NULL, '2026-07-23 07:25:15'),
	(277, 1, 1, 'Yeni kullanıcı eklendi: enes@gmail.com', 'users', 68, '::1', NULL, NULL, '2026-07-23 07:25:56'),
	(278, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 07:26:04'),
	(279, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 07:26:10'),
	(280, 1, 9, 'Yeni destek talebi oluşturuldu: #YEB-2026-000012', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 07:28:27'),
	(281, 1, 9, 'Ticket otomatik olarak teknisyene atandı. Teknisyen ID: 68', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 07:28:27'),
	(282, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 07:28:32'),
	(283, 1, 67, 'Sisteme giriş yapıldı', 'users', 67, '::1', NULL, NULL, '2026-07-23 07:28:42'),
	(284, 1, 67, 'Sistemden çıkış yapıldı', 'users', 67, '::1', NULL, NULL, '2026-07-23 07:28:46'),
	(285, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 07:28:48'),
	(286, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 07:28:58'),
	(287, 1, 68, 'Sisteme giriş yapıldı', 'users', 68, '::1', NULL, NULL, '2026-07-23 07:29:07'),
	(288, 1, 68, 'Sistemden çıkış yapıldı', 'users', 68, '::1', NULL, NULL, '2026-07-23 07:29:55'),
	(289, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 07:31:17'),
	(290, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 07:31:40'),
	(291, 1, 5, 'Sisteme giriş yapıldı', 'users', 5, '::1', NULL, NULL, '2026-07-23 07:31:42'),
	(292, 1, 5, 'Sistemden çıkış yapıldı', 'users', 5, '::1', NULL, NULL, '2026-07-23 07:31:49'),
	(293, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-23 07:31:50'),
	(294, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-23 07:31:55'),
	(295, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 07:31:56'),
	(296, 1, 4, 'Müşteri çözümü reddetti ve talebi yeniden açtı.', 'tickets', 10, '::1', NULL, NULL, '2026-07-23 07:32:07'),
	(297, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 07:32:09'),
	(298, 1, 5, 'Sisteme giriş yapıldı', 'users', 5, '::1', NULL, NULL, '2026-07-23 07:32:11'),
	(299, 1, 5, 'Sistemden çıkış yapıldı', 'users', 5, '::1', NULL, NULL, '2026-07-23 07:32:31'),
	(300, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 07:32:32'),
	(301, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 07:33:18'),
	(302, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 07:33:22'),
	(303, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 07:33:27'),
	(304, 1, 68, 'Sisteme giriş yapıldı', 'users', 68, '::1', NULL, NULL, '2026-07-23 07:33:35'),
	(305, 1, 68, 'Bilet yazışması eklendi. Tip: public', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 07:33:44'),
	(306, 1, 68, 'Bilet yazışması eklendi. Tip: public', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 07:33:55'),
	(307, 1, 68, 'Sistemden çıkış yapıldı', 'users', 68, '::1', NULL, NULL, '2026-07-23 07:34:23'),
	(308, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 07:34:25'),
	(309, 1, 9, 'Bilet yazışması eklendi. Tip: public', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 07:34:34'),
	(310, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 07:40:05'),
	(311, 1, 61, 'Sisteme giriş yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-23 07:40:51'),
	(312, 1, 61, 'Sistemden çıkış yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-23 07:42:51'),
	(313, 1, 63, 'Sisteme giriş yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 07:42:54'),
	(314, 1, 63, 'Sistemden çıkış yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 07:42:59'),
	(315, 1, 61, 'Sisteme giriş yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-23 07:43:01'),
	(316, 1, 61, 'Sistemden çıkış yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-23 07:43:11'),
	(317, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 07:43:12'),
	(318, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 07:43:26'),
	(319, 1, 60, 'Sisteme giriş yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 07:43:28'),
	(320, 1, 60, 'Sistemden çıkış yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 07:46:55'),
	(321, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 07:58:17'),
	(322, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 07:58:43'),
	(323, 1, 63, 'Sisteme giriş yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 07:58:45'),
	(324, 1, 63, 'Sistemden çıkış yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 08:01:39'),
	(325, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 08:01:41'),
	(326, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 08:50:49'),
	(327, 1, 60, 'Sisteme giriş yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 08:51:00'),
	(328, 1, 60, 'Sistemden çıkış yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 08:51:20'),
	(329, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 08:51:22'),
	(330, 1, 4, 'Müşteri çözümü onayladı ve talebi kapattı.', 'tickets', 8, '::1', NULL, NULL, '2026-07-23 08:51:26'),
	(331, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 08:51:31'),
	(332, 1, 59, 'Sisteme giriş yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-23 08:51:34'),
	(333, 1, 59, 'Talep tamamlandı olarak işaretlendi ve müşteri onayına sunuldu.', 'tickets', 10, '::1', NULL, NULL, '2026-07-23 08:51:42'),
	(334, 1, 59, 'Sistemden çıkış yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-23 08:51:46'),
	(335, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 08:51:47'),
	(336, 1, 4, 'Müşteri çözümü onayladı ve talebi kapattı.', 'tickets', 10, '::1', NULL, NULL, '2026-07-23 08:51:49'),
	(337, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 08:51:50'),
	(338, 1, 59, 'Sisteme giriş yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-23 08:51:51'),
	(339, 1, 59, 'Sistemden çıkış yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-23 08:54:32'),
	(340, 1, 63, 'Sisteme giriş yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 08:55:20'),
	(341, 1, 63, 'Sistemden çıkış yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 08:56:06'),
	(342, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 08:56:08'),
	(343, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 08:59:48'),
	(344, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 08:59:51'),
	(345, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 09:00:10'),
	(346, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 09:00:37'),
	(347, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 09:00:46'),
	(348, 1, 2, 'Sisteme giriş yapıldı', 'users', 2, '::1', NULL, NULL, '2026-07-23 09:00:52'),
	(349, 1, 2, 'Bilet doğrudan atandı. Bilet ID: 12, Uzman: mehmet tekin', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 09:02:35'),
	(350, 1, 2, 'Bilet doğrudan atandı. Bilet ID: 12, Uzman: Kerem Yazıcı', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 09:02:40'),
	(351, 1, 2, 'Bilet doğrudan atandı. Bilet ID: 12, Uzman: enes diker', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 09:02:43'),
	(352, 1, 2, 'Bilet ataması kaldırıldı. Bilet ID: 12', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 09:02:47'),
	(353, 1, 2, 'Bilet doğrudan atandı. Bilet ID: 12, Uzman: Selin Yılmaz', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 09:05:52'),
	(354, 1, 2, 'Bilet doğrudan atandı. Bilet ID: 12, Uzman: Selim Yönetici', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 09:05:53'),
	(355, 1, 2, 'Bilet doğrudan atandı. Bilet ID: 12, Uzman: musatafa yer', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 09:06:00'),
	(356, 1, 2, 'Sistemden çıkış yapıldı', 'users', 2, '::1', NULL, NULL, '2026-07-23 09:18:09'),
	(357, 1, 60, 'Sisteme giriş yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 09:18:11'),
	(358, 1, 60, 'Sistemden çıkış yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 09:30:17'),
	(359, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 09:30:19'),
	(360, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 09:32:03'),
	(361, 1, 62, 'Sisteme giriş yapıldı', 'users', 62, '::1', NULL, NULL, '2026-07-23 09:32:04'),
	(362, 1, 62, 'Sistemden çıkış yapıldı', 'users', 62, '::1', NULL, NULL, '2026-07-23 09:32:06'),
	(363, 1, 64, 'Sisteme giriş yapıldı', 'users', 64, '::1', NULL, NULL, '2026-07-23 09:32:07'),
	(364, 1, 64, 'Sistemden çıkış yapıldı', 'users', 64, '::1', NULL, NULL, '2026-07-23 09:32:12'),
	(365, 1, 4, 'Sisteme giriş yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 09:32:13'),
	(366, 1, 4, 'Sistemden çıkış yapıldı', 'users', 4, '::1', NULL, NULL, '2026-07-23 09:32:15'),
	(367, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 09:32:16'),
	(368, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 10:32:16'),
	(369, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-23 10:37:42'),
	(370, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-23 10:49:28'),
	(371, 1, 59, 'Sisteme giriş yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-23 10:50:32'),
	(372, 1, 59, 'Sistemden çıkış yapıldı', 'users', 59, '::1', NULL, NULL, '2026-07-23 11:30:18'),
	(373, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 11:30:19'),
	(374, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 11:53:50'),
	(375, 1, 61, 'Sisteme giriş yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-23 11:54:08'),
	(376, 1, 61, 'Sistemden çıkış yapıldı', 'users', 61, '::1', NULL, NULL, '2026-07-23 11:54:13'),
	(377, 1, 5, 'Sisteme giriş yapıldı', 'users', 5, '::1', NULL, NULL, '2026-07-23 11:54:15'),
	(378, 1, 5, 'Yeni destek talebi oluşturuldu: #YEB-2026-000013', 'tickets', 13, '::1', NULL, NULL, '2026-07-23 11:54:54'),
	(379, 1, 5, 'Ticket otomatik olarak teknisyene atandı. Teknisyen ID: 60', 'tickets', 13, '::1', NULL, NULL, '2026-07-23 11:54:54'),
	(380, 1, 5, 'Sistemden çıkış yapıldı', 'users', 5, '::1', NULL, NULL, '2026-07-23 11:55:02'),
	(381, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 11:55:04'),
	(382, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 11:55:14'),
	(383, 1, 60, 'Sisteme giriş yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 11:55:17'),
	(384, 1, 60, 'Talep tamamlandı olarak işaretlendi ve müşteri onayına sunuldu.', 'tickets', 13, '::1', NULL, NULL, '2026-07-23 11:58:38'),
	(385, 1, 60, 'Sistemden çıkış yapıldı', 'users', 60, '::1', NULL, NULL, '2026-07-23 12:11:28'),
	(386, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 12:12:14'),
	(387, 1, 1, 'Müşteri firma güncellendi: Elit Temizlik', 'customers', 50, '::1', NULL, NULL, '2026-07-23 12:13:17'),
	(388, 1, 1, 'Müşteri firma güncellendi: Elit Temizlik', 'customers', 50, '::1', NULL, NULL, '2026-07-23 12:13:22'),
	(389, 1, 1, 'Müşteri firma güncellendi: Elit Temizlik', 'customers', 50, '::1', NULL, NULL, '2026-07-23 12:13:34'),
	(390, 1, 1, 'Yeni proje eklendi: bilgisayarotomasyon', 'categories', 5, '::1', NULL, NULL, '2026-07-23 12:14:04'),
	(391, 1, 1, 'Yeni otomatik mesaj şablonu eklendi: a sorunu', 'canned_responses', 2, '::1', NULL, NULL, '2026-07-23 12:14:57'),
	(392, 1, 1, 'Bilet yazışması eklendi. Tip: public', 'tickets', 13, '::1', NULL, NULL, '2026-07-23 12:15:05'),
	(393, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 12:17:26'),
	(394, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 12:17:29'),
	(395, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 12:18:35'),
	(396, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 12:25:32'),
	(397, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atama bekliyor (Bilet ID: 12)', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 12:25:35'),
	(398, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atama bekliyor (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 12:25:38'),
	(399, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atama bekliyor (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 12:25:43'),
	(400, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Müşteriden bilgi bekleniyor (Bilet ID: 12)', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 12:25:48'),
	(401, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Test ediliyor (Bilet ID: 10)', 'tickets', 10, '::1', NULL, NULL, '2026-07-23 12:25:51'),
	(402, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atama bekliyor (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 12:25:53'),
	(403, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atandı (Bilet ID: 7)', 'tickets', 7, '::1', NULL, NULL, '2026-07-23 12:37:29'),
	(404, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atandı (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 12:37:32'),
	(405, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atama bekliyor (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 12:37:35'),
	(406, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Test ediliyor (Bilet ID: 12)', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 12:37:41'),
	(407, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Test ediliyor (Bilet ID: 12)', 'tickets', 12, '::1', NULL, NULL, '2026-07-23 12:37:46'),
	(408, 1, 1, 'Bilet doğrudan atandı. Bilet ID: 11, Uzman: Kerem Yazıcı', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 12:38:01'),
	(409, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atandı (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 12:38:10'),
	(410, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atandı (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 12:38:12'),
	(411, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atandı (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 12:38:36'),
	(412, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atama bekliyor (Bilet ID: 11)', 'tickets', 11, '127.0.0.1', NULL, NULL, '2026-07-23 12:40:57'),
	(413, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atandı (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 13:08:19'),
	(414, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Müşteriden bilgi bekleniyor (Bilet ID: 10)', 'tickets', 10, '::1', NULL, NULL, '2026-07-23 13:08:30'),
	(415, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 13:09:33'),
	(416, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 13:09:35'),
	(417, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Müşteriden bilgi bekleniyor (Bilet ID: 7)', 'tickets', 7, '::1', NULL, NULL, '2026-07-23 13:09:41'),
	(418, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Müşteriden bilgi bekleniyor (Bilet ID: 7)', 'tickets', 7, '::1', NULL, NULL, '2026-07-23 13:09:47'),
	(419, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atandı (Bilet ID: 7)', 'tickets', 7, '::1', NULL, NULL, '2026-07-23 13:10:47'),
	(420, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atama bekliyor (Bilet ID: 7)', 'tickets', 7, '::1', NULL, NULL, '2026-07-23 13:10:49'),
	(421, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atandı (Bilet ID: 7)', 'tickets', 7, '::1', NULL, NULL, '2026-07-23 13:10:51'),
	(422, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Müşteriden bilgi bekleniyor (Bilet ID: 7)', 'tickets', 7, '::1', NULL, NULL, '2026-07-23 13:10:53'),
	(423, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Test ediliyor (Bilet ID: 7)', 'tickets', 7, '::1', NULL, NULL, '2026-07-23 13:10:55'),
	(424, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Çözüldü (Bilet ID: 7)', 'tickets', 7, '::1', NULL, NULL, '2026-07-23 13:10:57'),
	(425, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 13:11:29'),
	(426, 1, 63, 'Sisteme giriş yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 13:11:31'),
	(427, 1, 63, 'Sistemden çıkış yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 13:15:07'),
	(428, 1, 9, 'Sisteme giriş yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 13:15:08'),
	(429, 1, 9, 'Sistemden çıkış yapıldı', 'users', 9, '::1', NULL, NULL, '2026-07-23 13:18:25'),
	(430, 1, 3, 'Sisteme giriş yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-23 13:18:27'),
	(431, 1, 3, 'Sistemden çıkış yapıldı', 'users', 3, '::1', NULL, NULL, '2026-07-23 13:18:35'),
	(432, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 13:18:36'),
	(433, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Müşteriden bilgi bekleniyor (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 13:18:41'),
	(434, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Test ediliyor (Bilet ID: 10)', 'tickets', 10, '::1', NULL, NULL, '2026-07-23 13:18:42'),
	(435, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Çözüldü (Bilet ID: 10)', 'tickets', 10, '::1', NULL, NULL, '2026-07-23 13:18:44'),
	(436, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Yeni (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 13:18:54'),
	(437, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atama bekliyor (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 13:18:56'),
	(438, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atandı (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 13:18:57'),
	(439, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Müşteriden bilgi bekleniyor (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 13:19:01'),
	(440, 1, 1, 'Sistemden çıkış yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 13:19:13'),
	(441, 1, 63, 'Sisteme giriş yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 13:19:15'),
	(442, 1, 63, 'Bilet durumu Kanban üzerinden güncellendi: Müşteriden bilgi bekleniyor (Bilet ID: 9)', 'tickets', 9, '::1', NULL, NULL, '2026-07-23 13:22:50'),
	(443, 1, 63, 'Bilet durumu Kanban üzerinden güncellendi: Atandı (Bilet ID: 9)', 'tickets', 9, '::1', NULL, NULL, '2026-07-23 13:22:52'),
	(444, 1, 63, 'Bilet durumu Kanban üzerinden güncellendi: Atama bekliyor (Bilet ID: 9)', 'tickets', 9, '::1', NULL, NULL, '2026-07-23 13:22:54'),
	(445, 1, 63, 'Bilet durumu Kanban üzerinden güncellendi: Atandı (Bilet ID: 9)', 'tickets', 9, '::1', NULL, NULL, '2026-07-23 13:22:55'),
	(446, 1, 63, 'Bilet durumu Kanban üzerinden güncellendi: Müşteriden bilgi bekleniyor (Bilet ID: 9)', 'tickets', 9, '::1', NULL, NULL, '2026-07-23 13:22:57'),
	(447, 1, 63, 'Bilet durumu Kanban üzerinden güncellendi: Müşteriden bilgi bekleniyor (Bilet ID: 9)', 'tickets', 9, '::1', NULL, NULL, '2026-07-23 13:22:57'),
	(448, 1, 63, 'Bilet durumu Kanban üzerinden güncellendi: Test ediliyor (Bilet ID: 9)', 'tickets', 9, '::1', NULL, NULL, '2026-07-23 13:22:58'),
	(449, 1, 63, 'Sistemden çıkış yapıldı', 'users', 63, '::1', NULL, NULL, '2026-07-23 13:23:48'),
	(450, 1, 1, 'Sisteme giriş yapıldı', 'users', 1, '::1', NULL, NULL, '2026-07-23 13:23:50'),
	(451, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atandı (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 13:23:58'),
	(452, 1, 1, 'Bilet durumu Kanban üzerinden güncellendi: Atama bekliyor (Bilet ID: 11)', 'tickets', 11, '::1', NULL, NULL, '2026-07-23 13:23:59');

-- tablo yapısı dökülüyor destek_as.canned_responses
CREATE TABLE IF NOT EXISTS `canned_responses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `canned_responses_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.canned_responses: ~2 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `canned_responses` (`id`, `company_id`, `title`, `content`, `created_at`, `updated_at`) VALUES
	(1, 1, 'KARŞILAMA MESAJI', 'SELAMLAR KADEŞ ASDASD', '2026-07-23 06:21:05', '2026-07-23 06:21:05'),
	(2, 1, 'a sorunu', 'asdoasdadasd', '2026-07-23 12:14:57', '2026-07-23 12:14:57');

-- tablo yapısı dökülüyor destek_as.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_department_id` int DEFAULT NULL,
  `default_priority` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Normal',
  `default_agent_id` int DEFAULT NULL,
  `sla_duration` int DEFAULT NULL,
  `canned_response_id` int DEFAULT NULL,
  `status` enum('active','passive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `default_department_id` (`default_department_id`),
  KEY `default_agent_id` (`default_agent_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `categories_ibfk_2` FOREIGN KEY (`default_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `categories_ibfk_3` FOREIGN KEY (`default_agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.categories: ~5 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `categories` (`id`, `company_id`, `name`, `description`, `default_department_id`, `default_priority`, `default_agent_id`, `sla_duration`, `canned_response_id`, `status`, `created_at`, `updated_at`) VALUES
	(1, 1, 'Veteriner Klinik Otomasyonu', 'Veteriner klinikleri için hasta, randevu ve aşı takip sistemi', 1, 'Normal', NULL, NULL, NULL, 'active', '2026-07-22 13:51:59', '2026-07-22 13:51:59'),
	(2, 1, 'Restoran Sipariş Otomasyonu', 'Restoranlar için dijital menü, sipariş ve masa yönetim sistemi', 1, 'Normal', NULL, NULL, NULL, 'active', '2026-07-22 13:51:59', '2026-07-22 13:51:59'),
	(3, 1, 'Otel Rezervasyon Sistemi', 'Oteller için oda, oda servisi ve fatura rezervasyon yönetimi', 1, 'Normal', NULL, NULL, NULL, 'active', '2026-07-22 13:51:59', '2026-07-22 13:51:59'),
	(4, 1, 'E-Ticaret Entegrasyon Sistemi', 'Pazaryerleri ile stok, sipariş ve kargo entegrasyon yazılımı', 1, 'Normal', NULL, NULL, NULL, 'active', '2026-07-22 13:51:59', '2026-07-22 13:51:59'),
	(5, 1, 'bilgisayarotomasyon', 'adsadasda', 1, 'Normal', NULL, NULL, NULL, 'active', '2026-07-23 12:14:04', '2026-07-23 12:14:04');

-- tablo yapısı dökülüyor destek_as.companies
CREATE TABLE IF NOT EXISTS `companies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trade_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_office` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sector` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `working_hours` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '09:00-18:00',
  `default_language` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'tr',
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Europe/Istanbul',
  `status` enum('active','passive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.companies: ~1 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `companies` (`id`, `name`, `trade_name`, `tax_number`, `tax_office`, `phone`, `email`, `website`, `address`, `logo`, `sector`, `contact_person`, `working_hours`, `default_language`, `timezone`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'Destek A.Ş. Yazılım A.Ş.', 'Destek A.Ş. Bilgi Teknolojileri', '1234567890', 'Giresun V.D.', '05016621628', 'info@destek.net', 'destek.net', 'Pazarsuyu Köyü 2. OSB Giresun Teknopark Bulancak / Giresun', NULL, NULL, NULL, '09:00-18:00', 'tr', 'Europe/Istanbul', 'active', '2026-07-21 12:26:05', '2026-07-21 12:26:05');

-- tablo yapısı dökülüyor destek_as.company_settings
CREATE TABLE IF NOT EXISTS `company_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_company_key` (`company_id`,`setting_key`),
  CONSTRAINT `company_settings_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.company_settings: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.customers
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `contract_start_date` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `support_package_id` int DEFAULT NULL,
  `monthly_ticket_limit` int DEFAULT '0',
  `priority_support` tinyint(1) DEFAULT '0',
  `custom_sla_rules` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','passive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `support_package_id` (`support_package_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customers_ibfk_2` FOREIGN KEY (`support_package_id`) REFERENCES `support_packages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.customers: ~50 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `customers` (`id`, `company_id`, `name`, `contact_person`, `phone`, `email`, `address`, `contract_start_date`, `contract_end_date`, `support_package_id`, `monthly_ticket_limit`, `priority_support`, `custom_sla_rules`, `status`, `created_at`, `updated_at`) VALUES
	(1, 1, 'Kuzey Teknoloji', 'Ahmet Kuzey', '0555 483 42 54', 'info@kuzey-teknoloji.com', 'Liman Cad. No: 1 / Merkez', '2026-01-01', '2027-01-01', 1, 100, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(2, 1, 'Bulut Lojistik', 'Mehmet Bulut', '0555 228 98 29', 'info@bulut-lojistik.com', 'Liman Cad. No: 2 / Merkez', '2026-01-01', '2027-01-01', 1, 100, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:45'),
	(3, 1, 'Ege Bilişim', 'Canan Ege', '0555 209 84 26', 'info@ege-bilisim.com', 'Liman Cad. No: 3 / Merkez', '2026-01-01', '2027-01-01', 1, 20, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:45'),
	(4, 1, 'Marmara Gıda', 'Elif Marmara', '0555 202 56 55', 'info@marmara-gida.com', 'Liman Cad. No: 4 / Merkez', '2026-01-01', '2027-01-01', 1, 100, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:45'),
	(5, 1, 'Anadolu Tekstil', 'Mustafa Anadolu', '0555 786 26 46', 'info@anadolu-tekstil.com', 'Liman Cad. No: 5 / Merkez', '2026-01-01', '2027-01-01', 2, 50, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(6, 1, 'Akdeniz Turizm', 'Zeynep Akdeniz', '0555 715 45 39', 'info@akdeniz-turizm.com', 'Liman Cad. No: 6 / Merkez', '2026-01-01', '2027-01-01', 1, 10, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(7, 1, 'Karadeniz Nakliyat', 'Hasan Karadeniz', '0555 546 63 69', 'info@karadeniz-nakliyat.com', 'Liman Cad. No: 7 / Merkez', '2026-01-01', '2027-01-01', 2, 10, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(8, 1, 'Efes Otomotiv', 'Ayşe Efes', '0555 739 96 38', 'info@efes-otomotiv.com', 'Liman Cad. No: 8 / Merkez', '2026-01-01', '2027-01-01', 1, 10, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:45'),
	(9, 1, 'Zirve İnşaat', 'Ali Zirve', '0555 916 46 25', 'info@zirve-İnsaat.com', 'Liman Cad. No: 9 / Merkez', '2026-01-01', '2027-01-01', 1, 10, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(10, 1, 'Hedef Kozmetik', 'Fatma Hedef', '0555 210 60 70', 'info@hedef-kozmetik.com', 'Liman Cad. No: 10 / Merkez', '2026-01-01', '2027-01-01', 1, 100, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(11, 1, 'Mega Enerji', 'İbrahim Mega', '0555 290 55 48', 'info@mega-enerji.com', 'Liman Cad. No: 11 / Merkez', '2026-01-01', '2027-01-01', 1, 100, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(12, 1, 'Özkan Hukuk', 'Hatice Özkan', '0555 110 33 91', 'info@Özkan-hukuk.com', 'Liman Cad. No: 12 / Merkez', '2026-01-01', '2027-01-01', 2, 20, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(13, 1, 'Yıldız Mimarlık', 'Kemal Yıldız', '0555 163 51 66', 'info@yildiz-mimarlik.com', 'Liman Cad. No: 13 / Merkez', '2026-01-01', '2027-01-01', 2, 20, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(14, 1, 'Bursa Plastik', 'Derya Bursa', '0555 557 73 61', 'info@bursa-plastik.com', 'Liman Cad. No: 14 / Merkez', '2026-01-01', '2027-01-01', 2, 20, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(15, 1, 'Güneş Medya', 'Murat Güneş', '0555 133 69 74', 'info@gunes-medya.com', 'Liman Cad. No: 15 / Merkez', '2026-01-01', '2027-01-01', 1, 100, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:45'),
	(16, 1, 'Dost Tarım', 'Selin Dost', '0555 200 18 10', 'info@dost-tarim.com', 'Liman Cad. No: 16 / Merkez', '2026-01-01', '2027-01-01', 2, 50, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(17, 1, 'Mavi Denizcilik', 'Deniz Mavi', '0555 529 87 94', 'info@mavi-denizcilik.com', 'Liman Cad. No: 17 / Merkez', '2026-01-01', '2027-01-01', 2, 50, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(18, 1, 'Hilal Kimya', 'Osman Hilal', '0555 795 98 35', 'info@hilal-kimya.com', 'Liman Cad. No: 18 / Merkez', '2026-01-01', '2027-01-01', 1, 50, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(19, 1, 'Serhat Metal', 'Seda Serhat', '0555 508 19 18', 'info@serhat-metal.com', 'Liman Cad. No: 19 / Merkez', '2026-01-01', '2027-01-01', 2, 50, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(20, 1, 'Doruk Telekom', 'Bülent Doruk', '0555 385 93 69', 'info@doruk-telekom.com', 'Liman Cad. No: 20 / Merkez', '2026-01-01', '2027-01-01', 2, 20, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(21, 1, 'Net Yazılım', 'Nihal Net', '0555 663 42 72', 'info@net-yazilim.com', 'Liman Cad. No: 21 / Merkez', '2026-01-01', '2027-01-01', 1, 50, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(22, 1, 'Uzman Danışmanlık', 'Hakan Uzman', '0555 647 80 27', 'info@uzman-danismanlik.com', 'Liman Cad. No: 22 / Merkez', '2026-01-01', '2027-01-01', 1, 50, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:45'),
	(23, 1, 'Pınar Su', 'Burcu Pınar', '0555 253 49 74', 'info@pinar-su.com', 'Liman Cad. No: 23 / Merkez', '2026-01-01', '2027-01-01', 2, 100, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(24, 1, 'Kanyon Yapı', 'Cem Kanyon', '0555 382 90 42', 'info@kanyon-yapi.com', 'Liman Cad. No: 24 / Merkez', '2026-01-01', '2027-01-01', 1, 100, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(25, 1, 'Aras Kargo', 'Levent Aras', '0555 135 80 22', 'info@aras-kargo.com', 'Liman Cad. No: 25 / Merkez', '2026-01-01', '2027-01-01', 2, 10, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(26, 1, 'Vatan Bilgisayar', 'Gökhan Vatan', '0555 713 89 12', 'info@vatan-bilgisayar.com', 'Liman Cad. No: 26 / Merkez', '2026-01-01', '2027-01-01', 2, 20, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(27, 1, 'Tekno Market', 'Ebru Tekno', '0555 272 31 26', 'info@tekno-market.com', 'Liman Cad. No: 27 / Merkez', '2026-01-01', '2027-01-01', 2, 10, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(28, 1, 'Uzay Havacılık', 'Serkan Uzay', '0555 271 29 56', 'info@uzay-havacilik.com', 'Liman Cad. No: 28 / Merkez', '2026-01-01', '2027-01-01', 1, 50, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(29, 1, 'Birlik Kooperatif', 'Ömer Birlik', '0555 578 78 10', 'info@birlik-kooperatif.com', 'Liman Cad. No: 29 / Merkez', '2026-01-01', '2027-01-01', 1, 20, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(30, 1, 'Karar Matbaa', 'Tugay Karar', '0555 681 44 56', 'info@karar-matbaa.com', 'Liman Cad. No: 30 / Merkez', '2026-01-01', '2027-01-01', 2, 100, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(31, 1, 'Sistem Güvenlik', 'Fatih Sistem', '0555 692 75 50', 'info@sistem-guvenlik.com', 'Liman Cad. No: 31 / Merkez', '2026-01-01', '2027-01-01', 2, 100, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(32, 1, 'Vizyon Eğitim', 'Gözde Vizyon', '0555 600 43 54', 'info@vizyon-egitim.com', 'Liman Cad. No: 32 / Merkez', '2026-01-01', '2027-01-01', 1, 20, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(33, 1, 'Özgür Yayıncılık', 'Yasin Özgür', '0555 779 39 45', 'info@Özgur-yayincilik.com', 'Liman Cad. No: 33 / Merkez', '2026-01-01', '2027-01-01', 2, 50, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(34, 1, 'Bilgi Akademi', 'Tuğba Bilgi', '0555 839 95 75', 'info@bilgi-akademi.com', 'Liman Cad. No: 34 / Merkez', '2026-01-01', '2027-01-01', 1, 50, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(35, 1, 'Seçkin Mobilya', 'Adem Seçkin', '0555 152 24 90', 'info@seckin-mobilya.com', 'Liman Cad. No: 35 / Merkez', '2026-01-01', '2027-01-01', 1, 20, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:45'),
	(36, 1, 'Öncü Factoring', 'Nisa Öncü', '0555 902 64 14', 'info@Öncu-factoring.com', 'Liman Cad. No: 36 / Merkez', '2026-01-01', '2027-01-01', 2, 20, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(37, 1, 'Lider Faktoring', 'Kadir Lider', '0555 110 44 35', 'info@lider-faktoring.com', 'Liman Cad. No: 37 / Merkez', '2026-01-01', '2027-01-01', 1, 100, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(38, 1, 'Merkez Döviz', 'Savaş Merkez', '0555 191 73 66', 'info@merkez-doviz.com', 'Liman Cad. No: 38 / Merkez', '2026-01-01', '2027-01-01', 1, 10, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:45'),
	(39, 1, 'Çağdaş Sağlık', 'Ender Çağdaş', '0555 431 99 71', 'info@Çagdas-saglik.com', 'Liman Cad. No: 39 / Merkez', '2026-01-01', '2027-01-01', 2, 10, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(40, 1, 'Güven Tıp', 'Berna Güven', '0555 592 21 55', 'info@guven-tip.com', 'Liman Cad. No: 40 / Merkez', '2026-01-01', '2027-01-01', 2, 100, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(41, 1, 'Deha Otomasyon', 'Mete Deha', '0555 784 95 66', 'info@deha-otomasyon.com', 'Liman Cad. No: 41 / Merkez', '2026-01-01', '2027-01-01', 1, 20, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(42, 1, 'Focus Reklam', 'Jale Focus', '0555 180 53 89', 'info@focus-reklam.com', 'Liman Cad. No: 42 / Merkez', '2026-01-01', '2027-01-01', 1, 50, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(43, 1, 'Kreatif Tasarım', 'Uğur Kreatif', '0555 658 43 97', 'info@kreatif-tasarim.com', 'Liman Cad. No: 43 / Merkez', '2026-01-01', '2027-01-01', 1, 100, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(44, 1, 'Pusula Harita', 'Tamer Pusula', '0555 918 95 55', 'info@pusula-harita.com', 'Liman Cad. No: 44 / Merkez', '2026-01-01', '2027-01-01', 1, 10, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(45, 1, 'Rota Mühendislik', 'Ceren Rota', '0555 115 62 12', 'info@rota-muhendislik.com', 'Liman Cad. No: 45 / Merkez', '2026-01-01', '2027-01-01', 2, 50, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(46, 1, 'Kare İnşaat', 'Oğuz Kare', '0555 819 28 41', 'info@kare-İnsaat.com', 'Liman Cad. No: 46 / Merkez', '2026-01-01', '2027-01-01', 1, 20, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(47, 1, 'Piramit Dekorasyon', 'Sibel Piramit', '0555 671 43 91', 'info@piramit-dekorasyon.com', 'Liman Cad. No: 47 / Merkez', '2026-01-01', '2027-01-01', 2, 10, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(48, 1, 'Tempo Kurye', 'Koray Tempo', '0555 302 47 86', 'info@tempo-kurye.com', 'Liman Cad. No: 48 / Merkez', '2026-01-01', '2027-01-01', 1, 100, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(49, 1, 'Doğa Peyzaj', 'Aslı Doğa', '0555 352 72 50', 'info@doga-peyzaj.com', 'Liman Cad. No: 49 / Merkez', '2026-01-01', '2027-01-01', 2, 10, 1, NULL, 'active', '2026-07-21 14:30:22', '2026-07-22 08:08:53'),
	(50, 1, 'Elit Temizlik', 'Seçil Elit', '0555 370 25 94', 'info@elit-temizlik.com', 'Liman Cad. No: 50 / Merkez', '2026-01-01', '2027-01-01', 1, 0, 0, NULL, 'active', '2026-07-21 14:30:22', '2026-07-23 12:13:16');

-- tablo yapısı dökülüyor destek_as.customer_categories
CREATE TABLE IF NOT EXISTS `customer_categories` (
  `customer_id` int NOT NULL,
  `category_id` int NOT NULL,
  PRIMARY KEY (`customer_id`,`category_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `customer_categories_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.customer_categories: ~197 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `customer_categories` (`customer_id`, `category_id`) VALUES
	(1, 1),
	(2, 1),
	(3, 1),
	(4, 1),
	(5, 1),
	(6, 1),
	(7, 1),
	(8, 1),
	(9, 1),
	(10, 1),
	(11, 1),
	(12, 1),
	(13, 1),
	(14, 1),
	(15, 1),
	(16, 1),
	(17, 1),
	(18, 1),
	(19, 1),
	(20, 1),
	(21, 1),
	(22, 1),
	(23, 1),
	(24, 1),
	(25, 1),
	(26, 1),
	(27, 1),
	(28, 1),
	(29, 1),
	(30, 1),
	(31, 1),
	(32, 1),
	(33, 1),
	(34, 1),
	(35, 1),
	(36, 1),
	(37, 1),
	(38, 1),
	(39, 1),
	(40, 1),
	(41, 1),
	(42, 1),
	(43, 1),
	(44, 1),
	(45, 1),
	(46, 1),
	(47, 1),
	(48, 1),
	(49, 1),
	(1, 2),
	(2, 2),
	(3, 2),
	(4, 2),
	(5, 2),
	(6, 2),
	(7, 2),
	(8, 2),
	(9, 2),
	(10, 2),
	(11, 2),
	(12, 2),
	(13, 2),
	(14, 2),
	(15, 2),
	(16, 2),
	(17, 2),
	(18, 2),
	(19, 2),
	(20, 2),
	(21, 2),
	(22, 2),
	(23, 2),
	(24, 2),
	(25, 2),
	(26, 2),
	(27, 2),
	(28, 2),
	(29, 2),
	(30, 2),
	(31, 2),
	(32, 2),
	(33, 2),
	(34, 2),
	(35, 2),
	(36, 2),
	(37, 2),
	(38, 2),
	(39, 2),
	(40, 2),
	(41, 2),
	(42, 2),
	(43, 2),
	(44, 2),
	(45, 2),
	(46, 2),
	(47, 2),
	(48, 2),
	(49, 2),
	(50, 2),
	(1, 3),
	(2, 3),
	(3, 3),
	(4, 3),
	(5, 3),
	(6, 3),
	(7, 3),
	(8, 3),
	(9, 3),
	(10, 3),
	(11, 3),
	(12, 3),
	(13, 3),
	(14, 3),
	(15, 3),
	(16, 3),
	(17, 3),
	(18, 3),
	(19, 3),
	(20, 3),
	(21, 3),
	(22, 3),
	(23, 3),
	(24, 3),
	(25, 3),
	(26, 3),
	(27, 3),
	(28, 3),
	(29, 3),
	(30, 3),
	(31, 3),
	(32, 3),
	(33, 3),
	(34, 3),
	(35, 3),
	(36, 3),
	(37, 3),
	(38, 3),
	(39, 3),
	(40, 3),
	(41, 3),
	(42, 3),
	(43, 3),
	(44, 3),
	(45, 3),
	(46, 3),
	(47, 3),
	(48, 3),
	(49, 3),
	(1, 4),
	(2, 4),
	(3, 4),
	(4, 4),
	(5, 4),
	(6, 4),
	(7, 4),
	(8, 4),
	(9, 4),
	(10, 4),
	(11, 4),
	(12, 4),
	(13, 4),
	(14, 4),
	(15, 4),
	(16, 4),
	(17, 4),
	(18, 4),
	(19, 4),
	(20, 4),
	(21, 4),
	(22, 4),
	(23, 4),
	(24, 4),
	(25, 4),
	(26, 4),
	(27, 4),
	(28, 4),
	(29, 4),
	(30, 4),
	(31, 4),
	(32, 4),
	(33, 4),
	(34, 4),
	(35, 4),
	(36, 4),
	(37, 4),
	(38, 4),
	(39, 4),
	(40, 4),
	(41, 4),
	(42, 4),
	(43, 4),
	(44, 4),
	(45, 4),
	(46, 4),
	(47, 4),
	(48, 4),
	(49, 4);

-- tablo yapısı dökülüyor destek_as.customer_ratings
CREATE TABLE IF NOT EXISTS `customer_ratings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `customer_user_id` int NOT NULL,
  `general_satisfaction` int DEFAULT '5',
  `response_speed` int DEFAULT '5',
  `solution_quality` int DEFAULT '5',
  `communication_quality` int DEFAULT '5',
  `agent_attitude` int DEFAULT '5',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `nps_score` int DEFAULT '10',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `customer_user_id` (`customer_user_id`),
  CONSTRAINT `customer_ratings_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_ratings_ibfk_2` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.customer_ratings: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.customer_users
CREATE TABLE IF NOT EXISTS `customer_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'standard',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `customer_users_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.customer_users: ~50 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `customer_users` (`id`, `customer_id`, `user_id`, `role`, `created_at`, `updated_at`) VALUES
	(1, 1, 3, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(2, 2, 4, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(3, 3, 5, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(4, 4, 6, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(5, 5, 7, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(6, 6, 8, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(7, 7, 9, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(8, 8, 10, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(9, 9, 11, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(10, 10, 12, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(11, 11, 13, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(12, 12, 14, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(13, 13, 15, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(14, 14, 16, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(15, 15, 17, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(16, 16, 18, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(17, 17, 19, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(18, 18, 20, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(19, 19, 21, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(20, 20, 22, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(21, 21, 23, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(22, 22, 24, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(23, 23, 25, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(24, 24, 26, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(25, 25, 27, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(26, 26, 28, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(27, 27, 29, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(28, 28, 30, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(29, 29, 31, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(30, 30, 32, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(31, 31, 33, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(32, 32, 34, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(33, 33, 35, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(34, 34, 36, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(35, 35, 37, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(36, 36, 38, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(37, 37, 39, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(38, 38, 40, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(39, 39, 41, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(40, 40, 42, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(41, 41, 43, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(42, 42, 44, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(43, 43, 45, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(44, 44, 46, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(45, 45, 47, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(46, 46, 48, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(47, 47, 49, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(48, 48, 50, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(49, 49, 51, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(50, 50, 52, 'standard', '2026-07-21 14:30:22', '2026-07-21 14:30:22');

-- tablo yapısı dökülüyor destek_as.departments
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_id` int DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `working_hours` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '09:00-18:00',
  `default_priority` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Normal',
  `status` enum('active','passive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `daily_capacity` int DEFAULT '10',
  `assignment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'round_robin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `manager_id` (`manager_id`),
  CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `departments_ibfk_2` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.departments: ~3 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `departments` (`id`, `company_id`, `name`, `description`, `manager_id`, `email`, `working_hours`, `default_priority`, `status`, `daily_capacity`, `assignment_method`, `created_at`, `updated_at`) VALUES
	(1, 1, 'Yazılım Destek', 'Yazılım hataları ve güncelleme destek birimi', NULL, 'yazilim@destek.net', '09:00-18:00', 'Normal', 'active', 10, 'round_robin', '2026-07-21 12:26:05', '2026-07-21 12:26:05'),
	(2, 1, 'Teknik Servis', 'Donanım ve fiziksel arıza destek birimi', NULL, 'teknik@destek.net', '09:00-18:00', 'Yüksek', 'active', 10, 'least_workload', '2026-07-21 12:26:05', '2026-07-21 12:26:05'),
	(3, 1, 'Bilgi İşlem', 'İç IT ve sunucu altyapı yönetim birimi', NULL, 'it@destek.net', '09:00-18:00', 'Kritik', 'active', 10, 'manual', '2026-07-21 12:26:05', '2026-07-21 12:26:05');

-- tablo yapısı dökülüyor destek_as.department_users
CREATE TABLE IF NOT EXISTS `department_users` (
  `department_id` int NOT NULL,
  `user_id` int NOT NULL,
  `is_manager` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`department_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `department_users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `department_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.department_users: ~7 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `department_users` (`department_id`, `user_id`, `is_manager`) VALUES
	(1, 3, 0),
	(1, 59, 0),
	(1, 60, 0),
	(1, 63, 0),
	(2, 61, 0),
	(2, 62, 0),
	(2, 64, 0);

-- tablo yapısı dökülüyor destek_as.email_accounts
CREATE TABLE IF NOT EXISTS `email_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `host` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `port` int DEFAULT '993',
  `protocol` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'IMAP',
  `status` enum('active','passive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `email_accounts_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.email_accounts: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.email_messages
CREATE TABLE IF NOT EXISTS `email_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `sender` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `attachment_paths` text COLLATE utf8mb4_unicode_ci,
  `processed_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `ticket_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `email_messages_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_messages_ibfk_2` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.email_messages: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.escalation_events
CREATE TABLE IF NOT EXISTS `escalation_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `rule_id` int NOT NULL,
  `trigger_time` timestamp NOT NULL,
  `executed_at` timestamp NULL DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `rule_id` (`rule_id`),
  CONSTRAINT `escalation_events_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `escalation_events_ibfk_2` FOREIGN KEY (`rule_id`) REFERENCES `escalation_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.escalation_events: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.escalation_rules
CREATE TABLE IF NOT EXISTS `escalation_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority_id` int NOT NULL,
  `trigger_duration` int NOT NULL,
  `action_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `priority_id` (`priority_id`),
  KEY `target_user_id` (`target_user_id`),
  CONSTRAINT `escalation_rules_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `escalation_rules_ibfk_2` FOREIGN KEY (`priority_id`) REFERENCES `priorities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `escalation_rules_ibfk_3` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.escalation_rules: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.knowledge_base_articles
CREATE TABLE IF NOT EXISTS `knowledge_base_articles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` text COLLATE utf8mb4_unicode_ci,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `view_count` int DEFAULT '0',
  `helpful_votes` int DEFAULT '0',
  `unhelpful_votes` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `knowledge_base_articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `knowledge_base_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.knowledge_base_articles: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.knowledge_base_categories
CREATE TABLE IF NOT EXISTS `knowledge_base_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `knowledge_base_categories_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.knowledge_base_categories: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `customer_user_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `customer_user_id` (`customer_user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.notifications: ~38 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `notifications` (`id`, `user_id`, `customer_user_id`, `title`, `content`, `notification_type`, `is_read`, `created_at`) VALUES
	(1, 63, NULL, 'Yeni Talep Atandı', 'Size yeni bir destek talebi atandı: #YEB-2026-000009.', 'assignment', 1, '2026-07-22 11:39:31'),
	(2, NULL, 3, 'Talep Oluşturuldu', 'Talebiniz (#YEB-2026-000009) başarıyla oluşturuldu.', 'ticket', 0, '2026-07-22 11:39:31'),
	(3, NULL, 9, 'Çözüm Onayı Bekleniyor', 'Talebiniz (#YEB-2026-000006) uzman tarafından tamamlandı olarak işaretlendi ve onayınızı bekliyor.', 'ticket', 0, '2026-07-22 11:55:03'),
	(4, 61, NULL, 'Çözüm Onaylandı', 'Müşteri #YEB-2026-000006 nolu talebin çözümünü onayladı.', 'ticket', 0, '2026-07-22 11:55:17'),
	(5, NULL, 3, 'Yeni Mesaj Var', 'Uzman #YEB-2026-000009 nolu talebinize yeni bir yanıt yazdı.', 'message', 0, '2026-07-22 11:57:20'),
	(6, 63, NULL, 'Yeni Mesaj Var', 'Müşteri #YEB-2026-000009 nolu talebe yeni bir mesaj yazdı.', 'message', 1, '2026-07-22 11:57:54'),
	(7, NULL, 3, 'Çözüm Onayı Bekleniyor', 'Talebiniz (#YEB-2026-000009) uzman tarafından tamamlandı olarak işaretlendi ve onayınızı bekliyor.', 'ticket', 0, '2026-07-22 11:58:05'),
	(8, NULL, 3, 'Yeni Mesaj Var', 'Uzman #YEB-2026-000009 nolu talebinize yeni bir yanıt yazdı.', 'message', 0, '2026-07-22 11:58:32'),
	(9, 63, NULL, 'Çözüm Onaylandı', 'Müşteri #YEB-2026-000009 nolu talebin çözümünü onayladı.', 'ticket', 1, '2026-07-22 11:59:06'),
	(10, 59, NULL, 'Yeni Talep Atandı', 'Size yeni bir destek talebi atandı: #YEB-2026-000010.', 'assignment', 0, '2026-07-22 12:15:15'),
	(11, NULL, 4, 'Talep Oluşturuldu', 'Talebiniz (#YEB-2026-000010) başarıyla oluşturuldu.', 'ticket', 0, '2026-07-22 12:15:15'),
	(12, NULL, 4, 'Çözüm Onayı Bekleniyor', 'Talebiniz (#YEB-2026-000010) uzman tarafından tamamlandı olarak işaretlendi ve onayınızı bekliyor.', 'ticket', 0, '2026-07-22 12:17:47'),
	(13, NULL, 4, 'Çözüm Onayı Bekleniyor', 'Talebiniz (#YEB-2026-000010) uzman tarafından tamamlandı olarak işaretlendi ve onayınızı bekliyor.', 'ticket', 0, '2026-07-22 12:17:49'),
	(14, NULL, 4, 'Çözüm Onayı Bekleniyor', 'Talebiniz (#YEB-2026-000008) uzman tarafından tamamlandı olarak işaretlendi ve onayınızı bekliyor.', 'ticket', 0, '2026-07-23 06:28:48'),
	(15, NULL, 4, 'Yeni Mesaj Var', 'Uzman #YEB-2026-000008 nolu talebinize yeni bir yanıt yazdı.', 'message', 0, '2026-07-23 06:34:07'),
	(16, NULL, 5, 'Talep Oluşturuldu', 'Talebiniz (#YEB-2026-000011) başarıyla oluşturuldu.', 'ticket', 0, '2026-07-23 06:43:01'),
	(17, NULL, 4, 'Çözüm Onayı Bekleniyor', 'Talebiniz (#YEB-2026-000010) uzman tarafından tamamlandı olarak işaretlendi ve onayınızı bekliyor.', 'ticket', 0, '2026-07-23 06:44:16'),
	(18, NULL, 4, 'Çözüm Onayı Bekleniyor', 'Talebiniz (#YEB-2026-000008) uzman tarafından tamamlandı olarak işaretlendi ve onayınızı bekliyor.', 'ticket', 0, '2026-07-23 06:44:32'),
	(19, 68, NULL, 'Yeni Talep Atandı', 'Size yeni bir destek talebi atandı: #YEB-2026-000012.', 'assignment', 0, '2026-07-23 07:28:27'),
	(20, NULL, 9, 'Talep Oluşturuldu', 'Talebiniz (#YEB-2026-000012) başarıyla oluşturuldu.', 'ticket', 0, '2026-07-23 07:28:27'),
	(21, 59, NULL, 'Çözüm Reddedildi', 'Müşteri #YEB-2026-000010 nolu talebin çözümünü reddetti. Talep yeniden açıldı.', 'ticket', 0, '2026-07-23 07:32:07'),
	(22, NULL, 9, 'Yeni Mesaj Var', 'Uzman #YEB-2026-000012 nolu talebinize yeni bir yanıt yazdı.', 'message', 0, '2026-07-23 07:33:44'),
	(23, NULL, 9, 'Yeni Mesaj Var', 'Uzman #YEB-2026-000012 nolu talebinize yeni bir yanıt yazdı.', 'message', 0, '2026-07-23 07:33:55'),
	(24, 68, NULL, 'Yeni Mesaj Var', 'Müşteri #YEB-2026-000012 nolu talebe yeni bir mesaj yazdı.', 'message', 0, '2026-07-23 07:34:34'),
	(25, 60, NULL, 'Çözüm Onaylandı', 'Müşteri #YEB-2026-000008 nolu talebin çözümünü onayladı.', 'ticket', 0, '2026-07-23 08:51:26'),
	(26, NULL, 4, 'Çözüm Onayı Bekleniyor', 'Talebiniz (#YEB-2026-000010) uzman tarafından tamamlandı olarak işaretlendi ve onayınızı bekliyor.', 'ticket', 0, '2026-07-23 08:51:42'),
	(27, 59, NULL, 'Çözüm Onaylandı', 'Müşteri #YEB-2026-000010 nolu talebin çözümünü onayladı.', 'ticket', 0, '2026-07-23 08:51:49'),
	(28, 65, NULL, 'Yeni Talep Atandı', 'Size yeni bir destek talebi atandı.', 'assignment', 0, '2026-07-23 09:02:35'),
	(29, 59, NULL, 'Yeni Talep Atandı', 'Size yeni bir destek talebi atandı.', 'assignment', 0, '2026-07-23 09:02:40'),
	(30, 68, NULL, 'Yeni Talep Atandı', 'Size yeni bir destek talebi atandı.', 'assignment', 0, '2026-07-23 09:02:43'),
	(31, 60, NULL, 'Yeni Talep Atandı', 'Size yeni bir bilet atandı.', 'assignment', 0, '2026-07-23 09:05:52'),
	(32, 2, NULL, 'Yeni Talep Atandı', 'Size yeni bir bilet atandı.', 'assignment', 0, '2026-07-23 09:05:53'),
	(33, 67, NULL, 'Yeni Talep Atandı', 'Size yeni bir bilet atandı.', 'assignment', 0, '2026-07-23 09:06:00'),
	(34, 60, NULL, 'Yeni Talep Atandı', 'Size yeni bir destek talebi atandı: #YEB-2026-000013.', 'assignment', 0, '2026-07-23 11:54:54'),
	(35, NULL, 5, 'Talep Oluşturuldu', 'Talebiniz (#YEB-2026-000013) başarıyla oluşturuldu.', 'ticket', 0, '2026-07-23 11:54:55'),
	(36, NULL, 5, 'Çözüm Onayı Bekleniyor', 'Talebiniz (#YEB-2026-000013) uzman tarafından tamamlandı olarak işaretlendi ve onayınızı bekliyor.', 'ticket', 0, '2026-07-23 11:58:38'),
	(37, NULL, 5, 'Yeni Mesaj Var', 'Uzman #YEB-2026-000013 nolu talebinize yeni bir yanıt yazdı.', 'message', 0, '2026-07-23 12:15:05'),
	(38, 59, NULL, 'Yeni Talep Atandı', 'Size yeni bir bilet atandı.', 'assignment', 0, '2026-07-23 12:38:01');

-- tablo yapısı dökülüyor destek_as.permissions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.permissions: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.priorities
CREATE TABLE IF NOT EXISTS `priorities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` int NOT NULL,
  `first_response_time` int DEFAULT NULL,
  `intervention_time` int DEFAULT NULL,
  `resolution_time` int DEFAULT NULL,
  `notification_rule` text COLLATE utf8mb4_unicode_ci,
  `escalation_rule` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `priorities_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.priorities: ~3 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `priorities` (`id`, `company_id`, `name`, `description`, `level`, `first_response_time`, `intervention_time`, `resolution_time`, `notification_rule`, `escalation_rule`, `created_at`, `updated_at`) VALUES
	(1, NULL, 'Normal', 'Günlük çalışmayı doğrudan engellemeyen talepler.', 1, 480, 1440, 4320, NULL, NULL, '2026-07-21 12:25:41', '2026-07-22 13:51:52'),
	(2, NULL, 'Öncelikli', 'Standart destek talepleri.', 2, 240, 720, 2880, NULL, NULL, '2026-07-21 12:25:41', '2026-07-22 13:51:52'),
	(3, NULL, 'Yüksek', 'İş süreçlerini önemli ölçüde etkileyen sorunlar.', 3, 60, 180, 720, NULL, NULL, '2026-07-21 12:25:41', '2026-07-21 12:25:41');

-- tablo yapısı dökülüyor destek_as.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `roles_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.roles: ~6 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `roles` (`id`, `company_id`, `name`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
	(1, NULL, 'Sistem Sahibi', 'Sistemin en yetkili kullanıcısı, tüm firmaları yönetebilir.', 1, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(2, NULL, 'Firma Yöneticisi', 'Kendi firmasına ait destek sistemini yöneten kullanıcı.', 1, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(3, NULL, 'Departman Yöneticisi', 'Kendi departmanındaki ticket ve personelleri yönetir.', 1, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(4, NULL, 'Destek Personeli', 'Kendisine veya departmanına atanan destek taleplerini çözer.', 1, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(5, NULL, 'Müşteri Kullanıcısı', 'Destek talebi (ticket) oluşturan ve takip eden kullanıcı.', 1, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(6, NULL, 'Gözlemci Kullanıcı', 'Sadece kendisine izin verilen ticketları görüntüleyebilen kullanıcı.', 1, '2026-07-21 12:25:41', '2026-07-21 12:25:41');

-- tablo yapısı dökülüyor destek_as.role_permissions
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.role_permissions: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.sla_events
CREATE TABLE IF NOT EXISTS `sla_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deadline` timestamp NOT NULL,
  `triggered_at` timestamp NULL DEFAULT NULL,
  `is_breached` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `sla_events_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.sla_events: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.sla_policies
CREATE TABLE IF NOT EXISTS `sla_policies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `first_response_time` int NOT NULL,
  `resolution_time` int NOT NULL,
  `priority_id` int DEFAULT NULL,
  `support_package_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `priority_id` (`priority_id`),
  KEY `support_package_id` (`support_package_id`),
  CONSTRAINT `sla_policies_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sla_policies_ibfk_2` FOREIGN KEY (`priority_id`) REFERENCES `priorities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sla_policies_ibfk_3` FOREIGN KEY (`support_package_id`) REFERENCES `support_packages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.sla_policies: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.staff_reports
CREATE TABLE IF NOT EXISTS `staff_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `staff_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.staff_reports: ~1 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `staff_reports` (`id`, `user_id`, `title`, `content`, `created_at`) VALUES
	(1, 60, 'gün sonu', 'bugün 5 adet tickt hallettim', '2026-07-22 11:00:39');

-- tablo yapısı dökülüyor destek_as.subcategories
CREATE TABLE IF NOT EXISTS `subcategories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.subcategories: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.support_packages
CREATE TABLE IF NOT EXISTS `support_packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ticket_limit` int DEFAULT '-1',
  `support_hours` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '9/5',
  `response_sla` int DEFAULT NULL,
  `resolution_sla` int DEFAULT NULL,
  `dedicated_agent` tinyint(1) DEFAULT '0',
  `critical_intervention` tinyint(1) DEFAULT '0',
  `price` decimal(10,2) DEFAULT '0.00',
  `status` enum('active','passive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `support_packages_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.support_packages: ~2 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `support_packages` (`id`, `company_id`, `name`, `description`, `ticket_limit`, `support_hours`, `response_sla`, `resolution_sla`, `dedicated_agent`, `critical_intervention`, `price`, `status`, `created_at`, `updated_at`) VALUES
	(1, 1, 'Standart Paket', 'Mesai saatlerinde destek, aylık 20 talep limiti', 20, '9/5', 480, 2880, 0, 0, 0.00, 'active', '2026-07-21 12:26:05', '2026-07-22 08:03:24'),
	(2, 1, 'Pro Paket', 'Öncelikli destek, aylık 100 talep limiti', 100, '9/5', 120, 720, 0, 1, 15.00, 'active', '2026-07-21 12:26:05', '2026-07-22 08:03:24');

-- tablo yapısı dökülüyor destek_as.tags
CREATE TABLE IF NOT EXISTS `tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_company_tag` (`company_id`,`name`),
  CONSTRAINT `tags_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.tags: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.tickets
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `ticket_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int DEFAULT NULL,
  `subcategory_id` int DEFAULT NULL,
  `product_service` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority_id` int DEFAULT NULL,
  `ticket_type_id` int DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `project_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `screenshot_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `communication_preference` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'email',
  `available_time` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_id` int DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `company_id` (`company_id`),
  KEY `category_id` (`category_id`),
  KEY `subcategory_id` (`subcategory_id`),
  KEY `priority_id` (`priority_id`),
  KEY `ticket_type_id` (`ticket_type_id`),
  KEY `department_id` (`department_id`),
  KEY `status_id` (`status_id`),
  KEY `customer_id` (`customer_id`),
  KEY `customer_user_id` (`customer_user_id`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_4` FOREIGN KEY (`priority_id`) REFERENCES `priorities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_5` FOREIGN KEY (`ticket_type_id`) REFERENCES `ticket_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_6` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_7` FOREIGN KEY (`status_id`) REFERENCES `ticket_statuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_8` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_9` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.tickets: ~13 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `tickets` (`id`, `company_id`, `ticket_number`, `title`, `description`, `category_id`, `subcategory_id`, `product_service`, `priority_id`, `ticket_type_id`, `department_id`, `project_name`, `screenshot_path`, `attachment_path`, `communication_preference`, `available_time`, `status_id`, `customer_id`, `customer_user_id`, `created_at`, `updated_at`) VALUES
	(1, 1, 'YEB-2026-000001', 'Donanım Arızası Destek Talebi', 'verdiğiniz program çalışmadı', NULL, NULL, 'Donanım Arızası', 2, 1, 2, NULL, NULL, NULL, 'email', NULL, 10, 1, 3, '2026-07-22 06:14:28', '2026-07-22 07:27:31'),
	(2, 1, 'YEB-2026-000002', 'Yazılım Geliştirme Destek Talebi', 'sanırsam ürünü iade edicem', NULL, NULL, 'Yazılım Geliştirme', 2, 1, 1, NULL, NULL, NULL, 'email', NULL, 10, 2, 4, '2026-07-22 06:36:57', '2026-07-22 07:27:26'),
	(3, 1, 'YEB-2026-000003', 'Donanım Arızası Destek Talebi', 'arıza verdi', NULL, NULL, 'Donanım Arızası', 2, 1, 2, NULL, NULL, NULL, 'email', NULL, 10, 7, 9, '2026-07-22 06:52:30', '2026-07-22 07:27:18'),
	(4, 1, 'YEB-2026-000004', 'Donanım Arızası Destek Talebi', 'arıza verdi', NULL, NULL, 'Donanım Arızası', 2, 1, 2, NULL, NULL, NULL, 'email', NULL, 10, 7, 9, '2026-07-22 06:59:34', '2026-07-22 07:27:11'),
	(5, 1, 'YEB-2026-000005', 'Yazılım Geliştirme Destek Talebi', 'program hata veriyor', NULL, NULL, 'Yazılım Geliştirme', 3, 1, 1, NULL, NULL, NULL, 'email', NULL, 10, 1, 3, '2026-07-22 07:21:10', '2026-07-22 07:27:00'),
	(6, 1, 'YEB-2026-000006', 'Donanım Arızası Destek Talebi', 'donanımımda teknik bir sorun yaşadım', NULL, NULL, 'Donanım Arızası', 1, 1, 2, NULL, NULL, NULL, 'email', NULL, 10, 7, 9, '2026-07-22 07:28:24', '2026-07-22 11:55:17'),
	(7, 1, 'YEB-2026-000007', 'Donanım Arızası Destek Talebi', 'hata bildirimi aldım', NULL, NULL, 'Donanım Arızası', 3, 1, 2, NULL, NULL, NULL, 'email', NULL, 10, 7, 9, '2026-07-22 07:28:44', '2026-07-23 13:10:57'),
	(8, 1, 'YEB-2026-000008', 'Yazılım Geliştirme Destek Talebi', 'adaddadasd', NULL, NULL, 'Yazılım Geliştirme', 1, 2, 1, NULL, NULL, NULL, 'email', NULL, 10, 2, 4, '2026-07-22 07:40:05', '2026-07-23 08:51:26'),
	(9, 1, 'YEB-2026-000009', 'Yazılım Geliştirme Destek Talebi', 'hata aldım', NULL, NULL, 'Yazılım Geliştirme', 3, 1, 1, NULL, NULL, NULL, 'email', NULL, 8, 1, 3, '2026-07-22 11:39:31', '2026-07-23 13:22:58'),
	(10, 1, 'YEB-2026-000010', 'Yazılım Geliştirme Destek Talebi', 'mailim çalışmıyor', NULL, NULL, 'Yazılım Geliştirme', 2, 1, 1, NULL, NULL, NULL, 'email', NULL, 10, 2, 4, '2026-07-22 12:15:15', '2026-07-23 13:18:44'),
	(11, 1, 'YEB-2026-000011', 'Otel Rezervasyon Sistemi Destek Talebi', 'otomasyonda buton çalışmıyor', 3, NULL, 'Otel Rezervasyon Sistemi', 1, 1, NULL, NULL, NULL, NULL, 'email', NULL, 2, 3, 5, '2026-07-23 06:43:01', '2026-07-23 13:23:59'),
	(12, 1, 'YEB-2026-000012', 'Restoran Sipariş Otomasyonu Destek Talebi', 'otomsayon dan hata alıyorum', 2, NULL, 'Restoran Sipariş Otomasyonu', 3, 1, 1, NULL, NULL, NULL, 'email', NULL, 8, 7, 9, '2026-07-23 07:28:27', '2026-07-23 12:37:41'),
	(13, 1, 'YEB-2026-000013', 'Restoran Sipariş Otomasyonu Destek Talebi', 'otomasyonda QR okumuyor', 2, NULL, 'Restoran Sipariş Otomasyonu', 1, 1, 1, NULL, NULL, NULL, 'email', NULL, 11, 3, 5, '2026-07-23 11:54:54', '2026-07-23 11:58:38');

-- tablo yapısı dökülüyor destek_as.ticket_assignments
CREATE TABLE IF NOT EXISTS `ticket_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `user_id` int NOT NULL,
  `assigned_by` int DEFAULT NULL,
  `method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'manual',
  `status` enum('active','completed','reassigned') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `unassigned_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `ticket_assignments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.ticket_assignments: ~19 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `ticket_assignments` (`id`, `ticket_id`, `user_id`, `assigned_by`, `method`, `status`, `assigned_at`, `unassigned_at`) VALUES
	(1, 1, 55, 1, 'manual', 'active', '2026-07-22 06:33:35', NULL),
	(2, 2, 53, 4, 'round_robin', 'active', '2026-07-22 06:36:57', NULL),
	(3, 3, 56, 9, 'round_robin', 'active', '2026-07-22 06:52:30', NULL),
	(4, 4, 55, 9, 'round_robin', 'active', '2026-07-22 06:59:34', NULL),
	(5, 5, 54, 3, 'round_robin', 'active', '2026-07-22 07:21:10', NULL),
	(6, 6, 61, 9, 'round_robin', 'active', '2026-07-22 07:28:24', NULL),
	(7, 7, 62, 9, 'round_robin', 'active', '2026-07-22 07:28:44', NULL),
	(8, 8, 60, 4, 'round_robin', 'active', '2026-07-22 07:40:05', NULL),
	(9, 9, 63, 3, 'round_robin', 'active', '2026-07-22 11:39:31', NULL),
	(10, 10, 59, 4, 'round_robin', 'active', '2026-07-22 12:15:15', NULL),
	(11, 12, 68, 9, 'round_robin', 'reassigned', '2026-07-23 07:28:27', '2026-07-23 09:02:35'),
	(12, 12, 65, 2, 'manual', 'reassigned', '2026-07-23 09:02:35', '2026-07-23 09:02:40'),
	(13, 12, 59, 2, 'manual', 'reassigned', '2026-07-23 09:02:40', '2026-07-23 09:02:43'),
	(14, 12, 68, 2, 'manual', 'reassigned', '2026-07-23 09:02:43', '2026-07-23 09:02:47'),
	(15, 12, 60, 2, 'manual', 'reassigned', '2026-07-23 09:05:52', '2026-07-23 09:05:53'),
	(16, 12, 2, 2, 'manual', 'reassigned', '2026-07-23 09:05:53', '2026-07-23 09:06:00'),
	(17, 12, 67, 2, 'manual', 'active', '2026-07-23 09:06:00', NULL),
	(18, 13, 60, 5, 'round_robin', 'active', '2026-07-23 11:54:54', NULL),
	(19, 11, 59, 1, 'manual', 'active', '2026-07-23 12:38:01', NULL);

-- tablo yapısı dökülüyor destek_as.ticket_attachments
CREATE TABLE IF NOT EXISTS `ticket_attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `message_id` int DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int DEFAULT '0',
  `file_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `message_id` (`message_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `ticket_attachments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_attachments_ibfk_2` FOREIGN KEY (`message_id`) REFERENCES `ticket_messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_attachments_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.ticket_attachments: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.ticket_internal_notes
CREATE TABLE IF NOT EXISTS `ticket_internal_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `user_id` int NOT NULL,
  `note_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'staff',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ticket_internal_notes_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_internal_notes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.ticket_internal_notes: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.ticket_messages
CREATE TABLE IF NOT EXISTS `ticket_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `message_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'public',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `ticket_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.ticket_messages: ~17 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `ticket_messages` (`id`, `ticket_id`, `sender_id`, `message_type`, `content`, `attachment_path`, `is_read`, `edited_at`, `created_at`) VALUES
	(1, 1, 1, 'public', 'tamam hemen hallediyouz', NULL, 0, NULL, '2026-07-22 06:27:46'),
	(2, 1, 1, 'public', 'tamam hemen hallediyouz', NULL, 0, NULL, '2026-07-22 06:29:24'),
	(3, 1, 1, 'public', 'hemen bakıyoruz', NULL, 0, NULL, '2026-07-22 06:29:51'),
	(4, 8, 60, 'public', 'çözüldü', NULL, 0, NULL, '2026-07-22 07:41:19'),
	(5, 9, 63, 'public', 'olayı tamamladım oldu mu', NULL, 0, NULL, '2026-07-22 11:57:20'),
	(6, 9, 3, 'public', 'evet oldu', NULL, 0, NULL, '2026-07-22 11:57:54'),
	(7, 9, 63, 'public', 'sorun giderildise tamamlandı butonuna basarsanız sevinirim', NULL, 0, NULL, '2026-07-22 11:58:32'),
	(8, 8, 60, 'public', 'Talebiniz tamamlanmıştır. İyi günler, iyi çalışmalar dileriz...', NULL, 0, NULL, '2026-07-23 06:28:48'),
	(9, 8, 60, 'public', 'SELAMLAR KADEŞ ASDASD', NULL, 0, NULL, '2026-07-23 06:34:07'),
	(10, 10, 59, 'public', 'Talebiniz tamamlanmıştır. İyi günler, iyi çalışmalar dileriz...\n(Memnun kaldıysanız onayla butonuna basabilirsiniz)', NULL, 0, NULL, '2026-07-23 06:44:16'),
	(11, 8, 60, 'public', 'Talebiniz tamamlanmıştır. İyi günler, iyi çalışmalar dileriz...\n(Memnun kaldıysanız onayla butonuna basabilirsiniz)', NULL, 0, NULL, '2026-07-23 06:44:32'),
	(12, 12, 68, 'public', 'sa', NULL, 0, NULL, '2026-07-23 07:33:44'),
	(13, 12, 68, 'public', 'as', NULL, 0, NULL, '2026-07-23 07:33:55'),
	(14, 12, 9, 'public', 'naber', NULL, 0, NULL, '2026-07-23 07:34:34'),
	(15, 10, 59, 'public', 'Talebiniz tamamlanmıştır. İyi günler, iyi çalışmalar dileriz...\n(Memnun kaldıysanız onayla butonuna basabilirsiniz)', NULL, 0, NULL, '2026-07-23 08:51:42'),
	(16, 13, 60, 'public', 'Talebiniz tamamlanmıştır. İyi günler, iyi çalışmalar dileriz...\n(Memnun kaldıysanız onayla butonuna basabilirsiniz)', NULL, 0, NULL, '2026-07-23 11:58:38'),
	(17, 13, 1, 'public', 'SELAMLAR KADEŞ ASDASD', NULL, 0, NULL, '2026-07-23 12:15:05');

-- tablo yapısı dökülüyor destek_as.ticket_priority_history
CREATE TABLE IF NOT EXISTS `ticket_priority_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `old_priority_id` int DEFAULT NULL,
  `new_priority_id` int NOT NULL,
  `changed_by` int NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `old_priority_id` (`old_priority_id`),
  KEY `new_priority_id` (`new_priority_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `ticket_priority_history_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_priority_history_ibfk_2` FOREIGN KEY (`old_priority_id`) REFERENCES `priorities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ticket_priority_history_ibfk_3` FOREIGN KEY (`new_priority_id`) REFERENCES `priorities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_priority_history_ibfk_4` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.ticket_priority_history: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.ticket_relations
CREATE TABLE IF NOT EXISTS `ticket_relations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `related_ticket_id` int NOT NULL,
  `relation_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `related_ticket_id` (`related_ticket_id`),
  CONSTRAINT `ticket_relations_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_relations_ibfk_2` FOREIGN KEY (`related_ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.ticket_relations: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.ticket_statuses
CREATE TABLE IF NOT EXISTS `ticket_statuses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '#ccc',
  `is_system` tinyint(1) DEFAULT '0',
  `is_closed` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `ticket_statuses_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.ticket_statuses: ~16 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `ticket_statuses` (`id`, `company_id`, `name`, `description`, `color`, `is_system`, `is_closed`, `created_at`, `updated_at`) VALUES
	(1, NULL, 'Yeni', 'Talep henüz açıldı, işlem görmedi.', '#8b5cf6', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(2, NULL, 'Atama bekliyor', 'Analiz edildi, ilgili departmana veya personele atanmayı bekliyor.', '#f59e0b', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(3, NULL, 'Atandı', 'Destek personeline atandı.', '#3b82f6', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(4, NULL, 'İnceleniyor', 'Personel tarafından detaylı inceleniyor.', '#06b6d4', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(5, NULL, 'Müşteriden bilgi bekleniyor', 'Müşteriye soru soruldu, cevap bekleniyor.', '#ec4899', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(6, NULL, 'Personelden işlem bekleniyor', 'Personelin işlem yapması gerekiyor.', '#f43f5e', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(7, NULL, 'Çözüm üzerinde çalışılıyor', 'Sorunun çözümü üzerinde aktif çalışılıyor.', '#a855f7', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(8, NULL, 'Test ediliyor', 'Çözüm test ediliyor.', '#10b981', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(9, NULL, 'Üçüncü taraf bekleniyor', 'Dış entegrasyon veya kargo süreci bekleniyor.', '#6b7280', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(10, NULL, 'Çözüldü', 'Sorun çözüldü, müşteri onayına sunuldu.', '#10b981', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(11, NULL, 'Onay Bekliyor', 'Çözüm müşteri tarafından onaylanmayı bekliyor.', '#14b8a6', 1, 0, '2026-07-21 12:25:41', '2026-07-22 10:55:37'),
	(12, NULL, 'Kapatıldı', 'Talep başarıyla tamamlandı ve kapatıldı.', '#059669', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(13, NULL, 'Yeniden açıldı', 'Müşteri çözümü onaylamadı ve talebi tekrar açtı.', '#ef4444', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(14, NULL, 'İptal edildi', 'Talep iptal edildi.', '#9ca3af', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(15, NULL, 'Birleştirildi', 'Başka bir ticket ile birleştirildi.', '#78716c', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(16, NULL, 'Askıya alındı', 'İşlem donduruldu.', '#4b5563', 1, 0, '2026-07-21 12:25:41', '2026-07-21 12:25:41');

-- tablo yapısı dökülüyor destek_as.ticket_status_history
CREATE TABLE IF NOT EXISTS `ticket_status_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `old_status_id` int DEFAULT NULL,
  `new_status_id` int NOT NULL,
  `changed_by` int NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `old_status_id` (`old_status_id`),
  KEY `new_status_id` (`new_status_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `ticket_status_history_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_status_history_ibfk_2` FOREIGN KEY (`old_status_id`) REFERENCES `ticket_statuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ticket_status_history_ibfk_3` FOREIGN KEY (`new_status_id`) REFERENCES `ticket_statuses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_status_history_ibfk_4` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.ticket_status_history: ~20 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `ticket_status_history` (`id`, `ticket_id`, `old_status_id`, `new_status_id`, `changed_by`, `reason`, `created_at`) VALUES
	(1, 5, 3, 10, 1, 'Uzman tarafından tamamlandı olarak işaretlendi', '2026-07-22 07:27:00'),
	(2, 4, 3, 10, 1, 'Uzman tarafından tamamlandı olarak işaretlendi', '2026-07-22 07:27:11'),
	(3, 3, 3, 10, 1, 'Uzman tarafından tamamlandı olarak işaretlendi', '2026-07-22 07:27:18'),
	(4, 2, 3, 10, 1, 'Uzman tarafından tamamlandı olarak işaretlendi', '2026-07-22 07:27:26'),
	(5, 1, 3, 10, 1, 'Uzman tarafından tamamlandı olarak işaretlendi', '2026-07-22 07:27:31'),
	(6, 8, 3, 11, 60, 'Uzman tarafından tamamlandı olarak işaretlendi, müşteri onayı bekleniyor', '2026-07-22 07:40:47'),
	(7, 6, 3, 11, 61, 'Uzman tarafından tamamlandı olarak işaretlendi, müşteri onayı bekleniyor', '2026-07-22 11:55:03'),
	(8, 6, 11, 10, 9, 'Müşteri çözümü onayladı', '2026-07-22 11:55:17'),
	(9, 9, 3, 11, 63, 'Uzman tarafından tamamlandı olarak işaretlendi, müşteri onayı bekleniyor', '2026-07-22 11:58:05'),
	(10, 9, 11, 10, 3, 'Müşteri çözümü onayladı', '2026-07-22 11:59:06'),
	(11, 10, 3, 11, 59, 'Uzman tarafından tamamlandı olarak işaretlendi, müşteri onayı bekleniyor', '2026-07-22 12:17:47'),
	(12, 10, 11, 11, 59, 'Uzman tarafından tamamlandı olarak işaretlendi, müşteri onayı bekleniyor', '2026-07-22 12:17:49'),
	(13, 8, 11, 11, 60, 'Uzman tarafından tamamlandı olarak işaretlendi, müşteri onayı bekleniyor', '2026-07-23 06:28:48'),
	(14, 10, 11, 11, 59, 'Uzman tarafından tamamlandı olarak işaretlendi, müşteri onayı bekleniyor', '2026-07-23 06:44:16'),
	(15, 8, 11, 11, 60, 'Uzman tarafından tamamlandı olarak işaretlendi, müşteri onayı bekleniyor', '2026-07-23 06:44:32'),
	(16, 10, 11, 13, 4, 'Müşteri çözümü reddetti, talep yeniden açıldı', '2026-07-23 07:32:07'),
	(17, 8, 11, 10, 4, 'Müşteri çözümü onayladı', '2026-07-23 08:51:26'),
	(18, 10, 13, 11, 59, 'Uzman tarafından tamamlandı olarak işaretlendi, müşteri onayı bekleniyor', '2026-07-23 08:51:42'),
	(19, 10, 11, 10, 4, 'Müşteri çözümü onayladı', '2026-07-23 08:51:49'),
	(20, 13, 3, 11, 60, 'Uzman tarafından tamamlandı olarak işaretlendi, müşteri onayı bekleniyor', '2026-07-23 11:58:38');

-- tablo yapısı dökülüyor destek_as.ticket_tags
CREATE TABLE IF NOT EXISTS `ticket_tags` (
  `ticket_id` int NOT NULL,
  `tag_id` int NOT NULL,
  PRIMARY KEY (`ticket_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `ticket_tags_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.ticket_tags: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.ticket_tasks
CREATE TABLE IF NOT EXISTS `ticket_tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `assigned_to` int DEFAULT NULL,
  `priority` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Normal',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `completion_percentage` int DEFAULT '0',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `ticket_tasks_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_tasks_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.ticket_tasks: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.ticket_time_entries
CREATE TABLE IF NOT EXISTS `ticket_time_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `user_id` int NOT NULL,
  `start_time` timestamp NOT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `total_duration` int DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_billable` tinyint(1) DEFAULT '0',
  `hourly_rate` decimal(10,2) DEFAULT '0.00',
  `flat_rate` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(10,2) DEFAULT '0.00',
  `approval_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ticket_time_entries_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_time_entries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.ticket_time_entries: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.ticket_types
CREATE TABLE IF NOT EXISTS `ticket_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT '0',
  `status` enum('active','passive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `ticket_types_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.ticket_types: ~15 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `ticket_types` (`id`, `company_id`, `name`, `description`, `is_system`, `status`, `created_at`, `updated_at`) VALUES
	(1, NULL, 'Teknik sorun', 'Teknik problemler ve sistem hataları', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(2, NULL, 'Hata bildirimi', 'Yazılımsal bug ve hatalar', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(3, NULL, 'Yeni özellik talebi', 'Sisteme eklenmesi istenen yeni özellikler', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(4, NULL, 'Kullanıcı desteği', 'Kullanım ve yapılandırma yardımı', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(5, NULL, 'Kurulum talebi', 'Program veya donanım kurulum istekleri', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(6, NULL, 'Eğitim talebi', 'Kullanıcı eğitimi talepleri', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(7, NULL, 'Donanım sorunu', 'Fiziksel donanım arızaları', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(8, NULL, 'Ağ ve internet sorunu', 'Network ve bağlantı problemleri', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(9, NULL, 'Fatura ve ödeme sorunu', 'Muhasebe ve ödeme problemleri', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(10, NULL, 'Ürün iade talebi', 'Satın alınan ürünlerin iade süreçleri', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(11, NULL, 'Bilgi talebi', 'Genel bilgilendirme istekleri', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(12, NULL, 'Şikâyet', 'Müşteri memnuniyetsizlik bildirimleri', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(13, NULL, 'Öneri', 'Geliştirme ve iyileştirme tavsiyeleri', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(14, NULL, 'Güvenlik bildirimi', 'Güvenlik zafiyeti bildirimleri', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41'),
	(15, NULL, 'Acil destek', 'Kritik seviyede anlık müdahale gerektiren durumlar', 1, 'active', '2026-07-21 12:25:41', '2026-07-21 12:25:41');

-- tablo yapısı dökülüyor destek_as.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','passive','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `two_factor_secret` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.users: ~62 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `status`, `two_factor_secret`, `created_at`, `updated_at`) VALUES
	(1, 'admin@destek.com', '$2y$10$0KIt.72JD/8QblS2oSLxkO8/OPgmdi.h05aDbeY8fIDCvOA0N24G2', 'Mustafa', 'Admin', '05016621628', 'active', NULL, '2026-07-21 12:26:05', '2026-07-21 12:26:05'),
	(2, 'yonetici@destek.com', '$2y$10$4zu/4PAcXlNHCmgg9IpYku7jo29wQEOSnxXFlOR6IEVNmlflu68V.', 'Selim', 'Yönetici', '05553334455', 'active', NULL, '2026-07-21 12:26:05', '2026-07-21 12:26:05'),
	(3, 'musteri@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Ahmet', 'Kuzey', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(4, 'mehmetbulut@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Mehmet', 'Bulut', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(5, 'cananege@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Canan', 'Ege', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(6, 'elifmarmara@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Elif', 'Marmara', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(7, 'mustafaanadolu@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Mustafa', 'Anadolu', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(8, 'zeynepakdeniz@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Zeynep', 'Akdeniz', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(9, 'hasankaradeniz@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Hasan', 'Karadeniz', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(10, 'ayseefes@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Ayşe', 'Efes', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(11, 'alizirve@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Ali', 'Zirve', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(12, 'fatmahedef@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Fatma', 'Hedef', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(13, 'İbrahimmega@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'İbrahim', 'Mega', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(14, 'haticeÖzkan@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Hatice', 'Özkan', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(15, 'kemalyildiz@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Kemal', 'Yıldız', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(16, 'deryabursa@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Derya', 'Bursa', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(17, 'muratgunes@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Murat', 'Güneş', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(18, 'selindost@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Selin', 'Dost', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(19, 'denizmavi@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Deniz', 'Mavi', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(20, 'osmanhilal@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Osman', 'Hilal', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(21, 'sedaserhat@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Seda', 'Serhat', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(22, 'bulentdoruk@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Bülent', 'Doruk', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(23, 'nihalnet@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Nihal', 'Net', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(24, 'hakanuzman@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Hakan', 'Uzman', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(25, 'burcupinar@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Burcu', 'Pınar', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(26, 'cemkanyon@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Cem', 'Kanyon', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(27, 'leventaras@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Levent', 'Aras', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(28, 'gokhanvatan@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Gökhan', 'Vatan', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(29, 'ebrutekno@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Ebru', 'Tekno', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(30, 'serkanuzay@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Serkan', 'Uzay', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(31, 'Ömerbirlik@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Ömer', 'Birlik', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(32, 'tugaykarar@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Tugay', 'Karar', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(33, 'fatihsistem@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Fatih', 'Sistem', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(34, 'gozdevizyon@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Gözde', 'Vizyon', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(35, 'yasinÖzgur@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Yasin', 'Özgür', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(36, 'tugbabilgi@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Tuğba', 'Bilgi', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(37, 'ademseckin@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Adem', 'Seçkin', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(38, 'nisaÖncu@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Nisa', 'Öncü', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(39, 'kadirlider@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Kadir', 'Lider', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(40, 'savasmerkez@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Savaş', 'Merkez', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(41, 'enderÇagdas@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Ender', 'Çağdaş', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(42, 'bernaguven@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Berna', 'Güven', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(43, 'metedeha@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Mete', 'Deha', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(44, 'jalefocus@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Jale', 'Focus', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(45, 'ugurkreatif@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Uğur', 'Kreatif', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(46, 'tamerpusula@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Tamer', 'Pusula', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(47, 'cerenrota@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Ceren', 'Rota', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(48, 'oguzkare@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Oğuz', 'Kare', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(49, 'sibelpiramit@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Sibel', 'Piramit', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(50, 'koraytempo@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Koray', 'Tempo', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(51, 'aslidoga@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Aslı', 'Doğa', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(52, 'secilelit@destek.com', '$2y$10$1lKnoFQH3c.OiY0kbP5HqeYnNy8TzL5h0ZUEY3n.ruiRuOSYWubEu', 'Seçil', 'Elit', NULL, 'active', NULL, '2026-07-21 14:30:22', '2026-07-21 14:30:22'),
	(59, 'keremyazici@destek.com', '$2y$10$V2qev31YOtttlRGiC42E..OXRK5cfHWSrDu.j5iTseE2RC3lEmk2K', 'Kerem', 'Yazıcı', NULL, 'active', NULL, '2026-07-22 07:24:41', '2026-07-22 07:24:41'),
	(60, 'selinyilmaz@destek.com', '$2y$10$V2qev31YOtttlRGiC42E..OXRK5cfHWSrDu.j5iTseE2RC3lEmk2K', 'Selin', 'Yılmaz', NULL, 'active', NULL, '2026-07-22 07:24:41', '2026-07-22 07:24:41'),
	(61, 'hakandemir@destek.com', '$2y$10$V2qev31YOtttlRGiC42E..OXRK5cfHWSrDu.j5iTseE2RC3lEmk2K', 'Hakan', 'Demir', NULL, 'active', NULL, '2026-07-22 07:24:41', '2026-07-22 07:24:41'),
	(62, 'muratkaya@destek.com', '$2y$10$V2qev31YOtttlRGiC42E..OXRK5cfHWSrDu.j5iTseE2RC3lEmk2K', 'Murat', 'Kaya', NULL, 'active', NULL, '2026-07-22 07:24:41', '2026-07-22 07:24:41'),
	(63, 'boracelik@destek.com', '$2y$10$V2qev31YOtttlRGiC42E..OXRK5cfHWSrDu.j5iTseE2RC3lEmk2K', 'Bora', 'Çelik', NULL, 'active', NULL, '2026-07-22 07:24:41', '2026-07-22 07:24:41'),
	(64, 'elifsahin@destek.com', '$2y$10$V2qev31YOtttlRGiC42E..OXRK5cfHWSrDu.j5iTseE2RC3lEmk2K', 'Elif', 'Şahin', NULL, 'active', NULL, '2026-07-22 07:24:41', '2026-07-22 07:24:41'),
	(65, 'mehmet@gmail.com', '$2y$10$7iHNli8k.Asq5CapTU2RxeL2PD5SpZtxeaj20.e9uIahz3BpGOmN.', 'mehmet', 'tekin', '0545858225', 'active', NULL, '2026-07-23 07:23:32', '2026-07-23 07:23:32'),
	(66, 'ali@gmail.com', '$2y$10$U5LTlVKeK6vHor.sYeZDT.JjqFCDEpEQ17.84PKnjfTlh8yPBMtSG', 'ali', 'soylu', '05458587744', 'active', NULL, '2026-07-23 07:24:21', '2026-07-23 07:24:21'),
	(67, 'mustafa@gmail.com', '$2y$10$UkNMCWElxHhx60sPSRCWtuyblKCwygmYnW4RRcjUrBgSs3dHYdq/u', 'musatafa', 'yer', '0565235689', 'active', NULL, '2026-07-23 07:25:05', '2026-07-23 07:25:05'),
	(68, 'enes@gmail.com', '$2y$10$cDCnHWrw9PUJUjmxzBLJoepbC5navD2WPTMtBacxSDeRU1gv9X6dy', 'enes', 'diker', '0578451254', 'active', NULL, '2026-07-23 07:25:55', '2026-07-23 07:25:55');

-- tablo yapısı dökülüyor destek_as.user_roles
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  `company_id` int DEFAULT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.user_roles: ~62 rows (yaklaşık) tablosu için veriler indiriliyor
INSERT INTO `user_roles` (`user_id`, `role_id`, `company_id`) VALUES
	(3, 5, NULL),
	(4, 5, NULL),
	(5, 5, NULL),
	(6, 5, NULL),
	(7, 5, NULL),
	(8, 5, NULL),
	(9, 5, NULL),
	(10, 5, NULL),
	(11, 5, NULL),
	(12, 5, NULL),
	(13, 5, NULL),
	(14, 5, NULL),
	(15, 5, NULL),
	(16, 5, NULL),
	(17, 5, NULL),
	(18, 5, NULL),
	(19, 5, NULL),
	(20, 5, NULL),
	(21, 5, NULL),
	(22, 5, NULL),
	(23, 5, NULL),
	(24, 5, NULL),
	(25, 5, NULL),
	(26, 5, NULL),
	(27, 5, NULL),
	(28, 5, NULL),
	(29, 5, NULL),
	(30, 5, NULL),
	(31, 5, NULL),
	(32, 5, NULL),
	(33, 5, NULL),
	(34, 5, NULL),
	(35, 5, NULL),
	(36, 5, NULL),
	(37, 5, NULL),
	(38, 5, NULL),
	(39, 5, NULL),
	(40, 5, NULL),
	(41, 5, NULL),
	(42, 5, NULL),
	(43, 5, NULL),
	(44, 5, NULL),
	(45, 5, NULL),
	(46, 5, NULL),
	(47, 5, NULL),
	(48, 5, NULL),
	(49, 5, NULL),
	(50, 5, NULL),
	(51, 5, NULL),
	(52, 5, NULL),
	(59, 4, NULL),
	(60, 4, NULL),
	(61, 4, NULL),
	(62, 4, NULL),
	(63, 4, NULL),
	(64, 4, NULL),
	(1, 1, 1),
	(2, 2, 1),
	(65, 4, 1),
	(66, 4, 1),
	(67, 4, 1),
	(68, 4, 1);

-- tablo yapısı dökülüyor destek_as.webhooks
CREATE TABLE IF NOT EXISTS `webhooks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `target_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_types` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','passive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `webhooks_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.webhooks: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

-- tablo yapısı dökülüyor destek_as.webhook_logs
CREATE TABLE IF NOT EXISTS `webhook_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `webhook_id` int NOT NULL,
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `http_status` int DEFAULT NULL,
  `retry_count` int DEFAULT '0',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Success',
  `response` text COLLATE utf8mb4_unicode_ci,
  `error_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `webhook_id` (`webhook_id`),
  CONSTRAINT `webhook_logs_ibfk_1` FOREIGN KEY (`webhook_id`) REFERENCES `webhooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destek_as.webhook_logs: ~0 rows (yaklaşık) tablosu için veriler indiriliyor

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
