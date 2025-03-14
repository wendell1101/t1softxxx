<?php
$api_url = !empty($data['url']) ? $data['url'] : null;
$username = !empty($data['username']) ? $data['username'] : null;
$password = !empty($data['password']) ? $data['password'] : null;

// Set headers to allow CORS (if frontend needs access)
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html");

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic " . base64_encode("$username:$password")
]);

$response = curl_exec($ch);
curl_close($ch);

// Output the response as HTML
echo $response;
?>
