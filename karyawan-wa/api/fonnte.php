<?php

/*
|--------------------------------------------------------------------------
| KONFIGURASI FONNTE
|--------------------------------------------------------------------------
*/

define("FONNTE_TOKEN", "yfxtoYucogjTeeBr1RDu");

/*
|--------------------------------------------------------------------------
| NOMOR WHATSAPP ADMIN
|--------------------------------------------------------------------------
| Ganti dengan nomor WhatsApp admin sebenarnya.
| Contoh: 6281234567890
|--------------------------------------------------------------------------
*/

define("ADMIN_WA", "628xxxxxxxxxx");


/*
|--------------------------------------------------------------------------
| NORMALISASI NOMOR WHATSAPP
|--------------------------------------------------------------------------
*/

function normalizeWhatsAppNumber($number)
{
    $number = preg_replace('/[^0-9]/', '', $number);

    if (empty($number)) {
        return "";
    }

    // 08xxxxxxxxxx -> 628xxxxxxxxxx
    if (substr($number, 0, 1) === "0") {
        $number = "62" . substr($number, 1);
    }

    return $number;
}


/*
|--------------------------------------------------------------------------
| KIRIM WHATSAPP MELALUI FONNTE
|--------------------------------------------------------------------------
*/

function sendWhatsApp($target, $message)
{
    /*
    |--------------------------------------------------------------------------
    | CEK TOKEN
    |--------------------------------------------------------------------------
    */

    if (
        !defined("FONNTE_TOKEN") ||
        trim(FONNTE_TOKEN) === ""
    ) {
        return [
            "status" => false,
            "reason" => "Token Fonnte belum diatur."
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | CEK CURL
    |--------------------------------------------------------------------------
    */

    if (!function_exists("curl_init")) {
        return [
            "status" => false,
            "reason" => "PHP cURL belum aktif."
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALISASI NOMOR
    |--------------------------------------------------------------------------
    */

    $target = normalizeWhatsAppNumber($target);

    if (empty($target)) {
        return [
            "status" => false,
            "reason" => "Nomor WhatsApp tujuan tidak valid."
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI PESAN
    |--------------------------------------------------------------------------
    */

    if (trim($message) === "") {
        return [
            "status" => false,
            "reason" => "Pesan WhatsApp tidak boleh kosong."
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | CURL FONNTE
    |--------------------------------------------------------------------------
    */

    $curl = curl_init();

    curl_setopt_array($curl, [

        CURLOPT_URL => "https://api.fonnte.com/send",

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_ENCODING => "",

        CURLOPT_MAXREDIRS => 10,

        CURLOPT_TIMEOUT => 30,

        CURLOPT_CONNECTTIMEOUT => 10,

        CURLOPT_FOLLOWLOCATION => true,

        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

        CURLOPT_CUSTOMREQUEST => "POST",

        CURLOPT_POSTFIELDS => [
            "target" => $target,
            "message" => $message,
            "countryCode" => "62"
        ],

        CURLOPT_HTTPHEADER => [
            "Authorization: " . FONNTE_TOKEN
        ]

    ]);


    /*
    |--------------------------------------------------------------------------
    | EKSEKUSI REQUEST
    |--------------------------------------------------------------------------
    */

    $response = curl_exec($curl);


    /*
    |--------------------------------------------------------------------------
    | CEK ERROR CURL
    |--------------------------------------------------------------------------
    */

    if ($response === false) {

        $error = curl_error($curl);

        curl_close($curl);

        return [
            "status" => false,
            "reason" => "Gagal terhubung ke Fonnte: " . $error
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | AMBIL HTTP STATUS
    |--------------------------------------------------------------------------
    */

    $httpCode = curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );

    curl_close($curl);


    /*
    |--------------------------------------------------------------------------
    | CONVERT RESPONSE JSON
    |--------------------------------------------------------------------------
    */

    $result = json_decode(
        $response,
        true
    );


    /*
    |--------------------------------------------------------------------------
    | RESPONSE TIDAK VALID
    |--------------------------------------------------------------------------
    */

    if (!is_array($result)) {

        return [
            "status" => false,
            "reason" => "Response Fonnte tidak valid.",
            "http_code" => $httpCode,
            "raw_response" => $response
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | TAMBAHKAN HTTP STATUS
    |--------------------------------------------------------------------------
    */

    $result["http_code"] = $httpCode;


    /*
    |--------------------------------------------------------------------------
    | RETURN RESPONSE FONNTE
    |--------------------------------------------------------------------------
    */

    return $result;
}