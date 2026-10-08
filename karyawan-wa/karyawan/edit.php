<?php

session_start();

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../api/fonnte.php";


// ======================================================
// AMBIL ID
// ======================================================

$id = (int) (
    $_GET["id"]
    ?? $_POST["id"]
    ?? 0
);

if ($id <= 0) {
    die("ID karyawan tidak valid.");
}


// ======================================================
// AMBIL DATA KARYAWAN
// ======================================================

$stmt = $conn->prepare(
    "SELECT *
     FROM karyawan
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$stmt->close();


if (!$row) {
    die("Data karyawan tidak ditemukan.");
}


// ======================================================
// VARIABEL
// ======================================================

$error = "";

$success = false;

$waBerhasil = false;

$waReason = "";


// ======================================================
// PROSES FORM EDIT
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nik = trim($_POST["nik"] ?? "");

    $nama = trim($_POST["nama"] ?? "");

    $jabatan = trim($_POST["jabatan"] ?? "");

    $no_hp = trim($_POST["no_hp"] ?? "");

    $email = trim($_POST["email"] ?? "");

    $password = $_POST["password"] ?? "";


    // ==================================================
    // VALIDASI WAJIB
    // ==================================================

    if (
        $nik === "" ||
        $nama === "" ||
        $jabatan === "" ||
        $no_hp === "" ||
        $email === ""
    ) {

        $error = "Semua field wajib diisi.";

    }


    // ==================================================
    // VALIDASI EMAIL
    // ==================================================

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    }


    else {

        // ==================================================
        // NORMALISASI NOMOR WHATSAPP
        // ==================================================

        $no_hp = preg_replace(
            "/[^0-9]/",
            "",
            $no_hp
        );


        // 0812xxxx → 62812xxxx
        if (substr($no_hp, 0, 1) === "0") {

            $no_hp =
                "62" .
                substr($no_hp, 1);
        }


        // 812xxxx → 62812xxxx
        elseif (substr($no_hp, 0, 1) === "8") {

            $no_hp =
                "62" .
                $no_hp;
        }


        // ==================================================
        // VALIDASI NOMOR
        // ==================================================

        if (
            strlen($no_hp) < 10 ||
            strlen($no_hp) > 15
        ) {

            $error =
                "Nomor WhatsApp tidak valid.";

        }


        else {

            // ==================================================
            // CEK NIK / EMAIL DUPLIKAT
            // ==================================================

            $cek = $conn->prepare(
                "SELECT id
                 FROM karyawan
                 WHERE
                    (nik = ? OR email = ?)
                    AND id <> ?
                 LIMIT 1"
            );

            $cek->bind_param(
                "ssi",
                $nik,
                $email,
                $id
            );

            $cek->execute();

            $hasilCek = $cek->get_result();


            if ($hasilCek->num_rows > 0) {

                $error =
                    "NIK atau email sudah digunakan.";
            }


            $cek->close();


            // ==================================================
            // UPDATE JIKA TIDAK ADA ERROR
            // ==================================================

            if ($error === "") {


                // ==================================================
                // JIKA PASSWORD DIUBAH
                // ==================================================

                if ($password !== "") {


                    if (strlen($password) < 6) {

                        $error =
                            "Password minimal 6 karakter.";

                    }


                    else {

                        $passwordHash =
                            password_hash(
                                $password,
                                PASSWORD_DEFAULT
                            );


                        $update = $conn->prepare(
                            "UPDATE karyawan
                             SET
                                nik = ?,
                                nama = ?,
                                jabatan = ?,
                                no_hp = ?,
                                email = ?,
                                password = ?
                             WHERE id = ?"
                        );


                        $update->bind_param(
                            "ssssssi",
                            $nik,
                            $nama,
                            $jabatan,
                            $no_hp,
                            $email,
                            $passwordHash,
                            $id
                        );
                    }

                }


                // ==================================================
                // PASSWORD TIDAK DIUBAH
                // ==================================================

                else {

                    $update = $conn->prepare(
                        "UPDATE karyawan
                         SET
                            nik = ?,
                            nama = ?,
                            jabatan = ?,
                            no_hp = ?,
                            email = ?
                         WHERE id = ?"
                    );


                    $update->bind_param(
                        "sssssi",
                        $nik,
                        $nama,
                        $jabatan,
                        $no_hp,
                        $email,
                        $id
                    );
                }


                // ==================================================
                // SIMPAN DATA
                // ==================================================

                if ($error === "") {


                    if ($update->execute()) {

                        $success = true;

                        $update->close();


                        // ==================================================
                        // PESAN WHATSAPP
                        // ==================================================

                        $pesanWA =
                            "🔔 *DATA KARYAWAN DIPERBARUI*\n\n" .
                            "Halo *" . $nama . "*," .
                            "\n\n" .
                            "Data karyawan kamu telah diperbarui oleh admin." .
                            "\n\n" .
                            "📋 *Data Terbaru*\n" .
                            "NIK: " . $nik . "\n" .
                            "Jabatan: " . $jabatan . "\n" .
                            "WhatsApp: " . $no_hp . "\n" .
                            "Email: " . $email . "\n\n" .
                            "Silakan gunakan data terbaru kamu saat login." .
                            "\n\n" .
                            "Terima kasih.\n" .
                            "— KaryawanHub";


                        // ==================================================
                        // KIRIM WA
                        // ==================================================

                        $hasilWA = sendWhatsApp(
                            $no_hp,
                            $pesanWA
                        );


                        // ==================================================
                        // CEK HASIL WA
                        // ==================================================

                        if (is_array($hasilWA)) {

                            if (
                                isset($hasilWA["status"]) &&
                                (
                                    $hasilWA["status"] === true ||
                                    $hasilWA["status"] === "true" ||
                                    $hasilWA["status"] === 1 ||
                                    $hasilWA["status"] === "1"
                                )
                            ) {

                                $waBerhasil = true;

                            }


                            if (isset($hasilWA["reason"])) {

                                $waReason =
                                    $hasilWA["reason"];

                            }

                            elseif (isset($hasilWA["message"])) {

                                $waReason =
                                    $hasilWA["message"];

                            }

                        }


                        if ($waBerhasil) {

                            $waReason =
                                "Notifikasi WhatsApp berhasil dikirim.";

                        }

                        elseif ($waReason === "") {

                            $waReason =
                                "Notifikasi WhatsApp gagal dikirim.";
                        }


                        // ==================================================
                        // UPDATE DATA YANG DITAMPILKAN DI FORM
                        // ==================================================

                        $row["nik"] = $nik;

                        $row["nama"] = $nama;

                        $row["jabatan"] = $jabatan;

                        $row["no_hp"] = $no_hp;

                        $row["email"] = $email;

                    }


                    else {

                        $error =
                            "Gagal memperbarui data: " .
                            $conn->error;
                    }
                }
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

<title>Edit Karyawan</title>


<link
    rel="stylesheet"
    href="../assets/style.css"
>


<style>

/* ======================================================
   HALAMAN EDIT TANPA SIDEBAR
====================================================== */

body {
    margin: 0;
}


/* ======================================================
   MAIN
====================================================== */

.main {
    width: 100%;
    min-height: 100vh;
    margin-left: 0;
}


/* ======================================================
   CONTENT
====================================================== */

.content {
    width: 100%;
    max-width: 1100px;
    margin: 0 auto;
    padding: 30px;
    box-sizing: border-box;
}


/* ======================================================
   NOTIFIKASI
====================================================== */

.notification-overlay {

    position: fixed;

    inset: 0;

    background: rgba(15, 23, 42, 0.55);

    display: flex;

    align-items: center;

    justify-content: center;

    z-index: 99999;

    padding: 20px;

    box-sizing: border-box;
}


.notification-box {

    width: 100%;

    max-width: 430px;

    background: #ffffff;

    border-radius: 18px;

    padding: 30px;

    text-align: center;

    box-sizing: border-box;

    box-shadow:
        0 20px 60px
        rgba(0, 0, 0, 0.20);
}


.notification-icon {

    width: 65px;

    height: 65px;

    margin: 0 auto 18px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 32px;

    font-weight: bold;
}


.notification-success {

    background: #dcfce7;

    color: #16a34a;
}


.notification-warning {

    background: #fef3c7;

    color: #d97706;
}


.notification-box h2 {

    margin: 0 0 12px;

    color: #111827;

    font-size: 22px;
}


.notification-box p {

    margin: 0;

    color: #6b7280;

    font-size: 15px;

    line-height: 1.7;
}


.notification-button {

    width: 100%;

    margin-top: 22px;

    padding: 12px 20px;

    border: 0;

    border-radius: 10px;

    background: #4f46e5;

    color: #ffffff;

    font-size: 15px;

    font-weight: 600;

    cursor: pointer;
}


.notification-button:hover {

    background: #4338ca;
}


/* ======================================================
   MOBILE
====================================================== */

@media (max-width: 700px) {

    .content {
        padding: 20px;
    }

}

</style>

</head>


<body>


<!-- ======================================================
     MAIN
====================================================== -->

<main class="main">


<!-- ======================================================
     TOPBAR
====================================================== -->

<header class="topbar">

<div>

<div class="topbar-title">
Edit Karyawan
</div>

<div class="topbar-sub">
Perbarui informasi karyawan
</div>

</div>

</header>


<!-- ======================================================
     CONTENT
====================================================== -->

<div class="content">


<!-- PAGE HEAD -->

<div class="page-head">

<div>


</div>

</div>


<!-- ======================================================
     FORM CARD
====================================================== -->

<div class="card form-card">


<div class="card-body">


<!-- ERROR -->

<?php if ($error !== ""): ?>

<div class="alert alert-error">

<?= htmlspecialchars(
    $error,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

<?php endif; ?>


<!-- FORM -->

<form
    method="POST"
    autocomplete="off"
>


<input
    type="hidden"
    name="id"
    value="<?= $id ?>"
>


<div class="form-grid">


<!-- ==================================================
     NIK
================================================== -->

<div class="form-group">

<label>
NIK
</label>

<input
    class="input"
    type="text"
    name="nik"
    value="<?= htmlspecialchars(
        $row["nik"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    required
>

</div>


<!-- ==================================================
     NAMA
================================================== -->

<div class="form-group">

<label>
Nama Lengkap
</label>

<input
    class="input"
    type="text"
    name="nama"
    value="<?= htmlspecialchars(
        $row["nama"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    required
>

</div>


<!-- ==================================================
     JABATAN
================================================== -->

<div class="form-group">

<label>
Jabatan
</label>

<input
    class="input"
    type="text"
    name="jabatan"
    value="<?= htmlspecialchars(
        $row["jabatan"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    required
>

</div>


<!-- ==================================================
     WHATSAPP
================================================== -->

<div class="form-group">

<label>
No. WhatsApp
</label>

<input
    class="input"
    type="text"
    name="no_hp"
    value="<?= htmlspecialchars(
        $row["no_hp"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    placeholder="081234567890"
    required
>

<div class="help">
Contoh: 081234567890
</div>

</div>


<!-- ==================================================
     EMAIL
================================================== -->

<div class="form-group full">

<label>
Email
</label>

<input
    class="input"
    type="email"
    name="email"
    value="<?= htmlspecialchars(
        $row["email"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    required
>

</div>


<!-- ==================================================
     PASSWORD
================================================== -->

<div class="form-group full">

<label>
Password Baru
</label>

<input
    class="input"
    type="password"
    name="password"
    minlength="6"
>

<div class="help">
Kosongkan jika password tidak ingin diubah.
</div>

</div>


</div>


<!-- ==================================================
     ACTION
================================================== -->

<div class="form-actions">


<a
    class="btn btn-gray"
    href="index.php"
>
Batal
</a>


<button
    type="submit"
    class="btn btn-primary"
>
Simpan Perubahan
</button>


</div>


</form>


</div>

</div>


</div>

</main>


<!-- ======================================================
     POPUP HASIL EDIT
====================================================== -->

<?php if ($success): ?>

<div class="notification-overlay">


<div class="notification-box">


<?php if ($waBerhasil): ?>

<div class="notification-icon notification-success">
✓
</div>

<h2>
Berhasil!
</h2>

<p>

Data karyawan berhasil diperbarui.

<br><br>

WhatsApp berhasil dikirim ke nomor:

<br>

<strong>
<?= htmlspecialchars(
    $row["no_hp"],
    ENT_QUOTES,
    "UTF-8"
) ?>
</strong>

</p>


<?php else: ?>

<div class="notification-icon notification-warning">
!
</div>

<h2>
Data Berhasil Diperbarui
</h2>

<p>

Data karyawan berhasil diperbarui.

<br><br>

Namun WhatsApp gagal dikirim.

<br><br>

<strong>
<?= htmlspecialchars(
    $waReason,
    ENT_QUOTES,
    "UTF-8"
) ?>
</strong>

</p>

<?php endif; ?>


<button
    type="button"
    class="notification-button"
    onclick="kembaliKeData()"
>
OK
</button>


</div>

</div>


<script>

function kembaliKeData() {

    window.location.href = "index.php";

}

</script>

<?php endif; ?>


</body>

</html>