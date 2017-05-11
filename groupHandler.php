<?php

require __DIR__ . '/vendor/autoload.php';

$event = json_decode(file_get_contents('php://input'), true);

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
use \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;

function getTimestamp(){
    return date('Y-m-d h:i:sa');
}

$link = new mysqli('localhost', 'mekarya_ailo', 'l4bk0mbkt1', 'mekarya_ailo');

function multiexplode ($delimiters,$string) {
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}

$channelSecret = "18e2fbcf379ac877ab49561a3142806d"; // Channel secret string
$channelAccess = "cVQepYZwQI53B4bUbaDL6DNoE5U6ZMwG5rahjcqDhU3QSPvp9VO2D6aC/jPVRZN2YfR2fIGe1tdQyi7TWWw8TPdpNKVPKG/bI5BLf9r3zggonvICMceuyuRl4MamBxUEKsHi2wBY1Pqqj5oIGsX5rgdB04t89/1O/w1cDnyilFU="; // Channel access token

// init bot
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($channelAccess);
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);

$cmds = explode(' ', $event['message']['text']);

if(strtolower($cmds[0]) == '.help')
{
	$options[] = new MessageTemplateActionBuilder('About AILO', '.About');
	$options[] = new MessageTemplateActionBuilder('Command Group', '.Command group');
    $buttonMessage = new ButtonTemplateBuilder(null, "AILO is a LINE ChatBot that will help you manage tour event.", null, $options);
    $templateMessage = new TemplateMessageBuilder("new message", $buttonMessage);
    $bot->pushMessage($event['source']['groupId'], $templateMessage);
} else if(strtolower($cmds[0]) == '.command')
{
    if(strtolower($cmds[1]) == 'group'){
        $options1[] = new PostbackTemplateActionBuilder('Follow Event', 'follow');
        $options1[] = new MessageTemplateActionBuilder('Leave Event', 'Leave Event');
        $options1[] = new MessageTemplateActionBuilder(' ', ' ');
        
        $options2[] = new MessageTemplateActionBuilder('Detail Event', 'Get Detail');               
        $options2[] = new MessageTemplateActionBuilder('Schedule', 'Get Schedule');
        $options2[] = new MessageTemplateActionBuilder('Rules', 'Get Rules');
        
        $options3[] = new MessageTemplateActionBuilder('Transport Seat', 'Get General Transport');               
        $options3[] = new MessageTemplateActionBuilder('Group Division', 'Get General Group');
        $options3[] = new MessageTemplateActionBuilder('Get Rules', 'Get Rules');
        
        $columns[] = new CarouselColumnTemplateBuilder("(1/3) Group or ChatRoom Command", "Initial Commands :", null, $options1);
        $columns[] = new CarouselColumnTemplateBuilder("(2/3) Group or ChatRoom Command", "Get Event Info :", null, $options2);
        $columns[] = new CarouselColumnTemplateBuilder("(3/3) Group orChatRoom Command", "Get General Event Info :", null, $options3);
        $carouselMessage = new CarouselTemplateBuilder($columns);
        $templateMessage = new TemplateMessageBuilder("new message", $carouselMessage);
        $bot->pushMessage($event['source']['groupId'], $templateMessage);
        session_destroy();
        } else {
        $bot->replyText($event['replyToken'],'Command not recognized.');
    }  
} else if(strtolower($cmds[0]) == '.about')
{
    $bot->replyText($event['replyToken'],'AILO is a LINE ChatBot that will help you manage tour event. There are at least two kind of user level, such as Events Creator and Tour Members. Events Creator can manage events and the tour members participant. Everyone can create an event and manage the event. A creator can manage the details of an event such as tour destinations, schedule, transport, groups, tour members, etc. 
    The creator can send a broadcast message for all tour members to inform something important. The creator can also send a confirmation message (e.g. attendance confirmation at the transportation seat) for all tour members via 1:1 chat with AILO.
    The tour members which have joined an event can ask AILO to get any information of the event they are in, such as schedule, transport seat number, groups, etc. At an urgent situation, a member can send an urgent message to the other members via 1:1 chat with AILO. AILO will send broadcast help message, including the sender location.
    AILO can be invited to a group or chat room. A member of group or chat room can follow an event. A member can ask AILO to get some general information of the followed event such as schedule, general transport seat, as well as broadcast messages concerning that event.
    ');
} else if(strtolower($cmds[0]) == '.follow')
{
    if(isset($cmds[1])){
        if($cmds[1][0]=='@') $cmds[1] = substr($cmds[1], 1);
        $res = $link->query("select * from Events where kd_event = '".$cmds[1]."'");
        if(mysqli_num_rows($res)){
            $res = $res->fetch_assoc();
            $link->query("INSERT INTO `Group`(id_event, id_group) VALUES(".$res['id_event'].", '".$event['source']['groupId']."')");

            if($link->error==""){
                $bot->replyText($event['replyToken'],'This group will receive any broadcast messages from '.$res['nama_event'].".");
            }else{
                $bot->replyText($event['replyToken'],"You might have followed another event. Unfollow it first by using \".unfollow\".");
            }
        } else {
            $bot->replyText($event['replyToken'],'Event @'.$cmds[1].' not found.');
        }
    } else {
        $bot->replyText($event['replyToken'],'Use this message to ...');
    }
} else if(strtolower($cmds[0]) == '.unfollow')
{
    $res1 = $link->query("DELETE FROM `Group` where id_group = '".$event['source']['groupId']."'");
    if($link->error==""){
        $bot->replyText($event['replyToken'],"You have unfollowed an event.");
    }else{
        $bot->replyText($event['replyToken'],"You have not followed any event yet.");
    }
}
// or we can use pushMessage() instead to send reply message
// $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event['message']['text']);
// $result = $bot->pushMessage($event['source']['groupId'], $textMessageBuilder);

//sendResponse($result->getHTTPStatus(), $result->getRawBody());
//echo $response->getHTTPStatus() . ' ' . $response->getRawBody();