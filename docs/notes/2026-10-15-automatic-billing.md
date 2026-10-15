# Tagihan bulanan mengikuti kalender

## Diagnosis
Pada 15 Oktober 2026, pemeriksaan baca-saja database Singapore menemukan jadwal nonaktif (`is_enabled=0`), tanggal terbit 1, jatuh tempo 10, dan tahun ajaran aktif 2026/2027. Data contoh berisi tagihan 2025, sedangkan sebagian periode 2026–2027 sudah dibuat/dibayar melalui alur lain. Docker menjalankan Apache dan migration, tetapi tidak memulai scheduler Laravel. Menyimpan aturan jadwal sebelumnya tidak menjalankan penerbitan tagihan.

## Perbaikan
- `AutomaticBillingService` menjadi jalur bersama untuk scheduler/CLI dan kunjungan GET terautentikasi. Pengaturan nonaktif tetap dihormati.
- Kalender memakai timezone aplikasi (default Asia/Jakarta), tanggal terbit, jatuh tempo, dan bulan mulai yang dipilih admin. Jatuh tempo dengan tanggal lebih awal dari terbit berada pada bulan berikutnya.
- Jika belum ada bulan mulai pada konfigurasi lama, sinkronisasi dimulai dari bulan berjalan, tidak otomatis menambahkan seluruh tunggakan sejak data contoh 2025.
- Periode terlewat dikejar dari checkpoint. Pemeriksaan dibatasi setiap lima menit, tetapi peralihan tanggal terbit langsung diperiksa. Siswa baru setelah pergantian bulan tidak ditagih ulang untuk bulan sebelumnya yang sudah selesai diproses.
- Generator mengunci siswa dan menyimpan tagihan secara batch. Tagihan yang sudah ada dihormati lintas tahun ajaran; periode pada invoice masa depan pending juga dicadangkan agar tidak ada dua pembayaran untuk bulan yang sama. Pembatalan melepas cadangan agar tagihan bulan berjalan bisa diterbitkan.
- UI menampilkan kalender, status aktif, jadwal berikutnya, pemeriksaan terakhir, dan biaya yang belum tersedia. Siswa melihat panel SPP bulan berjalan sebelum ringkasan tunggakan lama. Daftar tagihan diurutkan berdasarkan tahun/bulan, bukan waktu input.
- Penerbitan tidak mengubah nominal, status, atau bukti pembayaran tagihan lama.

## Operasional
Pada Render Free, sinkronisasi saat kunjungan memastikan periode terbaru tersedia ketika web digunakan; ini tidak menjanjikan pekerjaan berjalan saat instance tidur dan tidak ada pengunjung. Scheduler yang benar-benar dijalankan tetap dapat memakai `php artisan tagihan:generate-bulanan` dan aturan `routes/console.php`.

Untuk mengejar periode lama, admin memilih bulan mulai secara eksplisit pada Pengaturan SPP → Otomatis & Diskon. Tahun ajaran aktif tetap dikelola admin secara terpisah dari tahun kalender.

## Verifikasi
Tes mencakup jadwal nonaktif, tanggal terbit, lintas bulan, catch-up, biaya yang belum tersedia, siswa baru, periode prabayar pada tahun ajaran lain, invoice masa depan pending/cancel, pengurutan kalender, pengaktifan melalui form admin, dan idempotensi CLI. Tampilan HTML lokal diperiksa di Edge headless pada lebar 320/768/1440; tombol Jadwal Otomatis membuka tab yang benar dan kedua halaman tidak meluber secara horizontal.

Hasil: 43 tes, 323 assertion, seluruhnya lolos. Rollback/migrate pada database uji mempertahankan 40 tagihan dan membekukan bulan mulai jadwal aktif ke Oktober 2026. Flag jadwal pada database Singapore telah diaktifkan dengan tanggal 1/10 tanpa mengubah tagihan lama. Sinkronisasi runtime produksi menunggu deployment kode ini dan kunjungan pengguna; belum diklaim terverifikasi di Render.
