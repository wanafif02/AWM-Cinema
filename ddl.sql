-- MySQL 8 / MariaDB 10.4+ (XAMPP). AWM Cinema: Import ONCE in phpMyAdmin.
-- Creates a new database; refuses to overwrite existing tables/data.
CREATE DATABASE IF NOT EXISTS awm_cinema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE awm_cinema;
SET time_zone='+00:00';
CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(200) NOT NULL, email VARCHAR(254) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','organiser','staff','customer') NOT NULL DEFAULT 'customer',
 brand ENUM('AWM') NULL,
 active TINYINT NOT NULL DEFAULT 1,
 CONSTRAINT chk_user_brand CHECK ((role IN ('organiser','staff') AND brand IS NOT NULL) OR (role IN ('admin','customer') AND brand IS NULL)),
 CONSTRAINT chk_user_active CHECK(active IN(0,1))
) ENGINE=InnoDB;
CREATE TABLE venues (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(200) NOT NULL,
 brand ENUM('AWM') NOT NULL, location VARCHAR(200) NOT NULL,
 capacity INT NOT NULL, active TINYINT NOT NULL DEFAULT 1,
 CONSTRAINT chk_venue_capacity CHECK(capacity BETWEEN 1 AND 1000),
 CONSTRAINT chk_venue_active CHECK(active IN(0,1)),
 INDEX idx_venues_brand(brand,active)
) ENGINE=InnoDB;
CREATE TABLE events (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200) NOT NULL,
 genre VARCHAR(80) NOT NULL, duration_minutes INT NOT NULL,
 brand ENUM('AWM') NOT NULL, venue_id INT UNSIGNED NOT NULL,
 starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL,
 capacity INT NOT NULL, price_cents INT NOT NULL, active TINYINT NOT NULL DEFAULT 1,
 CONSTRAINT fk_event_venue FOREIGN KEY(venue_id) REFERENCES venues(id) ON DELETE RESTRICT,
 CONSTRAINT chk_event_time CHECK(ends_at>starts_at),
 CONSTRAINT chk_event_capacity CHECK(capacity>0),
 CONSTRAINT chk_event_price CHECK(price_cents BETWEEN 0 AND 100000),
 CONSTRAINT chk_event_duration CHECK(duration_minutes BETWEEN 1 AND 600),
 CONSTRAINT chk_event_active CHECK(active IN(0,1)),
 INDEX idx_events_brand_time(brand,active,starts_at),
 INDEX idx_events_venue_time(venue_id,active,starts_at,ends_at)
) ENGINE=InnoDB;
CREATE TABLE bookings (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL,
 event_id INT UNSIGNED NOT NULL, quantity INT NOT NULL, total_cents INT NOT NULL,
 reference CHAR(32) NOT NULL UNIQUE,
 status ENUM('confirmed','cancelled') NOT NULL DEFAULT 'confirmed',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_booking_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE RESTRICT,
 CONSTRAINT fk_booking_event FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE RESTRICT,
 CONSTRAINT chk_booking_quantity CHECK(quantity BETWEEN 1 AND 10),
 CONSTRAINT chk_booking_total CHECK(total_cents>=0),
 INDEX idx_bookings_event_status(event_id,status),
 INDEX idx_bookings_user(user_id,id)
) ENGINE=InnoDB;

-- Additive migration. Does not drop or replace existing project tables.
CREATE TABLE IF NOT EXISTS seats (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 venue_id INT UNSIGNED NOT NULL,
 row_index INT NOT NULL,
 seat_number INT NOT NULL,
 label VARCHAR(12) NOT NULL,
 active TINYINT NOT NULL DEFAULT 1,
 UNIQUE KEY uq_seat_position(venue_id,row_index,seat_number),
 UNIQUE KEY uq_seat_label(venue_id,label),
 CONSTRAINT fk_seat_venue FOREIGN KEY(venue_id) REFERENCES venues(id) ON DELETE RESTRICT,
 CHECK(row_index BETWEEN 1 AND 100),
 CHECK(seat_number BETWEEN 1 AND 10),
 CHECK(active IN (0,1))
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS booking_seats (
 booking_id INT UNSIGNED NOT NULL,
 seat_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(booking_id,seat_id),
 CONSTRAINT fk_bs_booking FOREIGN KEY(booking_id) REFERENCES bookings(id) ON DELETE RESTRICT,
 CONSTRAINT fk_bs_seat FOREIGN KEY(seat_id) REFERENCES seats(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS event_seats (
 event_id INT UNSIGNED NOT NULL,
 seat_id INT UNSIGNED NOT NULL,
 booking_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(event_id,seat_id),
 INDEX idx_es_booking(booking_id),
 CONSTRAINT fk_es_event FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE RESTRICT,
 CONSTRAINT fk_es_seat FOREIGN KEY(seat_id) REFERENCES seats(id) ON DELETE RESTRICT,
 CONSTRAINT fk_es_booking FOREIGN KEY(booking_id) REFERENCES bookings(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
