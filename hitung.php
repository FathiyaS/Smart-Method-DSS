<?php
include "config.php";

// Ambil data
$kriteria = [];
$rs = $koneksi->query("SELECT * FROM kriteria ORDER BY id");
while ($row = $rs->fetch_assoc()) $kriteria[$row['id']] = $row;

$alternatif = [];
$rs = $koneksi->query("SELECT * FROM alternatif ORDER BY id");
while ($row = $rs->fetch_assoc()) $alternatif[$row['id']] = $row;

$nilai = [];
$rs = $koneksi->query("SELECT * FROM nilai");
while ($row = $rs->fetch_assoc()) $nilai[$row['id_alternatif']][$row['id_kriteria']] = $row['nilai'];

// Step 1: Nilai Awal 
$nilai_awal = $nilai;

// Step 2: Normalisasi (asumsi semua kriteria benefit: val / max)
$normal = [];
$max_per_kri = [];
foreach ($kriteria as $id_kri => $k) {
  $max = 0;
  foreach ($alternatif as $id_alt => $a) {
    $v = $nilai[$id_alt][$id_kri] ?? 0;
    if ($v > $max) $max = $v;
  }
  $max_per_kri[$id_kri] = $max;

  foreach ($alternatif as $id_alt => $a) {
    $v = $nilai[$id_alt][$id_kri] ?? 0;
    $normal[$id_alt][$id_kri] = ($max > 0) ? $v / $max : 0;
  }
}

// Step 3: Utility (normalisasi × bobot) + total
$utility = [];
$total = [];
foreach ($alternatif as $id_alt => $a) {
  $sum = 0;
  foreach ($kriteria as $id_kri => $k) {
    $u = ($normal[$id_alt][$id_kri] ?? 0) * floatval($k['bobot']);
    $utility[$id_alt][$id_kri] = $u;
    $sum += $u;
  }
  $total[$id_alt] = $sum;
}

// Step 4: Ranking
$hasil = [];
foreach ($alternatif as $id_alt => $a) {
  $hasil[] = ['nama' => $a['nama'], 'nilai' => $total[$id_alt]];
}
usort($hasil, fn($x,$y) => $y['nilai'] <=> $x['nilai']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Perhitungan SMART – Step by Step</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <h2 class="mb-4">Hasil Perhitungan</h2>

  <!-- Step 1 -->
  <h4>1) Nilai Awal</h4>
  <div class="table-responsive">
    <table class="table table-bordered">
      <thead class="table-secondary">
        <tr>
          <th>Alternatif</th>
          <?php foreach($kriteria as $k): ?>
            <th><?= htmlspecialchars($k['nama']) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach($alternatif as $id_alt => $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['nama']) ?></td>
            <?php foreach($kriteria as $id_kri => $k): ?>
              <td><?= $nilai_awal[$id_alt][$id_kri] ?? 0 ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="table-light">
          <th>Max per Kriteria</th>
          <?php foreach($kriteria as $id_kri => $k): ?>
            <th><?= $max_per_kri[$id_kri] ?? 0 ?></th>
          <?php endforeach; ?>
        </tr>
      </tfoot>
    </table>
  </div>

  <!-- Step 2 -->
  <h4 class="mt-4">2) Normalisasi (nilai / max)</h4>
  <div class="table-responsive">
    <table class="table table-bordered">
      <thead class="table-secondary">
        <tr>
          <th>Alternatif</th>
          <?php foreach($kriteria as $k): ?>
            <th><?= htmlspecialchars($k['nama']) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach($alternatif as $id_alt => $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['nama']) ?></td>
            <?php foreach($kriteria as $id_kri => $k): ?>
              <td><?= number_format($normal[$id_alt][$id_kri] ?? 0, 4) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Step 3 -->
  <h4 class="mt-4">3) Utility (Normalisasi × Bobot)</h4>
  <div class="table-responsive">
    <table class="table table-bordered">
      <thead class="table-secondary">
        <tr>
          <th>Alternatif</th>
          <?php foreach($kriteria as $k): ?>
            <th><?= htmlspecialchars($k['nama']) ?> (<?= $k['bobot'] ?>)</th>
          <?php endforeach; ?>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($alternatif as $id_alt => $a): $sum=0; ?>
          <tr>
            <td><?= htmlspecialchars($a['nama']) ?></td>
            <?php foreach($kriteria as $id_kri => $k): 
              $v = $utility[$id_alt][$id_kri] ?? 0; $sum += $v; ?>
              <td><?= number_format($v, 4) ?></td>
            <?php endforeach; ?>
            <td class="fw-bold"><?= number_format($sum, 4) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Step 4 -->
  <h4 class="mt-4">4) Hasil Ranking</h4>
  <div class="table-responsive">
    <table class="table table-bordered">
      <thead class="table-dark">
        <tr>
          <th>Ranking</th>
          <th>Alternatif</th>
          <th>Nilai Akhir</th>
        </tr>
      </thead>
      <tbody>
        <?php $rank=1; foreach($hasil as $row): ?>
          <tr>
            <td><?= $rank++ ?></td>
            <td><?= htmlspecialchars($row['nama']) ?></td>
            <td><?= number_format($row['nilai'], 4) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="mt-3">
    <a href="index.php" class="btn btn-outline-secondary">← Kembali ke Dashboard Input</a>
  </div>
</body>
</html>
