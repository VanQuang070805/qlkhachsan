-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: qlkhachsan
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
INSERT INTO `booking_rooms` VALUES (40,12),(41,14),(42,1),(43,6),(44,11),(45,12),(46,12),(47,12),(48,12),(49,7),(50,2),(51,2),(52,1),(53,1),(54,1),(55,22),(56,14),(57,13),(58,7),(59,1),(60,1),(61,1),(62,16),(63,16);
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
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (40,7,'hoaii','thanhhoai11112005@gmail.com','0987654321','2026-06-07','2026-06-08','2026-06-06 19:44:29','2026-06-06 19:47:27',1,0,20000.00,0.00,0,0.00,'cash','paid','completed',NULL,NULL,'none',0.00,'2026-06-06 12:41:28','2026-06-06 12:47:27'),(41,4,'Hoai','thanhhoai11112005@gmail.com','0987777777','2026-06-06','2026-06-07','2026-06-06 19:52:37','2026-06-06 19:52:53',1,0,2000000.00,0.00,0,0.00,'cash','paid','completed',NULL,NULL,'none',0.00,'2026-06-06 12:52:37','2026-06-06 12:52:53'),(42,4,'Nguyen Thi Loan','26a4041708@hvnh.edu.vn','0357745893','2026-06-07','2026-06-08','2026-06-06 20:11:47',NULL,1,0,20000.00,0.00,0,0.00,'vietqr','paid','checked_in',NULL,NULL,'none',0.00,'2026-06-06 13:08:02','2026-06-06 13:11:47'),(43,7,'hoaii','thanhhoai11112005@gmail.com','0987654321','2026-06-07','2026-06-08',NULL,NULL,1,0,20000.00,0.00,0,0.00,NULL,'pending','pending',NULL,NULL,'none',0.00,'2026-06-06 13:16:47','2026-06-06 13:16:47'),(44,7,'hoaii','thanhhoai11112005@gmail.com','0987654321','2026-06-07','2026-06-08','2026-06-06 20:26:00',NULL,1,0,20000.00,0.00,0,0.00,'vietqr','paid','checked_in',NULL,NULL,'none',0.00,'2026-06-06 13:18:48','2026-06-06 13:26:01'),(45,4,'a','thanhhoai11112005@gmail.com','0987654321','2026-06-06','2026-06-07','2026-06-06 20:24:27','2026-06-06 20:25:21',1,0,20000.00,0.00,0,0.00,'cash','paid','completed',NULL,NULL,'none',0.00,'2026-06-06 13:24:27','2026-06-06 13:25:21'),(46,4,'a','thanhhoai11112005@gmail.com','0987654321','2026-06-06','2026-06-07','2026-06-06 20:24:34',NULL,1,0,20000.00,0.00,0,0.00,NULL,'pending','checked_in',NULL,NULL,'none',0.00,'2026-06-06 13:24:34','2026-06-06 13:24:34'),(47,4,'a','thanhhoai11112005@gmail.com','0987654321','2026-06-06','2026-06-07','2026-06-06 20:24:35',NULL,1,0,20000.00,0.00,0,0.00,NULL,'pending','checked_in',NULL,NULL,'none',0.00,'2026-06-06 13:24:35','2026-06-06 13:24:35'),(48,4,'a','thanhhoai11112005@gmail.com','0987654321','2026-06-06','2026-06-07','2026-06-06 20:24:35',NULL,1,0,20000.00,0.00,0,0.00,NULL,'pending','checked_in',NULL,NULL,'none',0.00,'2026-06-06 13:24:35','2026-06-06 13:24:35'),(49,7,'hoaii','thanhhoai11112005@gmail.com','0987654321','2026-06-07','2026-06-08','2026-06-06 20:30:19','2026-06-06 20:30:40',1,0,20000.00,0.00,0,0.00,'cash','paid','completed',NULL,NULL,'none',0.00,'2026-06-06 13:29:09','2026-06-06 13:30:40'),(50,15,'Quang Văn','huaquanghan114@gmail.com','0945843588','2026-09-30','2026-10-01',NULL,NULL,1,0,20000.00,0.00,0,10000.00,'momo','pending','cancelled','2026-09-29 19:31:04','s','none',0.00,'2026-09-29 12:29:31','2026-09-29 12:31:04'),(51,15,'Quang Văn','huaquanghan114@gmail.com','0945843588','2026-09-30','2026-10-01',NULL,NULL,1,0,20000.00,0.00,0,10000.00,'cash','pending','cancelled','2026-09-29 21:17:52','s','none',0.00,'2026-09-29 13:28:30','2026-09-29 14:17:52'),(52,15,'Quang Văn','huaquanghan114@gmail.com','0945843588','2026-09-30','2026-10-01',NULL,NULL,1,0,20000.00,0.00,0,10000.00,'cash','pending','cancelled','2026-09-29 21:26:57','y','none',0.00,'2026-09-29 14:02:05','2026-09-29 14:26:57'),(53,15,'Quang Văn','huaquanghan114@gmail.com','0945843588','2026-09-30','2026-10-01',NULL,NULL,1,0,200000.00,0.00,0,100000.00,'vietqr','pending','cancelled','2026-09-29 21:28:03','s','none',0.00,'2026-09-29 14:27:29','2026-09-29 14:28:03'),(54,15,'Quang Văn','huaquanghan114@gmail.com','0945843588','2026-09-30','2026-10-03','2026-09-30 02:48:52','2026-09-30 10:54:56',1,0,600000.00,0.00,0,100000.00,'cash','paid','completed',NULL,NULL,'none',0.00,'2026-09-29 15:07:51','2026-09-30 03:54:56'),(55,NULL,'Nguyễn Văn A','customer@gmail.com','0901234567','2026-09-29','2026-10-02','2026-09-29 02:34:26','2026-09-30 02:50:03',2,1,1500000.00,0.00,0,500000.00,'cash','paid','completed',NULL,NULL,'none',0.00,'2026-09-29 19:34:26','2026-09-29 19:50:03'),(56,NULL,'Văn Quang','','0945843588','2026-09-30','2026-10-13','2026-09-30 10:56:37','2026-09-30 10:57:51',1,1,26200000.00,0.00,0,0.00,'cash','paid','completed',NULL,NULL,'none',0.00,'2026-09-30 03:56:37','2026-09-30 03:57:51'),(57,15,'Quang Văn','huaquanghan114@gmail.com','0945843588','2026-09-30','2026-10-01',NULL,NULL,1,0,1200000.00,0.00,0,600000.00,'cash','pending','cancelled','2026-09-30 13:47:24','Bận','none',0.00,'2026-09-30 06:46:45','2026-09-30 06:47:24'),(58,15,'Quang Văn','huaquanghan114@gmail.com','0945843588','2026-09-30','2026-10-01',NULL,NULL,1,0,200000.00,0.00,0,100000.00,'cash','pending','cancelled','2026-09-30 16:13:37','k','none',0.00,'2026-09-30 09:12:59','2026-09-30 09:13:37'),(59,15,'Quang Văn','huaquanghan114@gmail.com','0945843588','2026-10-01','2026-10-02',NULL,NULL,1,0,200000.00,0.00,0,100000.00,'vietqr','paid','cancelled','2026-10-01 01:34:46',NULL,'none',0.00,'2026-09-30 18:26:43','2026-09-30 18:34:46'),(60,15,'Quang Văn','huaquanghan114@gmail.com','0945843588','2026-10-01','2026-10-02',NULL,NULL,1,0,200000.00,0.00,0,100000.00,'zalopay','pending','cancelled','2026-10-01 01:37:00',NULL,'none',0.00,'2026-09-30 18:35:06','2026-09-30 18:37:00'),(61,15,'Quang Văn','huaquanghan114@gmail.com','0945843588','2026-10-01','2026-10-02','2026-10-01 14:15:10','2026-10-01 14:18:44',1,0,400000.00,0.00,0,100000.00,'vietqr','paid','completed',NULL,NULL,'none',0.00,'2026-10-01 07:05:48','2026-10-01 07:18:44'),(62,15,'Quang Văn','huaquanghan114@gmail.com','0945843588','2026-10-01','2026-10-02',NULL,NULL,1,0,650000.00,0.00,0,325000.00,NULL,'pending','cancelled','2026-10-01 17:14:37',NULL,'none',0.00,'2026-10-01 09:47:56','2026-10-01 10:14:37'),(63,15,'Quang Văn','huaquanghan114@gmail.com','0945843588','2026-10-02','2026-10-03',NULL,NULL,1,0,650000.00,0.00,0,325000.00,NULL,'pending','cancelled','2026-10-01 17:14:30',NULL,'none',0.00,'2026-10-01 10:14:04','2026-10-01 10:14:30');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
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
  `embedding` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`embedding`)),
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
INSERT INTO `knowledge_chunks` VALUES (1,'Posh Boutique · royal-hotel-concierge','royal-hotel-concierge','# Hướng dẫn tư vấn khách hàng — Royal Concierge','dd6042c60f04a6d23350e7d1bb7da31ebe8277ad88370c4d0dda9c4e2e71892d',NULL,NULL,'2026-10-01 07:21:38','2026-10-01 07:21:38'),(2,'Posh Boutique · Cách trò chuyện','Cách trò chuyện','- Trò chuyện bằng ngôn ngữ khách đang dùng, ưu tiên tiếng Việt tự nhiên, thân thiện và lịch sự.\n- Trả lời thẳng vào câu hỏi trước; nếu khách hỏi nhiều ý, trả lời đủ từng ý trong cùng lượt. Không lặp lời chào ở mọi tin nhắn.\n- Với chuyện đời thường, kiến thức phổ thông và câu hỏi ngoài nghiệp vụ khách sạn, hãy trả lời tự nhiên bằng năng lực của Gemini; không ép mọi câu hỏi thành câu hỏi về phòng.\n- Khi thiếu dữ kiện để tra cứu, hỏi đúng phần còn thiếu bằng một câu ngắn gọn. Có thể xác nhận lại ngày, số khách hoặc số phòng theo cách hội thoại, không yêu cầu khách nhập lại những gì họ vừa cung cấp.\n- Không nói “theo nguồn”, “theo Knowledge Base”, không hiện tên tài liệu, trích dẫn hay dòng “Nguồn”. Không bịa sự kiện riêng của Posh Boutique để làm câu trả lời có vẻ đầy đủ.\n- Khi không tìm thấy thông tin đã xác nhận, nói rõ chưa có thông tin chắc chắn và hướng khách kiểm tra với lễ tân; không đoán.','b17dbb9ecaa644bf980e8961fe93734d301f4be7e2d037b8ee949231d308b1ad',NULL,NULL,'2026-10-01 07:21:38','2026-10-01 07:21:38'),(3,'Posh Boutique · Phòng và sức chứa đã xác nhận','Phòng và sức chứa đã xác nhận','Danh mục công khai hiện có năm hạng phòng:\n\n- Phòng Đơn Tiêu Chuẩn: tối đa 1 khách.\n- Phòng Đôi Tiêu Chuẩn: tối đa 3 khách, gồm 2 người lớn và 1 trẻ em.\n- Phòng Ba: tối đa 4 khách, gồm 3 người lớn.\n- Phòng Gia Đình: tối đa 6 khách, gồm 4 người lớn.\n- Phòng VIP: tối đa 4 khách, gồm 2 người lớn theo danh mục.\n\nSức chứa là căn cứ để gợi ý loại phòng; không tự suy ra diện tích, kiểu giường, tầm nhìn, tiện nghi hay quyền lợi nếu không có dữ liệu xác nhận.','b614d877618a5289ece7a4c0302dc751da641159cab8c8504b56784453add106',NULL,NULL,'2026-10-01 07:21:38','2026-10-01 07:21:38'),(4,'Posh Boutique · Giá và tình trạng phòng','Giá và tình trạng phòng','- Giá hiển thị và số lượng phòng có thể thay đổi. Với giá hiện tại, hãy dùng công cụ `getRoomTypes`; không lấy giá mẫu từ câu trả lời cũ hoặc nội dung quảng cáo tĩnh.\n- Giá cho toàn bộ kỳ nghỉ có thể phụ thuộc ngày lưu trú và quy tắc giá đang chạy. Không tự nhân giá niêm yết để cam kết tổng tiền nếu chưa có công cụ tính giá chính thức.\n- Để kiểm tra phòng trống cần ngày nhận phòng, ngày trả phòng, tổng số khách và số phòng khách muốn đặt. Dùng công cụ `searchAvailableRooms` khi đã đủ dữ kiện hợp lệ.\n- Nếu khách nói muốn hai (hoặc nhiều) phòng, truyền đúng số phòng cần tìm. Chỉ nói đáp ứng đủ khi kết quả `enough_for_request` là `true`; nếu không đủ, nêu số lượng thực tế và hỏi khách muốn đổi hạng phòng hoặc ngày ở không.\n- Không bao giờ khẳng định đã giữ phòng hoặc hoàn tất đặt phòng. Chatbot chỉ tra cứu; khách tiếp tục xác nhận trên luồng đặt phòng của website.','7c80fffcc39a6782b81c57adb21baa9f2f75a5787e50238b92bdc094e940608f',NULL,NULL,'2026-10-01 07:21:38','2026-10-01 07:21:38'),(5,'Posh Boutique · Nhận phòng, trả phòng và đặt phòng','Nhận phòng, trả phòng và đặt phòng','- Thời gian nhận phòng được xác nhận trong hệ thống: 12:00–17:00. Sau 17:00, không thể chọn ngày hiện tại làm ngày nhận phòng.\n- Trả phòng tiêu chuẩn trước 12:00. Trả phòng thực tế sau 13:00 phát sinh phụ thu bằng 50% giá một đêm của phòng.\n- Một đơn đang chờ thanh toán giữ phòng trong 30 phút.\n- Hướng dẫn khách vào mục **Tìm phòng trống** trên website để chọn ngày, số người, xem dữ liệu mới nhất và tự xác nhận đặt phòng.\n- Phương thức thanh toán khả dụng được hiển thị ở bước thanh toán của đơn; không yêu cầu khách gửi số thẻ, mật khẩu, OTP hoặc mã xác thực qua chat.','080bdfa62d04e4f9f5be3ddf450a7f9bfd6cb315570210527bb950bd79def357',NULL,NULL,'2026-10-01 07:21:38','2026-10-01 07:21:38'),(6,'Posh Boutique · Hủy và hoàn tiền','Hủy và hoàn tiền','- Khách gửi yêu cầu hủy từ mục kỳ nghỉ của chính mình khi trạng thái đơn cho phép.\n- Điều kiện hoàn tiền phụ thuộc trạng thái thanh toán, ngày nhận phòng và lịch ngày lễ mà hệ thống đang áp dụng. Logic hiện hành yêu cầu hủy trước ít nhất 5 ngày với ngày thường hoặc 3 ngày với cuối tuần/ngày lễ; yêu cầu còn phụ thuộc đơn đã thanh toán hay chưa.\n- Không xem hoặc tra cứu đơn cá nhân trong chat công khai. Hướng khách đăng nhập và mở kỳ nghỉ của mình, hoặc liên hệ lễ tân để kiểm tra điều kiện cụ thể.','fa13c6bb2899cb02474643284192adda98c55b1b01789f972d0186bf6dd5855d',NULL,NULL,'2026-10-01 07:21:38','2026-10-01 07:21:38'),(7,'Posh Boutique · Quyền riêng tư và phạm vi hỗ trợ','Quyền riêng tư và phạm vi hỗ trợ','- Chatbot chỉ phục vụ khách hàng. Không tiết lộ hồ sơ đặt phòng, thông tin liên hệ, doanh thu, lịch làm việc, tài khoản, ghi chú nội bộ hoặc dữ liệu của nhân viên/khách khác.\n- Không có quyền hủy, sửa, tạo đặt phòng hoặc thay đổi thanh toán. Chỉ cung cấp kiến thức công khai và kết quả tra cứu phòng đã được công cụ cho phép trả về.\n- Nếu khách cần hỗ trợ về một đặt phòng cá nhân, hướng dẫn đăng nhập vào tài khoản trên website; không yêu cầu họ gửi mật khẩu, OTP hay thông tin thanh toán vào cuộc trò chuyện.','6390f124a2d6694e816877642c0ab99654cc596201415012accbf49e6ea45987',NULL,NULL,'2026-10-01 07:21:38','2026-10-01 07:21:38'),(8,'Posh Boutique · Hạng phòng và sức chứa','Hạng phòng và sức chứa','Thông tin dưới đây được đối chiếu với danh mục hạng phòng công khai của Posh Boutique ngày 30/09/2026. Sức chứa tối đa là tổng số khách theo danh mục; không suy diễn về diện tích, loại giường, tầm nhìn, tiện ích hoặc đặc quyền nếu chưa có thông tin xác nhận.\n\n- **Phòng Đơn Tiêu Chuẩn**: dành cho 1 khách; tối đa 1 khách.\n- **Phòng Đôi Tiêu Chuẩn**: dành cho 2 người lớn và 1 trẻ em; tối đa 3 khách.\n- **Phòng Triple**: dành cho nhóm khách; tối đa 4 khách, trong đó tối đa 3 người lớn theo danh mục.\n- **Phòng Gia Đình**: dành cho gia đình; tối đa 6 khách, trong đó tối đa 4 người lớn theo danh mục.\n- **Phòng VIP**: hạng phòng cao cấp; tối đa 4 khách, trong đó tối đa 2 người lớn theo danh mục.\n\nKhi tư vấn, hãy dựa vào số khách và sức chứa được nêu ở trên. Nếu khách yêu cầu kiểm tra nhiều phòng còn trống, cần ngày nhận phòng, ngày trả phòng và tổng số khách; hỏi lại dữ kiện còn thiếu thay vì khẳng định còn phòng. Tình trạng phòng và giá thay đổi không thuộc tài liệu này, vì vậy không được suy ra từ Knowledge Base. Hướng khách dùng chức năng **Tìm phòng trống** trên website để kiểm tra dữ liệu hiện tại.','54280c75ce7605c5143e9dd2f157463cb61128527341d3fc17338d91242e02ed',NULL,NULL,'2026-10-01 07:21:38','2026-10-01 07:21:38'),(9,'Posh Boutique · Nhận và trả phòng','Nhận và trả phòng','Khách nhận phòng từ 12:00 đến 17:00. Sau 17:00, hệ thống không cho chọn ngày hiện tại làm ngày nhận phòng. Khách trả phòng trước 12:00. Trả phòng thực tế sau 13:00 mới phát sinh phụ thu; phụ thu bằng 50% giá một đêm của phòng.','bc77964e73bdb3efc606ffa2328310622af330232eb1ac135c305ccdbd307ab0',NULL,NULL,'2026-10-01 07:21:38','2026-10-01 07:21:38'),(10,'Posh Boutique · Tìm và đặt phòng','Tìm và đặt phòng','Khách chọn ngày nhận, ngày trả và số người để hệ thống kiểm tra phòng trống theo dữ liệu hiện tại. Sức chứa của các phòng được chọn phải bằng hoặc lớn hơn tổng số khách. Một đơn chờ thanh toán giữ phòng trong 30 phút. Giá và phòng trống phải được tra cứu trực tiếp, không suy đoán từ tài liệu này.','c05622775c45e358c474318d794a8681538488f450e3e1de3e5f4ac0f2a18d81',NULL,NULL,'2026-10-01 07:21:38','2026-10-01 07:21:38'),(11,'Posh Boutique · Thanh toán','Thanh toán','Posh Boutique hỗ trợ các phương thức được hiển thị tại bước thanh toán, gồm thanh toán trực tuyến và tiền mặt khi phương thức đó khả dụng cho đơn. Thông tin thẻ, mật khẩu và mã xác thực không được cung cấp cho chatbot. Trạng thái và số tiền của một đơn phải được lấy từ dữ liệu đặt phòng của chính khách đang đăng nhập.','fb2e4390c8d15a2c9e94c66c28b067e5fc10966cd1b37dc6b09442c34f9c8043',NULL,NULL,'2026-10-01 07:21:38','2026-10-01 07:21:38'),(12,'Posh Boutique · Hủy và hoàn tiền','Hủy và hoàn tiền','Khách có thể gửi yêu cầu hủy từ mục kỳ nghỉ của mình khi trạng thái đơn cho phép. Điều kiện hoàn tiền phụ thuộc trạng thái thanh toán, ngày nhận phòng và chính sách đang áp dụng trên đơn. Với đơn đã xác nhận hoặc đã thanh toán, khách nên mở chi tiết kỳ nghỉ hoặc liên hệ Posh Boutique để được kiểm tra chính xác.','f931b7a1562bde99626a1648712b7688f3cf4db24f8b8507c9680e002c371e50',NULL,NULL,'2026-10-01 07:21:38','2026-10-01 07:21:38'),(13,'Posh Boutique · Quyền riêng tư và hỗ trợ','Quyền riêng tư và hỗ trợ','Posh Concierge chỉ đọc kỳ nghỉ của tài khoản khách đang đăng nhập. Trợ lý không yêu cầu mật khẩu, mã OTP, dữ liệu thẻ hoặc khóa bí mật. Khi thiếu dữ kiện hoặc không chắc chắn, trợ lý phải nói rõ giới hạn và hướng dẫn khách đến trang phù hợp.','8d7bfcdff48a89b59751865ea1b749ba71b8b5dafd2428f8c30c9505fddf44ef',NULL,NULL,'2026-10-01 07:21:38','2026-10-01 07:21:38');
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2026_01_01_000001_create_users_table',1),(4,'2026_01_01_000002_create_password_resets_table',1),(5,'2026_01_01_000003_create_room_types_table',1),(6,'2026_01_01_000004_create_rooms_table',1),(7,'2026_01_01_000005_create_amenities_tables',1),(8,'2026_01_01_000006_create_holidays_table',1),(9,'2026_01_01_000007_create_bookings_table',1),(10,'2026_01_01_000008_create_remaining_tables',1),(11,'2026_06_06_102740_add_soon_to_checkout_to_bookings_status',2),(12,'2026_06_06_112844_add_deposit_amount_to_bookings',2),(13,'2026_06_06_113630_add_cash_to_payment_method_bookings',3),(14,'2026_06_06_133315_add_booked_to_rooms_status',4),(15,'2026_06_06_033237_create_sessions_table',5),(16,'2026_06_07_000000_replace_price_policies_with_price_settings',5),(17,'2026_06_08_042725_add_late_checkout_fee_to_bookings',5),(18,'2026_06_09_054621_add_waive_late_fee_to_bookings',5),(19,'2026_09_25_000001_add_unique_review_constraint',5),(20,'2026_09_25_000001_create_report_snapshots_table',5),(21,'2026_09_25_000002_create_knowledge_chunks_table',5),(22,'2026_09_25_000003_add_google_identity_to_users_table',5),(23,'2026_09_29_000001_harden_payment_logs',6),(24,'2026_09_29_000002_expand_user_otp_code',7);
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
  `raw_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_response`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_logs_reference_unique` (`reference_code`),
  KEY `payment_logs_booking_id_foreign` (`booking_id`),
  KEY `payment_logs_reference_code_index` (`reference_code`),
  KEY `payment_logs_transaction_id_index` (`transaction_id`),
  CONSTRAINT `payment_logs_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_logs`
--

LOCK TABLES `payment_logs` WRITE;
/*!40000 ALTER TABLE `payment_logs` DISABLE KEYS */;
INSERT INTO `payment_logs` VALUES (1,40,'vietqr','62181590','KS40XLDQ',10000.00,'deposit','success','{\"id\":\"62181590\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 02:42:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063345834607099904 Scan QR KS40XLDQ\",\"reference_number\":\"FT26159447861207\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}','2026-06-06 12:42:14','2026-06-06 12:42:41'),(2,40,'vietqr','62181590',NULL,20000.00,'deposit','success','{\"id\":\"62181590\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 02:42:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063345834607099904 Scan QR KS40XLDQ\",\"reference_number\":\"FT26159447861207\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}','2026-06-06 12:42:41','2026-06-06 12:42:41'),(3,42,'vietqr','62181963','KS42MAM9',10000.00,'deposit','success','{\"id\":\"62181963\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 03:10:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063352889835651072 Scan QR KS42MAM9\",\"reference_number\":\"FT26159414090469\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}','2026-06-06 13:10:04','2026-06-06 13:10:45'),(4,42,'vietqr','62181963',NULL,20000.00,'deposit','success','{\"id\":\"62181963\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 03:10:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063352889835651072 Scan QR KS42MAM9\",\"reference_number\":\"FT26159414090469\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}','2026-06-06 13:10:46','2026-06-06 13:10:46'),(5,44,'vietqr','62182081','KS44ZVSA',10000.00,'deposit','success','{\"id\":\"62182081\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 03:19:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063355067916877824 Scan QR KS44ZVSA\",\"reference_number\":\"FT26159233901559\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}','2026-06-06 13:19:01','2026-06-06 13:19:29'),(6,44,'vietqr','62182081',NULL,20000.00,'deposit','success','{\"id\":\"62182081\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 03:19:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063355067916877824 Scan QR KS44ZVSA\",\"reference_number\":\"FT26159233901559\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}','2026-06-06 13:19:29','2026-06-06 13:19:29'),(7,49,'vietqr','62182180','KS49KHXD',10000.00,'deposit','success','{\"id\":\"62182180\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 03:29:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063357705363513344 Scan QR KS49KHXD\",\"reference_number\":\"FT26159095787473\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}','2026-06-06 13:29:27','2026-06-06 13:29:56'),(8,49,'vietqr','62182180',NULL,20000.00,'deposit','success','{\"id\":\"62182180\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 03:29:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063357705363513344 Scan QR KS49KHXD\",\"reference_number\":\"FT26159095787473\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}','2026-06-06 13:29:56','2026-06-06 13:29:56'),(9,50,'vietqr',NULL,'KS50FUXP',10000.00,'deposit','pending',NULL,'2026-09-29 12:29:52','2026-09-29 12:29:52'),(10,53,'vietqr',NULL,'KS536KWS',100000.00,'deposit','pending',NULL,'2026-09-29 14:27:36','2026-09-29 14:27:36'),(11,55,'cash','cash-55-31e1da38-2d45-495f-8d29-9b9ef75a721f',NULL,1000000.00,'checkout','success','{\"received_by\":16}','2026-09-29 19:50:03','2026-09-29 19:50:03'),(12,54,'cash','cash-54-02413b0f-595e-41f1-955a-51ea8c03b112',NULL,600000.00,'checkout','success','{\"received_by\":16}','2026-09-30 03:54:56','2026-09-30 03:54:56'),(13,56,'cash','cash-56-b49180e9-2ec0-4a9d-87c8-4f14afc3a54f',NULL,26200000.00,'checkout','success','{\"received_by\":16}','2026-09-30 03:57:51','2026-09-30 03:57:51'),(14,59,'vietqr','85924030','KS59FMOB',100000.00,'deposit','success','{\"id\":\"85924030\"}','2026-09-30 18:26:52','2026-09-30 18:29:30'),(15,61,'vietqr','86056256','KS61LK3M',100000.00,'deposit','success','{\"id\":\"86056256\"}','2026-10-01 07:06:04','2026-10-01 07:07:38'),(16,61,'vietqr','86058493','CO61LBNI',300000.00,'checkout','success','{\"id\":\"86058493\"}','2026-10-01 07:16:08','2026-10-01 07:18:44');
/*!40000 ALTER TABLE `payment_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price_settings`
--

DROP TABLE IF EXISTS `price_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `price_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `adjustment_type` enum('percent','fixed') NOT NULL,
  `adjustment_value` decimal(10,2) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
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
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,3,2,5,'Phòng sạch sẽ, nhân viên thân thiện','2026-06-05 11:27:37','2026-06-05 11:27:37'),(2,3,1,4,'Giá hợp lý, đầy đủ tiện nghi','2026-06-05 11:27:37','2026-06-05 11:27:37'),(3,4,5,5,'tuyệt cú mèo','2026-06-06 12:53:42',NULL),(4,7,1,5,'Quá tuyệt','2026-06-06 13:33:20',NULL),(5,15,1,5,'Bữa sáng chuẩn vị, phong phú và rất ngon miệng.','2026-09-30 09:10:29',NULL);
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
INSERT INTO `room_types` VALUES (1,'Phòng Đơn Tiêu Chuẩn',200000.00,1,0,1,'Phòng dành cho 1 khách','images/rooms/1.jpg','2026-06-05 11:27:37','2026-06-05 11:27:37'),(2,'Phòng Đôi Tiêu Chuẩn',650000.00,2,1,3,'Phòng dành cho 2 người lớn và 1 trẻ em','images/rooms/2.jpg','2026-06-05 11:27:37','2026-06-05 11:27:37'),(3,'Phòng Triple',900000.00,3,1,4,'Phòng dành cho nhóm khách','images/rooms/3.jpg','2026-06-05 11:27:37','2026-06-05 11:27:37'),(4,'Phòng Gia Đình',1200000.00,4,2,6,'Phòng dành cho gia đình','images/rooms/4.jpg','2026-06-05 11:27:37','2026-06-05 11:27:37'),(5,'Phòng VIP',2000000.00,2,2,4,'Phòng cao cấp với nhiều tiện nghi','images/rooms/5.jpg','2026-06-05 11:27:37','2026-06-05 11:27:37');
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
INSERT INTO `rooms` VALUES (1,'101',1,1,'available','2026-06-05 11:27:37','2026-06-06 03:44:53'),(2,'102',1,1,'available','2026-06-05 11:27:37','2026-06-06 05:59:52'),(3,'103',1,1,'available','2026-06-05 11:27:37','2026-06-06 07:36:05'),(4,'104',5,1,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(5,'105',5,1,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(6,'201',1,2,'available','2026-06-05 11:27:37','2026-06-06 03:37:36'),(7,'202',1,2,'available','2026-06-05 11:27:37','2026-06-06 07:36:39'),(8,'203',4,2,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(9,'204',4,2,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(10,'205',5,2,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(11,'301',1,3,'available','2026-06-05 11:27:37','2026-06-06 09:36:10'),(12,'302',1,3,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(13,'303',4,3,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(14,'304',5,3,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(15,'305',3,3,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(16,'401',2,4,'available','2026-06-05 11:27:37','2026-06-05 16:20:26'),(17,'402',2,4,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(18,'403',4,4,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(19,'404',3,4,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(20,'405',3,4,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(21,'501',2,5,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(22,'502',2,5,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(23,'503',4,5,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(24,'504',3,5,'available','2026-06-05 11:27:37','2026-06-05 11:27:37'),(25,'505',3,5,'available','2026-06-05 11:27:37','2026-06-05 11:27:37');
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
INSERT INTO `sessions` VALUES ('2RFzj55Ab6Zn71D7jkXYegYHAS4pKDVpELDTR6t9',NULL,'127.0.0.1','curl/8.21.0','ZXlKcGRpSTZJbnBoV2xwelR6QmFSV0ZEZUVZNVZHZHVSVlJCZFVFOVBTSXNJblpoYkhWbElqb2lPQ3RKWmtVdmFXRjZTRU5zWTNWd1VtTXlSa2xHYzJGcFIyNDJXRkl6TUZCNlVYTXZTMUZvVFhWcFExVjFSbk5uUzNabGNYcDFkbUl6ZUcxVVFqQk1hM3BEUVc5WFRIbFRPVWh1TjFkRWFuRTVUbU5qYzAxNWJYaEtNREYwVkhCRmRVWlFkblY1WmpCcFNrODFUa0pITlRJMlZDdFlSRE5uYTNOS2JVUXdSVFZhYVZab1JubE9OVTF6WmpsaVpYQnNjbUo2YWxwdVJqTktSMDlhVWxkVWEzQk1TVTlqYTFKbFlrazRQU0lzSW0xaFl5STZJamhpWmpaa1ltSTVOR1k0T1ROaU9EYzJOR0kyTldObE5ESTBabVEzWm1ReU9UUmpZek13T1RJM05qQTNaVEUyTWpabVpUazNaREV5TW1NeE5HRXpOamNpTENKMFlXY2lPaUlpZlE9PQ==',1790847149),('5IcxfnQFMtupdYGUBD7wFigAYX1YEOy8wD3ZsmyI',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/154.0.0.0 Safari/537.36','ZXlKcGRpSTZJblZ1WlM5bmFYcDZTM3BTZWpWbFNXRjBlSGhKTUZFOVBTSXNJblpoYkhWbElqb2lUbWx0YTBoU2FVVkVORTVyUlV0emRHeHhOMnRrWldSTFdsSm5aVzEyTkdadGVFVmFkMHBTVGl0MFlWQmFlWGt5YldkRE0yTmljSFJLVm1sd1dYRjFTazhyYkhOVVZWbFJhMDFqUzBOSloxVkdWbTFhUlRWdmFEaExPVlZQYzB3eFl5dGxVRWhDZEZWUWRsQnVRMVY0UVdKU05qSm9lSGR0TjNGd1VYUTFkbGR4YXpWRE5qUkNabGxZTjI5NU1UazBUMHBEVlhaTVNrNUNZMm80Vkd0eGQyUkRUa3hRYjNCMlEwRkdWRkZUVGtKelNtNHZVV1JUZWtkWVJHcG5WRkIzTlZWRGRDOWlhMjEzU25OM1R6WnlTVVkzVG1aRVVUTnNVRU1yYjAxT05rTXlhalZIVm1ScmVVUm1TVzVtYURSUWNuVkdOVmxoV0c5Uk5sTmpLMnhsVjBGM1RXTXpWV280UzNwclZVczJZblJ6UWtab1dtdHdjVkpWWjFabVRpOVVMekp6WmpCUVpqWkdNRU5hYUVWdVVsUklRbTFsV1VWTVFXdERMMWc1TkRjaUxDSnRZV01pT2lKak9EQTBOVE5tTm1JNE1qWmpNREEzT0dFME1UZ3lNakkyTnpjd05HTmhNR1pqWTJZMk9HSTNZek0wWVRNeU5qaGhOR1F4TURNellUVXlZemMzTUROa0lpd2lkR0ZuSWpvaUluMD0=',1790851213),('5yIIfN4ync2F1Y4vipQLLEQz4pyadj9bSmvulMJV',NULL,'127.0.0.1','curl/8.21.0','ZXlKcGRpSTZJamhUVFVOQ2RETTBRbUoxVW5WMEwyNTRTbk41WlZFOVBTSXNJblpoYkhWbElqb2lUVUpEVVhOVGNVb3lPSEJ2VUdWUFdYVXlSazFLUVhkS1FUa3JMMUYxTVZsd2FsSlpibEUyWjNGNlptZFlURzlyZDI1TVJFSkJka2M1Tm0xb1lWaDVXVWg0ZWxSblR6ZzVjVU5qUjNGMEszaEJaVzFRUVRGWmNuTllVbE5HVDNoTU9FeHlMekZpUjBsUmQwNVdiMlZ0UWpjMVVVczJNR0ZsYWtOelJ6RmlObnBIVkZRNGIyUmFaa1JPUW04ckszTlVURXROYlZkRU9WaHNUelUwY0RWSFJIZFlUM041Ynk5UFMyRmFWWGRMUWtkQ1NHNUVZazExWm1adFNURjZkVGt3V1dOTFJFUnJORUZsYmxSaVlsVTFMMnh3Y2s5SFlXUjFTVXhuWWpVMVNtZDJSWGN5YkdoQ2NrZFRiazF2VERGMldrcEViRUZMY2tSVmRWUlNkRTl1T0hSSGJHRnlMMXBpTjIxTFQxUlZZbWR2WjJZeldqTm5VMEZNTVd4UlpIbDFWVFJ0TkZCeFJIbGpaMHhIVEhKaFNGTjZaVEp4UW04dmRuRkpTMFJ0SzNVaUxDSnRZV01pT2lKbU5qWTNaRGcxWVRBNU1qWXpObUl4WVRabVpURmlObUpqTnpOaFlqVXlOVGs1TUdJMk1XUXpNamMxT0RWbVpHVTVZVFJqT1RRNE9UZzRabVU0WkdNeUlpd2lkR0ZuSWpvaUluMD0=',1790850621),('7rrbz9QPMeFgoCnAakE6QPnwVy6P9AP4m6WJBE4L',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0','ZXlKcGRpSTZJaklyYkVObFpGRm5ObFpUYkRSVVoyVnFlRzlRTjNjOVBTSXNJblpoYkhWbElqb2lMMUpITDFsdWFYSkNiR3BQYm5sQ1Exa3JOSFZ3VVhOTmNIUnlUM2RSWjNCaE1UTnVObU5UUW0xc1VIRk5NRWRZSzFGSE5XTkxiblUwTnpGUk1FSmpZVU0zYTJwcVJtaERVbXhKV0hCUU5tcFJLekpxV0ZaRlFVeDNXalF2Y0hsa09VZHVTakJZU0VGRlYwZEVOWEZhVUV4NGVtOUZkR1EzUWpnM1lUZDVWMVV2Uml0elFtaFRZMGRLUWpOMFpXSmlSbGxMV0RSVFRqQldPWHBQWVZSNVdFc3pZM0ZpV1VjclprRldRVk5FV1d4QlowSldNVmd4WldkSGQxTTRWVzh5UlhCcVZHVlhRMUpzUmxCd1kydFNibnBxTUdweFlsSjZVMGRXT1cxbk1uTnRhMU42U0d0T2FUaFNWMGxTUjNvM2FuUkpZVTFYYjAxWGFYcE5ha2hOYW14S1IyOXVXblJySzFkSVlYWXJibmRqUzJzd1FsUnphMDFXTkZab2JWbGlVSEkxUzJGSFFtOTJSMFU5SWl3aWJXRmpJam9pTXpZNU0ySXdNbVExWlRobU9UQXlOR0pqTXpObFlqWTBPR00yTnpWak0yWmtZV1JqWXpobFl6Y3hNR1JtWm1ZNU9URTFNR0k0T1RnMU5qQmlNamxpWmlJc0luUmhaeUk2SWlKOQ==',1790849507),('96FtTUB5ofYSM5P8NFFFn4wqislktRrDAY1cMouQ',NULL,'127.0.0.1','curl/8.21.0','ZXlKcGRpSTZJblUzVXpJeGMwWlNOVzFpUW10U0szSklNbXRqYzNjOVBTSXNJblpoYkhWbElqb2lVbEl2Ums4cmMyVkpiRlZUVVdWV1VqUkphRXBqT1RjeVNqTlpTR1JPVVhCa1pIWlZObWxSVWt0TmR6Um1heXQ2VjJaVlpHOXdhekpSUjNjNVVWUTFZMVZHYzA1UVpuQjRZVmhrZFROSE1raFlOVGRxYm5WME5GUTFhWGh4TVRscFdrbDNLM2RzUm1neldGVlJSMVFyUVhKNFJqQlJRVFlyYW5nd2VXRmxhVXhuTWs4eVpuWklhV2d6U2xKNlJuQlZaWFY1ZEdadldHVXpTMU51WjFJdmFEbHdhMjk2U1RWbFlrRmxka2RYTlc5clQyWlRXbVZQVWxOUGVIZ3hPVU5OTUVGNFpYY3pUalpWV210RU1IazRjaTgzTUZWMk4xaG9SV3RSVFRObE5ua3daa2RCTVdoNWJuVktSbTlxZEc1V1pqWndOM0pwVjJ0UFEyOWxjVkJOTW5sRFRWZGhVQzlsVW1seU4xbFZUREpoSzBOSlNuaENXbGd2ZWpWR1drNUZPWEZqUmk5UlEzbDRSbk05SWl3aWJXRmpJam9pTXpnMVpXWTRZVGxqTUdRNU5EY3pPV00zTkdFMVlUVTFOR1JsTXpBd1pqa3pZV1pqTmpneE5qQmpNR0ZpT1RJNVpqTmxNelV3WVdGbFl6YzNOelV3WkNJc0luUmhaeUk2SWlKOQ==',1790849203),('9EPbVgCCY7oF8HT2QUNjltSWbugQhrVKOA4U2mcP',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0','ZXlKcGRpSTZJbXRSTDBoelVWaEdURmhRZEdGSmVHZE1SWEpqSzJjOVBTSXNJblpoYkhWbElqb2lhMlJNVW01aGFFWm5NekY1VFdSWFZIbEdhV0ZPYm5KYWVGRllVa05qYUVaR1lWWXhNR040VmtoNGNIWjNlVUpNVWxoUFdXOURWRFo2Tkc5UGJXdFhaalEzZVhKTVRUbGtTa3hpTDJOYVNqSkVTRlpPU0Uxdk5rcG1aMGhtUjBRM1dVTXlUbkJuYnpSQ04zcEpVRzVFVjFSa1kzaDZjMkkzTkRaQ01WRlpUbWRMZEd0eE5sQlJlR0pJVEc5VFNucHpSR3hDWWxneGNuVnVVSEZ5ZDFNeFUySlNOalp5VEVwQk9VeERSRWRFU1hOMFlWWkxTVWRGTlVwMGNtcDFMM0J4UjA5UFVFUTVhVXBFTXpNNWVrNW1aMjVFUnpGVVlWTjJVMHhSWm5wNWFHRnRhQzlyUnpGWlVVdDBNSEpvYXpOcllUbDVPV2RrYjFsdGFtRkpXVTVNUkZnNVJEWmhaVlUyTDJKdWVFeDVkRm8wYnpWUU9YRldjSFZXUzBNMFVUZE1abU5YT1hGSVdVTk5abTE2YjNaa2MyWnVWVXB6T1c1U1lrbzJablpFVmxaeFNuVmpWVUpuWWtKalF6aHlNRUpUWTFBeVFXSXZiV1ZMVGxwcmVIZzBVMEZ3TmxCTFUxRlZjak5yUFNJc0ltMWhZeUk2SWprNVl6QmxPRGxtTnpVNU9EUXlZamN3TlRZNU5XSTFNemhsTkdZd1lUVTJOMlF6TldSaE5qUmlaRFV6WkRjeVpqRmhNakEyWlRsalptRmxObUl4TkdVaUxDSjBZV2NpT2lJaWZRPT0=',1790849555),('BLUFAU6Me2zI6N0zkQRzRSK22LyVjzEuSMrNYXwQ',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26200; en-US) PowerShell/7.6.5','ZXlKcGRpSTZJaTlSVjJKUmJVVnpOMU5xZUhoQ1NrVXpMMEkzTDJjOVBTSXNJblpoYkhWbElqb2lMMjVIYjJ3elVtMUpNbVoyVml0WFkxSk5NRVZWVGxCcGVsQTRWblYwYm0welVVdHlURVZzZWt4cFRrMUhSbUV2U1VkSFQwWTNWbGxqY0V4MFRrbE1XbE5YUjI1VlVrZG5RelpGTVhveWFsQm1RVFp1YTJSVlVHZ3pVekJRYVdOc09ITlhObVZrYjJkVWNGRldla05CVUhwRllVOXBkRVZOWkdSaWFrWXpWbmszUkZwYU1XUjRTV2RrVUZVMlVXZG5WV0psYkhCNFpYa3dWRGw1TVhreldtTklXRU5uTmpkRU1WSmhNVWRFZGpkUGFtdG1abWRKTDNNMWFrYzROWGRMVlUxR0szSTVhVkZ0V0c1MVRHOVNObHBhZWpGcWJreGtjMkZRWkd4d1lscEtiakJ6YWpKNGRuRnhXSE5hZUZSM1RFcExiMlpFWW10UE4zcENlVEJGVW1sNlExVkJVVTlZVm0xa2FqUkNNWFpDTnpsek1HUmFXbVU1UXl0bE1ISlBOelZKTlRsaVNYWnJSbEU5SWl3aWJXRmpJam9pWW1JMllqTXhPVFV5TlRZME9UQXdZamxsTUdNM09HUTBOakEwTnpsa056bGxaak15WVRNM1ltUTBOalV3TURnMVpqWmtaREExTXpFeU5qTTVNR001TUNJc0luUmhaeUk2SWlKOQ==',1790846603),('cxzXD6awJbsyneiCAM0xGcGXGdfVvTSZnHaGFaZw',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/154.0.0.0 Safari/537.36','ZXlKcGRpSTZJbUpxVlhWck9GUTJVVXgyZGtjNVRIaE1hQ3R6T0ZFOVBTSXNJblpoYkhWbElqb2lkMncxUWxCRmVHNVFiMlZDWTNGVlRrazRiRE53T1dORUwyRm5SMU5ITUdGb1dsQkVSbXhMU1ZCNmRERkdlV2N6U21sUVEwaHZTbFppSzI1SVkwVlBZMkphZUU0MVYxcGtTVWxpTTNCWFZISkhSMXBYTldGNmJuQklOWEF2TVRocVEyTkZZbXQxYmpSMkswMXJlVm8yY1VWWlZqVXpiR05rVTNCblpXNHlhVkZ2Y1RaS2JucGlaalZOVmxGUUwwVk9PRWdyUkdWbFNuZ3dSbkp0YldoVFdpdFFNM3BXUlZCdlR6TndMMk5WTDFobFJXNVhZa3AwYkZkTFVrOVVObFoyU21OWmRGVkZURlJYUkd4Qkt5OVZRV2sxYWpKR1ExUXhjMGx3ZFVoaGVVZHVaMGxST1VzNGFFOW5UMHd6YmxWVmVUY3dibVZSVVRsalJUQk9TRkJXTlc5TFNETndPVkJQYzNBelpWRnljV2h4WVhaWU9IWjFTMk5vTlN0ek5sRkNjVWx2ZUcxMVNFNVRaVFpNTURJM1ZrNDBXRGw2U1RScWMzZGhkMmRHVUdwNE5FNURXamwyZFZwQk1DOHpVRkV4UWtsdVkwTndURWc1VTFOMmRqVldWMlZSVmtNNWFYUnhWemR2UFNJc0ltMWhZeUk2SWpRellXTm1aVGhtTm1ObFlUVTVZamRoTnpVeVlqUTBPVFEzTmpVNE9UTXdPRGxqTmpsak9ESmxaalE1WWpGbE5EQm1aV1UwWkRObFpERm1ORFJrWldJaUxDSjBZV2NpT2lJaWZRPT0=',1790851148),('D7va1kijlZQ6t52JA28W1HvTmLNMzoDIRJEmyfbi',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/154.0.0.0 Safari/537.36','ZXlKcGRpSTZJa05OWlhvMVdXRXJiM1E1YUdOM1Uyb3lhWEl3YjBFOVBTSXNJblpoYkhWbElqb2lUVVp5UmtWUFVYaGpNbUk1UjBkUVdUaENRMDlYYVVKNVF6TTNjakZsZWpGNVZXSnRVRmRtT1ZBM1FVbzBTMEZzVEhOTGIwOUtlVVZIVDNwM1dERlJPWEJxVjNKaGEyOTBhVUpZY1dkM2JreEdka3BqWTA5cGMwbHVkV04zV2s1R05HbGlRbTlSYWpoUlJtMVpTRkJMVFhBek9IVmhaVmRMVkRsTE9WZHhhbVpXYkdaS1dGVnhLM1ExTlhOd2NHUldPWEZCVkdkRlpVWlJhekF6UlROb2FGTnRaa1p4T1Vzd01WSkhkM2haYTFNMWMwSmxjVTVqWW5CSE9WVldjMmh4VVcxamNEazNVM2xrTXpGaFUwbzVZMDVEVURaSFRrRTRVMVZZYTJWbGIxZHpUMUpITDNWamFUWkxUemxKVFd0NWVFNVBUVGsyVFZGTWNXWlpkbTVPTjBobGQwcDBhR3RtZW5wTVdqRkNRVEkxYWsxak1TdGFkbFZFZDNGT2VXZFJXbkZqYUZkelluTnVXR1J0YW1oQ2RUWXJSMU5ZTDBob1VUZzBWV0ZwTVRkUFJXTXJNM1pFUlcwMmIzaHJSMHQ1T0c4eksydEtiQzgwU1hGd09GVldOSHBxTkdGV1FsbDJSa1pCUFNJc0ltMWhZeUk2SWpobE4yUmlZVGRrTlRNNU9EVTVNbUV3WWpjME1tRmtZV1ZpTURaa00yRTFaamRqTkRObFpqTTBNRFJpT1dVd1pqQXhObUUxTUdKa05XWmpNMkk0TTJZaUxDSjBZV2NpT2lJaWZRPT0=',1790850716),('depoZLxFqVLmgwnjJzfBdvHojHDZSh7bxblYuOeD',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444','ZXlKcGRpSTZJalZOV0U1a2NIWm9XWEIxTlZsdk5GQk5TRk5pU2xFOVBTSXNJblpoYkhWbElqb2lNMnRKVjIxTFYwaFFOVkI2VEVsQlNuVnpiRE0zVmk5eGNYaFdWbk40ZWtsbFFWZFJabGQ1UlRWVU5YVlBkMjFZY1RFeVNuSk1XWEk0ZEZkNGRqZ3pXR05vUXpsWGFVMVllbmxZVVhSWVluTkpla2xvVFRJeFlVRTVVREpTT1ZZdmIyUkdVRVpRTVRkSVNHUjFiV05yWTB0aVlTOHZXa1IwTDBkaVNrbHhNSFp1ZDFwUlRHbFBOM2RTZUZOSGMydEtSMmxVTXpCTmEzRjFja2hxYUVKaldHdFZjR3RZYTIwMmVFbDBaVE5sZFdsb2FHVktLMWRJWld0eFJpOTNObFpvVWpCWlUzWTFRemRHV1hwRlkzSkViRTVPYW5SUlIwcHRMMVpCWkdWQ05qVnlMemx5TVZVMU1EbElZbVozY0haamFHcDZWVTl0ZUVWT1NrazFZVUk1ZERkRlNEQjVhVGRxVFZCdlIwVmtjSE5OVGxkeFNXdDFkbVZYTWtVclNGRkJSVzEzZEVGaVVIZEpWV2M5SWl3aWJXRmpJam9pT0RCaU5qTTRORGMyTlRJd09UTTNNVE5tWldNMFpETmhZakUyTnpVMllUUXhZVE5rTnpBeE0yRXpaREpqT1RJeFltRmtNbVZrWldVME9UWXpOalk1WXlJc0luUmhaeUk2SWlKOQ==',1790847444),('eaoztbZmpI41TkbTOBApRkxdDTlYuZGGiaConcM6',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0','ZXlKcGRpSTZJbmhsVFROcVYxVmxlR1ZxWTJkdFVsUjZNWFV4V0VFOVBTSXNJblpoYkhWbElqb2lUbXRKYmxORldsZGlUVTkwVjNrMFlYTmtSbXc0WVd0TU9FOVdTVVZaY1U5blpEbFhhV2hOUVRJMGNsWlBjM012VVhsS2NWUmpRbkI2YTNvMmRESXpjazF3UVRCNFpVRk5PRUUwY2xjclVuVjZiek56ZWpjMldFeFNORWw2ZHpZek5tZElZbU4wU2xaMlNESnJTV2hSTnpCa0syMXdSMVUxUzJKV1VUbDJWamRaZVRnM1IzRklVVVZSWlRscmVrMHpVMWRaZUdSVE16UlJWRk5rZG1kelIyWjRhRXRwVFZKTlFrTnBibFpQUlROelpIUXdlRWxyY25sUk5sbHJUVTgyVUdWNVlrbFBNMFJGWjJrMFZFWndXbkJHWW5Oa1luTjBZbTFzYVhobmVrMHhXRTAwTHpaTldDOWFhRUV2UjNobmFXUnZLMFV3VUhWRFZsbzBWa0ZMWkZkVldYTk5kVW8wVDBkdVp6VlBjMEZVWm5Fdk9XaEJSR3BNYmpOck1XOUNVWHBHV0VKSmJ6RkRTbEU5SWl3aWJXRmpJam9pT1dJNFpEZzBZamd3WVRRek5UYzFZVGhoTnpRd01tSXhPV1ZqTVdZMlkyUXpZVE5oT1daaVptVmtPR1k0WVdFNFlUUXpZbVU1TkRFMVpEa3haV1V6TmlJc0luUmhaeUk2SWlKOQ==',1790847597),('F1uCN10f2Qwd49Zicvx80l4y7otnRgq5i7bO73sS',NULL,'127.0.0.1','curl/8.21.0','ZXlKcGRpSTZJalJJSzFaRk9UZ3lWblpaZFVJNFNtNVZURTR2YUhjOVBTSXNJblpoYkhWbElqb2laRk16V0hRckszWkhUREUxYlc1cWQxSXZlbklyYzNGemJITm5aRnBYVW5Vek1YbFdWblEwTVZoa1RFNXVPRmt3VURZMVJISldRbVJpYkdGcU9WTmtZV1paYVRjeVJHZDVSVEprYVcxRGNETjRiVGRXTURkbVoybEdZbXBqYldSeWNXOTJUVGd4UzFOR2FESTFaVVUyUVRkcGVuaHZTblpVWTBnMFRYa3plakZ6ZEdkSmFrZGxXbTA0V1hCMk1UUlFRMEoyV0RWdlVVcDFObU5pZW5sdk0yTXdiVzVLZVhveldUVTRUekpMVVhNclQzWkRNSFZFWlV0MVRVdE5UMUZyU1ZwS1ZEZENjMFJLZUZKUU1WaHdNbE15VldoWEsxSm9WR1ZsTWxJdlVYUkxaMVZMTW5ONldGY3ljVFV4ZUdJeWNXMWtNbHA2Y25Gd1dqUjVRemRVZEdSMU1WWkViVEpuTkRoTVduQkpjWFJqYWs1SWRVWXpjR2MxUVZsMFNVbEVWVU0zYW1KaWRHUjFUa2wwYXpWd1dtRTRVWGhZUjB0dmRsTnNhMlptUlhZaUxDSnRZV01pT2lJd056ZzNOekV3TlRVMU5tRTBNemcwWlRJek0yTTBPREE0WVdFek1XSmtPRGhqTURBNFlUZzJOR1kyWlRGbE16YzFaakZsWkdKbE1UWTROemxpWW1Kaklpd2lkR0ZuSWpvaUluMD0=',1790849282),('Fw460xBAFC8lMZhZqU58XUIqvQSIRmdcJdwwU10s',NULL,'127.0.0.1','curl/8.21.0','ZXlKcGRpSTZJbkJxWjBGNFRXa3JVM2RtTjBsUk5sUnphbTFEUTJjOVBTSXNJblpoYkhWbElqb2lUREZ0TjBOTmRFczRia1F3YmtaTWJIcEZkVzEzUkdodGQyODFjbnBLVEd4VE1GWkVVVll2VDNWeFZrOXlkMjh2Tkd4bFVFNXBWRXhoY1ZSNGVteFBUbVE1TURVeU5GaG5jbnB4VERaM1ozQlJhMFZ3Wm5aeWFrcG5WMDhyUTJsRVRXTXZiMHh4T1hCUlZIWlFSMGswYWpaWGNERkJWbWR1YWpGYWQzQkNRbGd5Y1N0bldtaHdLMWswWVZSSkwwbERURVYzY1RWc1dWZG5XVkpwUzJZMFR6RXJUMFZrYzJFdmVHUlhTekZIV0ZObVlVVmllVzFKZURWMEwwNVhjM2REY1UxS1JUbE9lSE0xVG1GbVYzbFJibUV6TXpCaGVuQk5VbE00TkhNeGRHSlZNa2RPYWtWNE9IWlZNMGNyZERaNWJEazFhM1pGVVZwdmJXZ3pkRkowUm1OVmRqUlVlbFZNV2pKaWRXSjRiRU5EVUZGMFdEWktjRlZGTlVsRlFqaGlOa1YxZEVwV1RHcFZTM3ByU1dWaFUyRTRaRGh2VTFWVGNXdGxWSFpRTkdzaUxDSnRZV01pT2lJMFlqVXhOVFl4WmpZeVltVmxNemhpTmpRNE4yWTFNRE0yT1RnNU1HVmhPV016TTJRNU1EQmpOREl5TldabFlUbG1ORGMyWmpVMFlqWm1OR1F6Wm1VeUlpd2lkR0ZuSWpvaUluMD0=',1790850612),('fXKYOEsiYENrK1EREy1jqgpnGW0dXt25hN9QwhWn',NULL,'127.0.0.1','curl/8.21.0','ZXlKcGRpSTZJbXR2VkZoemJYbzJiME5TZEVkelNtOUVla1pRWm5jOVBTSXNJblpoYkhWbElqb2laMGxKYVZBeVNFMVhkbEZNYjBaRGMzVmxRM1U0UnpsVWJteG5la29yTmtkdGNUQkhNMGN5TVZscmJYSjVlVk5YVGxsUFprcFhjM2x4T0dKRWRqSk9jSFkwZEdsWUwxbGlOazg1T1hKNlluaHhaMnRyZW0weU5FOXRSM2RGU0VOcU4yOW1WRk5QYjBkd1J6QnhNMlZIYWpNemRFNVFZMlp5V0hwV01scFZNSGhGZWxRNWFHaDFkMmRCT0VGT1kzTkhOazFDVVhscFNrbDVSblZHTDA5SGFrMTFZWEZyT0RsSlNXTmxjVzl5VUd0TlJtY3JSMUJ3U21SMGQzSTNiSFF4U0RWUlNWUTNObVZMT1V4WFdtZGtXbVZhVVZaa2VtcFBaMVJQVkcwNVkwWm1XRGd2ZGtSck4ya3pVV052WXpCMU1IQjZVWGQwVjBoaVVIVlZVMEo0ZUVNclJsTm9lamRCYWpFd1EwcHZObFIzYjA5MGJrWmhTMWx1WWtKT1dXNHZOV1ZPWWtsc1JGSlhRVlJTS3pKemRXZDJWbkl4VkdzNGNtRlBTa3RPYXpnaUxDSnRZV01pT2lJM1kySTRaVFV5TldRek1qVmpNRGhsTVRrek5UYzBPR1F4WkdSak9UUmhOekV5WldGak9ERXdORGRoTm1NMk0yTmhOMkUxWkRFeU1XUmhZamt4TXpNMElpd2lkR0ZuSWpvaUluMD0=',1790849219),('HvoqqK9MJF6D4BLPJ2hRxmzinUCvQYyGMPWiVVbG',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26200; en-US) PowerShell/7.6.5','ZXlKcGRpSTZJbFJwWVZKR01XWkdjMjlMY2taNE5GRkZUM05TYVdjOVBTSXNJblpoYkhWbElqb2lWRWxaWlN0d1IzSXpNMWR5YVZkeU0yaHpjREowUm5WbWMxRndSa3BrTWpoR2NVMHJXa2xFVld0aVRrTXJRbFZtV2tsRlRsQk1ha0UxZUVSdlpYcFlWVXcyYmxwaU9GaHhiekZuYWpWVk0zWXJNSGRhVUV4UFZqRlFZWE42U0ZCSWNtOXFSVXBSYlVocmExRjVZbFZTU1RVd05qZENibEp0S3psUFUyNXdhbXh3YkRGcFVFVkRiVmwyUjA1a1NpOHpNazVvUlVGMkwzTTFVSGh6TkVOa1ZTOVlhMjVLVmxCNFNVNUVkSFZ6U0hWdk16TjRTMFZ6ZVZNMlNESmFSbG94YnpOR2RVUmpjWFl4WkZaa1RUVjNNQzlyVERORGJDOUVkRzEzSzFWSlptMWtWVkZvVERSaWIweENia1pPWVZOVVF6VnlWMkpoU3pVelVFbEdaMVZVTUZwRk5YaEZUMWhVWmtJMllWZExNa2t4YkdSWVJrUjJkemRWYTFoSlIxQXhjbnBDTm5oeWJtMXZVRVU5SWl3aWJXRmpJam9pTXpKbU16SmtNRFZoTkdNNFptSXhZemMwWkRrME9EY3dOR0UwWmpjM1ptTTVaVGM0TkRkak9EWm1NRE5pTm1NNVltRTJPV1EyTXpkbE1XTTJNelV6WVNJc0luUmhaeUk2SWlKOQ==',1790846520),('IDe7cP9X5jhqXYUJ5diqt7H8v8EypLC9aGN7GTJ1',NULL,'127.0.0.1','curl/8.21.0','ZXlKcGRpSTZJbVE1V1ZCUmFXczVSSGRpVjNCWE1qTXlZMHhCVFhjOVBTSXNJblpoYkhWbElqb2liSEpqZG5KbmFGRkxZMU56U1dOcVdqaE5TVWhUVnpCUlZWSkdSeTl1VlVSalNtWnJhRTEwZEdOaVJIcFRWREZDTVRCeFkzWnpPSFJCYURSMGVrRm5ja2wzTkdkVlVVNTVjMHN6Y1hvMFJFWmlkelpGVXpGcmQxUk1ha0psVEhWc1R6RndkRlo0TjA0clZsZDNWV2t2WlZKRU0xTlNjV2sxWlRWU1dtWmtiMmxVVUVoRU1UbDRTa2hOYjBWWk0zWkhhRkpyWW5CcmNrd3lUVUp2ZUdaeWJHTXJTeTlxTTBoek9FeFBieTlIVVRWbWNtSXJXamM0U0dSVFVUTTVNamxaUWxaT05XUmpSVEV3UmpCME5WRjVUMUYyVFUxTU9YZFFTVXh5TlhGU0wwUldlREpaY1VkbWNqRnNWSFJPTjBZeGVUZEphRzFZZHpoRWFVcDRWVzlGT0djMWRXMTRjWEJDVVVzMVdrUTVOak15VTFKQllWSTFVa2xTWkVGRlluQnRlRzAxYkZaTmVtVXhaMFptVUdVM1MxcFVUemswWlRCblRUSkNjRXNyT0RrNVpsTjZWV2hCU2xRMFlXSlVZMmw0U1dKc2NqWTBPVFoxY0VrM1VIcDFlbVUwVVRadGNuSmpkVEZIVkcwMFN6QXJPV2x1U1U1UEx6WXhjbTluUzBsb2VHRkpLMFV6ZVVwcVJGUTNZV2cxYmxkSVVtVlpWMmxJTmpOdGNrcFpZM1JaZGxsbWJFeGlTblpzVTB4Tk1GUjBkVzlZT1hsWmFXdFNlQ3RWV2k5U2VIWkZNRUpaTDB4UGJXdDNibkZDTjNOSk1uUnNWbkk0VVVwelRFNUpWbFJoVXpKTlJsUlljbXhTVGxwWE1Ha3daR2xFWm04eVExZEtWVmQzWTAxME5HUk5iMlZhTUZCaWNuZFNiWHBJYTFOV1JYWkJPSE5SUkRrNVZqTkpObWszTURGelZFZE1XRmxpTkVZNFBTSXNJbTFoWXlJNkltWmhNVFkxTmpNeVpUUmtNakpoWm1WbFpUWmpabVF4TnpVMVptVXpNelE1T0RaaU5HRXhOV016WVRVNU1UWmhZbVF6TkRNME5ESTFOMkU0Wm1Oa00yUWlMQ0owWVdjaU9pSWlmUT09',1790849337),('ioKOvdgKX24vzqdYpYrdSLaw7MQgZIoyP45sT068',NULL,'127.0.0.1','curl/8.21.0','ZXlKcGRpSTZJa0p4ZUU5T1UxcFphVk5rVWsxdU5ra3ZXRVl5VkVFOVBTSXNJblpoYkhWbElqb2lkVGxhZHpoSFRTdFlURWR5VERkd1oxbEZXblpCVVdNMFNVTTRVbE14YjI1WmFUTm5TSFI2Y2pkc1kyWTRVMFZuZUhWWFZuaFpXRnBRTVZaa2JHRnViRzQyUlhFck1rRjRXRUkwZEUwM00zWlFXbHBoYjBGWFYzZEhSVXhLZURabFVtZFlZV3BhUmk5cFpsRmpZVVJVU2paVFNEZEJPRlU0T0hCalJHeHFZamdyY2pZd2QxUkJhMnRNUzNWVVYzVXpVV0pxUzB4SWFtaEtSMkkzYzJGWGVFZEJVREp4YkROd2JtTTRaR3hHWjFRdlpFSk5ZMmxSTlhWSmRUVkNSVFkwWVVweE1HSmlWMDU0S3poTmJWcGFVVFJHU3pRMlFtb3ZTVEo1U2tsQ1IyMVdZMjU0YzFod2RTODBPWFUxT0U5Rk5XTnBhRzk1WVcxalowZ3haREJzTkRWRlZTdEthalJ5ZWxCT1pHVnNhMlUzV2tKT1pEaFROR3QxY1ZWT1VWZFlUbTlPTW0xd1kzZHFkR005SWl3aWJXRmpJam9pT1daak9HUTROMkkxT0RRM1lUQTROekZqWWpnd1lUbGpaRGt4WXpSbE5EZzBPVFptWWpVek4yRmlNalJsTWpkaU1XUXhOR0UwT1RWaE5HVm1PVEEzTXlJc0luUmhaeUk2SWlKOQ==',1790849211),('iqnxfbSqMzmDE1u4VOaC2PW3oXHhbz8avVxopG10',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26200; en-US) PowerShell/7.6.5','ZXlKcGRpSTZJbHB3YW5oQlNEWlBMM1JXUldscFEzQnFlalJRYUZFOVBTSXNJblpoYkhWbElqb2lTR3A2U1d0MFRtdHlRVWxrTm5SdlJHb3lVVGhuY2s1TVNFaFRNMVF5V0ZaamRFWkhhRWszTjBOTU5VaEJiMlpVV21KUU5qVmxkR015UW5wSE5rWkphbkJhTmxwVFRYSnRja05NUTFWVE5tSlhMMWxTTTNWTVVITkJZMUZLWmxReVEwSk1UR0o1TTB0UGNEQlRZWEptZHpOSlUySm5iRU5KV0V4NVdUaGxSMnAwVWpSdlRtVnhSWFF2UVdkVFRGSlVSMFJ6S3poUlJWQXlOWGhUVFdvNFEyTlJVVkJMWTBsR1RFSlJaMUJITVU4M2RHSTRWRlZwUVZodWNuWlRXV0l5U25GVWFqWktOekJZWkVkTU4xSjFNRE54ZURsTmVIWkJhRGRvVVhWdmJHTjFVRk5CTlZCQ2RWZFhVM2R5T0hwdmQyUlFNbTlwWmtoWlJ6aGhhbWsxZVc5VU1UZ3lXbXcwV1VkMGMyZE9VVGxrZERoRWRIVjJNM1k0UjJFMFRISkxhemRvUVVScFdVeEdkRzg5SWl3aWJXRmpJam9pTnpnNU1tWXlaV0ZtTWpObE1tWXpNalUwTkdabE5UQmxNMlEwTTJJNVpEUTRaV0prWmpnMU16a3lOR0ZrWkdFeE1UUTVNVEF4T1RVd09UWTBOVGd4WkNJc0luUmhaeUk2SWlKOQ==',1790846765),('ISUzNuhQEVLPUofMkJxWu4SkAJ7TYbrzJ8FydHRL',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0','ZXlKcGRpSTZJalo2UVZoQ1EyeFBSMVIxVFdwbVZEVm1XWHBwV0djOVBTSXNJblpoYkhWbElqb2lhMEkyWTJsWWFWUlRhbFI0YlZaT1pHNDJkRVJxUjJKbEwwNVFkRzFxTHpOUGVYQXhXVTVIV2pBMlZEZGtTWEl5Y2k5Q1owMHJZV00yVlVkRmMwdzRNRlV3VW5wSlNVeFpZVXhaZW1GM1NteHViREZvTUhWQloyUjFRa00xVGs1SFNVZHVObkJtWTNOSE9FTlJNbFZWUVV4Wk1WRTRaV1pxUVhKM2VqWktkVzFUV0ZwMmVuQjRXVGhyY0hKa1FXNUJTRVV2YmxCd2VrZEVSMGQyYm5sU0x6TlljMUkyWkRKU2JrZG5TRmgxVGtaMUt6VlVNRTkzUkVkMWRGaDZZbGszYjBSek1HWnZMMlJ3TVhwWmJFVXZUbWxtV2xGQlFtSkRNbW95VFRrNFNGWmtiaTltTnpBM2VuSnVlU3RqVlVvNGRGWlVUMkZrTW1FMWRrUlhOMGN2ZDJwNlYwSmFVbTV0YkdkS1VEWlJZelpSY1hWWGREZzVNMDV5YUN0MFRVaDRUREpMTjBseE5rMVFWR005SWl3aWJXRmpJam9pWkRNMFpXVTFNakZpT1RSaU5URTNNMkUxTnpKbU4yVTNabVV4TTJVNE5tVmlOVGM0WmpsaE4yRTRaREF4WVRrNFlqTTBNREU0WXpRMk1UWXpZV0V5WXlJc0luUmhaeUk2SWlKOQ==',1790847547),('JGSF38ZQ3aIMn0HB8N0E6aaldLRTPPg482w3WcoC',NULL,'127.0.0.1','curl/8.21.0','ZXlKcGRpSTZJazEzU0dsTlIyeHBkVFpQYlZSR1MyOXRTM2RrVjFFOVBTSXNJblpoYkhWbElqb2lRVk42YzNSM1FUY3lka05NT0V4aE4xZDNhVmR3UTBObU1IbHJkMlIzUlZKS1dqZDVWVUpNTUV4WFVFbG5hSGxvVTNjM01qVjNOVzkxY2psaE5HTXZWamgxVUdSd1lWQnJOMGRsV1V4WE1VNHJkMDFVTlRkdmJ6RXdVMDFRTlZjeFptOXpNRk5SUWxWTU1sQjJSV2RpVkZCWFNVaHFUM2wySzBoWGFYRlJiMFk0TW1oVGFqZ3dkVE52YTJoek5ERTJNalZ1ZEVSaVNXRkpUR0pVUm5GUVdIZG5kazF1T0RSQmFrZDVhRXRrYURsQ1FtaHVVa0ZwUmtVdmVXZHJXVUpQWkhKYVRIcHpZV1Y0YVhjNFRGZEdTbUY0U0VkemJtdEdZVVF4U0ROUVRWWXdVbTk2TDNVeVRGZFpRVVZMY0hwV2JqTjFOblZVUlhGTGVuaFdRWEVyZDNKU2NtRm1UMWd4YVVKelFYQlNibHBVZFdOQlEwWTVTM2R3YmxKdU1qSjRjbWR2VTJGRGVUZHhUR3M5SWl3aWJXRmpJam9pWVRSbFpXUmhNbVl3WkRBMU56azJNRGMyWVdSbU5HSTVNbU5pTjJFMll6VTJaV1ZrTUdGaE5URXdPV016TWpsaE1HSmpOakEyWVdVeE1ERXlZMkV5TmlJc0luUmhaeUk2SWlKOQ==',1790847449),('mEGbw1Fcz91XcecPTI1XmvaLpIwmqPP5nDDXps1g',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26200; en-US) PowerShell/7.6.5','ZXlKcGRpSTZJazgzTW05eGVtaFZWVGxMVVU1VU5HSkVSVkE1VFhjOVBTSXNJblpoYkhWbElqb2ljWFZyWW5ReVJHNWxhakpsU3pkalZXUklSazlST0haV1pIWnBUM3BXYjBrclVEVXlSa3BzYUVOWGFWRktkMEZrU0ZodFMwRjVkbkZzWlRCWVFtTTJielJUU1hGck1UaEhjakJOT1Zsdk0ycFlPVk56UVVGWk4yMURRVk54VlM5eVQwNXJZWGhoVmsxMVNIaDVia2hUVEhsa1FqWlhhVGh6U1dNM1dXUnVTR3RyYlRseFpubElRbEJ0TDBsTWFrNVJTM3BYYzFORmEzcGpPUzkzY0ZNMlFXTlRPVEF2VDBkNllYRXpVamhVYldwQmFtVjZVVUYxUWtrcmFtZDNORXRsVW0xNWRUVlZPVlV6VFZkWE1UUlNjVU50Wm1SU1VrczVUV0p0U1hZNGFVRnNla2xsVGpaeFJrWXpTV0pXTWxWTVVtc3dWRmx3VTFkcGN6azJOblZqU1VKTVJTdEpiV3QxVldaWVRXcGhNV016UTA1eWQyTnpVbXcwU0ZGemJrcE9hMDFWUm5Kc1VTdE5RelE5SWl3aWJXRmpJam9pTVRnMVl6UmxNekEwWmpVelpqRXlNRFEyWVRZeE5qRXhOelpoTldFeVpHRm1ZelF5TWpFeE1qbGlZV0k1TVRKbE1HTXdORGd6WWpkaFpHSTBNbVF6TnlJc0luUmhaeUk2SWlKOQ==',1790846337),('nExgAPgptoqOCmRb6EdVNmSoMJ0KEtxEfAKaI61Z',NULL,'127.0.0.1','curl/8.21.0','ZXlKcGRpSTZJbEJJWkU5MFdrUnVUbVV2ZVdOSWMzQTVhRlpMVm1jOVBTSXNJblpoYkhWbElqb2lTa3BTWW1oclJtNTVNakprY25SaWFXaFVVMVZJT0dad2MyNXBiemhFVW01cmVWSnRSRWhEY0ZWVVJrZzBUMlJHVnpKRFZYaHJUakphZUVndldETkZWMlJYVmtWSE1YSlBjV3cxUkZsQlZHZHFlazRyZG05c2EycDFZWGwyUVZwNVdGQTFjbmhETlRab1VTODVNakUxVmpRclkwSXhLMFpaVXpFckwyZFZNRkpOVTJ0Q1QzSjNZbGxITVhZd1RIRnpTV1kxUld0aWNGRk5PRGhJTkdWc1FubG5ObU4wYWpjdmVraEJSVzFYYzI1aVkwMTNOa3RzTlhoWmJteEVUa3BpYzFsWWFVMVljbGxaUzBoWmF6TlFTWE5HWlVGSlVUTnRRbWRaTlVwblQwUlRNM2xvZVVKc1RHbDFla1ZQVlRCdU0wSmxWMHRvWm5wck5GUjRVMkZUYjBGcFVWcFBUMm80YlRWWk5tdFlNa3hMVjJOSGVUbE1NWGxXZW5sNFJIRTBiMk5pYWtaUmF6aHRPV05hV1VwSGJsQklXRGd2ZWtvNU1sSklZMUo0TTFsR1ZtNDBiM1ZwTmtsMFNHbEZTSFZZYVVOMmVFdFdlRXBUTm5GRlpGYzRWVlp3YkRkb05uUXZhbFE0VUUxdVJtdHZXa0YxUkhSUk5tZEpjVms0WlhrMWRXcDRNRGMwVDNNMFFuTlZPSFpLYTJOQ2JrSllheTlIWWpKcFRtRmFiM2MxV0RaSFlXVm1jMUYyZEdwS2NFRTJZMEpYUlhWcldubFFkVXRyYlhwVFozVkxiVmhhUkZNME1DOW9OV2xHVVRZNFNUZzJUREk0Y3pCamFqZDBLMFJTU2k5c2FDc3pTV1ZxWkZKMlltMVlkRWt4ZGtwM1RuWkdNRm8wU0hOWU5ETTJhMHRFWm1wR1pYWk1UbGxaUlZoU1VYaHlTbmczVEc4eVZreHhORTR4ZUd0QmNtdGlXV0ZhTTNBek4yMTBPRzhyT0hwb2FIWkhhMDgwVmt4U2FtNXBTM2RGVEdkTmRXeE9XVkJHYmpseFRGTmFOVlpKY2t4RVpURXpOV0p0YUdkcVJGVk1VRkIxVkRWa2IyMDBNV3hqVW5ReVNuRjRZeTl3VG5oaVVUVmpjV3hwYm5KM2NFNXBXbUpaYVhsNE9HSkJPRkJQWW0xM056Rk9UM0ZRU21RMkwwbzJabE12WlRCaVQyOUxVM1ZtVTBOMk5FVkVWemhXV0VwM01WQjVUMFF4V1hoMGIzRXhVRmhCYWxoM2VrODRkbFpXUW5CRVVVUlRNRE54Y0V4VVRIRXZiMnB1U0hjdldGUnJQU0lzSW0xaFl5STZJbU16T1RNNFl6VmxaalZqWkRnM05qRXpZMlV3TjJVeU9XVTNaV0prTTJJd1lqSTJaamMyWWpNMU1qZzRPRFl4TTJabE0yVTNOakZpWlROaU1qRXdaR0VpTENKMFlXY2lPaUlpZlE9PQ==',1790849291),('NXIT3FDSt1ZPZMQZAxisalwD4556aBAFZfRlKWv6',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/154.0.0.0 Safari/537.36','ZXlKcGRpSTZJbUZSTmtwQk5FeHpRMWR5ZEhacVEwcDVaVE1yWTFFOVBTSXNJblpoYkhWbElqb2lZekZXV1RFdlkxTTVaMDlTT0hWRE9DdFhjRzFvYlRoaVZtbGhNbFZhYURKV1VGbDNRVU41WW1kVFQzSnZSMnhXUzBvMFUyOVdaV05SWkdwQ1ZrWllSRXR1TTNOWk5UVlFSVzR2ZVdST05XdE9hbEY2ZWs1dWJESm9SVXBrYkU5bFRuSTNhV2xYYVU1NmFUVTBPRmxYUTBkemFWRldhekpEVTJaU1dpOTNaWE5uUm1ad2VtWmFiRmd6UXpScFEwbFpZVEZHVWsxclJsRk5kR3NyWnpWeU0zWkVXVWxCYUVwNU1qRlNObnB3UWtSd2JUVkhWalI0YlROWk1XbDRiazR6TjJaM1ptSXJWRkpTYldsNllUUlZOall3VHpsMGRFVXJTemhpYkRoVGJHSnNNVFJZUXpadE9IZFJVVGhLT0VkNU5VUkdWbFZMY0VOVFRYQmFRbU5wYkhadVRTOHlSMDlIYTNZd1ZWVm1OVU41U25nMk1XUm5lVEpaZFRKVE5YUnlOblIyVEVNNFYzQk1OM0ZMYTFKdFltTlhlbk13ZWxKT056VnZXRzVRVTJvaUxDSnRZV01pT2lJM01tWmxaVGhoWWpZeU5URm1PVGd4WkRsa1pqSTRZV0poTVdNek56RmxNRGd4TVRjMk5qZzFPV1k1WmpNd01tRmhPRGd6TW1JeU1EbGhNekkyTURKbElpd2lkR0ZuSWpvaUluMD0=',1790851915),('OoXLtWrsgID6TaEpZQuSIyZar5MofzWLwsk3nHMX',NULL,'127.0.0.1','curl/8.21.0','ZXlKcGRpSTZJbU4zYUVVMVkxVXJLM2R3TTI1bVZXVmFOVEJFUVhjOVBTSXNJblpoYkhWbElqb2lNbU12YjIxMVdGVXlUa1ZFYTI1RldXUjJZV3N3VVdWb2EyNWpjemgxUVVGNFRUSXhkMmxvUldGRFVHUkZTalJRTURRMmFtMUtNVE5sZFZSQ2RUTkRVWGd4VmxaSkwyRXpjbk5NVERCbmNYWldWRVJVTkc1TFZVRm1SWFp1TDNoYU1XczRWMlJXWjFaNVRYSlVhbTl2Vld0VmVIbDFRbTlPYUhGMVRIZFdaME5oZEU5UFVHOXNLMEp4VWxjd1UybzJjSEpZTWpaaGIyaFlVMGRQTURGQ01sRnBZa05ST1RGaFJFUTVjbXRRU1haelkxbzJTVGhLVTNBeVRHa3ZXQ3RoY0dGcGVtaFZTVE00UkRkMFdpOTZVa0puUjFkeUwzbFZVbFV3WkVNeFNFZzJVa2MwTDFSbU1ubE9iMnRHVGt0WFVpc3JlSEk0UzNoemVXTldXRTkyUzB0aGFGWjRVbFJ1ZG1KTVRGRXdUbTFYY1dNMmFqRnNWMDlJVWl0UGFFdFRURGhoWkRSU05GZHhVR1Y0UnpBdllVOVJRVXMyYW5aaFozcHRUWFpvVFhsSVlsVTJhSGg1TkhNdmEyOVpXbVZQV0hWa1MwZHpNRTlwYW5WVVVIQm9VbFZHZVU1MmRXSmtNRXR3TlhrMlNrNXVhRTFGV2xOSlUxUXZOREkyWjFWcmFYUldlU3R0VmtGUlZrSkdZVmhsYTIxcVJXRnhiME42U2xwTk9WQXhiV1l2VFdsT2N6Rm9WM0JFVG1GdVpYaExhSGhITVRFMmFrVkNWR1JSTkdNMmMydERSU3RDUmxaMmRFUk9ZMmN2VkhacmNrZEVhWGhZV1cxcllVaFBObGxQVXpWWWEzQmtSMjVDZEZaa1JXc3hjRkptVlRGRmRsTjVSRGt3VTFodlJYaEplVzF6T1ZaMGRXRkJiMEpRUjNKUlpWWmxNek5UUTFadldITlNVRk14VlZkbmJVdFJkRU5HYWpKalBTSXNJbTFoWXlJNklqSXhaRGc1WXpCaFpEVTBOalkxWWpRek1URXpaalkxWkRVNFpXRTNPRGRtTlRnMFpHUTNZamt6TURBM1pqZzJZekF4WmpVNU9EQm1ZekkwWldObE1EVWlMQ0owWVdjaU9pSWlmUT09',1790849330),('OWpYqEmjK43fqTNrCVVnbv5xdwz4UPRF3EDG9pV0',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444','ZXlKcGRpSTZJbkpQWW5SeVJXeEdVVTkxTWxreldYVlpUbGhUYjNjOVBTSXNJblpoYkhWbElqb2lhbk12YjBobVNrWnNTRUZaWWsxT1UweEdSRUo1VEVoU2JEWnpWelJwVEd0eFJGWmpOMDl2YkdoMU1IWlJVa3REU0ZSRVEzVnNOSFJYU0ZKcWVUVk5abGxQVDNCT1EwNVRWMVpEYzJGTVltRm5USEIxUlZGcWN6RnlWblk1WmlzNFdsZzNibWRqV25GUEx6TktkRlZvYUdoUFRYZHBhV3hyYVhBMFNWRnBZMnAxV0RVMmFEUndVa2syZWpOSVNrRjFTekZZVnpGMlRtUXJkRTFNVUZSMlNuTkNaRFkyY2sxcldYWXlORFYwVm1wa2JHMXhjM2xZU2t0SWNFMWtjWFZvT1dWcWJYcHplV3B1YVhaVVZIUmhVbkZETm04NU5HNURVVWN5YTB0amFXUk5hak5hUTI1dGRsbFlURnB5Y25oSVJ6UkZNVXhhZGtsa1QwcE1NbTQyTVVSVEwwUm1RamRyVG1OdVowVTBOalV5VW5Od1FtbFFhbU56YzBsclpuTmFNa2hKVWpoalluQXJRVXA1U25Gd1pXOVRWWEZ0ZWk5WlFVMHlVSGhKYlZZaUxDSnRZV01pT2lJek5HTXlOREk1WVROaE9EZGtPVGM1TVRjNFpXUTFaalpqWldOa04yRTNZV1k1TURkaU1qWTFOamxtWm1Vd09URmhOVEpsTURJeU9UQXlaR00zWmpnMElpd2lkR0ZuSWpvaUluMD0=',1790849256),('PBAFnj34hMhOa3BYboLmrTGCLZ1BenMx2jBwUwu1',NULL,'127.0.0.1','curl/8.21.0','ZXlKcGRpSTZJbEpYVTJaSWMwMXVTakJzYzBSNFNWRnRaV1l4V0hjOVBTSXNJblpoYkhWbElqb2lVbUl5VnpSeWRXNDBlRmMwYjFselNHTmFVVlU0UTBNMFlWZENXRlkzTVdSNVIwSTFha3B1T0U5aU1Fd3dlVGxQVW1kb1FTdDViaTl1ZUVsTVkxTTFUa3M0WTJ4M0wzVm1LMlp4Vld4d1NXczJaa1pNUTNkdFlrSlNaR1pyV0RGb2VsZHpNMHAxYTNST1RFSllRa2hNSzA5V1pYQktiWFZEWW5CYVEzcGtiV2x3Um5aTGJ5dFdUbkZoVDB0a1EwSTFTR0pIVERKTlEzcFVRM0pOYzNJdlREbExSVGhPUVdkU1FqWnpjRmw2YkdSbVJWazJObWRNVERkaVJuTlZPRGM0TDBJMlZIazNRMWN2TkdsaVNWUnNkakJOTjNOTlJscEJiamh5VTJwTWRubE5LMFpZU0VWa2JuSjNUamt5V0N0SVIwdEdlR1E0V0ZWUGFYWkhSMFozVERSRVNrMWlTMjluVkhFeVIzVmlOV1JuWlVKMlkwNVhiMlkyWTNaTlpqbFNVRzhyV2k5TFpFOVZjamMzUVRWME0zazJVV3BKYUcxRFowOW5aWE5KUzFsc1JXVTNaMGRQUlZSVlZGTjVlRTlCUXpGSGFtVTVWSHB4TkV4dFRXdDBlaTlLWW5sbVUyVnNlRk01TDNSRE9HcFZRMU5uU1hNMk1qQjVWMUpTV1hNNVpYQnNkV1pzY1ZKc1R6QnllbHBhVG1oMk9XWkliQ3R1YTBRM1RrZzBPRE5TUkZOeVNGTlJTRzAxVGxCa1NHUXdLMjFoWmxOaGVrdE9WbXM0VjBWb1NtdzJWazFLV0hrNWJraE1UV280YjBvME9TdHFURll6T0hOUmNEQk5WbkJ3VlZGWGJrTTRPVTR5YlZRdlVXTlpVamhGVEZKUlUydHdVR0ZTTVZjNVdWbDNOMmhqVUc1Vk9IazFhVzFHTXk5eGJ6bE5jMWx6ZUV0S01HVTJPVVF4VWpBNU9HWnZLeXRUYTJwRlBTSXNJbTFoWXlJNklqVmxOMkUzWWpobFlUSXpOMkkwWmpKa09HSTBZMkkwTUdWbE9UZ3hNRFkzTW1aaE1EbGlaakEyWkRsbU1XWXlNVGhrWkRJMFkyUTVPREpsTW1SaE1XSWlMQ0owWVdjaU9pSWlmUT09',1790849346),('qNYun7niEArX5jkkzGkEZxDjefzpscp5v8k9QFOq',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','ZXlKcGRpSTZJbEJ2VWtGR2NGVmtjWGt5VmpCR2NqUnhNa2xJVDNjOVBTSXNJblpoYkhWbElqb2lLMHB6V25aUWVtOTBkMll4U0RoelVFeENTVE5GVERjME1rNVJSWGt2YlZkS1VHSk9aMk4zU1dwMldIUkRhRFl6ZEUxWWQzZFNSVEZ3T0hoNGMzUnliek4yZHpoVWVXcGhlRXhMTkcxSFdGcDNTSEpDYkZsdFUwdDJOSFIyYTNsckwzWTNjaTl5V2xoRGFHZFpXalZHT0VGaWIxVnZZakkyWlhwdmNGUlVVVk5HZUhKaWVVVXpkMVJQWTBjdldXWnNXalpHTUVZdlkwUXdUemxPUnpnMlUybHhjMlpzUjJ4aFpWUndOM0JJVldJeE9VOVpVbk14YkVacWJXNWFOMHMwUVcxTlMwb3lNR0Z6UkRSaVpteHhSRUp4V2pWQ1RFeHhWMDV0WkhKbVVVZE9ZMlZTUW5WMEwzbFJjakJqWVhaSmRVSXhSME5VUms5c1VFNTFRVzl0VTNsVE0yVnVWbmxYU0U1VmVqbG5aMDgwVlZOcVVuWmxhbTFGTkhSeVVEbEpXVWhYWVVRNFNYQm1kSEF6U1N0VVZHaDZRbVpSUnpOd01rMUVUSHBQTmpFaUxDSnRZV01pT2lKbFlXTmtabVUzWXpKa1lURmtOV05tTXpZM05XUTBNVFl3WXpZeE5HUmhaR1poTWpnd1pUaGlZekZrTWpkaFpqRTRZVEptTWpjeU56WXpZV1pqWWprNElpd2lkR0ZuSWpvaUluMD0=',1790849794),('rlixLzyjrSoEIHPl8jtA4oDEpXxp6JLO3Te4k06g',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','ZXlKcGRpSTZJamRXZGpKdmNHUmtWbEJvUTNSdVppdFdZVTVKTkVFOVBTSXNJblpoYkhWbElqb2lOa3c0TURCUWJFWktZelZ6UTJwaFowWTNiVzFJTTJGTFFrWkRSbGhKWkhCelRXMXdlbkp6T1VOaFZYVTFjbGw0V210UWRqaDJXWFI1ZFZOMFQyMHljRmRKYVd4c2JqRkxRVUpsYXk5WGQwVmhRVzR6YzJwaVRVTXlNR29yZFcwMVRHZG1aVk5EVldwbGR6WkZlRll3UjNBeWVuaGpSa054ZGsxNldUWmxjVzFZT1ZkWlNEaEJXREkzYWsxRGNrRkRiVlEyY2xSclkydDJSRXRRVFZaRk4wZExVVFZSVlZKMWJWUkpXRXhITDBwUGMxaFJRMHhQVm0xRmVsaEVhVWhtT0ZKdGFrcDJORWR6T1V4clZrOUlVVGQwV1ZrMFNtMXBRV2sxTDFSMmJFZFpOMlZaUkhnM05qQnBXV3htWTAxelRVRk5UMEUzU0cxclNqUkJMemxMVWtGT1JUQjNSU3MwYUZoSlptVkNObkJKUm1salNEaGxaa2R4WldwMFNISk5PR2M0YmtwUE5YSkJNMDA5SWl3aWJXRmpJam9pTkRoaE9HRTVOelE0WkRZeE9HRmtNV1UzT1RoaVlXWTNOalZqTTJJeU1USXlaR1k1T1RGaVpUUmtNbUUxWWpJNE56SmxOREpqTWpka1pqZzFNV1V5TWlJc0luUmhaeUk2SWlKOQ==',1790849681),('srLNWQlGjEnT6zTDXsjeRhg74n3lGMIg9eubMu7B',16,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','ZXlKcGRpSTZJbkpHTTI0MGJ6ZFNRekpxY1dwVVZrMXJTVm8zWkZFOVBTSXNJblpoYkhWbElqb2lXV0pHVVd4NmFXczRTbGgzVGxZeFdFdFBUVEJ6WTBGMGJqZG5ZVlZsV1VKS2JVSlBPREJZU0N0RlVsbDFaR0ZKY3poNmFXNVlVSEJRTVRCeVEwRmlhVGgxVGl0cFVURXhUbW80TXpWNVpEQkdXRmgzY0d0bldHdDZMMm80ZEZSMU5tbHZOa0ZyY1Rsbk9HcEVUbTlxU2xwblEyWnJLMk5rZVRGa01ucGlkVVJRTTB4NlRYQm1NV0p5VEhkUU5TOWhZVGsyY0RoTmMzb3hPRVJGUjAxeWJFRjBaSHBQUmxWYU1VRjFiMDVHTXpKamJrMXZRbTh6V1M5elUyUkxjbEJEZW5aUVRHczJPWFphV1daWVdsWm1WbFIyTTNGWGNXOWFRVFZpZW5aWWIzcEdURUlyYURGTk4xWTVhbkpOSzFaRFNqQldXbmRPZW1SWWJsQlpTMjV1ZGxaaFZrc3hTQ3MxYkhNM00wVkRhR1phZFhacE0zUlZaMVZtY1ROeVZUbHNaRTlYY0hkNFRWVmpWbmRDWkRrMFVFdGlUV3hvU2pWclVIQmhVbmhPYVRWc1QwWXlNamd5YzFGYVVqRkZWaTlqYTJGUGRqZzFORlJYZFV0NFVHZG5kRkY1Ylc5cWIxcENXbWg2Y0Rkc1UzQTNlRFJMVDNOVVRIRktTa3BLTVZkTk9XaGlkRk5xWjFOR0sxTjVNMUZ6WlZSUFZqSk5ZbGRvYzFKUlltZG1lV2hwTjNsWmJ5dDBkRkZXZVRsVVV6WlliU3Q1YWtkSVRVZENaRmh4VEU1cmNUUnZUemd5WW1ZelZERklORmRyVFRSVk1rdDBjaTlGVHpONmVHOWxOMnBFT1ZWbFVXbDZOMnhGU1dKcmRISklXSHBuTVdGQlJrWm9NV05DU1ZwalRuWXhiRkZFTkZWeE9ESXpaMWMyTjBwcGJIa3ljV1puYTBGMlEyeHdOVzFRVm1KdWIxUnhVa053UTNGclluVm1abFFyYWpac1pWUmlNRkJWYjJab2FVTnZNVlpoYlhGSmNFaFhLM293U0ZseWFUSk9XVnBZUldsbVZFZzFVM2RVT0RGa1QwUjZaMHRXTjJSM2RuRnBkSEpWSzBob01FMUdRbEEwTmpOMVFrcHljVlJhV0ZoQmNtVlViWGczVkZNd1ZVMHJTVmxZWTBFOVBTSXNJbTFoWXlJNkltWm1OVGMzT0RaaVpUZ3hZMk14TmpSaU5XTTRNMkUxWkdSaVpEWTJaVFJtWWpNMVpHUXpaRFF6TldJNU5tRmxZekUwWkRjeE5qRXhaVE00Wm1NM1kySWlMQ0owWVdjaU9pSWlmUT09',1790847102),('tKSbD8GhKg04am3dOV5Kkvhm5J8Cr4pwepwkvxJL',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444','ZXlKcGRpSTZJamhxVUdaaU4ya3ZWVTQ0ZWprek9UVlNNR2RIZUZFOVBTSXNJblpoYkhWbElqb2lkRUpLTWtoNVdIaHFUMGRGVFd4bldrZzBSbVl5YTJaVGJXSTJVVGQ2V1U1R1N6UllRWEZ4UmtkRlNFMVlTMDF3YWtSelRHTkNWVFZFWjBONFduYzJZMmt3Y21wUk9FVmFNQzloVVRaeWMwTk9hVEZ0Tnl0V01IaFBObG94SzJsa01qTjFORzF6WW5GMmFuVnhkek01TDBJMlZYSm1aelJpUVZWdVJtdG5lVmh6TjA0eFVIaENSRnBHTkVaM1YwSkhia1JSWTJSVFNERlVaVWRDUlZSaVkwNW9Sa2xHYW14WVZIZE5jbXhhZUM5cmRFWmpVVmt4VDI5dGFqWmtiVVo0V25wTWJXUndWemhNYjJsUVIzZ3JZWE1yWmtaVU5qbHdZemxIYzNad1NuQkliM3BuU1RsU1FtMVdLM05CTDNWWVNUWjNZVzltVGxKaE5HVlpaelpVZUZSVmRpdEtkRkZqTmprNE9VUnFPWFJzTTNOck9WTjFZalV2VDNsQ2RUaFRkbk14U0hGMVpWaFRZa0U5SWl3aWJXRmpJam9pWkRkbE9HUTFNRFEzTURGaE5tVXdNVFJsWkRnNVpXVmtaVE0yT1RNM05qa3dORGRtWkROaU1XSmxOMk0yTm1NNU56VTNNbVF4WkdFMlpUSXlaRFpqT0NJc0luUmhaeUk2SWlKOQ==',1790847438),('w7k3hJV6zp4RZIl4KizGlvRCcijcPXxR4LB3THf9',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','ZXlKcGRpSTZJbU5QWVU1S1NFNHZUMUpyZERkM2RYRmhhbFZZTUdjOVBTSXNJblpoYkhWbElqb2laRlZpSzJOME9EWmFaMDU2Tm13NFEzWnhNelpTWkRRd1NYUlRVbmx2UjFkR1J6UmxNSEpTWWxwTFJFUm1jMjEzU2tKQ1ZGaFpNRmw1TkZKcFJucEtTM0ZNUzI5ME5tMVdUVTFyWTAwME9WaDBMMFZaYmpsU01FNXZSa2w2UVZrNFJXdDBaRGw0TkRkeVJWRjJjM1Z0YjA1R1NFRmlPR2h1TjFaNVMxUlBhRWswUVVGNVYzbE5WMGs0VjBwelJFNHdiRFpqY1ROWmRWQm1aVWN2Yld0WWNHeE9SV1k1TWtsTE1FOUlSVEJ5Y2t0blkwa3hVMnMxTXpsc2NqQkRTRmh2Vm1WQ1YxTTRPVEJDUkVKaGVWTllTbFJUVTBsNmNtMUNiVEpHWWpJNWEwdzJkREJPTkZwaFdVa3JTbWR5VGxwdmFIZGhUbTFUVGxJeFJtRk9UalJpVWxkaVJITnNOVVJWT1RjdmNEaEZZVzh3WVZGTVQxWlZVM1ZJZFhkVVYxcGllRmhMY0RNM1VIZEVNR3RYYkdST09YVXplVTlHTUhKbFNIQnJTRkJCYjJoRGVqRlBTRFp6VHpkM2NtdE5ZV2RsTkdGSU1XaEpZMFpUVVhOMFdHdDZMMEl3VUZKUlMzcDNTV3hHZHk5dWQxSmFaVFIyYzIxRFFrUk1XVUpIUW5sMU9HOU1Oa0lyZVV0WFNHSk1UVzF6TW1sVlZGVk9OVzFRS3psQlRrWTNVRlU1TTJSd2RrUlljall3Y0U1RVltUmliWGxSVlhSeWRUWnZZbUpSU210NGVYUXlNRVJSVlZCYVduQTJZV0p5UVRoWFIzZEhZVEpFVkRoRU4wTnpja012Ym1kQ1kyUk1OWGhTZFd0dk4wRnZZVFJOTDJwemVXdEhWRzlSY3psS1Iwa2lMQ0p0WVdNaU9pSTJZMlZoTnpFeU1EUXdNek5tWmpjd01tWmxPVEJqTXpWbU9URTNZMlkyTjJFM01HUTFOakk1TXpnelkyUmhNelE1WVRCaU1UQTVabVl6TURneU1qRmxJaXdpZEdGbklqb2lJbjA9',1790852576),('wpOp7oRlUgdkxZmdcA0TjW3O0PMHoE0HQc3cWibP',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0','ZXlKcGRpSTZJa2xEZVRkSWFHMUlSazh3UTBkaVkyNDNaMDlqT1ZFOVBTSXNJblpoYkhWbElqb2llSFJNVlRKNmNFWkJUV3haUm5GRE5qazBSR0ZSVW5vd2VXcGlaVllyWmtoV1lsRXhWekZCYVdsUmNtRnlRMGhLZG5aNmNIcE1LemM0TVhBdmEwOVJaMFphTVUxd1kwWXJkbE40WkhwNE1uRnlUMFF6Tld3d056bFVSV1l6Wm01Q1RtWnZkM0IzT0haaGQyWm9lRUV4Wmk5YVptNW9lR1UwY0VGc1FqQjVTMHN3Y0hocFQzTlJSMkpJV2pGbVFrcExOV05vYWs1VFRXZFNkRkp3VmxCUE16WXhhamxYYldOcFMwcHVSamxLTDNScmNGQTRRVXQ1UjJORlMyTTVkUzl2YWtadFVWbHBaVWg1YUVNck5FSkhSMjlZWm5CR09XTlpiVFUwTTBvNE1YUldOazkzZUVGU2JrWjJNV0ZHTlUxNmMzY3ZReTh5V0ZoV1NGQkJTRzFXVkdvMlUyczJUbGgyVTFWakswNVJXbkZhUjJVNFdqSlRlUzluYmtSRFJrUnJTVUkyVUd0dlVVdzFVVkJFZERkT1NpczVTVVpaY1ZwNVRYcDJVRzR2WVdkUFIzRkZWa3h6ZDJ4Q1dGcE1PVUpVYXl0dFFYaHJaSEo1ZDFrM2R6VllhREZIZGtocGNrNHdUblJuUFNJc0ltMWhZeUk2SWpJNE5qVXpNV1prTm1WaU5UZ3hNamRpTkdFelpqUmpPRFpsWm1ZeE1tRmpOamMxTUdGa1pqZ3paREk1WXpVNFpqQTVabVpqT1RKalpEVTVNemxsT1dNaUxDSjBZV2NpT2lJaWZRPT0=',1790849576);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$12$LMCOnRHDP2XnzM3zbjhV1eEEwUWhE.rls8vAdO8/OgL0pVX2.JCEC','Quản trị hệ thống','admin@hotel.com',NULL,NULL,'0900000001','admin',1,NULL,NULL,NULL,'2026-06-05 11:27:36','2026-06-05 11:27:36'),(2,'reception','$2y$12$9TsfqH6Epi7CqbNijSCU0es7fces81.porQGACedFagAk/xQI91OW','Lễ tân khách sạn','reception@hotel.com',NULL,NULL,'0900000002','receptionist',1,NULL,NULL,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(3,'customer','$2y$12$E4Qte3blhmW9SDvzbC9R1uI0q3RrR8oONIbHSOnnOZP/KhFuLjJ.2','Nguyễn Văn A','customer@gmail.com',NULL,NULL,'0900000003','customer',1,NULL,NULL,NULL,'2026-06-05 11:27:37','2026-06-05 11:27:37'),(4,'26a4041708@hvnh.edu.vn','$2y$12$nzG4ZyqDnlrRyZxWpcG7EeHEwNmLOTwerUnDZYGp7GcMO92E3sePC','Nguyen Thi Loan','26a4041708@hvnh.edu.vn',NULL,NULL,'0357745893','receptionist',1,NULL,NULL,NULL,'2026-06-05 13:20:37','2026-06-06 02:44:06'),(5,'loann1430@gmail.com','$2y$12$724mjXJGWonZ2nSBNTfpe.HVXK0IknFN6172hxT9KMGgyb1LI6YS.','Loan Nguyễn Thị','loann1430@gmail.com',NULL,NULL,'0357745624','customer',1,NULL,NULL,'oJKVtSm2fMWzP7eeo5gZXBuykKnOleXWfD60jqnjdxdnH7XJB93j1zeWKkZu','2026-06-06 02:29:41','2026-06-06 02:30:18'),(7,'thanhhoai11112005@gmail.com','$2y$12$2pHzYkSB5el3WBbnFCKaruUemZd9ZL8G2Nab7k.K9snEFEJfs8vXW','hoaii','thanhhoai11112005@gmail.com',NULL,NULL,'0987654321','customer',1,NULL,NULL,NULL,'2026-06-06 11:40:55',NULL),(15,'huaquanghan114@gmail.com','$2y$12$QTAfQcT3ijpahnysuvvVkeURK5B7.8lgejXAbx8U5EMG7XeLzM3d6','Quang Văn','huaquanghan114@gmail.com',NULL,NULL,'0945843588','customer',1,NULL,NULL,'FodTTIvLl1f6ZGJsDPPBqcUb6NaK9CYJ7JojEFlElMadyhZqLLD30yHvcUOh','2026-09-29 12:27:17',NULL),(16,'admin_ui_test','$2y$12$7ffuXytawP.l.I3VVnR2WeozAo5VM.SksOSWGN8WPh5VyXTOlh4Fy','Admin UI Test','admin_ui_test@localhost.test',NULL,NULL,NULL,'admin',1,NULL,NULL,NULL,'2026-09-29 12:45:18',NULL),(17,'nganphuongnguyen1412@gmail.com','$2y$12$7rVVrqad2dFJWz42rwYLSOCD7kuq6Xwzj4IPchhXU7l/wgAzR9Uom','Ngân Nguyễn','nganphuongnguyen1412@gmail.com',NULL,NULL,'0983956612','customer',1,NULL,NULL,NULL,'2026-10-01 07:26:54',NULL);
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

-- Dump completed on 2026-10-01 18:25:30
