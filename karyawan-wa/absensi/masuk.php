<?php
session_start();

date_default_timezone_set("Asia/Jakarta");

if (!isset($_SESSION["karyawan_id"])) {
    header("Location: ../index.php");
    exit;
}

$nama_karyawan = $_SESSION["nama"]
    ?? $_SESSION["username"]
    ?? "Karyawan";

$hari = [
    "Sunday"    => "Minggu",
    "Monday"    => "Senin",
    "Tuesday"   => "Selasa",
    "Wednesday" => "Rabu",
    "Thursday"  => "Kamis",
    "Friday"    => "Jumat",
    "Saturday"  => "Sabtu"
];

$bulan = [
    1  => "Januari",
    2  => "Februari",
    3  => "Maret",
    4  => "April",
    5  => "Mei",
    6  => "Juni",
    7  => "Juli",
    8  => "Agustus",
    9  => "September",
    10 => "Oktober",
    11 => "November",
    12 => "Desember"
];

$hari_ini = $hari[date("l")];
$tanggal = date("d") . " " . $bulan[(int)date("m")] . " " . date("Y");
$jam_sekarang = date("H:i:s");

$jam_masuk_normal = "08:00:00";

$status_waktu = "";
$keterangan_waktu = "";

if ($jam_sekarang <= $jam_masuk_normal) {
    $status_waktu = "Tepat waktu";
    $keterangan_waktu = "Anda belum terlambat untuk melakukan absensi masuk.";
} else {
    $terlambat = strtotime($jam_sekarang) - strtotime($jam_masuk_normal);
    $menit = floor($terlambat / 60);

    $status_waktu = "Terlambat";
    $keterangan_waktu = "Anda terlambat sekitar " . $menit . " menit.";
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

<title>Absensi - KaryawanHub</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Inter, "Segoe UI", Arial, sans-serif;
    min-height: 100vh;

    background:
        linear-gradient(
            135deg,
            #eef2ff 0%,
            #f8fafc 50%,
            #eef2ff 100%
        );

    color: #172033;
}

.absensi-page {
    min-height: 100vh;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 30px 20px;
}

.absensi-card {
    width: 100%;
    max-width: 950px;

    background: #ffffff;

    border-radius: 28px;

    overflow: hidden;

    box-shadow:
        0 25px 70px rgba(30, 41, 59, 0.14);

    border: 1px solid #e2e8f0;
}

/* HEADER */

.absensi-header {
    position: relative;

    padding: 38px 45px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #312e81,
            #4338ca,
            #6366f1
        );

    overflow: hidden;
}

.absensi-header::after {
    content: "";

    position: absolute;

    width: 230px;
    height: 230px;

    border-radius: 50%;

    background: rgba(255,255,255,0.07);

    right: -70px;
    top: -100px;
}

.header-content {
    position: relative;
    z-index: 2;
}

.brand {
    display: flex;
    align-items: center;

    gap: 13px;

    margin-bottom: 30px;
}

.brand-icon {
    width: 47px;
    height: 47px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 13px;

    background: rgba(255,255,255,0.15);

    border: 1px solid rgba(255,255,255,0.2);

    font-size: 21px;
    font-weight: 800;
}

.brand-text {
    display: flex;
    flex-direction: column;

    gap: 3px;
}

.brand-text strong {
    font-size: 18px;
}

.brand-text small {
    font-size: 10px;

    letter-spacing: 1.4px;

    opacity: 0.7;
}

.absensi-header h1 {
    font-size: 32px;

    letter-spacing: -0.8px;

    margin-bottom: 8px;
}

.absensi-header p {
    color: rgba(255,255,255,0.78);

    font-size: 14px;
}

/* CONTENT */

.absensi-content {
    padding: 38px 45px;
}

/* WELCOME */

.welcome {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 25px;
}

.welcome h2 {
    font-size: 22px;

    color: #111827;

    margin-bottom: 5px;
}

