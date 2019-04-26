<?php
/********************************************
* 
* System:   EASY 2.0 Loginsystem
* Author:   Marius Rasche (aka Marlight)
* Class:    sites
* File:     sites.php
* FVersion: 0.3.5.2 (this file)
* SVersion: 0.9.7 BETA (complete System)
* Date:     11.03.2018
*
* Created by www.marlight-systems.de
* Copyright by Marlight Systems (www.marlight-systems.de)
* All rights reserved.
* 
*********************************************/

class sites extends loginsystem{
	protected $mysql;
	protected $main_path = './templates/';
	
	public function __construct(){
		parent::__construct();	
	}
	
	public function allowSite($site = NULL, $userID = NULL){
		/*************************
		 * $site = ID der zu pruefenden Seite | ID from site which you will check
		 * $user = Fur welchen Benutzer geprueft werden soll
		 *************************/
		$userrank = parent::getUser('rank', $userID);
		$allowedSites = parent::getValue('ranks', 'id', $userrank, 'sites');
		// Sind alle erlaubt? (Webadmin Rechte)
		if($allowedSites == "all")
			return true;
		
		$allowedSitesArray = explode(',', $allowedSites);
		if(parent::getMainData('regist_active') == 0){
			if(($key = array_search(14, $allowedSitesArray)) !== false)
				unset($allowedSitesArray[$key]);
				
		}
		if(parent::getMainData('pwv_active') == 0){
			if(($key = array_search(13, $allowedSitesArray)) !== false)
				unset($allowedSitesArray[$key]);
				
		}
		if(in_array($site, $allowedSitesArray))
			return true;
		
		return false;
	}
	
	public function getSite($val = NULL, $id = NULL){
		$query = $val;
		$query = ($query == 'url') ? 'filename' : $query;
		if($val == 'complete_filename'){
			$rtn = parent::getValue('sites', 'id', $id, 'filename').'.'.parent::getValue('sites', 'id', $id, 'type');
		} else {
			$rtn = parent::getValue('sites', 'id', $id, $query);
		}
		return $rtn;
	}
	
	public function getSiteName(){
		global $p;
		$sitename = parent::getMainData('short_site_title');
		if(!empty($p)){
			$sql = $this->mysql->query("Select * From ".Prefix."_sites Where filename Like '$p' Limit 1");
			if($sql->num_rows == 1){
				$row = $sql->fetch_assoc();
				return $row['title'].' &bull; '.$sitename;
			}
		}
		return $sitename;
	}

	public function includeSite($pre = false){
		global $p;
		$errorsite = parent::getValue('sites', 'errorsite', '1', NULL, true);
		
		if(!empty($p)){
			$sql = $this->mysql->query("Select * From ".Prefix."_sites Where filename Like '$p' Limit 1");
			if($sql->num_rows == 1){
				$row = $sql->fetch_assoc();
				if(self::allowSite($row['id']) == true){
					if(file_exists($this->main_path.$row['dir'].$row['filename'].'.'.$row['type']) && is_readable($this->main_path.$row['dir'].$row['filename'].'.'.$row['type'])){
						$incl = $row['dir'].$row['filename'].'.'.$row['type'];
					} else {
						$incl = $errorsite['dir'].$errorsite['filename'].'.'.$errorsite['type'];
					}
				} else {
					$incl = $errorsite['dir'].$errorsite['filename'].'.'.$errorsite['type'];
				}
			} else {
				$incl = $errorsite['dir'].$errorsite['filename'].'.'.$errorsite['type'];
			}
		} else {
			if(parent::login_session() == true){
				$startsite = parent::getValue('sites', 'start_site_login', '1', NULL, true);
				$incl = $startsite['dir'].$startsite['filename'].'.'.$startsite['type'];
			} else {
				$startsite = parent::getValue('sites', 'start_site', '1', NULL, true);
				$incl = $startsite['dir'].$startsite['filename'].'.'.$startsite['type'];
			}
		}
		
		// Datei einbinden | Including file
		if(file_exists($this->main_path.$incl) && is_readable($this->main_path.$incl)){
			$file_array = explode('.', $incl);
			$filetype = array_pop($file_array);
			$allow_types = array("html", "htm", "xhtml", "php", "tpl", "txt");
			if(in_array($filetype, $allow_types) && $pre == false){
				return $this->main_path.$incl;
			} elseif($filetype == "pdf" && $pre == true) { // Binde PDF Dateien ein | Include PDF
				header("Content-Type: application/pdf");   
				header("Cache-Control: no-cache, must-revalidate");
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
				readfile($this->main_path.$incl);
				exit();
			}
		} else {
			if($pre == false)
				echo '++++++++++ Fehler beim einbinden der Datei! ++++++++++';
		}
		
	}
	
