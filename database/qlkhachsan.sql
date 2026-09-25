-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 06, 2026 lúc 11:06 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `qlkhachsan`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `amenities`
--

CREATE TABLE `amenities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `amenity_name` varchar(150) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `amenities`
--

INSERT INTO `amenities` (`id`, `amenity_name`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'WiFi miễn phí', 'fa-wifi', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(2, 'TV màn hình phẳng', 'fa-tv', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(3, 'Điều hòa', 'fa-snowflake', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(4, 'Máy sấy tóc', 'fa-wind', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(5, 'Dịch vụ phòng', 'fa-concierge-bell', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(6, 'Tủ lạnh', 'fa-cube', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(7, 'Bồn tắm', 'fa-bath', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(8, 'Máy chiếu', 'fa-film', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(9, 'Bữa sáng miễn phí', 'fa-coffee', '2026-06-05 11:27:37', '2026-06-05 11:27:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bookings`
--

CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
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
  `deposit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('vietqr','momo','zalopay','vnpay','cash') DEFAULT NULL,
  `payment_status` enum('pending','paid','refunded','failed') NOT NULL DEFAULT 'pending',
  `status` enum('pending','confirmed','checked_in','soon_to_checkout','completed','cancelled') DEFAULT 'pending',
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `refund_status` enum('none','eligible','processing','refunded') NOT NULL DEFAULT 'none',
  `refund_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `check_in`, `check_out`, `actual_check_in`, `actual_check_out`, `adult_count`, `child_count`, `total_price`, `deposit_amount`, `payment_method`, `payment_status`, `status`, `cancelled_at`, `cancellation_reason`, `refund_status`, `refund_amount`, `created_at`, `updated_at`) VALUES
