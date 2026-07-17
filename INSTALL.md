# Panduan Instalasi dan Penggunaan Aplikasi Pembayaran SPP

Dokumen ini menjelaskan cara memasang, mengatur, dan menggunakan aplikasi pembayaran SPP berbasis Laravel untuk MA Plus Taruna Teknik Al Jabbar.

Target pembaca:

- pengguna Windows,
- memakai Laragon sebagai local server,
- ingin menjalankan aplikasi dari nol,
- ingin memakai database dari migration dan seeder agar cepat siap demo,
- ingin mengaktifkan pembayaran online Midtrans Sandbox.

## 1. Kebutuhan Software

Install software berikut:

- Laragon Full
- Composer
- Node.js LTS
- Browser, misalnya Chrome
- Git, opsional

Download:

- Laragon: `https://laragon.org/download`
- Composer: `https://getcomposer.org/download`
- Node.js: `https://nodejs.org`

Cek instalasi lewat terminal Laragon atau PowerShell:

```bash
php -v
composer -V
node -v
npm -v
```

Jika `php` tidak terbaca, buka terminal dari Laragon:

```text
Laragon > Terminal
```

Laragon biasanya otomatis memakai PHP bawaan Laragon.

## 2. Menaruh Folder Project

Letakkan folder project di folder kerja, contoh:

```text
C:\laragon\www\spp-al-jabbar
```

atau:

```text
D:\Web spp
```

Masuk ke folder project:

```bash
cd "D:\Web spp"
```

Sesuaikan path dengan lokasi project di komputer.

## 3. Menyalakan Laragon

1. Buka Laragon.
2. Klik **Start All**.
3. Pastikan web server dan MySQL berjalan.

Jika MySQL tidak menyala:

```text
Laragon > Menu > MySQL > Start
```

Jika port bentrok, cek aplikasi lain seperti XAMPP, MySQL service bawaan Windows, atau aplikasi lain yang memakai port `3306`.

## 4. Membuat Database MySQL

Nama database yang dipakai:

```text
spp_al_jabbar
```

### Cara 1: Lewat Terminal

Buka terminal Laragon:

```bash
mysql -u root
```

Buat database:

```sql
CREATE DATABASE spp_al_jabbar;
EXIT;
```

### Cara 2: Lewat phpMyAdmin

1. Buka browser.
2. Akses:

```text
http://localhost/phpmyadmin
```

3. Login dengan:

```text
Username: root
Password: kosongkan
```

4. Klik **New**.
5. Isi nama database:

```text
spp_al_jabbar
```

6. Klik **Create**.

## 5. Install Dependency Project

### Cara Cepat: Pakai Installer Otomatis

Project ini menyediakan installer otomatis:

```text
install.bat
```

Cara pakai:

1. Pastikan Laragon sudah dibuka dan MySQL sudah **Start**.
2. Double-click file `install.bat` di folder project.
3. Installer akan otomatis:
   - membuat file `.env` jika belum ada,
   - menjalankan `composer install`,
   - menjalankan `npm install`,
   - generate `APP_KEY`,
   - membuat database `spp_al_jabbar` jika belum ada,
   - menjalankan migration,
   - mengisi seeder data contoh jika data demo belum ada,
   - membersihkan cache Laravel,
   - menjalankan server Laravel di `http://127.0.0.1:8000`.

Biarkan jendela installer tetap terbuka selama aplikasi digunakan.

Jika `install.bat` dijalankan ulang, installer tidak akan menggandakan data demo selama akun `admin` sudah ada.

Jika ingin reset database dari nol dan isi ulang data contoh, jalankan lewat terminal:

```bash
.\install.bat -Fresh
```

Mode `-Fresh` akan meminta konfirmasi `RESET` karena semua tabel dan data lama akan dihapus.

Jika hanya ingin instalasi tanpa menjalankan server:

```bash
.\install.bat -NoServe
```

### Cara Manual

Masuk ke folder project:

```bash
cd "D:\Web SPP"
```

Install dependency Laravel:

```bash
composer install
```

Install dependency frontend:

