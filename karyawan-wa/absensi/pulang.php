<?php

session_start();

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../api/fonnte.php";


if (!isset($_SESSION["karyawan_id"])) {

    header("Location: ../auth/login.php");

    exit;
}


$id =
    (int) $_SESSION["karyawan_id"];

$nama =
    $_SESSION["karyawan_nama"];

$no_hp =
    $_SESSION["karyawan_wa"];


/*
|--------------------------------------------------------------------------
| CARI ABSENSI HARI INI
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        id,
        jam_masuk,
        jam_pulang
     FROM absensi
     WHERE id_karyawan = ?
     AND tanggal = CURDATE()
     LIMIT 1"
);

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$absen =
    $stmt
    ->get_result()
    ->fetch_assoc();


/*
|--------------------------------------------------------------------------
| BELUM ABSEN MASUK
|--------------------------------------------------------------------------
*/

if (!$absen) {

    echo "
    <script>
        alert('Kamu belum melakukan absensi masuk.');
        window.location='index.php';
    </script>
    ";

    exit;
}


/*
|--------------------------------------------------------------------------
| SUDAH ABSEN PULANG
|--------------------------------------------------------------------------
*/

if (!empty($absen["jam_pulang"])) {

    echo "
    <script>
        alert('Kamu sudah melakukan absensi pulang.');
        window.location='index.php';
    </script>
    ";

    exit;
}


/*
|--------------------------------------------------------------------------
| SIMPAN JAM PULANG
|--------------------------------------------------------------------------
*/

$update = $conn->prepare(
    "UPDATE absensi
     SET jam_pulang = CURTIME()
     WHERE id = ?"
);

$update->bind_param(
    "i",
    $absen["id"]
);


if ($update->execute()) {

    $pesan =
        "Halo {$nama},\n\n" .
        "Absensi pulang berhasil.\n\n" .
        "Tanggal: " . date("d-m-Y") . "\n" .
        "Jam pulang: " . date("H:i:s") . "\n\n" .
        "Terima kasih dan hati-hati di perjalanan.";

    sendWhatsApp(
        $no_hp,
        $pesan
    );

    header(
        "Location: index.php"
    );

    exit;
}


echo "Gagal melakukan absensi pulang.";