(40, 7, 'hoaii', 'thanhhoai11112005@gmail.com', '0987654321', '2026-06-07', '2026-06-08', '2026-06-06 19:44:29', '2026-06-06 19:47:27', 1, 0, 20000.00, 0.00, 'cash', 'paid', 'completed', NULL, NULL, 'none', 0.00, '2026-06-06 12:41:28', '2026-06-06 12:47:27'),
(41, 4, 'Hoai', 'thanhhoai11112005@gmail.com', '0987777777', '2026-06-06', '2026-06-07', '2026-06-06 19:52:37', '2026-06-06 19:52:53', 1, 0, 2000000.00, 0.00, 'cash', 'paid', 'completed', NULL, NULL, 'none', 0.00, '2026-06-06 12:52:37', '2026-06-06 12:52:53'),
(42, 4, 'Nguyen Thi Loan', '26a4041708@hvnh.edu.vn', '0357745893', '2026-06-07', '2026-06-08', '2026-06-06 20:11:47', NULL, 1, 0, 20000.00, 0.00, 'vietqr', 'paid', 'checked_in', NULL, NULL, 'none', 0.00, '2026-06-06 13:08:02', '2026-06-06 13:11:47'),
(43, 7, 'hoaii', 'thanhhoai11112005@gmail.com', '0987654321', '2026-06-07', '2026-06-08', NULL, NULL, 1, 0, 20000.00, 0.00, NULL, 'pending', 'pending', NULL, NULL, 'none', 0.00, '2026-06-06 13:16:47', '2026-06-06 13:16:47'),
(44, 7, 'hoaii', 'thanhhoai11112005@gmail.com', '0987654321', '2026-06-07', '2026-06-08', '2026-06-06 20:26:00', NULL, 1, 0, 20000.00, 0.00, 'vietqr', 'paid', 'checked_in', NULL, NULL, 'none', 0.00, '2026-06-06 13:18:48', '2026-06-06 13:26:01'),
(45, 4, 'a', 'thanhhoai11112005@gmail.com', '0987654321', '2026-06-06', '2026-06-07', '2026-06-06 20:24:27', '2026-06-06 20:25:21', 1, 0, 20000.00, 0.00, 'cash', 'paid', 'completed', NULL, NULL, 'none', 0.00, '2026-06-06 13:24:27', '2026-06-06 13:25:21'),
(46, 4, 'a', 'thanhhoai11112005@gmail.com', '0987654321', '2026-06-06', '2026-06-07', '2026-06-06 20:24:34', NULL, 1, 0, 20000.00, 0.00, NULL, 'pending', 'checked_in', NULL, NULL, 'none', 0.00, '2026-06-06 13:24:34', '2026-06-06 13:24:34'),
(47, 4, 'a', 'thanhhoai11112005@gmail.com', '0987654321', '2026-06-06', '2026-06-07', '2026-06-06 20:24:35', NULL, 1, 0, 20000.00, 0.00, NULL, 'pending', 'checked_in', NULL, NULL, 'none', 0.00, '2026-06-06 13:24:35', '2026-06-06 13:24:35'),
(48, 4, 'a', 'thanhhoai11112005@gmail.com', '0987654321', '2026-06-06', '2026-06-07', '2026-06-06 20:24:35', NULL, 1, 0, 20000.00, 0.00, NULL, 'pending', 'checked_in', NULL, NULL, 'none', 0.00, '2026-06-06 13:24:35', '2026-06-06 13:24:35'),
(49, 7, 'hoaii', 'thanhhoai11112005@gmail.com', '0987654321', '2026-06-07', '2026-06-08', '2026-06-06 20:30:19', '2026-06-06 20:30:40', 1, 0, 20000.00, 0.00, 'cash', 'paid', 'completed', NULL, NULL, 'none', 0.00, '2026-06-06 13:29:09', '2026-06-06 13:30:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_rooms`
--

CREATE TABLE `booking_rooms` (
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `room_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `booking_rooms`
--

INSERT INTO `booking_rooms` (`booking_id`, `room_id`) VALUES
(40, 12),
(41, 14),
(42, 1),
(43, 6),
(44, 11),
(45, 12),
(46, 12),
(47, 12),
(48, 12),
(49, 7);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `holidays`
--

CREATE TABLE `holidays` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `recurring` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `holidays`
--

INSERT INTO `holidays` (`id`, `name`, `date`, `recurring`, `created_at`, `updated_at`) VALUES
(1, 'Tết Dương lịch', '2026-01-01', 1, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(2, 'Tết Nguyên Đán (30)', '2026-02-16', 0, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(3, 'Tết Nguyên Đán (Mùng 1)', '2026-02-17', 0, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(4, 'Tết Nguyên Đán (Mùng 2)', '2026-02-18', 0, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(5, 'Tết Nguyên Đán (Mùng 3)', '2026-02-19', 0, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(6, 'Tết Nguyên Đán (Mùng 4)', '2026-02-20', 0, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(7, 'Tết Nguyên Đán (Mùng 5)', '2026-02-21', 0, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(8, 'Giỗ Tổ Hùng Vương', '2026-04-06', 0, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(9, 'Ngày Giải phóng 30/4', '2026-04-30', 1, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(10, 'Ngày Quốc tế Lao động', '2026-05-01', 1, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(11, 'Ngày Quốc khánh 2/9', '2026-09-02', 1, '2026-06-05 11:27:37', '2026-06-05 11:27:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000001_create_cache_table', 1),
(2, '0001_01_01_000002_create_jobs_table', 1),
(3, '2026_01_01_000001_create_users_table', 1),
(4, '2026_01_01_000002_create_password_resets_table', 1),
(5, '2026_01_01_000003_create_room_types_table', 1),
(6, '2026_01_01_000004_create_rooms_table', 1),
(7, '2026_01_01_000005_create_amenities_tables', 1),
(8, '2026_01_01_000006_create_holidays_table', 1),
(9, '2026_01_01_000007_create_bookings_table', 1),
(10, '2026_01_01_000008_create_remaining_tables', 1),
(11, '2026_06_06_102740_add_soon_to_checkout_to_bookings_status', 2),
(12, '2026_06_06_112844_add_deposit_amount_to_bookings', 2),
(13, '2026_06_06_113630_add_cash_to_payment_method_bookings', 3),
(14, '2026_06_06_133315_add_booked_to_rooms_status', 4);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_resets`
--

CREATE TABLE `password_resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_logs`
--

CREATE TABLE `payment_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `gateway` enum('vietqr','momo','zalopay','vnpay') NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `reference_code` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('pending','success','failed') NOT NULL,
  `raw_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_response`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `payment_logs`
--

INSERT INTO `payment_logs` (`id`, `booking_id`, `gateway`, `transaction_id`, `reference_code`, `amount`, `status`, `raw_response`, `created_at`, `updated_at`) VALUES
(1, 40, 'vietqr', '62181590', 'KS40XLDQ', 10000.00, 'success', '{\"id\":\"62181590\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 02:42:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063345834607099904 Scan QR KS40XLDQ\",\"reference_number\":\"FT26159447861207\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}', '2026-06-06 12:42:14', '2026-06-06 12:42:41'),
(2, 40, 'vietqr', '62181590', NULL, 20000.00, 'success', '{\"id\":\"62181590\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 02:42:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063345834607099904 Scan QR KS40XLDQ\",\"reference_number\":\"FT26159447861207\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}', '2026-06-06 12:42:41', '2026-06-06 12:42:41'),
(3, 42, 'vietqr', '62181963', 'KS42MAM9', 10000.00, 'success', '{\"id\":\"62181963\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 03:10:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063352889835651072 Scan QR KS42MAM9\",\"reference_number\":\"FT26159414090469\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}', '2026-06-06 13:10:04', '2026-06-06 13:10:45'),
(4, 42, 'vietqr', '62181963', NULL, 20000.00, 'success', '{\"id\":\"62181963\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 03:10:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063352889835651072 Scan QR KS42MAM9\",\"reference_number\":\"FT26159414090469\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}', '2026-06-06 13:10:46', '2026-06-06 13:10:46'),
(5, 44, 'vietqr', '62182081', 'KS44ZVSA', 10000.00, 'success', '{\"id\":\"62182081\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 03:19:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063355067916877824 Scan QR KS44ZVSA\",\"reference_number\":\"FT26159233901559\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}', '2026-06-06 13:19:01', '2026-06-06 13:19:29'),
(6, 44, 'vietqr', '62182081', NULL, 20000.00, 'success', '{\"id\":\"62182081\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 03:19:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063355067916877824 Scan QR KS44ZVSA\",\"reference_number\":\"FT26159233901559\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}', '2026-06-06 13:19:29', '2026-06-06 13:19:29'),
(7, 49, 'vietqr', '62182180', 'KS49KHXD', 10000.00, 'success', '{\"id\":\"62182180\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 03:29:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063357705363513344 Scan QR KS49KHXD\",\"reference_number\":\"FT26159095787473\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}', '2026-06-06 13:29:27', '2026-06-06 13:29:56'),
(8, 49, 'vietqr', '62182180', NULL, 20000.00, 'success', '{\"id\":\"62182180\",\"bank_brand_name\":\"MBBank\",\"account_number\":\"0357745893\",\"transaction_date\":\"2026-06-07 03:29:00\",\"amount_out\":\"0.00\",\"amount_in\":\"10000.00\",\"accumulated\":\"0.00\",\"transaction_content\":\"SHOPEEPAY CHUYEN TIEN 2063357705363513344 Scan QR KS49KHXD\",\"reference_number\":\"FT26159095787473\",\"code\":null,\"sub_account\":null,\"bank_account_id\":\"63665\"}', '2026-06-06 13:29:56', '2026-06-06 13:29:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `price_policies`
--

CREATE TABLE `price_policies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `policy_name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `multiplier` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `price_policies`
--

INSERT INTO `price_policies` (`id`, `policy_name`, `start_date`, `end_date`, `multiplier`, `created_at`, `updated_at`) VALUES
(1, 'Cuối tuần', '2026-01-01', '2026-12-31', 1.20, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(2, 'Dịp lễ Tết', '2026-02-16', '2026-02-21', 1.50, '2026-06-05 11:27:37', '2026-06-05 11:27:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `room_type_id` bigint(20) UNSIGNED NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `room_type_id`, `rating`, `comment`, `created_at`, `updated_at`) VALUES
(1, 3, 2, 5, 'Phòng sạch sẽ, nhân viên thân thiện', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(2, 3, 1, 4, 'Giá hợp lý, đầy đủ tiện nghi', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(3, 4, 5, 5, 'tuyệt cú mèo', '2026-06-06 12:53:42', NULL),
(4, 7, 1, 5, 'Quá tuyệt', '2026-06-06 13:33:20', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `room_type_id` bigint(20) UNSIGNED NOT NULL,
  `floor` int(11) NOT NULL,
  `status` enum('available','soon_to_checkin','occupied','soon_to_checkout','cleaning','maintenance','booked','overdue') DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type_id`, `floor`, `status`, `created_at`, `updated_at`) VALUES
(1, '101', 1, 1, 'cleaning', '2026-06-05 11:27:37', '2026-06-06 03:44:53'),
(2, '102', 1, 1, 'available', '2026-06-05 11:27:37', '2026-06-06 05:59:52'),
(3, '103', 1, 1, 'available', '2026-06-05 11:27:37', '2026-06-06 07:36:05'),
(4, '104', 5, 1, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(5, '105', 5, 1, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(6, '201', 1, 2, 'available', '2026-06-05 11:27:37', '2026-06-06 03:37:36'),
(7, '202', 1, 2, 'cleaning', '2026-06-05 11:27:37', '2026-06-06 07:36:39'),
(8, '203', 4, 2, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(9, '204', 4, 2, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(10, '205', 5, 2, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(11, '301', 1, 3, 'cleaning', '2026-06-05 11:27:37', '2026-06-06 09:36:10'),
(12, '302', 1, 3, 'cleaning', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(13, '303', 4, 3, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(14, '304', 5, 3, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(15, '305', 3, 3, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(16, '401', 2, 4, 'available', '2026-06-05 11:27:37', '2026-06-05 16:20:26'),
(17, '402', 2, 4, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(18, '403', 4, 4, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(19, '404', 3, 4, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(20, '405', 3, 4, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(21, '501', 2, 5, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(22, '502', 2, 5, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(23, '503', 4, 5, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(24, '504', 3, 5, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(25, '505', 3, 5, 'available', '2026-06-05 11:27:37', '2026-06-05 11:27:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_types`
--

CREATE TABLE `room_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `max_adults` int(11) NOT NULL,
  `max_children` int(11) NOT NULL DEFAULT 0,
  `max_guests` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_types`
--

INSERT INTO `room_types` (`id`, `type_name`, `price`, `max_adults`, `max_children`, `max_guests`, `description`, `image`, `created_at`, `updated_at`) VALUES
(1, 'Phòng Đơn Tiêu Chuẩn', 20000.00, 1, 0, 1, 'Phòng dành cho 1 khách', NULL, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(2, 'Phòng Đôi Tiêu Chuẩn', 650000.00, 2, 1, 3, 'Phòng dành cho 2 người lớn và 1 trẻ em', NULL, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(3, 'Phòng Triple', 900000.00, 3, 1, 4, 'Phòng dành cho nhóm khách', NULL, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(4, 'Phòng Gia Đình', 1200000.00, 4, 2, 6, 'Phòng dành cho gia đình', NULL, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(5, 'Phòng VIP', 2000000.00, 2, 2, 4, 'Phòng cao cấp với nhiều tiện nghi', NULL, '2026-06-05 11:27:37', '2026-06-05 11:27:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_type_amenities`
--

CREATE TABLE `room_type_amenities` (
  `room_type_id` bigint(20) UNSIGNED NOT NULL,
  `amenity_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_type_amenities`
--

INSERT INTO `room_type_amenities` (`room_type_id`, `amenity_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(3, 5),
(3, 6),
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(4, 5),
(4, 6),
(4, 7),
(5, 1),
(5, 2),
(5, 3),
(5, 4),
(5, 5),
(5, 6),
(5, 7),
(5, 8),
(5, 9);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `role` enum('customer','receptionist','admin') NOT NULL DEFAULT 'customer',
  `verified` tinyint(4) NOT NULL DEFAULT 0,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `phone`, `role`, `verified`, `otp_code`, `otp_expires_at`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$12$LMCOnRHDP2XnzM3zbjhV1eEEwUWhE.rls8vAdO8/OgL0pVX2.JCEC', 'Quản trị hệ thống', 'admin@hotel.com', '0900000001', 'admin', 1, NULL, NULL, NULL, '2026-06-05 11:27:36', '2026-06-05 11:27:36'),
(2, 'reception', '$2y$12$C.1/6RkeDdj8XAASDda6TO2yNeDL7DXKbexuLs.IlkbaoyV0o7Bsa', 'Lễ tân khách sạn', 'reception@hotel.com', '0900000002', 'receptionist', 1, NULL, NULL, NULL, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(3, 'customer', '$2y$12$E4Qte3blhmW9SDvzbC9R1uI0q3RrR8oONIbHSOnnOZP/KhFuLjJ.2', 'Nguyễn Văn A', 'customer@gmail.com', '0900000003', 'customer', 1, NULL, NULL, NULL, '2026-06-05 11:27:37', '2026-06-05 11:27:37'),
(4, '26a4041708@hvnh.edu.vn', '$2y$12$nzG4ZyqDnlrRyZxWpcG7EeHEwNmLOTwerUnDZYGp7GcMO92E3sePC', 'Nguyen Thi Loan', '26a4041708@hvnh.edu.vn', '0357745893', 'receptionist', 1, NULL, NULL, NULL, '2026-06-05 13:20:37', '2026-06-06 02:44:06'),
(5, 'loann1430@gmail.com', '$2y$12$724mjXJGWonZ2nSBNTfpe.HVXK0IknFN6172hxT9KMGgyb1LI6YS.', 'Loan Nguyễn Thị', 'loann1430@gmail.com', '0357745624', 'customer', 1, NULL, NULL, NULL, '2026-06-06 02:29:41', '2026-06-06 02:30:18'),
(7, 'thanhhoai11112005@gmail.com', '$2y$12$2pHzYkSB5el3WBbnFCKaruUemZd9ZL8G2Nab7k.K9snEFEJfs8vXW', 'hoaii', 'thanhhoai11112005@gmail.com', '0987654321', 'customer', 1, NULL, NULL, NULL, '2026-06-06 11:40:55', NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `amenities`
--
ALTER TABLE `amenities`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bookings_user_id_foreign` (`user_id`);

--
-- Chỉ mục cho bảng `booking_rooms`
--
ALTER TABLE `booking_rooms`
  ADD PRIMARY KEY (`booking_id`,`room_id`),
  ADD KEY `booking_rooms_room_id_foreign` (`room_id`);

--
-- Chỉ mục cho bảng `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Chỉ mục cho bảng `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Chỉ mục cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Chỉ mục cho bảng `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Chỉ mục cho bảng `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `password_resets_email_index` (`email`),
  ADD KEY `password_resets_token_index` (`token`);

--
-- Chỉ mục cho bảng `payment_logs`
--
ALTER TABLE `payment_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_logs_booking_id_foreign` (`booking_id`),
  ADD KEY `payment_logs_reference_code_index` (`reference_code`),
  ADD KEY `payment_logs_transaction_id_index` (`transaction_id`);

--
-- Chỉ mục cho bảng `price_policies`
--
ALTER TABLE `price_policies`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviews_user_id_foreign` (`user_id`),
  ADD KEY `reviews_room_type_id_foreign` (`room_type_id`);

--
-- Chỉ mục cho bảng `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rooms_room_number_unique` (`room_number`),
  ADD KEY `rooms_room_type_id_foreign` (`room_type_id`);

--
-- Chỉ mục cho bảng `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `room_type_amenities`
--
ALTER TABLE `room_type_amenities`
  ADD PRIMARY KEY (`room_type_id`,`amenity_id`),
  ADD KEY `room_type_amenities_amenity_id_foreign` (`amenity_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `amenities`
--
ALTER TABLE `amenities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_logs`
--
ALTER TABLE `payment_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `price_policies`
--
ALTER TABLE `price_policies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT cho bảng `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `booking_rooms`
--
ALTER TABLE `booking_rooms`
  ADD CONSTRAINT `booking_rooms_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `booking_rooms_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `payment_logs`
--
ALTER TABLE `payment_logs`
  ADD CONSTRAINT `payment_logs_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_room_type_id_foreign` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_room_type_id_foreign` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `room_type_amenities`
--
ALTER TABLE `room_type_amenities`
  ADD CONSTRAINT `room_type_amenities_amenity_id_foreign` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `room_type_amenities_room_type_id_foreign` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
