<?php
/********************************************
* 
* System:   EASY 2.0 Loginsystem
* Author:   Marius Rasche (aka Marlight)
* Class:    loginsystem
* File:     loginsystem.php
* FVersion: 0.9.9.2 (this file)
* SVersion: 0.9.7 BETA (complete System)
* Date:     20.05.2018
*
* Created by www.marlight-systems.de
* Copyright by Marlight Systems (www.marlight-systems.de)
* All rights reserved.
* 
*********************************************/

class loginsystem extends database{
	protected $mysql;
	private $options = array('cost' => 13);
	private	$dirAvatar = './avatare/';
	private $username_blacklist = array("root", "admin", "administrator", "supporter", "system", "gast", "benutzer", "user");
	
	/**
	 * SessionData
	 */
	protected $sessionData = array();
	
	public function __construct(){
		parent::__construct();	
		self::session_data();
		self::autoCodeRemover();
	}
	
	public function pwverify($passwd, $passwd_ref = NULL){
		if(empty($passwd_ref)) $passwd_ref = self::getUser('password');
		$options = array('cost' => 13); 
		if(password_verify($passwd, $passwd_ref)){
			return true;
		}
		return false;
	}
	
	public function pwhash($passwd){
		return password_hash($passwd, PASSWORD_BCRYPT, $this->options);
	}
	
	/*********************************************
	 * Session
	 *********************************************/
	
	private function session_data(){
		$this->sessionData['uik'] = length(isset($_SESSION['ml_login_uik']) ? $_SESSION['ml_login_uik'] : '', 32, NULL, "sql");
		$this->sessionData['ulc']  = length(isset($_SESSION['ml_login_code']) ? $_SESSION['ml_login_code'] : '', 32, NULL, "sql");
		$this->sessionData['ult']  = length(isset($_SESSION['ml_login_time']) ? $_SESSION['ml_login_time'] : '', 16, NULL, "sql");
		$this->sessionData['sic']  = length(isset($_SESSION['ml_login_sic']) ? $_SESSION['ml_login_sic'] : '', 32, NULL, "sql");
		$this->sessionData['csrf'] = length(isset($_SESSION['ml_csrfToken']) ? $_SESSION['ml_csrfToken'] : '', 16, NULL, "sql");
		$this->sessionData['url'] = length(isset($_SESSION['ml_url']) ? $_SESSION['ml_url'] : '', 512, NULL, "sql");
		$this->sessionData['url_old'] = length(isset($_SESSION['ml_url_old']) ? $_SESSION['ml_url_old'] : '', 512, NULL, "sql");

		$url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		if($url != $this->sessionData['url']){
			$_SESSION['ml_url_old'] = $this->sessionData['url'];
			$this->sessionData['url_old'] = length(isset($_SESSION['ml_url_old']) ? $_SESSION['ml_url_old'] : '', 512, NULL, "sql");
			$this->sessionData['url'] = $url;
			$_SESSION['ml_url'] = $url;
		}
	}

	public function login_session(){
		if(!empty($this->sessionData['uik']) && !empty($this->sessionData['sic']) && !empty($this->sessionData['ulc']) && !empty($this->sessionData['ult']) && $this->sessionData['csrf']){
			$sql    = "Select * From ".Prefix."_user Where active = '1' AND uik = '".$_SESSION['ml_login_uik']."'";
			$result = $this->mysql->query($sql);
			// If user is found
			if($result->num_rows == 1){
				$sql = $this->mysql->query("Select * From ".Prefix."_sessions Where uik = '".$this->sessionData['uik']."' AND sic = '".$this->sessionData['sic']."' AND ult = '".$this->sessionData['ult']."' AND ulc = '".$this->sessionData['ulc']."' AND logout = '0' AND closed = '0'");
				if($sql->num_rows == 1){
					$sql = $this->mysql->query("Update ".Prefix."_sessions Set last_action = '".time()."' Where sic = '".$this->sessionData['sic']."'");
					return true;
				} else {
					return false;					
				}
			} else {
				return false;
			}
		} else {
			return false;
		}	
	}
	
	/*********************************************
	 * Eingeloggt bleiben / remember me
	 *********************************************/
	
	public function cookielogin(){
		$uik = length(isset($_COOKIE['ml_login_uik']) ? $_COOKIE['ml_login_uik'] : '', 32);
		$sic = length(isset($_COOKIE['ml_login_sic']) ? $_COOKIE['ml_login_sic'] : '', 32);
		$ult = length(isset($_COOKIE['ml_login_ult']) ? $_COOKIE['ml_login_ult'] : '', 16);
		$login_code = decodeRand(length(isset($_COOKIE['ml_login_ulc']) ? $_COOKIE['ml_login_ulc'] : '', 512));
		if(!self::login_session()){
			if(!empty($uik) && !empty($login_code) && !empty($sic) && !empty($ult)){
				if(database::getAmount('user', 'uik', $uik) == 1){
					if(database::getValue('user', 'uik', $uik, 'active') == 1){
						$sql = $this->mysql->query("Select * From ".Prefix."_sessions Where uik = '".$uik."' AND sic = '".$sic."' AND ult = '".$ult."' AND ulc = '".$login_code."' AND logout = '0' AND closed = '0'");
						if($sql->num_rows == 1){
							$_SESSION['ml_login_uik'] = $uik;
							$_SESSION['ml_login_code'] = $login_code;
							$_SESSION['ml_login_time'] = $ult;
							$_SESSION['ml_login_sic'] = $sic;
							$_SESSION['ml_csrfToken'] = uniqid('', true);
							self::session_data();
							global $p;
							$url = NULL;
							if(!empty($p)){
								global $sites;
								$userID = self::getUser('id', $uik, 'uik');
								$pID = NULL;
								$sql = $this->mysql->query("Select `id` From ".Prefix."_sites Where filename Like '$p.%' Limit 1");
								if($sql->num_rows == 1){
									$row = $sql->fetch_assoc();
									$pID = $row['id'];
								}
								if($sites->allowSite($pID, $userID)){
									$url = parse_url($this->sessionData['url'], PHP_URL_QUERY);
								}
							}
							header('Location: '.$_SERVER["SCRIPT_NAME"].'?'.$url);
							exit();
						}
					 }
				}
			}
		}
	}
	
	/*********************************************
	 * Gesperrt / locked screen
	 *********************************************/
	
	public function is_locked(){
		$sql = $this->mysql->query("Select `locked` From ".Prefix."_sessions Where uik = '".$this->sessionData['uik']."' AND sic = '".$this->sessionData['sic']."' AND ult = '".$this->sessionData['ult']."' AND ulc = '".$this->sessionData['ulc']."' AND logout = '0' AND closed = '0'");
		$row = $sql->fetch_assoc();
		if($row['locked'] === '1')
			return true;
		return false;
	}
	
	public function lock(){
		$csrf = length(isset($_GET['csrf']) ? $_GET['csrf'] : '', 16);
		if($csrf == $this->sessionData['csrf']){
			$url = parse_url($this->sessionData['url_old'], PHP_URL_QUERY);
			$sql = $this->mysql->query("Update ".Prefix."_sessions Set locked = '1', locked_dir = '".$url."' Where uik = '".$this->sessionData['uik']."' AND sic = '".$this->sessionData['sic']."' AND ult = '".$this->sessionData['ult']."' AND ulc = '".$this->sessionData['ulc']."' AND logout = '0' AND closed = '0'");
			if($sql === true){
				header('Location: '.$_SERVER["SCRIPT_NAME"]);
				exit();
			} else {
				$error = 'Fehler beim Sperren!';	
			}
		} else {
			$error = 'Ung&uuml;tiger CSRF Code!';	
		}
		return $error;
	}
	
	public function unlock(){
		$passwd = length(isset($_POST['locked-passwd']) ? $_POST['locked-passwd'] : '', 32);
		if(self::pwverify($passwd)){
			$sql = $this->mysql->query("Update ".Prefix."_sessions Set locked = '0' Where uik = '".$this->sessionData['uik']."' AND sic = '".$this->sessionData['sic']."' AND ult = '".$this->sessionData['ult']."' AND ulc = '".$this->sessionData['ulc']."' AND logout = '0' AND closed = '0'");
			if($sql === true){
				header('Location: '.$_SERVER["SCRIPT_NAME"].'?'.database::getValue('sessions', 'sic', $this->sessionData['sic'], 'locked_dir'));
				exit();
			} else {
				$error = 'Fehler beim Entsperren!';	
			}
		} else {
			$error = 'Du hast ein falsches Passwort eingegeben!';	
		}
		return $error;
	}
	
	/*********************************************
	 * Anmelden/Abmelden | login/logout
	 *********************************************/
	
	public function login(){
		$error = '';
		$email = length(isset($_POST['login-email']) ? $_POST['login-email'] : '', 64);	
		$passwd = length(isset($_POST['login-passwd']) ? $_POST['login-passwd'] : '', 64);	
		$remember = length(isset($_POST['login-remember']) ? $_POST['login-remember'] : 0, 1);
		if(!empty($email) && !empty($passwd)){
			$user = $this->mysql->query("SELECT `password`, `id`, `uik`, `active` FROM `".Prefix."_user` WHERE (`username` = '".$email."' OR `email` = '".$email."') LIMIT 0,1");
			$result = $user->fetch_assoc(); 
			if($user->num_rows == 1){
				if(self::pwverify($passwd, $result['password'])){
					if(password_needs_rehash($result['password'], PASSWORD_BCRYPT, $this->options)) { 
						$new_hash = self::password_hash($passwd, PASSWORD_BCRYPT, $this->options);  
						$this->mysql->query("Update `".Prefix."_user` Set `password` = '".$new_hash."' Where `id` = '".$result['id']."'");
					}
					if($result['active'] == 1){
						$lc = getCode(32, 'sessions', 'ulc'); // Generate Logincode
						$sic = getCode(32, 'sessions', 'sic'); // Generate Sessioncode
						$lt = time(); // Logintime
						$this->mysql->query("Insert Into `".Prefix."_sessions` (uik, sic, ult, ulc, last_action) Values ('".$result['uik']."', '$sic', '$lt', '$lc', '$lt')");
						$_SESSION['ml_login_uik'] = $result['uik'];
						$_SESSION['ml_login_code'] = $lc;
						$_SESSION['ml_login_time'] = $lt;
						$_SESSION['ml_login_sic'] = $sic;
						$_SESSION['ml_csrfToken'] = uniqid('', true);
						if($remember == 1){
							$lifetime = database::getMainData('cookielifetime');
							setcookie('ml_login_uik', $result['uik'], time() + $lifetime, '/');
							setcookie('ml_login_ulc', encodeRand($lc), time() + $lifetime, '/');
							setcookie('ml_login_sic', $sic, time() + $lifetime, '/');
							setcookie('ml_login_ult', $lt, time() + $lifetime, '/');
						}
						header('Location: '.$_SERVER["SCRIPT_NAME"]);
						exit();
					} else {
						$error = 'Dein Account ist nicht freigegeben! Bitte wende dich an den Webadministrator.';	
					}
				} else {
					$error = 'Du hast ein falsches Passwort oder eine falsche E-Mail Adresse / Benutzernamen eingegeben!';	
				}
			} else {
				$error = 'Du hast eine falsche E-Mail Adresse / Benutzernamen oder ein falsches Passwort eingegeben!';	
			}
		} else {
			$error = 'Du musst deine E-Mail und dein Passwort angeben!';
		}
		return $error;	
	}

