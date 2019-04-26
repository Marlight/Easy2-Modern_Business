<?php
/********************************************
* 
* System:   EASY 2.0 Loginsystem
* Author:   Marius Rasche (aka Marlight)
* File:     functions.inc.php
* FVersion: 1.0.3.2 (this file)
* SVersion: 0.9.7 BETA (complete System)
* Date:     20.05.2018
*
* Created by www.marlight-systems.de
* Copyright by Marlight Systems (www.marlight-systems.de)
* All rights reserved.
* 
*********************************************/

function check_email($use) {
  	if(filter_var($use, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE) == ''){
   		return false;
  	}
 	return true;
} 

function delTree($dir) { // Löscht einen ganzen Verzeichnis-Baum
	if($dir != './' && $dir != ''){
		$files = array_diff(scandir($dir), array('.','..')); 
		foreach ($files as $file) { 
			is_dir("$dir/$file") ? delTree("$dir/$file") : unlink("$dir/$file"); 
		} 
		return rmdir($dir); 
	}
	return false;
}

function htmlspecialchar($string){ // Wandelt Htmlbefehle in zeichen um z.B. wie "<" in &lt; oder ">" in &gt; oder "/" in &#47;
 	$string = htmlentities($string, ENT_QUOTES | ENT_IGNORE, "UTF-8");
 	return $string;
}

function length($item, $length = 64, $start = 0, $type = "html"){
	global $mysql;
	if($type == "html")
		return substr(htmlspecialchar($item), $start, $length);
	elseif($type == "sql")
		return substr($mysql->real_escape_string($item), $start, $length);
	elseif($type == "none")
		return substr($item, $start, $length);
	end;
}

function this_domain(){
	return $_SERVER["HTTP_HOST"];
}

function checker($a, $b, $c = 0){
	if(is_array($a)){
		if(in_array($b, $a)){
			if($c == 1){
				return 'checked';
			}
			return 'selected';
		}
	} else {
		if($a == $b){
			if($c == 1){
				return 'checked';
			}
			return 'selected';
		}
	}
	return '';
}

function getCurrentUrl(){
	$https = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : false;
	if($https)
		$url = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	else
		$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$url = parse_url($url);
	return $url['scheme'].'://'.$url['host'].$url['path'];
}

function generate_hex(){  
    return bin2hex(openssl_random_pseudo_bytes(8*4));
}

function encrypt($str, $key = "abe44db3f97da1ec74eb3993e9c08be4d99d383d1a5a3b3596f889e0d43cd2c4") {
	$key = pack('H*', $key);
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB); 
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND); 
	$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $str, MCRYPT_MODE_ECB, $iv);
	$ciphertext = $iv . $ciphertext;
	$ciphertext_base64 = base64_encode($ciphertext);
	return $ciphertext_base64;
}

function decrypt($str, $key = "abe44db3f97da1ec74eb3993e9c08be4d99d383d1a5a3b3596f889e0d43cd2c4"){
	$key = pack('H*', $key);
	$ciphertext_dec = base64_decode($str);
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB); 
	$iv_dec = substr($ciphertext_dec, 0, $iv_size);
	$ciphertext_dec = substr($ciphertext_dec, $iv_size);
	return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_ECB, $iv_dec);
}

function generate($length){
	$dummy = array_merge(range('0', '9'), range('a', 'z'), range('A', 'Z'));
    mt_srand((double)microtime() * 1000000);
    for($i = 1; $i <= (count($dummy)*2); $i++) 
    {
      $swap = mt_rand(0, count($dummy)-1);
      $tmp = $dummy[$swap];
      $dummy[$swap] = $dummy[0];
      $dummy[0] = $tmp;
    }
    $dummy = substr(implode('', $dummy), 0, $length);
    
    return $dummy;
}

function isvalid($code, $tables = NULL, $columns = NULL){ 
	global $loginsystem;
	$tables = is_array($tables) ? $tables : array($tables);
	$columns = is_array($columns) ? $columns : array($columns);	
	foreach($tables as $key => $table){
		$cols = $columns[$key];
		$cols = explode(',', $cols);
		foreach($cols as $column){
			if($loginsystem->getAmount($table, $column, $code) > 0){
				return false;
			}
		}
	}
	return true;
} 

function getCode($length = 32, $tables = NULL, $columns = NULL){
	/*****************
	 * $length = Laenge des Codes
	 * $tables = Welche Tabellen auf existens des Codes geprueft werden sollen
	 * $columns = Welche Spalten in den Tabellen geprueft werden sollen
	 * Angabe als Array oder String moeglich
	 * String: Nur eine Tabelle moeglich, bei mehreren Spalten ($columns) mit Komma trennen
	 * Array: Mehrere Tabellen als Array angeben, die Keys der Array werden 1:1 verglichen d.h. im ersten Eintrag auch Tabelle 1 mit Spalte 1 (oder ggf. mehrere wenn mit Komma getrennt)
	 *****************/
	$code = generate($length);
	while(!isvalid($code, $tables, $columns)){ 
 	 $code = generate($length); 
	} 
	return $code; 
}

