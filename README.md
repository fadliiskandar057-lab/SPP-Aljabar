# Sistem Pembayaran SPP MA Plus Taruna Teknik Al Jabbar

Aplikasi web pembayaran SPP berbasis Laravel untuk membantu pengelolaan tagihan, pembayaran, tunggakan, laporan, dan monitoring pembayaran siswa di MA Plus Taruna Teknik Al Jabbar.

### Judul Tugas Akhir (TA)
> **Implementasi Algoritma Sequential Search pada Pembayaran SPP Berbasis Web di MA Plus Taruna Teknik Al Jabbar**

---

## Daftar Isi
1. [Ringkasan Sistem](#ringkasan-sistem)
2. [Teknologi yang Digunakan](#teknologi-yang-digunakan)
3. [Fitur Utama](#fitur-utama)
4. [Arsitektur & Alur Kerja Sistem (Workflow)](#arsitektur--alur-kerja-sistem-workflow)
5. [Implementasi Algoritma Sequential Search](#implementasi-algoritma-sequential-search)
6. [Struktur Database](#struktur-database)
7. [Panduan Instalasi & Konfigurasi](#panduan-instalasi--konfigurasi)
8. [Scheduler & Otomatisasi](#scheduler--otomatisasi)
9. [Panduan Penggunaan Akun Demo](#panduan-penggunaan-akun-demo)
10. [Struktur Folder Utama](#struktur-folder-utama)
11. [Catatan Deployment Server](#catatan-deployment-server)

---

## Ringkasan Sistem

Sistem ini dirancang untuk memudahkan manajemen administrasi SPP di sekolah secara digital dan transparan. Sistem memiliki 4 role utama dengan wewenang yang berbeda:

*   **Admin TU (Tata Usaha)**: Mengelola data master siswa, kelas, tahun ajaran, tarif biaya SPP, mengenerate tagihan bulanan secara massal/otomatis, memvalidasi antrian pembayaran tunai, mencatat pembayaran manual/bukti foto, memproses pembayaran Midtrans di tempat, melihat rekapan laporan, mengelola pengguna, dan memonitor seluruh statistik keuangan.
*   **Siswa/Orang Tua**: Portal mandiri untuk memeriksa daftar tagihan bulanan (belum lunas, menunggu konfirmasi, lunas, gagal), melakukan pembayaran online secara real-time via Midtrans Snap (QRIS, Virtual Account, Credit Card), membuat invoice antrian tunai untuk dibawa ke TU, melihat riwayat transaksi, serta mengunduh kwitansi digital (PDF).
*   **Wali Kelas**: Hak akses semi-monitoring untuk memantau status pembayaran siswa khusus di kelas yang diampunya, memeriksa total tunggakan, melihat laporan kelas, dan menghubungi orang tua via WhatsApp API.
*   **Kepala Sekolah**: Akses monitoring *read-only* untuk memantau arus kas masuk (pemasukan bulanan/kelas), menganalisis tren grafik pembayaran, dan mengunduh laporan keuangan berformat PDF/Excel.

---

## Teknologi yang Digunakan

*   **Backend Core**: PHP 8.2+ dengan Framework Laravel 11.
*   **Database**: MySQL / MariaDB.
*   **Frontend**: Vanilla HTML5, CSS3, Javascript, dan template engine Blade.
*   **Styling & Icons**: Bootstrap v5.3.3 & Bootstrap Icons v1.11.3.
*   **Asset Bundler**: Vite.
*   **Gateway Pembayaran**: Midtrans PHP SDK (Snap API & Webhook API).
*   **Laporan & Dokumen**: DomPDF (Ekspor PDF) & Laravel Excel (Ekspor/Import Excel).
*   **Grafik Visual**: Chart.js.

---

## Fitur Utama

1.  **Multi-role Authentication & Authorization**: Pembagian akses ketat dengan Middleware Laravel.
2.  **Generate Tagihan Otomatis**: Generator tagihan terjadwal bulanan otomatis untuk seluruh siswa aktif.
3.  **Sistem Diskon / Bebas SPP**: Dukungan potongan biaya (misal: beasiswa/gratis) bagi siswa tertentu secara dinamis.
4.  **Dua Metode Pembayaran**:
    *   **Tunai / Manual (Di Tempat)**: Siswa mengajukan antrian tunai, atau TU langsung menginput transaksi manual beserta upload bukti foto.
    *   **Midtrans Online (Instant/Real-time)**: Integrasi Snap gateway pembayaran pihak ketiga (QRIS, VA Bank Transfer, Retail Outlet).
5.  **Pembayaran Tunggakan Kumulatif (Arrears)**: Admin dapat memproses pelunasan beberapa bulan tunggakan sekaligus secara kumulatif, baik via tunai maupun online (Midtrans).
6.  **Penyelesaian Otomatis Multi-invoice**: Jika pembayaran Midtrans kumulatif berhasil, sistem membagi transaksi tersebut menjadi invoice individual per bulan secara otomatis agar pelaporan keuangan tetap rapi.
7.  **Pembersihan Transaksi Kedaluwarsa**: Scheduler otomatis menghapus invoice pending online yang melewati batas waktu kedaluwarsa (30 menit) dan mengembalikan status tagihan ke belum lunas.
8.  **Ekspor PDF & Excel**: Cetak kwitansi per transaksi atau rekapitulasi bulanan secara instan.
9.  **WhatsApp Quick-link**: Tombol pintas untuk mengirim pesan penagihan/tunggakan langsung ke nomor WhatsApp orang tua siswa.
10. **Pencarian Sequential Search**: Implementasi algoritma pencarian langsung (Sequential) di backend untuk profiling data siswa, riwayat transaksi, dan laporan.

---

## Arsitektur & Alur Kerja Sistem (Workflow)

### 1. Alur Pembayaran Online Siswa (Midtrans Snap)
```
[Siswa] -> Pilih Tagihan -> Klik "Bayar Online" 
   |
   +--> [Sistem] -> Create Pembayaran (Pending) -> Request Snap Token ke Midtrans
           |
           +--> [Midtrans] -> Return Snap Token
                   |
                   +--> [Siswa] -> Tampil Modal Snap -> Bayar (QRIS/VA)
                           |
                           +--> [Midtrans Webhook] -> Kirim Status 'settlement' / 'success'
                                   |
                                   +--> [Sistem Webhook] -> Update Pembayaran -> Lunas -> Kirim Notifikasi
```

### 2. Alur Pembayaran Tunai (Antrian TU)
```
[Siswa] -> Pilih Tagihan -> Klik "Bayar Tunai"
   |
   +--> [Sistem] -> Create Pembayaran (Status: pending, Metode: tunai) 
           |      -> Set Status Tagihan: 'menunggu_konfirmasi'
           |
           v
[Admin TU] -> Masuk menu "Antrian Pembayaran Tunai"
   |
   +--> Terima Fisik Uang -> Klik "Konfirmasi Lunas"
           |
           +--> [Sistem] -> Update Pembayaran: success, paid_at
                         -> Set Status Tagihan: 'lunas'
```

### 3. Alur Pembayaran Tunggakan Kumulatif Admin TU (Arrears)
Admin TU dapat menekan "bubble" bulan tunggakan di dashboard tunggakan siswa. Ini memicu modal konfirmasi pembayaran kumulatif sampai bulan yang dipilih (misalnya tunggakan 3 bulan):
*   **Metode Tunai / Manual**:
    1. Admin mengisi tanggal transaksi dan bukti upload foto (opsional).
    2. Sistem membuat `Pembayaran` manual individual untuk masing-masing bulan tunggakan.
    3. Status tagihan-tagihan tersebut diubah menjadi `lunas`.
*   **Metode Midtrans Online**:
    1. Admin memicu pembayaran online. Sistem menghitung total nominal kumulatif dan membuat satu record master `Pembayaran` (metode `midtrans`, status `pending`).
    2. Status seluruh tagihan terkait diubah menjadi `menunggu_konfirmasi`.
    3. Sistem mengarahkan admin ke halaman checkout Midtrans Snap. Siswa/orang tua di tempat dapat memindai QRIS atau menggunakan Virtual Account untuk membayar.
    4. Setelah pembayaran sukses dideteksi oleh Webhook / Callback, sistem secara otomatis mengeksekusi metode `resolveMultiBillPayment()`:
        *   Mencatat `Pembayaran` Midtrans individual untuk setiap bulan sebelumnya yang tergabung dalam transaksi arrears.
        *   Mengubah status tagihan sebelumnya menjadi `lunas`.
        *   Menyesuaikan nominal record master pembayaran utama agar sesuai dengan tarif bulanannya (sehingga rekap pemasukan per bulan tetap presisi).
    5. Jika transaksi kedaluwarsa atau dibatalkan, metode `revertPrecedingBills()` akan mengembalikan seluruh tagihan terkait ke status `belum_lunas`.

---

## Implementasi Algoritma Sequential Search

Algoritma **Sequential Search** (Pencarian Berurutan/Linear Search) diterapkan pada data collection di tingkat aplikasi untuk membandingkan keyword yang dimasukkan oleh pengguna dengan data satu per satu dari awal sampai akhir.

Implementasi utama berada pada berkas:
[`app/Services/SequentialSearchService.php`](file:///d:/Web%20mutek/app/Services/SequentialSearchService.php)

### Cara Kerja Algoritma pada Sistem:
1.  Controller menarik data dari database menggunakan Eloquent ORM (misalnya mengambil semua record siswa/pembayaran aktif).
2.  Data dilewatkan ke method pencarian di `SequentialSearchService` dalam bentuk Laravel `Collection`.
3.  Waktu awal pencarian dicatat menggunakan `microtime(true)`.
4.  Looping `foreach` berjalan untuk memeriksa setiap item:
    *   Sistem menggabungkan string pencarian (haystack) seperti NIS, nama, email, kelas, status, atau kode invoice, lalu mengubahnya menjadi huruf kecil (`mb_strtolower`).
    *   Sistem membandingkan keyword (yang juga sudah di-normalize) dengan haystack menggunakan `str_contains()`.
    *   Setiap pemeriksaan menaikkan nilai counter `$checked`.
    *   Jika cocok, item dimasukkan ke dalam sub-koleksi hasil.
5.  Waktu akhir dicatat, dan sistem mengembalikan array berisi:
    *   `results`: Koleksi data hasil pencarian.
    *   `checked`: Total data yang diperiksa (ukuran array/koleksi asli).
    *   `duration_ms`: Durasi waktu eksekusi pencarian dalam milidetik.

Visualisasi keluaran pencarian pada antarmuka admin menyertakan data profiling ini guna memperjelas aspek analisis algoritma.

---

## Struktur Database

Sistem ini menggunakan 6 tabel utama:

1.  `users`: Mengelola akun pengguna (Admin TU, Wali Kelas, Kepala Sekolah, Siswa).
2.  `kelas`: Mengelola data kelas (misal: X-A, XI-B) beserta wali kelasnya.
3.  `siswa`: Mengelola data profil siswa (nama, NIS, data orang tua, nomor HP).
4.  `tahun_ajaran`: Mengelola tahun ajaran aktif/nonaktif.
5.  `biaya_spp`: Mengatur tarif biaya SPP dasar per kelas dan tahun ajaran.
6.  `tagihan`: Mengelola status tagihan bulanan siswa (`belum_lunas`, `menunggu_konfirmasi`, `lunas`, `gagal`).
7.  `pembayaran`: Log transaksi pembayaran (`pending`, `settlement`, `success`, `failed`, `expired`, `cancelled`) lengkap dengan relasi tagihan, metode, nominal, `midtrans_order_id`, dan bukti fisik foto manual.
8.  `tagihan_exemptions`: Mencatat dispensasi (diskon/gratis) siswa tertentu.
9.  `web_notifications`: Sistem notifikasi internal aplikasi.

---

## Panduan Instalasi & Konfigurasi

Pastikan komputer Anda sudah terinstal **PHP 8.2+**, **Composer**, **Node.js & NPM**, serta server database **MySQL/MariaDB** (disarankan menggunakan Laragon atau XAMPP).

### Langkah-Langkah Instalasi:

1.  **Clone / Unduh Repository**:
    Ekstrak project ke folder web server Anda (misal `C:\laragon\www\spp-al-jabbar`).

2.  **Masuk ke Direktori Project**:
    ```bash
    cd spp-al-jabbar
    ```

3.  **Install PHP Dependency**:
    ```bash
    composer install
    ```

4.  **Install Frontend Dependency**:
    ```bash
    npm install
    ```

5.  **Salin File Konfigurasi `.env`**:
    Salin file template `.env.example` menjadi `.env`.
    ```bash
    cp .env.example .env
    ```
    *(Gunakan `copy .env.example .env` di Windows Command Prompt).*

6.  **Buat Database Baru**:
    Melalui phpMyAdmin atau client SQL lainnya, buat database kosong bernama `spp_al_jabbar`.

7.  **Konfigurasi File `.env`**:
    Sesuaikan kredensial database dan API key Midtrans Anda:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=spp_al_jabbar
    DB_USERNAME=root
    DB_PASSWORD=

    MIDTRANS_SERVER_KEY=Kunci_Server_Sandbox_Anda
    MIDTRANS_CLIENT_KEY=Kunci_Client_Sandbox_Anda
    MIDTRANS_IS_PRODUCTION=false
    ```

8.  **Generate Application Key**:
    ```bash
    php artisan key:generate
    ```

9.  **Jalankan Migrasi & Database Seeder**:
    ```bash
    php artisan migrate --seed
    ```
    *Gunakan `php artisan migrate:fresh --seed` jika Anda ingin mengosongkan database terlebih dahulu dan mengisinya dengan data uji coba segar.*

10. **Jalankan Aplikasi**:
    Jalankan server lokal PHP:
    ```bash
    php artisan serve
    ```
    Jalankan asset compiler di terminal terpisah untuk styling halaman:
    ```bash
    npm run dev
    ```
    Aplikasi dapat diakses melalui browser pada alamat: `http://127.0.0.1:8000`.

---

## Scheduler & Otomatisasi

Untuk mendukung operasi otomatis secara terus-menerus (generate tagihan bulanan dan pembersihan transaksi kedaluwarsa), jalankan scheduler Laravel:

*   **Menjalankan scheduler lokal**:
    ```bash
    php artisan schedule:work
    ```
*   **Menjalankan queue worker** (jika menggunakan `QUEUE_CONNECTION=database` untuk memproses notifikasi/proses latar belakang):
    ```bash
    php artisan queue:work
    ```

*   **Menjalankan manual trigger generator tagihan**:
    Jika ingin men-generate tagihan bulanan tanpa menunggu tanggal penjadwalan otomatis:
    ```bash
    php artisan tagihan:generate-bulanan
    ```

---

## Panduan Penggunaan Akun Demo

Gunakan akun-akun berikut untuk masuk dan menguji sistem setelah melakukan seeding:

| Role Pengguna | Username | Password | Deskripsi Fitur Utama |
|---|---|---|---|
| **Admin TU** | `admin` | `password` | Mengelola data spp, mengonfirmasi antrian tunai, dan arrears. |
| **Kepala Sekolah**| `kepala` | `password` | Cetak rekap PDF, melihat grafik trend arus kas masuk. |
| **Wali Kelas** | `wali` | `password` | Memantau siswa kelas X-A, kirim pesan penagihan WA. |
| **Siswa / Orang Tua**| `26260001` | `password` | Membayar tagihan online via Midtrans, cetak kwitansi. |

*NIS Siswa demo berurutan dari `26260001` hingga `26260010`. Password semuanya adalah `password`.*

---

## Struktur Folder Utama

```text
app/
  ├── Http/Controllers/     # Controller untuk logika antarmuka dan request
  ├── Models/               # Representasi data tabel (Siswa, Tagihan, Pembayaran, dll)
  └── Services/             # Layanan eksternal (Midtrans, Notifikasi, Sequential Search)
config/                     # File konfigurasi Laravel (services, database, dll)
database/
  ├── migrations/           # Definisi struktur tabel database
  └── seeders/              # Data awal/demo untuk pengujian sistem
resources/
  └── views/                # Halaman antarmuka pengguna (.blade.php)
routes/
  ├── web.php               # Kumpulan rute URL aplikasi web
  └── console.php           # Kumpulan perintah scheduler / CLI artisan
```

---

## Catatan Deployment Server

Saat memindahkan aplikasi ini ke server produksi (hosting/VPS):
1.  Ubah variabel `.env`: `APP_ENV=production` dan `APP_DEBUG=false`.
2.  Gunakan kredensial server key Midtrans Production dan atur `MIDTRANS_IS_PRODUCTION=true`.
3.  Jalankan instalasi dependency production:
    ```bash
    composer install --no-dev --optimize-autoloader
    ```
4.  Build asset frontend final:
    ```bash
    npm run build
    ```
5.  Pastikan root direktori web server (Apache/Nginx) mengarah ke sub-folder `/public` dari project ini, bukan root folder utama.
6.  Atur Cron Job di server Anda untuk memicu scheduler Laravel setiap menit:
    ```text
    * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
    ```