.welcome p {
    color: #64748b;

    font-size: 13px;
}

.date-box {
    padding: 11px 15px;

    border-radius: 11px;

    background: #f8fafc;

    border: 1px solid #e2e8f0;

    color: #475569;

    font-size: 12px;

    white-space: nowrap;
}

/* CLOCK */

.clock-box {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    padding: 20px 22px;

    margin-bottom: 25px;

    border-radius: 17px;

    background: #f8fafc;

    border: 1px solid #e2e8f0;
}

.clock-left {
    display: flex;
    align-items: center;

    gap: 15px;
}

.clock-icon {
    width: 50px;
    height: 50px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 14px;

    background: #e0e7ff;

    font-size: 22px;
}

.clock-info small {
    display: block;

    color: #64748b;

    font-size: 11px;

    margin-bottom: 3px;
}

.clock-info strong {
    color: #111827;

    font-size: 24px;
}

.clock-status {
    text-align: right;

    font-size: 12px;

    font-weight: 700;
}

.status-tepat {
    color: #059669;
}

.status-terlambat {
    color: #dc2626;
}

/* OPTIONS */

.absensi-options {
    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 18px;
}

.absensi-option {
    display: flex;
    align-items: center;

    gap: 18px;

    padding: 25px;

    text-decoration: none;

    border-radius: 18px;

    border: 1px solid;

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.absensi-option:hover {
    transform: translateY(-4px);

    box-shadow:
        0 12px 25px rgba(15,23,42,0.08);
}

.option-icon {
    width: 58px;
    height: 58px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 16px;

    font-size: 25px;
}

.option-content {
    flex: 1;
}

.option-content h3 {
    font-size: 17px;

    margin-bottom: 5px;
}

.option-content p {
    font-size: 12px;

    line-height: 1.5;
}

/* MASUK */

.absen-masuk {
    color: #047857;

    background: #ecfdf5;

    border-color: #d1fae5;
}

.absen-masuk .option-icon {
    background: #d1fae5;
}

.absen-masuk .option-content p {
    color: #059669;
}

/* PULANG */

.absen-pulang {
    color: #b45309;

    background: #fffbeb;

    border-color: #fde68a;
}

.absen-pulang .option-icon {
    background: #fef3c7;
}

.absen-pulang .option-content p {
    color: #d97706;
}

/* ARROW */

.option-arrow {
    width: 34px;
    height: 34px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: rgba(255,255,255,0.8);

    font-size: 17px;
}

/* INFO */

.info-box {
    margin-top: 20px;

    padding: 16px 18px;

    border-radius: 14px;

    background: #eef2ff;

    border: 1px solid #c7d2fe;

    color: #3730a3;

    font-size: 12px;

    line-height: 1.6;
}

/* BOTTOM */

.bottom-menu {
    display: flex;
    justify-content: center;

    margin-top: 28px;

    padding-top: 25px;

    border-top: 1px solid #e2e8f0;
}

.back-button {
    display: inline-flex;
    align-items: center;

    gap: 8px;

    padding: 11px 18px;

    border-radius: 11px;

    color: #475569;

    background: #f8fafc;

    border: 1px solid #e2e8f0;

    text-decoration: none;

    font-size: 13px;

    font-weight: 600;

    transition: 0.2s ease;
}

.back-button:hover {
    background: #f1f5f9;
}

/* RESPONSIVE */

@media (max-width: 700px) {

    .absensi-page {
        padding: 15px;
    }

    .absensi-header {
        padding: 30px 25px;
    }

    .absensi-content {
        padding: 30px 25px;
    }

    .absensi-options {
        grid-template-columns: 1fr;
    }

    .welcome {
        align-items: flex-start;

        flex-direction: column;
    }

    .date-box {
        width: 100%;

        text-align: center;
    }

    .clock-box {
        align-items: flex-start;

        flex-direction: column;
    }

    .clock-status {
        text-align: left;
    }
}

@media (max-width: 450px) {

    .absensi-card {
        border-radius: 20px;
    }

    .absensi-header h1 {
        font-size: 26px;
    }

    .absensi-option {
        padding: 20px;
    }

    .option-icon {
        width: 50px;
        height: 50px;

        font-size: 21px;
    }
}

</style>

</head>

<body>

<div class="absensi-page">

<div class="absensi-card">

<header class="absensi-header">

    <div class="header-content">

        <div class="brand">

            <div class="brand-icon">
                K
            </div>

            <div class="brand-text">

                <strong>KaryawanHub</strong>

                <small>
                    SISTEM ABSENSI
                </small>

            </div>

        </div>

        <h1>
            Absensi Karyawan
        </h1>

        <p>
            Catat kehadiran masuk dan kepulangan dengan mudah.
        </p>

    </div>

</header>

<main class="absensi-content">

    <div class="welcome">

        <div>

            <h2>
                Halo, <?= htmlspecialchars($nama_karyawan) ?> 👋
            </h2>

            <p>
                Silakan pilih jenis absensi yang ingin dilakukan.
            </p>

        </div>

        <div class="date-box">
            📅 <?= $hari_ini ?>, <?= $tanggal ?>
        </div>

    </div>

    <!-- JAM -->

    <div class="clock-box">

        <div class="clock-left">

            <div class="clock-icon">
                🕐
            </div>

            <div class="clock-info">

                <small>
                    Waktu Sekarang
                </small>

                <strong id="clock">
                    <?= $jam_sekarang ?>
                </strong>

            </div>

        </div>

        <div class="clock-status">

            <div
                class="<?= $status_waktu === 'Terlambat'
                    ? 'status-terlambat'
                    : 'status-tepat' ?>"
            >
                <?= $status_waktu ?>
            </div>

            <span>
                <?= htmlspecialchars($keterangan_waktu) ?>
            </span>

        </div>

    </div>

    <!-- PILIHAN ABSENSI -->

    <div class="absensi-options">

        <a
            href="/karyawan-wa/absensi/masuk.php"
            class="absensi-option absen-masuk"
        >

            <div class="option-icon">
                🟢
            </div>

            <div class="option-content">

                <h3>
                    Absensi Masuk
                </h3>

                <p>
                    Catat waktu kedatangan Anda hari ini.
                    Jika lewat pukul 08:00 akan tercatat sebagai terlambat.
                </p>

            </div>

            <div class="option-arrow">
                →
            </div>

        </a>

        <a
            href="/karyawan-wa/absensi/pulang.php"
            class="absensi-option absen-pulang"
        >

            <div class="option-icon">
                🟠
            </div>

            <div class="option-content">

                <h3>
                    Absensi Pulang
                </h3>

                <p>
                    Catat waktu kepulangan Anda hari ini.
                </p>

            </div>

            <div class="option-arrow">
                →
            </div>

        </a>

    </div>

    <div class="info-box">

        <strong>Jam kerja:</strong>
        Absensi masuk dianggap tepat waktu sampai pukul
        <strong>08:00 WIB</strong>.
        Setelah itu sistem otomatis menghitung keterlambatan.

    </div>

    <div class="bottom-menu">

        <a
            href="/karyawan-wa/index.php"
            class="back-button"
        >
            ← Kembali ke Beranda
        </a>

    </div>

</main>

</div>

</div>

<script>

function updateClock() {

    const now = new Date();

    const jam = String(now.getHours()).padStart(2, "0");
    const menit = String(now.getMinutes()).padStart(2, "0");
    const detik = String(now.getSeconds()).padStart(2, "0");

    document.getElementById("clock").textContent =
        `${jam}:${menit}:${detik}`;

}

setInterval(updateClock, 1000);

updateClock();

</script>

</body>
</html>