# Kenaikan kelas, kelulusan, dan tarif SPP

Rancangan disetujui pengguna pada 15 Oktober 2026, dengan instruksi melanjutkan implementasi.

## Perilaku
- Kelas aktif, angkatan masuk, tahun ajaran, dan status siswa adalah data terpisah.
- Kelas memiliki jenjang SD/SMP/SMA/SMK, tingkat numerik, dan jurusan opsional. SD memakai 1–6, SMP 7–9, SMA/SMK 10–12. Kelas lama tetap dapat digunakan; metadata dapat dilengkapi admin.
- Admin TU memilih tahun ajaran tujuan, bulan efektif, dan pemetaan kelas asal ke tujuan. Pilihan per siswa dapat menimpa pemetaan untuk tinggal kelas, pindah kelas/jurusan, lulus, atau keluar.
- Pratinjau tidak mengubah database. Konfirmasi berlaku atomik untuk satu snapshot siswa, bukan membaca ulang kelas setelah setiap perpindahan. Snapshot kedaluwarsa ditolak bila data siswa atau tarif berubah.
- Satu siswa hanya boleh diproses sekali per tahun ajaran tujuan. Riwayat mencatat asal, tujuan, tindakan, tanggal efektif, dan pelaksana.
- Angkatan tidak berubah ketika naik kelas. Kelas terakhir alumni tetap disimpan, status menjadi lulus/keluar, dan akun tetap dapat membaca riwayat serta membayar tagihan lama.
- Tarif default pada kenaikan adalah nominal sebelumnya per siswa. Admin dapat mengisi nominal baru mulai bulan efektif. Pengaturan tarif terpisah mendukung perubahan semester tanpa kenaikan kelas.
- Nominal nol berarti gratis. Tarif baru hanya untuk tagihan yang belum dibuat; tagihan lama, termasuk pembayaran di muka, tidak dihitung ulang.
- Riwayat kelas pada tagihan dan invoice memakai snapshot nama kelas yang tersimpan saat tagihan dibuat; data lama dibekukan dari kelas yang tercatat sebelum peluncuran fitur.
- Generator tagihan dan kutipan bayar masa depan memakai resolver tarif yang sama berdasarkan periode. Siswa nonaktif tidak mendapat tagihan baru.
- Kenaikan berlaku langsung pada konfirmasi; bulan efektif tidak boleh sesudah bulan berjalan. Pergantian semester/tarif boleh dijadwalkan ke depan.
- Kelulusan/keluar ditolak selama invoice masa depan masih pending; invoice harus diselesaikan atau dibatalkan agar tidak membentuk tagihan baru sesudah siswa nonaktif.
- Batch besar dikirim sebagai JSON yang tetap divalidasi server agar batas jumlah field PHP tidak memotong pemetaan siswa.
- Tidak mengaktifkan tahun ajaran tujuan secara diam-diam, menghapus alumni, atau menghapus pembayaran di muka. Admin tetap mengatur tahun ajaran aktif melalui menu yang tersedia.

## Implementasi dan verifikasi
Migration menambah metadata kelas, angkatan/tahun ajaran siswa, riwayat perpindahan, tarif efektif per siswa, dan snapshot kelas tagihan. Controller terpisah menangani preview/confirm dan tarif semester dengan middleware admin_tu. Service resolver dipakai generator bulanan, bendahara, dan pembayaran masa depan. Tes menguji batch bercampur, tarif tetap/baru, alumni, otorisasi, pratinjau tanpa write, duplikasi, snapshot kelas lama, dan perlindungan pembayaran di muka.

Tidak ada keputusan produk yang menahan implementasi; bulan efektif dan pilihan per siswa ditampilkan eksplisit sebelum konfirmasi.
