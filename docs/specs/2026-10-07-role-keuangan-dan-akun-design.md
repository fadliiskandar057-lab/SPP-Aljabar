# Pemisahan Admin Manajemen dan Bendahara

## Tujuan

Memisahkan wewenang pengelolaan aplikasi SPP dari operasional keuangan, menyediakan audit yang transparan untuk staf manajemen, serta melengkapi pengelolaan akun dan pembayaran SPP untuk periode mendatang.

## Peran dan batas akses

| Peran | Hak utama | Tidak boleh dilakukan |
| --- | --- | --- |
| Admin Manajemen (`admin_tu`) | Mengelola siswa, kelas, tahun ajaran, biaya SPP, pengaturan tagihan, pembebasan/diskon, dan akun pengguna | Membuat, mengonfirmasi, membatalkan, atau membuka kembali pembayaran |
| Bendahara (`bendahara`) | Mengelola tunggakan, pembayaran tunai/manual, konfirmasi tunai, pembatalan dengan alasan, pembayaran masa depan, transaksi, invoice, dan laporan PDF | Mengubah data master, biaya, jadwal tagihan, dan akun pengguna |
| Kepala Sekolah | Melihat laporan serta aktivitas manajemen; ditetapkan sebagai penanda tangan laporan | Mengubah transaksi, data master, atau akun |
| Wali Kelas | Memantau data dan pembayaran untuk kelas yang diampu | Mengubah pembayaran atau data master |
| Siswa | Melihat profil/tagihan/riwayat dan membuat pembayaran sendiri | Mengakses data pengguna lain atau operasi staf |

Nilai basis data `admin_tu` dipertahankan demi kompatibilitas, tetapi semua label antarmuka diubah menjadi **Admin Manajemen**. Role baru adalah `bendahara`.

## Audit dan notifikasi

Tabel `activity_logs` menyimpan: pelaku (`user_id`), aksi, tipe dan ID objek, ringkasan, metadata sebelum/sesudah, waktu, dan alamat IP bila tersedia. Log tidak dapat diedit dari antarmuka.

Setiap aksi sensitif oleh Admin Manajemen atau Bendahara dicatat dan dibagikan sebagai notifikasi kepada Admin Manajemen, Bendahara, dan Kepala Sekolah. Aksi mencakup perubahan data master/pengguna, pembuatan atau perubahan tagihan, pencatatan/konfirmasi/pembatalan/buka ulang pembayaran, serta ekspor laporan.

Pembatalan pembayaran wajib menyertakan alasan. Notifikasi menyebut nama pelaku, siswa/NIS, invoice, waktu WIB, dan alasan. Notifikasi bukan pemberian hak akses; penerima hanya dapat melakukan aksi yang diizinkan perannya.

## Laporan resmi

Bendahara dapat memfilter laporan dengan bulan/tahun awal dan akhir lalu mengunduh PDF. Footer PDF mengambil nama dan NIP dari satu akun Kepala Sekolah yang ditandai sebagai penanda tangan laporan. Jika belum ada penanda tangan yang aktif, ekspor PDF ditolak dengan pesan yang jelas.

## Pembayaran sampai periode mendatang

Pengguna memilih bulan dan tahun terakhir, misalnya sampai bulan kelulusan. Sistem membuat tagihan untuk setiap bulan yang belum ada sampai periode tersebut, menghitung total, dan membuat bukti pembayaran per tagihan agar pembatalan, invoice, dan laporan tetap konsisten. Pembayaran hanya dapat mencakup tagihan aktif siswa tersebut.

## Akun mandiri

Setiap `users` memiliki email unik (opsional untuk akun lama, wajib saat memakai lupa password), NIP opsional, dan penanda penanda-tangan laporan. Pengguna dapat memperbarui profil yang aman: nama, email, dan kata sandi dengan verifikasi kata sandi saat ini. Data akademik siswa tetap dikelola Admin Manajemen.

Fitur lupa password menggunakan broker password Laravel dan mengirim tautan satu kali pakai ke email akun. Konfigurasi mail harus tersedia pada lingkungan produksi.

## Notifikasi saat aplikasi tertutup

Aplikasi menyediakan Web Push melalui PWA (manifest dan service worker). Saat pengguna mengizinkan notifikasi, browser atau aplikasi PWA yang terpasang di Android menyimpan subscription perangkat. Notifikasi aktivitas penting dapat kemudian muncul meskipun tab situs sudah ditutup.

Notifikasi push dan notifikasi di dalam aplikasi memakai sumber aktivitas yang sama, tetapi pengiriman push hanya dilakukan untuk pengguna yang berlangganan dan mengaktifkan preferensinya. Mengetuk notifikasi membawa pengguna ke halaman aktivitas atau objek terkait setelah autentikasi. Pengguna dapat mengaktifkan atau mematikan push dari profilnya.

Web Push membutuhkan situs yang diakses lewat HTTPS di produksi serta pasangan kunci VAPID pada konfigurasi server. Push tidak dapat dijamin apabila pengguna menolak izin, mematikan notifikasi pada perangkat/browser, atau memaksa berhenti aplikasi/browser.

## Validasi dan pengujian

- Middleware dan route test membuktikan Admin Manajemen tidak dapat melakukan route Bendahara dan sebaliknya.
- Pembatalan tanpa alasan ditolak dan pembatalan valid mencatat aktivitas serta menghasilkan notifikasi staf.
- PDF mengikuti rentang periode dan memuat nama/NIP penanda tangan.
- Pembayaran masa depan membuat tagihan per bulan tanpa duplikasi.
- Penggantian profil/password dan lupa password menjaga email unik serta token reset yang aman.
- Notifikasi push dikirim kepada subscription yang aktif, membuka tujuan yang benar saat diketuk, dan tidak dikirim kepada pengguna yang menonaktifkan preferensi.