```bash
npm install
```

Jika folder `vendor` dan `node_modules` sudah ada, perintah di atas tetap aman dijalankan untuk memastikan dependency lengkap.

## 6. Membuat dan Mengatur File `.env`

Jika `.env` belum ada, copy dari `.env.example`:

```bash
copy .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Isi bagian utama `.env` seperti berikut:

```env
APP_NAME="SPP Al Jabbar"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spp_al_jabbar
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
```

`APP_KEY` akan otomatis terisi setelah menjalankan:

```bash
php artisan key:generate
```

Setelah mengubah `.env`, jalankan:

```bash
php artisan optimize:clear
```

## 7. Migrasi dan Seeder Database

Project ini sudah menyediakan migration dan seeder. Cara tercepat untuk membuat struktur tabel dan data awal:

```bash
php artisan migrate --seed
```

Jika database sudah pernah diisi dan ingin mulai ulang dari nol:

```bash
php artisan migrate:fresh --seed
```

Peringatan:

```text
migrate:fresh --seed akan menghapus semua tabel dan data lama.
```

Seeder membuat data awal seperti:

- tahun ajaran,
- kelas,
- biaya SPP,
- siswa,
- akun admin,
- akun kepala sekolah,
- akun siswa,
- beberapa tagihan awal.

Setelah aplikasi sudah dipakai sungguhan, jangan gunakan `migrate:fresh --seed` kecuali memang ingin reset data.

## 8. Menjalankan Aplikasi

Jalankan server Laravel:

```bash
php artisan serve
```

Buka:

```text
http://127.0.0.1:8000
```

Jika port `8000` bentrok:

```bash
php artisan serve --port=8001
```

Buka:

```text
http://127.0.0.1:8001
```

## 9. Menjalankan Scheduler

Scheduler dipakai untuk:

- generate tagihan bulanan otomatis,
- membersihkan invoice Midtrans pending yang melewati batas waktu.

Buka terminal kedua di folder project:

```bash
php artisan schedule:work
```

Biarkan terminal ini tetap berjalan saat aplikasi digunakan.

Cek daftar jadwal:

```bash
php artisan schedule:list
```

Di project ini scheduler pentingnya:

```text
tagihan:generate-bulanan
midtrans-pending-cleaner
```

## 10. Menjalankan Queue

Project memakai database queue. Scheduler sudah menjalankan:

```text
queue:work --stop-when-empty
```

Jika ingin menjalankan queue manual:

```bash
php artisan queue:work
```

Untuk penggunaan lokal, biasanya cukup menjalankan `schedule:work`.

## 11. Membuat Akun Midtrans Sandbox

Pembayaran online aplikasi ini memakai Midtrans Snap.

Rujukan resmi:

- Midtrans Dashboard login: `https://dashboard.midtrans.com/login`
- Dokumentasi dashboard dan sandbox: `https://docs.midtrans.com/docs/dashboard-basics`
- Dokumentasi Snap preparation: `https://docs.midtrans.com/docs/snap-preparation`
- Dokumentasi access keys: `https://docs.midtrans.com/docs/access-keys`

### 11.1 Daftar Akun Midtrans

1. Buka:

```text
https://dashboard.midtrans.com/login
```

2. Pilih daftar akun baru jika belum punya akun.
3. Isi data merchant.
4. Verifikasi email.
5. Login ke dashboard Midtrans.

Midtrans menyediakan environment **Sandbox** untuk pengujian. Transaksi sandbox tidak memakai uang asli.

### 11.2 Masuk Mode Sandbox

Setelah login ke dashboard Midtrans:

1. Lihat dropdown environment di bagian kiri atas dashboard.
2. Pilih **Sandbox**.
3. Pastikan tampilan dashboard menunjukkan mode sandbox.

Catatan dari dokumentasi Midtrans: Sandbox dan Production memakai access key berbeda. Jangan mencampur key sandbox dan production.

### 11.3 Mengambil Server Key dan Client Key

Di dashboard Midtrans:

1. Pastikan environment adalah **Sandbox**.
2. Buka:

