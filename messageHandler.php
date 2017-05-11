<?php

require __DIR__ . '/vendor/autoload.php';

$event = json_decode(file_get_contents('php://input'), true);

session_id($event['source']['userId']);
session_start();

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

if(strtolower($cmds[0]) == 'whoami')
{
    $response = $bot->getProfile($event['source']['userId']);
    if ($response->isSucceeded())
    {
        $profile = $response->getJSONDecodedBody();
        $bot->replyText($event['replyToken'], json_encode($profile).' '.'ID: '.$event['source']['userId']);
    }
} else if(strtolower($cmds[0]) == 'remindme')
{
    $result = $bot->replyText($event['replyToken'], "kamu akan saya ingatkan dalam 1 menit. Updated");

    $request = "https://www.ailo.mekarya.com/sendhello.php?t=60&id=".$event['source']['userId'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $request);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    curl_exec($ch);
    curl_close($ch);
} else if(strtolower($cmds[0]) == 'bantuan')
{
		$options[] = new MessageTemplateActionBuilder('Tentang KLENCER', 'Tentang KLENCER');
		$options[] = new MessageTemplateActionBuilder('Kontak KLENCER', 'Kontak KLENCER');
		$options[] = new UriTemplateActionBuilder('Video Tutorial', 'http://bit.ly/klencer_video');	
		$buttonMessage = new ButtonTemplateBuilder(null, "Line Bot yang dapat membantu mengelola event wisata.", "https://www.bot.mekarya.com/img/klencer_logo_2.jpg", $options);
		$templateMessage = new TemplateMessageBuilder("Bantuan", $buttonMessage);
		$bot->pushMessage($event['source']['userId'], $templateMessage);
		session_destroy();
} else if(strtolower($cmds[0]) == 'kontak')
{
	if(strtolower($cmds[1]) == 'klencer'){
		$options[] = new UriTemplateActionBuilder('KLENCER CS', 'http://bit.ly/klencer_cs');	
		$buttonMessage = new ButtonTemplateBuilder(null, "Jika Kakak butuh bantuan, silahkan kontak ke Official Account KLENCER Customer Service.", null, $options);
		$templateMessage = new TemplateMessageBuilder("KLENCER CS", $buttonMessage);
		$bot->pushMessage($event['source']['userId'], $templateMessage);
		session_destroy();      
        } else {
            $bot->replyText($event['replyToken'],'Command not recognized.');
        }  
} else if(strtolower($cmds[0]) == 'atur')
{
    $res = $link->query("select * from Events where id_creator = '".$event['source']['userId']."'");
    if($res->num_rows>0){
        $res = $res->fetch_assoc();
        $_SESSION['set'] = array('timestamp'=>'', 'value'=> '', 'kd_event'=>$res['kd_event']);
        $_SESSION['set']['timestamp'] = getTimestamp();
        if(strtolower($cmds[1]) == 'destinasi'){
            $_SESSION['set']['value']='destination';
            $bot->replyText($event['replyToken'],"Masukkan destinasi event : \n-------------------------------------\nContoh : \nBali, Indonesia \n- Tanah Lot \n- Pantai Sanur \n- Pantai Kuta \n- dll");
        } else if(strtolower($cmds[1]) == 'jadwal'){
            $_SESSION['set']['value']='schedule';
            $bot->replyText($event['replyToken'],"Masukkan jadwal event : \n\nContoh : \nBali, Indonesia \n- Tanah Lot \n- Pantai Sanur \n- Pantai Kuta \n- dll");
        } else if(strtolower($cmds[1]) == 'aturan'){
            $_SESSION['set']['value']='rule';
            $bot->replyText($event['replyToken'],'Masukkan aturan event : ');     
        } else {
	        $res = $link->query("select * from Events where id_creator = '".$event['source']['userId']."'");
	        $res = $res->fetch_assoc();
		$options[] = new MessageTemplateActionBuilder('Destinasi Event', 'Atur Destinasi');
		$options[] = new MessageTemplateActionBuilder('Jadwal Event', 'Atur Jadwal');
		$options[] = new MessageTemplateActionBuilder('Aturan Event', 'Atur Aturan');
		$options[] = new UriTemplateActionBuilder('Lainnya', "https://www.config.ailo.mekarya.com/".$res['id_creator'].'/event');
		$buttonMessage = new ButtonTemplateBuilder(null, "Atur Kelengkapan Event", null, $options);
		$templateMessage = new TemplateMessageBuilder("new message", $buttonMessage);
		$bot->pushMessage($event['source']['userId'], $templateMessage);
		session_destroy();
        } 
    } else {
        $bot->replyText($event['replyToken'],'Kamu belum membuat sebuah event.');
    }
} else if(strtolower($cmds[0]) == 'minta')
{
    $res = $link->query("select * from Joiner where id_user = '".$event['source']['userId']."'");
    if($res->num_rows>0){
        $res = $res->fetch_assoc();
        $res = $link->query("select * from Events where id_event = '".$res['id_event']."'")->fetch_assoc();
        if(strtolower($cmds[1]) == 'rincian'){
            $bot->replyText($event['replyToken'],($res['nama_event']?$res['nama_event']:"Belum diatur.")." (@".$res['kd_event'].")\n\nDestinasi/Tempat Event : \n".($res['Destination']?$res['Destination']:"Belum diatur.")."\n\nWaktu Event : \n".($res['Time']?$res['Time']:"Belum diatur."));
        } else if(strtolower($cmds[1]) == 'jadwal'){
            $bot->replyText($event['replyToken'],"Jadwal Event : \n\n".($res['Schedule']?$res['Schedule']:"Belum diatur."));
        } else if(strtolower($cmds[1]) == 'aturan'){
            $bot->replyText($event['replyToken'],"Aturan Event : \n\n".($res['Rules']?$res['Rules']:"Belum diatur."));
        } else if(strtolower($cmds[1]) == 'kontak'){
            $bot->replyText($event['replyToken'],"ID Line Penyelenggara Event : \n\n".($res['Contact']?$res['Contact']:"Belum diatur."));  
        } else if(strtolower($cmds[1]) == 'tempat'){
            $options[] = new MessageTemplateActionBuilder('Kontak Penyelenggara', 'Minta Kontak Penyelenggara');
	    $buttonMessage = new ButtonTemplateBuilder(null, "Tempat duduk belum diatur. Harap hubungi penyelenggara event.", null, $options);
	    $templateMessage = new TemplateMessageBuilder("new message", $buttonMessage);
	    $bot->pushMessage($event['source']['userId'], $templateMessage);       
        } else if(strtolower($cmds[1]) == 'info'){
            $options[] = new MessageTemplateActionBuilder('Rincian Event', 'Minta Rincian Event');
            $options[] = new MessageTemplateActionBuilder('Jadwal Event', 'Minta Jadwal Event');
            $options[] = new MessageTemplateActionBuilder('Aturan Event', 'Minta Aturan Event');
            $options[] = new MessageTemplateActionBuilder('Tempat Duduk', 'Minta Tempat Duduk');
	        $buttonMessage = new ButtonTemplateBuilder(null, "Informasi Event", null, $options);
	        $templateMessage = new TemplateMessageBuilder("new message", $buttonMessage);
	        $bot->pushMessage($event['source']['userId'], $templateMessage);
        } else {
            $result = $bot->replyText($event['replyToken'], "Maaf, saya kurang paham yang kamu maksud.");
            $options[] = new MessageTemplateActionBuilder('Minta Info Event', 'Minta Info Event');
            $options[] = new MessageTemplateActionBuilder('Buat Event', 'Buat Event');
            $options[] = new MessageTemplateActionBuilder('Gabung Event', 'Gabung Event');
            $options[] = new MessageTemplateActionBuilder('Bantuan', 'Bantuan');
	        $buttonMessage = new ButtonTemplateBuilder(null, "Ada yang bisa saya bantu?", null, $options);
	        $templateMessage = new TemplateMessageBuilder("new message", $buttonMessage);
	        $bot->pushMessage($event['source']['userId'], $templateMessage);
        } 
    } else {
        $bot->replyText($event['replyToken'],'kamu belum bergabung dengan salah satu event.');
    }
} else if(strtolower($cmds[0]) == 'tinggalkan')
{
    $res = $link->query("select * from Joiner where id_user = '".$event['source']['userId']."'");
    if($res->num_rows>0){
        $res = $link->query("select * from Events where id_event = '".$res = $res->fetch_assoc()['id_event']."'");
        $res = $res->fetch_assoc();
        $options[] = new MessageTemplateActionBuilder('Ya', 'Ya');
        $options[] = new MessageTemplateActionBuilder('Tidak', 'Tidak');
        
        if($res['id_creator']==$event['source']['userId']){
            $buttonTemplate = new ConfirmTemplateBuilder("Kamu adalah pembuat event ini. Meninggalkan event berarti menghapus event ini dari sistem. Apakah kamu yakin ingin meninggalkan ".$res['nama_event']." (@".$res['kd_event'].")?", $options);
            $_SESSION['leave'] = array('is_creator'=>true);
        } else {
            $buttonTemplate = new ConfirmTemplateBuilder("Apakah kamu yakin ingin meninggalkan ".$res['nama_event']." (@".$res['kd_event'].")?", $options);
        	$_SESSION['leave'] = array('is_creator'=>false);            
        }
        $messageBuilder = new TemplateMessageBuilder("Gunakan aplikasi mobile untuk melihat pesan ini.", $buttonTemplate);
        $result = $bot->replyMessage($event['replyToken'], $messageBuilder);        
    } else {
        $bot->replyText($event['replyToken'],'Kamu belum bergabung dengan salah satu event.');
    }
} else if(strtolower($cmds[0]) == 'urgent')
{
	$bot->replyText($event['replyToken'],'Sorry, command is not available yet.');	  
} else if(strtolower($cmds[0]) == 'kelola')
{
	$res = $link->query("select * from Events where id_creator = '".$event['source']['userId']."'");
        $res = $res->fetch_assoc();
	$bot->replyText($event['replyToken'],'Kamu bisa mengelola event pada www.config.ailo.mekarya.com/home/'.$res['id_creator']);
} else if(strtolower($cmds[0]) == 'menu')
{
    if(strtolower($cmds[1]) == ''){
	$options1[] = new MessageTemplateActionBuilder('Menu Penyelenggara', 'Menu Penyelenggara');
        $options2[] = new MessageTemplateActionBuilder('Menu Peserta', 'Menu Peserta');
        $options3[] = new MessageTemplateActionBuilder('Menu di Grup', 'Menu Grup');
        $columns[] = new CarouselColumnTemplateBuilder("Menu Penyelenggara Event", "Daftar Perintah untuk Penyeleggara Event", null, $options1);
        $columns[] = new CarouselColumnTemplateBuilder("Menu Peserta Event", "Daftar Perintah untuk Peserta Event", null, $options2);
        $columns[] = new CarouselColumnTemplateBuilder("Menu di Grup", "Daftar Perintah di Grup", null, $options3);
        $carouselMessage = new CarouselTemplateBuilder($columns);
        $templateMessage = new TemplateMessageBuilder("new message", $carouselMessage);
        $bot->pushMessage($event['source']['userId'], $templateMessage);
        session_destroy();
        }
 else if(strtolower($cmds[1]) == 'penyelenggara'){
 $res = $link->query("select * from Events where id_creator = '".$event['source']['userId']."'");
        $res = $res->fetch_assoc();
	$options1[] = new MessageTemplateActionBuilder('Buat Event', 'Buat Event');
	$options1[] = new UriTemplateActionBuilder('Kelola Event', "https://www.config.klencer.mekarya.com/home/".$res['id_creator']);
        $options1[] = new MessageTemplateActionBuilder('Hapus Event', 'Tinggalkan Event');
        
       	$options2[] = new MessageTemplateActionBuilder('Kirim Broadcast', 'BC');
        $options2[] = new MessageTemplateActionBuilder('BC dengan Konfirmasi', 'BC Konfirmasi');
        $options2[] = new MessageTemplateActionBuilder('Minta Daftar Peserta', 'Minta Daftar Peserta');
        
        $options3[] = new MessageTemplateActionBuilder('Atur Destinasi', 'Atur Destinasi');               
        $options3[] = new MessageTemplateActionBuilder('Atur Jadwal', 'Atur Jadwal');
        $options3[] = new MessageTemplateActionBuilder('Atur Aturan', 'Atur Aturan');
        
        $options4[] = new MessageTemplateActionBuilder('Minta Rincian Event', 'Minta Rincian');               
        $options4[] = new MessageTemplateActionBuilder('Minta Jadwal', 'Minta Jadwal');
        $options4[] = new MessageTemplateActionBuilder('Minta Aturan', 'Minta Aturan');
        
        $columns[] = new CarouselColumnTemplateBuilder("Menu Penyelenggara [1]", "Perintah Awal :", null, $options1);
        $columns[] = new CarouselColumnTemplateBuilder("Menu Penyelenggara [2]", "Perintah Utama :", null, $options2);
        $columns[] = new CarouselColumnTemplateBuilder("Menu Penyelenggara [3]", "Atur Info Event :", null, $options3);
        $columns[] = new CarouselColumnTemplateBuilder("Menu Penyelenggara [4]", "Minta Info Event :", null, $options4);
        $carouselMessage = new CarouselTemplateBuilder($columns);
        $templateMessage = new TemplateMessageBuilder("new message", $carouselMessage);
        $bot->pushMessage($event['source']['userId'], $templateMessage);
        session_destroy();
        } 
 else if(strtolower($cmds[1]) == 'peserta'){
	$options1[] = new MessageTemplateActionBuilder('Gabung Event', 'Gabung Event');
        $options1[] = new MessageTemplateActionBuilder('Tinggalkan Event', 'Tinggalkan Event');
        $options1[] = new MessageTemplateActionBuilder(' ', ' ');
        
       	$options2[] = new MessageTemplateActionBuilder('Rincian Event', 'Minta Rincian');   
        $options2[] = new MessageTemplateActionBuilder('Jadwal Event', 'Minta Jadwal');
        $options2[] = new MessageTemplateActionBuilder('Aturan Event', 'Minta Aturan');
                    
        $options3[] = new MessageTemplateActionBuilder('Info Tempat Duduk', 'Minta Tempat Duduk'); 
        $options3[] = new MessageTemplateActionBuilder('Kontak Penyelenggara', 'Minta Kontak Penyelenggara');
        $options3[] = new MessageTemplateActionBuilder(' ', ' ');
        
        $columns[] = new CarouselColumnTemplateBuilder("Menu Peserta Event [1]", "Perintah Awal :", null, $options1);
        $columns[] = new CarouselColumnTemplateBuilder("Menu Peserta Event [2]", "Minta Info Event :", null, $options2);
        $columns[] = new CarouselColumnTemplateBuilder("Menu Peserta Event [3]", "Minta Info Event :", null, $options3);
        $carouselMessage = new CarouselTemplateBuilder($columns);
        $templateMessage = new TemplateMessageBuilder("Menu Peserta", $carouselMessage);
        $bot->pushMessage($event['source']['userId'], $templateMessage);
        session_destroy();        
        }
 else if(strtolower($cmds[1]) == 'grup'){
 
	$bot->replyText($event['replyToken'],'Perintah ini hanya untuk di grup.');       
        } else {
		$result = $bot->replyText($event['replyToken'], "Maaf, saya kurang paham yang kamu maksud.");
		$options[] = new MessageTemplateActionBuilder('Minta Info Event', 'Minta Info Event');
		$options[] = new MessageTemplateActionBuilder('Buat Event', 'Buat Event');
		$options[] = new MessageTemplateActionBuilder('Gabung Event', 'Gabung Event');
		$options[] = new MessageTemplateActionBuilder('Bantuan', 'Bantuan');
	        $buttonMessage = new ButtonTemplateBuilder(null, "Ada yang bisa saya bantu?", null, $options);
	        $templateMessage = new TemplateMessageBuilder("new message", $buttonMessage);
	        $bot->pushMessage($event['source']['userId'], $templateMessage);
        }  
} else if(strtolower($cmds[0]) == 'tentang')
{
    if(strtolower($cmds[1]) == 'klencer'){
	$bot->replyText($event['replyToken'],'Banyak para agen wisata masih mengelola event wisata dengan cara manual. Seperti pengaturan jadwal, pembagian tempat duduk, dan pembagian kamar para peserta event. Selain itu, penyebaran info event tersebut dari agen wisata kepada peserta event wisata masih dengan cara yang kurang efektif. Yaitu masih menggunakan kertas, yang kurang efektif dan efisien. KLENCER hadir untuk memberikan solusi mengelola event wisata dengan cara yang mudah dan interaktif kepada para peserta event wisata.

Awalnya penyelenggara membuat sebuah event dengan id event yang unik. Kemudian para peserta dapat bergabung dengan event tersebut melalui id event yang telah disebarkan oleh penyelenggara. Penyelenggara dapat mengelola event dan juga para peserta dapat memperoleh info event. Sehingga KLENCER dapat menghubungkan antara penyelenggara dan para peserta event wisata secara langsung.');
        session_destroy();
        } else {
					$result = $bot->replyText($event['replyToken'], "Maaf, saya kurang paham yang Kakak maksud.");
					$options[] = new MessageTemplateActionBuilder('Minta Info Event', 'Minta Info Event');
					$options[] = new MessageTemplateActionBuilder('Buat Event', 'Buat Event');
					$options[] = new MessageTemplateActionBuilder('Gabung Event', 'Gabung Event');
					$options[] = new MessageTemplateActionBuilder('Bantuan', 'Bantuan');
	        $buttonMessage = new ButtonTemplateBuilder(null, "Ada yang bisa saya bantu Kak?", null, $options);
	        $templateMessage = new TemplateMessageBuilder("new message", $buttonMessage);
	        $bot->pushMessage($event['source']['userId'], $templateMessage);
        }      
} else if(strtolower($cmds[0]) == 'buat' && empty($_SESSION))
{
    if(strtolower($cmds[1]) == 'event'){
        $res = $link->query("select * from Events where id_creator = '".$event['source']['userId']."'");
        if($res->num_rows>0){
            $res = $res->fetch_assoc();
            $bot->replyText($event['replyToken'],'Kamu telah membuat event '."\n".$res['nama_event'].' (@'.$res['kd_event'].'). Hapus event yang telah kamu buat terlebih dahulu sebelum membuat event yang lain.');
        } else {
            $_SESSION['create_event'] = array('timestamp'=>'', 'nama_event'=> '', 'kd_event'=>'');
            $_SESSION['create_event']['timestamp'] = getTimestamp();
            $bot->replyText($event['replyToken'],'Masukkan nama event : ');
        }
    }
} else if(strtolower($cmds[0]) == 'tinggalkan' && empty($_SESSION))
{
    $res = $link->query("select Joiner.*, Events.* from Joiner, Events where Joiner.id_event = Events.id_event AND Joiner.id_user = '".$event['source']['userId']."'");
    
    if($res->num_rows>0){
        $res = $res->fetch_assoc();
        $bot->replyText($event['replyToken'],'Saya menerima permintaan meninggalkan event darimu tapi saya tidak bisa menangani hal tersebut. Saya minta maaf atas ketidaknyamanannya.');
    } else {
        $bot->replyText($event['replyToken'],'Kamu belum bergabung dengan salah satu event.');
    }
} else if(strtolower($cmds[0]) == 'gabung' && empty($_SESSION))
{
    $res = $link->query("select Joiner.*, Events.* from Joiner, Events where Joiner.id_event = Events.id_event AND Joiner.id_user = '".$event['source']['userId']."'");
    
    if($res->num_rows>0){
        $res = $res->fetch_assoc();
        $bot->replyText($event['replyToken'],'Kamu sudah bergabung dengan '.$res['nama_event'].' (@'.$res['kd_event'].'). Tinggalkan event itu terlebih dahulu sebelum bergabung dengan event yang lain.');
    } else if(strtolower($cmds[1]) == 'event'){
        $_SESSION['join_event'] = array('timestamp'=>'', 'nama_event'=>'', 'kd_event'=>'', 'nama'=>'', 'no_telepon'=>'');
        $_SESSION['join_event']['timestamp'] = getTimestamp();
        $bot->replyText($event['replyToken'],'Masukkan kode event : ');
    } else {
        if($cmds[1][0]=='@') $cmds[1] = substr($cmds[1], 1);
        
        $res = $link->query("select Joiner.*, Events.* from Joiner, Events where Joiner.id_event = Events.id_event AND Joiner.id_user = '".$event['source']['userId']."' AND Events.kd_event = '".$cmds[1]."'");
        if($res->num_rows>0){
            $res = $res->fetch_assoc();
            $bot->replyText($event['replyToken'],'Kamu telah bergabung dengan '.$res['nama_event'].' (@'.$res['kd_event'].').');
        } else {
            $res = $link->query("select * from Events where kd_event = '".$cmds[1]."'");
            if(mysqli_num_rows($res)){
                $res = $res->fetch_assoc();
                $_SESSION['join_event'] = array('timestamp'=>'', 'nama_event'=>$res['nama_event'], 'id_event'=>$res['id_event'], 'nama'=>'', 'no_telepon'=>'');
                $_SESSION['join_event']['timestamp'] = getTimestamp();

                $bot->replyText($event['replyToken'],'Kode event sesuai. Masukkan nama asli kamu : ');
            } else {
                $bot->replyText($event['replyToken'],'Event @'.$cmds[1].' tidak ditemukan.');
            }
        }
    }
} else if(strtolower($cmds[0]) == 'bc' && empty($_SESSION))
{
    if(strtolower($cmds[1]) == ''){
	$_SESSION['broadcasting'] = array('timestamp'=>'', 'message'=>'');
    	$_SESSION['broadcasting']['timestamp'] = getTimestamp();
    	$bot->replyText($event['replyToken'],'Masukkan pesan broadcast :');
        } 
    else if(strtolower($cmds[1]) == 'confirmation'){
	$bot->replyText($event['replyToken'],'Maaf ya Kak, fitur ini belum tersedia.');
        } else {
           	$result = $bot->replyText($event['replyToken'], "Maaf, saya kurang paham yang Kakak maksud.");
		$options[] = new MessageTemplateActionBuilder('Minta Info Event', 'Minta Info Event');
		$options[] = new MessageTemplateActionBuilder('Buat Event', 'Buat Event');
		$options[] = new MessageTemplateActionBuilder('Gabung Event', 'Gabung Event');
		$options[] = new MessageTemplateActionBuilder('Bantuan', 'Bantuan');
	        $buttonMessage = new ButtonTemplateBuilder(null, "Ada yang bisa saya bantu?", null, $options);
	        $templateMessage = new TemplateMessageBuilder("pesan baru", $buttonMessage);
	        $bot->pushMessage($event['source']['userId'], $templateMessage);
        }
} else if(strtolower($cmds[0]) == 'batal')
{
    session_destroy();
    $bot->replyText($event['replyToken'],'Permintaanmu telah dibatalkan.');
} else if(strtolower($cmds[0]) == 'hai'||strtolower($cmds[0]) == 'hello'||strtolower($cmds[0]) == 'helo'||strtolower($cmds[0]) == 'halo'||strtolower($cmds[0]) == 'hallo'||strtolower($cmds[0]) == 'pagi'||strtolower($cmds[0]) == 'siang'||strtolower($cmds[0]) == 'malam')
{
		$options[] = new MessageTemplateActionBuilder('Minta Info Event', 'Minta Info Event');
		$options[] = new MessageTemplateActionBuilder('Buat Event', 'Buat Event');
		$options[] = new MessageTemplateActionBuilder('Gabung Event', 'Gabung Event');
		$options[] = new MessageTemplateActionBuilder('Bantuan', 'Bantuan');
		$buttonMessage = new ButtonTemplateBuilder(null, "Halo Kak, ada yang bisa saya bantu?", null, $options);
		$templateMessage = new TemplateMessageBuilder("pesan baru", $buttonMessage);
		$bot->pushMessage($event['source']['userId'], $templateMessage);  
} else {
    if(isset($_SESSION['create_event'])){
        if($_SESSION['create_event']['nama_event']==''){
            $_SESSION['create_event']['nama_event'] = $event['message']['text'];
            $bot->replyText($event['replyToken'],'Input the event code : ');
        } else if ($_SESSION['create_event']['kd_event']==''){
            if(count(multiexplode(array(' ', ',', '|', '@', '%', '*', '$', '!', '#', '^', '(', ')', '+', '='), $event['message']['text'])) == 1){
                $_SESSION['create_event']['kd_event'] = $event['message']['text'];
                
                $res = $link->query("select * from Events where id_creator = '".$event['source']['userId']."'")->fetch_assoc();
                
                $_SESSION['join_event'] = array('timestamp'=>'', 'nama_event'=>'', 'id_event'=>'', 'nama'=>'', 'no_telepon'=>'', 'create_event'=>$_SESSION['create_event']);
                $_SESSION['join_event']['timestamp'] = getTimestamp();

                $bot->replyText($event['replyToken'],'Harap isi data pribadi dulu ya. Sekarang masukkan nama asli kamu : ');
                
                $_SESSION['join_event']['timestamp'] = getTimestamp();
                unset($_SESSION['create_event']);
            }
            else {
                $bot->replyText($event['replyToken'],'Jangan menggunakan karakter apapun kecuali huruf dan garis bawah.');
            }
        }
    } else if(isset($_SESSION['set'])){
        if($_SESSION['set']['value']=='destination'){
            $data = $event['message']['text'];
            $link->query("UPDATE Events SET Destination='$data' WHERE kd_event='".$_SESSION['set']['kd_event']."'");
            $bot->replyText($event['replyToken'],'Destinasi event berhasil diatur. Sekarang masukkan waktu event:');
            $_SESSION['set']['value']='time';
        } else if($_SESSION['set']['value']=='time'){
            $data = $event['message']['text'];
            $link->query("UPDATE Events SET Time='$data' WHERE kd_event='".$_SESSION['set']['kd_event']."'");
            $bot->replyText($event['replyToken'],'Waktu event berhasil diatur.');
            session_destroy();
        } else if($_SESSION['set']['value']=='schedule'){
            $data = $event['message']['text'];
            $link->query("UPDATE Events SET Schedule='$data' WHERE kd_event='".$_SESSION['set']['kd_event']."'");
            $bot->replyText($event['replyToken'],'Jadwal event berhasil diatur.');
            session_destroy();
        } else if($_SESSION['set']['value']=='rule'){
            $data = $event['message']['text'];
            $link->query("UPDATE Events SET Rules='$data' WHERE kd_event='".$_SESSION['set']['kd_event']."'");
            $bot->replyText($event['replyToken'],'Aturan event berhasil diatur.');
            session_destroy();
        }
    } else if(isset($_SESSION['join_event'])) {
        if($_SESSION['join_event']['nama_event']=='' && !isset($_SESSION['join_event']['create_event'])){
            if($cmds[0][0]=='@') $cmds[0] = substr($cmds[0], 1);
            $res = $link->query("select Joiner.*, Events.* from Joiner, Events where Joiner.id_event = Events.id_event AND Joiner.id_user = '".$event['source']['userId']."' AND Events.kd_event = '".$cmds[0]."'");
            if($res->num_rows>0){
                $res = $res->fetch_assoc();
                $bot->replyText($event['replyToken'],'Kamu telah bergabung dengan event '.$res['nama_event'].' (@'.$res['kd_event'].').');
            } else {
                $res = $link->query("select * from Events where kd_event = '".$cmds[0]."'");
                if(mysqli_num_rows($res)){
                    $res = $res->fetch_assoc();
                    $_SESSION['join_event'] = array('timestamp'=>'', 'nama_event'=>$res['nama_event'], 'id_event'=>$res['id_event'], 'nama'=>'', 'no_telepon'=>'');
                    $_SESSION['join_event']['timestamp'] = getTimestamp();

                    $bot->replyText($event['replyToken'],'Kode event sesuai. Masukkan nama asli kamu : ');
                } else {
                    $bot->replyText($event['replyToken'],'Event @'.$cmds[0].' tidak ditemukan.');
                    session_destroy();
                }
            }
        } else if($_SESSION['join_event']['nama']==''){
            $_SESSION['join_event']['nama'] = implode(' ', $cmds);
            $bot->replyText($event['replyToken'],'Masukkan nomor HP mu : ');
        } else if($_SESSION['join_event']['no_telepon']==''){
            $_SESSION['join_event']['no_telepon'] = $cmds[0];
            
            if(isset($_SESSION['join_event']['create_event'])){
                $msg = "The Event Name : ".$_SESSION['join_event']['create_event']['nama_event']."\nEvent code : @".$_SESSION['join_event']['create_event']['kd_event']."\n\nInformasi pribadi :\nNama : ".$_SESSION['join_event']['nama']."\nNo HP : ".$_SESSION['join_event']['no_telepon']."\nKamu yakin akan membuat event ini?";
            } else {
                $msg = "Nama event : ".$_SESSION['join_event']['nama_event']."\nNama : ".$_SESSION['join_event']['nama']."\nNo HP : ".$_SESSION['join_event']['no_telepon']."\nKamu yakin mau gabung?";
            }
            
            $options[] = new MessageTemplateActionBuilder('Ya', 'Ya');
        	$options[] = new MessageTemplateActionBuilder('Tidak', 'Tidak');
        	$buttonTemplate = new ConfirmTemplateBuilder($msg, $options);

        	// build message
        	$messageBuilder = new TemplateMessageBuilder("Gunakan aplikasi mobile untuk melihat pesan ini.", $buttonTemplate);

            $result = $bot->replyMessage($event['replyToken'], $messageBuilder);
           
        } else {
            if(strtolower($cmds[0]) == 'ya'){
                if(isset($_SESSION['join_event']['create_event'])){
                    $_SESSION['create_event'] = $_SESSION['join_event']['create_event'];
                    $res = $link->query("INSERT INTO Events(kd_event, nama_event, id_creator) VALUES('".$_SESSION['create_event']['kd_event']."', '".$_SESSION['create_event']['nama_event']."','".$event['source']['userId']."')");
                    
                    $res = $link->query("select * from Events where id_creator = '".$event['source']['userId']."'")->fetch_assoc();
                    
                    $_SESSION['join_event']['id_event'] = $res['id_event'];
                    
                    $res = $link->query("INSERT INTO Joiner(id_event, name, phone, id_user) VALUES(".$_SESSION['join_event']['id_event'].", '".$_SESSION['join_event']['nama']."','".$_SESSION['join_event']['no_telepon']."','".$event['source']['userId']."')");
                    
                    $options[] = new MessageTemplateActionBuilder('Atur Event', 'Atur Event');
                    $buttonMessage = new ButtonTemplateBuilder(null, 'Nama event : ' . $_SESSION['create_event']['nama_event'] . ' ; Kode event : @' . $_SESSION['create_event']['kd_event']."\nSekarang kamu dapat mengelola event.", null, $options);
                    $templateMessage = new TemplateMessageBuilder("new message", $buttonMessage);
                    $bot->pushMessage($event['source']['userId'], $templateMessage);
                } else {
                    $res = $link->query("INSERT INTO Joiner(id_event, name, phone, id_user) VALUES(".$_SESSION['join_event']['id_event'].", '".$_SESSION['join_event']['nama']."','".$_SESSION['join_event']['no_telepon']."','".$event['source']['userId']."')");
                    $options[] = new MessageTemplateActionBuilder('Dapatkan Info Event', 'Minta Info Event');
                    $buttonMessage = new ButtonTemplateBuilder(null, "Sekarang kamu bisa mendapatkan informasi dari event yang telah kamu ikuti.", null, $options);
                    $templateMessage = new TemplateMessageBuilder("Dapatkan Info Event", $buttonMessage);
                    $bot->pushMessage($event['source']['userId'], $templateMessage);
                }
                session_destroy();
            } else {
                $bot->replyText($event['replyToken'],"Pendaftaran dibatalkan. Silahkan ulangi pendaftaran dari awal lagi ya.");
            }
            session_destroy();
        }
    } else if(isset($_SESSION['broadcasting'])){
        $_SESSION['broadcasting']['messages'] = implode(' ', $cmds);
        $res = $link->query("select * from Events where id_creator = '".$event['source']['userId']."'");
        if($res->num_rows>0){
            $res = $res->fetch_assoc();
            $res1 = $link->query("select id_user from Joiner where id_event = ".$res['id_event']);
            if($res1->num_rows > 0){
                while($id_user = $res1->fetch_assoc()){
                    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($_SESSION['broadcasting']['messages']);
                    $response = $bot->pushMessage($id_user['id_user'], $textMessageBuilder);
                }
                $bot->replyText($event['replyToken'],"Pesan broadcast berhasil dikirim ke seluruh peserta event ".$res['nama_event'].' (@'.$res['kd_event'].').');
            } else {
                $bot->replyText($event['replyToken'],"Tidak ada peserta event ".$res['nama_event'].' (@'.$res['kd_event'].').');
            }
        } else {
            $bot->replyText($event['replyToken'],"Kakak belum membuat suatu event. Hanya penyelenggara event yang dapat mengirim pesan broadcast.");
        }
        session_destroy();
    } else if(isset($_SESSION['leave'])){
        if(strtolower($cmds[0])=='ya'){
            if($_SESSION['leave']['is_creator']==true) $query = "DELETE FROM Events where id_creator = '".$event['source']['userId']."'";
            else $query = "DELETE FROM Joiner where id_user = '".$event['source']['userId']."'";
            $link->query($query);
            $bot->replyText($event['replyToken'],"Kakak berhasil meninggalkan event.");
        } else {
            $bot->replyText($event['replyToken'],"Permintaan Kakak dibatalkan.");
        }
        session_destroy();
    } else {
	$result = $bot->replyText($event['replyToken'], "Maaf, saya kurang paham yang Kakak maksud.");
	$options[] = new MessageTemplateActionBuilder('Minta Info Event', 'Minta Info Event');
	$options[] = new MessageTemplateActionBuilder('Buat Event', 'Buat Event');
	$options[] = new MessageTemplateActionBuilder('Gabung Event', 'Gabung Event');
	$options[] = new MessageTemplateActionBuilder('Bantuan', 'Bantuan');
        $buttonMessage = new ButtonTemplateBuilder(null, "Ada yang bisa saya bantu?", null, $options);
        $templateMessage = new TemplateMessageBuilder("pesan baru", $buttonMessage);
        $bot->pushMessage($event['source']['userId'], $templateMessage);
    }
}
// or we can use pushMessage() instead to send reply message
// $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event['message']['text']);
// $result = $bot->pushMessage($event['source']['userId'], $textMessageBuilder);

//sendResponse($result->getHTTPStatus(), $result->getRawBody());
//echo $response->getHTTPStatus() . ' ' . $response->getRawBody();