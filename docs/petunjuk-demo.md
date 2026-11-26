# Petunjuk Demo

Demo berlangsung sekitar 3 menit. Alamat `localhost` diganti dengan IP host jika demo dilakukan di VM.

## Persiapan

1. Pastikan Docker berjalan (Docker Desktop berstatus *running*, atau Docker Engine aktif di VM).
2. Pastikan file `.env` sudah dibuat dari `.env.example` (lihat README).
3. Di folder proyek, jalankan:
   ```
   docker compose up -d
   docker compose ps
   ```
   `web` harus berstatus **Up** dan `db` berstatus **healthy**.
4. Pastikan `http://localhost:8080` menampilkan aplikasi.
5. Jangan menampilkan isi `.env` di layar. Siapkan nilai `DB_PASSWORD` di catatan terpisah untuk langkah 6.

## Alur demo

| No | Langkah | Yang disampaikan |
|---|---|---|
| 1 | Buka `compose.yaml` | Ada dua service: `web` (PHP-Apache) dan `db` (MySQL 8.4). Port 8080 di host diteruskan ke port 80 container. Password dibaca dari `.env`, bukan ditulis di file. |
| 2 | `docker compose ps` | Kedua service berjalan; `db` berstatus healthy, jadi `web` baru menyala setelah database siap. |
| 3 | Buka `http://localhost:8080` | Halaman menampilkan saldo, total pemasukan, total pengeluaran, dan riwayat transaksi. |
| 4 | Tambah satu pemasukan dan satu pengeluaran | Transaksi muncul di riwayat. Saldo sama dengan total pemasukan dikurangi total pengeluaran. |
| 5 | Kosongkan Nominal lalu simpan | Form ditolak dengan pesan kesalahan. Data tidak bertambah. |
| 6 | Cek database: `docker compose exec db mysql -u app -p kas`, ketik password, lalu `SELECT * FROM transaksi;` dan `exit` | Data yang tampil di halaman memang tersimpan di tabel `transaksi`. |
| 7 | `docker compose down`, `docker compose ps`, `docker compose up -d`, tunggu sekitar 15 detik, muat ulang halaman | Container dihapus dan dibuat ulang, tetapi data tetap ada karena disimpan di volume `db_data`. |

Jangan menambahkan `-v` pada `docker compose down`, karena opsi itu menghapus volume beserta datanya.

Jangan menekan F5 pada halaman yang menampilkan pesan kesalahan, karena form akan terkirim ulang.

## Jika terjadi masalah

| Gejala | Penyebab dan solusi |
|---|---|
| `port is already allocated` | Port 8080 dipakai stack lain. Jalankan `docker compose down` di folder stack tersebut, atau ubah pemetaan port di `compose.yaml` (misalnya `8081:80`). |
| Tidak bisa terhubung ke Docker | Docker Desktop belum selesai menyala. Tunggu lalu ulangi. |
| MySQL menolak koneksi setelah `up -d` | Database belum siap. Tunggu sekitar 15 detik lalu ulangi. |
| Halaman tidak tampil | Cek `docker compose ps`; jika perlu baca `docker compose logs web`. |

## Cadangan

Jika demo langsung bermasalah, tunjukkan bukti pengujian di [pengujian.md](pengujian.md) dan folder [bukti](bukti/).
