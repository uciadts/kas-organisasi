<?php
date_default_timezone_set('Asia/Makassar');

try {
    $db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASSWORD'), getenv('DB_NAME'));
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Gagal terhubung ke database.');
}

$error = '';
$old = ['jenis' => 'pemasukan', 'keterangan' => '', 'nominal' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis = $_POST['jenis'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $nominal = filter_var($_POST['nominal'] ?? '', FILTER_VALIDATE_INT);
    $old = ['jenis' => $jenis, 'keterangan' => $keterangan, 'nominal' => $_POST['nominal'] ?? ''];

    if (!in_array($jenis, ['pemasukan', 'pengeluaran'], true)) {
        $error = 'Jenis transaksi tidak valid.';
    } elseif ($keterangan === '' || strlen($keterangan) > 100) {
        $error = 'Keterangan wajib diisi (maksimal 100 karakter).';
    } elseif ($nominal === false || $nominal <= 0) {
        $error = 'Nominal harus berupa angka lebih dari 0.';
    } else {
        $tanggal = date('Y-m-d');
        $stmt = $db->prepare('INSERT INTO transaksi (jenis, keterangan, nominal, tanggal) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssis', $jenis, $keterangan, $nominal, $tanggal);
        $stmt->execute();
        header('Location: /');
        exit;
    }
}

$tot = $db->query("SELECT
    COALESCE(SUM(IF(jenis = 'pemasukan', nominal, 0)), 0) AS masuk,
    COALESCE(SUM(IF(jenis = 'pengeluaran', nominal, 0)), 0) AS keluar
  FROM transaksi")->fetch_assoc();
$masuk = (int) $tot['masuk'];
$keluar = (int) $tot['keluar'];
$saldo = $masuk - $keluar;
$daftar = $db->query('SELECT tanggal, jenis, keterangan, nominal FROM transaksi ORDER BY id DESC');

function rp($n) { return 'Rp ' . number_format($n, 0, ',', '.'); }
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kas Organisasi Mahasiswa</title>
  <style>
    :root {
      --bg: #f1f5f9; --text: #0f172a; --muted: #64748b; --line: #e2e8f0;
      --primary: #2563eb; --green: #059669; --green-bg: #d1fae5; --red: #dc2626; --red-bg: #fee2e2;
    }
    * { box-sizing: border-box; }
    body { margin: 0; background: var(--bg); color: var(--text); line-height: 1.5;
           font-family: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif; }
    .hero { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #0ea5e9 100%);
            color: #fff; padding: 36px 16px 96px; }
    .hero .in, .wrap { max-width: 960px; margin: 0 auto; }
    .brand { font-size: .8rem; letter-spacing: .12em; text-transform: uppercase; opacity: .8; }
    .hero h1 { margin: 4px 0 0; font-size: 1.5rem; font-weight: 700; }
    .saldo-label { margin-top: 28px; opacity: .85; font-size: .95rem; }
    .saldo { font-size: 3rem; font-weight: 800; letter-spacing: -.02em; line-height: 1.15; }
    .wrap { margin-top: -64px; padding: 0 16px 40px; }
    .card { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 20px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, .06); }
    .stats { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .stat { display: flex; align-items: center; gap: 14px; }
    .ic { flex: none; width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; }
    .ic svg { width: 22px; height: 22px; }
    .ic.masuk { background: var(--green-bg); color: var(--green); }
    .ic.keluar { background: var(--red-bg); color: var(--red); }
    .label { color: var(--muted); font-size: .85rem; }
    .value { font-size: 1.4rem; font-weight: 700; }
    .masuk-t { color: var(--green); }
    .keluar-t { color: var(--red); }
    .grid { display: grid; grid-template-columns: 340px 1fr; gap: 16px; margin-top: 16px; align-items: start; }
    h2 { font-size: 1.05rem; margin: 0 0 16px; display: flex; justify-content: space-between; align-items: center; }
    .count { font-size: .75rem; font-weight: 600; color: var(--muted); background: var(--bg);
             padding: 2px 10px; border-radius: 999px; }
    .field { display: block; margin-bottom: 14px; }
    .field > span { display: block; font-size: .85rem; font-weight: 600; margin-bottom: 6px; color: #334155; }
    input[type=text], input[type=number] { width: 100%; font: inherit; padding: 10px 12px;
            border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; }
    input[type=text]:focus, input[type=number]:focus { outline: 3px solid #bfdbfe; border-color: var(--primary); }
    .money { position: relative; }
    .money b { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); font-weight: 600; }
    .money input { padding-left: 40px; }
    .seg { display: grid; grid-template-columns: 1fr 1fr; background: var(--bg); border-radius: 12px; padding: 4px; margin-bottom: 14px; }
    .seg input { position: absolute; opacity: 0; }
    .seg label { text-align: center; padding: 9px 6px; border-radius: 9px; cursor: pointer;
                 font-weight: 600; font-size: .9rem; color: var(--muted); }
    #j1:checked + label { background: #fff; color: var(--green); box-shadow: 0 1px 3px rgba(15, 23, 42, .15); }
    #j2:checked + label { background: #fff; color: var(--red); box-shadow: 0 1px 3px rgba(15, 23, 42, .15); }
    #j1:focus-visible + label, #j2:focus-visible + label { outline: 3px solid #bfdbfe; }
    button { width: 100%; font: inherit; font-weight: 700; padding: 12px; border: 0; border-radius: 10px;
             background: var(--primary); color: #fff; cursor: pointer; }
    button:hover { background: #1d4ed8; }
    .alert { background: var(--red-bg); color: #991b1b; border-radius: 10px; padding: 10px 12px;
             margin-bottom: 14px; font-size: .9rem; }
    .list { list-style: none; margin: 0; padding: 0; }
    .row { display: flex; align-items: center; gap: 14px; padding: 12px 8px; border-bottom: 1px solid var(--line); border-radius: 10px; }
    .row:last-child { border-bottom: 0; }
    .row:hover { background: #f8fafc; }
    .meta { flex: 1; min-width: 0; }
    .ket { font-weight: 600; overflow-wrap: anywhere; }
    .tgl { font-size: .8rem; color: var(--muted); }
    .amt { font-weight: 700; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .empty { text-align: center; color: var(--muted); padding: 24px 0; }
    footer { text-align: center; color: var(--muted); font-size: .8rem; padding: 8px 16px 32px; }
    @media (max-width: 820px) { .grid { grid-template-columns: 1fr; } }
    @media (max-width: 480px) { .stats { grid-template-columns: 1fr; } .saldo { font-size: 2.4rem; } }
  </style>
</head>
<body>
  <div class="hero">
    <div class="in">
      <div class="brand">KELOMPOK 7</div>
      <h1>Kas Organisasi Mahasiswa</h1>
      <div class="saldo-label">Saldo kas saat ini</div>
      <div class="saldo"><?= rp($saldo) ?></div>
    </div>
  </div>

  <div class="wrap">
    <div class="stats">
      <div class="card stat">
        <span class="ic masuk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg></span>
        <div><div class="label">Total pemasukan</div><div class="value masuk-t"><?= rp($masuk) ?></div></div>
      </div>
      <div class="card stat">
        <span class="ic keluar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M19 12l-7 7-7-7"/></svg></span>
        <div><div class="label">Total pengeluaran</div><div class="value keluar-t"><?= rp($keluar) ?></div></div>
      </div>
    </div>

    <div class="grid">
      <div class="card">
        <h2>Tambah transaksi</h2>
        <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
          <div class="seg">
            <input type="radio" name="jenis" id="j1" value="pemasukan" <?= $old['jenis'] === 'pengeluaran' ? '' : 'checked' ?>>
            <label for="j1">Pemasukan</label>
            <input type="radio" name="jenis" id="j2" value="pengeluaran" <?= $old['jenis'] === 'pengeluaran' ? 'checked' : '' ?>>
            <label for="j2">Pengeluaran</label>
          </div>
          <label class="field"><span>Keterangan</span>
            <input type="text" name="keterangan" placeholder="Contoh: Iuran bulan November" maxlength="100" value="<?= htmlspecialchars($old['keterangan']) ?>">
          </label>
          <label class="field"><span>Nominal</span>
            <span class="money"><b>Rp</b><input type="number" name="nominal" placeholder="0" min="1" value="<?= htmlspecialchars((string) $old['nominal']) ?>"></span>
          </label>
          <button type="submit">Simpan transaksi</button>
        </form>
      </div>

      <div class="card">
        <h2>Riwayat transaksi <span class="count"><?= $daftar->num_rows ?> transaksi</span></h2>
        <ul class="list">
          <?php if ($daftar->num_rows === 0): ?>
            <li class="empty">Belum ada transaksi.</li>
          <?php endif; ?>
          <?php while ($r = $daftar->fetch_assoc()): ?>
            <?php $in = $r['jenis'] === 'pemasukan'; ?>
            <li class="row">
              <span class="ic <?= $in ? 'masuk' : 'keluar' ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $in ? 'M12 19V5M5 12l7-7 7 7' : 'M12 5v14M19 12l-7 7-7-7' ?>"/></svg></span>
              <div class="meta">
                <div class="ket"><?= htmlspecialchars($r['keterangan']) ?></div>
                <div class="tgl"><?= date('d/m/Y', strtotime($r['tanggal'])) ?> &middot; <?= htmlspecialchars($r['jenis']) ?></div>
              </div>
              <div class="amt <?= $in ? 'masuk-t' : 'keluar-t' ?>"><?= ($in ? '+ ' : '- ') . rp($r['nominal']) ?></div>
            </li>
          <?php endwhile; ?>
        </ul>
      </div>
    </div>
  </div>

  <footer>KELOMPOK 7</footer>
</body>
</html>