```text
Settings > Access Keys
```

3. Copy:

```text
Server Key
Client Key
```

Menurut dokumentasi Midtrans, `Client Key` dipakai di sisi frontend/client, sedangkan `Server Key` dipakai untuk request backend/server dan harus dijaga rahasia.

### 11.4 Memasukkan Key Midtrans ke `.env`

Buka file `.env`, isi:

```env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxxxxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxxxxxxxxxx
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_PENDING_TIMEOUT_MINUTES=30
```

Keterangan:

- `MIDTRANS_SERVER_KEY`: key server dari Midtrans Sandbox.
- `MIDTRANS_CLIENT_KEY`: key client dari Midtrans Sandbox.
- `MIDTRANS_IS_PRODUCTION=false`: wajib `false` untuk sandbox.
- `MIDTRANS_PENDING_TIMEOUT_MINUTES=30`: invoice Midtrans pending akan dihapus otomatis setelah 30 menit.

Setelah mengubah `.env`, jalankan:

```bash
php artisan optimize:clear
```

### 11.5 Cara Integrasi Midtrans di Project Ini

Project ini sudah terintegrasi dengan Midtrans di file:

```text
app/Services/MidtransService.php
app/Http/Controllers/SiswaPortalController.php
app/Http/Controllers/MidtransWebhookController.php
resources/views/siswa/midtrans.blade.php
```

Alurnya:

1. Siswa membuka **Tagihan Saya**.
2. Siswa klik **Online**.
3. Sistem membuat data pembayaran `metode = midtrans`, `status = pending`.
4. Sistem meminta Snap Token ke Midtrans.
5. Halaman menampilkan tombol **Buka Midtrans Snap**.
6. Snap Midtrans terbuka.
7. Jika pembayaran sukses, status pembayaran menjadi `settlement/success`.
8. Tagihan berubah menjadi `lunas`.
9. Jika invoice pending lewat batas waktu, data invoice pending Midtrans dihapus otomatis.

### 11.6 Menguji Pembayaran Sandbox

1. Login sebagai siswa.
2. Buka **Tagihan Saya**.
3. Klik **Online**.
4. Klik **Buka Midtrans Snap**.
5. Pilih metode pembayaran sandbox.
6. Ikuti instruksi test payment dari halaman Snap/Midtrans.

Jika transaksi pending:

- pembayaran tampil sebagai pending di riwayat,
- jika melewati batas `MIDTRANS_PENDING_TIMEOUT_MINUTES`, invoice pending Midtrans akan dibersihkan.

### 11.7 Webhook Midtrans

Route webhook project:

```text
POST /midtrans/webhook
```

Untuk lokal biasa, webhook dari Midtrans tidak bisa langsung mengakses `127.0.0.1`. Pilihan saat development:

- gunakan tombol finish dari Snap yang sudah ada di aplikasi,
- atau gunakan tunneling seperti ngrok jika ingin webhook benar-benar dipanggil Midtrans.

Jika memakai ngrok, contoh:

```text
https://domain-ngrok.ngrok-free.app/midtrans/webhook
```

Masukkan URL itu ke dashboard Midtrans bagian konfigurasi payment notification/webhook.

## 12. Login Awal Setelah Seeder

Seeder menyediakan akun awal agar admin bisa masuk dan mengatur data.

```text
Admin TU
Username: admin
Password: password

Kepala Sekolah
Username: kepala
Password: password

Siswa
Username: NIS siswa dari data seed, contoh 26260001
Password: password
```

Setelah aplikasi dipakai sungguhan:

1. Login sebagai admin.
2. Buka **User Management**.
3. Ganti password akun default.
4. Tambahkan akun wali kelas jika diperlukan.

Catatan: akun awal ini tidak lagi ditampilkan di halaman login.

## 13. Urutan Setup Data Dari Nol

Urutan yang disarankan:

1. Tahun ajaran
2. Kelas
3. Biaya SPP
4. Data siswa
5. User login
6. Jadwal tagihan otomatis
7. Gratis/diskon jika ada
8. Tunggakan awal atau set terakhir bayar
9. Pembayaran dan laporan

