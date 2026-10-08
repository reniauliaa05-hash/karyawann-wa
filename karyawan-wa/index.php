<?php

session_start();

date_default_timezone_set("Asia/Jakarta");

require_once __DIR__ . "/config/database.php";


/*
|--------------------------------------------------------------------------
| STATUS LOGIN AWAL
|--------------------------------------------------------------------------
*/

$is_karyawan = (
    isset($_SESSION["karyawan_id"]) &&
    (int) $_SESSION["karyawan_id"] > 0
);


$is_admin = (
    isset($_SESSION["admin_id"]) &&
    (int) $_SESSION["admin_id"] > 0
);


/*
|--------------------------------------------------------------------------
| VALIDASI SESSION KARYAWAN
|--------------------------------------------------------------------------
|
| Kita tidak langsung percaya nama yang ada di session.
| ID karyawan dicek kembali ke database.
|
*/

$nama_karyawan = "";

$karyawan_id = 0;


if ($is_karyawan) {

    $karyawan_id =
        (int) $_SESSION["karyawan_id"];


    $stmt =
        $conn->prepare(
            "SELECT
                id,
                nik,
                nama,
                jabatan,
                no_hp,
                email
             FROM karyawan
             WHERE id = ?
             LIMIT 1"
        );


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $karyawan_id
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        $data_karyawan =
            $result->fetch_assoc();


        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | DATA KARYAWAN DITEMUKAN
        |--------------------------------------------------------------------------
        */

        if ($data_karyawan) {

            $nama_karyawan =
                $data_karyawan["nama"];


            /*
            |--------------------------------------------------------------------------
            | SINKRONKAN SESSION
            |--------------------------------------------------------------------------
            */

            $_SESSION["karyawan_id"] =
                (int) $data_karyawan["id"];

            $_SESSION["karyawan_nik"] =
                $data_karyawan["nik"];

            $_SESSION["karyawan_nama"] =
                $data_karyawan["nama"];

            $_SESSION["karyawan_jabatan"] =
                $data_karyawan["jabatan"];

            $_SESSION["karyawan_wa"] =
                $data_karyawan["no_hp"];

            $_SESSION["karyawan_email"] =
                $data_karyawan["email"];

            $_SESSION["role"] =
                "karyawan";


        } else {

            /*
            |--------------------------------------------------------------------------
            | SESSION SUDAH TIDAK VALID
            |--------------------------------------------------------------------------
            */

            unset(
                $_SESSION["karyawan_id"],
                $_SESSION["karyawan_nik"],
                $_SESSION["karyawan_nama"],
                $_SESSION["karyawan_jabatan"],
                $_SESSION["karyawan_wa"],
                $_SESSION["karyawan_email"],
                $_SESSION["role"]
            );


            $is_karyawan = false;

            $karyawan_id = 0;
        }
    }
}


/*
|--------------------------------------------------------------------------
| DATA ADMIN
|--------------------------------------------------------------------------
*/

$nama_admin = "";


if ($is_admin) {

    $nama_admin =
        $_SESSION["admin_nama"]
        ??
        $_SESSION["admin_username"]
        ??
        "Admin";


    $nama_admin =
        trim(
            (string) $nama_admin
        );


    if ($nama_admin === "") {

        $nama_admin = "Admin";
    }
}


/*
|--------------------------------------------------------------------------
| DATA KARYAWAN DEFAULT
|--------------------------------------------------------------------------
*/

if ($nama_karyawan === "") {

    $nama_karyawan =
        "Karyawan";
}


/*
|--------------------------------------------------------------------------
| STATUS LOGIN
|--------------------------------------------------------------------------
*/

if ($is_karyawan) {

    $status_login =
        "karyawan";

} elseif ($is_admin) {

    $status_login =
        "admin";

} else {

    $status_login =
        "tamu";
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

<title>
    KaryawanHub - Beranda
</title>


<style>

/* ======================================================
   RESET
====================================================== */

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {

    margin: 0;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #f5f3ff,
            #eef2ff,
            #f8fafc
        );

    min-height: 100vh;

    color: #111827;
}


/* ======================================================
   PAGE
====================================================== */

.home-page {

    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 30px 20px;
}


/* ======================================================
   CARD
====================================================== */

.home-card {

    width: 100%;

    max-width: 1000px;

    display: grid;

    grid-template-columns:
        1.05fr
        .95fr;

    background: #ffffff;

    border-radius: 26px;

    overflow: hidden;

    box-shadow:
        0 25px 70px
        rgba(79, 70, 229, .15);
}


/* ======================================================
   LEFT
====================================================== */