	public function listSites(){
		$rtn = NULL;
		
		$sql = $this->mysql->query("Select * From ".Prefix."_sites Order by dir ASC, filename ASC");
		while($row = $sql->fetch_assoc()){
			$startsite = NULL;
			if($row['start_site'] == '1') $startsite .= '<i class="fa fa-home" title="Startseite"></i> ';
			if($row['start_site_login'] == '1') $startsite .= '<i class="fa fa-lock" title="Startseite nach dem Login"></i> ';
			if($row['errorsite'] == '1') $startsite .= '<i class="fa fa-flash" title="Fehlerseite"></i> ';
			if($row['logout_site'] == '1') $startsite .= '<i class="fa fa-power-off" title="Logoutseite"></i> ';
			
			$btn = array();
			if(parent::auditRight('site_edit')) $btn[] = '<a href="?p=sites&f=check&id='.$row['id'].'" title="Informationen"><i class="fa fa-info-circle"></i></a>';
			if(parent::auditRight('site_edit')) $btn[] = '<a href="?p=sites&c=download&id='.$row['id'].'" title="Download"><i class="fa fa-download"></i></a>';
			if(parent::auditRight('site_edit')) $btn[] = '<a href="?p=sites&f=edit&id='.$row['id'].'" title="Bearbeiten"><i class="fa fa-pencil"></i></a>';
			if(parent::auditRight('site_remove')) $btn[] = '<a href="?p=sites&f=remove&id='.$row['id'].'" title="Entfernen"><i class="fa fa-trash"></i></a>';
			
			
			$rtn .= '<tr>
				<td>'.$row['id'].'</td>
				<td>'.$row['title'].'</td>
				<td><a href="?p='.$row['filename'].'">./'.$row['dir'].$row['filename'].'.'.$row['type'].'</a></td>
				<td>'.$startsite.'</td>
				<td>'.implode(' | ', $btn).'</td>
			</tr>';
		}
		
		if($sql->num_rows == 0)
			$rtn = '<tr><td class="text-center" colspan="4"><em>Keine Seiten vorhanden...</em></td></tr>';
		
		return $rtn;
	}
	
