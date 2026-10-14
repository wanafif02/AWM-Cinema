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
