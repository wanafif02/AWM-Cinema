# AWM Cinema — Cara Setup dan Guna

## 1. Benda Yang Kena Ada

- Folder projek: `awm-cinema`
- Nama database: `awm_cinema`
- Guna XAMPP supaya senang run Apache dan MySQL.
- Perlukan PHP 8.0+, extension PDO MySQL dan cURL.
- Database guna MariaDB 10.4+ atau MySQL 8.
- Tak perlu Python, npm atau Composer untuk run.

## 2. Buka Projek Dalam VS Code

- Extract ZIP projek.
- Letak folder dekat:

t
C:\xampp\htdocs\awm-cinema


- Buka VS Code → **File → Open Folder** → pilih `awm-cinema`.
- Jangan bagi folder jadi berlapis macam `awm-cinema/awm-cinema`.

## 3. Import Database Sekali Je

- Start **Apache** dan **MySQL** dalam XAMPP.
- Buka [phpMyAdmin](http://localhost/phpmyadmin).
- Tekan **Import** → pilih `database/awm_cinema.sql` → **Go**.
- Fail ni dah buat **tujuh table**, seats, akaun, screenings dan contoh booking.
- Untuk fresh install, tak perlu run command tambah seats.
- Jangan import `ddl.sql` atau `dml.sql` sekali.
- Kalau database `awm_cinema` dah ada, export backup dan check dulu sebelum import semula.
- SQL ni tak ada arahan DROP atau overwrite.
- Database lama `cinema_shared` tak disentuh dan tak digunakan oleh projek ni.

## 4. Buka Website dan Login

- Buka [AWM Cinema](http://localhost/awm-cinema/).
- Muka depan ada preview movie dan tiga bahagian login.
- Semua orang boleh tengok screenings, tapi nak reserve seats kena login Customer.
- Pilih bahagian Admin tak terus dapat permission admin. Server tetap check role akaun.

| Role     | Nama                | Email            | Password demo | Tema           |
| Admin    | Aiman Hakim         | aiman@awm.test   | AWMCinema123! | Putih          |
| Customer | Wan Afifuddin         afif@awm.test    | AWMCinema123! | Hitam dan emas |
| Staff    | Fairuz Ikhwan — Mat | fairuz@awm.test  | AWMCinema123! | Oren           |#matkedahoripadi

- Akaun demo lain: `customer2@awm.test`, `customer3@awm.test`, `staff2@awm.test` dan `organiser@awm.test`.
- Semua akaun demo guna password yang sama.
- Password disimpan sebagai **bcrypt hash** dalam SQL.
- Tukar password demo sebelum deployment sebenar.

## 5. Bahagian Wan — Customer

- Login bahagian **Customer**.
- Pilih screening → **Book tickets**.
- Pilih **1–10 seats** → **Confirm**.
- Buka **My tickets** untuk tengok seat labels, QR atau cancel booking.
- Kalau belum login, sistem akan buka Sign in bila nak booking.
- Klik seat belum reserve lagi. Reservation dibuat masa tekan Confirm melalui POST.
- Kalau dapat **409 Conflict**, refresh seat map dan pilih seats semula.
- Bila cancel, seats dibebaskan tapi history booking dan seats masih disimpan.

## 6. Bahagian Aiman — Admin

- Login bahagian **Admin**.
- **Users:** tambah, edit dan deactivate pengguna.
- **Screenings & halls:** tambah, tengok, edit dan padam events/venues.
- **Ticket desk:** semak dan cancel booking.
- **Seat Studio:** urus layout dan seats.
- **Hall activity:** tengok hall yang sedang berjalan atau ready.
- Admin boleh akses semua resource AWM.
- Admin tak boleh demote atau deactivate akaun sendiri.
- Akaun admin tak boleh booking tiket customer.

## 7. Bahagian Mat — Staff

- Login bahagian **Staff**.
- **Hall activity:** tengok status hall serta jumlah tiket dan seats.
- **Ticket desk:** tengok booking untuk cinema yang assigned pada akaun staff.
- **Seat Studio:** block atau buka semula seats dan generate layout untuk hall baru.
- **Screenings & halls:** urus operasi hall dan screening.
- Staff tak boleh manage users.
- Tak boleh block seats yang dah reserved untuk screening sedang berjalan atau akan datang.
- Tak boleh cancel booking selepas screening bermula.
- Contoh screening semasa dibuat waktu import database. Status jadi finished bila tempohnya habis.

## 8. Fail Code Yang Kena Kenal

| Fail | Kegunaan |
| --- | --- |
| `assets/admin-aiman.js` | Frontend admin Aiman |
| `assets/customer-wan-afif.js` | Frontend booking seats Wan |
| `assets/seats-mat.js` | Seats, hall activity dan ticket operations Mat |
| `assets/workspace.js` | Login tiga bahagian dan role routing |
| `assets/common.js` | API calls, movie list, booking history, QR, cancellation dan cinema CRUD |
| `assets/catalog.js` | Metadata preview poster movie |
| `assets/app.js` | Startup aplikasi |
| `assets/style.css` | Tema customer, admin dan staff |
| `index.php` | HTML sections dan dialogs |
| `api/endpoints.php` | API users, events, venues dan bookings |
| `api/seats.php` | API seats dan operations/halls |
| `api/bootstrap.php` | Helpers auth, validation dan database |
| `api/index.php` | Entry point, middleware, errors dan logging |

- Nama ahli pada fail menunjukkan pembahagian tanggungjawab.
- Bukti contribution tetap kena guna **commits, kerja dan testing sebenar**.
- Setiap orang kena faham, customise dan demo bahagian sendiri.
- Projek ni **satu aplikasi dengan tiga role workspace**.
- Kalau lecturer nak frontend standalone berasingan, confirm dulu sama ada format ni diterima.

## 9. URL API

- Users: [http://localhost/awm-cinema/api/users](http://localhost/awm-cinema/api/users)
- Events: [http://localhost/awm-cinema/api/events](http://localhost/awm-cinema/api/events)
- Venues: [http://localhost/awm-cinema/api/venues](http://localhost/awm-cinema/api/venues)
- Bookings: [http://localhost/awm-cinema/api/bookings](http://localhost/awm-cinema/api/bookings)

Kalau Apache rewrite tak jalan, guna fallback:

- Events: [http://localhost/awm-cinema/api/index.php?route=events](http://localhost/awm-cinema/api/index.php?route=events)
- Health check: [http://localhost/awm-cinema/api/index.php?route=health](http://localhost/awm-cinema/api/index.php?route=health)

## 10. Token, API Key dan Database Settings

- API Users perlukan **admin JWT**.
- Kalau ubah data users terus melalui API, kena sertakan **X-API-Key** juga.
- Browser guna route `admin/users` yang check role admin dan masukkan API key dekat server.
- API key tak dimasukkan dalam JavaScript.
- Untuk ambil API key bagi Postman, buka terminal PowerShell dalam folder projek:

```powershell
& C:\xampp\php\php.exe tools\admin_key.php
```

- Jangan share, commit atau screenshot API key.
- Token tamat selepas **1 jam**. Login semula untuk dapat token baru.
- Default database settings:
  - Host: `127.0.0.1`
  - Port: `3306`
  - Username: `root`
  - Password: kosong
- Kalau settings lain, copy `config.example.php` jadi `config.local.php`, kemudian ubah DB settings.
- Simpan keys dalam `storage` dan fail `config.local.php` secara private.

## 11. Postman dan QuickChart QR API

- Import `docs/postman_collection.json` dalam Postman.
- Request login customer, staff dan admin akan simpan token ikut role.
- API docs ada request body, methods, permissions, response/status examples dan seat extension.
- QR endpoint panggil **QuickChart QR API** melalui HTTPS.
- Response QR ialah imej **PNG dalam bentuk base64** dalam JSON.
- Kena ada internet, PHP cURL dan CA certificates yang berfungsi.
- Jangan disable TLS verification.
- Hanya random demo ticket reference dihantar. Maklumat peribadi dan JWT tak dihantar.
- Kalau QR gagal dengan error **502**, booking masih tersimpan dan boleh cuba QR semula.
- Poster ialah local image assets, bukan external API integration.
- Untuk assignment, ambil bukti sebenar panggilan QuickChart dan QR yang berjaya.