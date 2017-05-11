<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot\SignatureValidator as SignatureValidator;

// SDK for build message
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use \LINE\LINEBot\MessageBuilder\ImageMessageBuilder;

// SDK for build button and template action
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;

function sendResponse($code, $phrase){
    header("HTTP/1.1 " . $code . ' ' . $phrase);
}

$id = $_GET['id'];

sleep($_GET['t']);

$channelSecret = "3d8ec08a5b17e671d78d5fc379c3c65d"; // Channel secret string
$channelAccess = "GklA2en+0ZzszceIlGLrhAxzC0MVPgpzLQQafTWz7GRZYTR7rk0iMJW4MYTtI4FrxaXzG+q67HT/GZrjFENdoA9QYMm7w3wUafikwucV6a6d+yThOC/Wy11YRsPOxonSoH8KoG7kKm0xVzWGMCUmdwdB04t89/1O/w1cDnyilFU="; // Channel access token

$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($channelAccess);
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);

$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('hello');
$response = $bot->pushMessage($id, $textMessageBuilder);

echo $response->getHTTPStatus() . ' ' . $response->getRawBody();
