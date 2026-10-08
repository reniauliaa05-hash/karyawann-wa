<?php

session_start();

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../api/fonnte.php";

$error = "";
$success = "";
$waInfo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nik = trim($_POST["nik"] ?? "");
    $nama = trim($_POST["nama"] ?? "");
    $jabatan = trim($_POST["jabatan"] ?? "");
    $no_hp = trim($_POST["no_hp"] ?? "");
    $email = trim($_POST["email"] ?? "");

    $password = $_POST["password"] ?? "";
    $konfirmasi = $_POST["konfirmasi_password"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

    if (
        $nik === "" ||
        $nama === "" ||
        $jabatan === "" ||
        $no_hp === "" ||
        $email === "" ||
        $password === "" ||
        $konfirmasi === ""
    ) {

        $error = "Semua field wajib diisi.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    } elseif ($password !== $konfirmasi) {

        $error = "Konfirmasi password tidak sama.";

    } elseif (strlen($password) < 6) {

        $error = "Password minimal 6 karakter.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | NORMALISASI NOMOR WHATSAPP
        |--------------------------------------------------------------------------
        */

        $no_hp_db = preg_replace('/[^0-9]/', '', $no_hp);

        // 081234567890 -> 6281234567890
        if (substr($no_hp_db, 0, 1) === "0") {
            $no_hp_db = "62" . substr($no_hp_db, 1);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI NOMOR WHATSAPP
        |--------------------------------------------------------------------------
        */

        if (
            strlen($no_hp_db) < 10 ||
            strlen($no_hp_db) > 15 ||
            substr($no_hp_db, 0, 2) !== "62"
        ) {

            $error = "Nomor WhatsApp tidak valid. Contoh: 081234567890.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | CEK NIK / EMAIL
            |--------------------------------------------------------------------------
            */

            $cek = $conn->prepare(
                "SELECT id
                 FROM karyawan
                 WHERE nik = ? OR email = ?
                 LIMIT 1"
            );

            $cek->bind_param(
                "ss",
                $nik,
                $email
            );

            $cek->execute();

            $hasil = $cek->get_result();

            if ($hasil->num_rows > 0) {

                $error = "NIK atau email sudah terdaftar.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | HASH PASSWORD
                |--------------------------------------------------------------------------
                */

                $hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                /*
                |--------------------------------------------------------------------------
                | SIMPAN KARYAWAN
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare(
                    "INSERT INTO karyawan
                    (
                        nik,
                        nama,
                        jabatan,
                        no_hp,
                        email,
                        password
                    )
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "ssssss",
                    $nik,
                    $nama,
                    $jabatan,
                    $no_hp_db,
                    $email,
                    $hash
                );


                if ($stmt->execute()) {

                    /*
                    |--------------------------------------------------------------------------
                    | NOTIFIKASI KE KARYAWAN
                    |--------------------------------------------------------------------------
                    */

                    $pesanKaryawan =
                        "Halo *{$nama}* 👋\n\n" .
                        "Registrasi karyawan berhasil.\n\n" .
                        "━━━━━━━━━━━━━━\n" .
                        "NIK : {$nik}\n" .
                        "Nama : {$nama}\n" .
                        "Jabatan : {$jabatan}\n" .
                        "Email : {$email}\n" .
                        "━━━━━━━━━━━━━━\n\n" .
                        "Akun kamu sudah berhasil dibuat.\n" .
                        "Silakan login menggunakan NIK dan password yang telah dibuat.\n\n" .
                        "Terima kasih.";

                    $hasilWA = sendWhatsApp(
                        $no_hp_db,
                        $pesanKaryawan
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | CEK HASIL PENGIRIMAN WA KARYAWAN
                    |--------------------------------------------------------------------------
                    */

                    if (
                        isset($hasilWA["status"]) &&
                        $hasilWA["status"] === true
                    ) {

                        $waInfo = "Notifikasi WhatsApp berhasil dikirim ke {$no_hp_db}.";

                    } else {

                        $alasan = $hasilWA["reason"]
                            ?? $hasilWA["message"]
                            ?? "Tidak diketahui.";

                        $waInfo = "Data berhasil disimpan, tetapi WhatsApp karyawan gagal dikirim. Alasan: " . $alasan;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | NOTIFIKASI KE ADMIN
                    |--------------------------------------------------------------------------
                    */

                    if (
                        defined("ADMIN_WA") &&
                        ADMIN_WA !== "" &&
                        ADMIN_WA !== "628xxxxxxxxxx"
                    ) {

                        $pesanAdmin =
                            "🔔 *KARYAWAN BARU TERDAFTAR*\n\n" .
                            "━━━━━━━━━━━━━━\n" .
                            "Nama : {$nama}\n" .
                            "NIK : {$nik}\n" .
                            "Jabatan : {$jabatan}\n" .
                            "WhatsApp : {$no_hp_db}\n" .
                            "Email : {$email}\n" .
                            "━━━━━━━━━━━━━━\n\n" .
                            "Karyawan berhasil ditambahkan ke sistem.";

                        sendWhatsApp(
                            ADMIN_WA,
                            $pesanAdmin
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | PESAN BERHASIL
                    |--------------------------------------------------------------------------
                    */

                    $success =
                        "Registrasi berhasil. " .
                        $waInfo;

                } else {

                    $error =
                        "Registrasi gagal: " .
                        $stmt->error;
                }

                $stmt->close();
            }

            $cek->close();
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

<title>Register Karyawan</title>

<link
    rel="stylesheet"
    href="../assets/style.css"
>

</head>

<body>

<div class="login-page">

<div class="login-card">

<section class="login-info">

<div class="brand">

<div class="brand-icon">
K
</div>

<div>
<strong>KaryawanHub</strong>
<small>REGISTER KARYAWAN</small>
</div>

</div>

<h1>
Buat akun karyawan.
</h1>

<p>
Lengkapi data karyawan dengan benar.
Setelah berhasil, sistem dapat mengirim
notifikasi melalui WhatsApp.
</p>

<div class="feature">
<span class="feature-dot">✓</span>
NIK unik
</div>

<div class="feature">
<span class="feature-dot">✓</span>
Email unik
</div>

<div class="feature">
<span class="feature-dot">✓</span>
Password aman
</div>

<div class="feature">
<span class="feature-dot">✓</span>
Notifikasi WhatsApp
</div>

</section>


<section class="login-form">

<h2>
Register Karyawan
</h2>

<p class="subtitle">
Isi semua informasi berikut.
</p>


<?php if ($error): ?>

<div class="alert alert-error">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>


<?php if ($success): ?>

<div class="alert alert-success">

<?= htmlspecialchars($success) ?>

</div>

<?php endif; ?>


<form method="POST">

<div class="form-grid">


<div class="form-group">

<label>
NIK
</label>

<input
    class="input"
    type="text"
    name="nik"
    value="<?= htmlspecialchars($_POST["nik"] ?? "") ?>"
    required
>

</div>


<div class="form-group">

<label>
Nama Lengkap
</label>

<input
    class="input"
    type="text"
    name="nama"
    value="<?= htmlspecialchars($_POST["nama"] ?? "") ?>"
    required
>

</div>


<div class="form-group">

<label>
Jabatan
</label>

<input
    class="input"
    type="text"
    name="jabatan"
    value="<?= htmlspecialchars($_POST["jabatan"] ?? "") ?>"
    required
>

</div>


<div class="form-group">

<label>
Nomor WhatsApp
</label>

<input
    class="input"
    type="text"
    name="no_hp"
    value="<?= htmlspecialchars($_POST["no_hp"] ?? "") ?>"
    placeholder="08xxxxxxxxxx"
    required
>

</div>


<div class="form-group full">

<label>
Email
</label>

<input
    class="input"
    type="email"
    name="email"
    value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
    required
>

</div>


<div class="form-group">

<label>
Password
</label>

<input
    class="input"
    type="password"
    name="password"
    required
>

</div>


<div class="form-group">

<label>
Konfirmasi Password
</label>

<input
    class="input"
    type="password"
    name="konfirmasi_password"
    required
>

</div>


</div>


<div class="form-actions">

<a
    class="btn btn-gray"
    href="../index.php"
>
    Kembali
</a>

<button
    class="btn btn-primary"
    type="submit"
>
    Daftar Karyawan
</button>

</div>

</form>

</section>

</div>

</div>

</body>

</html>