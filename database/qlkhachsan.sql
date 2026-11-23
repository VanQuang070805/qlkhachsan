-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: qlkhachsan
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `qlkhachsan`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `qlkhachsan` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `qlkhachsan`;

--
-- Table structure for table `amenities`
--

DROP TABLE IF EXISTS `amenities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amenities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `amenity_name` varchar(150) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `amenities`
--

LOCK TABLES `amenities` WRITE;
/*!40000 ALTER TABLE `amenities` DISABLE KEYS */;
INSERT INTO `amenities` VALUES (1,'WiFi miễn phí','fa-wifi','2026-06-05 11:27:37','2026-06-05 11:27:37'),(2,'TV màn hình phẳng','fa-tv','2026-06-05 11:27:37','2026-06-05 11:27:37'),(3,'Điều hòa','fa-snowflake','2026-06-05 11:27:37','2026-06-05 11:27:37'),(4,'Máy sấy tóc','fa-wind','2026-06-05 11:27:37','2026-06-05 11:27:37'),(5,'Dịch vụ phòng','fa-concierge-bell','2026-06-05 11:27:37','2026-06-05 11:27:37'),(6,'Tủ lạnh','fa-cube','2026-06-05 11:27:37','2026-06-05 11:27:37'),(7,'Bồn tắm','fa-bath','2026-06-05 11:27:37','2026-06-05 11:27:37'),(8,'Máy chiếu','fa-film','2026-06-05 11:27:37','2026-06-05 11:27:37'),(9,'Bữa sáng miễn phí','fa-coffee','2026-06-05 11:27:37','2026-06-05 11:27:37');
/*!40000 ALTER TABLE `amenities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_rooms`
--

DROP TABLE IF EXISTS `booking_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking_rooms` (
  `booking_id` bigint(20) unsigned NOT NULL,
  `room_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`booking_id`,`room_id`),
  KEY `booking_rooms_room_id_foreign` (`room_id`),
  CONSTRAINT `booking_rooms_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `booking_rooms_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_rooms`
--

LOCK TABLES `booking_rooms` WRITE;
/*!40000 ALTER TABLE `booking_rooms` DISABLE KEYS */;
/*!40000 ALTER TABLE `booking_rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(30) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `actual_check_in` datetime DEFAULT NULL,
  `actual_check_out` datetime DEFAULT NULL,
  `adult_count` int(11) NOT NULL,
  `child_count` int(11) NOT NULL DEFAULT 0,
  `total_price` decimal(12,2) NOT NULL,
  `late_checkout_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `waive_late_fee` tinyint(1) NOT NULL DEFAULT 0,
  `deposit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('vietqr','momo','zalopay','vnpay','cash') DEFAULT NULL,
  `payment_status` enum('pending','paid','refunded','failed') NOT NULL DEFAULT 'pending',
  `status` enum('pending','confirmed','checked_in','soon_to_checkout','completed','cancelled') DEFAULT 'pending',
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `refund_status` enum('none','eligible','processing','refunded') NOT NULL DEFAULT 'none',
  `refund_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bookings_user_id_foreign` (`user_id`),
  CONSTRAINT `bookings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `face_profiles`
--

DROP TABLE IF EXISTS `face_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `face_profiles` (
  `id` char(36) NOT NULL,
  `booking_id` bigint(20) unsigned NOT NULL,
  `room_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `guest_cccd` longtext DEFAULT NULL,
  `guest_phone` longtext DEFAULT NULL,
  `embedding` longtext NOT NULL,
  `embedding_model` varchar(80) NOT NULL DEFAULT 'sface_2021dec',
  `embedding_dimension` smallint(5) unsigned NOT NULL,
  `sample_count` smallint(5) unsigned NOT NULL DEFAULT 15,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `consent_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `face_profiles_user_id_foreign` (`user_id`),
  KEY `face_profiles_active_index` (`active`),
  KEY `face_profiles_booking_id_index` (`booking_id`),
  KEY `face_profiles_room_active_index` (`room_id`,`active`),
  CONSTRAINT `face_profiles_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `face_profiles_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `face_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `face_profiles`
--

LOCK TABLES `face_profiles` WRITE;
/*!40000 ALTER TABLE `face_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `face_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `face_sync_queue`
--

DROP TABLE IF EXISTS `face_sync_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `face_sync_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `face_profile_id` char(36) NOT NULL,
  `action` varchar(10) NOT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'PENDING',
  `retry_count` int(10) unsigned NOT NULL DEFAULT 0,
  `next_attempt_at` timestamp NULL DEFAULT NULL,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `last_error` varchar(500) DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `face_sync_lookup` (`face_profile_id`,`action`,`status`),
  KEY `face_sync_queue_status_index` (`status`),
  KEY `face_sync_queue_next_attempt_at_index` (`next_attempt_at`),
  CONSTRAINT `face_sync_queue_face_profile_id_foreign` FOREIGN KEY (`face_profile_id`) REFERENCES `face_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `face_sync_queue`
--

LOCK TABLES `face_sync_queue` WRITE;
/*!40000 ALTER TABLE `face_sync_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `face_sync_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `holidays` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `recurring` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
INSERT INTO `holidays` VALUES (1,'Tết Dương lịch','2026-01-01',1,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(2,'Tết Nguyên Đán (30)','2026-02-16',0,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(3,'Tết Nguyên Đán (Mùng 1)','2026-02-17',0,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(4,'Tết Nguyên Đán (Mùng 2)','2026-02-18',0,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(5,'Tết Nguyên Đán (Mùng 3)','2026-02-19',0,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(6,'Tết Nguyên Đán (Mùng 4)','2026-02-20',0,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(7,'Tết Nguyên Đán (Mùng 5)','2026-02-21',0,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(8,'Giỗ Tổ Hùng Vương','2026-04-06',0,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(9,'Ngày Giải phóng 30/4','2026-04-30',1,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(10,'Ngày Quốc tế Lao động','2026-05-01',1,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(11,'Ngày Quốc khánh 2/9','2026-09-02',1,'2026-06-05 11:27:37','2026-06-05 11:27:37');
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knowledge_chunks`
--

DROP TABLE IF EXISTS `knowledge_chunks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knowledge_chunks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `content_hash` char(64) NOT NULL,
  `embedding` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `embedding_model` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `knowledge_chunks_content_hash_unique` (`content_hash`),
  KEY `knowledge_chunks_source_title_index` (`source`,`title`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_chunks`
--

LOCK TABLES `knowledge_chunks` WRITE;
/*!40000 ALTER TABLE `knowledge_chunks` DISABLE KEYS */;
/*!40000 ALTER TABLE `knowledge_chunks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2026_01_01_000001_create_users_table',1),(4,'2026_01_01_000002_create_password_resets_table',1),(5,'2026_01_01_000003_create_room_types_table',1),(6,'2026_01_01_000004_create_rooms_table',1),(7,'2026_01_01_000005_create_amenities_tables',1),(8,'2026_01_01_000006_create_holidays_table',1),(9,'2026_01_01_000007_create_bookings_table',1),(10,'2026_01_01_000008_create_remaining_tables',1),(11,'2026_06_06_102740_add_soon_to_checkout_to_bookings_status',2),(12,'2026_06_06_112844_add_deposit_amount_to_bookings',2),(13,'2026_06_06_113630_add_cash_to_payment_method_bookings',3),(14,'2026_06_06_133315_add_booked_to_rooms_status',4),(15,'2026_06_06_033237_create_sessions_table',5),(16,'2026_06_07_000000_replace_price_policies_with_price_settings',5),(17,'2026_06_08_042725_add_late_checkout_fee_to_bookings',5),(18,'2026_06_09_054621_add_waive_late_fee_to_bookings',5),(19,'2026_09_25_000001_add_unique_review_constraint',5),(20,'2026_09_25_000001_create_report_snapshots_table',5),(21,'2026_09_25_000002_create_knowledge_chunks_table',5),(22,'2026_09_25_000003_add_google_identity_to_users_table',5),(23,'2026_09_29_000001_harden_payment_logs',6),(24,'2026_09_29_000002_expand_user_otp_code',7),(25,'2026_09_25_000001_create_face_id_tables',8),(26,'2026_09_26_000001_allow_multiple_face_profiles_per_room',9),(27,'2026_09_26_000002_add_guest_details_to_face_profiles',10),(28,'2026_09_30_000001_add_cleaning_request_to_rooms',11),(29,'2026_10_02_000001_drop_unused_framework_and_report_tables',12),(30,'2026_10_03_000001_drop_legacy_password_resets_table',13),(31,'2026_10_07_000001_complete_physical_schema_relations',14);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_logs`
--

DROP TABLE IF EXISTS `payment_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `gateway` enum('vietqr','momo','zalopay','vnpay','cash') NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `reference_code` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `purpose` varchar(32) NOT NULL DEFAULT 'deposit',
  `status` enum('pending','success','failed') NOT NULL,
  `raw_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_logs_reference_unique` (`reference_code`),
  KEY `payment_logs_booking_id_foreign` (`booking_id`),
  KEY `payment_logs_reference_code_index` (`reference_code`),
  KEY `payment_logs_transaction_id_index` (`transaction_id`),
  CONSTRAINT `payment_logs_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_logs`
--

LOCK TABLES `payment_logs` WRITE;
/*!40000 ALTER TABLE `payment_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price_setting_room_types`
--

DROP TABLE IF EXISTS `price_setting_room_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `price_setting_room_types` (
  `price_setting_id` bigint(20) unsigned NOT NULL,
  `room_type_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`price_setting_id`,`room_type_id`),
  KEY `price_setting_room_types_room_type_id_foreign` (`room_type_id`),
  CONSTRAINT `price_setting_room_types_price_setting_id_foreign` FOREIGN KEY (`price_setting_id`) REFERENCES `price_settings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `price_setting_room_types_room_type_id_foreign` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_setting_room_types`
--

LOCK TABLES `price_setting_room_types` WRITE;
/*!40000 ALTER TABLE `price_setting_room_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `price_setting_room_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price_settings`
--

DROP TABLE IF EXISTS `price_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `price_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `holiday_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `adjustment_type` enum('percent','fixed') NOT NULL,
  `adjustment_value` decimal(10,2) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `price_settings_holiday_id_foreign` (`holiday_id`),
  CONSTRAINT `price_settings_holiday_id_foreign` FOREIGN KEY (`holiday_id`) REFERENCES `holidays` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_settings`
--

LOCK TABLES `price_settings` WRITE;
/*!40000 ALTER TABLE `price_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `price_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `room_type_id` bigint(20) unsigned NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reviews_user_room_type_unique` (`user_id`,`room_type_id`),
  KEY `reviews_user_id_foreign` (`user_id`),
  KEY `reviews_room_type_id_foreign` (`room_type_id`),
  CONSTRAINT `reviews_room_type_id_foreign` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_rating_range_check` CHECK (`rating` between 1 and 5)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_type_amenities`
--

DROP TABLE IF EXISTS `room_type_amenities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_type_amenities` (
  `room_type_id` bigint(20) unsigned NOT NULL,
  `amenity_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`room_type_id`,`amenity_id`),
  KEY `room_type_amenities_amenity_id_foreign` (`amenity_id`),
  CONSTRAINT `room_type_amenities_amenity_id_foreign` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `room_type_amenities_room_type_id_foreign` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_type_amenities`
--

LOCK TABLES `room_type_amenities` WRITE;
/*!40000 ALTER TABLE `room_type_amenities` DISABLE KEYS */;
INSERT INTO `room_type_amenities` VALUES (1,1),(1,2),(1,3),(1,4),(2,1),(2,2),(2,3),(2,4),(2,5),(2,6),(3,1),(3,2),(3,3),(3,4),(3,5),(3,6),(4,1),(4,2),(4,3),(4,4),(4,5),(4,6),(4,7),(5,1),(5,2),(5,3),(5,4),(5,5),(5,6),(5,7),(5,8),(5,9);
/*!40000 ALTER TABLE `room_type_amenities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_types`
--

DROP TABLE IF EXISTS `room_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `max_adults` int(11) NOT NULL,
  `max_children` int(11) NOT NULL DEFAULT 0,
  `max_guests` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_types`
--

LOCK TABLES `room_types` WRITE;
/*!40000 ALTER TABLE `room_types` DISABLE KEYS */;
INSERT INTO `room_types` VALUES (1,'Phòng Đơn Tiêu Chuẩn',10000.00,1,0,1,'Phòng dành cho 1 khách','images/rooms/1.jpg','2026-06-05 11:27:37','2026-06-05 11:27:37'),(2,'Phòng Đôi Tiêu Chuẩn',20000.00,2,1,3,'Phòng dành cho 2 người lớn và 1 trẻ em','images/rooms/2.jpg','2026-06-05 11:27:37','2026-06-05 11:27:37'),(3,'Phòng Triple',50000.00,3,1,4,'Phòng dành cho nhóm khách','images/rooms/3.jpg','2026-06-05 11:27:37','2026-06-05 11:27:37'),(4,'Phòng Gia Đình',100000.00,4,2,6,'Phòng dành cho gia đình','images/rooms/4.jpg','2026-06-05 11:27:37','2026-06-05 11:27:37'),(5,'Phòng VIP',100000.00,2,2,4,'Phòng cao cấp với nhiều tiện nghi','images/rooms/5.jpg','2026-06-05 11:27:37','2026-06-05 11:27:37');
/*!40000 ALTER TABLE `room_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_number` varchar(20) NOT NULL,
  `room_type_id` bigint(20) unsigned NOT NULL,
  `floor` int(11) NOT NULL,
  `status` enum('available','soon_to_checkin','occupied','soon_to_checkout','cleaning','maintenance','booked','overdue') DEFAULT 'available',
  `needs_cleaning` tinyint(1) NOT NULL DEFAULT 0,
  `cleaning_requested_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rooms_room_number_unique` (`room_number`),
  KEY `rooms_room_type_id_foreign` (`room_type_id`),
  CONSTRAINT `rooms_room_type_id_foreign` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (1,'101',1,1,'available',0,NULL,'2026-06-05 11:27:37','2026-06-06 03:44:53'),(2,'102',1,1,'available',0,NULL,'2026-06-05 11:27:37','2026-06-06 05:59:52'),(3,'103',1,1,'available',0,NULL,'2026-06-05 11:27:37','2026-06-06 07:36:05'),(4,'104',5,1,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(5,'105',5,1,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(6,'201',1,2,'available',0,NULL,'2026-06-05 11:27:37','2026-06-06 03:37:36'),(7,'202',1,2,'available',0,NULL,'2026-06-05 11:27:37','2026-06-06 07:36:39'),(8,'203',4,2,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(9,'204',4,2,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(10,'205',5,2,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(11,'301',1,3,'available',0,NULL,'2026-06-05 11:27:37','2026-06-06 09:36:10'),(12,'302',1,3,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(13,'303',4,3,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(14,'304',5,3,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(15,'305',3,3,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(16,'401',2,4,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 16:20:26'),(17,'402',2,4,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(18,'403',4,4,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(19,'404',3,4,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(20,'405',3,4,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(21,'501',2,5,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(22,'502',2,5,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(23,'503',4,5,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(24,'504',3,5,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(25,'505',3,5,'available',0,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37');
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_permissions_user_key_unique` (`user_id`,`permission_key`),
  CONSTRAINT `user_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permissions`
--

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(2048) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `role` enum('customer','receptionist','admin') NOT NULL DEFAULT 'customer',
  `verified` tinyint(4) NOT NULL DEFAULT 0,
  `otp_code` varchar(255) DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_google_id_unique` (`google_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (19,'admin','$2y$12$uqUjpZolzToL3Pe9sJhGXentQOvhMBKydv2BMywlJL2HRZCif95cG','Quản trị hệ thống','admin@hotel.local',NULL,NULL,NULL,'admin',1,NULL,NULL,NULL,'2026-10-07 08:34:07','2026-10-07 08:34:07'),(20,'staff','$2y$12$pg09PO3Wuylj/SEyGqMdFu2g8zXdWQ4U/IXMftE87r7IGjKFW067S','Nhân viên lễ tân','staff@hotel.local',NULL,NULL,NULL,'receptionist',1,NULL,NULL,NULL,'2026-10-07 08:34:07','2026-10-07 08:34:07');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'qlkhachsan'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-07 15:35:53
