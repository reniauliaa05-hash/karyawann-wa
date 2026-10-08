<?php

require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| STATISTIK
|--------------------------------------------------------------------------
*/

$totalKaryawan =
    (int) $conn
    ->query(
        "SELECT COUNT(*) AS total
         FROM karyawan"
    )
    ->fetch_assoc()["total"];


$masuk =
    (int) $conn
    ->query(
        "SELECT COUNT(*) AS total
         FROM absensi
         WHERE tanggal = CURDATE()
         AND jam_masuk IS NOT NULL"
    )
    ->fetch_assoc()["total"];


$pulang =
    (int) $conn
    ->query(
        "SELECT COUNT(*) AS total
         FROM absensi
         WHERE tanggal = CURDATE()
         AND jam_pulang IS NOT NULL"
    )
    ->fetch_assoc()["total"];


$belum =
    max(
        0,
        $totalKaryawan - $masuk
    );


/*
|--------------------------------------------------------------------------
| LAPORAN
|--------------------------------------------------------------------------
*/

$tanggal =
    $_GET["tanggal"]
    ??
    date("Y-m-d");


$stmt =
    $conn->prepare(
        "SELECT
            a.tanggal,
            a.jam_masuk,
            a.jam_pulang,
            a.status,

            k.nik,
            k.nama,
            k.jabatan

         FROM absensi a

         INNER JOIN karyawan k
            ON k.id = a.id_karyawan

         WHERE a.tanggal = ?

         ORDER BY
            a.jam_masuk DESC"
    );


$stmt->bind_param(
    "s",
    $tanggal
);

$stmt->execute();

$laporan =
    $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Dashboard Admin</title>

<link
    rel="stylesheet"
    href="../assets/style.css"
>

</head>

<body>

<div class="app-shell">


<aside class="sidebar">

<div class="brand">

<div class="brand-icon">
K
</div>

<div>

<strong>
KaryawanHub
</strong>

<small>
ADMIN PANEL
</small>

</div>

</div>


<div class="nav-title">
Dashboard
</div>


<a
    class="nav-link active"
    href="index.php"
>
<span class="nav-icon">
▦
</span>

Dashboard

</a>


<a
    class="nav-link"
    href="../karyawan/index.php"
>
<span class="nav-icon">
♙
</span>

Data Karyawan

</a>


<a
    class="nav-link"
    href="../karyawan/tambah.php"
>
<span class="nav-icon">
＋
</span>

Tambah Karyawan

</a>


<div class="sidebar-bottom">

<a
    class="nav-link"
    href="../index.php"
>
<span class="nav-icon">
⌂
</span>

Beranda

</a>

</div>

</aside>


<main class="main">


<header class="topbar">

<div>

<div class="topbar-title">
Dashboard Admin
</div>

<div class="topbar-sub">
Ringkasan aktivitas karyawan hari ini
</div>

</div>


<div class="user-pill">

<div class="avatar">
A
</div>

<div
    style="
        font-size:11px;
        font-weight:700;
    "
>
Administrator
</div>

</div>

</header>


<div class="content">


<div class="page-head">

<div>

<h1>
Dashboard
</h1>

<p>
Pantau data karyawan dan kehadiran.
</p>

</div>

</div>


<div class="stats">


<div class="stat-card">

<div class="stat-top">

<span class="stat-label">
Total Karyawan
</span>

<span class="stat-icon">
♙
</span>

</div>

<div class="stat-number">
<?= $totalKaryawan ?>
</div>

<div class="stat-note">
Karyawan terdaftar
</div>

</div>


<div class="stat-card">

<div class="stat-top">

<span class="stat-label">
Sudah Masuk
</span>

<span class="stat-icon">
✓
</span>

</div>

<div class="stat-number">
<?= $masuk ?>
</div>

<div class="stat-note">
Absensi masuk hari ini
</div>

</div>


<div class="stat-card">

<div class="stat-top">

<span class="stat-label">
Belum Masuk
</span>

<span class="stat-icon">
!
</span>

</div>

<div class="stat-number">
<?= $belum ?>
</div>

<div class="stat-note">
Belum melakukan absensi
</div>

</div>


<div class="stat-card">

<div class="stat-top">

<span class="stat-label">
Sudah Pulang
</span>

<span class="stat-icon">
↪
</span>

</div>

<div class="stat-number">
<?= $pulang ?>
</div>

<div class="stat-note">
Absensi pulang selesai
</div>

</div>


</div>


<div class="card">


<div class="card-head">


<div>

<h2>
Laporan Absensi
</h2>

<p>
Filter berdasarkan tanggal
</p>

</div>


<form
    style="
        display:flex;
        gap:8px;
    "
>

<input
    class="input"
    type="date"
    name="tanggal"
    value="<?= htmlspecialchars($tanggal) ?>"
    style="width:auto;"
>

<button class="btn btn-primary">
Tampilkan
</button>

</form>


</div>


<div class="table-wrap">

<table>

<thead>

<tr>

<th>No</th>
<th>NIK</th>
<th>Nama</th>
<th>Jabatan</th>
<th>Jam Masuk</th>
<th>Jam Pulang</th>
<th>Status</th>

</tr>

</thead>


<tbody>

<?php

$no = 1;

while (
    $r =
    $laporan->fetch_assoc()
):

?>

<tr>

<td>
<?= $no++ ?>
</td>

<td>
<strong>
<?= htmlspecialchars(
    $r["nik"]
) ?>
</strong>
</td>

<td>
<?= htmlspecialchars(
    $r["nama"]
) ?>
</td>

<td>
<?= htmlspecialchars(
    $r["jabatan"]
) ?>
</td>

<td>
<?= htmlspecialchars(
    $r["jam_masuk"] ?? "-"
) ?>
</td>

<td>
<?= htmlspecialchars(
    $r["jam_pulang"] ?? "-"
) ?>
</td>

<td>

<span
    class="badge
    <?= empty($r["jam_pulang"])
        ? "badge-green"
        : "badge-blue"
    ?>"
>

<?= htmlspecialchars(
    $r["status"]
) ?>

</span>

</td>

</tr>

<?php endwhile; ?>


<?php if (
    $laporan->num_rows === 0
): ?>

<tr>

<td colspan="7">

<div class="empty">

Tidak ada data absensi
pada tanggal tersebut.

</div>

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</div>

</main>

</div>

</body>

</html>