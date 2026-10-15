# Sistem Pembayaran SPP MA Plus Taruna Teknik Al Jabbar

Aplikasi web untuk mengelola data siswa, tagihan SPP, pembayaran, tunggakan, dan laporan sekolah. Siswa atau orang tua memeriksa tagihan melalui akun siswa. Pengelolaan data menjadi tugas Admin TU, sedangkan penerimaan pembayaran menjadi tugas Bendahara.

**Judul Tugas Akhir:** Implementasi Algoritma Sequential Search pada Pembayaran SPP Berbasis Web di MA Plus Taruna Teknik Al Jabbar.

README ini mengikuti route, controller, service, migration, seeder, dan skrip proyek. Beberapa langkah lama di [INSTALL.md](INSTALL.md) masih menyebut Admin TU untuk pekerjaan keuangan; gunakan pembagian peran di README ini sebagai acuan.

## Daftar isi

- [Teknologi](#teknologi)
- [Peran dan fitur](#peran-dan-fitur)
- [Instalasi lokal](#instalasi-lokal)
- [Konfigurasi layanan](#konfigurasi-layanan)
- [Akun demo](#akun-demo)
- [Persiapan data oleh Admin TU](#persiapan-data-oleh-admin-tu)
- [Panduan Bendahara](#panduan-bendahara)
- [Panduan siswa dan orang tua](#panduan-siswa-dan-orang-tua)
- [Status dan batas pelunasan](#status-dan-batas-pelunasan)
- [Laporan, akun, dan notifikasi](#laporan-akun-dan-notifikasi)
- [Scheduler dan perawatan](#scheduler-dan-perawatan)
- [Deployment](#deployment)
- [Sequential Search](#sequential-search)
- [Struktur proyek dan database](#struktur-proyek-dan-database)
- [Pengujian](#pengujian)
- [Pemecahan masalah](#pemecahan-masalah)
- [Batasan aplikasi](#batasan-aplikasi)

## Teknologi

| Bagian | Teknologi dalam proyek |
| --- | --- |
| Backend | PHP `^8.4`, Laravel `^12.0` |
| Database | MySQL; konfigurasi lokal juga ditujukan untuk MariaDB |
| Antarmuka | Blade, HTML, CSS, JavaScript |
| Tampilan | Bootstrap 5.3.3 dan Bootstrap Icons 1.11.3 melalui CDN |
| Grafik | Chart.js melalui CDN |
| Pembayaran | Midtrans PHP SDK dan Snap |
| Dokumen | Laravel DomPDF dan Laravel Excel |
| Notifikasi browser | Web Push dengan kunci VAPID |
| Reset password | Laravel Mail atau Brevo API jika dikonfigurasi |
| Deployment yang disediakan | Docker PHP 8.4 Apache dan Blueprint Render |

`package.json` masih menyediakan dependency Vite dan `npm run dev`, tetapi layout saat ini memuat aset melalui CDN dan kode di Blade. **`npm run build` hanya mencetak `No assets to build`**, bukan membuat bundle. Server Vite bukan syarat membuka halaman aplikasi saat ini.

## Peran dan fitur

Ada **lima peran**, sesuai [routes/web.php](routes/web.php).

| Peran | Menu dan tugas utama |
| --- | --- |
| **Admin TU / Admin Manajemen** (`admin_tu`) | Data Siswa, Pengaturan SPP, User Management, Aktivitas Manajemen. Mengatur kelas, tahun ajaran, tarif, jadwal, diskon, dan akun. |
| **Bendahara** (`bendahara`) | Tunggakan Siswa, Antrian Tunai, Transaksi Pembayaran, Laporan Bulanan, Aktivitas Manajemen. Menyiapkan tunggakan dan menerima/mengonfirmasi pembayaran. |
| **Siswa / Orang Tua** (`siswa`) | Tagihan Saya, Riwayat Pembayaran, Profil. Data mengikuti siswa yang terhubung ke akun. Orang tua menggunakan akun siswa, bukan peran terpisah. |
| **Wali Kelas** (`wali_kelas`) | Dashboard, Siswa Kelas, Tagihan Kelas, Riwayat Pembayaran, Tunggakan, Laporan Kelas, Kontak Orang Tua. Data dibatasi sesuai kelas akun. |
| **Kepala Sekolah** (`kepala_sekolah`) | Laporan Per Bulan, Laporan Per Kelas, Grafik Pemasukan; route Aktivitas Manajemen juga mengizinkan peran ini. Memantau keuangan. |

Semua pengguna yang masuk dapat mengakses Profil Akun dan Notifikasi. **Admin TU tidak memiliki akses route transaksi Bendahara**, dan Bendahara tidak mengelola akun pengguna.

Fitur utama mencakup:

- Tambah/ubah/hapus siswa, pencarian, impor/ekspor, dan perubahan/hapus massal.
- Tarif SPP per kelas atau umum, jadwal tagihan, gratis dan diskon nominal/persen.
- Pembayaran Midtrans, pengajuan tunai oleh siswa, dan pencatatan manual Bendahara.
- Pelunasan beberapa tunggakan sekaligus dan pembayaran periode mendatang.
- Keterangan **Lunas sampai** pada tampilan Bendahara dan siswa.
- Invoice web/PDF, laporan PDF/Excel, dan koreksi transaksi sesuai ketentuan aplikasi.
- Tautan WhatsApp dengan pesan tunggakan. Pengguna tetap mengirim melalui WhatsApp; aplikasi tidak mengirim otomatis lewat WhatsApp API.
- Notifikasi internal/push, profil akun, perubahan/reset password, serta log aktivitas tertentu.
- Sequential Search melalui service backend.

## Instalasi lokal

### Kebutuhan

Siapkan PHP 8.4 yang dapat dipanggil dari terminal, Composer, dan MySQL/MariaDB. Ekstensi yang secara eksplisit diminta `composer.json` adalah `gd`, `pdo_mysql`, dan `zip`; penuhi juga ekstensi yang diminta Laravel/dependency saat Composer berjalan. Laragon/XAMPP dapat dipakai jika versi PHP sesuai.

Node.js/npm diperlukan oleh **installer Windows**, karena installer menjalankan `npm install`. Tampilan saat ini tidak memerlukan server Vite.

```powershell
php -v
php -m
composer --version
node --version
npm --version
```

### Installer Windows

1. Unduh/clone repository dan buka folder proyek.
2. Nyalakan MySQL/MariaDB.
3. Jalankan `install.bat`.
4. Pilih **1** untuk install/update tanpa menghapus database dan langsung menjalankan aplikasi, atau **2** untuk install/update saja.
5. Periksa `.env`, terutama database dan kunci layanan.

Pilihan **3** menghapus seluruh tabel dan mengisi demo; gunakan hanya untuk database uji. Pilihan **4** membersihkan cache. Installer dapat mengisi demo ketika pemeriksaannya menganggap data demo belum tersedia; untuk database sekolah aktif, gunakan prosedur manual yang terkontrol.

Setelah terpasang, jalankan **`start.bat`**. Skrip menjalankan server `http://127.0.0.1:8000`, scheduler, dan mencoba tunnel jika ngrok tersedia. Tekan `Ctrl+C` untuk berhenti. Jika menjalankan `start.ps1` langsung, gunakan `-Tunnel` untuk tunnel.

### Instalasi manual

```powershell
git clone https://github.com/fadliiskandar057-lab/SPP-Aljabar.git
cd SPP-Aljabar
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Buat database kosong `spp_al_jabbar`, lalu sesuaikan `.env`:

```env
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spp_al_jabbar
DB_USERNAME=root
DB_PASSWORD=
```

Untuk **demo pada database kosong**:

```powershell
php artisan migrate --seed
php artisan serve
```

Buka `http://127.0.0.1:8000`. Pada terminal kedua di folder sama:

```powershell
php artisan schedule:work
```

Untuk instalasi tanpa demo, gunakan `php artisan migrate` tanpa `--seed`; akun awal perlu disediakan melalui proses administrasi yang dikelola pengembang. Seeder berisi data contoh, bukan hanya akun administrator.

### Update tanpa menghapus data

Cadangkan database dan foto bukti sebelum mengambil kode terbaru, kemudian:

```powershell
composer install
php artisan migrate
php artisan optimize:clear
php artisan view:cache
```

Jangan menjalankan `migrate:fresh` pada database aktif. Jangan mengulang seeder sebagai update rutin: sebagian data memakai `create`, sehingga pengulangan dapat menyebabkan duplikasi/benturan.

## Konfigurasi layanan

Template ada di [.env.example](.env.example). Simpan rahasia di `.env` atau environment server, jangan di Git. Setelah mengubah konfigurasi lokal, jalankan `php artisan optimize:clear`.

### Midtrans

```env
MIDTRANS_SERVER_KEY=KUNCI_SERVER_ANDA
MIDTRANS_CLIENT_KEY=KUNCI_CLIENT_ANDA
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_PENDING_TIMEOUT_MINUTES=30
```

Gunakan Sandbox untuk pengujian. Untuk transaksi nyata, gunakan kunci Production dan `MIDTRANS_IS_PRODUCTION=true`. Metode yang muncul di Snap mengikuti konfigurasi akun Midtrans.

Endpoint notifikasi pembayaran:

```text
POST https://domain-aplikasi/midtrans/webhook
```

Arahkan konfigurasi notifikasi Midtrans ke URL tersebut. Webhook memeriksa signature. Endpoint finish setelah Snap juga mengambil status dari server Midtrans; tampilan sukses di browser saja bukan konfirmasi pembayaran.

Pada localhost, webhook membutuhkan URL publik/tunnel. `start.bat` mencoba menyiapkannya bila ngrok tersedia. Verifikasi finish dapat berjalan dari localhost dengan internet, tetapi bergantung pada pengguna menyelesaikan alur browser. Webhook publik diperlukan untuk konfirmasi yang tidak bergantung pada browser pengguna.

`MIDTRANS_WEBHOOK_URL` ada di template dan digunakan skrip start untuk mencatat URL tunnel. Mengisinya tidak otomatis mengubah dashboard Midtrans. Konfigurasi utama ada di [config/services.php](config/services.php).

### Email dan push

Default `MAIL_MAILER=log` mencatat email ke log, **tidak mengirim ke kotak masuk**. Konfigurasikan Laravel Mail atau isi Brevo untuk reset password:

```env
BREVO_API_KEY=KUNCI_API_ANDA
BREVO_SENDER_EMAIL=alamat-pengirim-anda
BREVO_SENDER_NAME="SPP Al Jabbar"
```

Pastikan akun penyedia/pengirim dikonfigurasi dan akun pengguna memiliki email. Kode memakai Brevo untuk reset password bila API key tersedia.

Push memakai `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, dan `VAPID_SUBJECT`. Isi pasangan kunci valid; pengguna membutuhkan dukungan browser, izin, dan subscription. Notifikasi internal berbeda dari push; VAPID kosong membuat layanan push tidak mengirim.

## Akun demo

Akun berikut dibuat oleh [DatabaseSeeder](database/seeders/DatabaseSeeder.php):

| Peran | Username | Password awal |
| --- | --- | --- |
| Admin TU / Manajemen | `admin` | `password` |
| Bendahara | `bendahara` | `password` |
| Kepala Sekolah | `kepala` | `password` |
| Wali Kelas X-A | `wali` | `password` |
| Siswa | `26260001` sampai `26260010` | `password` |

Login menerima username atau email akun. Seeder membuat tahun ajaran `2025/2026`, tujuh kelas, sepuluh siswa, dan tagihan Juli–September 2025. Beberapa tagihan langsung lunas sebagai contoh tanpa record pembayaran; jumlah tagihan lunas demo tidak harus sama dengan jumlah invoice.

Sebelum penggunaan sekolah, ganti password contoh, lengkapi email/NIP, dan periksa relasi akun siswa/kelas. Akun siswa baru melalui pengelolaan siswa/impor memakai NIS dan password awal `password`.

## Persiapan data oleh Admin TU

1. Di **Pengaturan SPP**, buat tahun ajaran dan tentukan yang aktif.
2. Tambahkan kelas.
3. Atur biaya SPP per kelas/tahun ajaran. Tarif kelas diprioritaskan dibanding tarif umum. Jika tarif tahun ajaran tidak ditemukan, kode mencoba tarif terbaru yang cocok; tetap isi tarif tahun ajaran berjalan.
4. Tambahkan siswa dan kontak orang tua di **Data Siswa**.
5. Di **User Management**, siapkan akun Bendahara, Kepala Sekolah, Wali Kelas, dan relasi akun siswa.
6. Atur tanggal keluar/jatuh tempo dan aktifkan tagihan otomatis.
7. Masukkan gratis/diskon sebelum generator membuat tagihan.
8. Minta Bendahara menyiapkan tunggakan awal berdasarkan catatan terverifikasi.

### Impor siswa

Gunakan fitur impor Data Siswa. Contoh nama kolom dan isian:

```csv
nis,nama,email,kelas,nama_orang_tua,no_hp_orang_tua,alamat,status
26260015,Budi Santoso,budi@example.com,X-A,Orang Tua Budi,6281234567890,Jalan Pendidikan,aktif
```

Wajib: `nis`, `nama`, `kelas`, `nama_orang_tua`, `no_hp_orang_tua`. Email/alamat opsional. Simpan NIS/telepon sebagai teks agar nol awal tidak hilang. Gunakan format file yang diterima form.

NIS sama memperbarui siswa lama. Kelas baru dibuat importer. Status didukung: `aktif`, `lulus`, `keluar`; kosong/tidak dikenal menjadi `aktif`. Baris tanpa isian wajib dilewati dan dicatat sebagai kesalahan. Importer menyelaraskan akun tanpa mengganti password akun yang sudah ditemukan.

### Gratis dan diskon

Isi tahun ajaran, bulan/tahun, cakupan, dan manfaat. Prioritas generator: **siswa**, lalu **kelas**, lalu **semua siswa**. Generator memilih satu aturan cocok, bukan menumpuk diskon. Nominal akhir nol menghasilkan `gratis`.

Aturan berlaku pada tagihan baru melalui generator. Tagihan yang sudah ada dilewati, tidak dihitung ulang. **Set Terakhir Bayar dan Bayar Masa Depan saat ini memakai tarif dasar**, bukan perhitungan diskon generator; periksa nominal sebelum konfirmasi.

## Panduan Bendahara

### Set Terakhir Bayar

1. Buka **Tunggakan Siswa > Set Terakhir Bayar**.
2. Pilih kelas jika perlu, siswa atau semua siswa aktif, dan tahun ajaran.
3. Isi bulan/tahun pembayaran terakhir terverifikasi dan hari jatuh tempo.
4. Klik **Siapkan Tagihan**, lalu periksa hasilnya.

Contoh: terakhir bayar Februari 2026, batas penerbitan sudah Juni 2026. Sistem menyiapkan Maret–Juni yang belum tercatat. Sebelum tanggal keluar bulan berjalan, batas mengikuti bulan sebelumnya.

Fitur juga menandai **tagihan yang sudah tercatat** sampai bulan terakhir bayar sebagai lunas. Ini bukan penerimaan uang baru, tidak membuat invoice historis, dan tidak membuat semua bulan historis yang belum ada.

### Melunasi tunggakan

1. Klik bubble bulan yang menjadi batas pembayaran pada baris siswa.
2. Periksa jumlah bulan/total; seluruh tagihan belum selesai sampai batas ikut dihitung.
3. Pilih **Tunai / Manual** atau **Midtrans Online**.
4. Untuk tunai, isi tanggal dan foto bukti opsional, lalu konfirmasi setelah uang diterima.
5. Untuk Midtrans, buat transaksi, lanjutkan ke Snap, dan tunggu verifikasi.

Manual mencatat pembayaran per tagihan. Midtrans kumulatif memakai transaksi gabungan, lalu layanan penyelesaian mengalokasikannya ke tagihan terkait setelah berhasil.

### Bayar sampai periode mendatang

1. Buka **Tunggakan Siswa > Bayar Masa Depan**.
2. Pilih siswa aktif, tahun ajaran, bulan/tahun tujuan: bulan ini atau sesudahnya.
3. Klik **Proses Pelunasan**.
4. Sistem menyiapkan tagihan dari bulan berjalan sampai tujuan yang belum ada, lalu membuka **Metode Pembayaran**.
5. Periksa rincian/total, pilih **Konfirmasi Bayar Tunai** atau **Bayar melalui Midtrans**.

**Proses Pelunasan belum menandai tagihan lunas.** Pelunasan baru melalui konfirmasi tunai atau hasil Midtrans terverifikasi. Tunggakan sebelumnya ikut dalam total. Jika kembali sebelum memilih metode, tagihan yang disiapkan tetap tersimpan. Jika semua tagihan sampai tujuan sudah lunas/gratis, muncul pesan tidak ada tagihan yang perlu dibayar.

### Antrian tunai dan koreksi

Di **Antrian Tunai**, cari invoice pengajuan siswa, cocokkan nominal, terima uang, lalu konfirmasi. Pengajuan siswa belum melunasi tagihan.

Di **Transaksi Pembayaran**, catat manual, cari riwayat, buka invoice, atau batalkan transaksi yang diizinkan. Bubble hijau pada Tunggakan Siswa menyediakan buka ulang dengan konfirmasi. Periksa dampak transaksi terkait; koreksi mengikuti pemeriksaan metode/status pada controller.

## Panduan siswa dan orang tua

1. Login memakai NIS atau email akun dan password.
2. Buka **Tagihan Saya**; periksa periode, nominal, status, dan pelunasan.
3. Untuk online, pilih Midtrans, buka Snap, dan ikuti pembayaran.
4. Untuk tunai, buat invoice lalu bayar kepada Bendahara. Tunggu konfirmasi.
5. Buka **Riwayat Pembayaran** untuk transaksi dan invoice/PDF sesuai akses.
6. Buka **Profil** untuk data siswa, ringkasan tagihan, dan pembayaran terakhir.

Invoice pending bukan bukti uang diterima. Pastikan status terverifikasi. Pembayaran online membutuhkan internet.

## Status dan batas pelunasan

| Status tagihan | Makna |
| --- | --- |
| `belum_lunas` | Belum selesai. |
| `menunggu_konfirmasi` | Diproses/menunggu konfirmasi. |
| `lunas` | Ditandai selesai pada data sistem. |
| `gratis` | Tidak perlu dibayar sesuai manfaat/nominal. |
| `gagal` | Percobaan gagal; periksa dan ulangi sesuai pilihan tersedia. |

Status transaksi berbeda: `pending`, `settlement`, `success`, `failed`, `expired`, `cancelled`. Pembayaran berhasil dicatat `settlement`/`success`; gagal/kedaluwarsa bukan lunas.

Ringkasan tersedia di **Bendahara > Tunggakan Siswa** serta halaman siswa **Tagihan Saya**, **Riwayat Pembayaran**, dan **Profil**. Perhitungan memakai semua tagihan siswa, bukan hanya halaman tabel terlihat.

- **Lunas sampai:** bulan selesai berurutan sejak awal data. `lunas` dan `gratis` dihitung selesai.
- Bulan belum selesai atau celah bulan yang belum tercatat menghentikan batas.
- **Periode lunas paling akhir:** muncul jika ada bulan selesai setelah urutan terputus.
- **Tagihan belum lunas paling awal:** bulan tercatat pertama yang belum selesai.

Contoh Januari lunas, Februari belum, Maret lunas: **Lunas sampai Januari**, **Periode lunas paling akhir Maret**, dan **Tagihan belum lunas paling awal Februari**. Semua bulan sampai Maret tidak dianggap selesai.

Ringkasan berdasarkan status tagihan, termasuk baseline/data demo, bukan hanya invoice. Bulan sebelum awal data tidak diasumsikan lunas; riwayat yang belum diinput tidak dapat dibuktikan oleh ringkasan.

## Laporan, akun, dan notifikasi

**Bendahara:** gunakan Laporan Bulanan, filter, ringkasan, ekspor PDF/Excel. Untuk PDF laporan, Admin TU perlu menetapkan akun Kepala Sekolah sebagai penandatangan di User Management. Lengkapi nama/NIP.

**Kepala Sekolah:** Laporan Per Bulan, Per Kelas, Grafik Pemasukan. Route ekspor PDF tersedia; ekspor Excel berada pada kelompok route Bendahara.

**Wali Kelas:** monitoring sesuai kelas akun dan Kontak Orang Tua/WhatsApp. Bedakan tagihan selesai dan pemasukan: baseline/gratis tidak otomatis memiliki invoice penerimaan uang.

- **Profil Akun:** ubah nama/email; NIP diperlukan saat pembaruan profil Kepala Sekolah/Wali Kelas sesuai validasi. Ganti password memakai password saat ini dan konfirmasi password baru minimal delapan karakter.
- **User Management:** Admin TU mengatur akun, relasi, penandatangan, dan perubahan/reset password.
- **Lupa password:** masukkan username/email. Akun tanpa email perlu dilengkapi admin; pengiriman email harus aktif.
- **Notifikasi:** lihat pemberitahuan dan tandai dibaca. Push perlu konfigurasi/izin terpisah.
- **Aktivitas Manajemen:** mencatat aksi yang memanggil `ActivityLogger`, bukan seluruh perubahan database otomatis.

## Scheduler dan perawatan

Jadwal pada [routes/console.php](routes/console.php):

| Pekerjaan | Jadwal |
| --- | --- |
| Worker antrean sampai kosong | Setiap menit |
| Cleaner Midtrans pending | Setiap menit |
| Generator tagihan bulan berjalan | Harian pukul 01.00, zona waktu aplikasi |

Generator memeriksa otomatisasi aktif, tanggal keluar, tahun ajaran aktif, siswa aktif, dan tarif. Tagihan yang sudah ada dilewati. Generator tidak mengisi semua bulan historis terlewat.

```powershell
php artisan schedule:list
php artisan schedule:work
php artisan tagihan:generate-bulanan
php artisan tagihan:generate-bulanan --force
php artisan queue:work
php artisan optimize:clear
php artisan route:list
```

`--force` melewati tanggal keluar, **tidak mengaktifkan pengaturan nonaktif**. Worker terpisah dapat digunakan; scheduler juga menjalankan `queue:work --stop-when-empty`. `QUEUE_CONNECTION=sync` menjalankan pekerjaan langsung.

Cleaner mengikuti `MIDTRANS_PENDING_TIMEOUT_MINUTES` (default 30, minimum 1), **menghapus pembayaran pending** yang melewati batas, dan mengembalikan tagihan jika tidak dilindungi transaksi aktif/berhasil. Record tidak selalu tinggal sebagai `expired`. Cleaner juga dipanggil saat membuka beberapa halaman.

Cadangkan database, `.env` secara aman, dan foto `public/payment-proofs`. Backup database saja tidak mencakup foto.

## Deployment

### Hosting/VPS

1. Siapkan PHP/dependency, database, HTTPS, dan jaringan layanan.
2. Arahkan document root ke **`public`**.
3. Isi `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, APP_KEY valid, database, dan layanan.
4. Pertahankan APP_KEY yang sama pada instalasi tersebut.
5. Jalankan `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, dan cache konfigurasi/route/view sesuai kebutuhan.
6. Pastikan `storage`/`bootstrap/cache` dapat ditulis, bukti persisten, scheduler/worker aktif.
7. Gunakan web server produksi, bukan `artisan serve`. Konfigurasikan webhook dan uji pembayaran/reset password/ekspor.

Contoh cron:

```cron
* * * * * cd /path/to/SPP-Aljabar && php artisan schedule:run >> /dev/null 2>&1
```

### Docker/Render

[Dockerfile](Dockerfile) memakai PHP 8.4 Apache. [docker/start-container.sh](docker/start-container.sh) menjalankan migration/cache lalu Apache, **tanpa seeder/scheduler/worker permanen**. Database baru tidak otomatis memiliki akun demo.

[render.yaml](render.yaml) menyediakan Blueprint `spp-al-jabbar`, runtime Docker, plan `free`, koneksi MySQL port 4000, dan CA TLS.

1. Hubungkan repository melalui Blueprint Render.
2. Isi variabel `sync: false`: URL, database, serta kunci layanan yang dipakai.
3. Untuk TiDB, gunakan koneksi akun database Anda; CA pada Blueprint adalah `/etc/ssl/certs/ca-certificates.crt`.
4. Pastikan APP_KEY menggunakan format valid Laravel dan dipertahankan.
5. Isi `MIDTRANS_IS_PRODUCTION` eksplisit; Blueprint belum mengisinya sehingga default kode `false`.
6. Sediakan scheduler di luar web container dan storage persisten bila diperlukan.
7. Impor backup terverifikasi untuk pemindahan data; jangan mencampur demo dengan data aktif.

Blueprint memakai `QUEUE_CONNECTION=sync` dan variabel Brevo/VAPID. Periksa kemampuan/ketentuan pada akun hosting: label `free` bukan jaminan selalu aktif atau storage persisten.

## Sequential Search

[SequentialSearchService](app/Services/SequentialSearchService.php) memiliki `searchStudents`, `searchPayments`, `searchBills`.

1. Controller mengambil collection melalui Eloquent.
2. Keyword dibersihkan/diubah menjadi huruf kecil.
3. `foreach` memeriksa setiap item dan membentuk teks kolom relevan.
4. `str_contains` menentukan kecocokan.
5. Hasil berupa `results`, `checked`, `duration_ms`.

Siswa: NIS/nama/email/kelas. Pembayaran: invoice, siswa, periode/tanggal, metode/status. Tagihan: siswa, kelas, periode, nominal/status.

Jumlah pemeriksaan linear (`O(n)`) terhadap item yang diberikan. Durasi adalah waktu service, bukan seluruh query/pemuatan halaman. Keyword kosong tetap memeriksa semua item. Filter JavaScript browser berbeda dari backend; tidak semua kotak pencarian memakai service. Tidak ada menu terpisah Pencarian Sequential pada navigasi saat ini.

## Struktur proyek dan database

```text
app/Http/Controllers/    Halaman, validasi, aksi pengguna
app/Models/              Model data
app/Services/            Generator, Midtrans, pelunasan, pencarian, notifikasi
app/Imports/             Impor siswa
app/Exports/             Ekspor laporan
app/Console/Commands/    Perintah generator
config/                  Konfigurasi aplikasi/layanan
database/                Migration/seeder
resources/views/         Tampilan Blade
routes/web.php           Route dan pembatasan peran
routes/console.php       Scheduler
public/                  Aset, manifest, service worker, bukti pembayaran
storage/                 Log/file runtime
tests/Feature/           Tes aplikasi
docker/                  Apache/startup container
docs/                    Rancangan/rencana pengembangan
```

| Tabel domain | Isi |
| --- | --- |
| `users` | Akun/peran, relasi siswa/kelas, email, NIP, penandatangan |
| `kelas`, `siswa`, `tahun_ajaran`, `biaya_spp` | Data master/tarif |
| `tagihan` | Tagihan per siswa/tahun ajaran/bulan/tahun |
| `pembayaran` | Transaksi, invoice, metode/status, referensi Midtrans |
| `auto_bill_settings` | Jadwal penerbitan/jatuh tempo |
| `tagihan_exemptions` | Gratis/diskon |
| `web_notifications` | Notifikasi internal |
| `push_subscriptions` | Subscription push |
| `activity_logs` | Log aktivitas tertentu |

Ada pula tabel infrastruktur Laravel untuk sesi, reset password, cache, antrean. Keunikan tagihan meliputi siswa, tahun ajaran, bulan, tahun; bulan kalender sama pada tahun ajaran berbeda dapat memiliki record berbeda.

## Pengujian

Siapkan PHP dan dependency development Composer. [phpunit.xml](phpunit.xml) memakai **MySQL `spp_al_jabbar_testing`**, bukan SQLite. Migration mengandung SQL khusus MySQL. Gunakan database uji terpisah/kredensial sesuai; `RefreshDatabase` mengubah database uji.

```powershell
php artisan test
php artisan test --filter=RoleAccessTest
php artisan test --filter=AuthenticationIdentifierTest
php artisan test --filter=FuturePaymentTest
php artisan test --filter=SppCoverageSummaryTest
php artisan view:cache
```

Tes mencakup akses peran, autentikasi, persiapan masa depan tanpa pelunasan langsung, serta urutan ringkasan. Keberadaan tes bukan bukti lulus pada instalasi Anda. Uji browser/Midtrans Sandbox secara terpisah.

Skenario penerimaan: login lima peran, siapkan tagihan, konfirmasi tunai, selesaikan Sandbox, periksa invoice, bayar masa depan, cocokkan Lunas sampai, buka/ekspor laporan.

## Pemecahan masalah

| Gejala | Pemeriksaan/tindakan |
| --- | --- |
| `php` tidak dikenali | Gunakan terminal Laragon atau tambahkan PHP 8.4 ke PATH, buka terminal baru. |
| `vendor/autoload.php` tidak ada | `composer install` dari root proyek. |
| Database gagal terhubung | Periksa server, host/port, database, kredensial/TLS. |
| APP_KEY kosong/tidak valid | Instalasi baru: `key:generate`. Instalasi aktif: periksa/pulihkan kunci valid. |
| `.env` belum terbaca | `php artisan optimize:clear`, cache ulang pada server jika diperlukan. |
| Tampilan/ikon hilang | Periksa internet/CDN; layout tidak memerlukan Vite. |
| Admin TU tidak melihat menu keuangan | Login **Bendahara**, sesuai pembagian akses. |
| Siswa gagal login | Periksa NIS, password, akun, relasi `siswa_id`; admin mengelola password. |
| Reset email tidak masuk | Periksa email akun/mailer/Brevo; mailer `log` tidak mengirim nyata. |
| Tagihan otomatis tidak muncul | Periksa scheduler, otomatisasi aktif, tahun ajaran, tanggal keluar, siswa aktif, tarif. |
| Tarif belum diatur | Periksa kelas dan biaya tahun ajaran pilihan. |
| Midtrans gagal dibuat | Periksa pasangan kunci, Sandbox/Production, internet, log. |
| Selesai Snap belum lunas | Periksa finish/webhook dan status Midtrans, bukan hanya tampilan browser. |
| Bayar Masa Depan tidak berlanjut | Pilih siswa aktif, bulan ini/berikutnya, pastikan tarif/tagihan belum selesai. |
| Lunas sampai lebih awal dari pembayaran terakhir | Periksa celah data dan bulan belum selesai. |
| Lunas tanpa invoice | Dapat berasal dari baseline Set Terakhir Bayar/data demo. |
| Pending online menghilang | Cleaner menghapus pending melewati batas. |
| PDF laporan gagal | Periksa penandatangan Kepala Sekolah. |

Baca validasi dan `storage/logs/laravel.log` pada lokal. Untuk `LOG_CHANNEL=stderr`, baca log hosting. Samarkan rahasia/data pribadi sebelum membagikan log.

## Batasan aplikasi

- Data seed untuk demonstrasi; cocokkan nominal/status dengan catatan sekolah.
- Lunas sampai menyatakan urutan sejak awal data, bukan bukti seluruh bulan sejak siswa masuk.
- Persiapan historis/masa depan belum memakai perhitungan diskon generator.
- Bayar Masa Depan memakai tahun ajaran pilihan untuk semua tagihan baru, tanpa membagi otomatis ke beberapa tahun ajaran.
- Pembayaran kumulatif mencakup tagihan belum selesai sampai tujuan, termasuk tahun ajaran sebelumnya.
- Bukti berada di `public/payment-proofs`; deployment perlu storage/pengaturan akses sesuai.
- CDN, Snap, email, push perlu konektivitas. Manifest/service worker bukan jaminan pembayaran offline.
- README mendokumentasikan kode; pengujian runtime/integrasi tetap diperlukan pada lingkungan instalasi.
