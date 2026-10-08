<?php

session_start();

date_default_timezone_set("Asia/Jakarta");

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../api/fonnte.php";


/*
|--------------------------------------------------------------------------
| CEK LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["karyawan_id"]) ||
    (int) $_SESSION["karyawan_id"] <= 0
) {

    header(
        "Location: /karyawan-wa/auth/login.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| ID KARYAWAN DARI SESSION
|--------------------------------------------------------------------------
*/

$karyawan_id = (int) $_SESSION["karyawan_id"];


/*
|--------------------------------------------------------------------------
| AMBIL DATA KARYAWAN DARI DATABASE
|--------------------------------------------------------------------------
|
| Jangan hanya mengandalkan nama dari session.
| Kita ambil ulang berdasarkan ID agar:
|
| Fifi -> tetap Fifi
| Renii -> tetap Renii
|
*/

$stmt_karyawan = $conn->prepare(
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


if (!$stmt_karyawan) {

    die(
        "Terjadi kesalahan database: "
        . htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            "UTF-8"
        )
    );
}


$stmt_karyawan->bind_param(
    "i",
    $karyawan_id
);

$stmt_karyawan->execute();

$result_karyawan =
    $stmt_karyawan->get_result();

$data_karyawan =
    $result_karyawan->fetch_assoc();

$stmt_karyawan->close();


/*
|--------------------------------------------------------------------------
| JIKA ID SESSION TIDAK ADA DI DATABASE
|--------------------------------------------------------------------------
|
| Ini juga mencegah error:
| Cannot add or update a child row
|
*/