## 14. Mengatur Tahun Ajaran

Masuk sebagai Admin TU:

```text
Admin TU > Pengaturan SPP > Tahun Ajaran
```

Yang perlu dilakukan:

1. Tambah tahun ajaran, contoh:

```text
2026/2027
```

2. Centang **Aktif** untuk tahun ajaran yang sedang berjalan.
3. Simpan.

Catatan:

- Hanya satu tahun ajaran yang sebaiknya aktif.
- Tagihan otomatis memakai tahun ajaran aktif.
- Kalau tahun ajaran baru dibuat, pastikan biaya SPP juga diatur untuk tahun ajaran tersebut.

## 15. Mengatur Kelas

Masuk:

```text
Admin TU > Pengaturan SPP > Kelas
```

Contoh kelas:

```text
X-A
X-B
XI-A
XI-B
XII-A
XII-B
```

Langkah:

1. Isi nama kelas.
2. Klik simpan.
3. Ulangi sesuai kebutuhan.

Kelas dipakai untuk:

- data siswa,
- biaya SPP per kelas,
- akun wali kelas,
- laporan kelas,
- filter tunggakan.

## 16. Mengatur Biaya SPP

Masuk:

```text
Admin TU > Pengaturan SPP > Biaya SPP
```

Langkah:

1. Pilih tahun ajaran.
2. Pilih kelas.
3. Isi nominal SPP.
4. Simpan.

Contoh:

```text
Tahun Ajaran: 2026/2027
Kelas: X-A
Nominal: 250000
```

Jika ingin semua kelas memakai biaya yang sama, gunakan pilihan **Semua Kelas** jika tersedia.

Catatan penting:

- Jika biaya untuk tahun ajaran aktif belum ada, sistem akan mencoba memakai biaya terbaru dari kelas yang sama.
- Namun untuk data yang rapi, tetap buat biaya SPP per tahun ajaran aktif.

## 17. Menambah Data Siswa

Masuk:

```text
Admin TU > Data Siswa
```

Langkah tambah siswa:

1. Isi NIS.
2. Isi nama siswa.
3. Isi email jika ada.
4. Pilih kelas.
5. Isi nama orang tua/wali.
6. Isi nomor WhatsApp orang tua.
7. Isi alamat.
8. Pilih status siswa.
9. Simpan.

Setelah siswa dibuat, sistem otomatis membuat akun login siswa:

```text
Username: NIS siswa
Password default: password
```

Contoh:

```text
NIS: 26260015
Nama: Budi Santoso
Kelas: X-A
No WA Orang Tua: 6281234567890
```

## 18. Import Data Siswa Dari Excel

Masuk:

```text
Admin TU > Data Siswa
```

Gunakan tombol import.

Kolom yang umum dipakai:

```text
nis
nama
email
kelas
nama_orang_tua
no_hp_orang_tua
alamat
status
```

Setelah import:

- siswa baru dibuat,
- siswa lama diperbarui jika NIS sama,
- akun siswa dibuat otomatis.

Jika import gagal, cek nama kolom dan format file.

## 19. Mengatur User Login

Masuk:

```text
Admin TU > User Management
```

Role yang tersedia:

- Admin TU
- Kepala Sekolah
- Wali Kelas
- Siswa / Orang Tua

### Membuat Akun Wali Kelas

1. Isi nama.
2. Isi username.
3. Isi password awal.
4. Pilih role **Wali Kelas**.
5. Pilih kelas yang dipegang.
6. Simpan.

Akun wali kelas hanya bisa melihat data kelas yang terhubung.

### Reset Password

Di tabel user:

1. Klik reset password.
2. Password akan dikembalikan ke default sesuai sistem.

Untuk keamanan, ganti password default sebelum dipakai sungguhan.

## 20. Mengatur Jadwal Tagihan Otomatis

Masuk:

```text
Admin TU > Pengaturan SPP > Otomatis & Diskon
```

Bagian **Jadwal Tagihan Otomatis**:

