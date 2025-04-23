<?php
function cambioDolar($amount, $date): object {
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.apilayer.com/currency_data/convert?to=USD&from=HNL&amount=$amount&date=$date",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: text/plain",
            "apikey: VlzXp0OA3IHQrplFafPCIafsXY2caxyE"
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET"
    ));

    $res = curl_exec($curl);

    if ($res === false) {
        // Manejo de error de cURL
        return new stdClass(); // Devuelve un objeto vacío o maneja el error de otra manera
    }

    curl_close($curl);

    $res = json_decode($res);

    if ($res === null) {
        // Manejo de error al decodificar JSON
        return new stdClass(); // Devuelve un objeto vacío o maneja el error de otra manera
    }

    return $res;
}