function check_filename($string){
	if(preg_match("/^[_a-zA-Z0-9\(\)\s-.]*[.]{1}[a-zA-Z]{1,16}$/", $string)){
		return true;
	}
	return false;
}

function getFilePermission($file) {
	$length = strlen(decoct(fileperms($file)))-3;
	return substr(decoct(fileperms($file)),$length);
}

function encodeRand($str, $seed=354613) {
	mt_srand($seed);
    $out = array();
    for ($x=0, $l=strlen($str); $x<$l; $x++) {
       $out[$x] = (ord($str[$x]) * 3) + mt_rand(350, 16000);
    }
    
    mt_srand();
    return implode('-', $out);
}
   
function decodeRand($str, $seed=354613) {
    mt_srand($seed);
    $blocks = explode('-', $str);
    $out = array();
    foreach ($blocks as $block) {
        $ord = (intval($block) - mt_rand(350, 16000)) / 3;
        $out[] = chr($ord);
    }
      
     mt_srand();
     return implode('', $out);
}

function timeformer($dom = 'd', $timeset, $short = 'long'){
	$months 	= array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);
	$monthnames = array();
	$monthnames['01']['de']['long'] = 'Januar';
	$monthnames['02']['de']['long'] = 'Februar';
	$monthnames['03']['de']['long'] = 'März';
	$monthnames['04']['de']['long'] = 'April';
	$monthnames['05']['de']['long'] = 'Mai';
	$monthnames['06']['de']['long'] = 'Juni';
	$monthnames['07']['de']['long'] = 'Juli';
	$monthnames['08']['de']['long'] = 'August';
	$monthnames['09']['de']['long'] = 'September';
	$monthnames['10']['de']['long'] = 'Oktober';
	$monthnames['11']['de']['long'] = 'November';
	$monthnames['12']['de']['long'] = 'Dezember';
	$monthnames['01']['de']['short'] = 'Jan';
	$monthnames['02']['de']['short'] = 'Feb';
	$monthnames['03']['de']['short'] = 'Mär';
	$monthnames['04']['de']['short'] = 'Apr';
	$monthnames['05']['de']['short'] = 'Mai';
	$monthnames['06']['de']['short'] = 'Jun';
	$monthnames['07']['de']['short'] = 'Jul';
	$monthnames['08']['de']['short'] = 'Aug';
	$monthnames['09']['de']['short'] = 'Sep';
	$monthnames['10']['de']['short'] = 'Okt';
	$monthnames['11']['de']['short'] = 'Nov';
	$monthnames['12']['de']['short'] = 'Dez';
	$weekdays 		   = array();
	$weekdays[0]['de']['long'] = 'Sonntag';
	$weekdays[1]['de']['long'] = 'Montag';
	$weekdays[2]['de']['long'] = 'Dienstag';
	$weekdays[3]['de']['long'] = 'Mittwoch';
	$weekdays[4]['de']['long'] = 'Donnerstag';
	$weekdays[5]['de']['long'] = 'Freitag';
	$weekdays[6]['de']['long'] = 'Samstag';
	$weekdays[0]['de']['short'] = 'So';
	$weekdays[1]['de']['short'] = 'Mo';
	$weekdays[2]['de']['short'] = 'Di';
	$weekdays[3]['de']['short'] = 'Mi';
	$weekdays[4]['de']['short'] = 'Do';
	$weekdays[5]['de']['short'] = 'Fr';
	$weekdays[6]['de']['short'] = 'Sa';
	
	switch($dom){
		case'd':
			$number = date('w', $timeset);
			$output = $weekdays[$number]['de'][$short];
		break;
		case'm':
			$month = date('m', $timeset);
			if(in_array($month, $months)){
				$output = $monthnames[$month]['de'][$short];
			}
		break;
	}
	return $output;
}

function timeout($timestamp, $short = 'long'){
	$time	   	= time();
	$timediff	= $time - $timestamp;
	if($timediff <= 59){
		$timeout = 'vor '.$timediff.' Sek';
	} elseif($timediff <= 3599) {
		$timeout = 'vor '.number_format($timediff / 60).' Min';
	} elseif($timediff <= 86399) {
		$timeout = 'vor '.number_format($timediff / 3600).' Std';
	} elseif(date('d.m.Y', $timestamp) == date('d.m.Y', $time - 86399)) {
		$timeout = 'Gestern';
	} else {
		$timeout = date('d.', $timestamp).' '.timeformer('m', $timestamp, $short);
	}
	return $timeout;
}

