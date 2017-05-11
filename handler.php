<?php

/*****************************************

-- Table of Contents --

[001] Buat Event
[00A] Profil

*****************************************/

require __DIR__ . '/vendor/autoload.php';

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

function handler($json){
    global $event;
    global $data;
    global $channelSecret;
    global $channelAccess;
    global $httpClient;
    global $bot;
    
    date_default_timezone_set('Asia/Jakarta');

    $event = $json;
    $data['userId'] = $event['source']['userId'];

    $data['cmd'] = json_decode(file_get_contents("cmd.json"), true);

    if($event['type'] == 'message'){
        $data['msgText'] = $event['message']['text'];
    } else if($event['type'] == 'postback'){
        parse_str($event['postback']['data'], $postback);
        $data['msgText'] = $postback['intent'];
        $data['postback'] = $postback;
    }

    $channelSecret = $data['cmd']['channelSecret']; // Channel secret string
    $channelAccess = $data['cmd']['channelAccess']; // Channel access token

    session_id($data['userId']);
    session_start();

    // inisialisasi bot
    $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($channelAccess);
    $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);

    // Eksekusi
    main();
}

function close_session(){
    unset($_SESSION['state']);
    unset($_SESSION['data']);
}

function get_timestamp(){
    return strtotime('now');
}

function get_dblink(){
    return new mysqli('localhost', 'mekarya_ailo', 'l4bk0mbkt1', 'mekarya_ailo');
}

function multiexplode ($delimiters,$string) {
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}

function cutstr ($str,$maxchar) {
    if(strlen($str) > 60) return substr($str, 0, ($maxchar-3)) . "...";
    else return $str;
}

function is_registered(){
    global $data;
    $link = get_dblink();
    $res = $link->query("select * from Joiner where id_user = '".$data['userId']."'");
    return $res->num_rows;
}

function buildstr($str, ... $var_array){
    $tokens = explode('$', $str);
    if((count($tokens)-1) != count($var_array)){
        return "Error!".(count($tokens)-1).count($var_array).print_r($tokens).print_r($var_array);
    } else {
        $str = '';
        $i=0;
        foreach($tokens as $token){
            $str .= ($token. ($i != (count($tokens)-1) ? $var_array[$i++] : ""));
        }
        return $str;
    }
}

function truncstr($str, $max){
    if(strlen($str) > $max){
        return substr($str, 0, $max-4)."...";
    } else {
        return $str;
    }
}

function is_creator(){
    global $data;
    
    $link = get_dblink();
    $res = $link->query("select * from Events where id_creator = '".$data['userId']."'");
    
    if($res->num_rows>0){
        return $res->fetch_assoc();
    } else {
        return null;
    }
}