	public function logout($system = false, $header = true){
		$csrf = length(isset($_GET['csrf']) ? $_GET['csrf'] : '', 16);
		if($csrf == $this->sessionData['csrf'] || $system){
			$sql = $this->mysql->query("Update ".Prefix."_sessions Set closed = '1', logout = '1' Where sic = '".$this->sessionData['sic']."'");
			// Destroy all Sessions and Cookies
			unset($_SESSION['ml_login_uik']);
			unset($_SESSION['ml_login_code']);
			unset($_SESSION['ml_login_time']);
			unset($_SESSION['ml_login_sic']);
			unset($_SESSION['ml_csrfToken']);
			setcookie('ml_login_uik', '', time() - 1, '/', NULL, true, true);
			setcookie('ml_login_ulc', '', time() - 1, '/', NULL, true, true);
			setcookie('ml_login_ult', '', time() - 1, '/', NULL, true, true);
			setcookie('ml_login_sic', '', time() - 1, '/', NULL, true, true);
			
			// Read Logout-Site
			$page = parent::getValue('sites', 'logout_site', '1', 'filename');
			if(parent::getValue('sites', 'filename', $page, 'start_site') == 1)
				$page = NULL;
			
			if(!empty($page))
				$page = '?p='.$page;
			
			if($header === true)
				header('Location: '.$_SERVER["SCRIPT_NAME"].$page);
		}
	}
	
	/*********************************************
	 * Passwort vergessen / password forget
	 *********************************************/
	
