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

USE awm_cinema;
SET time_zone='+00:00';
-- Local demo password for all accounts: AWMCinema123!
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Aiman Hakim','aiman@awm.test','$2b$12$ntjKHHEpFKPyvaJBihGjUu85qAERhQs61rkXt0RXmGrg3eZMsuwcW','admin',NULL);
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Wan Afifuddin','afif@awm.test','$2b$12$ntjKHHEpFKPyvaJBihGjUu85qAERhQs61rkXt0RXmGrg3eZMsuwcW','customer',NULL);
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Fairuz Ikhwan (Mat)','fairuz@awm.test','$2b$12$ntjKHHEpFKPyvaJBihGjUu85qAERhQs61rkXt0RXmGrg3eZMsuwcW','staff','AWM');
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Demo Customer Two','customer2@awm.test','$2b$12$ntjKHHEpFKPyvaJBihGjUu85qAERhQs61rkXt0RXmGrg3eZMsuwcW','customer',NULL);
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Demo Customer Three','customer3@awm.test','$2b$12$ntjKHHEpFKPyvaJBihGjUu85qAERhQs61rkXt0RXmGrg3eZMsuwcW','customer',NULL);
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Demo Staff','staff2@awm.test','$2b$12$ntjKHHEpFKPyvaJBihGjUu85qAERhQs61rkXt0RXmGrg3eZMsuwcW','staff','AWM');
INSERT INTO users(name,email,password_hash,role,brand) VALUES('Demo Organiser','organiser@awm.test','$2b$12$ntjKHHEpFKPyvaJBihGjUu85qAERhQs61rkXt0RXmGrg3eZMsuwcW','organiser','AWM');
INSERT INTO venues(name,brand,location,capacity) VALUES('AWM Hall 1','AWM','Kuala Lumpur - Demo Cinema',60);
INSERT INTO venues(name,brand,location,capacity) VALUES('AWM Hall 2','AWM','Kuala Lumpur - Demo Cinema',60);
INSERT INTO venues(name,brand,location,capacity) VALUES('AWM Hall 3','AWM','Kuala Lumpur - Demo Cinema',60);
INSERT INTO venues(name,brand,location,capacity) VALUES('AWM Hall 4','AWM','Kuala Lumpur - Demo Cinema',60);
INSERT INTO venues(name,brand,location,capacity) VALUES('AWM Hall 5','AWM','Kuala Lumpur - Demo Cinema',60);
INSERT INTO venues(name,brand,location,capacity) VALUES('AWM Hall 6','AWM','Kuala Lumpur - Demo Cinema',60);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Avengers: Endgame','Action',181,'AWM',1,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 1 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 1 DAY),INTERVAL 201 MINUTE),60,1900);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Avatar','Adventure',162,'AWM',2,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 2 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 2 DAY),INTERVAL 182 MINUTE),60,2000);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Interstellar','Sci-Fi',169,'AWM',3,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 3 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 3 DAY),INTERVAL 189 MINUTE),60,2100);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Inception','Thriller',148,'AWM',4,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 4 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 4 DAY),INTERVAL 168 MINUTE),60,2200);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Titanic','Romance',194,'AWM',5,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 5 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 5 DAY),INTERVAL 214 MINUTE),60,2300);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Top Gun: Maverick','Action',130,'AWM',6,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 6 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 6 DAY),INTERVAL 150 MINUTE),60,2400);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Avengers: Endgame','Action',181,'AWM',1,DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 7 DAY),DATE_ADD(DATE_ADD(TIMESTAMP(UTC_DATE(),'12:00:00'),INTERVAL 7 DAY),INTERVAL 201 MINUTE),60,2500);
INSERT INTO events(title,genre,duration_minutes,brand,venue_id,starts_at,ends_at,capacity,price_cents) VALUES('Avatar','Adventure',162,'AWM',2,DATE_SUB(UTC_TIMESTAMP(),INTERVAL 45 MINUTE),DATE_ADD(DATE_SUB(UTC_TIMESTAMP(),INTERVAL 45 MINUTE),INTERVAL 182 MINUTE),60,2600);
INSERT INTO bookings(user_id,event_id,quantity,total_cents,reference,status) VALUES(2,1,2,3800,LOWER(REPLACE(UUID(),'-','')),'confirmed');
INSERT INTO bookings(user_id,event_id,quantity,total_cents,reference,status) VALUES(4,2,2,4000,LOWER(REPLACE(UUID(),'-','')),'confirmed');
INSERT INTO bookings(user_id,event_id,quantity,total_cents,reference,status) VALUES(2,3,2,4200,LOWER(REPLACE(UUID(),'-','')),'confirmed');
INSERT INTO bookings(user_id,event_id,quantity,total_cents,reference,status) VALUES(4,4,2,4400,LOWER(REPLACE(UUID(),'-','')),'confirmed');
INSERT INTO bookings(user_id,event_id,quantity,total_cents,reference,status) VALUES(2,5,2,4600,LOWER(REPLACE(UUID(),'-','')),'confirmed');
-- Six rows of ten physical seats per hall. No application setup command needed.
INSERT INTO seats(venue_id,row_index,seat_number,label)
SELECT v.id,r.n,c.n,CONCAT(CHAR(64+r.n),c.n) FROM venues v
CROSS JOIN (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6)r
CROSS JOIN (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10)c
ORDER BY v.id,r.n,c.n;
-- Seed bookings get A1/A2 on their different screenings (demo assignment).
INSERT INTO booking_seats(booking_id,seat_id)
SELECT b.id,s.id FROM bookings b JOIN events e ON e.id=b.event_id JOIN seats s ON s.venue_id=e.venue_id
WHERE s.row_index=1 AND s.seat_number<=b.quantity;
INSERT INTO event_seats(event_id,seat_id,booking_id)
SELECT b.event_id,bs.seat_id,b.id FROM booking_seats bs JOIN bookings b ON b.id=bs.booking_id WHERE b.status='confirmed';
-- Counts: users7,venues6,events8,bookings5,seats360,booking_seats10,event_seats10.