	public function addSite(){
		$title 		 = length(isset($_POST['title']) ? $_POST['title'] : NULL, 32);
		$filename 	 = length(isset($_POST['filename']) ? $_POST['filename'] : NULL, 64);
		$dir		 = length(isset($_POST['dir']) ? $_POST['dir'] : NULL, 128);
		$start		 = length(isset($_POST['start_site']) ? $_POST['start_site'] : 0, 1);
		$start_login = length(isset($_POST['start_site_login']) ? $_POST['start_site_login'] : 0, 1);
		$errorsite	 = length(isset($_POST['error_site']) ? $_POST['error_site'] : 0, 1);
		$logoutsite	 = length(isset($_POST['logout_site']) ? $_POST['logout_site'] : 0, 1);
		$createsite	 = length(isset($_POST['create_site']) ? $_POST['create_site'] : 0, 1);
		$file_check  = length(isset($_POST['file_check']) ? $_POST['file_check'] : 0, 1);
		if(parent::auditRight('site_add')){
			if(!empty("title") && !empty("filename")){
				if(check_filename($filename)){
					if(empty($dir) || preg_match('/^[a-zA-Z0-9_\-]{1}[a-zA-Z0-9_\-\/]{1,126}[\/]{1}$/', $dir)){ // Check Dir
						$filearray = explode('.', $filename);
						$type = array_pop($filearray);
						$filename = implode('.', $filearray);
						if(parent::getAmount('sites', 'filename', $filename) == 0){
							if($file_check == 0 || $createsite == 1 || file_exists($this->main_path.$dir.$filename.'.'.$type)){
								if($start == 1){
									// Reset old startsite | Setze alte Startseite zurueck
									$this->mysql->query("Update ".Prefix."_sites Set start_site = '0' Where start_site = '1'");
								}
								if($start_login == 1){
									// Reset old startsite after login | Setze alte Startseite nach dem Login zurueck
									$this->mysql->query("Update ".Prefix."_sites Set start_site_login = '0' Where start_site_login = '1'");
								}
								if($errorsite == 1){
									$this->mysql->query("Update ".Prefix."_sites Set errorsite = '0' Where errorsite = '1'");
								}
								if($logoutsite == 1){
									$this->mysql->query("Update ".Prefix."_sites Set logout_site = '0' Where logout_site = '1'");
								}

								$sql = $this->mysql->query("Insert Into ".Prefix."_sites (`title`,`filename`,`dir`,`start_site`,`start_site_login`, `errorsite`, `type`, `logout_site`) Values ('$title', '$filename', '$dir', '$start', '$start_login', '$errorsite', '$type', '$logoutsite')");
								if($sql === true){
									if($createsite == 1 && !file_exists($this->main_path.$dir.$filename.'.'.$type)){
										if(!is_dir($this->main_path.$dir))
											mkdir($this->main_path.$dir, 0755);
										$search = array("{SITENAME}");
										$replace = array($title);
										$tpl = file_get_contents('./system/tpl/default_site.tpl');
										$tpl = str_replace($search, $replace, $tpl);
										file_put_contents($this->main_path.$dir.$filename.'.'.$type, $tpl);
									} elseif($_FILES["file"]["error"] == UPLOAD_ERR_OK){
										// Holt sich alle Paramater zur Datei
										$tmp_name = $_FILES["file"]['tmp_name'];
										
										if(file_exists($this->main_path.$dir.$filename.'.'.$type)){
											unlink($this->main_path.$dir.$filename.'.'.$type);
										}

										if(!move_uploaded_file($tmp_name, $this->main_path.$dir.$filename.'.'.$type)){ // Datei mit neuem Namen hochladen
											errormail('Fehler beim hochladen einer Datei! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()!');
											header('Location: ?p=sites&h=site_add_successfully_upl_error');
											exit();
										} // END-if(move_uploaded_file)
									}

									header('Location: ?p=sites&h=site_add_successfully');
									exit();
								} else {
									$error = 'Fehler beim hinzuf&uuml;gen der Seite!';
									errormail('Fehler beim hinzuf&uuml;gen einer Seite! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
								}
							} else {
								$error = 'Die Datei oder das Verzeichnis exsistieren nicht!';
							}
						} else {
							$error = 'F&uuml;r diese Datei liegt bereits ein Eintrag vor!';
						}
					} else {
						$error = 'Das Verzeichnis hat ein ung&uuml;ltiges Format! Beispiel f&uuml;r eine korrekte Einagbe: "test/" oder "test1/test2/"';
					}
				} else {
					$error = 'Dies ist ein ung&uuml;ltiger Dateiname!';
				}
			} else {
				$error = 'Es muss mindestens ein Seitentitel und ein Dateiname eingegeben werden!';
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		return $error;
	}
	
	public function editSite(){
		global $id;
		$title 		 = length(isset($_POST['title']) ? $_POST['title'] : NULL, 32);
		$filename 	 = length(isset($_POST['filename']) ? $_POST['filename'] : NULL, 64);
		$dir		 = length(isset($_POST['dir']) ? $_POST['dir'] : NULL, 128);
		$start		 = length(isset($_POST['start_site']) ? $_POST['start_site'] : 0, 1);
		$start_login = length(isset($_POST['start_site_login']) ? $_POST['start_site_login'] : 0, 1);
		$errorsite	 = length(isset($_POST['error_site']) ? $_POST['error_site'] : 0, 1);
		$logoutsite	 = length(isset($_POST['logout_site']) ? $_POST['logout_site'] : 0, 1);
		$createsite	 = length(isset($_POST['create_site']) ? $_POST['create_site'] : 0, 1);
		$file_check  = length(isset($_POST['file_check']) ? $_POST['file_check'] : 0, 1);
		if(parent::auditRight('site_edit')){
			if(!empty("title") && !empty("filename")){
				if(check_filename($filename)){
					if(empty($dir) || preg_match('/^[a-zA-Z0-9_\-]{1}[a-zA-Z0-9_\-\/]{1,126}[\/]{1}$/', $dir)){ // Check dir
						$filearray = explode('.', $filename);
						$type = array_pop($filearray);
						$filename = implode('.', $filearray);
						if(parent::getAmount('sites', array('filename', '!id'), array($filename, $id)) == 0){
							if($file_check == 0 || $createsite == 1 || file_exists($this->main_path.$dir.$filename.'.'.$type)){
								if(!empty($id) && parent::getAmount('sites', 'id', $id) == 1){
									if($start == 1){
										// Reset old startsite | Setze alte Startseite zurueck
										$this->mysql->query("Update ".Prefix."_sites Set start_site = '0' Where start_site = '1'");
									}
									if($start_login == 1){
										// Reset old startsite after login | Setze alte Startseite nach dem Login zurueck
										$this->mysql->query("Update ".Prefix."_sites Set start_site_login = '0' Where start_site_login = '1'");
									}
									if($errorsite == 1){
										$this->mysql->query("Update ".Prefix."_sites Set errorsite = '0' Where errorsite = '1'");
									}
									if($logoutsite == 1){
										$this->mysql->query("Update ".Prefix."_sites Set logout_site = '0' Where logout_site = '1'");
									}

									$sql = $this->mysql->query("Update ".Prefix."_sites Set title = '$title', filename = '$filename', dir = '$dir', start_site = '$start', start_site_login = '$start_login', logout_site = '$logoutsite', errorsite = '$errorsite', type = '$type' Where id = '$id'");
									if($sql === true){
										if($createsite == 1 && !file_exists($this->main_path.$dir.$filename.'.'.$type)){
											if(!is_dir($this->main_path.$dir))
												mkdir($this->main_path.$dir, 0755);
											$search = array("{SITENAME}");
											$replace = array($title);
											$tpl = file_get_contents('./system/tpl/default_site.tpl');
											$tpl = str_replace($search, $replace, $tpl);
											file_put_contents($this->main_path.$dir.$filename.'.'.$type, $tpl);
										} elseif($_FILES["file"]["error"] == UPLOAD_ERR_OK){
											// Holt sich alle Paramater zur Datei
											$tmp_name = $_FILES["file"]['tmp_name'];

											if(file_exists($this->main_path.$dir.$filename.'.'.$type)){
												unlink($this->main_path.$dir.$filename.'.'.$type);
											}

											if(!move_uploaded_file($tmp_name, $this->main_path.$dir.$filename.'.'.$type)){ // Datei mit neuem Namen hochladen
												errormail('Fehler beim hochladen einer Datei! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()!');
												header('Location: ?p=sites&h=site_edit_successfully_upl_error');
												exit();
											} // END-if(move_uploaded_file)
										}
										
										header('Location: ?p=sites&h=site_edit_successfully');
										exit();
									} else {
										$error = 'Fehler beim bearbeiten der Seite!';
										errormail('Fehler beim bearbeiten einer Seite! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
									}
								} else {
									$error = 'Fehler! Es wurde keine g&uuml;ltige Id &uuml;bergeben!';
								}
							} else {
								$error = 'Die Datei oder das Verzeichnis exsistieren nicht!';
							}
						} else {
							$error = 'F&uuml;r diese Datei liegt bereits ein Eintrag vor!';
						}
					} else {
						$error = 'Das Verzeichnis hat ein ung&uuml;ltiges Format! Beispiel f&uuml;r eine korrekte Einagbe: "test/" oder "test1/test2/"';
					}
				} else {
					$error = 'Dies ist ein ung&uuml;ltiger Dateiname!';
				}
			} else {
				$error = 'Es muss mindestens ein Seitentitel und ein Dateiname eingegeben werden!';
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		return $error;
	}
	
	private function delTree($dir) { // Loescht einen ganzen Verzeichnis-Baum
		if($dir != $this->main_path && $dir != './' && $dir != '' && $dir != $this->main_path.'/'){
			$files = array_diff(scandir($dir), array('.','..')); 
			foreach ($files as $file) { 
				is_dir("$dir/$file") ? delTree("$dir/$file") : unlink("$dir/$file"); 
			} 
			return rmdir($dir); 
		}
		return false;
	}
	
	public function removeSite(){
		global $id;
		$error = '';
		$delete_file = length(isset($_POST['delete_file']) ? $_POST['delete_file'] : 0, 1);
		$delete_dir  = length(isset($_POST['delete_dir']) ? $_POST['delete_dir'] : 0, 1);
		if(parent::auditRight('site_remove')){
			$filename = self::getSite('complete_filename', $id);
			$dir	  = parent::getValue('sites', 'id', $id, 'dir');
			if(!empty($id) && parent::getAmount('sites', 'id', $id) == 1){
				if(parent::getAmount('sites', array('start_site', 'start_site_login', 'id'), array('0', '0', $id)) == 1){
					$sql = $this->mysql->query("Delete From ".Prefix."_sites Where id = '$id'");
					if($sql === true){
						if($delete_file == 1){
							if(file_exists($this->main_path.$dir.$filename))
								unlink($this->main_path.$dir.$filename);
							
							if($delete_dir == 1 && !empty($dir)){
								delTree($this->main_path.$dir);
							}
						}
						header('Location: ?p=sites&h=site_remove_successfully');
						exit();
					} else {
						$error = 'Fehler beim entfernen der Seite!';
						errormail('Fehler beim entfernen einer Seite! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
					}
				} else {
					$error = 'Eine Startseite kann nicht entfernt werden! Setze eine andere Seite als Startseite um diese Seite entfernen zu k&ouml;nnen!';
				}
			} else {
				$error = 'Fehler! Es wurde keine g&uuml;ltige Id &uuml;bergeben!';
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		return $error;
	}
	
	public function downloadSite(){
		global $id;
		$error = NULL;
		
		if(parent::auditRight('site_edit')){
			if(parent::getAmount('sites', 'id', $id) == 1){
				$filename = self::getSite('complete_filename', $id);
				$filepath = $this->main_path.self::getSite('dir', $id);
				$file = $filepath.$filename;
				$mimetyp = filetype($file);
				
				set_time_limit(0);
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private",false);
				header("Content-Type: ".$mimetyp);
				header("Content-Disposition: attachment; filename=".$filename);
				header("Content-Length: ".filesize($file));

				readfile($file);
			} else {
				$error = 'Diese Seite konnte nicht gefunden werden!';
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		return $error;
	}
}



















?>