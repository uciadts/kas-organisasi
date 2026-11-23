CREATE TABLE transaksi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jenis ENUM('pemasukan','pengeluaran') NOT NULL,
  keterangan VARCHAR(100) NOT NULL,
  nominal INT NOT NULL,
  tanggal DATE NOT NULL DEFAULT (CURRENT_DATE)
);

INSERT INTO transaksi (jenis, keterangan, nominal) VALUES
  ('pemasukan', 'Iuran awal anggota', 500000),
  ('pengeluaran', 'Beli alat tulis', 75000);