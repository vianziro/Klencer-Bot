<?php
$request = "http://ariefatkhur.web.id";
$data_string = json_encode($event);
$ch = curl_init($request);
$response = curl_exec($ch);
curl_close($ch);

echo $response;