<?php

header("Content-Type: application/json");

if (!isset($_GET['city']) || $_GET['city'] == "") {
    echo json_encode([]);
    exit;
}

$city = urlencode($_GET['city']);

$url = "https://nominatim.openstreetmap.org/search?city={$city}&country=India&format=jsonv2&addressdetails=1&limit=1";

$options = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: StudentPortal/1.0\r\n"
    ]
];

$context = stream_context_create($options);

$response = file_get_contents($url, false, $context);

echo $response;