	public function password_forget(){
		$email = length(isset($_POST['email']) ? $_POST['email'] : NULL , 64);
		$captcha = length(isset($_POST['captcha']) ? $_POST['captcha'] : NULL , 64);
		if(database::getMainData('pwv_active') == 1){
			if(!empty($email) && check_email($email)){
				$captchaClass = new captcha();
				if($captchaClass->check_captcha($captcha)){
					if(database::getAmount('user', 'email', $email) == 1){
						$code = getCode(32, 'codes', 'code');
						$expiry = time() + 172800;
						$sql = $this->mysql->query("Insert Into ".Prefix."_codes (uik, code, action, expiry_date) Values ('".self::getUser('uik', $email, 'email')."', '$code', 'pwr', '$expiry')");
						if($sql === true){
							$data = array();
							$data["subject"] = "Dein Passwort zurücksetzen";
							$data["fullname"] = self::getUser('fullname', $email, 'email');
							$data["code"] = '?p=pw_reset&a='.$code;
							self::sendMail("password_forget.html", self::getUser('id', $email, 'email'), $data, $email);
							header('Location: '.$_SERVER["SCRIPT_NAME"].'?h=pwv_success');
							exit();
						} else {
							$error = 'Fehler beim erstellen des Links zum zur&uuml;cksetzen des Passwortes! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut. Der Administrator wurde &uuml;ber das Problem informiert.';	
							errormail('Fehler beim erstellen des Links zum zur&uuml;cksetzen des Passwortes! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);	
						}
					} else {
						$error = 'Es konnte kein Konto gefunden werden!';
					}
				} else {
					$error = 'Du hast einen falschen Sicherheitscode eingegeben!';
				}
			} else {
				$error = "Bitte gebe eine E-Mail Adresse ein!";
			}
		}
		return $error;
	}
	
	public function password_forget_reset(){
		global $a;
		$passwd = length(isset($_POST['pwr_passwd']) ? $_POST['pwr_passwd'] : NULL , 64);
		$passwd_confirm = length(isset($_POST['pwr_passwd_confirm']) ? $_POST['pwr_passwd_confirm'] : NULL , 64);
		$captcha = length(isset($_POST['pwr_captcha']) ? $_POST['pwr_captcha'] : NULL , 64);
		if(database::getMainData('pwv_active') == 1){
			if(!empty($passwd) && !empty($passwd_confirm)){
				$captchaClass = new captcha();
				if($captchaClass->check_captcha($captcha)){
					if(md5($passwd) == md5($passwd_confirm)){
						$pw_length = database::getMainData('password_length');
						if(strlen($passwd) >= $pw_length){
							if(database::getAmount('codes', array('action', 'code'), array('pwr', $a)) == 1){
								$new_passwd = self::pwhash($passwd);
								$uik = database::getValue('codes', array('action', 'code'), array('pwr', $a), 'uik');
								$sql = $this->mysql->query("Update ".Prefix."_user Set password = '$new_passwd' Where uik = '$uik'");
								if($sql === true){
									$data = array();
									$data["subject"] = "Dein Passwort zurückgesetzt";
									$data["fullname"] = self::getUser('fullname', $uik, 'uik');
									self::sendMail("password_forget_success.html", self::getUser('id', $uik, 'uik'), $data, $email);
									$this->mysql->query("Delete From ".Prefix."_codes Where code = '$a' Limit 1");
									header('Location: '.$_SERVER["SCRIPT_NAME"].'?h=pwr_success');
									exit();
								} else {
									$error = 'Fehler beim zur&uuml;cksetzen des Passwortes des Benutzers! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut. Der Administrator wurde &uuml;ber das Problem informiert.';	
									errormail('Fehler beim zur&uuml;cksetzen des Passwortes eines Benutzers! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);	
								}
							} else {
								$error = 'Dieser Link ist nicht g&uuml;tig!';
							}
						} else {
							$error = 'Dein Passwort ist zu kurz! Die Mindestl&auml;nge muss '.$pw_length.' betragen!';
						}
					} else {
						$error = 'Die Passw&ouml;rter stimmen nicht &uuml;berein!';
					}
				} else {
					$error = 'Du hast einen falschen Sicherheitscode eingegeben!';
				}
			} else {
				$error = "Bitte gebe die Passw&ouml;rter ein!";
			}
		}
		return $error;
	}
	
	
	/*********************************************
	 * Registrierung / registration
	 *********************************************/	
	
	public function regist(){
		global $additional_fields;
		$username = length(isset($_POST['regist-username']) ? $_POST['regist-username'] : NULL , 64);
		$fullname = length(isset($_POST['regist-fullname']) ? $_POST['regist-fullname'] : NULL , 64);
		$passwd   = length(isset($_POST['regist-passwd']) ? $_POST['regist-passwd'] : NULL , 64);
		$passwdco = length(isset($_POST['regist-passwd-confirm']) ? $_POST['regist-passwd-confirm'] : NULL , 64);
		$email    = length(isset($_POST['regist-email']) ? $_POST['regist-email'] : NULL , 64);
		$emailco  = length(isset($_POST['regist-email-confirm']) ? $_POST['regist-email-confirm'] : NULL , 64);
		$captcha  = length(isset($_POST['regist-captcha']) ? $_POST['regist-captcha'] : NULL , 64);
		$dsgvo    = length(isset($_POST['dsgvo']) ? $_POST['dsgvo'] : '', 1);
		$error    = NULL;
		
		if(database::getMainData('regist_active') == 1){
			if(!empty($username) && !empty($passwd) && !empty($passwdco) && !empty($email) && !empty($emailco) && !empty($captcha) && !empty($dsgvo)){
				$pw_length = database::getMainData('password_length');
				if(strlen($passwd) >= $pw_length){
					if(md5($passwd) == md5($passwdco)){
						if(check_email($email)){
							if(md5($email) == md5($emailco)){
								$captchaClass = new captcha();
								if($captchaClass->check_captcha($captcha)){
									if(empty($fullname) || preg_match("/^[\D\s]{2,128}+$/", $fullname)){
										if(!in_array(strtolower($username), $this->username_blacklist)){
											if(preg_match("/^[a-zA-Z0-9äöüÄÖÜß_-]{4,}+$/", $username)){
												if(database::getAmount('user', 'username', $username) == 0){
													if(database::getAmount('user', 'email', $email) == 0){
														$error = $additional_fields->setFieldValues(1, 'regist-', NULL, true);
														if(empty($error)){
															$defaultRank = database::getValue('ranks', '`default`', '1', 'id');
															$names = explode(' ', $fullname);
															$namesAmount = count($names);
															$last_name = ($namesAmount > 1) ? array_pop($names) : NULL;
															$first_name = implode(' ', $names);
															$password = self::pwhash($passwd);
															$uik = getCode(32, 'user', 'uik');
															$mode = database::getMainData('user_activation_mode');
															$user_active = ($mode == 0) ? 1 : 0;

															$sql = $this->mysql->query("Insert Into ".Prefix."_user (username, first_name, last_name, email, password, active, rank, uik, regdate) Values ('$username', '$first_name', '$last_name', '$email', '$password', '$user_active', '$defaultRank', '$uik', '".time()."')");
															if($sql === true){
																$code = NULL;
																if($mode == 1){
																	$code = getCode(32, 'codes', 'code');
																	$sql = $this->mysql->query("Insert Into ".Prefix."_codes (uik, code, action, expiry_date) Values ('$uik', '$code', 'activate', '0')");
																	$tpl = 'regist_link';
																} elseif($mode == 0)
																	$tpl = 'regist';
																else
																	$tpl = 'regist_admin';

																$error = $additional_fields->setFieldValues(1, 'regist-', self::getUser('id', $uik, 'uik'));
																$data = array();
																$data['subject'] = "Willkommen auf ".database::getMainData('site_title');
																$data['username'] = $username;
																$data['fullname'] = empty($fullname) ? '<keinen>' : $fullname;
																$data['email'] = $email;
																$data['password'] = $passwd;
																$data['code'] = '?v=activate&a='.$code;
																// sende E-Mail | send mail
																self::sendMail($tpl.".html", NULL, $data, $email);

																if($mode == 2){
																	$data['subject'] = database::getMainData('site_title').": Neue Registrierung";
																	$data['password'] = NULL;
																	self::sendMail("new_user.html", NULL, $data, database::getMainData('administrator_mail'));
																}

																header('Location: ?p=login&h=regist_'.$mode.'_successfully');
																exit();
															} else {
																$error = 'Fehler beim erstellen deines Accounts! Der zust&auml;ndige Admin wurde informiert, bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut.';
																errormail('Fehler beim erstellen eines Accounts! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
															}
														}
													} else {
														$error = 'Es existiert bereits ein Account mit dieser E-Mail Adresse!';
													}
												} else {
													$error = 'Es existiert bereits ein Account mit diesem Benutzernamen!';
												}
											} else {
												$error = 'Der eingegebe Benutzername erf&uuml;llt nicht unsere Kriterien! Nur Buchstaben von a-Z, Zahlen von 0-9, _ , - sind erlaubt und mindestens 4 Zeichen lang!';
											}
										} else {
											$error = 'Dieser Benutzername ist nicht erlaubt!';
										}
									} else {
										$error = 'Der eingebene Name ist nicht g&uuml;ltig!';
									}
								} else {
									$error = 'Du hast einen falschen Sicherheitscode eingegeben!';
								}
							} else {
								$error = 'Die E-Mail Adressen stimmen nicht &uuml;berein!';
							}
						} else {
							$error = 'Du hast eine fehlerhafte E-Mail Adresse eingegeben! Bitte &uuml;berpr&uuml;fe diese.';
						}
					} else {
						$error = 'Die Passw&ouml;rter stimmen nicht &uuml;berein!';
					}
				} else {
					$error = 'Das Passwort ist zu kurz! Mindestl&auml;nge: '.$pw_length.' Zeichen';
				}
			} else {
				$error = 'Es m&uuml;ssen alle Felder mit * ausgef&uuml;t werden!';
			}
		} else {
			$error = 'Du kannst dich leider zur Zeit nicht registrieren, da dies vom Administrator unterbunden wurde.';
		}
		
		return $error;
	}
	
	
	/*********************************************
	 * Benutzer de-/aktivieren / User de-/activate
	 *********************************************/
	
	public function activateUserSelf(){
		global $a;
		$error = NULL;
		
		if(!empty($a)){
			if(database::getAmount('codes', array('action', 'code'), array('activate', $a)) == 1){
				$sql = $this->mysql->query("Select * From ".Prefix."_codes Where action = 'activate' AND code = '$a' Limit 1");
				$row = $sql->fetch_assoc();
				$sql = $this->mysql->query("Update ".Prefix."_user Set active = '1' Where uik = '".$row['uik']."'");
				if($sql === true){
					$sql = $this->mysql->query("Delete From ".Prefix."_codes Where id = '".$row['id']."'");
					if($sql === false)
						errormail('Fehler beim entfernen der Aktivierung ID#'.$row['id'].' - Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
					
					header('Location: ?p=login&h=activate_successfully');
					exit();
				} else {
					$error = 'Fehler beim freischalten ihres Benutzerkontos.';
					errormail('Fehler beim Freischalten des Benutzerkontos: UIK#'.$row['uik'].' - Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
				}
			} else {
				$error = 'Aktivierung nicht m&ouml;glich!';
			}
		}
		
		return $error;
	}
	
	public function removeUserSelfLink(){
		global $a;
		$error = NULL;
		
		if(!empty($a)){
			if(database::getAmount('codes', array('action', 'code'), array('remove', $a)) == 1){
				$sql = $this->mysql->query("Select * From ".Prefix."_codes Where action = 'remove' AND code = '$a' Limit 1");
				$row = $sql->fetch_assoc();
				$sql = $this->mysql->query("Delete From ".Prefix."_user Where uik = '".$row['uik']."'");
				if($sql === true){			
					$this->mysql->query("Delete From ".Prefix."_sessions Where uik = '".$row['uik']."'");
					header('Location: ?p=home&h=user_remove_self_successfully');
					exit();
				} else {
					$error = 'Fehler beim l&ouml;schen ihres Benutzerkontos.';
					errormail('Fehler beim l&ouml;schen des Benutzerkontos: UIK#'.$row['uik'].' - Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
				}
			} else {
				$error = 'L&ouml;schung des Kontos nicht m&ouml;glich!';
			}
		}
		
		return $error;
	}
	
	private function autoCodeRemover(){
		$this->mysql->query("Delete From ".Prefix."_codes Where expiry_date != '0' AND expiry_date < '".time()."'");
	}
	
	
	/*********************************************
	 * Passwortgenerator / password generator
	 *********************************************/
	
	public function generatePassword($length = 9, $add_dashes = false, $available_sets = 'luds'){
		$sets = array();
		if(strpos($available_sets, 'l') !== false)
			$sets[] = 'abcdefghjkmnpqrstuvwxyz';
		if(strpos($available_sets, 'u') !== false)
			$sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
		if(strpos($available_sets, 'd') !== false)
			$sets[] = '23456789';
		if(strpos($available_sets, 's') !== false)
			$sets[] = '!@#$%&*?';
		$all = '';
		$password = '';
		foreach($sets as $set)
		{
			$password .= $set[array_rand(str_split($set))];
			$all .= $set;
		}
		$all = str_split($all);
		for($i = 0; $i < $length - count($sets); $i++)
			$password .= $all[array_rand($all)];
		$password = str_shuffle($password);
		if(!$add_dashes)
			return $password;
		$dash_len = floor(sqrt($length));
		$dash_str = '';
		while(strlen($password) > $dash_len)
		{
			$dash_str .= substr($password, 0, $dash_len) . '-';
			$password = substr($password, $dash_len);
		}
		$dash_str .= $password;
		return $dash_str;
	}
	
	/*********************************************
	 * Benutzerdaten auslesen / get user data
	 *********************************************/

	public function getUser($column = NULL, $value = NULL, $search_column = 'id', $array = false){
		if(!empty($column)){ // If don't empty the $column parameter?
			if($value == null && self::login_session()){
				$value = $this->sessionData['uik'];
				$search_column = 'uik';	
			} elseif($value == null && !self::login_session()) {
				$value = "3A2xdfRKw9l6IqptThiZSFXbT5J0oELO"; // Gast Account
				$search_column = 'uik';	
			}
			
			$specials = array("fullname");
			
			$search_column = empty($search_column) ? 'id' : $search_column;
			$sql = $this->mysql->query("Select * From `".Prefix."_user` Where `$search_column` = '$value'");
			if($sql->num_rows === 0){
				return "nicht gefunden";	
			} elseif($sql->num_rows > 1 || $array === true){
				return $sql->fetch_array();
			} else {
				$rtn = $sql->fetch_array();
				if(!in_array($column, $specials)){
					if(!array_key_exists($column, $rtn)){
						return "nicht gefunden";
					}
				} else {
					$rtn["fullname"] = $rtn["username"];
					if(!empty($rtn["first_name"]) || !empty($rtn["last_name"])){
						$rtn["fullname"] = !empty($rtn['first_name']) ? $rtn['first_name'] : '';
						$rtn["fullname"] = empty($rtn["fullname"]) ? $rtn['last_name'] : (!empty($rtn['last_name']) ? $rtn['fullname'].' '.$rtn['last_name'] : $rtn["fullname"]);
					}
						
				}
				return $rtn[$column];
			}
		} else {
			return NULL;
		}
		return NULL;
	}
	
	public function getData($column){
		$data = array();
		$data['csrfToken'] = $this->sessionData['csrf'];
		return array_key_exists($column, $data) ? $data[$column] : '';
	}

	/************************************
	 * Benutzerdaten aendern
	 ************************************/
	
	public function changePasswd(){
		$passwd 		= length(isset($_POST['password']) ? $_POST['password'] : '', 64);	
		$passwd_confirm = length(isset($_POST['password-confirm']) ? $_POST['password-confirm'] : '', 64);	
		$passwd_actual	= length(isset($_POST['password-actual']) ? $_POST['password-actual'] : '', 64);
		if(!empty($passwd) && !empty($passwd_confirm) && !empty($passwd_actual)){
			$actual_password = self::getUser('password');
			if(self::pwverify($passwd_actual, $actual_password)){
				if(md5($passwd) == md5($passwd_confirm)){
					if(strlen($passwd) >= 6){
						$sql = $this->mysql->query("Update ".Prefix."_user Set password = '".self::pwhash($passwd)."' Where id = '".self::getUser('id')."'");
						if($sql === true){
							// beende alle laufenden Sessions ausser dieser | exit all running sessions except this
							$sql = $this->mysql->query("Update ".Prefix."_sessions Set closed = '1' Where uik = '".self::getUser('uik')."' AND sic != '".$this->sessionData['sic']."'");
							
							if(parent::getMainData('restore') >= 1){
								$backup_code = getCode(32, 'changes', 'code');
								$data = array();
								$data["subject"] = "Dein Passwort wurde geändert!";
								$data["username"] = self::getUser('username');
								$data["changecode"] = '?v=restore&a='.$backup_code;

								$sql = $this->mysql->query("Insert Into ".Prefix."_changes (code, timestamp, coloum, value, author, user) Values ('$backup_code', '".time()."', 'password', '$actual_password', '".self::getUser('id')."', '".self::getUser('id')."')");
								self::sendMail("user_changed_pw.html", self::getUser('id'), $data, self::getUser('email'));
							}
							header('Location: ?p=profil&h=pwchange_success');
							exit();
						} else {
							$error = 'Fehler beim &auml;ndern des Passwortes! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut.';
							errormail('Fehler beim &auml;ndern eines Passwortes! Fehler in class loginsystem => function changePasswd() MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);	
						}
					} else {
						$error = 'Das Passwort muss mindestens 6 Zeichen haben!';	
					}
				} else {
					$error = 'Die neuen Passw&ouml;rter stimmen nicht &uuml;berein!';	
				}
			} else {
				$error = 'Du hast ein falsches Passwort eingegeben!';	
			}
		} else {
			$error = 'Bitte alle Felder ausf&uuml;llen!';
		}
		return $error;
	}
	
	public function changeEmail(){
		$old_email		= self::getUser('email');
		$email	 		= length(isset($_POST['email']) ? $_POST['email'] : '', 64);	
		$email_confirm	= length(isset($_POST['email-confirm']) ? $_POST['email-confirm'] : '', 64);	
		$passwd_actual	= length(isset($_POST['password-actual']) ? $_POST['password-actual'] : '', 64);
		if(!empty($email) && !empty($email_confirm) && !empty($passwd_actual)){
			if(self::pwverify($passwd_actual, self::getUser('password'))){
				if(md5($email) == md5($email_confirm)){
					if(check_email($email)){
						$sql = $this->mysql->query("Update ".Prefix."_user Set email = '".$email."' Where id = '".self::getUser('id')."'");
						if($sql === true){
							if(parent::getMainData('restore') >= 1){
								$backup_code = getCode(32, 'changes', 'code');
								$data = array();
								$data["subject"] = "Deine E-Mail Adresse wurde geändert!";
								$data["username"] = self::getUser('username');
								$data["changecode"] = '?v=restore&a='.$backup_code;
								$data["data"] = 'E-Mail Adresse: '.$old_email.' => '.$email;

								$sql = $this->mysql->query("Insert Into ".Prefix."_changes (code, timestamp, coloum, value, author, user) Values ('$backup_code', '".time()."', 'email', '$old_email', '".self::getUser('id')."', '".self::getUser('id')."')");
								self::sendMail("user_changed.html", self::getUser('id'), $data, self::getUser('email'));
								self::sendMail("user_changed.html", self::getUser('id'), $data, $old_email);
							}
							header('Location: ?p=profil&h=emailchange_success');
							exit();
						} else {
							$error = 'Fehler beim &auml;ndern der E-Mail Adresse! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut.';
							errormail('Fehler beim &auml;ndern einer E-Mail Adresse! Fehler in class loginsystem => function changeEmail() MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);	
						}
					} else {
						$error = 'Die E-Mail ist ung&uuml;tig!';	
					}
				} else {
					$error = 'Die neuen E-Mail Adressen stimmen nicht &uuml;berein!';	
				}
			} else {
				$error = 'Du hast ein falsches Passwort eingegeben!';	
			}
		} else {
			$error = 'Bitte alle Felder ausf&uuml;llen!';
		}
		return $error;
	}
	
	public function changeUserdata(){
		global $additional_fields;
		$old_username	= self::getUser('username');
		$username 		= length(isset($_POST['edit-username']) ? $_POST['edit-username'] : '', 64);	
		$fullname		= length(isset($_POST['edit-fullname']) ? $_POST['edit-fullname'] : '', 64);	
		$passwd_actual	= length(isset($_POST['password-actual']) ? $_POST['password-actual'] : '', 64);
		if(!empty($username) && !empty($passwd_actual)){
			if(self::pwverify($passwd_actual, self::getUser('password'))){
				$old_fullname = self::getUser('first_name').' '.self::getUser('last_name');
				if($username == $old_username || database::getAmount('user', 'username', $username) == 0){
					if(preg_match("#^[a-zA-Z0-9äöüÄÖÜß]{4,}+$#", $username)){
						if(!in_array(strtolower($username), $this->username_blacklist)){
							if(empty($fullname) || preg_match("#^[\D\s]{2,128}+$#", $fullname)){
								$names = explode(' ', $fullname);
								$namesAmount = count($names);
								$last_name = ($namesAmount > 1) ? array_pop($names) : NULL;
								$first_name = implode(' ', $names);

								$old_name = explode(' ', $old_fullname);
								$old_nameAmount = count($old_name);
								$old_last_name = ($old_nameAmount > 1) ? array_pop($old_name) : NULL;
								$old_first_name = implode(' ', $old_name);									

								$error = $additional_fields->setFieldValues(0, 'edit-', self::getUser('id'), true);
								if(empty($error)){
									$sql = $this->mysql->query("Update ".Prefix."_user Set username = '".$username."', first_name = '".$first_name."', last_name = '".$last_name."' Where id = '".self::getUser('id')."'");
									if($sql === true){
										$error = $additional_fields->setFieldValues(0, 'edit-', self::getUser('id'));
										if(parent::getMainData('restore') >= 1){
											$msg = 'Du hast deine Kontodaten ge&auml;ndert.';
											if($old_username != $username && $old_fullname == $fullname)
												$msg = 'Du hast dein Benutzernamen von "'.$old_username.'" auf "'.$username.'" ge&auml;ndert!';

											if($old_fullname != $fullname || $old_username != $username){										
												$backup_code = getCode(32, 'changes', 'code');
												$changed_data = array();
												if($old_username != $username){
													$sql = $this->mysql->query("Insert Into ".Prefix."_changes (code, timestamp, coloum, value, author, user) Values ('$backup_code', '".time()."', 'username', '$old_username', '".self::getUser('id')."', '".self::getUser('id')."')");
													$changed_data[] = 'Benutzername: '.$old_username.' => '.$username;
												}
												if($old_fullname != $fullname){
													$sql = $this->mysql->query("Insert Into ".Prefix."_changes (code, timestamp, coloum, value, author, user) Values ('$backup_code', '".time()."', 'first_name', '$old_first_name', '".self::getUser('id')."', '".self::getUser('id')."'),('$backup_code', '".time()."', 'last_name', '$old_last_name', '".self::getUser('id')."', '".self::getUser('id')."')");
													$changed_data[] = 'Vor-/Nachname: '.$old_fullname.' => '.$fullname;
												}

												$data = array();
												$data["subject"] = "Änderungen am Benutzerkonto!";
												$data["username"] = $old_username;
												$data["changecode"] = '?v=restore&a='.$backup_code;
												$data["data"] = implode('<br>', $changed_data);

												self::sendMail("user_changed.html", self::getUser('id'), $data, self::getUser('email'));
											}
										}

										header('Location: ?p=profil&h=datachange_success');
										exit();
									} else {
										$error = 'Fehler beim &auml;ndern der Benutzerdaten! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut.';
										errormail('Fehler beim &auml;ndern der Benutzerdaten! Fehler in class loginsystem => function changeUserdata() MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);	
									}
								}
							} else {
								$error = 'Du hast aber einen komsichen Namen?! Du hast bestimmt keine Zahlen oder Zeichen in deinem Namen, korrigiere dies bitte :) oder ist dein Name k&uuml;rzer als 6 Buchstaben? o.O Ich glaube nicht ;)';	
							}
						} else {
							$error = 'Dieser Benutzername ist nicht erlaubt!';
						}
					} else {
						$error = 'Der Benutzername enth&auml;lt ung&uuml;tige Zeichen oder ist zu kurz! <br>Erlaubte Zeichen:<br><ul><li>A-Z</li><li>a-z</li><li>0-9</li><li>mindestens 4 Zeichen</li></ul>';	
					}
				} else {
					$error = 'Dieser Benutzername ist bereits vergeben!';	
				}
			} else {
				$error = 'Du hast ein falsches Passwort eingegeben!';	
			}
		} else {
			$error = 'Bitte alle Felder ausf&uuml;llen!';
		}
		return $error;
	}
	
	/*********************************************
	 * Avatar ausgeben / get avatar
	 *********************************************/
	
	public function getUserAvatar($user = NULL){
		$rtn = NULL;
		$user = (database::getAmount('user', 'id', $user) == 1) ? $user : NULL;
		$userid = !empty($user) ? $user : self::getUser('id');
		$type   = self::getUser('avatar', $userid);
		if(file_exists($this->dirAvatar.$userid.'.'.$type)){
			$rtn = $this->dirAvatar.$userid.'.'.$type;
		}
		
		if(empty($rtn))
			$rtn = $this->dirAvatar.'default_avatar.'.parent::getMainData('default_avatar_type');
		return $rtn;
	}
	
	/*********************************************
	 * Avatar ändern / change avatar
	 *********************************************/
	
	public function setUserAvatar(){
		$error = NULL;
		$remove = length(isset($_POST['avatar-remove']) ? $_POST['avatar-remove'] : NULL, 6);
		$passwd = length(isset($_POST['password-actual']) ? $_POST['password-actual'] : NULL, 64);
		$userid = self::getUser('id');
		$type   = self::getUser('avatar');
		$avatar = self::getUserAvatar();
		
		if($remove == 'delete' || $_FILES["avatar-file"]["error"][0] == UPLOAD_ERR_OK){
			if(self::pwverify($passwd)){
				if($remove == 'delete'){
					if(file_exists($this->dirAvatar.$userid.'.'.$type)){
						unlink($this->dirAvatar.$userid.'.'.$type);
						header('Location: ?p=profil&h=remove_avatar_success');
						exit();
					} else {
						$error = 'Das standard Profilbild kann nicht entfernt werden!';	
					}
				} else { // Upload

					if(file_exists($this->dirAvatar.$userid.'.'.$type))
						unlink($this->dirAvatar.$userid.'.'.$type);

					$dir = $this->dirAvatar;
					$maxsize = return_bytes("1M"); // Gibt die maximale Dateigröße an in MB
					if($_FILES["avatar-file"]["error"] == UPLOAD_ERR_OK){
						// Holt sich alle Paramater zur Datei
						$filename = $_FILES["avatar-file"]['name'];
						$filemime = $_FILES["avatar-file"]['type'];
						$tmp_name = $_FILES["avatar-file"]['tmp_name'];
						$filesize = $_FILES["avatar-file"]['size'];

						// Verarbeitung der Parameter
						$newname	= $userid;
						$newname   .= strtolower(substr($filename, strrpos($filename, ".")));
						$size		= count_size($filesize);
						$filetype	= strtolower(str_replace('.', '', substr($filename, strrpos($filename, "."))));
						$blankname	= substr($filename, 0, strrpos($filename, "."));

						if($filesize <= $maxsize){
							if(in_array('image', explode('/', $filemime))){ // Prüft ob die Datei ein Bild ist
								if(move_uploaded_file($tmp_name, $dir.$newname)){ // Bild mit neuem Namen hochladen
									if($this->mysql->query("Update `".Prefix."_user` Set avatar = '$filetype' Where id = '$userid'")){
										header('Location: ?p=profil&h=set_new_avatar_success');
										exit();
									} else {
										errormail('Fehler beim speichern des Datentypes eines Profilbildes! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
										$error = 'Fehler beim speichern des Bildes! Bitte versuchen Sie es zu einem sp&auml;tern Zeitpunkt erneut.';
									}
								} else {
									errormail('Fehler beim hochladen eines Bildes! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()!');
									$error = 'Fehler beim Hochladen des Bildes! Bitte versuchen Sie es zu einem sp&auml;tern Zeitpunkt erneut.';
								} // END-if(move_uploaded_file)
							} else {
								$error = 'Die ausgew&auml;hlte Datei ist kein Bild!';
							}
						} else {
							$error = 'Die Datei '.$filename.' ist mit '.$size.' zu gro&szlig;! Die maximal zul&auml;ssige Dateigr&ouml;&szlig;e liegt bei: '.count_size($maxsize);	
						} // END-if($fielsize)
					} else {// END-if($error == UPLOAD_ERR_OK)
						$error = 'Fehler! Bild konnte nicht hochgeladen werden!';
					}
				}
			} else {
				$error = 'Du hast ein falsches Passwort eingegeben!';
			}
		} else {
			$error = 'Bitte eines der beiden Felder ausf&uuml;llen!';	
		}
		return $error;
	}
	
	public function removeUserSelf(){
		$passwd = length(isset($_POST['password-actual']) ? $_POST['password-actual'] : NULL, 64);
		$error = NULL;
		if(self::pwverify($passwd)){
			$code = getCode(32, 'codes', 'code');
			$expiry_time = strtotime("+14 days");
			$sql = $this->mysql->query("Insert Into ".Prefix."_codes (uik, code, action, expiry_date) Values ('".self::getUser('uik')."', '$code', 'remove', '$expiry_time')");
			if($sql !== false){
				$data = array();
				$data["subject"] = "Löschung deines Kontos!";
				$data["username"] = self::getUser('username');
				$data["removeLink"] = '?v=remove&a='.$code;
				
				self::sendMail("user_remove_self.html", self::getUser('id'), $data, self::getUser('email'));
				header('Location: ?p=profil&f=remove_self&h=remove_self_link');
				exit();
			} else {
				$error = 'Fehler beim erzeugen des Best&auml;tigungslinks! Bitte versuche es sp&auml;ter erneut.';
				errormail('Fehler beim erzeugen des Best&auml;tigungslinks! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
			}
		} else {
			$error = 'Du hast ein falsches Passwort eingegeben!';
		}
		return $error;
	}
	
	/************************************
	 * Benutzeradministration
	 ************************************/
	
	public function listAllUsers(){
		$rtn = NULL;
		$sql = $this->mysql->query("Select * From ".Prefix."_user Where uik != '3A2xdfRKw5k6IqptThiZSFXbT5J0oELO' AND uik != '3A2xdfRKw9l6IqptThiZSFXbT5J0oELO'");
		while($row = $sql->fetch_assoc()){
			$name = $row['first_name'].' '.$row['last_name'];
			
			$email = empty($row['email']) ? '-' : $row['email'];
			
			$btns = array();
			if(self::auditRight('user_show'))
				$btns[] = '<a href="?p=userlist&f=show&id='.$row['id'].'" title="Anzeigen"><i class="fa fa-fw fa-eye"></i></a>'; 
			
			if(self::auditRight('user_edit'))
				$btns[] = '<a href="?p=userlist&f=edit&id='.$row['id'].'" title="Bearbeiten"><i class="fa fa-fw fa-pencil"></i></a>';
			if($row['active'] == 1){
				if(self::auditRight('user_disable'))
					$btns[] = '<a href="?p=userlist&c=deactivate&id='.$row['id'].'" title="Deaktivieren"><i class="fa fa-fw fa-ban"></i></a>';
			} else {
				if(self::auditRight('user_enable'))
					$btns[] = '<a href="?p=userlist&c=activate&id='.$row['id'].'" title="Aktivieren"><i class="fa fa-fw fa-check"></i></a>';
			}
			
			if(self::auditRight('user_delete'))
				$btns[] = '<a href="?p=userlist&f=delete&id='.$row['id'].'" title="L&ouml;schen"><i class="fa fa-fw fa-trash"></i></a>';
			
			$btn = implode(' | ', $btns);

			
			$rtn .= '<tr>
				<td>#'.$row['id'].'</td>
				<td>'.$row['username'].'</td>
				<td>'.$name.'</td>
				<td>'.self::getRank($row['id'], true).'</td>
				<td>'.$row['email'].'</td>
				<td>'.$btn.'</td>
			</tr>';
		}
		
		return $rtn;
	}
	
	public function check_password($password){
		if(strlen($password) < database::getMainData('password_length'))
			return false;
		return true;
	}
	
	public function createUser(){
		global $additional_fields;
		$error = NULL;
		$username	= length(isset($_POST['new-username']) ? $_POST['new-username'] : '', 64);	
		$fullname	= length(isset($_POST['new-fullname']) ? $_POST['new-fullname'] : '', 64);	
		$email		= length(isset($_POST['new-email']) ? $_POST['new-email'] : '', 64);	
		$password	= length(isset($_POST['new-password']) ? $_POST['new-password'] : '', 64);	
		$password_confirm	= length(isset($_POST['new-password-confirm']) ? $_POST['new-password-confirm'] : '', 64);	
		$rank		= length(isset($_POST['new-rank']) ? $_POST['new-rank'] : '', 16);
		$sendmail	= length(isset($_POST['new-send-email']) ? $_POST['new-send-email'] : 0, 1);
		if(self::auditRight('user_add')){
			if(!empty($username) && !empty($email) && !empty($password) && !empty($password_confirm)){
				if(parent::getAmount('user', 'username', $username) == 0){
					if(parent::getAmount('user', 'email', $email) == 0){
						if(md5($password) == md5($password_confirm)){
							if(check_email($email)){
								if(!in_array(strtolower($username), $this->username_blacklist)){
									if(preg_match("#^[a-zA-Z0-9äöüÄÖÜß_-]{4,}+$#", $username)){
										if(empty($fullname) || preg_match("#^[\D\s]{2,128}+$#", $fullname)){
											if(self::check_password($password)){
												$error = $additional_fields->setFieldValues(0, 'new-', NULL, true);
												if(empty($error)){
													$rank = (parent::getAmount('ranks', 'id', $rank) == 1 && self::auditRight('user_rank')) ? $rank : parent::getValue('ranks', '`default`', '1', 'id');
													$password = self::pwhash($password);
													$uik = getCode(32, 'user', 'uik');

													$names = explode(' ', $fullname);
													$namesAmount = count($names);
													$last_name = ($namesAmount > 1) ? array_pop($names) : NULL;
													$first_name = implode(' ', $names);

													$sql = $this->mysql->query("Insert Into ".Prefix."_user (username, first_name, last_name, email, password, active, rank, uik, regdate) Values ('$username', '$first_name', '$last_name', '$email', '$password', '1', '$rank', '$uik', '".time()."')");
													if($sql === true){
														$id = parent::getValue('user', 'uik', $uik, 'id');
														$error = $additional_fields->setFieldValues(0, 'new-', $id);
														if($sendmail == 1){
															$data = array();
															$data["subject"] = "Willkommen auf ".parent::getMainData('site_title');
															$data["username"] = $username;
															$data["email"] = $email;
															$data["fullname"] = $fullname;
															$data["password"] = $password_confirm;

															self::sendMail("welcome.html", $id, $data, $email);
														}
														header('Location: ?p=userlist&h=create_user_successfully&id='.$id);
														exit();
													} else {
														$error = 'Fehler beim erstellen des Benutzers! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut. Der Administrator wurde &uuml;ber das Problem informiert.';
														errormail('Fehler beim erstellen eines Benutzers! Fehler in class loginsystem => function createUser() MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);	
													}
												}
											} else {
												$error = 'Das Passwort muss mindestens aus 6 Zeichen bestehen!';	
											}
										} else {
											$error = 'Du hast aber einen komsichen Namen?! Du hast bestimmt keine Zahlen oder Zeichen in deinem Namen, korrigiere dies bitte :) oder ist dein Name k&uuml;rzer als 6 Buchstaben? o.O Ich glaube nicht ;)';	
										}
									} else {
										$error = 'Der Benutzername enth&auml;lt ung&uuml;tige Zeichen oder ist zu kurz! <br>Erlaubte Zeichen:<br><ul><li>A-Z</li><li>a-z</li><li>0-9</li><li>mindestens 4 Zeichen</li></ul>';	
									}
								} else {
									$error = 'Der Benutzername ist nicht m&ouml;glich!';
								}
							} else {
								$error = 'Dei einegegebe E-Mail Adresse hat ein ung&uuml;ltiges Format! Bitte &uuml;berpr&uuml;fe die E-Mail Adresse!';	
							}
						} else {
							$error = 'Die Passw&ouml;rter stimmen nicht &uuml;berein! Bitte &uuml;berpr&uuml;fe diese!';	
						}
					} else {
						$error = 'Diese E-Mail Adresse wird bereit von einem anderen Benutzer verwendet! Bitte w&auml;hle eine andere!';	
					}
				} else {
					$error = 'Dieser Benutzername wird bereit von einem anderen Benutzer verwendet! Bitte w&auml;hle einen anderen!';	
				}
			} else {
				$error = 'Du musst alle Felder ausf&uuml;llen um einen Benutzer hinzuf&uuml;gen zu k&ouml;nnen!';
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion durchzuf&uuml;hren!';	
		}
		return $error;
	}
	
	public function editUser(){
		global $id;
		global $additional_fields;
		$error = NULL;
		if(self::auditRight('user_pwreset') && self::getUser('uik', $id) != '3A2xdfRKw5k6IqptThiZSFXbT5J0oELO'){
			if(parent::getAmount('user', 'id', $id) == 1){
				$username	= length(isset($_POST['edit-username']) ? $_POST['edit-username'] : '', 64);	
				$fullname	= length(isset($_POST['edit-fullname']) ? $_POST['edit-fullname'] : '', 64);	
				$email		= length(isset($_POST['edit-email']) ? $_POST['edit-email'] : '', 64);	
				$rank		= length(isset($_POST['edit-rank']) ? $_POST['edit-rank'] : '', 16);
				if(!empty($username) && !empty($fullname) && !empty($email)){
					if(parent::getAmount('user', array('email', '!id'), array($email, $id)) == 0){
						if(parent::getAmount('user', array('username', '!id'), array($username, $id)) == 0){
							if(check_email($email)){
								if(!in_array(strtolower($username), $this->username_blacklist)){
									if(preg_match("#^[a-zA-Z0-9äöüÄÖÜß]{4,}+$#", $username)){
										if(empty($fullname) || preg_match("#^[\D\s]{2,128}+$#", $fullname)){
											$error = $additional_fields->setFieldValues(0, 'edit-', $id);
											if(empty($error)){
												$old_username = self::getUser('username', $id);
												$old_email = self::getUser('email', $id);
												$old_fullname = self::getUser('first_name', $id).' '.self::getUser('last_name', $id);
												$old_rank = self::getUser('rank', $id);
												if(!self::auditRight('user_rank'))
													$rank = $old_rank;
												// Split name
												$names = explode(' ', $fullname);
												$namesAmount = count($names);
												$last_name = ($namesAmount > 1) ? array_pop($names) : NULL;
												$first_name = implode(' ', $names);

												$old_name = explode(' ', $old_fullname);
												$old_nameAmount = count($old_name);
												$old_last_name = ($old_nameAmount > 1) ? array_pop($old_name) : NULL;
												$old_first_name = implode(' ', $old_name);

												$sql = $this->mysql->query("Update ".Prefix."_user Set username = '$username', email = '$email', first_name = '$first_name', last_name = '$last_name', rank = '$rank' Where id = '$id'");	
												if($sql === true){
													if(parent::getMainData('restore') == 2){
														if($old_email != $email || $old_fullname != $fullname || $old_username != $username){

															$backup_code = getCode(32, 'changes', 'code');
															$changed_data = array();
															if($old_email != $email){
																$sql = $this->mysql->query("Insert Into ".Prefix."_changes (code, timestamp, coloum, value, author, user) Values ('$backup_code', '".time()."', 'email', '$old_email', '".self::getUser('id')."', '$id')");
																$changed_data[] = 'E-Mail Adresse: '.$old_email.' => '.$email;
															}
															if($old_username != $username){
																$sql = $this->mysql->query("Insert Into ".Prefix."_changes (code, timestamp, coloum, value, author, user) Values ('$backup_code', '".time()."', 'username', '$old_username', '".self::getUser('id')."', '$id')");
																$changed_data[] = 'Benutzername: '.$old_username.' => '.$username;
															}
															if($old_fullname != $fullname){
																$sql = $this->mysql->query("Insert Into ".Prefix."_changes (code, timestamp, coloum, value, author, user) Values ('$backup_code', '".time()."', 'first_name', '$old_first_name', '".self::getUser('id')."', '$id'),('$backup_code', '".time()."', 'last_name', '$old_last_name', '".self::getUser('id')."', '$id')");
																$changed_data[] = 'Vor-/Nachname: '.$old_fullname.' => '.$fullname;
															}
															if($old_rank != $rank){
																$changed_data[] = 'Rang: '.ranks($old_rank).' => '.ranks($rank);
															}

															$data = array();
															$data["subject"] = "Änderungen am Benutzerkonto!";
															$data["username"] = $old_username;
															$data["changecode"] = '?v=restore&a='.$backup_code;
															$data["admin"] = self::getUser('username');
															$data["data"] = implode('<br>', $changed_data);

															self::sendMail("user_edit.html", $id, $data, $old_email);
															if($old_email != $email)
																self::sendMail("user_edit.html", $id, $data, $email);
														}
													}
													header('Location: ?p=userlist&h=edit_user_successfully&id='.$id);
													exit();
												} else {
													$error = 'Fehler beim bearbeiten des Benutzers! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut. Der Administrator wurde &uuml;ber das Problem informiert.';
													errormail('Fehler beim bearbeiten eines Benutzers! Fehler in class loginsystem => function editUser() MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);	
												}
											}
										} else {
											$error = 'Der hat aber einen komsichen Namen?! Der hat bestimmt keine Zahlen oder Zeichen in seinem Namen, korrigiere dies bitte :) oder ist der Name k&uuml;rzer als 6 Buchstaben? o.O Ich glaube nicht ;)';	
										}
									} else {
										$error = 'Der Benutzername enth&auml;lt ung&uuml;tige Zeichen oder ist zu kurz! <br>Erlaubte Zeichen:<br><ul><li>A-Z</li><li>a-z</li><li>0-9</li><li>mindestens 4 Zeichen</li></ul>';	
									}
								} else {
									$error = 'Der Benutzername ist nicht m&ouml;glich!';
								}
							} else {
								$error = 'Dei einegegebe E-Mail Adresse hat ein ung&uuml;ltiges Format! Bitte &uuml;berpr&uuml;fe die E-Mail Adresse!';	
							}
						} else {
							$error = 'Dieser Benutzername wird bereit von einem anderen Benutzer verwendet! Bitte w&auml;hle einen anderen!';	
						}
					} else {
						$error = 'Diese E-Mail Adresse wird bereit von einem anderen Benutzer verwendet! Bitte w&auml;hle eine andere!';	
					}
				} else {
					$error = 'Es m&uuml;ssen alle Felder ausgef&uuml;llt sein!'; 	
				}
			} else {
				$error = 'Ung&uuml;ltiger Benutzer! Dieser Benutzer konnte nicht gefunden werden!';	
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion durchzuf&uuml;hren!';	
		}
		return $error;
	}
	
	public function resetUserPasswd(){
		global $id;
		$error = NULL;
		if(self::auditRight('user_pwreset') && self::getUser('uik', $id) != '3A2xdfRKw5k6IqptThiZSFXbT5J0oELO' && self::getUser('uik', $id) != '3A2xdfRKw9l6IqptThiZSFXbT5J0oELO'){
			if(database::getAmount('user', 'id', $id) == 1){
				$password = length(isset($_POST['reset-password']) ? $_POST['reset-password'] : '', 64);
				if(self::pwverify($password, self::getUser('password'))){
					$new_passwd = self::generatePassword();
					$new_passwd_hash = self::pwhash($new_passwd);
					$sql = $this->mysql->query("Update ".Prefix."_user Set password = '$new_passwd_hash' Where id = '$id'");
					if($sql === true){
						$data = array();
						$data["subject"] = "Dein Passwort wurde zurückgesetzt";
						$data["admin_username"] = self::getUser('username');
						$data["fullname"] = self::getUser('first_name', $id).' '.self::getUser('last_name', $id);
						$data["password"] = $new_passwd;
						self::sendMail("password_reset.html", $id, $data, self::getUser('email', $id));
						// beende alle laufenden Sessions ausser dieser | exit all running sessions except this
						$sql = $this->mysql->query("Update ".Prefix."_sessions Set closed = '1' Where uik = '".self::getUser('uik')."' AND sic != '".$this->sessionData['sic']."'");
						header('Location: ?p=userlist&h=reset_passwd_user_successfully&id='.$id);
						exit();
					} else {
						$error = 'Fehler beim zur&uuml;cksetzen des Passwortes des Benutzers! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut. Der Administrator wurde &uuml;ber das Problem informiert.';	
						errormail('Fehler beim zur&uuml;cksetzen des Passwortes eines Benutzers! Fehler in class loginsystem => function resetUserPasswd() MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);	
					}
				} else {
					$error = 'Du hast ein falsches Passwort einegegeben! Bitte gebe dein Passwort ein um den Vorgang abschlie&szlig;en zu k&ouml;nnen.';	
				}
			} else {
				$error = 'Ung&uuml;ltiger Benutzer! Dieser Benutzer konnte nicht gefunden werden!';	
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion durchzuf&uuml;hren!';	
		}
		return $error;
	}
	
	public function deleteUser(){
		global $id;
		$error = NULL;
		if(self::auditRight('user_delete') && self::getUser('uik', $id) != '3A2xdfRKw5k6IqptThiZSFXbT5J0oELO' && self::getUser('uik', $id) != '3A2xdfRKw9l6IqptThiZSFXbT5J0oELO'){
			if(database::getAmount('user', 'id', $id) == 1){
				$passwd = length(isset($_POST['delete-password']) ? $_POST['delete-password'] : '', 64);
				if(self::pwverify($passwd, self::getUser('password'))){
					if(self::getUser('id') != $id){
						$email = self::getUser('email', $id);
						$username = self::getUser('username', $id);
						$uik = self::getUser('uik', $id);
						$sql = $this->mysql->query("Delete From ".Prefix."_user Where id = '$id'");
						if($sql === true){
							$this->mysql->query("Delete From ".Prefix."_sessions Where uik = '$uik'");
							$this->mysql->query("Delete From ".Prefix."_additional_user_information Where user_id = '$id'");
							$this->mysql->query("Delete From ".Prefix."_changes Where user = '$id'");
							
							if(parent::getMainData('useradministration_share') == 1){
								$data = array();
								$data["subject"] = "Dein Konto wurde entfernt";
								$data["username"] = $username;
								$data["admin"] = self::getUser('username');
												
								self::sendMail("user_delete.html", $id, $data, $email);
							}
							header('Location: ?p=userlist&h=remove_user_successfully&a='.$username);
							exit();
						} else {
							$error = 'Fehler beim entfernen des Benutzers! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut. Der Administrator wurde &uuml;ber das Problem informiert.';	
							errormail('Fehler beim entfernen eines Benutzers! Fehler in class loginsystem => function deleteUser() MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);	
						}
					} else {
						$error = 'Du kannst nicht dein eigenes Benutzerkonto l&ouml;schen!';	
					}
				} else {
					$error = 'Du hast ein falsches Passwort eingegeben!';	
				}
			} else {
				$error = 'Ung&uuml;ltiger Benutzer! Dieser Benutzer konnte nicht gefunden werden!';	
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion durchzuf&uuml;hren!';	
		}
		return $error;
	}
	
	public function activateUser(){
		global $id;
		$error = NULL;
		if(self::auditRight('user_enable') && self::getUser('uik', $id) != '3A2xdfRKw5k6IqptThiZSFXbT5J0oELO' && self::getUser('uik', $id) != '3A2xdfRKw9l6IqptThiZSFXbT5J0oELO'){
			if(database::getAmount('user', 'id', $id) == 1){
				if(self::getUser('id') != $id){
					$sql = $this->mysql->query("Update ".Prefix."_user Set active = '1' Where id = '$id'");
					if($sql === true){
						if(parent::getMainData('useradministration_share') == 1){
							$data = array();
							$data["subject"] = "Dein Konto wurde freigeschaltet";
							$data["username"] = self::getUser('username', $id);
														
							self::sendMail("user_activate.html", $id, $data, self::getUser('email', $id));
						}
						header('Location: ?p=userlist&h=activate_user_successfully&id='.$id);
						exit();
					} else {
						$error = 'Fehler beim aktivieren des Benutzers! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut. Der Administrator wurde &uuml;ber das Problem informiert.';	
						errormail('Fehler beim aktivieren eines Benutzers! Fehler in class loginsystem => function activateUser() MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);	
					}
				} else {
					$error = 'Du kannst nicht dein eigenes Benutzerkonto aktivieren!';	
				}
			} else {
				$error = 'Ung&uuml;ltiger Benutzer! Dieser Benutzer konnte nicht gefunden werden!';	
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion durchzuf&uuml;hren!';	
		}
		return $error;
	}
	
	public function deactivateUser(){
		global $id;
		$error = NULL;
		if(self::auditRight('user_disable') && self::getUser('uik', $id) != '3A2xdfRKw5k6IqptThiZSFXbT5J0oELO'){
			if(database::getAmount('user', 'id', $id) == 1){
				if(self::getUser('id') != $id){
					$sql = $this->mysql->query("Update ".Prefix."_user Set active = '0' Where id = '$id'");
					if($sql === true){
						if(parent::getMainData('useradministration_share') == 1){
							$data = array();
							$data["subject"] = "Dein Konto wurde deaktiviert";
							$data["username"] = self::getUser('username', $id);
														
							self::sendMail("user_deactivate.html", $id, $data, self::getUser('email', $id));
						}
						header('Location: ?p=userlist&h=deactivate_user_successfully&id='.$id);
						exit();
					} else {
						$error = 'Fehler beim deaktivieren des Benutzers! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut. Der Administrator wurde &uuml;ber das Problem informiert.';	
						errormail('Fehler beim deaktivieren eines Benutzers! Fehler in class loginsystem => function deactivateUser() MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);	
					}
				} else {
					$error = 'Du kannst nicht dein eigenes Benutzerkonto deaktivieren!';	
				}
			} else {
				$error = 'Ung&uuml;ltiger Benutzer! Dieser Benutzer konnte nicht gefunden werden!';	
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion durchzuf&uuml;hren!';	
		}
		return $error;
	}

	public function rmUserAvatar(){
		global $id;
		$userid = $id;
		$error = NULL;
		#$avatar = self::getUserAvatar($userid);
		
		if(self::auditRight('user_rm_avatar') && self::getUser('uik', $id) != '3A2xdfRKw5k6IqptThiZSFXbT5J0oELO'){
			if(database::getAmount('user', 'id', $userid) == 1){
				$password = length(isset($_POST['reset-password']) ? $_POST['reset-password'] : '', 64);
				if(self::pwverify($password, self::getUser('password'))){
					$type   = self::getUser('avatar', $userid);
					if(file_exists($this->dirAvatar.$userid.'.'.$type)){
						unlink($this->dirAvatar.$userid.'.'.$type);
						header('Location: ?p=userlist&f=show&id='.$userid.'&h=rm_avatar_success');
						exit();
					} else {
						$error = 'Das standard Profilbild kann nicht entfernt werden!';	
					}
				} else {
					$error = 'Du hast ein falsches Passwort einegegeben! Bitte gebe dein Passwort ein um den Vorgang abschlie&szlig;en zu k&ouml;nnen.';	
				}
			} else {
				$error = 'Der Benutzer konnte nicht gefunden werden!';	
			}
		} else {
			$error = 'Du hast keine Berechtigung um diese Aktion durchzuf&uuml;hren!';	
		}
		return $error;
	}
	
	public function restore(){
		global $a;
		if(!empty($a)){
			$sql = $this->mysql->query("Select * From ".Prefix."_changes Where code = '$a' AND changed = '0'");
			if($sql->num_rows > 0){
				$row = $sql->fetch_assoc();
				$time = $row['timestamp'];
				if($time >= (time() - (86400 * 7))){
					$changes = array();
					$sql = $this->mysql->query("Select * From ".Prefix."_changes Where code = '$a' AND changed = '0'");
					while($row = $sql->fetch_assoc()){
						$changes[$row['coloum']][0] = self::getUser($row['coloum'], $row['user']);
						$changes[$row['coloum']][1] = $row['value'];
						$sql_edit = $this->mysql->query("Update ".Prefix."_user Set ".$row['coloum']." = '".$row['value']."' Where id = '".$row['user']."'");
						$sql_edit = $this->mysql->query("Update ".Prefix."_changes Set changed = '".time()."' Where id = '".$row['id']."'");
						$user = $row['user'];
					}
					if(empty($this->mysql->error)){
						
						if(array_key_exists('first_name', $changes)){
							$changes['fullname'][0] = $changes['first_name'][0].' '.$changes['last_name'][0];
							$changes['fullname'][1] = $changes['first_name'][1].' '.$changes['last_name'][1];
							unset($changes['first_name']);
							unset($changes['last_name']);
						}
						$changed = array();
						foreach($changes as $key => $val){
							$changed[$key] = $val[0].' => '.$val[1];
						}
						
						$data = array();
						$data["subject"] = "Wiederherstellung der Benutzerdaten!";
						$data["username"] = self::getUser('username', $user);
						$data["data"] = implode('<br>', $changed);
							
						self::sendMail("user_restore.html", $user, $data, self::getUser('email', $user));
						if(array_key_exists('email', $changes))
							self::sendMail("user_restore.html", $user, $data, $changes['email'][0]);
						header('Location: ?v=restore&h=restore_successfully');
						exit();
					} else {
						$error = 'Fehler beim wiederherstellen der Daten! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut. Der Administrator wurde &uuml;ber das Problem informiert.';	
						errormail('Fehler beim wiederherstellen der Daten! Fehler in class loginsystem => function restore() MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);	
					}
				} else {
					$error = 'Keine Wiederherstellung mehr m&ouml;glich! Da die &Auml;nderung mehr als 7 Tage zur&uuml;ck liegt!';
				}
			} else {
				$error = 'Es konnte leider keine Wiederherstellung gefunden werden!';	
			}
		} else {
			$error = 'Fehler! Keine Wiederherstellung gefunden!';	
		}
		return $error;
	}
	
	/************************************
	 * Rang- und Rechteverwaltung
	 ************************************/
	
	public function auditRight($rule, $userID = NULL){
		/*****
		 * Prueft ob der (aktuelle oder $userID) Benutzer
		 * das Recht "$rule" besitzt
		 *****/
		$userID = empty($userID) ? self::getUser('id'): $userID;
		if(database::getAmount('user', 'id', $userID) != 1)
			return false;
		$rank = self::getUser('rank', $userID);
		$rules = database::getValue('ranks', 'id', $rank, 'rules');
		if($rules == 'all')
			return true;
		$rules = explode(',', $rules);
		
		if(in_array($rule, $rules))
			return true;
		return false;
	}
	
	public function getRank($userID = NULL, $style = false, $string = NULL, $rankID = false){
		/*****
		 * Gibt den Rangnamen des gewaehlten Benutzers zurueck
		 * $userID => Von welchem Benutzer soll der Rang zurueck gegeben werden?
		 *            Wenn NULL: wird der aktuell angemeldete Benutzer verwendet
		 * $style => false: Rangname/String wird ohne Styleeinstellungen ausgegeben
		 *            true: Rangname/String wird mit eingestellten Styleeinstellungen zurueckgegeben
		 * $string => Wird ein String uebergeben, dann wird dieser Statt dem Rangnamen mit dem Style versehen und zurueckgegeben
		 * $rankID => false: BenutzerID wird verwendet
		 *             true: Statt bei $userID die ID des Benutzers zu uebergeben, wird nun die ID des Rangs erwartet
		 *****/
		if($rankID === false){
			$userID = empty($userID) ? self::getUser('id') : $userID;
			if(database::getAmount('user', 'id', $userID) != 1)
				return 'Benutzer #'.$userID.' nicht gefunden...';
			$rank = self::getUser('rank', $userID);
		} else {
			$rank = $userID;	
		}
		
		if(database::getAmount('ranks', 'id', $rank) != 1)
			return 'Rang #'.$rank.' nicht gefunden...';
		
		$rankData = database::getValue('ranks', 'id', $rank);
		$color = $rankData['color'];
		$special = explode(',', $rankData['special']);
		$name = $rankData['title'];
		$rtn = empty($string) ? $name : $string;
		if($style === true){
			if(!empty($color)) $rtn = '<span style="color: #'.$color.'">'.$rtn.'</span>';
			foreach($special as $sp){
				if($sp == 'bold') $rtn = '<strong>'.$rtn.'</strong>';
				if($sp == 'italic') $rtn = '<em>'.$rtn.'</em>';
				if($sp == 'underline') $rtn = '<u>'.$rtn.'</u>';
			}	
		}
		return $rtn;	
	}
	
	public function getRankList(){
		$return = NULL;
		$count = 1;
		$sql = $this->mysql->query("Select * From ".Prefix."_ranks Order by pos ASC");
		while($row = $sql->fetch_assoc()){
			$options = array();
			$options[] = '<a href="?p=ranks&f=view_rank&id='.$row['id'].'" title="ansehen"><i class="fa fa-eye"></i></a>';

			if(self::auditRight('rank_edit'))
				$options[] = '<a href="?p=ranks&f=edit_rank&id='.$row['id'].'" title="bearbeiten"><i class="fa fa-pencil"></i></a>';
			
			if(self::auditRight('rank_move') && $row['pos'] != '0'){
				if($count <= 2 || $sql->num_rows == $count){
					$options[] = '<a class="text-muted"><i class="fa fa-arrow-up"></i></a>';
				} else {
					$options[] = '<a href="?p=ranks&c=move_rank_up&id='.$row['id'].'"><i class="fa fa-arrow-up"></i></a>';
				}
				if($sql->num_rows - 1 <= $count){
					$options[] = '<a class="text-muted"><i class="fa fa-arrow-down"></i></a>';
				} else {
					$options[] = '<a href="/?p=ranks&c=move_rank_down&id='.$row['id'].'" ><i class="fa fa-arrow-down"></i></a>';			
				}				
			}
			
			if(self::auditRight('rank_default') && $row['default'] == '0' && $row['guest'] == '0' && $row['pos'] != '0')
				$options[] = '<a href="?p=ranks&c=default_rank&id='.$row['id'].'" title="Als Standard Rang setzen"><i class="fa fa-bookmark"></i></a>';
			if(self::auditRight('rank_delete') && $row['pos'] != '0' && $row['guest'] == '0' && $row['default'] == '0')
				$options[] = '<a href="?p=ranks&f=delete_rank&id='.$row['id'].'" title="l&ouml;schen"><i class="fa fa-trash"></i></a>';
				
			$icon = ($row['default'] == '1') ? '<i class="fa fa-bookmark" title="Standard Rang"></i>' : NULL;
			$icon = ($row['guest'] == '1') ? '<i class="fa fa-user" title="Rang f&uuml;r G&auml;ste (Nicht-User)"></i>' : $icon;
			$return .= '<tr>
				<td class="text-center">#'.$count.'</td>
				<td class="text-center">'.database::getAmount('user', 'rank', $row['id']).'</td>
				<td>'.self::getRank($row['id'], true, NULL, true).'</td>
				<td class="text-center">'.$icon.'</td>
				<td>'.implode(' | ', $options).'</td>
			</tr>';
			$count++;
		}
		
		if($sql->num_rows == 0)
			return 'Keine R&auml;nge gefunden...';
		
		return $return;
	}
	
	public function getRankOptions($sid = NULL, $without = array()){
		$outp = NULL;
		$sql = $this->mysql->query("Select * From ".Prefix."_ranks Where `guest` = '0' Order by pos");
		while($row = $sql->fetch_array()){
			$sel = NULL;
			if(($row['id'] == $sid && !empty($sid)) || (empty($sid)) && $row['default'] == 1) $sel = 'selected ';
			if(!in_array($row['id'], $without))
				$outp .= '<option '.$sel.'value="'.$row['id'].'">'.$row['title'].'</option>';
		}
		return $outp;
	}
	
	public function getRuleOptions($sid = NULL){
		$outp = NULL;
		$urules	= explode(',', str_replace(' ', '', database::getValue('ranks', 'id', self::getUser('rank'), 'rules')));
		if(!is_array($sid))
			$sid = explode(',', str_replace(' ', '', $sid));
		$sql = $this->mysql->query("Select * From ".Prefix."_rules Order by tag");
		while($row = $sql->fetch_array()){
			$sel = NULL;
			if(in_array($row['tag'], $urules) || $urules[0] == 'all'){
				if(in_array($row['tag'], $sid)) $sel = 'selected ';
				$outp .= '<option '.$sel.'value="'.$row['tag'].'">'.$row['name'].'</option>';
			}
		}
		return $outp;
	}
	
	public function getSiteOptions($sid = NULL){
		$outp = NULL;
		$usites	= explode(',', str_replace(' ', '', database::getValue('ranks', 'id', self::getUser('rank'), 'sites')));
		if(!is_array($sid))
			$sid = explode(',', str_replace(' ', '', $sid));
		$sql = $this->mysql->query("Select * From ".Prefix."_sites Where title != '404' Order by filename");
		while($row = $sql->fetch_array()){
			$sel = NULL;
			if(in_array($row['id'], $usites) || $usites[0] == 'all'){
				if(in_array($row['id'], $sid)) $sel = 'selected ';
				$outp .= '<option '.$sel.'value="'.$row['id'].'">'.$row['title'].'</option>';
			}
		}
		$sql = $this->mysql->query("Select * From ".Prefix."_menu Where url != '' || (url = '' AND sid = '0') Order by title");
		while($row = $sql->fetch_array()){
			$sel = NULL;
			if(in_array('m'.$row['id'], $usites) || $usites[0] == 'all'){
				if(in_array('m'.$row['id'], $sid)) $sel = 'selected ';
				$ext = '- P.-D.-M.';
				if(!empty($row['url']))
					$ext = '- Ext. Link';
				if($row['link_type'] == '1'){
					$ext .= ' !Nur G&auml;ste';
				}
				
				if(empty($row['title']))
					$ext = $row['icon'].' '.$ext;
				$outp .= '<option '.$sel.'value="m'.$row['id'].'">M - '.$row['title'].$ext.'</option>';
			}
		}
		return $outp;
	}
	
	public function newRank(){
		$name	= length(isset($_POST['name']) ? $_POST['name'] : '', 32);
		$site	= isset($_POST['sites']) ? $_POST['sites'] : array();
		$rule	= isset($_POST['rules']) ? $_POST['rules'] : array();
		$pos	= length(isset($_POST['position']) ? $_POST['position'] : '', 40);
		$sites	= NULL;
		$rules	= NULL;
		$usites	= explode(',', str_replace(' ', '', database::getValue('ranks', 'id', self::getUser('rank'), 'sites')));
		$urules	= explode(',', str_replace(' ', '', database::getValue('ranks', 'id', self::getUser('rank'), 'rules')));
		
		if($usites[0] == 'all'){
			$usites	= $site;
		}
		
		if($urules[0] == 'all'){
			$urules	= $rule;
		}		
		
		if(self::auditRight('rank_new')){
			if(strlen($name) >= 3 && !empty($pos)){
			
				if(count($site) != 0){
				  foreach($site as $key => $values){
					 if(in_array($values, $usites)){
						 $sites[] = $values;
					 }
				  }
				  $sites = implode(',', $sites);
				}
				
				if(count($rule) != 0){
				  foreach($rule as $key => $values){
					 if(in_array($values, $urules)){
						 $rules[] =  $values;
					 }
				  }	
				  $rules = implode(',', $rules);
				}
								
				$pos = database::getValue('ranks', 'id', $pos, 'pos') + 1;
				$this->mysql->query("Update ".Prefix."_ranks Set pos = pos + 1 Where pos >= '".$pos."'");
				
				$sql = $this->mysql->query("Insert Into ".Prefix."_ranks (`title`, `sites`, `rules`, `pos`) Values ('$name', '$sites', '$rules', '$pos')");
				if($sql){
					header('Location: ?p=ranks&h=new_rank_successfully');
					exit();
				} else {
					$error = 'Fehler beim hinzuf&uuml;gen des Ranges! Bitte versuchen Sie es sp&auml;ter erneut.';
					errormail('Fehler beim erstellen eines Ranges! Fehler in class loginsystem => function "newRank()". MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
				}
			} else {
				$error = 'Der Titel des Rangs muss mindestens 3 Zeichen beinhalten und eine Position muss gew&auml;hlt sein!';
			}
		} else {
			$error = 'Sie haben keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		return $error;
	}
	
	public function setRank(){
		global $id;
		$name	= length(isset($_POST['name']) ? $_POST['name'] : '', 32);
		$site	= isset($_POST['sites']) ? $_POST['sites'] : array();
		$rule	= isset($_POST['rules']) ? $_POST['rules'] : array();
		$color	= length(isset($_POST['color']) ? $_POST['color'] : '', 7);
		$colorc	= length(isset($_POST['colorcheck']) ? $_POST['colorcheck'] : '', 1);
		$special= isset($_POST['specials']) ? $_POST['specials'] : array();
		$sites	= NULL;
		$rules	= NULL;
		$usites	= explode(',', str_replace(' ', '', database::getValue('ranks', 'id', self::getUser('rank'), 'sites')));
		$urules	= explode(',', str_replace(' ', '', database::getValue('ranks', 'id', self::getUser('rank'), 'rules')));

		
		if($usites[0] == 'all'){
			$usites	= $site;
		}
		
		if($urules[0] == 'all'){
			$urules	= $rule;
		}		
		
		if(self::auditRight('rank_edit')){
			if(strlen($name) >= 3){
				if($id == '1813201541'){
					$sites = "all";
					$rules = "all";
				} else {
					if(count($site) != 0){
					  $sites = array();
					  foreach($site as $key => $values){
						 if(in_array($values, $usites)){
							 $sites[] = $values;
						 }
					  }
					  $sites = implode(',', $sites);
					}
					
					if(count($rule) != 0){
					  $rules = array();
					  foreach($rule as $key => $values){
						 if(in_array($values, $urules)){
							 $rules[] =  $values;
						 }
					  }	
					  $rules = implode(',', $rules);
					}
				}
				$color = empty($colorc) ? NULL : str_replace('#', '', $color);
				$special = implode(',', $special);
				$sql = $this->mysql->query("Update ".Prefix."_ranks Set title = '$name', sites = '$sites', rules = '$rules', color = '$color', special = '$special' Where id = '$id'");
				if($sql === true){
					header('Location: ?p=ranks&h=edit_rank_successfully');
					exit();
				} else {
					$error = 'Fehler beim speichern der &Auml;nderungen! Bitte versuche es sp&auml;ter erneut.';
					errormail('Fehler beim speichern einer &Auml;nderung eines Rangs! Fehler in class loginsystem => function setRank. MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
				}
			} else {
				$error = 'Der Titel des Rangs muss mindestens 3 Zeichen beinhalten!';	
			}
		} else {
			$error = 'Sie haben keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		return $error;
	}
	
	public function moveRank($direction = "up"){
		global $id;
		if(self::auditRight('rank_move') && $id != "1813201541"){
			$pos = database::getValue('ranks', 'id', $id, 'pos');
			$guest = database::getValue('ranks', 'id', $id, 'guest');
			if($direction == "up"){	
				$pmdown = database::getValue('ranks', 'pos', $pos - 1, 'id');		
				if($pos >= 2 && $id != "1813201542" && $pmdown != "1813201541"){
					$update = $this->mysql->query("Update ".Prefix."_ranks Set pos = pos - 1 Where id = '$id'");
					$update2 = $this->mysql->query("Update ".Prefix."_ranks Set pos = pos + 1 Where id = '$pmdown'");
				} else {
					$error = 'Diese Aktion ist nicht m&ouml;glich!';	
				}
			} else {
				$pmup = database::getValue('ranks', 'pos', $pos + 1, 'id');
				if($pmup != "1813201542" && $id != "1813201542"){
					$update = $this->mysql->query("Update ".Prefix."_ranks Set pos = pos + 1 Where id = '$id'");
					$update2 = $this->mysql->query("Update ".Prefix."_ranks Set pos = pos - 1 Where id = '$pmup'");
				} else {
					$error = 'Diese Aktion ist nicht m&ouml;glich!';	
				}
			}
			if($update && $update2){
				header('Location: ?p=ranks&h=move_rank_successfully');
				exit();
			} else {
				$error = 'Fehler beim &auml;ndern der Position! Bitte versuchen Sie es zu einem sp&auml;teren Zeitpunkt erneut.';
				errormail('Fehler beim &auml;ndern der Position eines Ranges! Fehler in class loginsystem => function "rank_move_up()". MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
			}
		} else {
			$error = 'Sie haben keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		return $error;
	}
	
	public function removeRank(){
		global $id;
		$pos = database::getValue('ranks', 'id', $id, 'pos');
		if(self::auditRight('rank_delete')){
			$user = database::getAmount('user', 'rank', $id);
			$rank = length(isset($_POST['rank']) ? $_POST['rank'] : '', 16);
			$passwd = length(isset($_POST['passwd']) ? $_POST['passwd'] : '', 64);
			if(self::pwverify($passwd, self::getUser('password'))){
				$def = database::getValue('ranks', 'id', $id, 'default');
				if(($user > 0 && !empty($rank) && $rank != $id && $rank != "1813201541" && $def == "0") || $user == 0){
					if($user > 0)
						$this->mysql->query("Update ".Prefix."_user Set rank = '$rank' Where rank = '$id'");
					
					$sql = $this->mysql->query("Delete From ".Prefix."_ranks Where id = '$id'");
					$sql2 = $this->mysql->query("Update ".Prefix."_ranks Set pos = pos - 1 Where pos >= '$pos'");
					if($sql === true && $sql2 == true){
						header('Location: ?p=ranks&h=remove_rank_successfully');
						exit();
					} else {
						$error = 'Fehler beim l&ouml;schen des Rangs! Bitte verusuche es zu einem sp&auml;teren Zeitpunkt erneut.';	
						errormail('Fehler beim l&ouml;schen eines Rangs! Fehler in class loginsystem => function "removeRank()". MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
					}
				} else {
					$error = 'Wenn diesem Rang noch Benutzer zugeordnet seind, m&uuml;ssen diese neu zugeordnet werden! Den Rang zum neu zuordnern ist ung&uuml;tig!';
				}
			} else {
				$error = 'Falsches Passwort eingegeben! Bitte versuche es erneut!';
			}
		} else {
			$error = 'Sie haben keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		return $error;
	}
	
	public function setSpecialRank(){
		global $id;
		
		if(self::auditRight('rank_edit')){
			if(database::getValue('ranks', 'id', $id, 'default') == "0" && $id != '1813201542' && $id != '1813201541'){
				$sql = $this->mysql->query("Update ".Prefix."_ranks Set `default` = '0' Where `default` = '1'");
				$sql2 = $this->mysql->query("Update ".Prefix."_ranks Set `default` = '1' Where id = '$id'");
				if($sql === true && $sql2 === true){
					header('Location: ?p=ranks&h=special_rank_successfully');
					exit();
				} else {
					$error = 'Fehler beim setzen des Standard-Rangs! Bitte versuche es sp&auml;ter erneut.';
					errormail('Fehler beim setzen des Standard-Ranges! Fehler in class loginsystem => function "setSpecialRank()". MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
				}
			} else {
				$error = 'Dieser Rang kann nicht als Standard-Rang definiert werden oder ist bereits ein Standard-Rang!';	
			}
		} else {
			$error = 'Sie haben keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		return $error;
	}
	
	/************************************
	 * Haupteinstellungen
	 ************************************/
	
	public function mainSettings(){
		$title		= length(isset($_POST['title']) ? $_POST['title'] : NULL, 64);
		$title_short= length(isset($_POST['title_short']) ? $_POST['title_short'] : NULL, 16);
		$email		= length(isset($_POST['email']) ? $_POST['email'] : NULL, 128);
		$sender		= length(isset($_POST['from']) ? $_POST['from'] : NULL, 128);
		$to			= length(isset($_POST['to']) ? $_POST['to'] : NULL, 128);
		$user_share	= length(isset($_POST['useradministration_share']) ? $_POST['useradministration_share'] : NULL, 1);
		$restore	= length(isset($_POST['restore']) ? $_POST['restore'] : NULL, 1);
		$pwlength	= length(isset($_POST['pwlength']) ? $_POST['pwlength'] : NULL, 2);
		$cookielife	= length(isset($_POST['cookie_lifetime']) ? $_POST['cookie_lifetime'] : 90*86400, 16);
		$regist_ac	= length(isset($_POST['regist_ac']) ? $_POST['regist_ac'] : 0, 1);
		$pwv_ac		= length(isset($_POST['pwv_ac']) ? $_POST['pwv_ac'] : 0, 1);
		$dsgvo   	= length(isset($_POST['dsgvo']) ? $_POST['dsgvo'] : '', 128);
		$impress	= length(isset($_POST['impressum_info']) ? $_POST['impressum_info'] : null, 4096, null, "sql");
		$imp_cont	= length(isset($_POST['impressum_content']) ? $_POST['impressum_content'] : null, 9999999, null, "none");
		$privacy	= length(isset($_POST['privacy_policy']) ? $_POST['privacy_policy'] : null, 9999999, null, "none");
		if(self::auditRight('mainsave')){
			if(!empty($title) && !empty($title_short) && !empty($email) && !empty($sender) && !empty($to) && !empty($dsgvo) && !empty($restore) && !empty($pwlength) && $pwlength >= 3 && $pwlength < 65){
				
				$default_avatar_type = parent::getMainData('default_avatar_type');
				$dir = $this->dirAvatar;
				$maxsize = return_bytes("1M"); // Gibt die maximale Dateigröße an in MB
				if($_FILES["avatar-file"]["error"] == UPLOAD_ERR_OK){
					// Holt sich alle Paramater zur Datei
					$filename = $_FILES["avatar-file"]['name'];
					$filemime = $_FILES["avatar-file"]['type'];
					$tmp_name = $_FILES["avatar-file"]['tmp_name'];
					$filesize = $_FILES["avatar-file"]['size'];
					// Verarbeitung der Parameter
					$newname	= 'default_avatar';
					$newname   .= strtolower(substr($filename, strrpos($filename, ".")));
					$size		= count_size($filesize);
					$filetype	= strtolower(str_replace('.', '', substr($filename, strrpos($filename, "."))));
					if($filesize <= $maxsize){
						if(in_array('image', explode('/', $filemime))){ // Prüft ob die Datei ein Bild ist
							if(move_uploaded_file($tmp_name, $dir.$newname)){ // Bild mit neuem Namen hochladen
								$default_avatar_type = $filetype;
							} else {
								errormail('Fehler beim hochladen eines Bildes! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()!');
								$error = 'Fehler beim Hochladen des Bildes! Bitte versuchen Sie es zu einem sp&auml;tern Zeitpunkt erneut.';
							} // END-if(move_uploaded_file)
						} else {
							$error = 'Die ausgew&auml;hlte Datei ist kein Bild!';
						}
					} else {
						$error = 'Die Datei '.$filename.' ist mit '.$size.' zu gro&szlig;! Die maximal zul&auml;ssige Dateigr&ouml;&szlig;e liegt bei: '.count_size($maxsize);	
					} // END-if($fielsize)
				}
				
				// Save Impressum & Privacy Policy
				file_put_contents('./system/tpl/impressum.tpl', $imp_cont);
				file_put_contents('./system/tpl/privacy_policy.tpl', $privacy);
				
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
				$update['default_avatar_type'] = $default_avatar_type;
				$update['dsgvo_email'] = $dsgvo;
				$update['impressum_info'] = $impress;
				
				foreach($update as $key => $val){
					$sql = $this->mysql->query("Update ".Prefix."_main Set value = '$val' Where tag = '$key'");
					if($sql === false)
						break;
				}
				if($sql === true){
					header('Location: ?p=settings&h=settings_saved');
					exit();
				} else {
					$error = 'Fehler beim speichern der Einstellungen! Bitte versuche es zu einem sp&auml;teren Zeitpunkt erneut.';
					errormail('Fehler beim speichern der Einstellungen! Fehler in class loginsystem => function "mainSettings()". MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
				}
			} else {
				$error = 'Es m&uuml;ssen alle Felder ausgef&uuml;t werden!';	
			}
		} else {
			$error = 'Sie haben keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		return $error;
	}

	/************************************
	 * Impressum und Datenschutz ausgeben
	 ************************************/
	
	public function getImpressum(){
		return file_get_contents('./system/tpl/impressum.tpl');
	}
	
	public function getPrivacyPolicy(){
		return file_get_contents('./system/tpl/privacy_policy.tpl');
	}

	/************************************
	 * E-Mails versenden
	 ************************************/

	public function sendMail($tpl, $userID = NULL, array $data, $to = NULL, $rplto = NULL, $header_file = 'header.tpl', $footer_file = 'footer.tpl'){
		/***********************
		 * $tpl => Datei mit dem gewuenschtem Inhalt
		 * $userID => UserID uebergeben (noch keine Verwendung)
		 * $data => Das "data" Array enthaelt alle Variablen Werte welche in dem E-Mail Template ersetzt werden sollen.
		 *          Der "key" enthaelt den Variablennamen und das "value" enthaelt den zu ersetzenden Wert.
		 * $to => Empfaenger der E-Mail (E-Mail Adresse)
		 * $rplto => E-Mail zurueck zu (E-Mail Adresse)
		 * $header_file => Abweichende Header(Kopf)-Datei angeben (Aus dem Verzeichnis "./emailtpl/")
		 * $footer_file => Abweichende Footer(Fuss)-Datei angeben (Aus dem Verzeichnis "./emailtpl/")
		 ***********************/
		$tpl_dir = './emailtpl/';
		$tpl_file = $tpl_dir.$tpl;
		$tpl_header_file = $tpl_dir.$header_file;
		$tpl_footer_file = $tpl_dir.$footer_file;
		$content = file_get_contents($tpl_header_file);
		$content .= file_get_contents($tpl_file);
		$content .= file_get_contents($tpl_footer_file);
		
		// Replacement
		foreach($data as $key => $val){
			$content = str_replace('['.$key.']', $val, $content);
		}
		
		$link = getCurrentUrl();
		$content = str_replace('[link]', $link, $content);
		
		$content = str_replace('[title]', parent::getMainData('site_title'), $content);
		$message = str_replace('[adminmail]', parent::getMainData('administrator_mail'), $content);
		
		$to = empty($to) ? parent::getMainData('administrator_mail') : $to;
		$rplto = empty($rplto) ? parent::getMainData('mail_receiver') : $rplto;

 	    $mime_boundary = "-----=" . md5(uniqid(microtime(), true));

	    $header  = "From: ".parent::getMainData('site_title')." <".parent::getMainData('mail_sender').">"."\r\n";
	    $header .= "Reply-To: ".$rplto."\r\n";
	    $header .= "MIME-Version: 1.0\r\n";
	    $header .= "Content-Type: multipart/mixed;\r\n";
	    $header .= " boundary=\"".$mime_boundary."\"\r\n";
 
	    $encoding = mb_detect_encoding($message, "utf-8, iso-8859-1, cp-1252");
	    $content = "This is a multi-part message in MIME format.\r\n\r\n";
	    $content.= "--".$mime_boundary."\r\n";
	    $content.= "Content-Type: text/html; charset=\"$encoding\"\r\n";
	    $content.= "Content-Transfer-Encoding: 8bit\r\n\r\n";
	    $content.= $message."\r\n";
	    $content .= "--".$mime_boundary."--"; 

		
		mail($to, "=?UTF-8?B?".base64_encode($data['subject'])."?=", $content, $header);
	}
}
















?>