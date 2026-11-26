# Kas Organisasi Mahasiswa

Aplikasi web sederhana untuk mencatat pemasukan dan pengeluaran kas organisasi mahasiswa. Berjalan sebagai stack Docker Compose (PHP-Apache dan MySQL).

Proyek kelompok mata kuliah Cloud Computing, Kelompok 7, PTIK B.

## Anggota

| Nama | NIM | Kontribusi |
|---|---|---|
| **Uci Adityantikasari** | **240209502001** | **Persiapan host Docker, aplikasi PHP (form, daftar, saldo), skema database, Dockerfile, `compose.yaml`, dan uji persistensi.** |
| Muhammad Fauzan | 240209502034 | Dokumentasi: diagram dan petunjuk demo (tugas hingga UAS). |
| Fransisca Gabriela | 240209501089 | Pengujian fitur, pengumpulan bukti uji, dan pemeliharaan README (tugas hingga UAS). |
| Muhammad Fikri | 240209502016 | Review rancangan dan persiapan demo (tugas hingga UAS). |

Riwayat kontribusi lengkap dapat dilihat pada riwayat commit repositori.

## Fitur

- Tambah transaksi (pemasukan atau pengeluaran)
- Lihat daftar transaksi
- Lihat total saldo kas

## Arsitektur

| Service | Image | Fungsi |
|---|---|---|
| `web` | `php:8.3-apache` (dibangun dari `Dockerfile`) | Menjalankan aplikasi PHP. Port 8080 host diteruskan ke port 80 container. |
| `db` | `mysql:8.4` | Menyimpan tabel `transaksi`. Tidak dipublikasikan ke host. |

Aplikasi menghubungi database lewat nama service `db` pada jaringan Compose. Data MySQL disimpan di named volume `db_data`, sedangkan kode aplikasi dipasang dari folder `app`. Diagram dan alur transaksi ada di [docs/arsitektur.md](docs/arsitektur.md).

## Cara menjalankan

1. Salin `.env.example` menjadi `.env`, lalu isi password-nya. Di PowerShell: `copy .env.example .env`. Di Linux atau macOS: `cp .env.example .env`.
2. Jalankan `docker compose up -d --build`. Pertama kali butuh beberapa menit, karena Compose menunggu database sehat sebelum menyalakan web.
3. Buka `http://localhost:8080` (atau `http://IP-HOST:8080` dari komputer lain).

Petunjuk demo langkah demi langkah ada di [docs/petunjuk-demo.md](docs/petunjuk-demo.md).

## Menghentikan

Jalankan `docker compose down`. Data tetap tersimpan di volume. Jangan menambah opsi `-v` kecuali ingin menghapus seluruh data.

## Struktur proyek

- `app/index.php`: halaman aplikasi (form, daftar, saldo)
- `Dockerfile`: image web (PHP-Apache dan ekstensi mysqli)
- `compose.yaml`: definisi service web dan db
- `init.sql`: skema tabel dan data awal (hanya dijalankan saat volume masih kosong)
- `.env.example`: contoh variabel lingkungan (password diisi di `.env`, tidak diunggah)
- `docs/`: diagram arsitektur, petunjuk demo, laporan pengujian, dan folder `bukti/` serta `bukti-vm/` berisi tangkapan layar

## Pengujian

| No | Skenario | Hasil yang diharapkan |
|---|---|---|
| 1 | Buka `http://localhost:8080` | Halaman aplikasi tampil |
| 2 | Tambah transaksi | Transaksi muncul di daftar |
| 3 | Tambah satu pemasukan dan satu pengeluaran | Saldo = total pemasukan dikurangi total pengeluaran |
| 4 | `docker compose down` lalu `docker compose up -d` | Data transaksi masih ada |
| 5 | Kirim form dengan nominal atau keterangan kosong | Ditolak dengan pesan kesalahan |

Hasil pengujian beserta tangkapan layar, baik di Docker Desktop maupun di VM Ubuntu Server, ada di [docs/pengujian.md](docs/pengujian.md).

## Catatan

- Jika `init.sql` diubah setelah database terbentuk, jalankan `docker compose down -v` lalu `docker compose up -d` untuk membuat database ulang (data akan terhapus).
- Port 8080 harus kosong. Jika bentrok, ubah pemetaan port di `compose.yaml`, misalnya `8081:80`.
