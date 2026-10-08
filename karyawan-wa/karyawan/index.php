<?php

require_once __DIR__ . "/../config/database.php";

$keyword =
    trim($_GET["q"] ?? "");


$sql =
    "SELECT
        id,
        nik,
        nama,
        jabatan,
        no_hp,
        email,
        created_at
     FROM karyawan";

$params = [];
$types = "";


if ($keyword !== "") {

    $sql .=
        " WHERE
        nik LIKE ?
        OR nama LIKE ?
        OR jabatan LIKE ?";

    $like =
        "%" . $keyword . "%";

    $params = [
        $like,
        $like,
        $like
    ];

    $types = "sss";
}


$sql .=
    " ORDER BY id DESC";


$stmt =
    $conn->prepare($sql);


if ($types !== "") {

    $stmt->bind_param(
        $types,
        ...$params
    );
}


$stmt->execute();

$data =
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

<title>Data Karyawan</title>

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
<strong>KaryawanHub</strong>
<small>ADMIN PANEL</small>
</div>

</div>


<div class="nav-title">
Manajemen
</div>


<a
    class="nav-link"
    href="../admin/index.php"
>
<span class="nav-icon">▦</span>
Dashboard
</a>


<a
    class="nav-link active"
    href="index.php"
>
<span class="nav-icon">♙</span>
Data Karyawan
</a>


<a
    class="nav-link"
    href="tambah.php"
>
<span class="nav-icon">＋</span>
Tambah Karyawan
</a>


<div class="sidebar-bottom">

<a
    class="nav-link"
    href="../index.php"
>
<span class="nav-icon">⌂</span>
Beranda
</a>

</div>

</aside>


<main class="main">


<header class="topbar">

<button
    class="mobile-menu"
    onclick="
        document
        .querySelector('.sidebar')
        .classList
        .toggle('open')
    "
>
☰
</button>


<div>

<div class="topbar-title">
Data Karyawan
</div>

<div class="topbar-sub">
Kelola seluruh karyawan
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
Data Karyawan
</h1>

<p>
Tambah, edit, cari dan hapus data karyawan.
</p>

</div>


<div class="actions">

<a
    class="btn btn-primary"
    href="tambah.php"
>
＋ Tambah Karyawan
</a>

</div>

</div>


<form
    class="filter"
    method="GET"
>

<div class="search-wrap">

<input
    class="input"
    type="text"
    name="q"
    value="<?= htmlspecialchars($keyword) ?>"
    placeholder="Cari NIK, nama atau jabatan..."
>

<button
    class="btn btn-primary"
>
Cari
</button>

</div>

</form>


<div class="card">

<div class="table-wrap">

<table>

<thead>

<tr>

<th>No</th>
<th>NIK</th>
<th>Nama</th>
<th>Jabatan</th>
<th>WhatsApp</th>
<th>Email</th>
<th>Terdaftar</th>
<th>Aksi</th>

</tr>

</thead>


<tbody>

<?php

$no = 1;

while (
    $row =
    $data->fetch_assoc()
):

?>

<tr>

<td>
<?= $no++ ?>
</td>

<td>
<strong>
<?= htmlspecialchars($row["nik"]) ?>
</strong>
</td>

<td>
<?= htmlspecialchars($row["nama"]) ?>
</td>

<td>

<span class="badge badge-blue">

<?= htmlspecialchars(
    $row["jabatan"]
) ?>

</span>

</td>

<td>
<?= htmlspecialchars(
    $row["no_hp"]
) ?>
</td>

<td>
<?= htmlspecialchars(
    $row["email"]
) ?>
</td>

<td>
<?= htmlspecialchars(
    $row["created_at"]
) ?>
</td>

<td>

<div class="table-actions">

<a
    class="btn btn-sm btn-soft"
    href="edit.php?id=<?= $row["id"] ?>"
>
Edit
</a>


<a
    class="btn btn-sm btn-danger"
    href="hapus.php?id=<?= $row["id"] ?>"
    onclick="
        return confirm(
            'Yakin ingin menghapus karyawan ini?'
        );
    "
>
Hapus
</a>

</div>

</td>

</tr>

<?php endwhile; ?>


<?php if ($data->num_rows === 0): ?>

<tr>

<td colspan="8">

<div class="empty">

Belum ada data karyawan.

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