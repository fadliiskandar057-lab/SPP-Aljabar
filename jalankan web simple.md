# Panduan Jalankan Web SPP Dari ZIP

Panduan ini dipakai setelah kamu download file:


WebSPP.zip


atau nama sejenis seperti:


Web spp.zip
Web spp.rar


## 1. Siapkan Aplikasi Wajib

Pastikan komputer sudah punya:

- Laragon Full
- Composer
- Node.js LTS
- Browser seperti Chrome

Jika belum ada, install dulu:

```text
Laragon: https://laragon.org/download
Composer: https://getcomposer.org/download
Node.js: https://nodejs.org
```

## 2. Ekstrak File ZIP

1. Klik kanan file `WebSPP.zip`.
2. Pilih **Extract All** atau **Extract Here**.
3. Setelah diekstrak, pastikan ada folder project, contoh:


D:\Web SPP


Di dalam folder harus ada file penting seperti:

artisan
composer.json
package.json
install.bat
install.ps1
.env.example


## 3. Jalankan Laragon

1. Buka Laragon.
2. Klik **Start All**.
3. Pastikan Apache/Nginx dan MySQL sudah jalan.

Jika MySQL belum jalan:


Laragon > Menu > MySQL > Start

## 4. Jalankan Installer Otomatis

Buka folder project hasil ekstrak, lalu double-click:

```text
install.bat
```

Installer akan otomatis:

- membuat file `.env` jika belum ada,
- menjalankan `composer install`,
- menjalankan `npm install`,
- generate `APP_KEY`,
- membuat database `spp_al_jabbar`,
- menjalankan migration,
- mengisi data contoh dari seeder,
- membersihkan cache Laravel,
- menjalankan web di `http://127.0.0.1:8000`.

Biarkan jendela installer tetap terbuka selama web digunakan.

## 5. Buka Web

Setelah installer selesai dan server Laravel berjalan, buka browser:


http://127.0.0.1:8000
```

## 6. Login Akun Demo

Gunakan akun berikut:


Admin TU
Username: admin
Password: password

Kepala Sekolah
Username: kepala
Password: password

Wali Kelas
Username: wali
Password: password

Siswa
Username: 26260001
Password: password


## 7. Jika Web Sudah Pernah Diinstall

Kalau dependency dan database sudah ada, tidak perlu install ulang.

Cukup buka terminal di folder project:

perintah
cd "D:\Web SPP"
php artisan serve


Lalu buka:


http://127.0.0.1:8000


Jika port `8000` bentrok:

perintah
php artisan serve --port=8001


Lalu buka:


http://127.0.0.1:8001


## 8. Jika Ingin Reset Database Dari Nol

Gunakan hanya kalau ingin menghapus semua data lama dan isi ulang data contoh.

Buka terminal di folder project:


.\install.bat -Fresh


Jika diminta konfirmasi, ketik:


RESET


## 9. Troubleshooting Cepat

### install.bat tidak bisa jalan

Klik kanan `install.bat`, lalu pilih **Run as administrator**.

### PHP tidak terbaca

Buka terminal dari Laragon:

```text
Laragon > Terminal
```

Lalu jalankan lagi:

```bash
php artisan serve
```

### Database gagal dibuat

Cek:

- Laragon sudah **Start All**.
- MySQL sudah jalan.
- Port `3306` tidak dipakai XAMPP atau MySQL lain.

### composer install gagal

Pastikan Composer sudah terinstall, lalu cek:

```bash
composer -V
```

### npm install gagal

Pastikan Node.js sudah terinstall, lalu cek:

```bash
node -v
npm -v
```

### phpMyAdmin tidak muncul

Buka:

```text
http://localhost/phpmyadmin
```

Jika tidak muncul atau `Forbidden`, download phpMyAdmin, ekstrak, rename folder menjadi:

```text
phpmyadmin
```

Lalu letakkan di:

```text
C:\laragon\etc\apps
```

Restart Laragon, lalu buka lagi phpMyAdmin.
