<?php

session_start();

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../api/fonnte.php";


// ======================================================
// AMBIL ID
// ======================================================

$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {
    $_SESSION["hapus_status"] = "error";
    $_SESSION["hapus_pesan"] = "ID karyawan tidak valid.";

    header("Location: index.php");
    exit;
}


// ======================================================
// AMBIL DATA KARYAWAN SEBELUM DIHAPUS
// ======================================================

$stmt = $conn->prepare(
    "SELECT
        nama,
        nik,
        no_hp
     FROM karyawan
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

$karyawan = $result->fetch_assoc();

$stmt->close();


if (!$karyawan) {

    $_SESSION["hapus_status"] = "error";

    $_SESSION["hapus_pesan"] =
        "Data karyawan tidak ditemukan.";

    header("Location: index.php");
    exit;
}


// ======================================================
// SIMPAN DATA UNTUK NOTIFIKASI
// ======================================================

$nama = $karyawan["nama"];
$nik = $karyawan["nik"];
$no_hp = $karyawan["no_hp"];


// ======================================================
// HAPUS DATA
// ======================================================

$hapus = $conn->prepare(
    "DELETE FROM karyawan
     WHERE id = ?"
);

$hapus->bind_param(
    "i",
    $id
);


if (!$hapus->execute()) {

    $hapus->close();

    $_SESSION["hapus_status"] = "error";

    $_SESSION["hapus_pesan"] =
        "Data karyawan gagal dihapus.";

    header("Location: index.php");
    exit;
}

$hapus->close();


// ======================================================
// KIRIM WHATSAPP KE KARYAWAN
// ======================================================

$pesanKaryawan =
    "🔔 *DATA KARYAWAN DIHAPUS*\n\n" .
    "Halo *" . $nama . "*," .
    "\n\n" .
    "Data karyawan kamu telah dihapus dari sistem." .
    "\n\n" .
    "📋 *Data Karyawan*\n" .
    "NIK: " . $nik . "\n" .
    "Nama: " . $nama . "\n\n" .
    "Jika penghapusan ini tidak kamu lakukan, " .
    "silakan hubungi admin.\n\n" .
    "— KaryawanHub";


$hasilWA = sendWhatsApp(
    $no_hp,
    $pesanKaryawan
);


// ======================================================
// CEK HASIL WHATSAPP KARYAWAN
// ======================================================

$waBerhasil = false;

$waReason = "WhatsApp gagal dikirim.";


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

        $waReason = $hasilWA["reason"];

    } elseif (isset($hasilWA["message"])) {

        $waReason = $hasilWA["message"];
    }
}


// ======================================================
// KIRIM WHATSAPP KE ADMIN
// ======================================================

$adminWAberhasil = false;


if (
    defined("ADMIN_WA") &&
    trim(ADMIN_WA) !== "" &&
    ADMIN_WA !== "628xxxxxxxxxx"
) {

    $pesanAdmin =
        "🗑️ *DATA KARYAWAN DIHAPUS*\n\n" .
        "Data berikut telah dihapus dari sistem:\n\n" .
        "Nama: " . $nama . "\n" .
        "NIK: " . $nik . "\n" .
        "No. WhatsApp: " . $no_hp . "\n\n" .
        "— KaryawanHub";


    $hasilAdminWA = sendWhatsApp(
        ADMIN_WA,
        $pesanAdmin
    );


    if (is_array($hasilAdminWA)) {

        if (
            isset($hasilAdminWA["status"]) &&
            (
                $hasilAdminWA["status"] === true ||
                $hasilAdminWA["status"] === "true" ||
                $hasilAdminWA["status"] === 1 ||
                $hasilAdminWA["status"] === "1"
            )
        ) {

            $adminWAberhasil = true;
        }
    }
}


// ======================================================
// SIMPAN STATUS KE SESSION
// ======================================================

$_SESSION["hapus_status"] = "success";


if ($waBerhasil) {

    $_SESSION["hapus_pesan"] =
        "Data karyawan berhasil dihapus " .
        "dan notifikasi WhatsApp berhasil dikirim ke " .
        $nama . ".";

} else {

    $_SESSION["hapus_pesan"] =
        "Data karyawan berhasil dihapus, " .
        "tetapi WhatsApp gagal dikirim. " .
        "Alasan: " . $waReason;
}


// ======================================================
// REDIRECT KE INDEX
// ======================================================

header("Location: index.php");

exit;