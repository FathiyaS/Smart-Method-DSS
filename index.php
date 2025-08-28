<?php
include "config.php";

// ====== PROSES INPUT ======

// Tambah Kriteria
if (isset($_POST['simpan_kriteria'])) {
  $nama  = trim($_POST['nama']);
  $bobot = floatval($_POST['bobot']);
  $stmt = $koneksi->prepare("INSERT INTO kriteria (nama, bobot) VALUES (?, ?)");
  $stmt->bind_param("sd", $nama, $bobot);
  $stmt->execute();
  $stmt->close();
}

// Update Kriteria
if (isset($_POST['update_kriteria'])) {
  $id    = intval($_POST['id']);
  $nama  = trim($_POST['nama']);
  $bobot = floatval($_POST['bobot']);
  $stmt = $koneksi->prepare("UPDATE kriteria SET nama=?, bobot=? WHERE id=?");
  $stmt->bind_param("sdi", $nama, $bobot, $id);
  $stmt->execute();
  $stmt->close();
}

// Hapus Kriteria
if (isset($_GET['hapus_kriteria'])) {
  $id = intval($_GET['hapus_kriteria']);
  $koneksi->query("DELETE FROM kriteria WHERE id=$id");
  $koneksi->query("DELETE FROM nilai WHERE id_kriteria=$id");
}

// Tambah Alternatif
if (isset($_POST['simpan_alternatif'])) {
  $nama  = trim($_POST['nama']);
  $stmt = $koneksi->prepare("INSERT INTO alternatif (nama) VALUES (?)");
  $stmt->bind_param("s", $nama);
  $stmt->execute();
  $stmt->close();
}

// Update Alternatif
if (isset($_POST['update_alternatif'])) {
  $id   = intval($_POST['id']);
  $nama = trim($_POST['nama']);
  $stmt = $koneksi->prepare("UPDATE alternatif SET nama=? WHERE id=?");
  $stmt->bind_param("si", $nama, $id);
  $stmt->execute();
  $stmt->close();
}

// Hapus Alternatif
if (isset($_GET['hapus_alternatif'])) {
  $id = intval($_GET['hapus_alternatif']);
  $koneksi->query("DELETE FROM alternatif WHERE id=$id");
  $koneksi->query("DELETE FROM nilai WHERE id_alternatif=$id");
}

// Simpan Matriks Nilai (massal)
if (isset($_POST['simpan_matrix'])) {
  $nilai = $_POST['nilai'] ?? [];
  $koneksi->begin_transaction();
  try {
    $del = $koneksi->prepare("DELETE FROM nilai WHERE id_alternatif=? AND id_kriteria=?");
    $ins = $koneksi->prepare("INSERT INTO nilai (id_alternatif, id_kriteria, nilai) VALUES (?, ?, ?)");
    foreach ($nilai as $id_alt => $cols) {
      foreach ($cols as $id_kri => $val) {
        $id_alt = intval($id_alt);
        $id_kri = intval($id_kri);
        // Hapus dulu supaya update bersih
        $del->bind_param("ii", $id_alt, $id_kri);
        $del->execute();

        // Jika ada angka, insert ulang
        if ($val !== "" && $val !== null) {
          $v = floatval($val);
          $ins->bind_param("iid", $id_alt, $id_kri, $v);
          $ins->execute();
        }
      }
    }
    $del->close();
    $ins->close();
    $koneksi->commit();
  } catch (Exception $e) {
    $koneksi->rollback();
  }
}

// ====== AMBIL DATA ======
$kriteria = [];
$rs = $koneksi->query("SELECT * FROM kriteria ORDER BY id");
while ($row = $rs->fetch_assoc()) $kriteria[$row['id']] = $row;

$alternatif = [];
$rs = $koneksi->query("SELECT * FROM alternatif ORDER BY id");
while ($row = $rs->fetch_assoc()) $alternatif[$row['id']] = $row;

$matrix = []; // nilai[alt][kri] = angka
$rs = $koneksi->query("SELECT * FROM nilai");
while ($row = $rs->fetch_assoc()) {
  $matrix[$row['id_alternatif']][$row['id_kriteria']] = $row['nilai'];
}

// Hitung total bobot
$total_bobot = 0;
foreach ($kriteria as $k) $total_bobot += floatval($k['bobot']);
$pas_1 = abs($total_bobot - 1.0) < 0.0001;

// ambil data edit (kalau ada)
$edit_kriteria = null;
if (isset($_GET['edit_kriteria'])) {
  $id = intval($_GET['edit_kriteria']);
  $edit_kriteria = $koneksi->query("SELECT * FROM kriteria WHERE id=$id")->fetch_assoc();
}

