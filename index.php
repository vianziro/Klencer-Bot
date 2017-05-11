<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot\SignatureValidator as SignatureValidator;

require 'handler.php';

function sendResponse($code, $phrase){
    header("HTTP/1.1 " . $code . ' ' . $phrase);
}

function getTimestamp(){
    return date('Y-m-d h:i:sa');
}

// get request body and line signature header
$body 	   = file_get_contents('php://input');
if(isset($_SERVER['HTTP_X_LINE_SIGNATURE'])){
    $signature_header = $_SERVER['HTTP_X_LINE_SIGNATURE'];
    $channelSecret = "18e2fbcf379ac877ab49561a3142806d"; // Channel secret string
$channelAccess = "cVQepYZwQI53B4bUbaDL6DNoE5U6ZMwG5rahjcqDhU3QSPvp9VO2D6aC/jPVRZN2YfR2fIGe1tdQyi7TWWw8TPdpNKVPKG/bI5BLf9r3zggonvICMceuyuRl4MamBxUEKsHi2wBY1Pqqj5oIGsX5rgdB04t89/1O/w1cDnyilFU="; // Channel access token
    $hash = hash_hmac('sha256', $body, $channelSecret, true);
    $signature = base64_encode($hash);

    // Compare X-Line-Signature request header string and the signature
    if($signature_header !== $signature) {http_response_code(400); die('dead');}
    
    // init bot
	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($channelAccess);
	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);

	$data = json_decode($body, true);
    
	foreach ($data['events'] as $event)
	{
		if ($event['type'] == 'message')
		{
			if($event['message']['type'] == 'text')
			{
                if(isset($event['source']['userId'])){
                    handler($event);
                } else {
                    $request = "https://www.bot.mekarya.com/groupHandler.php";
                    $data_string = json_encode($event);
                    $ch = curl_init($request);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
                    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                        'Content-Type: application/json',                                                                                
                        'Content-Length: ' . strlen($data_string))                                                                       
                    );
                    $response = curl_exec($ch);
                    curl_close($ch);
                }
            }
		} else if ($event['type'] == 'follow')
        {
                $response = $bot->getProfile($event['source']['userId']);
                if ($response->isSucceeded())
                {
                    $profile = $response->getJSONDecodedBody();
                    $bot->replyText($event['replyToken'], 'Hai, ' . $profile['displayName'] . ' :) Perkenalkan nama saya Klencer. Disini saya akan membantu kamu mengelola event wisata. Meliputi event KKL, Studi Wisata, Umroh, Wisata Rohani, dan Wisata Kelompok lainnya. Langsung saja klik menu di bagian bawah yaaa');
                    sendResponse($result->getHTTPStatus(), $result->getRawBody());
                }
        } else if ($event['type'] == 'postback')
        {
            if($event['source']['type']=="group"){
                if($event['postback']['data'] == 'follow'){
                    $bot->replyText($event['replyToken'], "Usage :\n.follow [event code]");
                }
            } else {
                handler($event);
            }
        } else if ($event['type'] == 'join'){        	
                $bot->replyText($event['replyToken'], 'Halo semua! Thank you for adding this ChatBot to your group. To follow an event, you can send message with format ".follow @codeevent". Try the demo with follow @kklektroundip2017.For more information, send ".help" message.');
                sendResponse($result->getHTTPStatus(), $result->getRawBody());
        } else {
        	$bot->replyText(json_encode($event));
        }
	}
} else {
    //echo phpinfo();
    header("Location: https://www.ailo.mekarya.com");
}
				
