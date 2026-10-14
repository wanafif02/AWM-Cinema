-- Existing database upgrade only: no table drops, no password changes.
USE cinema_shared;
START TRANSACTION;
UPDATE users SET role='admin',brand=NULL WHERE email='aiman@gsc.test';
UPDATE users SET role='customer',brand=NULL WHERE email='wan@tgv.test';
-- Reuse the old demo staff account if no Mat account already exists.
UPDATE users SET name='Mat',email='mat@gsc.test',role='organiser',brand='GSC'
WHERE email='staff@gsc.test' AND NOT EXISTS (
 SELECT id FROM (SELECT id FROM users WHERE email='mat@gsc.test') AS existing_mat
);
COMMIT;
SELECT id,name,email,role,brand FROM users
WHERE email IN ('aiman@gsc.test','wan@tgv.test','mat@gsc.test');