.home-info {

    padding: 48px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #1e1b4b,
            #3730a3,
            #4f46e5
        );

    position: relative;

    overflow: hidden;
}


.home-info::before {

    content: "";

    position: absolute;

    width: 220px;

    height: 220px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.06);

    top: -90px;

    right: -70px;
}


.home-info::after {

    content: "";

    position: absolute;

    width: 160px;

    height: 160px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.05);

    bottom: -70px;

    left: -60px;
}


/* ======================================================
   BRAND
====================================================== */

.brand {

    position: relative;

    z-index: 2;

    display: flex;

    align-items: center;

    gap: 13px;

    margin-bottom: 50px;
}


.brand-icon {

    width: 48px;

    height: 48px;

    border-radius: 14px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(255,255,255,.14);

    border:
        1px solid
        rgba(255,255,255,.15);

    font-size: 21px;

    font-weight: 800;
}


.brand strong {

    display: block;

    font-size: 19px;

    letter-spacing: .2px;
}


.brand small {

    display: block;

    margin-top: 3px;

    font-size: 10px;

    letter-spacing: 1.4px;

    opacity: .7;
}


/* ======================================================
   TITLE
====================================================== */

.home-info h1 {

    position: relative;

    z-index: 2;

    margin: 0 0 16px;

    font-size: 35px;

    line-height: 1.2;
}


.home-info p {

    position: relative;

    z-index: 2;

    margin: 0 0 28px;

    max-width: 480px;

    color:
        rgba(255,255,255,.76);

    font-size: 14px;

    line-height: 1.8;
}


/* ======================================================
   FEATURES
====================================================== */

.features {

    position: relative;

    z-index: 2;
}


.feature {

    display: flex;

    align-items: center;

    gap: 11px;

    margin-bottom: 13px;

    color:
        rgba(255,255,255,.9);

    font-size: 13px;
}


.feature-dot {

    width: 24px;

    height: 24px;

    flex-shrink: 0;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(255,255,255,.13);

    font-size: 12px;

    font-weight: 700;
}


/* ======================================================
   RIGHT
====================================================== */

.home-menu {

    padding: 48px;

    display: flex;

    flex-direction: column;

    justify-content: center;
}


.home-menu h2 {

    margin: 0;

    color: #111827;

    font-size: 27px;
}


.subtitle {

    margin:
        8px
        0
        28px;

    color: #6b7280;

    font-size: 13px;

    line-height: 1.6;
}


/* ======================================================
   USER STATUS
====================================================== */

.login-status {

    padding: 14px 15px;

    margin-bottom: 18px;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #f5f3ff,
            #eef2ff
        );

    border:
        1px solid
        #e0e7ff;

    color: #3730a3;

    font-size: 12px;

    line-height: 1.5;
}


.login-status strong {

    color: #312e81;

    font-weight: 700;
}


/* ======================================================
   MENU
====================================================== */

.menu-list {

    display: flex;

    flex-direction: column;

    gap: 11px;
}


.menu-button {

    width: 100%;

    min-height: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding: 13px 16px;

    border-radius: 12px;

    text-decoration: none;

    font-size: 13px;

    font-weight: 700;

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        background .2s ease;

    border:
        1px solid
        transparent;
}


.menu-button:hover {

    transform:
        translateY(-2px);
}


/* ======================================================
   PRIMARY
====================================================== */

.btn-primary {

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #4f46e5,
            #6366f1
        );

    box-shadow:
        0 8px 18px
        rgba(79,70,229,.18);
}


.btn-primary:hover {

    box-shadow:
        0 12px 25px
        rgba(79,70,229,.25);
}


/* ======================================================
   ABSENSI
====================================================== */

.btn-absensi {

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #059669,
            #10b981
        );

    box-shadow:
        0 8px 18px
        rgba(16,185,129,.16);
}


.btn-absensi:hover {

    box-shadow:
        0 12px 25px
        rgba(16,185,129,.24);
}


/* ======================================================
   SOFT
====================================================== */

.btn-soft {

    color: #3730a3;

    background: #eef2ff;

    border-color: #e0e7ff;
}


.btn-soft:hover {

    background: #e0e7ff;

    border-color: #c7d2fe;
}


/* ======================================================
   GRAY
====================================================== */

.btn-gray {

    color: #374151;

    background: #f8fafc;

    border-color: #e5e7eb;
}


.btn-gray:hover {

    background: #f1f5f9;

    border-color: #d1d5db;
}


/* ======================================================
   LOGOUT
====================================================== */

.btn-logout {

    color: #b91c1c;

    background: #fef2f2;

    border-color: #fecaca;
}


.btn-logout:hover {

    background: #fee2e2;

    border-color: #fca5a5;
}


