<?php 
/****************************************
 *                                      *
 *           Marlight Systems           *
 *      Install Script for EASY 2.0     *
 *    Copyright by Marlight Systems     *
 *        www.marlight-systems.de       *
 *              20.05.2018              *
 *          Version 0.9.7 BETA          *
 *                                      *
 ****************************************/

function htmlspecialchar($string) // Wandelt Htmlbefehle in zeichen um z.B. wie "<" in &lt; oder ">" in &gt; oder "/" in &#47;
{
 	$string = htmlentities($string, ENT_QUOTES | ENT_IGNORE, "UTF-8");
 	return $string;
}

function check_email($use) {
  	if(filter_var($use, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE) == '')
   		return false;
 	return true;
} 


function length($item, $length = 64, $start = 0, $type = "none"){
	if($type == "html")
		return substr(htmlspecialchar($item), $start, $length);
	elseif($type == "none")
		return substr($item, $start, $length);
	end;
}

function this_domain(){
	return $_SERVER["HTTP_HOST"];
}

function this_dir(){
	$url	= $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"];
	$parts 	= parse_url($url);
	$dir 	= dirname($parts["path"]);
	$folder = str_replace('install', '', $dir);
	$folder = str_replace($_SERVER["SERVER_NAME"].'/', '', $folder);
	return $folder;
}

function optional_title(){
	$url = $_SERVER["SERVER_NAME"];
	$splitHost = explode('.', $url);
	$cnt = count($splitHost);
	$name = explode('-', $splitHost[$cnt-2]);
	$newname = NULL;
	foreach($name as $str){
		if(!empty($newname)) $newname .= ' ';
		$newname .= ucfirst($str);	
	}
	return $newname;
}

function checker($a, $b, $c = 0){
	if($a == $b){
		if($c == 1){
			return 'checked';
		}
		return 'selected';
	} else {
		return '';
	}
}

function pass_conrtol($string){
	$str_anz = "";
	$pw_ok	 = "";
	$str_anz = strlen($string);
	$pw_ok = false;
	if($str_anz >= mainout('password_length')) $pw_ok = true;
	return $pw_ok;
}

function mainout($i){
	global $mysql;
	$select = $mysql->query("Select * From ".Prefix."_main Where id = '1'");
	$out = $select->fetch_array();
	$out = $out[$i];
	return $out;
}

function getMainData($tag){
	global $mysql;
	/************************
	 * Gibt den gewuenschten Wert aus der Tabelle "main" zurueck
	 * $tag => Welche Spalte ausgegeben werden soll
	 ************************/
	
	$sql = $mysql->query("Select `value` From `".Prefix."_main` Where tag = '$tag' Limit 1");
	if($sql->num_rows > 0){
		$row = $sql->fetch_assoc();
		return $row['value'];
	}
	return "Einstellung nicht gefunden!";
}

function dbout($i, $j, $t, $table){
	global $mysql;
	$select = $mysql->query("Select * From ".Prefix."_$table Where $j = '$t'");
	$out = $select->fetch_array();
	$out = $out[$i];
	return $out;
}