function timediv($time){
	if(date('d') == date('d', $time)){
		return 'Heute';
	} elseif(date('d', time() - 86400) == date('d', $time)){
		return 'Gestern';
	} else {
		return 'am '.date('d.m.Y', $time);
	}
}

function check_date($date,$format,$sep){    
    $pos1    = strpos($format, 'd');
    $pos2    = strpos($format, 'm');
    $pos3    = strpos($format, 'Y');
    $check    = explode($sep,$date);
    return checkdate($check[$pos2],$check[$pos1],$check[$pos3]);
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

function count_size($size){ // Ermittelt die richtige Größeneinheit 
	$sizes = array();
	$sizes[0] = 'Byte';
	$sizes[1] = 'KiB';
	$sizes[2] = 'MiB';
	$sizes[3] = 'GiB';
	
	if($size < 1024){
		return $size.' '.$sizes[0];
	}
	
	if($size < 1048576){
		$size = $size / 1024;
		$size = round($size, 2);
		$size = str_replace('.', ',', $size);
		return $size.' '.$sizes[1];
	}
	
	if($size < 1073741824){
		$size = $size / 1048576;
		$size = round($size, 2);
		$size = str_replace('.', ',', $size);
		return $size.' '.$sizes[2];
	}
	
	if($size < 1099511627776){
		$size = $size / 1073741824;
		$size = round($size, 2);
		$size = str_replace('.', ',', $size);
		return $size.' '.$sizes[3];
	}
}

/* Error Mails
**********************************/

function errormail($message){
	global $loginsystem;
	$from = $loginsystem->getMainData('site_title');
	$subject = 'Fehler auf deiner Homepage';
	$message = $message;
	$header  = "From: $from <".$loginsystem->getMainData('mail_sender').">"."\r\n";
	$header .= "Reply-To:"."\r\n";
	$header .= "Mime-Version: 1.0"."\r\n";
    $header .= "Content-Type: text/html; charset=utf-8"."\r\n";
    $header .= "Content-Transfer-Encoding: quoted-printable"."\r\n";
    $header .= "X-Mailer: PHP v".phpversion();
    mail($loginsystem->getMainData('administrator_mail'), $subject, $message, $header);
}



/* Auto remover
**********************************/

function auto_remover(){
	global $mysql;
	global $loginsystem;
	
	$time = strtotime("-14 days");
	$sql = $mysql->query("Delete From `".Prefix."_changes` Where (changed = '0' AND timestamp < '$time') OR (changed != '0' AND changed < '$time')");
	$time2 = time() - $loginsystem->getMainData('cookielifetime');
	$sql = $mysql->query("Delete From `".Prefix."_sessions` Where (closed = '1' AND last_action < '$time') OR (closed = '0' AND logout = '0' AND last_action < '$time2')");
}

/* Kontaktformular / Contact
**********************************/

function contact(){
	global $loginsystem;
	$name = length(isset($_POST['name']) ? $_POST['name'] : '', 64);
	$phone = length(isset($_POST['phone']) ? $_POST['phone'] : '', 16);
	$email = length(isset($_POST['email']) ? $_POST['email'] : '', 64);
	$message = length(isset($_POST['message']) ? $_POST['message'] : '', 2048);
	$captcha = length(isset($_POST['captcha']) ? $_POST['captcha'] : '', 4);
	$dsgvo = length(isset($_POST['dsgvo']) ? $_POST['dsgvo'] : '', 1);
	if(!empty($name) && !empty($email) && !empty($message) && !empty($captcha) && !empty($dsgvo)){
		if(check_email($email)){
			$captchaClass = new captcha();
			if($captchaClass->check_captcha($captcha)){
				$data = array();
				$data["subject"] = "Kontaktformular";
				$data["name"] = $name;
				$data["email"] = $email;
				$data["phone"] = $phone;
				$data["message"] = nl2br($message);
				$loginsystem->sendMail("contact.html", NULL, $data, $loginsystem->getMainData('mail_receiver'), $email);
				header('Location: ?p=contact&h=contact_success');
				exit();
			} else {
				$error = 'Fehlerhaften Sicherheitscode eingegeben! Bitte versuche es erneut!';
			}
		} else {
			$error = 'Ung&uuml;ltige E-Mail Adresse!';
		}
	} else {
		$error = 'Es m&uuml;ssen alle Pflichtfelder (*) ausgef&uuml;llt werden.';
	}
	return $error;
}



 // Help functions
function printarray($array){
  while(list($key,$value) = each($array)){
    if(is_array($value)){
      echo $key."(array):<blockquote>";
      printarray($value);//recursief!! 
      echo "</blockquote>";
    }elseif(is_object($value)){
      echo $key."(object):<blockquote>";
      printobject($value);
      echo "</blockquote>";
    }else{
      echo $key."==>".$value."<br />";
    }
  }
}

function printobject($obj){
  $array = get_object_vars($obj);
  printarray($array);
}














?>