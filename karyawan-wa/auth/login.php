<?php

session_start();

require_once __DIR__ . "/../config/database.php";


// ======================================================
// JIKA SUDAH LOGIN
// ======================================================

if (isset($_SESSION["karyawan_id"])) {

    header("Location: ../absensi/index.php");

    exit;
}


$error = "";

$nikInput = "";


// ======================================================
// PROSES LOGIN
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    // Ambil input
    $nik = trim($_POST["nik"] ?? "");

    $password = $_POST["password"] ?? "";

    $nikInput = $nik;


    // ==================================================
    // VALIDASI INPUT
    // ==================================================

    if ($nik === "" && $password === "") {

        $error =
            "NIK dan password wajib diisi.";

    }

    elseif ($nik === "") {

        $error =
            "NIK wajib diisi.";

    }

    elseif ($password === "") {

        $error =
            "Password wajib diisi.";

    }


    // ==================================================
    // PROSES CEK DATABASE
    // ==================================================

    else {

        $stmt = $conn->prepare(
            "SELECT
                id,
                nik,
                nama,
                jabatan,
                no_hp,
                email,
                password
             FROM karyawan
             WHERE nik = ?
             LIMIT 1"
        );


        if (!$stmt) {

            $error =
                "Terjadi kesalahan pada sistem database.";

        }

        else {

            $stmt->bind_param(
                "s",
                $nik
            );


            $stmt->execute();


            $result =
                $stmt->get_result();


            $user =
                $result->fetch_assoc();


            $stmt->close();


            // ==================================================
            // CEK USER DAN PASSWORD
            // ==================================================

            if (
                $user &&
                !empty($user["password"]) &&
                password_verify(
                    $password,
                    $user["password"]
                )
            ) {


                // ==================================================
                // REGENERATE SESSION
                // ==================================================

                session_regenerate_id(true);


                // ==================================================
                // SIMPAN DATA KARYAWAN KE SESSION
                // ==================================================

                $_SESSION["karyawan_id"] =
                    (int) $user["id"];


                $_SESSION["karyawan_nik"] =
                    $user["nik"];


                $_SESSION["karyawan_nama"] =
                    $user["nama"];


                $_SESSION["karyawan_jabatan"] =
                    $user["jabatan"];


                $_SESSION["karyawan_wa"] =
                    $user["no_hp"];


                $_SESSION["karyawan_email"] =
                    $user["email"];


                // ==================================================
                // REDIRECT ABSENSI
                // ==================================================

                header(
                    "Location: ../absensi/index.php"
                );

                exit;

            }


            // ==================================================
            // LOGIN GAGAL
            // ==================================================

            else {

                $error =
                    "NIK atau password salah.";
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

<title>Login Karyawan - KaryawanHub</title>


<link
    rel="stylesheet"
    href="../assets/style.css"
>


<style>

/* ======================================================
   LOGIN PASSWORD
====================================================== */

.password-wrapper {

    position: relative;

}


.password-wrapper .input {

    padding-right: 50px;

}


.password-toggle {

    position: absolute;

    right: 14px;

    top: 50%;

    transform: translateY(-50%);

    border: none;

    background: transparent;

    cursor: pointer;

    color: #667085;

    font-size: 17px;

    padding: 5px;

}


.password-toggle:hover {

    color: #4f46e5;

}


/* ======================================================
   ERROR
====================================================== */

.login-error {

    background: #fef2f2;

    border: 1px solid #fecaca;

    color: #b91c1c;

    border-radius: 10px;

    padding: 12px 14px;

    font-size: 13px;

    line-height: 1.5;

    margin-bottom: 18px;

}


/* ======================================================
   LOGIN INFO
====================================================== */

.login-help {

    margin-top: 18px;

    padding: 12px 14px;

    background: #f5f3ff;

    border: 1px solid #ddd6fe;

    border-radius: 10px;

    color: #5b21b6;

    font-size: 12px;

    line-height: 1.6;

}


/* ======================================================
   BUTTON
====================================================== */

.login-submit {

    width: 100%;

    margin-top: 20px;

    border: none;

    cursor: pointer;

}


/* ======================================================
   MOBILE
====================================================== */

@media (max-width: 600px) {

    .login-card {

        margin: 15px;

    }

}

</style>

</head>


<body>


<div class="login-page">


<div class="login-card">


<!-- ==================================================
     BAGIAN INFORMASI
================================================== -->

<section class="login-info">


<!-- BRAND -->

<div class="brand">


<div class="brand-icon">
K
</div>


<div>

<strong>
KaryawanHub
</strong>

<small>
ABSENSI KARYAWAN
</small>

</div>


</div>


<!-- TITLE -->

<h1>

Masuk ke akunmu.

</h1>


<p>

Gunakan NIK dan password yang telah
didaftarkan untuk melakukan absensi
masuk dan pulang.

</p>


<!-- FEATURE -->

<div class="feature">

<span class="feature-dot">
✓
</span>

Login menggunakan NIK

</div>


<div class="feature">

<span class="feature-dot">
✓
</span>

Password terenkripsi

</div>


<div class="feature">

<span class="feature-dot">
✓
</span>

Absensi masuk dan pulang

</div>


<div class="feature">

<span class="feature-dot">
✓
</span>

Notifikasi WhatsApp

</div>


</section>


<!-- ==================================================
     FORM LOGIN
================================================== -->

<section class="login-form">


<h2>

Login Karyawan

</h2>


<p class="subtitle">

Masukkan NIK dan password kamu.

</p>


<!-- ==================================================
     ERROR
================================================== -->

<?php if ($error !== ""): ?>

<div class="login-error">

<?= htmlspecialchars(
    $error,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

<?php endif; ?>


<!-- ==================================================
     FORM
================================================== -->

<form
    method="POST"
    autocomplete="off"
>


<!-- NIK -->

<div class="form-group">


<label for="nik">

NIK

</label>


<input
    class="input"
    id="nik"
    type="text"
    name="nik"
    value="<?= htmlspecialchars(
        $nikInput,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    placeholder="Masukkan NIK"
    autocomplete="username"
    required
    autofocus
>


</div>


<!-- PASSWORD -->

<div
    class="form-group"
    style="margin-top:15px;"
>


<label for="password">

Password

</label>


<div class="password-wrapper">


<input
    class="input"
    id="password"
    type="password"
    name="password"
    placeholder="Masukkan password"
    autocomplete="current-password"
    required
>


<button
    type="button"
    class="password-toggle"
    id="togglePassword"
    aria-label="Tampilkan password"
>

👁

</button>


</div>


</div>


<!-- BUTTON -->

<button
    type="submit"
    class="btn btn-primary login-submit"
>

Masuk ke Absensi

</button>


</form>


<!-- ==================================================
     INFO
================================================== -->

<div class="login-help">

💡 Setelah berhasil login, kamu akan
langsung diarahkan ke halaman absensi
untuk melakukan <strong>Absen Masuk</strong>
atau <strong>Absen Pulang</strong>.

</div>


<!-- ==================================================
     REGISTER
================================================== -->

<p
    style="
        font-size:12px;
        color:#667085;
        margin-top:22px;
    "
>


Belum punya akun?


<a
    class="back-link"
    href="register.php"
>

Register sekarang

</a>


</p>


<!-- ==================================================
     KEMBALI
================================================== -->

<a
    class="back-link"
    href="../index.php"
>

← Kembali ke halaman utama

</a>


</section>


</div>

</div>


<script>

// ======================================================
// TOGGLE PASSWORD
// ======================================================

const togglePassword =
    document.getElementById(
        "togglePassword"
    );


const passwordInput =
    document.getElementById(
        "password"
    );


togglePassword.addEventListener(
    "click",
    function () {


        if (
            passwordInput.type ===
            "password"
        ) {

            passwordInput.type =
                "text";

            togglePassword.textContent =
                "🙈";

            togglePassword.setAttribute(
                "aria-label",
                "Sembunyikan password"
            );

        }

        else {

            passwordInput.type =
                "password";

            togglePassword.textContent =
                "👁";

            togglePassword.setAttribute(
                "aria-label",
                "Tampilkan password"
            );
        }

    }
);

</script>


</body>

</html>