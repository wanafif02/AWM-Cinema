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