if (!$data_karyawan) {

    unset(
        $_SESSION["karyawan_id"],
        $_SESSION["karyawan_nik"],
        $_SESSION["karyawan_nama"],
        $_SESSION["karyawan_jabatan"],
        $_SESSION["karyawan_wa"],
        $_SESSION["karyawan_email"],
        $_SESSION["role"]
    );


    header(
        "Location: /karyawan-wa/auth/login.php?error=session"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| UPDATE SESSION DENGAN DATA DATABASE
|--------------------------------------------------------------------------
|
| Ini penting untuk masalah:
| login Fifi tetapi tampil Renii.
|
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


/*
|--------------------------------------------------------------------------
| DATA KARYAWAN
|--------------------------------------------------------------------------
*/

$nama_karyawan =
    $data_karyawan["nama"]
    ?? "Karyawan";

$nik =
    $data_karyawan["nik"]
    ?? "-";

$jabatan =
    $data_karyawan["jabatan"]
    ?? "-";

$no_hp =
    $data_karyawan["no_hp"]
    ?? "";


/*
|--------------------------------------------------------------------------
| WAKTU KERJA NORMAL
|--------------------------------------------------------------------------
*/

$jam_masuk_normal = "08:00:00";

$tanggal_hari_ini =
    date("Y-m-d");


/*
|--------------------------------------------------------------------------
| FUNGSI AMBIL ABSENSI HARI INI
|--------------------------------------------------------------------------
*/

function getAbsensiHariIni(
    $conn,
    $karyawan_id,
    $tanggal
) {

    $absensi = null;


    $stmt = $conn->prepare(
        "SELECT
            id,
            id_karyawan,
            tanggal,
            jam_masuk,
            jam_pulang
         FROM absensi
         WHERE id_karyawan = ?
         AND tanggal = ?
         LIMIT 1"
    );


    if (!$stmt) {

        return null;
    }


    $stmt->bind_param(
        "is",
        $karyawan_id,
        $tanggal
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    $absensi =
        $result->fetch_assoc();


    $stmt->close();


    return $absensi;
}


/*
|--------------------------------------------------------------------------
| AMBIL ABSENSI AWAL
|--------------------------------------------------------------------------
*/

$absensi =
    getAbsensiHariIni(
        $conn,
        $karyawan_id,
        $tanggal_hari_ini
    );


/*
|--------------------------------------------------------------------------
| PROSES ABSEN MASUK
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["aksi"]) &&
    $_POST["aksi"] === "masuk"
) {


    /*
    |--------------------------------------------------------------------------
    | CEK ABSENSI SUDAH ADA
    |--------------------------------------------------------------------------
    */

    if (
        $absensi &&
        !empty($absensi["jam_masuk"])
    ) {

        $_SESSION["absensi_message"] =
            "Kamu sudah melakukan absensi masuk hari ini.";

        $_SESSION["absensi_type"] =
            "error";


    } else {


        /*
        |--------------------------------------------------------------------------
        | CEK ULANG KARYAWAN
        |--------------------------------------------------------------------------
        |
        | Sebelum INSERT ke absensi, pastikan
        | id_karyawan memang ada di tabel karyawan.
        |
        */

        $cek_karyawan =
            $conn->prepare(
                "SELECT id, nama, no_hp
                 FROM karyawan
                 WHERE id = ?
                 LIMIT 1"
            );


        if (!$cek_karyawan) {

            $_SESSION["absensi_message"] =
                "Tidak dapat memeriksa data karyawan.";

            $_SESSION["absensi_type"] =
                "error";

        } else {

            $cek_karyawan->bind_param(
                "i",
                $karyawan_id
            );

            $cek_karyawan->execute();

            $result_cek =
                $cek_karyawan->get_result();

            $karyawan_valid =
                $result_cek->fetch_assoc();

            $cek_karyawan->close();


            /*
            |--------------------------------------------------------------------------
            | KARYAWAN TIDAK DITEMUKAN
            |--------------------------------------------------------------------------
            */

            if (!$karyawan_valid) {

                unset(
                    $_SESSION["karyawan_id"],
                    $_SESSION["karyawan_nik"],
                    $_SESSION["karyawan_nama"],
                    $_SESSION["karyawan_jabatan"],
                    $_SESSION["karyawan_wa"],
                    $_SESSION["karyawan_email"],
                    $_SESSION["role"]
                );


                $_SESSION["absensi_message"] =
                    "Data karyawan tidak ditemukan. Silakan login kembali.";

                $_SESSION["absensi_type"] =
                    "error";


                header(
                    "Location: /karyawan-wa/auth/login.php"
                );

                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | WAKTU ABSEN
            |--------------------------------------------------------------------------
            */

            $jam_sekarang =
                date("H:i:s");


            /*
            |--------------------------------------------------------------------------
            | STATUS ABSEN
            |--------------------------------------------------------------------------
            */

            if (
                $jam_sekarang <=
                $jam_masuk_normal
            ) {

                $status_db =
                    "Tepat Waktu";

            } else {

                $status_db =
                    "Terlambat";
            }


            /*
            |--------------------------------------------------------------------------
            | INSERT ABSENSI
            |--------------------------------------------------------------------------
            */

            try {

                $insert =
                    $conn->prepare(
                        "INSERT INTO absensi
                        (
                            id_karyawan,
                            tanggal,
                            jam_masuk
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?
                        )"
                    );


                if (!$insert) {

                    throw new Exception(
                        "Prepare INSERT gagal."
                    );
                }


                $insert->bind_param(
                    "iss",
                    $karyawan_id,
                    $tanggal_hari_ini,
                    $jam_sekarang
                );


                if ($insert->execute()) {


                    /*
                    |--------------------------------------------------------------------------
                    | WHATSAPP
                    |--------------------------------------------------------------------------
                    */

                    if (
                        function_exists(
                            "sendWhatsApp"
                        ) &&
                        !empty($no_hp)
                    ) {

                        $pesan =
                            "Halo "
                            . $nama_karyawan
                            . ",\n\n"
                            . "Absensi masuk berhasil.\n\n"
                            . "Tanggal: "
                            . date("d-m-Y")
                            . "\n"
                            . "Jam masuk: "
                            . $jam_sekarang
                            . "\n"
                            . "Status: "
                            . $status_db
                            . "\n\n"
                            . "Terima kasih.";


                        try {

                            sendWhatsApp(
                                $no_hp,
                                $pesan
                            );

                        } catch (
                            Throwable $wa_error
                        ) {

                            // WhatsApp gagal tidak
                            // membatalkan absensi.
                        }
                    }


                    $_SESSION["absensi_message"] =
                        "Absensi masuk berhasil.";

                    $_SESSION["absensi_type"] =
                        "success";


                } else {

                    $_SESSION["absensi_message"] =
                        "Gagal menyimpan absensi masuk.";

                    $_SESSION["absensi_type"] =
                        "error";
                }


                $insert->close();


            } catch (
                Throwable $error
            ) {

                $_SESSION["absensi_message"] =
                    "Gagal menyimpan absensi. "
                    . "Pastikan data karyawan masih tersedia.";

                $_SESSION["absensi_type"] =
                    "error";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | KEMBALI KE HALAMAN ABSENSI
    |--------------------------------------------------------------------------
    */

    header(
        "Location: /karyawan-wa/absensi/index.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| PROSES ABSEN PULANG
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["aksi"]) &&
    $_POST["aksi"] === "pulang"
) {


    /*
    |--------------------------------------------------------------------------
    | BELUM ABSEN MASUK
    |--------------------------------------------------------------------------
    */

    if (
        !$absensi ||
        empty($absensi["jam_masuk"])
    ) {

        $_SESSION["absensi_message"] =
            "Kamu belum melakukan absensi masuk.";

        $_SESSION["absensi_type"] =
            "error";
    }


    /*
    |--------------------------------------------------------------------------
    | SUDAH ABSEN PULANG
    |--------------------------------------------------------------------------
    */

    elseif (
        !empty($absensi["jam_pulang"])
    ) {

        $_SESSION["absensi_message"] =
            "Kamu sudah melakukan absensi pulang hari ini.";

        $_SESSION["absensi_type"] =
            "error";
    }


    /*
    |--------------------------------------------------------------------------
    | PROSES ABSEN PULANG
    |--------------------------------------------------------------------------
    */

    else {

        $jam_pulang =
            date("H:i:s");


        try {

            $update =
                $conn->prepare(
                    "UPDATE absensi
                     SET jam_pulang = ?
                     WHERE id = ?
                     AND id_karyawan = ?
                     LIMIT 1"
                );


            if (!$update) {

                throw new Exception(
                    "Prepare UPDATE gagal."
                );
            }


            $id_absensi =
                (int) $absensi["id"];


            $update->bind_param(
                "sii",
                $jam_pulang,
                $id_absensi,
                $karyawan_id
            );


            if ($update->execute()) {


                /*
                |--------------------------------------------------------------------------
                | WHATSAPP PULANG
                |--------------------------------------------------------------------------
                */

                if (
                    function_exists(
                        "sendWhatsApp"
                    ) &&
                    !empty($no_hp)
                ) {

                    $pesan =
                        "Halo "
                        . $nama_karyawan
                        . ",\n\n"
                        . "Absensi pulang berhasil.\n\n"
                        . "Tanggal: "
                        . date("d-m-Y")
                        . "\n"
                        . "Jam pulang: "
                        . $jam_pulang
                        . "\n\n"
                        . "Terima kasih dan hati-hati di perjalanan.";


                    try {

                        sendWhatsApp(
                            $no_hp,
                            $pesan
                        );

                    } catch (
                        Throwable $wa_error
                    ) {

                        // Jangan menggagalkan
                        // proses absensi.
                    }
                }


                $_SESSION["absensi_message"] =
                    "Absensi pulang berhasil.";

                $_SESSION["absensi_type"] =
                    "success";


            } else {

                $_SESSION["absensi_message"] =
                    "Gagal menyimpan absensi pulang.";

                $_SESSION["absensi_type"] =
                    "error";
            }


            $update->close();


        } catch (
            Throwable $error
        ) {

            $_SESSION["absensi_message"] =
                "Gagal menyimpan absensi pulang.";

            $_SESSION["absensi_type"] =
                "error";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | KEMBALI KE ABSENSI
    |--------------------------------------------------------------------------
    */

    header(
        "Location: /karyawan-wa/absensi/index.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL PESAN
|--------------------------------------------------------------------------
*/

$message =
    $_SESSION["absensi_message"]
    ?? "";

$message_type =
    $_SESSION["absensi_type"]
    ?? "";


unset(
    $_SESSION["absensi_message"],
    $_SESSION["absensi_type"]
);


/*
|--------------------------------------------------------------------------
| REFRESH DATA ABSENSI
|--------------------------------------------------------------------------
*/

$absensi =
    getAbsensiHariIni(
        $conn,
        $karyawan_id,
        $tanggal_hari_ini
    );


/*
|--------------------------------------------------------------------------
| JAM ABSENSI
|--------------------------------------------------------------------------
*/

$jam_masuk =
    $absensi["jam_masuk"]
    ?? "-";

$jam_pulang =
    $absensi["jam_pulang"]
    ?? "-";


/*
|--------------------------------------------------------------------------
| STATUS TOMBOL
|--------------------------------------------------------------------------
*/

$sudah_masuk =
    (
        $absensi &&
        !empty($absensi["jam_masuk"])
    );


$sudah_pulang =
    (
        $absensi &&
        !empty($absensi["jam_pulang"])
    );


/*
|--------------------------------------------------------------------------
| STATUS ABSENSI
|--------------------------------------------------------------------------
*/

$status_text =
    "Belum Absen";

$status_class =
    "status-wait";

$status_detail =
    "Silakan melakukan absensi masuk.";


if ($sudah_masuk) {

    if (
        $jam_masuk <=
        $jam_masuk_normal
    ) {

        $status_text =
            "Tepat Waktu";

        $status_class =
            "status-success";

        $status_detail =
            "Absensi masuk dilakukan tepat waktu.";

    } else {

        $status_text =
            "Terlambat";

        $status_class =
            "status-late";


        $selisih =
            strtotime($jam_masuk)
            -
            strtotime($jam_masuk_normal);


        $menit_terlambat =
            max(
                0,
                floor(
                    $selisih / 60
                )
            );


        $status_detail =
            "Terlambat "
            . $menit_terlambat
            . " menit.";
    }
}


/*
|--------------------------------------------------------------------------
| TANGGAL INDONESIA
|--------------------------------------------------------------------------
*/

$hari = [

    "Sunday" =>
        "Minggu",

    "Monday" =>
        "Senin",

    "Tuesday" =>
        "Selasa",

    "Wednesday" =>
        "Rabu",

    "Thursday" =>
        "Kamis",

    "Friday" =>
        "Jumat",

    "Saturday" =>
        "Sabtu"
];


$bulan = [

    "January" =>
        "Januari",

    "February" =>
        "Februari",

    "March" =>
        "Maret",

    "April" =>
        "April",

    "May" =>
        "Mei",

    "June" =>
        "Juni",

    "July" =>
        "Juli",

    "August" =>
        "Agustus",

    "September" =>
        "September",

    "October" =>
        "Oktober",

    "November" =>
        "November",

    "December" =>
        "Desember"
];


$nama_hari =
    $hari[date("l")];


$nama_bulan =
    $bulan[date("F")];


$tanggal_indonesia =
    $nama_hari
    . ", "
    . date("d")
    . " "
    . $nama_bulan
    . " "
    . date("Y");

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
    Absensi Karyawan - KaryawanHub
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

.page {

    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 30px 20px;
}


/* ======================================================
   CARD
====================================================== */

.card {

    width: 100%;

    max-width: 1000px;

    background: #ffffff;

    border-radius: 26px;

    overflow: hidden;

    box-shadow:
        0 25px 70px
        rgba(79,70,229,0.15);
}


/* ======================================================
   HEADER
====================================================== */

.header {

    padding: 30px 38px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #1e1b4b,
            #3730a3,
            #4f46e5
        );

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;
}

.brand {

    display: flex;

    align-items: center;

    gap: 13px;
}

.brand-icon {

    width: 46px;

    height: 46px;

    border-radius: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(255,255,255,0.14);

    font-size: 21px;

    font-weight: 800;
}

.brand strong {

    display: block;

    font-size: 18px;
}

.brand small {

    display: block;

    margin-top: 3px;

    font-size: 10px;

    letter-spacing: 1.3px;

    opacity: 0.7;
}

.header-date {

    text-align: right;

    font-size: 13px;

    color:
        rgba(255,255,255,0.8);
}


/* ======================================================
   CONTENT
====================================================== */

.content {

    padding: 38px;
}


/* ======================================================
   WELCOME
====================================================== */

.welcome {

    margin-bottom: 28px;
}

.welcome h1 {

    margin: 0;

    font-size: 29px;

    color: #111827;
}

.welcome p {

    margin: 7px 0 0;

    color: #6b7280;

    font-size: 14px;
}


/* ======================================================
   CLOCK
====================================================== */

.clock-box {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    padding: 24px;

    margin-bottom: 22px;

    border-radius: 18px;

    background:
        linear-gradient(
            135deg,
            #f5f3ff,
            #eef2ff
        );

    border: 1px solid #e0e7ff;
}

.clock-label {

    color: #6b7280;

    font-size: 12px;

    margin-bottom: 5px;
}

.clock {

    color: #312e81;

    font-size: 36px;

    font-weight: 800;

    letter-spacing: 1px;
}

.clock-note {

    text-align: right;

    color: #6b7280;

    font-size: 12px;

    line-height: 1.6;
}


/* ======================================================
   EMPLOYEE
====================================================== */

.employee {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 12px;

    margin-bottom: 25px;
}

.employee-box {

    padding: 17px;

    border: 1px solid #e5e7eb;

    border-radius: 15px;

    background: #ffffff;

    transition: .2s;
}

.employee-box:hover {

    transform: translateY(-2px);

    box-shadow:
        0 8px 20px
        rgba(15,23,42,0.06);
}

.employee-label {

    color: #9ca3af;

    font-size: 11px;

    margin-bottom: 5px;

    text-transform: uppercase;

    letter-spacing: .5px;
}

.employee-value {

    color: #111827;

    font-size: 14px;

    font-weight: 700;

    word-break: break-word;
}


/* ======================================================
   MESSAGE
====================================================== */

.message {

    padding: 14px 16px;

    border-radius: 12px;

    margin-bottom: 20px;

    font-size: 13px;
}

.message.success {

    color: #166534;

    background: #f0fdf4;

    border: 1px solid #bbf7d0;
}

.message.error {

    color: #b91c1c;

    background: #fef2f2;

    border: 1px solid #fecaca;
}


/* ======================================================
   STATUS
====================================================== */

.status-card {

    padding: 20px;

    margin-bottom: 25px;

    border-radius: 18px;

    border: 1px solid #e5e7eb;

    background: #ffffff;

    box-shadow:
        0 5px 20px
        rgba(15,23,42,0.03);
}

.status-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;
}

.status-title {

    font-size: 13px;

    color: #6b7280;

    margin-bottom: 5px;
}

.status-name {

    font-size: 20px;

    font-weight: 800;
}

.status-detail {

    margin-top: 7px;

    color: #6b7280;

    font-size: 12px;
}

.status-badge {

    padding: 8px 13px;

    border-radius: 999px;

    font-size: 11px;

    font-weight: 700;

    white-space: nowrap;
}

.status-success {

    color: #166534;

    background: #dcfce7;
}

.status-late {

    color: #b45309;

    background: #fef3c7;
}

.status-wait {

    color: #475569;

    background: #f1f5f9;
}


/* ======================================================
   ATTENDANCE
====================================================== */

.attendance-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 18px;

    margin-bottom: 25px;
}

.attendance-card {

    padding: 24px;

    border-radius: 18px;

    border: 1px solid #e5e7eb;

    background: #ffffff;

    transition: .2s;
}

.attendance-card:hover {

    transform: translateY(-2px);

    box-shadow:
        0 10px 25px
        rgba(15,23,42,0.06);
}

.attendance-card h3 {

    margin: 0;

    font-size: 17px;

    color: #111827;
}

.attendance-card p {

    margin: 7px 0 18px;

    color: #6b7280;

    font-size: 12px;

    line-height: 1.5;
}

.time {

    font-size: 25px;

    font-weight: 800;

    color: #312e81;

    margin-bottom: 17px;
}


/* ======================================================
   BUTTON
====================================================== */

.absen-button {

    width: 100%;

    border: none;

    border-radius: 12px;

    padding: 13px;

    color: #ffffff;

    font-size: 13px;

    font-weight: 700;

    cursor: pointer;

    transition: .2s;
}

.absen-button:hover:not(:disabled) {

    transform: translateY(-2px);

    box-shadow:
        0 8px 20px
        rgba(79,70,229,0.2);
}

.absen-button:active:not(:disabled) {

    transform: translateY(0);
}

.absen-button:disabled {

    cursor: not-allowed;

    opacity: .55;
}

.btn-masuk {

    background:
        linear-gradient(
            135deg,
            #4f46e5,
            #6366f1
        );
}

.btn-pulang {

    background:
        linear-gradient(
            135deg,
            #059669,
            #10b981
        );
}


/* ======================================================
   BACK BUTTON
====================================================== */

.back-button {

    display: block;

    width: 100%;

    padding: 13px;

    text-align: center;

    border-radius: 12px;

    text-decoration: none;

    color: #475569;

    background: #f8fafc;

    border: 1px solid #e2e8f0;

    font-size: 13px;

    font-weight: 600;

    transition: .2s;
}

.back-button:hover {

    background: #eef2ff;

    color: #312e81;

    border-color: #c7d2fe;

    transform: translateY(-1px);
}


/* ======================================================
   RESPONSIVE
====================================================== */

@media (max-width: 700px) {

    .page {
        padding: 15px;
    }

    .card {
        border-radius: 20px;
    }

    .header {

        align-items: flex-start;

        flex-direction: column;

        padding: 25px 22px;
    }

    .header-date {
        text-align: left;
    }

    .content {
        padding: 25px 20px;
    }

    .welcome h1 {
        font-size: 24px;
    }

    .employee {
        grid-template-columns: 1fr;
    }

    .attendance-grid {
        grid-template-columns: 1fr;
    }

    .clock-box {

        align-items: flex-start;

        flex-direction: column;
    }

    .clock-note {
        text-align: left;
    }

    .clock {
        font-size: 30px;
    }

    .status-top {

        align-items: flex-start;

        flex-direction: column;
    }

    .status-badge {
        align-self: flex-start;
    }
}

</style>

</head>


<body>


<div class="page">

<div class="card">


<!-- ==================================================
     HEADER
================================================== -->

<div class="header">

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


<div class="header-date">

<?= htmlspecialchars(
    $tanggal_indonesia,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


<!-- ==================================================
     CONTENT
================================================== -->

<div class="content">


<div class="welcome">

<h1>

Halo,
<?= htmlspecialchars(
    $nama_karyawan,
    ENT_QUOTES,
    "UTF-8"
) ?>

👋

</h1>

<p>
    Silakan lakukan absensi sesuai waktu kerja kamu.
</p>

</div>


<!-- JAM -->

<div class="clock-box">

<div>

<div class="clock-label">
    Waktu Sekarang
</div>

<div
    class="clock"
    id="liveClock"
>
    <?= date("H:i:s") ?>
</div>

</div>


<div class="clock-note">

Jam kerja normal dimulai<br>

<strong>
    08:00 WIB
</strong>

</div>

</div>


<!-- DATA KARYAWAN -->

<div class="employee">


<div class="employee-box">

<div class="employee-label">
    NIK
</div>

<div class="employee-value">

<?= htmlspecialchars(
    $nik,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


<div class="employee-box">

<div class="employee-label">
    Nama
</div>

<div class="employee-value">

<?= htmlspecialchars(
    $nama_karyawan,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


<div class="employee-box">

<div class="employee-label">
    Jabatan
</div>

<div class="employee-value">

<?= htmlspecialchars(
    $jabatan,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


</div>


<!-- PESAN -->

<?php if ($message !== ""): ?>

<div
    class="message <?= $message_type === "success"
        ? "success"
        : "error" ?>"
>

<?= htmlspecialchars(
    $message,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

<?php endif; ?>


<!-- STATUS -->

<div class="status-card">

<div class="status-top">

<div>

<div class="status-title">
    Status Absensi Hari Ini
</div>

<div class="status-name">

<?= htmlspecialchars(
    $status_text,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

<div class="status-detail">

<?= htmlspecialchars(
    $status_detail,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>


<div
    class="status-badge <?= htmlspecialchars(
        $status_class,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

<?= htmlspecialchars(
    $status_text,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

</div>

</div>


<!-- ABSEN -->

<div class="attendance-grid">


<!-- MASUK -->

<div class="attendance-card">

<h3>
    🟣 Absen Masuk
</h3>

<p>
    Catat waktu kamu mulai bekerja hari ini.
</p>


<div class="time">

<?= htmlspecialchars(
    $jam_masuk,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>


<form
    method="POST"
    action=""
    onsubmit="
        const button = this.querySelector('button');

        if (button.disabled) {
            return false;
        }

        button.disabled = true;
        button.textContent = 'Memproses...';

        return true;
    "
>

<input
    type="hidden"
    name="aksi"
    value="masuk"
>


<button
    type="submit"
    class="absen-button btn-masuk"
    <?= $sudah_masuk ? "disabled" : "" ?>
>

<?= $sudah_masuk
    ? "✓ Sudah Absen Masuk"
    : "Absen Masuk" ?>

</button>

</form>

</div>


<!-- PULANG -->

<div class="attendance-card">

<h3>
    🟢 Absen Pulang
</h3>

<p>
    Catat waktu kamu selesai bekerja hari ini.
</p>


<div class="time">

<?= htmlspecialchars(
    $jam_pulang,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>


<form
    method="POST"
    action=""
    onsubmit="
        const button = this.querySelector('button');

        if (button.disabled) {
            return false;
        }

        button.disabled = true;
        button.textContent = 'Memproses...';

        return true;
    "
>

<input
    type="hidden"
    name="aksi"
    value="pulang"
>


<button
    type="submit"
    class="absen-button btn-pulang"
    <?= (
        !$sudah_masuk ||
        $sudah_pulang
    )
        ? "disabled"
        : "" ?>
>

<?php

if (!$sudah_masuk) {

    echo "Absen Masuk Dahulu";

} elseif ($sudah_pulang) {

    echo "✓ Sudah Absen Pulang";

} else {

    echo "Absen Pulang";
}

?>

</button>

</form>

</div>


</div>


<!-- KEMBALI -->

<a
    href="/karyawan-wa/index.php"
    class="back-button"
>
    ← Kembali ke Beranda
</a>


</div>

</div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| JAM DIGITAL
|--------------------------------------------------------------------------
*/

function updateClock() {

    const now = new Date();


    const hours =
        String(
            now.getHours()
        ).padStart(
            2,
            "0"
        );


    const minutes =
        String(
            now.getMinutes()
        ).padStart(
            2,
            "0"
        );


    const seconds =
        String(
            now.getSeconds()
        ).padStart(
            2,
            "0"
        );


    const clock =
        document.getElementById(
            "liveClock"
        );


    if (clock) {

        clock.textContent =
            hours
            + ":"
            + minutes
            + ":"
            + seconds;
    }
}


updateClock();


setInterval(
    updateClock,
    1000
);

</script>


</body>

</html>