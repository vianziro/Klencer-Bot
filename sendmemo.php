<?php

require __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('Asia/Jakarta');

use \LINE\LINEBot\SignatureValidator as SignatureValidator;

// SDK for build message
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder as TemplateMessageBuilder;
use \LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder as ConfirmTemplateBuilder;

// SDK for build button and template action
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder as MessageTemplateActionBuilder;
use \LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;

$channelSecret = "18e2fbcf379ac877ab49561a3142806d"; // Channel secret string
$channelAccess = "cVQepYZwQI53B4bUbaDL6DNoE5U6ZMwG5rahjcqDhU3QSPvp9VO2D6aC/jPVRZN2YfR2fIGe1tdQyi7TWWw8TPdpNKVPKG/bI5BLf9r3zggonvICMceuyuRl4MamBxUEKsHi2wBY1Pqqj5oIGsX5rgdB04t89/1O/w1cDnyilFU="; // Channel access token

// init bot
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($channelAccess);
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);

$link = new mysqli('localhost', 'mekarya_ailo', 'l4bk0mbkt1', 'mekarya_ailo');
$time = date('H:i');
$res = $link->query("SELECT * FROM memo WHERE waktu='".$time."'");
while($data = $res->fetch_assoc()){
    $bot->pushText($data['id_user'], "[MEMO ".$data['waktu']."]\n".$data['text']);
    
    $options[] = new PostbackTemplateActionBuilder('Ulangi 5 menit lagi', 'intent=snooze+memo&id='.$data['id'].'&text='.$data['text'].'&snooze=5');
    $options[] = new PostbackTemplateActionBuilder('Ulangi 10 menit lagi', 'intent=snooze+memo&id='.$data['id'].'&text='.$data['text'].'&snooze=10');
    $options[] = new PostbackTemplateActionBuilder('Atur waktu lagi', 'intent=snooze+memo&id='.$data['id'].'&text='.$data['text']);

    $buttonMessage = new ButtonTemplateBuilder(null, "Apakah mau diingatkan kembali?", null, $options);
    $templateMessage = new TemplateMessageBuilder("[".$data['waktu']."] ".$data['text'], $buttonMessage);
    $bot->pushMessage($data['id_user'], $templateMessage);
}
$link->query("DELETE FROM memo WHERE waktu='".$time."'");