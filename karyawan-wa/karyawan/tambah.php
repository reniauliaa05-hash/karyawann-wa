<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../api/fonnte.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nik =
        trim($_POST["nik"] ?? "");

    $nama =
        trim($_POST["nama"] ?? "");

    $jabatan =
        trim($_POST["jabatan"] ?? "");

    $no_hp =
        trim($_POST["no_hp"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $password =
        $_POST["password"] ?? "";


    if (
        $nik === "" ||
        $nama === "" ||
        $jabatan === "" ||
        $no_hp === "" ||
        $email === "" ||
        $password === ""
    ) {

        $error =
            "Semua field wajib diisi.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Format email tidak valid.";

    } elseif (
        strlen($password) < 6
    ) {

        $error =
            "Password minimal 6 karakter.";

    } else {

        $no_hp =
            preg_replace(
                '/[^0-9]/',
                '',
                $no_hp
            );


        $cek =
            $conn->prepare(
                "SELECT id
                 FROM karyawan
                 WHERE nik = ?
                 OR email = ?"
            );

        $cek->bind_param(
            "ss",
            $nik,
            $email
        );

        $cek->execute();


        if (
            $cek
            ->get_result()
            ->num_rows > 0
        ) {

            $error =
                "NIK atau email sudah digunakan.";

        } else {

            $hash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            $stmt =
                $conn->prepare(
                    "INSERT INTO karyawan
                    (
                        nik,
                        nama,
                        jabatan,
                        no_hp,
                        email,
                        password
                    )
                    VALUES
                    (?, ?, ?, ?, ?, ?)"
                );


            $stmt->bind_param(
                "ssssss",
                $nik,
                $nama,
                $jabatan,
                $no_hp,
                $email,
                $hash
            );


            if ($stmt->execute()) {

                sendWhatsApp(
                    $no_hp,
                    "Halo {$nama},\n\n" .
                    "Data karyawan kamu berhasil " .
                    "ditambahkan.\n\n" .
                    "NIK: {$nik}\n" .
                    "Jabatan: {$jabatan}"
                );


                if (
                    ADMIN_WA !== "" &&
                    ADMIN_WA !== "628xxxxxxxxxx"
                ) {

                    sendWhatsApp(
                        ADMIN_WA,
                        "Karyawan baru ditambahkan.\n\n" .
                        "Nama: {$nama}\n" .
                        "NIK: {$nik}\n" .
                        "Jabatan: {$jabatan}"
                    );
                }


                header(
                    "Location: index.php"
                );

                exit;

            } else {

                $error =
                    "Gagal menyimpan data.";
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Tambah Karyawan</title>

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
▦ &nbsp; Dashboard
</a>


<a
    class="nav-link"
    href="index.php"
>
♙ &nbsp; Data Karyawan
</a>


<a
    class="nav-link active"
    href="tambah.php"
>
＋ &nbsp; Tambah Karyawan
</a>


</aside>


<main class="main">


<header class="topbar">

<div>

<div class="topbar-title">
Tambah Karyawan
</div>

<div class="topbar-sub">
Masukkan data karyawan baru
</div>

</div>

</header>


<div class="content">


<div class="page-head">

<div>

<h1>
Tambah Karyawan
</h1>

<p>
Lengkapi informasi karyawan.
</p>

</div>

</div>


<div class="card form-card">

<div class="card-body">


<?php if ($error): ?>

<div class="alert alert-error">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>


<form method="POST">


<div class="form-grid">


<div class="form-group">

<label>NIK</label>

<input
    class="input"
    name="nik"
    required
>

</div>


<div class="form-group">

<label>Nama Lengkap</label>

<input
    class="input"
    name="nama"
    required
>

</div>


<div class="form-group">

<label>Jabatan</label>

<input
    class="input"
    name="jabatan"
    required
>

</div>


<div class="form-group">

<label>No. WhatsApp</label>

<input
    class="input"
    name="no_hp"
    placeholder="08xxxxxxxxxx"
    required
>

</div>


<div class="form-group full">

<label>Email</label>

<input
    class="input"
    type="email"
    name="email"
    required
>

</div>


<div class="form-group full">

<label>Password</label>

<input
    class="input"
    type="password"
    name="password"
    required
>

<div class="help">
Password akan disimpan menggunakan password_hash().
</div>

</div>


</div>


<div class="form-actions">

<a
    class="btn btn-gray"
    href="index.php"
>
Batal
</a>

<button
    class="btn btn-primary"
>
Simpan Karyawan
</button>

</div>


</form>

</div>

</div>

</div>

</main>

</div>

</body>

</html>