/* ======================================================
   DIVIDER
====================================================== */

.divider {

    display: flex;

    align-items: center;

    gap: 12px;

    margin:
        22px
        0
        15px;

    color: #9ca3af;

    font-size: 11px;
}


.divider::before,
.divider::after {

    content: "";

    height: 1px;

    flex: 1;

    background: #e5e7eb;
}


/* ======================================================
   FOOTER
====================================================== */

.home-footer {

    margin-top: 25px;

    text-align: center;

    color: #9ca3af;

    font-size: 11px;
}


/* ======================================================
   RESPONSIVE
====================================================== */

@media (max-width: 800px) {

    .home-card {

        grid-template-columns: 1fr;
    }

    .home-info {

        padding: 35px 28px;
    }

    .home-menu {

        padding: 35px 28px;
    }

    .brand {

        margin-bottom: 35px;
    }

    .home-info h1 {

        font-size: 29px;
    }
}


@media (max-width: 500px) {

    .home-page {

        padding: 15px;
    }

    .home-card {

        border-radius: 20px;
    }

    .home-info,
    .home-menu {

        padding: 28px 22px;
    }

    .home-info h1 {

        font-size: 25px;
    }

    .home-menu h2 {

        font-size: 23px;
    }
}

</style>

</head>


<body>


<div class="home-page">


<div class="home-card">


<!-- ==================================================
     BAGIAN KIRI
================================================== -->

<section class="home-info">


<div class="brand">

<div class="brand-icon">
    K
</div>

<div>

<strong>
    KaryawanHub
</strong>

<small>
    REGISTER & ABSENSI
</small>

</div>

</div>


<h1>

    Kelola karyawan
    lebih mudah.

</h1>


<p>

    Sistem registrasi, pengelolaan data karyawan,
    absensi masuk dan pulang, serta notifikasi
    WhatsApp dalam satu aplikasi.

</p>


<div class="features">


<div class="feature">

<span class="feature-dot">
    ✓
</span>

Registrasi karyawan

</div>


<div class="feature">

<span class="feature-dot">
    ✓
</span>

CRUD data karyawan

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

Notifikasi WhatsApp Fonnte

</div>


</div>

</section>


<!-- ==================================================
     BAGIAN KANAN
================================================== -->

<section class="home-menu">


<h2>
    Selamat datang 👋
</h2>


<p class="subtitle">
    Pilih menu yang ingin kamu gunakan.
</p>


<!-- ==================================================
     STATUS KARYAWAN
================================================== -->

<?php if ($is_karyawan): ?>

<div class="login-status">

    👤 Kamu login sebagai

    <strong>

        <?= htmlspecialchars(
            $nama_karyawan,
            ENT_QUOTES,
            "UTF-8"
        ) ?>

    </strong>

</div>

<?php endif; ?>


<!-- ==================================================
     STATUS ADMIN
================================================== -->

<?php if ($is_admin): ?>

<div class="login-status">

    🛡️ Kamu login sebagai

    <strong>

        <?= htmlspecialchars(
            $nama_admin,
            ENT_QUOTES,
            "UTF-8"
        ) ?>

    </strong>

</div>

<?php endif; ?>


<div class="menu-list">


<!-- REGISTER -->

<a
    class="menu-button btn-primary"
    href="auth/register.php"
>
    ＋ Register Karyawan
</a>


<!-- LOGIN / ABSENSI -->

<?php if ($is_karyawan): ?>

<a
    class="menu-button btn-absensi"
    href="absensi/index.php"
>
    ✓ Buka Absensi Karyawan
</a>

<?php else: ?>

<a
    class="menu-button btn-soft"
    href="auth/login.php"
>
    → Login & Absensi
</a>

<?php endif; ?>


<!-- DATA KARYAWAN -->

<a
    class="menu-button btn-gray"
    href="karyawan/index.php"
>
    ♙ Data Karyawan
</a>


<!-- ADMIN -->

<a
    class="menu-button btn-gray"
    href="admin/index.php"
>
    ▦ Dashboard Admin
</a>


<!-- LOGOUT -->

<?php if ($is_karyawan || $is_admin): ?>

<div class="divider">

    <span>
        Akun
    </span>

</div>


<a
    class="menu-button btn-logout"
    href="auth/logout.php"
    onclick="
        return confirm(
            'Apakah kamu yakin ingin keluar dari aplikasi?'
        );
    "
>
    ↪ Logout
</a>

<?php endif; ?>


</div>


<div class="home-footer">

    KaryawanHub &copy;
    <?= date("Y") ?>

</div>


</section>


</div>


</div>


</body>

</html>