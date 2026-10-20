-- ============================================================
--  RYDO — Online Cab, Bike Taxi & Parcel Delivery Platform
--  Course: Mini-Project-I (Course Code: UCBIL0371)
--  Department of Computer Science and Business Systems, KITCoEK
-- ============================================================

CREATE DATABASE IF NOT EXISTS `rydo_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `rydo_db`;

-- -------------------------------------------------------
-- Table: admins
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(60)  NOT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin account is NOT seeded here. The stored hash in older versions of this
-- file did not match 'Admin@1234'. Create the admin with setup_admin.php
-- (or create_admin.sql below after generating a hash with PHP's password_hash).

-- -------------------------------------------------------
-- Table: users (customers who log in / sign up on the rider app)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name`  VARCHAR(100) NOT NULL,
    `email`      VARCHAR(150) NOT NULL,
    `phone`      VARCHAR(20)  NULL,
    `password`   VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: captains (Drivers / Micro-mobility partners)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `captains` (
    `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(100)  NOT NULL,
    `phone`          VARCHAR(15)   NOT NULL UNIQUE,
    `vehicle_type`   ENUM('Bike','Auto','Cab') NOT NULL DEFAULT 'Bike',
    `vehicle_number` VARCHAR(20)   NOT NULL,
    `license_number` VARCHAR(30)   NOT NULL,
    `kyc_status`     ENUM('Approved','Pending','Rejected') NOT NULL DEFAULT 'Pending',
    `is_online`      TINYINT(1)    NOT NULL DEFAULT 1,
    `rating`         DECIMAL(2,1)  NOT NULL DEFAULT 4.8,
    `total_earnings` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed verified captains for testing & demonstration
INSERT INTO `captains` (`id`, `name`, `phone`, `vehicle_type`, `vehicle_number`, `license_number`, `kyc_status`, `is_online`, `rating`, `total_earnings`) VALUES
(1, 'Vikram Patil',   '9822114455', 'Bike', 'MH-09-EV-4210', 'MH092021004123', 'Approved', 1, 4.9, 1420.00),
(2, 'Sanjay Jadhav',  '9765332211', 'Auto', 'MH-09-TC-8899', 'MH092019008942', 'Approved', 1, 4.8, 2350.00),
(3, 'Mahesh Deshmukh','9921445566', 'Cab',  'MH-09-AZ-1122', 'MH092018001155', 'Approved', 1, 4.7, 4580.00),
(4, 'Amol Kulkarni',  '9890123456', 'Bike', 'MH-09-BK-7700', 'MH092023009911', 'Pending',  0, 5.0, 0.00)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- -------------------------------------------------------
-- Table: bookings (Rides & RYDO Parcel Delivery)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
    `id`                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `booking_id`        VARCHAR(20)      NOT NULL,
    `service_type`      ENUM('ride','parcel') NOT NULL DEFAULT 'ride',
    `passenger_name`    VARCHAR(100)     NOT NULL,
    `phone`             VARCHAR(15)      NOT NULL,
    `pickup`            VARCHAR(150)     NOT NULL,
    `destination`       VARCHAR(150)     NOT NULL,
    `vehicle_type`      ENUM('Bike','Auto','Cab','Parcel') NOT NULL DEFAULT 'Bike',
    `distance_km`       DECIMAL(6,2)     NOT NULL,
    `fare_estimate`     DECIMAL(8,2)     NOT NULL,
    `commission_amount` DECIMAL(8,2)     NOT NULL DEFAULT 0.00,  -- 20% RYDO platform commission
    `captain_earning`   DECIMAL(8,2)     NOT NULL DEFAULT 0.00,  -- 80% to Captain
    `payment_method`    ENUM('Cash','UPI','Card','Wallet') NOT NULL DEFAULT 'UPI',
    `otp_pin`           VARCHAR(6)       NOT NULL,              -- 4-digit ride start OTP
    `status`            ENUM('Pending','Accepted','In_Progress','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    `captain_id`        INT UNSIGNED     NULL,
    -- Parcel specific fields (RYDO Parcel)
    `receiver_name`     VARCHAR(100)     NULL,
    `receiver_phone`    VARCHAR(15)      NULL,
    `receiver_address`  VARCHAR(200)     NULL,
    `parcel_weight`     DECIMAL(4,2)     NULL DEFAULT 1.00,     -- in KG (max 5kg as per SRS)
    -- Ratings & feedback
    `rating`            TINYINT UNSIGNED NULL,
    `feedback`          TEXT             NULL,
    `booked_at`         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_booking_id` (`booking_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_captain` (`captain_id`),
    INDEX `idx_service` (`service_type`),
    CONSTRAINT `fk_booking_captain` FOREIGN KEY (`captain_id`) REFERENCES `captains` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample bookings (Bike Taxi, Cab & RYDO Parcel)
INSERT INTO `bookings`
  (`booking_id`,`service_type`,`passenger_name`,`phone`,`pickup`,`destination`,`vehicle_type`,`distance_km`,`fare_estimate`,`commission_amount`,`captain_earning`,`payment_method`,`otp_pin`,`status`,`captain_id`,`receiver_name`,`receiver_phone`,`receiver_address`,`parcel_weight`,`rating`,`feedback`)
VALUES
  ('RYD-202610-001', 'ride',   'Aryan Chougule', '9822001122', 'KIT College of Engg', 'Central Bus Stand', 'Bike',   8.5,  88.00,  17.60,  70.40, 'UPI',  '4829', 'Completed',   1, NULL, NULL, NULL, NULL, 5, 'Quick pickup and very safe driving!'),
  ('RYD-202610-002', 'ride',   'Sainath Chavan', '9822334455', 'Railway Station',    'Rankala Lake',       'Auto',   5.2,  92.40,  18.48,  73.92, 'Cash', '6194', 'Completed',   2, NULL, NULL, NULL, NULL, 5, 'Smooth ride and on time.'),
  ('RYD-202610-003', 'parcel', 'Sumeet Keroor',  '9988776655', 'DYP City Mall',      'Shivaji University', 'Parcel', 4.8,  73.00,  14.60,  58.40, 'UPI',  '3051', 'In_Progress', 1, 'Prof. Sheetal Gaikwad', '9890887766', 'Dept of CSBS, KITCoEK', 2.5, NULL, NULL),
  ('RYD-202610-004', 'ride',   'Varad Akolkar',  '9766554433', 'Mahalaxmi Temple',   'CPR Hospital',       'Cab',    3.8, 110.80,  22.16,  88.64, 'Card', '7721', 'Accepted',    3, NULL, NULL, NULL, NULL, NULL, NULL),
  ('RYD-202610-005', 'parcel', 'Atif Gulbarga',  '9123456789', 'Central Bus Stand',  'Shiroli MIDC',       'Parcel', 7.5, 100.00,  20.00,  80.00, 'UPI',  '1834', 'Pending',     NULL, 'Industrial Tools Agency', '9422003311', 'Plot No 42, Shiroli MIDC', 3.2, NULL, NULL)
ON DUPLICATE KEY UPDATE `booking_id`=`booking_id`;