function main(){
    // Variables
    global $data;
    global $event;
    global $bot;
    global $httpClient;
    
    if(isset($_SESSION['timestamp'])){
        if(get_timestamp() - $_SESSION['timestamp'] > 1800){
            close_session();
        }
        $_SESSION['timestamp'] = get_timestamp();
    } else {
        $_SESSION['timestamp'] = get_timestamp();
    }

    if(!isset($_SESSION['state'])){
        if(!is_registered()){
            $data['msgText'] = strtolower($data['msgText']);
            if($data['msgText'] == 'atur notel' || $data['msgText'] == 'atur nama' || $data['msgText'] == 'bantuan' || $data['msgText'] == 'memo' || $data['msgText'] == 'lihat memo' || $data['msgText'] == 'hapus memo' || $data['msgText'] == 'atur memo'){
                $_SESSION['state'] = $data['cmd'][$data['msgText']];
            } else {
                $_SESSION['state'] = $data['cmd']['profil'];
            }
        } else {
            foreach ($data['cmd'] as $key => $value) {
                if($key == strtolower($data['msgText'])){
                    $_SESSION['state'] = $value;
                    break;
                }
            }
        }
    }
    
    /**
     * Batalkan perintah
     */
    if(strtolower($data['msgText']) == 'batal'){
        close_session();
        $bot->pushText($data['userId'], 'Perintah terakhir telah dibatalkan.');
    }
    
    /**
      * [001]Buat Event
      * -- Code : 0x01
      * -- Membuat Event Baru
      */
    else if($_SESSION['state']['code'] == "0x01"){
        // Set variabel data
        if(!isset($_SESSION['data'])){
            $_SESSION['data'] = array('nama_event'=> '', 'kd_event'=>'');

            // Cek apakah user sudah pernah membuat event
            $link = get_dblink();
            $res = $link->query("select * from Events where id_creator = '".$data['userId']."'");
            if($res->num_rows>0){
                // Jika sudah pernah buat event
                $res = $res->fetch_assoc();
                $bot->replyText($event['replyToken'],buildstr($_SESSION['state']['onFailure'][0], $res['nama_event'], $res['kd_event']));
                close_session();
            } else {
                // Jika belum pernah buat event
                // Cek sudah tergabung ke event atau belum
                $res = $link->query("select Joiner.*, Events.* from Joiner, Events where Joiner.id_event = Events.id_event AND Joiner.id_user = '".$data['userId']."'");
                if($res->num_rows>0){
                    $res = $res->fetch_assoc();
                    $bot->replyText($event['replyToken'],buildstr($_SESSION['state']['onFailure'][1], $res['nama_event'], $res['kd_event']));
                    close_session();
                } else {
                    $bot->replyText($event['replyToken'], $_SESSION['state']['description']);
                }
            }
        } else if($_SESSION['data']['nama_event']==''){
            $_SESSION['data']['nama_event'] = $data['msgText'];
            $bot->replyText($event['replyToken'],'Masukkan ID event : ');
        } else if($_SESSION['data']['kd_event']==''){
            if(count(multiexplode(array(' ', ',', '|', '@', '%', '*', '$', '!', '#', '^', '(', ')', '+', '='), $data['msgText'])) == 1){
                $link = get_dblink();
                $res = $link->query("select * from Events where kd_event = '".$link->real_escape_string($data['msgText'])."'");
                if($res->num_rows>0){
                    $bot->replyText($event['replyToken'],$_SESSION['state']['onFailure'][2]);
                } else {
                    $_SESSION['data']['kd_event'] = $data['msgText'];

                    $msg = "Nama event : ".$_SESSION['data']['nama_event']."\nID event : @".$_SESSION['data']['kd_event']."\nKamu yakin akan membuat event ini?";
                    
                    if(strlen($msg)<=160){
                        $options[] = new MessageTemplateActionBuilder('Ya', 'Ya');
                        $options[] = new MessageTemplateActionBuilder('Tidak', 'Tidak');
                        $buttonTemplate = new ConfirmTemplateBuilder($msg, $options);

                        // build message
                        $messageBuilder = new TemplateMessageBuilder("Gunakan aplikasi mobile untuk melihat pesan ini.", $buttonTemplate);

                        $result = $bot->replyMessage($event['replyToken'], $messageBuilder);
                    } else {
                        $bot->pushText($data['userId'], $msg);
                        
                        $options[] = new MessageTemplateActionBuilder('Ya', 'Ya');
                        $options[] = new MessageTemplateActionBuilder('Tidak', 'Tidak');
                        $buttonTemplate = new ConfirmTemplateBuilder("Konfirmasi", $options);

                        // build message
                        $messageBuilder = new TemplateMessageBuilder("Gunakan aplikasi mobile untuk melihat pesan ini.", $buttonTemplate);

                        $result = $bot->replyMessage($event['replyToken'], $messageBuilder);
                    }
                }
            }
            else {
                $bot->replyText($event['replyToken'],$_SESSION['state']['onFailure'][3]);
            }
        } else {
            if(strtolower($data['msgText']) == 'ya'){
                $link = get_dblink();
                $res = $link->query("INSERT INTO Events(kd_event, nama_event, id_creator) VALUES('".$_SESSION['data']['kd_event']."', '".$_SESSION['data']['nama_event']."','".$data['userId']."')");
				
				$text = "Bagi yang ingin bergabung ke $, harap jadikan KLENCER ( bit.ly/klencer ) sebagai teman dan menggunakan ID @$ untuk bergabung.";
				$bot->pushText($data['userId'], buildstr($text, $_SESSION['data']['nama_event'], $_SESSION['data']['kd_event']));
					
                $options[] = new MessageTemplateActionBuilder('Atur Event', 'Atur Event');
                $buttonMessage = new ButtonTemplateBuilder(null,
                                                           buildstr($_SESSION['state']['onSuccess'], $_SESSION['data']['nama_event'], $_SESSION['data']['kd_event']),
                                                           null, $options);
                $templateMessage = new TemplateMessageBuilder("Atur Event", $buttonMessage);
                $bot->pushMessage($data['userId'], $templateMessage);
            } else {
                $bot->replyText($event['replyToken'], $_SESSION['state']['onCancel']);
            }
            close_session();
        }
    }
    
    /**
      * [003]Tinggalkan event
      * -- Code : 0x03
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0x03"){
        $link = get_dblink();
        
        // Cek creator atau bukan
        $res = $link->query("select * from Events where id_creator = '".$data['userId']."'");
        if($res->num_rows > 0){
            $res = $res->fetch_assoc();
            $bot->replyText($event['replyToken'],"Kamu tidak bisa menggunakan perintah ini.");
            close_session();
        } else {
            // Cek sudah tergabung event atau belum
            $res = $link->query("select * from Joiner where id_user = '".$data['userId']."' AND id_event IS NOT NULL");
            if($res->num_rows > 0){
                if(!isset($_SESSION['data'])){
                    $res = $res->fetch_assoc();
                    $res = $link->query("select * from Events where id_event = ".$res['id_event']);
                    $res = $res->fetch_assoc();
                    
                    $_SESSION['data'] = array('kd_event' => $res['kd_event'], 'nama_event'=>$res['nama_event'], 'id_event'=>$res['id_event']);

                    $options[] = new MessageTemplateActionBuilder('Ya', 'Ya');
                    $options[] = new MessageTemplateActionBuilder('Tidak', 'Tidak');
                    $buttonTemplate = new ConfirmTemplateBuilder('Tinggalkan '.$res['nama_event'].' (@'.$res['kd_event'].')?', $options);

                    // build message
                    $messageBuilder = new TemplateMessageBuilder("Gunakan aplikasi mobile untuk melihat pesan ini.", $buttonTemplate);

                    $result = $bot->replyMessage($event['replyToken'], $messageBuilder);
                } else {
                    if(strtolower($data['msgText']) == 'ya'){
                        $link->query("UPDATE Joiner SET id_event = NULL WHERE id_user='".$data['userId']."'");
                        $bot->replyText($event['replyToken'], 'Kamu sudah meninggalkan event '.$_SESSION['data']['nama_event'].' @('.$_SESSION['data']['kd_event'].')');
                    } else {
                        $bot->replyText($event['replyToken'], 'Permintaan meninggalkan event dibatalkan.');
                    }
                    close_session();
                }
            } else {
                // Belum tergabung di manapun
                $bot->replyText($event['replyToken'],"Kamu tidak bisa menggunakan perintah ini.");
                close_session();
            }
        }
    }
    
    /**
      * [004]Atur event
      * -- Code : 0x04
      * -- Broadcast pesan
      */
    else if($_SESSION['state']['code'] == "0x04"){
        $link = get_dblink();
        
        // Cek creator atau bukan
        $res = $link->query("select * from Events where id_creator = '".$data['userId']."'");
        if($res->num_rows > 0){
            $res = $res->fetch_assoc();
            
            $options[] = new MessageTemplateActionBuilder('Destinasi', 'atur destinasi');
            $options[] = new MessageTemplateActionBuilder('Jadwal', 'atur jadwal');
            $options[] = new MessageTemplateActionBuilder('Info Tambahan', 'atur tambahan');
            $options[] = new MessageTemplateActionBuilder('Contact Person', 'atur cp');
            
            $msg_str = $res['nama_event']." (@".$res['kd_event'].")";
            
            if(strlen($msg_str) > 60) {
                $bot->pushText($data['userId'],"Nama event : ". $msg_str);
                $buttonMessage = new ButtonTemplateBuilder("Atur Informasi Event", "Apa yang ingin kamu atur?", null, $options);
            } else {
                $buttonMessage = new ButtonTemplateBuilder("Atur Informasi Event", $msg_str, null, $options);
            }
            $templateMessage = new TemplateMessageBuilder("Atur Informasi Event\natur destinasi\natur jadwal\natur tambahan\natur cp", $buttonMessage);
            $bot->pushMessage($data['userId'], $templateMessage);
        } else {
            $bot->replyText($event['replyToken'], $_SESSION['state']['onFailure']);
        }
        close_session();
    }
    
    /**
      * [006]Broadcast (bc)
      * -- Code : 0x06
      * -- Broadcast pesan
      */
    else if($_SESSION['state']['code'] == "0x06"){
        // Set variabel data
        if(!isset($_SESSION['data'])){
            $res = is_creator();
            if($res){
                $_SESSION['data'] = array('pesan'=> '', 'event'=> $res);
                $bot->replyText($event['replyToken'],$_SESSION['state']['description']);
            } else {
                $bot->replyText($event['replyToken'], $_SESSION['state']['onFailure'][0]);
                close_session();
            }
        } else if($_SESSION['data']['pesan']==''){
            $_SESSION['data']['pesan'] = "[".$_SESSION['data']['event']['nama_event']."]\n\n".$data['msgText'];
            
            $options[] = new MessageTemplateActionBuilder('Ya', 'Ya');
            $options[] = new MessageTemplateActionBuilder('Tidak', 'Tidak');
            $buttonTemplate = new ConfirmTemplateBuilder('Kamu yakin ingin mengirimkan pesan ini?', $options);

            // build message
            $messageBuilder = new TemplateMessageBuilder("Gunakan aplikasi mobile untuk melihat pesan ini.", $buttonTemplate);

            $bot->pushText($data['userId'], $_SESSION['data']['pesan']);
            $result = $bot->replyMessage($event['replyToken'], $messageBuilder);
        } else {
            if(strtolower($data['msgText']) == 'ya'){
                $link = get_dblink();
                $res = $link->query("select id_user from Joiner where id_event = ".$_SESSION['data']['event']['id_event']);
                if($res->num_rows > 0){
                    while($id_user = $res->fetch_assoc()){
                        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($_SESSION['data']['pesan']);
                        $response = $bot->pushMessage($id_user['id_user'], $textMessageBuilder);
                    }
                    $bot->replyText($event['replyToken'],buildstr($_SESSION['state']['onSuccess'], $_SESSION['data']['event']['nama_event'], $_SESSION['data']['event']['kd_event']));
                } else {
                    $bot->replyText($event['replyToken'],buildstr($_SESSION['state']['onFailure'][1], $_SESSION['data']['event']['nama_event'], $_SESSION['data']['event']['kd_event']));
                }
            } else {
                $bot->replyText($event['replyToken'], $_SESSION['state']['onCancel']);
            }
            close_session();
        }
    }

    /**
      * [00A]Profil
      * -- Code : 0x0A
      * -- Menampilkan profil
      */
    else if($_SESSION['state']['code'] == "0x0A"){
        $link = get_dblink();
        $res = $link->query("select * from Joiner where id_user = '".$data['userId']."'");
        $options[] = new MessageTemplateActionBuilder('Atur Profil', 'atur nama');
        if($res->num_rows>0){
            $res = $res->fetch_assoc();

            $msg_str = "Nama: " . (isset($res['name']) ? $res['name'] : "(Belum diatur)") . "\nNo. telp : " . (isset($res['phone']) ? $res['phone'] : "(Belum diatur)");

            $options[] = new MessageTemplateActionBuilder('Atur No. Telepon', 'atur notel');
            if(strlen($msg_str) > 60){
                $bot->replyText($event['replyToken'],"Profil\n\n".$msg_str);

                $buttonMessage = new ButtonTemplateBuilder(null, "Apakah kamu ingin mengubah profilmu?", null, $options);
            } else {
                $buttonMessage = new ButtonTemplateBuilder("Profil", $msg_str, null, $options);
            }

            $templateMessage = new TemplateMessageBuilder("Profil\n".$msg_str."\n\nIngin mengubah profil?\natur nama\natur notel", $buttonMessage);
            $bot->pushMessage($data['userId'], $templateMessage);
        } else {
            $buttonMessage = new ButtonTemplateBuilder("Profil Belum Diatur", "Silahkan atur profil kamu untuk menggunakan layanan kami.", null, $options);
            $templateMessage = new TemplateMessageBuilder("Profil Belum Diatur\nSilahkan atur profil kamu untuk menggunakan layanan kami\nKetik 'atur nama' untuk mendaftarkan nama kamu.", $buttonMessage);
            $bot->pushMessage($data['userId'], $templateMessage);
        }
        close_session();
    }

    /**
      * [00B]Atur Nama
      * -- Code : 0x0B
      * -- Mengatur Nama
      */
    else if($_SESSION['state']['code'] == "0x0B"){
        // Set variabel data
        if(!isset($_SESSION['data'])){
            $_SESSION['data'] = array('nama'=> '');
            $bot->replyText($event['replyToken'],"Silahkan masukkan namamu.");
        } else {
            $link = get_dblink();
            $res = $link->query("select * from Joiner where id_user = '".$data['userId']."'");
            $str_msg = '';
            if($res->num_rows>0){
                $res = $link->query("UPDATE Joiner SET name='".$data['msgText']."' WHERE id_user='".$data['userId']."'");
                $str_msg = "Nama berhasil diubah.";
                $bot->replyText($event['replyToken'],$str_msg);
                close_session();
            } else {
                $res = $link->query("INSERT INTO Joiner(name, id_user) VALUES('".$data['msgText']."','".$data['userId']."')");
                $str_msg = "Selamat datang, ".$data['msgText'].". Kamu dapat melengkapi profilmu di Menu Pengaturan.";
                $_SESSION['state'] = $data['cmd']['menu'];
                $bot->replyText($event['replyToken'],$str_msg);
                main();
            }
        }
    }

    /**
      * [00C]Atur Nomor Telepon
      * -- Code : 0x0C
      * -- Mengatur nomor telepon
      */
    else if($_SESSION['state']['code'] == "0x0C"){
        if(!isset($_SESSION['data'])){
            $_SESSION['data'] = array('notel'=> '');
            $bot->replyText($event['replyToken'],"Silahkan masukkan nomor teleponmu.");
        } else {
            if(is_numeric($data['msgText'])){
                $link = get_dblink();
                $res = $link->query("select * from Joiner where id_user = '".$data['userId']."'");
                if($res->num_rows>0){
                    $res = $link->query("UPDATE Joiner SET phone='".$data['msgText']."' WHERE id_user='".$data['userId']."'");
                } else {
                    $res = $link->query("INSERT INTO Joiner(name, id_user) VALUES('".$data['msgText']."','".$data['userId']."')");
                }
                $bot->replyText($event['replyToken'],"Nomor telepon berhasil diubah.");
            } else {
                $bot->replyText($event['replyToken'],"Nomor telepon yang kamu masukkan tidak valid. Tolong hanya gunakan angka pada nomor teleponmu. Ketik 'atur notel' untuk memasukkan kembali nomor telepon kamu.");
            }
            close_session();
        }
    }
    
    /**
      * [00D]Bubarkan
      * -- Code : 0x0D
      * -- Bubarkan event
      */
    else if($_SESSION['state']['code'] == "0x0D"){
        // Set variabel data
        if(!isset($_SESSION['data'])){
            $res = is_creator();
            if($res){
                $_SESSION['data'] = array('event' => $res);
                
                $options[] = new MessageTemplateActionBuilder('Ya', 'Ya');
                $options[] = new MessageTemplateActionBuilder('Tidak', 'Tidak');
                $buttonTemplate = new ConfirmTemplateBuilder('Kamu yakin ingin membubarkan '.$_SESSION['data']['event']['nama_event'].' (@'.$_SESSION['data']['event']['kd_event'].')?', $options);

                // build message
                $messageBuilder = new TemplateMessageBuilder("Gunakan aplikasi mobile untuk melihat pesan ini.", $buttonTemplate);

                $bot->pushText($data['userId'], $_SESSION['data']['pesan']);
                $result = $bot->replyMessage($event['replyToken'], $messageBuilder);
            } else {
                $bot->replyText($event['replyToken'],"Kamu belum pernah membuat event.");
                close_session();
            }
        } else {
            if(strtolower($data['msgText']) == 'ya'){
                $link = get_dblink();
                $res = $link->query("DELETE FROM Events WHERE id_event = ".$_SESSION['data']['event']['id_event']);
                $bot->replyText($event['replyToken'],buildstr($_SESSION['state']['onSuccess'], $_SESSION['data']['event']['nama_event'], $_SESSION['data']['event']['kd_event']));
            } else {
                $bot->replyText($event['replyToken'], $_SESSION['state']['onCancel']);
            }
            close_session();
        }
    }
    
    /**
      * [00E]Atur Destinasi
      * -- Code : 0x0E
      * -- Mengatur destinasi
      */
    else if($_SESSION['state']['code'] == "0x0E"){        
        // Cek creator atau bukan
        $res = is_creator();
        if($res){
            if(!isset($_SESSION['data'])){
                $_SESSION['data'] = array('destination'=>'', 'event' => $res);
                $bot->replyText($event['replyToken'],"Silahkan masukkan nama destinasi perjalanan kamu");
            } else if($_SESSION['data']['destination'] == ''){
                $_SESSION['data']['destination'] = $data['msgText'];
                $bot->replyText($event['replyToken'],"Silahkan masukkan waktu berjalannya event kamu");
            } else {
                $link = get_dblink();
                $link->query("UPDATE Events SET Time = '".$link->real_escape_string($data['msgText'])."', Destination = '".$link->real_escape_string($_SESSION['data']['destination'])."' WHERE id_event = ".$_SESSION['data']['event']['id_event']);
                $bot->replyText($event['replyToken'], $_SESSION['state']['onSuccess']);
                close_session();
            }
        } else {
            $bot->replyText($event['replyToken'], $_SESSION['state']['onFailure']);
            close_session();
        }
    }
    
    /**
      * [00F]Atur Jadwal
      * -- Code : 0x0F
      * -- Mengatur destinasi
      */
    else if($_SESSION['state']['code'] == "0x0F"){        
        // Cek creator atau bukan
        $res = is_creator();
        if($res){
            if(!isset($_SESSION['data'])){
                $_SESSION['data'] = array('event' => $res);
                $bot->replyText($event['replyToken'],"Silahkan masukkan Jadwal perjalanan kamu");
            } else {
                $link = get_dblink();
                $link->query("UPDATE Events SET Schedule = '".$link->real_escape_string($data['msgText'])."' WHERE id_event = ".$_SESSION['data']['event']['id_event']);
                $bot->replyText($event['replyToken'], $_SESSION['state']['onSuccess']);
                close_session();
            }
        } else {
            $bot->replyText($event['replyToken'], $_SESSION['state']['onFailure']);
            close_session();
        }
    }
    
    /**
      * [00E]Atur Info tambahan
      * -- Code : 0x10
      * -- Mengatur destinasi
      */
    else if($_SESSION['state']['code'] == "0x10"){        
        // Cek creator atau bukan
        $res = is_creator();
        if($res){
            if(!isset($_SESSION['data'])){
                $_SESSION['data'] = array('event' => $res);
                $bot->replyText($event['replyToken'],"Silahkan masukkan informasi tambahan perjalanan kamu");
            } else {
                $link = get_dblink();
                $link->query("UPDATE Events SET Rules = '".$link->real_escape_string($data['msgText'])."' WHERE id_event = ".$_SESSION['data']['event']['id_event']);
                $bot->replyText($event['replyToken'], $_SESSION['state']['onSuccess']);
                close_session();
            }
        } else {
            $bot->replyText($event['replyToken'], $_SESSION['state']['onFailure']);
            close_session();
        }
    }
    
    /**
      * [00E]Atur Contact Person
      * -- Code : 0x11
      * -- Mengatur destinasi
      */
    else if($_SESSION['state']['code'] == "0x11"){        
        // Cek creator atau bukan
        $res = is_creator();
        if($res){
            if(!isset($_SESSION['data'])){
                $_SESSION['data'] = array('event' => $res);
                $bot->replyText($event['replyToken'],"Silahkan masukkan contact person untuk perjalanan kamu");
            } else {
                $link = get_dblink();
                $link->query("UPDATE Events SET Contact = '".$link->real_escape_string($data['msgText'])."' WHERE id_event = ".$_SESSION['data']['event']['id_event']);
                $bot->replyText($event['replyToken'], $_SESSION['state']['onSuccess']);
                close_session();
            }
        } else {
            $bot->replyText($event['replyToken'], $_SESSION['state']['onFailure']);
            close_session();
        }
    }
    
    /**
      * [012]Lihat rincian event
      * -- Code : 0x12
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0x12"){
        $link = get_dblink();
        
        // Cek creator atau bukan
        $res = $link->query("select * from Events where id_creator = '".$data['userId']."'");
        if($res->num_rows > 0){
            $res = $res->fetch_assoc();
            $bot->replyText($event['replyToken'], "Nama Event\n".$res['nama_event']."\n\nID Event\n@".$res['kd_event']."\n\nWaktu\n".$res['Time']."\n\nDestinasi\n".$res['Destination']);
        } else {
            // Cek sudah tergabung event atau belum
            $res = $link->query("select Joiner.*, Events.* from Joiner, Events where Joiner.id_event = Events.id_event AND Joiner.id_user = '".$event['source']['userId']."'");
            if($res->num_rows > 0){
                $res = $res->fetch_assoc();
                $bot->replyText($event['replyToken'], "Nama Event\n".$res['nama_event']."\n\nID Event\n".$res['kd_event']."\n\nWaktu Keberangkatan\n".$res['Time']."\n\nDestinasi\n".$res['Destination']);
            } else {
                // Belum tergabung di manapun
                $bot->replyText($event['replyToken'], "Kamu belum tergabung dalam event apapun");
            }
        }
        close_session();
    }
    
    /**
      * [013]Lihat jadwal
      * -- Code : 0x13
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0x13"){
        $link = get_dblink();
        
        // Cek creator atau bukan
        $res = $link->query("select * from Events where id_creator = '".$data['userId']."'");
        if($res->num_rows > 0){
            $res = $res->fetch_assoc();
			
			$text = $res['Schedule'] ? $res['Schedule'] : "(Kosong) Harap hubungi\n".$res['Contact'];
            $bot->replyText($event['replyToken'], $text);
        } else {
            // Cek sudah tergabung event atau belum
            $res = $link->query("select Joiner.*, Events.* from Joiner, Events where Joiner.id_event = Events.id_event AND Joiner.id_user = '".$event['source']['userId']."'");
            if($res->num_rows > 0){
                $res = $res->fetch_assoc();
				
				$text = $res['Schedule'] ? $res['Schedule'] : "(Kosong) Harap hubungi\n".$res['Contact'];
            	$bot->replyText($event['replyToken'], $text);
            } else {
                // Belum tergabung di manapun
                $bot->replyText($event['replyToken'], "Kamu belum tergabung dalam event apapun");
            }
        }
        close_session();
    }
    
    /**
      * [014]Lihat akomodasi
      * -- Code : 0x14
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0x14"){
        $link = get_dblink();
        
        // Cek creator atau bukan
        $res = $link->query("select * from Events where id_creator = '".$data['userId']."'");
        if($res->num_rows > 0){
            $res = $res->fetch_assoc();
            $bot->replyText($event['replyToken'], "Pembagian Tempat Duduk Kendaraan (Transportasi) dan Kamar Tidur (Penginapan) belum diatur. \n\nHarap hubungi ".$res['Contact']);
        } else {
            // Cek sudah tergabung event atau belum
            $res = $link->query("select Joiner.*, Events.* from Joiner, Events where Joiner.id_event = Events.id_event AND Joiner.id_user = '".$event['source']['userId']."'");
            if($res->num_rows > 0){
                $res = $res->fetch_assoc();
                $bot->replyText($event['replyToken'], "Pembagian Tempat Duduk Kendaraan (Transportasi) dan Kamar Tidur (Penginapan) belum diatur. \n\nHarap hubungi ".$res['Contact']);
            } else {
                // Belum tergabung di manapun
                $bot->replyText($event['replyToken'], "Kamu belum tergabung dalam event apapun");
            }
        }
        close_session();
    }
    
    /**
      * [015]Lihat info tambahan event
      * -- Code : 0x12
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0x15"){
        $link = get_dblink();
        
        // Cek creator atau bukan
        $res = $link->query("select * from Events where id_creator = '".$data['userId']."'");
        if($res->num_rows > 0){
            $res = $res->fetch_assoc();
			
			$text = $res['Rules'] ? $res['Rules'] : "(Kosong) Harap hubungi\n".$res['Contact'];
            $bot->replyText($event['replyToken'], $text);
        } else {
            // Cek sudah tergabung event atau belum
            $res = $link->query("select Joiner.*, Events.* from Joiner, Events where Joiner.id_event = Events.id_event AND Joiner.id_user = '".$event['source']['userId']."'");
            if($res->num_rows > 0){
                $res = $res->fetch_assoc();
                
				$text = $res['Rules'] ? $res['Rules'] : "(Kosong) Harap hubungi\n".$res['Contact'];
            	$bot->replyText($event['replyToken'], $text);
            } else {
                // Belum tergabung di manapun
                $bot->replyText($event['replyToken'], "Kamu belum tergabung dalam event apapun");
            }
        }
        close_session();
    }
    
    /**
      * [016]Lihat cp event
      * -- Code : 0x16
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0x16"){
        $link = get_dblink();
        
        // Cek creator atau bukan
        $res = $link->query("select * from Events where id_creator = '".$data['userId']."'");
        if($res->num_rows > 0){
            $res = $res->fetch_assoc();
            $bot->replyText($event['replyToken'], $res['Contact']);
        } else {
            // Cek sudah tergabung event atau belum
            $res = $link->query("select Joiner.*, Events.* from Joiner, Events where Joiner.id_event = Events.id_event AND Joiner.id_user = '".$event['source']['userId']."'");
            if($res->num_rows > 0){
                $res = $res->fetch_assoc();
                $bot->replyText($event['replyToken'], $res['Contact']);
            } else {
                // Belum tergabung di manapun
                $bot->replyText($event['replyToken'], "Kamu belum tergabung dalam event apapun");
            }
        }
        close_session();
    }
    
    /**
      * [017]Gabung event
      * -- Code : 0x17
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0x17"){
        $link = get_dblink();
        
        // Cek creator atau bukan
        $res = $link->query("select * from Events where id_creator = '".$data['userId']."'");
        if($res->num_rows > 0){
            $res = $res->fetch_assoc();
            $bot->replyText($event['replyToken'],"Kamu tidak bisa bergabung ke event lain karena kamu sudah membuat event ".$res['nama_event']." (@".$res['kd_event'].")");
            close_session();
        } else {
            // Cek sudah tergabung event atau belum
            $res = $link->query("select Joiner.*, Events.* from Joiner, Events where Joiner.id_event = Events.id_event AND Joiner.id_user = '".$data['userId']."'");
            if($res->num_rows > 0){
                $res = $res->fetch_assoc();
                $bot->replyText($event['replyToken'],"Kamu tidak bisa bergabung ke event lain karena kamu sudah tergabung dalam event ".$res['nama_event']." (@".$res['kd_event'].")");
                close_session();
            } else {
                // Belum tergabung di manapun
                if(!isset($_SESSION['data'])){
                    $_SESSION['data'] = array('kd_event' => '', 'nama_event'=>'', 'id_event'=>'');
                    $bot->replyText($event['replyToken'],"Silahkan masukkan ID event yang ingin kamu ikuti.");
                } else if ($_SESSION['data']['kd_event'] == ''){
                    if($data['msgText'][0]=='@') $data['msgText'] = substr($data['msgText'], 1);
                    $_SESSION['data']['kd_event'] = $data['msgText'];
                    
                    $res = $link->query("select * from Events where kd_event = '".$_SESSION['data']['kd_event']."'");
                    if($res->num_rows > 0){
                        $res = $res->fetch_assoc();
                        
                        $_SESSION['data']['id_event'] = $res['id_event'];
                        $_SESSION['data']['nama_event'] = $res['nama_event'];
                        
                        $options[] = new MessageTemplateActionBuilder('Ya', 'Ya');
                        $options[] = new MessageTemplateActionBuilder('Tidak', 'Tidak');
                        $buttonTemplate = new ConfirmTemplateBuilder('Gabung ke ['.$res['nama_event'].']?', $options);

                        // build message
                        $messageBuilder = new TemplateMessageBuilder("Gunakan aplikasi mobile untuk melihat pesan ini.", $buttonTemplate);

                        $result = $bot->replyMessage($event['replyToken'], $messageBuilder);
                    } else {
                        $bot->replyText($event['replyToken'], 'Event dengan ID tersebut tidak ditemukan.');
                        close_session();
                    }
                } else {
                    if(strtolower($data['msgText']) == 'ya'){
                        $link->query('UPDATE Joiner SET id_event = '.$_SESSION['data']['id_event']." WHERE id_user='".$data['userId']."'");
                        $bot->replyText($event['replyToken'], 'Kamu sudah bergabung ke '.$_SESSION['data']['nama_event'].' @('.$_SESSION['data']['kd_event'].')');
                        $_SESSION['state'] = $data['cmd']['menu'];
                        main();
                    } else {
                        $bot->replyText($event['replyToken'], 'Permintaan gabung event dibatalkan.');
                        close_session();
                    }
                }
            }
        }
    }
    
    /**
      * [018]Memo
      * -- Code : 0x18
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0x18"){
        if(!isset($_SESSION['data'])){
            $link = get_dblink();
            $res = $link->query("SELECT id from memo WHERE id_user='".$data['userId']."'");
            
            if($res->num_rows < 5){
                $_SESSION['data'] = array('waktu'=>'10:15', 'memo'=>'');
                $bot->replyText($event['replyToken'], 'Tuliskan catatan untuk saya ingatkan nanti.');
            } else {
                $bot->replyText($event['replyToken'], 'Kamu sudah memiliki 5 memo. Saya tidak bisa mengingat banyak-banyak T_T.');
                close_session();
            }
        } else {
            $_SESSION['data']['memo'] = $data['msgText'];
            
            $link = get_dblink();
            $link->query("INSERT INTO memo(id_user, waktu, text) VALUES('".$data['userId']."', '".$_SESSION['data']['waktu']."', '".$link->real_escape_string($_SESSION['data']['memo'])."')");
            
            $res = $link->query("SELECT id from memo WHERE id_user='".$data['userId']."' ORDER BY id DESC LIMIT 1")->fetch_assoc();

            $options[] = new PostbackTemplateActionBuilder('Atur waktu', 'intent=atur+memo&id='.$res['id']);
            $options[] = new PostbackTemplateActionBuilder('Batalkan', 'intent=hapus+memo&id='.$res['id']);
            
            $msg = "Kamu akan saya ingatkan pada jam ".$_SESSION['data']['waktu'];
            
            $buttonMessage = new ButtonTemplateBuilder("Memo telah dibuat", truncstr($msg, 60), null, $options);
            $templateMessage = new TemplateMessageBuilder("Memo telah dibuat", $buttonMessage);
            $bot->pushMessage($data['userId'], $templateMessage);

            close_session();
        }
    }
    
    /**
      * [019]Atur Memo
      * -- Code : 0x19
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0x19"){
        if($event['type']=='postback'){
            if(!isset($_SESSION['data'])){
                $link = get_dblink();
                $res = $link->query("SELECT * from memo WHERE id='".$data['postback']['id']."'");

                if($res->num_rows > 0){
                    $_SESSION['data'] = array('id'=>$data['postback']['id']);
                    $bot->replyText($event['replyToken'], 'Mau saya ingatkan jam berapa? (Gunakan format 24 jam, [JAM]:[MENIT], Contoh: 13:00)');
                } else {
                    $bot->replyText($event['replyToken'], 'Memo ini tidak ditemukan. Mungkin sudah selesai atau sudah kamu hapus.');
                    close_session();
                }
            }
        } else {
            if(isset($_SESSION['data'])){
                $_SESSION['data']['waktu'] = explode(':',$data['msgText']);

                if(count($_SESSION['data']['waktu']) == 2){
                    if(is_numeric($_SESSION['data']['waktu'][0]) &&
                       is_numeric($_SESSION['data']['waktu'][1])){
                        
                        $_SESSION['data']['waktu'][0] = sprintf("%02d", $_SESSION['data']['waktu'][0]);
                        $_SESSION['data']['waktu'][1] = sprintf("%02d", $_SESSION['data']['waktu'][1]);
                        $data['msgText'] = implode(':', $_SESSION['data']['waktu']);
                        if($_SESSION['data']['waktu'][0] < 24 && $_SESSION['data']['waktu'][1] < 60 && $_SESSION['data']['waktu'][0] >= 0 && $_SESSION['data']['waktu'][1] >= 0){
                            $link = get_dblink();
                            $link->query("UPDATE memo SET waktu = '".$data['msgText']."' WHERE id=".$_SESSION['data']['id']);

                            $bot->replyText($event['replyToken'], "Waktu pengingat telah diatur pada jam ".$data['msgText'].".");

                            close_session();
                        } else {
                            $bot->replyText($event['replyToken'], 'Waktu yang kamu masukkan diluar standar internasional (00:00 - 23:59). Tolong masukkan kembali waktu dengan benar.');
                        }
                    }  else {
                        $bot->replyText($event['replyToken'], 'Waktu yang kamu masukkan diluar standar internasional (00:00 - 23:59). Tolong masukkan kembali waktu dengan benar.');
                    }
                }  else {
                    $bot->replyText($event['replyToken'], 'Waktu yang kamu masukkan diluar standar internasional (00:00 - 23:59). Tolong masukkan kembali waktu dengan benar.');
                }
            } else {
                close_session();
            }
        }
    }
    
    /**
      * [020]Hapus Memo
      * -- Code : 0x20
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0x20"){
        if($event['type']=='postback'){
            $link = get_dblink();
            $link->query("DELETE FROM memo WHERE id=".$data['postback']['id']);
            $bot->replyText($event['replyToken'], 'Memo telah dihapus.');
        }
        
        close_session();
    }
    
    /**
      * [021]Lihat Memo
      * -- Code : 0x21
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0x21"){
        $link=get_dblink();
        $res = $link->query("SELECT * FROM memo WHERE id_user='".$data['userId']."'");
        
        if($res->num_rows>1){
            $columns = null;
            while($row=$res->fetch_assoc()){
                $options=null;
                $options[] = new PostbackTemplateActionBuilder('Atur waktu', 'intent=atur+memo&id='.$row['id']);
                $options[] = new PostbackTemplateActionBuilder('Hapus', 'intent=hapus+memo&id='.$row['id']);
                
                $columns[] = new CarouselColumnTemplateBuilder($row['waktu'], truncstr(str_replace("\n", "", $row['text']), 60), null, $options);
            }
            $carouselMessage = new CarouselTemplateBuilder($columns);
            $templateMessage = new TemplateMessageBuilder("Daftar memo", $carouselMessage);
            $bot->pushMessage($data['userId'], $templateMessage);
        } else if($res->num_rows>0){
			$row=$res->fetch_assoc();
			
			$options[] = new PostbackTemplateActionBuilder('Atur waktu', 'intent=atur+memo&id='.$row['id']);
			$options[] = new PostbackTemplateActionBuilder('Hapus', 'intent=hapus+memo&id='.$row['id']);

			$buttonMessage = new ButtonTemplateBuilder($row['waktu'], truncstr(str_replace("\n", "", $row['text']), 60), null, $options);
            $templateMessage = new TemplateMessageBuilder("Daftar Memo", $buttonMessage);
            $bot->pushMessage($data['userId'], $templateMessage);
        } else {
            $bot->replyText($event['replyToken'], 'Kamu tidak mempunyai memo. Tambahkan memo dengan mengatakan \'memo\'.');
        }
        
        close_session();
    }
    
    /**
      * [022]Snooze Memo
      * -- Code : 0x22
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0x22"){
        if($event['type']=='postback'){
            $link = get_dblink();
            
            if(isset($data['postback']['snooze'])){
                $link->query("INSERT INTO memo(id, id_user, waktu, text) VALUES(".$data['postback']['id'].", '".$data['userId']."', '".date('H:i', strtotime('+'.$data['postback']['snooze'].'min'))."', '".$link->real_escape_string($data['postback']['text'])."') ON DUPLICATE KEY UPDATE waktu='".date('H:i', strtotime('+'.$data['postback']['snooze'].'min'))."'");
                $bot->replyText($event['replyToken'], 'Oke, saya akan ingatkan lagi pada jam '.date('H:i', strtotime('+'.$data['postback']['snooze'].'min')));
                close_session();
            } else {
                $bot->replyText($event['replyToken'], 'Oke, mau diingatkan lagi jam berapa?');
                $_SESSION['data'] = array('text'=>$link->real_escape_string($data['postback']['text']), 'waktu'=>'', 'id'=>$data['postback']['id']);
            }
        } else {
            if(isset($_SESSION['data'])){
                $_SESSION['data']['waktu'] = explode(':',$data['msgText']);

                if(count($_SESSION['data']['waktu']) == 2){
                    if(is_numeric($_SESSION['data']['waktu'][0]) &&
                       is_numeric($_SESSION['data']['waktu'][1])){
                        
                        $_SESSION['data']['waktu'][0] = sprintf("%02d", $_SESSION['data']['waktu'][0]);
                        $_SESSION['data']['waktu'][1] = sprintf("%02d", $_SESSION['data']['waktu'][1]);
                        $data['msgText'] = implode(':', $_SESSION['data']['waktu']);
                        if($_SESSION['data']['waktu'][0] < 24 && $_SESSION['data']['waktu'][1] < 60 && $_SESSION['data']['waktu'][0] >= 0 && $_SESSION['data']['waktu'][1] >= 0){
                            $link = get_dblink();
                            $link->query("INSERT INTO memo(id, id_user, waktu, text) VALUES(".$_SESSION['data']['id'].", '".$data['userId']."', '".$data['msgText']."', '".$_SESSION['data']['text']."') ON DUPLICATE KEY UPDATE waktu='".date('H:i', strtotime('+'.$data['postback']['snooze'].'min'))."'");

                            $bot->replyText($event['replyToken'], "Siap! Akan saya ingatkan kembali pada jam ".$data['msgText'].".");

                            close_session();
                        } else {
                            $bot->replyText($event['replyToken'], 'Waktu yang kamu masukkan diluar standar internasional (00:00 - 23:59). Tolong masukkan kembali waktu dengan benar.');
                        }
                    }  else {
                        $bot->replyText($event['replyToken'], 'Waktu yang kamu masukkan diluar standar internasional (00:00 - 23:59). Tolong masukkan kembali waktu dengan benar.');
                    }
                }  else {
                    $bot->replyText($event['replyToken'], 'Waktu yang kamu masukkan diluar standar internasional (00:00 - 23:59). Tolong masukkan kembali waktu dengan benar.');
                }
            } else {
                close_session();
            }
        }
    }
	
	/**
      * [023]Lihat Event
      * -- Code : 0x23
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0x23"){
        $options1[] = new MessageTemplateActionBuilder('Rincian Event', 'lihat detail');
		$options1[] = new MessageTemplateActionBuilder('Jadwal', 'lihat jadwal');                
		$options1[] = new MessageTemplateActionBuilder('Akomodasi', 'lihat akomodasi');

		$options2[] = new MessageTemplateActionBuilder('Informasi Tambahan', 'lihat tambahan');                
		$options2[] = new MessageTemplateActionBuilder('Kontak Penyelenggara', 'lihat cp');
		$options2[] = new MessageTemplateActionBuilder('Tinggalkan Event', 'tinggalkan');

		$columns[] = new CarouselColumnTemplateBuilder(null, "Menu 1", null, $options1);
		$columns[] = new CarouselColumnTemplateBuilder(null, "Menu 2", null, $options2);
		$carouselMessage = new CarouselTemplateBuilder($columns);
		$templateMessage = new TemplateMessageBuilder("Lihat informasi terkait event\nlihat detail\nlihat jadwal\nlihat akomodasi\nlihat tambahan\nlihat cp\ntinggalkan", $carouselMessage);
		$bot->pushMessage($data['userId'], $templateMessage);
        
        close_session();
    }
    
    /**
      * [0FD]Gabung @demo
      * -- Code : 0xFD
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0xFD"){
        $link = get_dblink();
        
        // Cek creator atau bukan
        $res = $link->query("select * from Events where id_creator = '".$data['userId']."'");
        if($res->num_rows > 0){
            $res = $res->fetch_assoc();
            $bot->pushText($data['userId'],"Kamu tidak bisa bergabung ke event lain karena kamu sudah membuat event ".$res['nama_event']." (@".$res['kd_event'].")");
            close_session();
        } else {
            // Cek sudah tergabung event atau belum
            $res = $link->query("select Joiner.*, Events.* from Joiner, Events where Joiner.id_event = Events.id_event AND Joiner.id_user = '".$data['userId']."'");
            if($res->num_rows > 0){
                $res = $res->fetch_assoc();
                $bot->pushText($data['userId'],"Kamu tidak bisa bergabung ke event lain karena kamu sudah tergabung dalam event ".$res['nama_event']." (@".$res['kd_event'].")");
                close_session();
            } else {
                // Belum tergabung di manapun
                if(!isset($_SESSION['data'])){
                    $_SESSION['data'] = array('kd_event' => '', 'nama_event'=>'', 'id_event'=>'');
                    $data['msgText'] = '@demo';
                    $_SESSION['state'] = $data['cmd']['gabung'];
                    main();
                }
            }
        }
    }
    
    /**
      * [0FE]Menu
      * -- Code : 0xFE
      * -- Menu utama
      */
    else if($_SESSION['state']['code'] == "0xFE"){
        $link = get_dblink();
        
        // Cek creator atau bukan
        $res = $link->query("select * from Events where id_creator = '".$data['userId']."'");
        if($res->num_rows > 0){
            $res = $res->fetch_assoc();
            
            $options[] = new MessageTemplateActionBuilder('Broadcast', 'bc');
            $options[] = new MessageTemplateActionBuilder('Atur Informasi', 'atur event');
            $options[] = new UriTemplateActionBuilder('Atur Kelengkapan', "https://www.config.klencer.mekarya.com/home/".$res['id_creator']);
            $options[] = new MessageTemplateActionBuilder('Bubarkan Event', 'bubarkan');
            
            $msg_str = $res['nama_event']." (@".$res['kd_event'].")";
            
            if(strlen($msg_str) > 60) {
                $bot->pushText($data['userId'],"Nama event : ". $msg_str);
                $buttonMessage = new ButtonTemplateBuilder("Menu Utama Penyelenggara", "Apa yang ingin kamu lakukan?", null, $options);
            } else {
                $buttonMessage = new ButtonTemplateBuilder("Menu Utama Penyelenggara", $msg_str, null, $options);
            }
            $templateMessage = new TemplateMessageBuilder("Menu penyelenggara\nbroadcast/bc\natur event\natur kelengkapan\nbubarkan", $buttonMessage);
            $bot->pushMessage($data['userId'], $templateMessage);
        } else {
            // Cek sudah tergabung event atau belum
            $res = $link->query("select * from Joiner where id_user = '".$data['userId']."' AND id_event IS NOT NULL");
            if($res->num_rows > 0){
                $options1[] = new MessageTemplateActionBuilder('Rincian Event', 'lihat detail');
                $options1[] = new MessageTemplateActionBuilder('Jadwal', 'lihat jadwal');                
                $options1[] = new MessageTemplateActionBuilder('Akomodasi', 'lihat akomodasi');
                
                $options2[] = new MessageTemplateActionBuilder('Informasi tambahan', 'lihat tambahan');                
                $options2[] = new MessageTemplateActionBuilder('Kontak Penyelenggara', 'lihat cp');
                $options2[] = new MessageTemplateActionBuilder('Tinggalkan event', 'tinggalkan');

                $columns[] = new CarouselColumnTemplateBuilder(null, "Menu 1", null, $options1);
                $columns[] = new CarouselColumnTemplateBuilder(null, "Menu 2", null, $options2);
                $carouselMessage = new CarouselTemplateBuilder($columns);
                $templateMessage = new TemplateMessageBuilder("new message", $carouselMessage);
                $bot->pushMessage($data['userId'], $templateMessage);
            } else {
                // Belum tergabung di manapun
                $options[] = new MessageTemplateActionBuilder('Selenggarakan event', 'buat');
                $options[] = new MessageTemplateActionBuilder('Gabung ke event', 'gabung');
                $options[] = new MessageTemplateActionBuilder('Gabung ke event demo', 'gabung @demo');
                $buttonMessage = new ButtonTemplateBuilder("Menu Utama", "Silahkan buat atau gabung ke event untuk memulai", null, $options);
                $templateMessage = new TemplateMessageBuilder("Menu anggota\nlihat detail\nlihat jadwal\nlihat akomodasi\nlihat tambahan\nlihat cp\ntinggalkan", $buttonMessage);
                $bot->pushMessage($data['userId'], $templateMessage);
            }
        }
        close_session();
    }

    /**
      * [0FF]Bantuan
      * -- Code : 0xFF
      * -- Bantuan
      */
    else if($_SESSION['state']['code'] == "0xFF"){
        $options[] = new MessageTemplateActionBuilder('Profilku', 'profil');
        $options[] = new UriTemplateActionBuilder('Video Tutorial', "https://bit.ly/klencer_video1");
        $options[] = new UriTemplateActionBuilder('Kontak', "https://bit.ly/klencer_cs");
        $options[] = new MessageTemplateActionBuilder('Tentang Kami', 'tentang');
        
        $buttonMessage = new ButtonTemplateBuilder("Menu Pengaturan dan Bantuan", "Kamu dapat lihat dan edit profilmu atau pilih menu bantuan.", null, $options);
        $templateMessage = new TemplateMessageBuilder("Menu pengaturan\nprofil\nhttps://bit.ly/klencer_video1\nhttps://bit.ly/klencer_cs\ntentang", $buttonMessage);
        $bot->pushMessage($data['userId'], $templateMessage);
        close_session();
    }
    
    /**
      * [009]Tentang
      * -- Code : 0x09
      * -- Bantuan
      */
    else if($_SESSION['state']['code'] == "0x09"){
        $bot->replyText($event['replyToken'], $_SESSION['state']['description']);
        close_session();
    }
	
	else {
		close_session();
	}
}