1. Aktifkan generate otomatis.
2. Isi tanggal tagihan keluar, contoh `1`.
3. Isi tanggal jatuh tempo, contoh `10`.
4. Simpan.

Contoh logika:

```text
Tanggal tagihan keluar: 1
Tanggal jatuh tempo: 10
```

Artinya setiap bulan, mulai tanggal 1, tagihan bulan tersebut dianggap sudah waktunya dibuat. Jatuh tempo ditetapkan tanggal 10.

Scheduler harus berjalan:

```bash
php artisan schedule:work
```

Jika ingin generate manual paksa:

```bash
php artisan tagihan:generate-bulanan --force
```

## 21. Mengatur Gratis atau Diskon Tagihan

Masuk:

```text
Admin TU > Pengaturan SPP > Otomatis & Diskon > Tambah Gratis/Diskon
```

Isi:

1. Tahun ajaran.
2. Bulan.
3. Tahun.
4. Cakupan:
   - Semua Siswa
   - Kelas Tertentu
   - Siswa Tertentu
5. Jenis:
   - Gratis Penuh
   - Potongan Nominal
   - Potongan Persen
6. Nilai potongan jika bukan gratis penuh.
7. Alasan.
8. Simpan.

Contoh gratis penuh:

```text
Bulan: Juni
Cakupan: Siswa Tertentu
Jenis: Gratis Penuh
Alasan: Beasiswa
```

Contoh diskon nominal:

```text
Bulan: Juni
Cakupan: Kelas X-A
Jenis: Potongan Nominal
Nilai: 50000
```

Catatan:

- Jika memilih siswa, sistem otomatis mengaitkan siswa tersebut dengan kelasnya.
- Aturan gratis/diskon berlaku saat tagihan dibuat.
- Jika tagihan sudah telanjur dibuat, hapus/buka ulang/generate ulang sesuai kebutuhan data.

## 22. Mengatur Tunggakan Siswa

Masuk:

```text
Admin TU > Tunggakan Siswa
```

Fitur ini dipakai jika ada siswa yang belum punya tagihan historis atau ingin menyesuaikan data berdasarkan bulan terakhir bayar.

### Set Terakhir Bayar

1. Pilih kelas jika ingin membatasi siswa.
2. Cari siswa berdasarkan NIS, nama, atau kelas.
3. Pilih:
   - Semua siswa aktif, atau
   - satu siswa tertentu.
4. Pilih tahun ajaran.
5. Pilih bulan terakhir bayar.
6. Isi tahun terakhir bayar.
7. Isi tanggal jatuh tempo.
8. Klik **Siapkan Tagihan**.

Contoh:

```text
Bulan terakhir bayar: Februari
Tahun: 2026
```

Jika jadwal tagihan otomatis sudah sampai Juni 2026, sistem akan membuat tagihan:

```text
Maret 2026
April 2026
Mei 2026
Juni 2026
```

Sistem juga menandai tunggakan sebelum atau sama dengan bulan terakhir bayar sebagai lunas.

### Konfirmasi Tunggakan

Di tabel tunggakan:

1. Lihat bubble bulan tunggakan.
2. Klik bulan sampai mana siswa membayar.
3. Isi tanggal transaksi.
4. Upload bukti pembayaran jika ada.
5. Konfirmasi.

Jika klik Juni 2026, sistem akan melunasi semua tunggakan sampai Juni 2026.

### Buka Ulang Tagihan

Jika salah input:

1. Lihat bagian tagihan yang sudah lunas.
2. Klik bubble hijau bulan lunas.
3. Konfirmasi buka ulang.

Tagihan akan kembali masuk tunggakan.

### Kirim WhatsApp Tunggakan

Jika siswa punya minimal satu tunggakan:

1. Klik **WA Tunggakan**.
2. Sistem membuka WhatsApp.
3. Pesan otomatis berisi:
   - nama siswa,
   - jumlah bulan tunggakan,
   - bulan apa saja,
   - total nominal.

## 23. Pembayaran Oleh Siswa

Login sebagai siswa.

Masuk:

```text
Siswa > Tagihan Saya
```