$edit_alternatif = null;
if (isset($_GET['edit_alternatif'])) {
  $id = intval($_GET['edit_alternatif']);
  $edit_alternatif = $koneksi->query("SELECT * FROM alternatif WHERE id=$id")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard SMART – Input & Rekap</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <h2 class="mb-4">Dashboard SMART – Input Data & Rekap Tabel</h2>

    <!-- ===== A L T E R N A T I F ===== -->
  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header fw-bold"><?= $edit_alternatif ? "Edit Alternatif" : "Tambah Alternatif" ?></div>
        <div class="card-body">
          <form method="post" class="row g-3">
            <input type="hidden" name="id" value="<?= $edit_alternatif['id'] ?? '' ?>">
            <div class="col-12">
              <label class="form-label">Nama Alternatif</label>
              <input type="text" name="nama" class="form-control" required value="<?= $edit_alternatif['nama'] ?? '' ?>">
            </div>
            <div class="col-12">
              <?php if ($edit_alternatif): ?>
                <button type="submit" name="update_alternatif" class="btn btn-warning">Update Alternatif</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
              <?php else: ?>
                <button type="submit" name="simpan_alternatif" class="btn btn-success">Simpan Alternatif</button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header fw-bold">Data Alternatif</div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-bordered mb-0">
              <thead class="table-secondary">
                <tr>
                  <th>ID</th>
                  <th>Nama Alternatif</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($alternatif)): ?>
                  <tr><td colspan="3" class="text-center text-muted">Belum ada alternatif</td></tr>
                <?php else: foreach($alternatif as $a): ?>
                  <tr>
                    <td><?= $a['id'] ?></td>
                    <td><?= htmlspecialchars($a['nama']) ?></td>
                    <td>
                      <a href="?edit_alternatif=<?= $a['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                      <a href="?hapus_alternatif=<?= $a['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus alternatif ini?')">Hapus</a>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>


  <!-- ===== K R I T E R I A ===== -->
  <div class="row g-4 mt-1">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header fw-bold"><?= $edit_kriteria ? "Edit Kriteria" : "Tambah Kriteria" ?></div>
        <div class="card-body">
          <form method="post" class="row g-3">
            <input type="hidden" name="id" value="<?= $edit_kriteria['id'] ?? '' ?>">
            <div class="col-7">
              <label class="form-label">Nama Kriteria</label>
              <input type="text" name="nama" class="form-control" required value="<?= $edit_kriteria['nama'] ?? '' ?>">
            </div>
            <div class="col-5">
              <label class="form-label">Bobot (0–1)</label>
              <input type="number" step="0.01" name="bobot" class="form-control" required value="<?= $edit_kriteria['bobot'] ?? '' ?>">
            </div>
            <div class="col-12">
              <?php if ($edit_kriteria): ?>
                <button type="submit" name="update_kriteria" class="btn btn-warning">Update Kriteria</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
              <?php else: ?>
                <button type="submit" name="simpan_kriteria" class="btn btn-primary">Simpan Kriteria</button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header fw-bold">Data Kriteria</div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-bordered mb-0">
              <thead class="table-secondary">
                <tr>
                  <th>ID</th>
                  <th>Nama</th>
                  <th>Bobot</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($kriteria)): ?>
                  <tr><td colspan="4" class="text-center text-muted">Belum ada kriteria</td></tr>
                <?php else: foreach($kriteria as $k): ?>
                  <tr>
                    <td><?= $k['id'] ?></td>
                    <td><?= htmlspecialchars($k['nama']) ?></td>
                    <td><?= $k['bobot'] ?></td>
                    <td>
                      <a href="?edit_kriteria=<?= $k['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                      <a href="?hapus_kriteria=<?= $k['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus kriteria ini?')">Hapus</a>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
          <div class="small text-muted p-2">Total Bobot: <?= number_format($total_bobot,2) ?></div>
        </div>
      </div>
    </div>
  </div>


  <!-- ===== M A T R I K S   N I L A I  (mass input) ===== -->
  <div class="card mt-4">
    <div class="card-header fw-bold">Matriks Nilai (Alternatif × Kriteria)</div>
    <div class="card-body">
      <?php if(empty($kriteria) || empty($alternatif)): ?>
        <div class="alert alert-warning">
          Tambahkan <b>kriteria</b> dan <b>alternatif</b> terlebih dahulu untuk mengisi matriks nilai.
        </div>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="simpan_matrix" value="1">
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-secondary">
                <tr>
                  <th style="min-width:180px;">Alternatif \ Kriteria</th>
                  <?php foreach($kriteria as $k): ?>
                    <th><?= htmlspecialchars($k['nama']) ?><br><span class="small text-muted">(bobot: <?= $k['bobot'] ?>)</span></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach($alternatif as $id_alt => $a): ?>
                  <tr>
                    <th><?= htmlspecialchars($a['nama']) ?></th>
                    <?php foreach($kriteria as $id_kri => $k): 
                      $val = $matrix[$id_alt][$id_kri] ?? "";
                    ?>
                      <td style="width:150px;">
                        <input
                          type="number"
                          step="0.01"
                          class="form-control"
                          name="nilai[<?= $id_alt ?>][<?= $id_kri ?>]"
                          value="<?= htmlspecialchars($val) ?>"
                          placeholder="angka">
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning">Simpan Matriks Nilai</button>
            <a href="hitung.php" class="btn btn-dark">Hitung SMART →</a>
          </div>
          <div class="small text-muted mt-2">
            Isi semua sel nilai untuk tiap kombinasi Alternatif × Kriteria. Skala bebas (mis. 1–5 atau 0–100) konsisten untuk semua.
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="mt-4 text-end">
    <a href="hitung.php" class="btn btn-outline-dark">Lihat Hasil Perhitungan & Ranking</a>
  </div>
</body>
</html>
