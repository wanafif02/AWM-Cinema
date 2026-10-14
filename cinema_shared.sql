-- MySQL 8 / MariaDB 10.4+ (XAMPP). Import ONCE in phpMyAdmin.
-- Creates a new database; refuses to overwrite existing tables/data.
CREATE DATABASE IF NOT EXISTS cinema_shared CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cinema_shared;
SET time_zone='+00:00';
CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(200) NOT NULL, email VARCHAR(254) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','organiser','staff','customer') NOT NULL DEFAULT 'customer',
 brand ENUM('GSC','TGV','PARAGON') NULL,
 active TINYINT NOT NULL DEFAULT 1,
 CONSTRAINT chk_user_brand CHECK ((role IN ('organiser','staff') AND brand IS NOT NULL) OR (role IN ('admin','customer') AND brand IS NULL)),
 CONSTRAINT chk_user_active CHECK(active IN(0,1))
) ENGINE=InnoDB;
CREATE TABLE venues (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(200) NOT NULL,
 brand ENUM('GSC','TGV','PARAGON') NOT NULL, location VARCHAR(200) NOT NULL,
 capacity INT NOT NULL, active TINYINT NOT NULL DEFAULT 1,
 CONSTRAINT chk_venue_capacity CHECK(capacity BETWEEN 1 AND 1000),
 CONSTRAINT chk_venue_active CHECK(active IN(0,1)),
 INDEX idx_venues_brand(brand,active)
) ENGINE=InnoDB;
CREATE TABLE events (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200) NOT NULL,
 genre VARCHAR(80) NOT NULL, duration_minutes INT NOT NULL,
 brand ENUM('GSC','TGV','PARAGON') NOT NULL, venue_id INT UNSIGNED NOT NULL,
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
-- DEMONSTRATION ONLY: every seeded account password is CinemaDemo123!
-- Hashes only in SQL; change all demonstration passwords before deployment.
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Administrator','admin@cinema.test','$2b$12$ACS.O6njl4.80aZZLGxtDuiUgEe.iYMyjfM1aaRcdWXK9cxC7OJhi','admin',NULL);
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Aiman Hakim','aiman@gsc.test','$2b$12$ACS.O6njl4.80aZZLGxtDuiUgEe.iYMyjfM1aaRcdWXK9cxC7OJhi','admin',NULL);
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Wan Afifuddin','wan@tgv.test','$2b$12$ACS.O6njl4.80aZZLGxtDuiUgEe.iYMyjfM1aaRcdWXK9cxC7OJhi','customer',NULL);
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Fairuz Ikhwan','fairuz@paragon.test','$2b$12$ACS.O6njl4.80aZZLGxtDuiUgEe.iYMyjfM1aaRcdWXK9cxC7OJhi','organiser','PARAGON');
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Demo Customer One','customer@gsc.test','$2b$12$ACS.O6njl4.80aZZLGxtDuiUgEe.iYMyjfM1aaRcdWXK9cxC7OJhi','customer',NULL);
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Demo Customer Two','customer2@cinema.test','$2b$12$ACS.O6njl4.80aZZLGxtDuiUgEe.iYMyjfM1aaRcdWXK9cxC7OJhi','customer',NULL);
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Mat','mat@gsc.test','$2b$12$ACS.O6njl4.80aZZLGxtDuiUgEe.iYMyjfM1aaRcdWXK9cxC7OJhi','organiser','GSC');
INSERT INTO venues(name,brand,location,capacity) VALUES('GSC Demo Hall 1','GSC','Kuala Lumpur (demo)',100);
INSERT INTO venues(name,brand,location,capacity) VALUES('GSC Demo Hall 2','GSC','Kuala Lumpur (demo)',80);
INSERT INTO venues(name,brand,location,capacity) VALUES('TGV Demo Hall 1','TGV','Selangor (demo)',90);
INSERT INTO venues(name,brand,location,capacity) VALUES('TGV Demo Hall 2','TGV','Selangor (demo)',100);
INSERT INTO venues(name,brand,location,capacity) VALUES('Paragon Demo Hall 1','PARAGON','Johor (demo)',80);
INSERT INTO venues(name,brand,location,capacity) VALUES('Paragon Demo Hall 2','PARAGON','Johor (demo)',90);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Beyond the Horizon','Adventure',128,'GSC',1,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 8 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 8 DAY),INTERVAL 180 MINUTE),60,2200);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Midnight Signal','Thriller',115,'GSC',2,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 9 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 9 DAY),INTERVAL 180 MINUTE),60,1800);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('The Last Ember','Action',140,'GSC',1,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 10 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 10 DAY),INTERVAL 180 MINUTE),60,2500);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Letters to Tomorrow','Drama',110,'GSC',2,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 11 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 11 DAY),INTERVAL 180 MINUTE),60,1900);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Wild at Heart','Animation',96,'GSC',1,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 12 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 12 DAY),INTERVAL 180 MINUTE),60,1600);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('A World Apart','Sci-Fi',132,'GSC',2,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 13 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 13 DAY),INTERVAL 180 MINUTE),60,2400);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('TGV Demo Adventure','Adventure',120,'TGV',3,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 14 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 14 DAY),INTERVAL 180 MINUTE),60,2000);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('TGV Demo Comedy','Comedy',100,'TGV',4,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 15 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 15 DAY),INTERVAL 180 MINUTE),60,1800);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Paragon Demo Journey','Drama',125,'PARAGON',5,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 16 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 16 DAY),INTERVAL 180 MINUTE),60,1700);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Paragon Demo Mystery','Mystery',112,'PARAGON',6,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 17 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 17 DAY),INTERVAL 180 MINUTE),60,1900);
INSERT INTO bookings(user_id,event_id,quantity,total_cents,reference,status) VALUES(5,1,2,4400,LOWER(REPLACE(UUID(),'-','')),'confirmed');
INSERT INTO bookings(user_id,event_id,quantity,total_cents,reference,status) VALUES(6,2,2,3600,LOWER(REPLACE(UUID(),'-','')),'confirmed');
INSERT INTO bookings(user_id,event_id,quantity,total_cents,reference,status) VALUES(5,3,2,5000,LOWER(REPLACE(UUID(),'-','')),'confirmed');
INSERT INTO bookings(user_id,event_id,quantity,total_cents,reference,status) VALUES(6,7,2,4000,LOWER(REPLACE(UUID(),'-','')),'confirmed');
INSERT INTO bookings(user_id,event_id,quantity,total_cents,reference,status) VALUES(5,9,2,3400,LOWER(REPLACE(UUID(),'-','')),'confirmed');
-- Data counts: users 7, venues 6, events 10, bookings 5.