Pilihan pembayaran:

- **Online**: Midtrans Snap.
- **Tunai**: siswa membuat invoice tunai lalu membayar ke TU.

### Bayar Online

1. Klik **Online**.
2. Klik **Buka Midtrans Snap**.
3. Selesaikan pembayaran di Midtrans Sandbox.
4. Jika sukses, tagihan menjadi lunas.

Jika transaksi pending lebih dari batas waktu:

```env
MIDTRANS_PENDING_TIMEOUT_MINUTES=30
```

invoice pending Midtrans akan otomatis dihapus.

### Bayar Tunai

1. Klik **Tunai**.
2. Sistem membuat invoice tunai.
3. Siswa membawa bukti/invoice ke TU.
4. Admin TU mengonfirmasi di menu **Antrian Tunai**.

## 24. Konfirmasi Pembayaran Tunai

Login sebagai Admin TU.

Masuk:

```text
Admin TU > Antrian Tunai
```

Langkah:

1. Cari invoice tunai.
2. Klik **Konfirmasi Lunas**.
3. Tagihan berubah menjadi lunas.
4. Siswa bisa melihat kwitansi di riwayat.

## 25. Input Pembayaran Manual

Masuk:

```text
Admin TU > Transaksi Pembayaran
```

Bagian **Input Pembayaran Manual** dipakai untuk pembayaran yang sudah diterima langsung oleh TU.

Langkah:

1. Cari tagihan.
2. Pilih tagihan belum lunas.
3. Isi tanggal transaksi.
4. Klik simpan.

Jika salah input, transaksi manual yang sukses bisa dibatalkan dari tabel transaksi.

## 26. Riwayat Transaksi Pembayaran

Masuk:

```text
Admin TU > Transaksi Pembayaran
```

Fitur:

- melihat semua transaksi,
- mencari invoice/NIS/nama/bulan/metode/status,
- melihat jumlah data diperiksa,
- melihat waktu proses pencarian,
- membuka WhatsApp orang tua,
- membuka kwitansi,
- membatalkan transaksi manual.

Pencarian di halaman ini memakai `SequentialSearchService`.

## 27. Laporan

Masuk:

```text
Admin TU > Laporan Bulanan
```

Fitur:

- filter bulan,
- filter tahun,
- filter kelas,
- filter status,
- pencarian sequential nama/NIS,
- ringkasan pemasukan,
- ringkasan tunggakan,
- export PDF,
- export Excel.

Untuk Kepala Sekolah:

```text
Kepala Sekolah > Laporan Per Bulan
Kepala Sekolah > Laporan Per Kelas
Kepala Sekolah > Grafik Pemasukan
```

Kepala sekolah hanya monitoring dan tidak mengubah data.

## 28. Wali Kelas

Buat akun wali kelas di:

```text
Admin TU > User Management
```

Setelah login sebagai wali kelas, menu yang tersedia:

- Dashboard kelas
- Siswa kelas
- Tagihan kelas
- Riwayat pembayaran
- Tunggakan
- Laporan kelas
- Kontak orang tua

Wali kelas hanya melihat data dari kelas yang dihubungkan ke akunnya.

## 29. Notifikasi

Notifikasi muncul untuk aktivitas seperti:

- invoice tunai baru,
- pembayaran tunai dikonfirmasi,
- pembayaran Midtrans berhasil/gagal,
- pembayaran manual dicatat,
- transaksi manual dibatalkan.

Menu:

```text
Notifikasi
```

User bisa membuka notifikasi dan menandai semua sebagai dibaca.

## 30. Sequential Search Untuk TA

Implementasi algoritma ada di:

```text
app/Services/SequentialSearchService.php
```

Konsep:

1. Controller mengambil kumpulan data.
2. Service melakukan perulangan dari data pertama sampai terakhir.
3. Setiap data dihitung sebagai data diperiksa.
4. Data yang cocok dengan keyword dimasukkan ke hasil.
5. Sistem mengembalikan:
   - hasil,
   - jumlah data diperiksa,
   - waktu proses dalam milidetik.