function dbanz($table, $j, $t){
	global $mysql;
	$select = $mysql->query("Select * From ".Prefix."_$table Where $j = '$t'");
	return $select->num_rows;
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

function isvalid($code){ 
	global $loginsystem;
	$menge = dbanz('user', 'uik', $code);
	$menge += dbanz('sessions', 'ulc', $code); 
	$menge += dbanz('sessions', 'sic', $code); 
	$menge += dbanz('changes', 'code', $code); 
	$menge += dbanz('codes', 'code', $code); 
    if($menge == 0){  
     return true;  
    } else {  
     return false; 
    }  
} 

function getCode($length = 32){ 
	$code = generate($length);
	while(!isvalid($code)){ 
 	 $code = generate($length); 
	} 
	return $code; 
}

class template extends install{
	private $page = NULL;
	private $dir = './steps/';
	
	public function __construct(){
		$this->page = length(isset($_GET['p']) ? $_GET['p'] : '', 16);
	}
	
	public function includeFile(){
		parent::conditions(NULL, false);
		$files 	 = array();
		$files[] = 'home';
		$files[] = 'terms_of_use';
		$files[] = 'step1';
		if($this->condition){
			$files[] = 'step2';
			$files[] = 'step2.2';
			if(parent::mysqlCondition()){
				$files[] = 'step3';
				$files[] = 'step4';
				if(!parent::is_user() || true){
					$files[] = 'finish';
				}
			}
		}
		
		if(!empty($this->page)){
			if(file_exists($this->dir.$this->page.'.php')){
				if(in_array($this->page, $files)){
					$page = $this->page;
				} else {
					$page = '404';	
				}
			} else {
				$page = '404';
			}
		} else {
			$page = 'home';	
		}
		
		return $this->dir.$page.'.php';
	}
}

class install{
	private $step;
	private $mysql;
	public  $condition = false;
	public  $valid_field = array();
	private $dbs = array("changes", "codes", "main", "ranks", "rules", "sessions", "sites", "user", "menu", "menu_group", "fields", "additional_user_information");
	private $username_blacklist = array("root", "admin", "administrator", "supporter", "system", "gast", "benutzer", "user");
	
	public function __construct(){
		global $db;
		$this->mysql = $mysql;
	}
	
	public function conditions($col, $return = true){
		$dirA = '../avatare';
		@chmod($dirA, 0755);
		$cols[0] = is_writable($dirA) ? '<span class="text-success">Ja</span>' : '<span class="text-danger">Nein</span>';
		
		$dirB = '../system';
		@chmod($dirb, 0755);
		$cols[1] = is_writable($dirB) ? '<span class="text-success">Ja</span>' : '<span class="text-danger">Nein</span>';
				
		$cols[3] = (version_compare(PHP_VERSION, '5.5.0') >= 0) ? '<span class="text-success">'.phpversion().'</span>' : '<span class="text-danger">'.phpversion().'</span>';
		
		if(is_writable($dirA) && is_writable($dirB) && (version_compare(PHP_VERSION, '5.5.0') >= 0) && function_exists('imagettftext') && extension_loaded('gd'))
			$this->condition = true;
		
		if($return)
			return $cols[$col];
	}
	
	public function checkTable($table){
		global $mysql;
		$res = $mysql->query("SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_name = '".Prefix."_".$table."'");
		return ($res->num_rows == 1) ? true : false;
	}
	
	public function mysqlCondition(){
		global $mysql_data;
		global $dberror;
		if(!$dberror)
			return true;
		if($mysql_data["host"] != "[host]" && $mysql_data["user"] != "[user]" && $mysql_data["passwd"] != "[pass]" && $mysql_data["database"] != "[dbna]" && $mysql_data["prefix"] != "[pref]"){
			$this->mysql = @new mysqli($mysql_data["host"], $mysql_data["user"], $mysql_data["passwd"], $mysql_data["database"]);
			if(mysqli_connect_errno()){ // If no connection to the database is possible
				return false;
			} else {
				return true;
			}
		}
		return false;
	}
	
	public function show_tables(){
		$dbs = $this->dbs;
		$rtn = '';
		foreach($dbs as $database){
			$rtn .= '<tr>
				<td>'.Prefix."_".$database.'</td>
				<td>'.(self::checkTable($database) ? '<span class="text-danger">Ja</span>' : '<span class="text-success">Nein</span>').'</td>
			</tr>';
		}
		return $rtn;
	}
	
	public function mysqlConnection(){
		$error 		= NULL;
		$host 		= length(isset($_POST['mysql_host']) 	? $_POST['mysql_host'] : '', 64);
		$user 		= length(isset($_POST['mysql_user']) 	? $_POST['mysql_user'] : '', 64);
		$passwd 	= length(isset($_POST['mysql_passwd']) 	? $_POST['mysql_passwd'] : '', 64);
		$database	= length(isset($_POST['mysql_database'])? $_POST['mysql_database'] : '', 63);
		$prefix		= length(isset($_POST['mysql_prefix'])	? $_POST['mysql_prefix'] : 'ml_', 16);
		$this->valid_field["host"]		= empty($host) 		? 'has-error' : '';
		$this->valid_field["user"]		= empty($user) 		? 'has-error' : '';
		$this->valid_field["database"]	= empty($database) 	? 'has-error' : '';
		$this->valid_field["prefix"]	= empty($prefix) 	? 'has-error' : '';
		if(!in_array('has-error', $this->valid_field)){
			if(preg_match("/^([a-zA-Z0-9_]{1,16}$)/", $prefix)){
				if(preg_match("/^([a-zA-Z0-9_-]{4,63}$)/", $database)){
					if(preg_match("/^([a-zA-Z0-9_-]{4,16}$)/", $user)){
						$db = @new mysqli($host, $user, $passwd, $database);
						if(!mysqli_connect_errno()){
							if(substr($prefix, -3) != '_ml')
								$prefix = $prefix.'_ml';
							$file = file_get_contents('./setup/config.php');
							$file = str_replace('[host]";', $host.'";', $file);
							$file = str_replace('[user]";', $user.'";', $file);
							$file = str_replace('[pass]";', $passwd.'";', $file);
							$file = str_replace('[dbna]";', $database.'";', $file);
							$file = str_replace('[pref]";', $prefix.'";', $file);
							if(file_put_contents('../system/config.inc.php', $file)){
								$dbs = $this->dbs;
								$sum_nums = 0;
								
								foreach($dbs as $dbfile){
									$query = $db->query("SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_name = '".$prefix."_".$dbfile."'");
									$sum_nums += $query->num_rows;
								}

								if($sum_nums == 0){
									foreach($dbs as $database_file){
										$query = explode(';', str_replace('[prefix]', substr($prefix, 0, -3), str_replace("uml;", 'uml#',  str_replace("lig;", 'lig#', file_get_contents('./sql/_ml_'.$database_file.'.sql')))));
										foreach($query as $end_query){
											$end_query = str_replace(array('uml#', 'lig#'), array('uml;', 'lig;'), $end_query);
											if(!empty($end_query)){
												if(preg_match('/^INSERT/', $end_query)){
													preg_match("/(.*)VALUES/", $end_query, $output_array);
													$clean_query = $output_array[0];
													preg_match_all("/\((.*)\)/", $end_query, $output_array);
													unset($output_array[0][0]);
													foreach($output_array[0] as $val_query){
														if(!empty($val_query)){
															$sql = $db->query($clean_query." ".$val_query);
															if($sql === false){
																$text = 'Eintr&auml;ge erstellen in';
																$error = 'Fehler beim '.$text.' der Tabelle "'.$prefix.'_'.$database_file.'"!<br>'.$clean_query." ".$val_query.' <br> MySQL-Fehler '.$db->errno.': '.$db->error;
																break;
																break;
															}
														}
													}
												} else {
													$sql = $db->query($end_query);
													if($sql === false){
														if(preg_match('/^CREATE/', $end_query))
															$text = 'erstellen';
														elseif(preg_match('/^INSERT/', $end_query))
															$text = 'Eintr&auml;ge erstellen in';
														else
															$text = 'ver&auml;ndern';

														$error = 'Fehler beim '.$text.' der Tabelle "'.$prefix.'_'.$database_file.'"!<br>'.$end_query.' <br> MySQL-Fehler '.$db->errno.': '.$db->error;
														break;
													}
												}
											}
										}
									}
									
									if(empty($error)){
										header('Location: '.$_SERVER["SCRIPT_NAME"].'?p=step3');
										exit();
									}
								} else {
									header('Location: '.$_SERVER["SCRIPT_NAME"].'?p=step2.2');
									exit();
								}
							} else {
								$error = 'Fehler beim &auml;ndern der config.php! Bitte &uuml;berpr&uuml;fe die Schreibrechte des Ordners ./system/ und der Datei ./system/config.php';	
							}
						} else {
							$error = 'Fehler beim Verbinden mit der Datenbank!<br>'.mysqli_connect_error()." (MySQL-Error: ".mysqli_connect_errno().")";	
							$this->valid_field["host"] = 'has-error';
							$this->valid_field["user"] = 'has-error';
							$this->valid_field["passwd"] = 'has-error';
							$this->valid_field["database"] = 'has-error';
						}
					} else {
						$error = 'Der Benutzername besitzt ung&uuml;tige Zeichen!<br>Erlaubte Zeichen: <ol><li>Zwischen 6 und 14 alphanumerische Zeichen</li><li>keine Sonderzeichen, bis auf den Unterstrich ("_") erlaubt</li></ol>';	
						$this->valid_field["user"] = 'has-error';
					}
				} else {
					$error = 'Der Datenbankname besitzt ung&uuml;tige Zeichen!<br>Erlaubte Zeichen: <ol><li>Zwischen 6 und 63 alphanumerische Zeichen</li><li>keine Sonderzeichen, bis auf den Unterstrich ("_") erlaubt</li></ol>';	
					$this->valid_field["database"] = 'has-error';
				}
			} else {
				$error = 'Der Pr&auml;fix darf keine Sonderzeichen enthalten!';
				$this->valid_field["prefix"] = 'has-error';
			}
		} else {
			$error = 'Bitte f&uuml;lle alle Felder aus!';
		}
		return $error;
	}
	
	public function createMysqlTableIgnoreExists(){
		$prefix = Prefix;
		$dbs = $this->dbs;
		$db = $this->mysql;
		
		foreach($dbs as $database_file){
			if(!self::checkTable($database_file)){
				$query = explode(';', str_replace('[prefix]', substr($prefix, 0, -3), str_replace("uml;", 'uml#',  str_replace("lig;", 'lig#', file_get_contents('./sql/_ml_'.$database_file.'.sql')))));
				foreach($query as $end_query){
					$end_query = str_replace(array('uml#', 'lig#'), array('uml;', 'lig;'), $end_query);
					if(!empty($end_query)){
						if(preg_match('/^INSERT/', $end_query)){
							preg_match("/(.*)VALUES/", $end_query, $output_array);
							$clean_query = $output_array[0];
							preg_match_all("/\((.*)\)/", $end_query, $output_array);
							unset($output_array[0][0]);
							foreach($output_array[0] as $val_query){
								if(!empty($val_query)){
									$sql = $db->query($clean_query." ".$val_query);
									if($sql === false){
										$text = 'Eintr&auml;ge erstellen in';
										$error = 'Fehler beim '.$text.' der Tabelle "'.$prefix.'_'.$database_file.'"!<br>'.$clean_query." ".$val_query.' <br> MySQL-Fehler '.$db->errno.': '.$db->error;
										break;
										break;
									}
								}
							}
						} else {
							$sql = $db->query($end_query);
							if($sql === false){
								if(preg_match('/^CREATE/', $end_query))
									$text = 'erstellen';
								elseif(preg_match('/^INSERT/', $end_query))
									$text = 'Eintr&auml;ge erstellen in';
								else
									$text = 'ver&auml;ndern';
								$error = 'Fehler beim '.$text.' der Tabelle "'.$prefix.'_'.$database_file.'"!<br>'.$end_query.' <br> MySQL-Fehler '.$db->errno.': '.$db->error;
								break;
							}
						}
					}
				}
			}
		}
		
		if(empty($error)):
			header('Location: '.$_SERVER["SCRIPT_NAME"].'?p=step3');
			exit();
		endif;
		
		return $error;
	}
	
	public function createMysqlTableDeleteExists(){
		$prefix = Prefix;
		$dbs = $this->dbs;
		$db = $this->mysql;
		
		foreach($dbs as $database_file){
			if(self::checkTable($database_file))
				$this->mysql->query("DROP TABLE `".Prefix."_".$database_file."`");
			
			$query = explode(';', str_replace('[prefix]', substr($prefix, 0, -3), str_replace("uml;", 'uml#',  str_replace("lig;", 'lig#', file_get_contents('./sql/_ml_'.$database_file.'.sql')))));
			foreach($query as $end_query){
				$end_query = str_replace(array('uml#', 'lig#'), array('uml;', 'lig;'), $end_query);
				if(!empty($end_query)){
					if(preg_match('/^INSERT/', $end_query)){
						preg_match("/(.*)VALUES/", $end_query, $output_array);
						$clean_query = $output_array[0];
						preg_match_all("/\((.*)\)/", $end_query, $output_array);
						unset($output_array[0][0]);
						foreach($output_array[0] as $val_query){
							if(!empty($val_query)){
								$sql = $db->query($clean_query." ".$val_query);
								if($sql === false){
									$text = 'Eintr&auml;ge erstellen in';
									$error = 'Fehler beim '.$text.' der Tabelle "'.$prefix.'_'.$database_file.'"!<br>'.$clean_query." ".$val_query.' <br> MySQL-Fehler '.$db->errno.': '.$db->error;
									break;
									break;
								}
							}
						}
					} else {
						$sql = $db->query($end_query);
						if($sql === false){
							if(preg_match('/^CREATE/', $end_query))
								$text = 'erstellen';
							elseif(preg_match('/^INSERT/', $end_query))
								$text = 'Eintr&auml;ge erstellen in';
							else
								$text = 'ver&auml;ndern';
							$error = 'Fehler beim '.$text.' der Tabelle "'.$prefix.'_'.$database_file.'"!<br>'.$end_query.' <br> MySQL-Fehler '.$db->errno.': '.$db->error;
							break;
						}
					}
				}
			}

		}
		
		return $error;
	}
	
	public function saveMainsettings(){
		global $mysql;
		$error  = NULL;
		$title		= length(isset($_POST['title']) ? $_POST['title'] : NULL, 64, 0, "html");
		$title_short= length(isset($_POST['title_short']) ? $_POST['title_short'] : NULL, 16, 0, "html");
		$email		= length(isset($_POST['email']) ? $_POST['email'] : NULL, 128);
		$sender		= length(isset($_POST['from']) ? $_POST['from'] : NULL, 128);
		$to			= length(isset($_POST['to']) ? $_POST['to'] : NULL, 128);
		$user_share	= length(isset($_POST['useradministration_share']) ? $_POST['useradministration_share'] : NULL, 1);
		$restore	= length(isset($_POST['restore']) ? $_POST['restore'] : NULL, 1);
		$pwlength	= length(isset($_POST['pwlength']) ? $_POST['pwlength'] : NULL, 2);
		$cookielife	= length(isset($_POST['cookie_lifetime']) ? $_POST['cookie_lifetime'] : 90*86400, 16);
		$regist_ac	= length(isset($_POST['regist_ac']) ? $_POST['regist_ac'] : 0, 1);
		$pwv_ac		= length(isset($_POST['pwv_ac']) ? $_POST['pwv_ac'] : 0, 1);
		$reg_mode	= length(isset($_POST['reg_mode']) ? $_POST['reg_mode'] : 2, 1);
		if(!empty($title) && !empty($title_short) && !empty($email) && !empty($sender) && !empty($to) && !empty($restore) && !empty($pwlength) && $pwlength >= 3 && $pwlength < 65){
						
			$update = array();
			$update['site_title'] = $title;
			$update['short_site_title'] = $title_short;
			$update['administrator_mail'] = $email;
			$update['mail_sender'] = $sender;
			$update['mail_receiver'] = $to;
			$update['useradministration_share'] = $user_share;
			$update['restore'] = $restore;
			$update['password_length'] = $pwlength;
			$update['cookielifetime'] = $cookielife;
			$update['regist_active'] = $regist_ac;
			$update['pwv_active'] = $pwv_ac;
			$update['user_activation_mode'] = $reg_mode;
			
			foreach($update as $key => $val){
				$sql = $mysql->query("Update ".Prefix."_main Set value = '$val' Where tag = '$key'");
				if($sql === false)
					break;
			}
			if($sql === true){
				header('Location: '.$_SERVER["SCRIPT_NAME"].'?p=step4');
				exit();
			} else {
				$error = 'Fehler beim speichern der Einstellungen! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut.';
				errormail('Fehler beim speichern der Einstellungen! Fehler in class loginsystem => function "mainSettings()". MySQL-Fehler '.$mysql->errno.': '.$mysql->error);
			}
		} else {
			$error = 'Es m&uuml;ssen alle Felder ausgef&uuml;t werden!';	
		}

		return $error;
	}
	
	public function pwhash($passwd){
		return password_hash($passwd, PASSWORD_BCRYPT, array('cost' => 13));
	}
	
	public function createUser(){
		global $mysql;
		$error = isset($error) ? $error : '';	
		$this->valid_field["username"] = empty($user) ? 'has-error' : '';
		$this->valid_field["email"] = empty($mail) ? 'has-error' : '';
		$this->valid_field["passwd"] = empty($pass) ? 'has-error' : '';
		$this->valid_field["pw_co"] = empty($pwco) ? 'has-error' : '';
		
		$username	= length(isset($_POST['new-username']) ? $_POST['new-username'] : '', 64, 0, "html");	
		$fullname	= length(isset($_POST['new-fullname']) ? $_POST['new-fullname'] : '', 64, 0, "html");	
		$email		= length(isset($_POST['new-email']) ? $_POST['new-email'] : '', 64, 0, "html");	
		$password	= length(isset($_POST['new-password']) ? $_POST['new-password'] : '', 64, 0, "html");	
		$password_confirm	= length(isset($_POST['new-password-confirm']) ? $_POST['new-password-confirm'] : '', 64);	

		if(self::is_user()){
			if(!empty($username) && !empty($fullname) && !empty($email) && !empty($password) && !empty($password_confirm)){
				if(md5($password) == md5($password_confirm)){
					if(check_email($email)){
						if(!in_array(strtolower($username), $this->username_blacklist)){
							if(preg_match("#^[a-zA-Z0-9äöüÄÖÜß_-]{4,}+$#", $username)){
								if(empty($fullname) || preg_match("#^[\D\s]{2,128}+$#", $fullname)){
									if(pass_conrtol($password)){
										$rank = '1813201541';
										$password = self::pwhash($password);
										$uik = getCode(32);
										$names = explode(' ', $fullname);
										$namesAmount = count($names);
										$last_name = ($namesAmount > 1) ? array_pop($names) : NULL;
										$first_name = implode(' ', $names);

										$sql = $mysql->query("Insert Into ".Prefix."_user (username, first_name, last_name, email, password, active, rank, uik, regdate, avatar) Values ('$username', '$first_name', '$last_name', '$email', '$password', '1', '$rank', '$uik', '".time()."', '')");
										if($sql === true){
											header('Location: '.$_SERVER["SCRIPT_NAME"].'?p=finish');
											exit();
										} else {
											$error = 'Fehler beim erstellen des Benutzers! Fehler in class loginsystem => function createUser() MySQL-Fehler '.$mysql->errno.': '.$mysql->error;
										}
									} else {
										$error = 'Das Passwort muss mindestens aus '.mainout('password_length').' Zeichen bestehen!';
										$this->valid_field["passwd"] = 'has-error';
									}
								} else {
									$error = 'Du hast aber einen komsichen Namen?! Du hast bestimmt keine Zahlen oder Zeichen in deinem Namen, korrigiere dies bitte :) oder ist dein Name k&uuml;rzer als 6 Buchstaben? o.O Ich glaube nicht ;)';	
									$this->valid_field["fullname"] = 'has-error';
								}
							} else {
								$error = 'Der Benutzername enth&auml;lt ung&uuml;tige Zeichen oder ist zu kurz! <br>Erlaubte Zeichen:<br><ul><li>A-Z</li><li>a-z</li><li>0-9</li><li>mindestens 4 Zeichen</li></ul>';	
								$this->valid_field["username"] = 'has-error';
							}
						} else {
							$error = 'Der Benutzername ist nicht zugelassen!';
							$this->valid_field["username"] = 'has-error';
						}
					} else {
						$error = 'Dei einegegebe E-Mail Adresse hat ein ung&uuml;ltiges Format! Bitte &uuml;berpr&uuml;fe die E-Mail Adresse!';	
						$this->valid_field["email"] = 'has-error';
					}
				} else {
					$error = 'Die Passw&ouml;rter stimmen nicht &uuml;berein! Bitte &uuml;berpr&uuml;fe diese!';	
					$this->valid_field["passwd"] = 'has-error';
					$this->valid_field["pwco"] = 'has-error';
				}
			} else {
				$error = 'Du musst alle Felder ausf&uuml;llen um einen Benutzer hinzuf&uuml;gen zu k&ouml;nnen!';
			}
		} else {
			$error = 'Es existiert bereits ein Account!';	
		}

		return $error;
	}
	
	public function is_user(){
		global $dberror;
		global $mysql;
		if(!$dberror){ // self::mysqlCondition()
			$res = $mysql->query("SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_name = '".Prefix."_user'");
			if($res->num_rows != 0){
				$sql = $mysql->query("Select * From ".Prefix."_user Where uik != '3A2xdfRKw5k6IqptThiZSFXbT5J0oELO' AND uik != '3A2xdfRKw9l6IqptThiZSFXbT5J0oELO'");
				return ($sql->num_rows != 0) ? false : true;
			}
			return false;
		}
	}

}




























?>