Halaman yang memakai sequential search:

- Admin TU > Data Siswa
- Admin TU > Transaksi Pembayaran
- Admin TU > Pencarian Sequential
- Admin TU > Laporan Bulanan

Saat presentasi, tunjukkan:

- input keyword,
- angka data diperiksa,
- waktu proses,
- perubahan hasil tabel.

## 31. Perintah Penting

Membersihkan cache:

```bash
php artisan optimize:clear
```

Cache view:

```bash
php artisan view:cache
```

Melihat daftar route:

```bash
php artisan route:list
```

Melihat jadwal scheduler:

```bash
php artisan schedule:list
```

Generate tagihan otomatis paksa:

```bash
php artisan tagihan:generate-bulanan --force
```

Reset database dari nol:

```bash
php artisan migrate:fresh --seed
```

## 32. Troubleshooting

### Database tidak konek

Cek:

- MySQL Laragon sudah menyala.
- `DB_DATABASE=spp_al_jabbar`.
- database sudah dibuat.
- username/password benar.

Lalu jalankan:

```bash
php artisan optimize:clear
```

### APP_KEY kosong

Jalankan:

```bash
php artisan key:generate
```

### Perubahan `.env` tidak terbaca

Jalankan:

```bash
php artisan optimize:clear
```

### Halaman error setelah edit Blade

Jalankan:

```bash
php artisan view:cache
```

Jika masih error, bersihkan:

```bash
php artisan optimize:clear
```

### Midtrans gagal membuat transaksi

Cek:

- `MIDTRANS_SERVER_KEY` benar.
- `MIDTRANS_CLIENT_KEY` benar.
- key berasal dari environment Sandbox.
- `MIDTRANS_IS_PRODUCTION=false`.
- koneksi internet aktif.

Setelah mengubah key:

```bash
php artisan optimize:clear
```

### Tagihan otomatis tidak muncul

Cek:

1. Scheduler berjalan:

```bash
php artisan schedule:work
```

2. Pengaturan otomatis aktif:

```text
Admin TU > Pengaturan SPP > Otomatis & Diskon
```

3. Tahun ajaran aktif sudah diatur.
4. Biaya SPP tahun ajaran aktif sudah diatur.
5. Tanggal hari ini sudah mencapai tanggal tagihan keluar.

Jika ingin paksa:

```bash
php artisan tagihan:generate-bulanan --force
```

### Biaya SPP dianggap belum diatur

Cek:

- siswa sudah punya kelas,
- biaya SPP untuk kelas tersebut sudah ada,
- tahun ajaran aktif benar,
- jika tahun ajaran baru dibuat, tambahkan biaya SPP untuk tahun ajaran itu.

### Siswa tidak bisa login

Cek:

- username memakai NIS,
- akun siswa sudah terbuat,
- user terhubung ke siswa,
- reset password dari menu User Management jika perlu.

## 33. Checklist Setelah Instalasi

Pastikan semua ini sudah selesai:

- Laragon menyala.
- Database `spp_al_jabbar` ada.
- `.env` sudah benar.
- `composer install` sukses.
- `npm install` sukses.
- `php artisan key:generate` sukses.
- `php artisan migrate --seed` sukses.
- `php artisan serve` berjalan.
- `php artisan schedule:work` berjalan.
- Admin bisa login.
- Tahun ajaran aktif ada.
- Kelas ada.
- Biaya SPP ada.
- Siswa ada.
- Tagihan bisa dibuat.
- Pembayaran tunai/manual bisa dikonfirmasi.
- Laporan bisa dibuka.
- Sequential search menampilkan data diperiksa dan waktu proses.

## 34. Catatan Produksi

Untuk penggunaan sungguhan:

- ganti semua password default,
- jangan tampilkan akun login di halaman login,
- isi Midtrans production key jika sudah go-live,
- ubah `MIDTRANS_IS_PRODUCTION=true` hanya jika memakai key production,
- pastikan server publik memakai HTTPS,
- pastikan scheduler berjalan permanen di server,
- backup database secara berkala.
