<?php
/********************************************
* 
* System:   EASY 2.0 Loginsystem
* Author:   Marius Rasche (aka Marlight)
* Class:    menu
* File:     menu.php
* FVersion: 0.4.5 (this file)
* SVersion: 0.9.7 BETA (complete System)
* Date:     04.03.2018
*
* Created by www.marlight-systems.de
* Copyright by Marlight Systems (www.marlight-systems.de)
* All rights reserved.
* 
*********************************************/

class menu extends sites{
	protected $mysql;
	protected $main_path = './system/tpl/menu/';
	
	public function __construct(){
		parent::__construct();	
	}
	
	// Gibt Menue aus
	public function getMenu($menu = 1, $tpl_dir = 'default'){
		$rtn = array();
		global $p;
		
		// Load TPL
		$tpl_link = file_get_contents($this->main_path.$tpl_dir.'/menu_point.tpl');
		$tpl_drop = file_get_contents($this->main_path.$tpl_dir.'/menu_dropdown_point.tpl');
		
		$sql = $this->mysql->query("Select * From ".Prefix."_menu Where menu = '$menu' AND under = '0' Order by `pos` ASC, `title` ASC");
		while($row = $sql->fetch_assoc()){
			$page = NULL;
			$url = NULL;
			if(parent::login_session() == false || (parent::login_session() == true && $row['link_type'] == '0')){
				$tpl = $tpl_link;
				$icon = NULL;
				if(!empty($row['icon']))
					$icon = '<i class="fa fa-fw '.$row['icon'].'"></i>';

				$title = $row['title'];
				if(!empty($row['sid']))
					$page = parent::getSite('url', $row['sid']);

				$active = ($page == $p && !empty($p)) ? ' active' : NULL;

				// Dropdown
				if(empty($row['sid'].$row['url']) || parent::getAmount('menu', 'under', $row['id']) > 0){
					$list = self::getUnderMenu($row['id'], $tpl_dir);
					$active = $list[1];
					if(!empty($list[0]) && (($list[2] == true && $list[3] > 1) || ($list[2] == false && $list[3] > 0)))
						$tpl = $tpl_drop;
				} else {
					$url = !empty($row['sid']) ? '?p='.$page : str_replace('[csrf]', parent::getData('csrfToken'),$row['url']);
				}

				$tpl = str_replace('[title]', $title, $tpl);
				$tpl = str_replace('[icon]', $icon, $tpl);
				$tpl = str_replace('[id]', $row['id'], $tpl);
				$tpl = str_replace('[icon_raw]', $row['icon'], $tpl);
				$tpl = str_replace('[url]', $url, $tpl);
				$tpl = str_replace('[target]', $row['target'], $tpl);
				$tpl = str_replace('[active]', $active, $tpl);
				if(isset($list))
					$tpl = str_replace('[list]', $list[0], $tpl);

				$allow = false;
				if(((empty($row['url']) && $row['sid'] == '0') || parent::getAmount('menu', 'under', $row['id']) > 0) && (isset($list) && !empty($list[0]))) $allow = true; // Dropdown
				if(!empty($row['url']) && parent::allowSite('m'.$row['id'])) $allow = true; // External Links
				if(!empty($row['sid']) && parent::allowSite($row['sid'])) $allow = true; // Pages
				if($row['sid'] == '14' && parent::getMainData('regist_active') == 0) $allow = false; // Registrierung deaktiviert
				if($row['sid'] == '13' && parent::getMainData('pwv_active') == 0) $allow = false; // Passwort vergessen deaktiviert

				if($allow == true)
					$rtn[] = $tpl;
			}
		}
		return implode("\n", $rtn);
	}
	
	private function getUnderMenu($id, $tpl_dir = 'default'){
		$rtn = array();
		$rtn[0] = array();
		$rtn[1] = NULL;
		$rtn[2] = false;
		$rtn[3] = 0;
		global $p;
		
		// Load TPL
		$tpl_link = file_get_contents($this->main_path.$tpl_dir.'/menu_dropdown_link.tpl');
		
		// Falls der Pull-Down-Link auch einen Link enthaelt wird dieser ins Pull-Down aufgenommen
		$sql = $this->mysql->query("Select * From ".Prefix."_menu Where id = '$id'");
		$row = $sql->fetch_assoc();
		if(!empty($row['sid']) || !empty($row['url'])){
			$page = NULL;
			$tpl = $tpl_link;
			$icon = NULL;
			if(parent::login_session() == false || (parent::login_session() == true && $row['link_type'] == '0')){
				if(!empty($row['icon']))
					$icon = '<i class="fa fa-fw '.$row['icon'].'"></i> ';

				$title = $row['title'];
				if(!empty($row['sid']))
					$page = parent::getSite('url', $row['sid']);

				$url = !empty($row['sid']) ? '?p='.$page : str_replace('[csrf]', parent::getData('csrfToken'),$row['url']);
				$active = ($page == $p && !empty($p)) ? ' active' : NULL;
				if($row['sid'] == '14' && parent::getMainData('regist_active') == 0) $active = NULL; // Registrierung deaktiviert
				if($row['sid'] == '13' && parent::getMainData('pwv_active') == 0) $active = NULL; // Passwort vergessen deaktiviert

				$tpl = str_replace('[title]', $title, $tpl);
				$tpl = str_replace('[id]', $row['id'], $tpl);
				$tpl = str_replace('[icon]', $icon, $tpl);
				$tpl = str_replace('[icon_raw]', $row['icon'], $tpl);
				$tpl = str_replace('[url]', $url, $tpl);
				$tpl = str_replace('[target]', $row['target'], $tpl);
				$tpl = str_replace('[active]', $active, $tpl);

				if($active != NULL)
					$rtn[1] = ' active';

				$allow = false;
				if(!empty($row['url']) && parent::allowSite('m'.$row['id'])) $allow = true; // External Links
				if(!empty($row['sid']) && parent::allowSite($row['sid'])) $allow = true; // Pages
				if($row['sid'] == '14' && parent::getMainData('regist_active') == 0) $allow = false; // Registrierung deaktiviert
				if($row['sid'] == '13' && parent::getMainData('pwv_active') == 0) $allow = false; // Passwort vergessen deaktiviert

				if($allow == true){
					$rtn[0][] = $tpl;
					$rtn[2] = true;
					$rtn[3]++;
				}
			}
		}
		
		$sql = $this->mysql->query("Select * From ".Prefix."_menu Where under = '$id' Order by `pos` ASC, `title` ASC");
		while($row = $sql->fetch_assoc()){
			$page = NULL;
			$tpl = $tpl_link;
			$icon = NULL;
			if(parent::login_session() == false || (parent::login_session() == true && $row['link_type'] == '0')){
				if(!empty($row['icon']))
					$icon = '<i class="fa fa-fw '.$row['icon'].'"></i> ';

				$title = $row['title'];
				if(!empty($row['sid']))
					$page = parent::getSite('url', $row['sid']);
				$url = !empty($row['sid']) ? '?p='.$page : str_replace('[csrf]', parent::getData('csrfToken'),$row['url']);
				$active = ($page == $p && !empty($p)) ? ' active' : NULL;
				if($row['sid'] == '14' && parent::getMainData('regist_active') == 0) $active = NULL; // Registrierung deaktiviert
				if($row['sid'] == '13' && parent::getMainData('pwv_active') == 0) $active = NULL; // Passwort vergessen deaktiviert

				$tpl = str_replace('[title]', $title, $tpl);
				$tpl = str_replace('[icon]', $icon, $tpl);
				$tpl = str_replace('[id]', $row['id'], $tpl);
				$tpl = str_replace('[icon_raw]', $row['icon'], $tpl);
				$tpl = str_replace('[url]', $url, $tpl);
				$tpl = str_replace('[target]', $row['target'], $tpl);
				$tpl = str_replace('[active]', $active, $tpl);

				if($active != NULL)
					$rtn[1] = ' active';

				$allow = false;
				if(!empty($row['url']) && parent::allowSite('m'.$row['id'])) $allow = true; // External Links
				if(!empty($row['sid']) && parent::allowSite($row['sid'])) $allow = true; // Pages
				if($row['sid'] == '14' && parent::getMainData('regist_active') == 0) $allow = false; // Registrierung deaktiviert
				if($row['sid'] == '13' && parent::getMainData('pwv_active') == 0) $allow = false; // Passwort vergessen deaktiviert

				if($allow == true){
					$rtn[0][] = $tpl;
					$rtn[3]++;
				}
			}
		}
		$rtn[0] = implode("\n", $rtn[0]);
		return $rtn;
	}
	
	public function getMenuGroupOptions($id = NULL){
		$rtn = NULL;
		$sql = $this->mysql->query("Select * From ".Prefix."_menu_group Order by `name`");
		while($row = $sql->fetch_assoc()){
			$sel = ($id == $row['id']) ? ' selected' : NULL;
			$rtn .= '<option value="'.$row['id'].'"'.$sel.'>'.$row['name'].' #'.$row['id'].'</option>';
		}
		return $rtn;
	}
	
	public function getSiteOptions($id = NULL){
		$rtn = NULL;
		$sql = $this->mysql->query("Select * From ".Prefix."_sites Order by `title`");
		while($row = $sql->fetch_assoc()){
			$sel = ($id == $row['id']) ? ' selected' : NULL;
			$rtn .= '<option value="'.$row['id'].'"'.$sel.'>'.$row['title'].' ('.$row['filename'].'.'.$row['type'].')</option>';
		}
		return $rtn;
	}
	
	private function listUnderMenu($id){
		$rtn = '<table class="table table-striped">
			<thead>
				<tr>
					<th>Name</th>
					<th>Link</th>
					<th>Aktion</th>
				</tr>
			</thead>
			<tbody>';
		
		$sql = $this->mysql->query("Select * From ".Prefix."_menu Where id = '$id'");
		$row = $sql->fetch_assoc();
		if(!empty($row['sid']) || !empty($row['url'])){
			$btn = array();
			$icon = empty($row['icon']) ? NULL : '<i class="fa fa-fw '.$row['icon'].'"></i> ';
			$content = NULL;
			if(empty($row['sid'].$row['url'])){
				$content = '';
			} elseif(!empty($row['sid'])) {
				$content = '<a target="'.$row['target'].'" href="?p='.parent::getSite('url', $row['sid']).'">?p='.parent::getSite('url', $row['sid']).'</a>';
			} else {
				$content = '<a target="'.$row['target'].'" href="'.$row['url'].'">'.$row['url'].'</a>';
			}
			
			
			$btn[] = '<a href="?p=menu&f=view&id='.$row['id'].'"><i class="fa fa-eye"></i></a>';
			
			if(parent::auditRight('menu_edit'))
				$btn[] = '<a href="?p=menu&f=edit&id='.$row['id'].'"><i class="fa fa-pencil"></i></a>';
			
			if(parent::auditRight('menu_remove'))
				$btn[] = '<a href="?p=menu&f=remove&id='.$row['id'].'"><i class="fa fa-trash"></i></a>';

			$btn = implode(' | ', $btn);
			
			$rtn .= '<tr>
				<td>'.$icon.$row['title'].'</td>
				<td>'.$content.'</td>
				<td>'.$btn.'</td>
			</tr>';
		}
		
		$sql = $this->mysql->query("Select * From ".Prefix."_menu Where under = '$id' Order by `pos` ASC, `title` ASC");
		while($row = $sql->fetch_assoc()){
			$btn = array();
			$icon = empty($row['icon']) ? NULL : '<i class="fa fa-fw '.$row['icon'].'"></i> ';
			$content = NULL;
			if(empty($row['sid'].$row['url'])){
				$content = 'Pull-Down-Men&uuml;';
			} elseif(!empty($row['sid'])) {
				$content = '<a target="'.$row['target'].'" href="?p='.parent::getSite('url', $row['sid']).'">?p='.parent::getSite('url', $row['sid']).'</a>';
			} else {
				$content = '<a target="'.$row['target'].'" href="'.$row['url'].'">'.$row['url'].'</a>';
			}
			
			
			$btn[] = '<a href="?p=menu&f=view&id='.$row['id'].'"><i class="fa fa-eye"></i></a>';
			
			if(parent::auditRight('menu_edit'))
				$btn[] = '<a href="?p=menu&f=edit&id='.$row['id'].'"><i class="fa fa-pencil"></i></a>';
			
			if(parent::auditRight('menu_remove'))
				$btn[] = '<a href="?p=menu&f=remove&id='.$row['id'].'"><i class="fa fa-trash"></i></a>';

			$btn = implode(' | ', $btn);
			
			$rtn .= '<tr>
				<td><span class="wp11">'.$row['pos'].' </span> '.$icon.$row['title'].'</td>
				<td>'.$content.'</td>
				<td>'.$btn.'</td>
			</tr>';
		}
		return $rtn.'</tbody></table>';
	}
	
	// Gibt Menue-Tabelle aus
	public function listMenu(){
		$rtn = NULL;
		$sql = $this->mysql->query("Select * From ".Prefix."_menu Where under = '0' Order by `pos` ASC, `title` ASC");
		while($row = $sql->fetch_assoc()){
			$btn = array();
			$icon = empty($row['icon']) ? NULL : '<i class="fa fa-fw '.$row['icon'].'"></i> ';
			$content = NULL;
			if(empty($row['sid'].$row['url']) || parent::getAmount('menu', 'under', $row['id']) > 0){
				$content = self::listUnderMenu($row['id']);
			} elseif(!empty($row['sid'])) {
				$content = '<a target="'.$row['target'].'" href="?p='.parent::getSite('url', $row['sid']).'">?p='.parent::getSite('url', $row['sid']).'</a>';
			} else {
				$content = '<a target="'.$row['target'].'" href="'.$row['url'].'">'.$row['url'].'</a>';
			}
			
			$btn[] = '<a href="?p=menu&f=view&id='.$row['id'].'"><i class="fa fa-eye"></i></a>';
			
			if(parent::auditRight('menu_edit'))
				$btn[] = '<a href="?p=menu&f=edit&id='.$row['id'].'"><i class="fa fa-pencil"></i></a>';
			
			if(parent::auditRight('menu_remove'))
				$btn[] = '<a href="?p=menu&f=remove&id='.$row['id'].'"><i class="fa fa-trash"></i></a>';
			
			$btn = implode(' | ', $btn);
			
			$rtn .= '<tr>
				<td><span class="wp11">'.$row['pos'].' </span> '.$icon.$row['title'].'</td>
				<td>'.$content.'</td>
				<td>'.$btn.'</td>
			</tr>';
		}
		return $rtn;
	}
	
	public function menuOptionsUnder($id = NULL){
		$rtn = NULL;
		$sql = $this->mysql->query("Select * From ".Prefix."_menu Where under = '0' Order by `title`");
		while($row = $sql->fetch_assoc()){
			$sel = ($id == $row['id']) ? ' selected' : NULL;
			$icon = NULL;
			if(!empty($row['icon']) && empty($row['title'])){
				$icon = 'Nur ICON ('.$row['icon'].')';
			} elseif(!empty($row['icon'])) {
				$icon = ' '.$row['icon'];
			}
			$rtn .= '<option value="'.$row['id'].'"'.$sel.'>'.$row['title'].$icon.'</option>';
		}
		return $rtn;
	}
	
	private function autoPosition($start = 0, $menu = 0, $direction = 0, $end = 'na'){
		/***********************
		 * $start => Startwert, ab welcher er die anderen Menuepunkte verschieben soll
		 * $menu  => In welchem Menue verschoben werden soll (Menu-Under-ID)
		 * $direction => In welche Richtung verschoben werden soll (0 = +, 1 = -)
		 * $end => Bis zu welchem Wert er verschieben soll
		 ***********************/
		if($end == 'na'){
			if($direction == 0){
				$query = "Update `".Prefix."_menu` Set pos = pos + 1 Where under = '$menu' AND pos >= '$start'";
			} else {
				$query = "Update `".Prefix."_menu` Set pos = pos - 1 Where under = '$menu' AND pos >= '$start'";
			}
		} elseif(is_numeric($end)){
			if($direction == 0){
				$query = "Update `".Prefix."_menu` Set pos = pos + 1 Where under = '$menu' AND pos >= '$start' AND pos <= '$end'";
			} else {
				$query = "Update `".Prefix."_menu` Set pos = pos - 1 Where under = '$menu' AND pos >= '$start' AND pos <= '$end'";
			}
		}
		
		$sql = $this->mysql->query($query);
		if($sql === false){
			errormail('Fehler beim anpassen der Positionen! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
			return false;
		}
		return true;
	}
	
	public function addMenu(){
		$error = NULL;
		
		$title  = length(isset($_POST['title'])  ? $_POST['title']  : NULL, 32);
		$icon   = length(isset($_POST['icon'])   ? $_POST['icon']   : NULL, 32);
		$pos    = length(isset($_POST['pos'])    ? $_POST['pos']    : NULL, 3);
		$under  = length(isset($_POST['under'])  ? $_POST['under']  : NULL, 16);
		$file   = length(isset($_POST['file'])   ? $_POST['file']   : NULL, 16, 0, "sql");
		$url    = length(isset($_POST['url'])    ? $_POST['url']    : NULL, 1024, 0, "sql");
		$target = length(isset($_POST['target']) ? $_POST['target'] : NULL, 6);
		$type   = length(isset($_POST['link_type']) ? $_POST['link_type'] : 0, 1);
		$menu   = '1';
		
		if(parent::auditRight('menu_add')){
			if((!empty($icon) || !empty($title)) && $pos != ''){
				if((!empty($url) XOR !empty($file) && !empty($target)) || empty($url.$file)){
					if(empty($file) || parent::getAmount('sites', 'id', $file) == 1){
						// passe Position an
						if(self::autoPosition($pos, $under, 0)){
							if(!empty($url) && preg_match('/^www.(.*)/si', $url))
								$url = 'http://'.$url;
							$sql = $this->mysql->query("Insert Into ".Prefix."_menu (`title`, `icon`, `under`, `sid`, `url`, `target`, `link_type`, `menu`, `pos`) Values ('$title', '$icon', '$under', '$file', '$url', '$target', '$type', '$menu', '$pos')");
							if($sql !== false){
								header('Location: ?p=menu&h=menu_add_successfully');
								exit();
							} else {
								$error = 'Fehler beim erstellen des Men&uuml;punktes! Bitte versuche es sp&auml;ter erneut.';
								errormail('Fehler beim erstellen eines Men&uuml;punktes! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
							}
						} else {
							$error = 'Fehler beim anpassen der Positionen! Bitte versuche es sp&auml;ter erneut.';
						}
					} else {
						$error = 'Die Datei existiert nicht!';
					}
				} else {
					$error = 'Bitte einen Link-Target w&auml;hlen! (Es darf entweder nur die URL oder die Datei ausgew&auml;hlt werden!)';
				}
			} else {
				$error = 'Bitte Name und/oder Icon und Position ausf&uuml;llen!';
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		
		return $error;
	}
	
	public function editMenu(){
		$error = NULL;
		global $id;
		
		$title  = length(isset($_POST['title'])  ? $_POST['title']  : NULL, 32);
		$icon   = length(isset($_POST['icon'])   ? $_POST['icon']   : NULL, 32);
		$pos    = length(isset($_POST['pos'])    ? $_POST['pos']    : NULL, 3);
		$under  = length(isset($_POST['under'])  ? $_POST['under']  : NULL, 16);
		$file   = length(isset($_POST['file'])   ? $_POST['file']   : NULL, 16, 0, "sql");
		$url    = length(isset($_POST['url'])    ? $_POST['url']    : NULL, 1024, 0, "sql");
		$target = length(isset($_POST['target']) ? $_POST['target'] : NULL, 6);
		$type   = length(isset($_POST['link_type']) ? $_POST['link_type'] : 0, 1);
		$menu   = '1';
		
		if(parent::auditRight('menu_edit')){
			if((!empty($icon) || !empty($title)) && $pos != ''){
				if((!empty($url) XOR !empty($file) && !empty($target)) || empty($url.$file)){
					if(empty($file) || parent::getAmount('sites', 'id', $file) == 1){
						if(parent::getAmount('menu', 'id', $id) == 1){
							if($under != $id){
								// passe Position an
								$autoPos = true;
								$old_pos = parent::getValue('menu', 'id', $id, 'pos');
								if($old_pos > $pos){
									$autoPos = self::autoPosition($pos, $under, 0, $old_pos);
								} elseif($old_pos < $pos){
									$autoPos = self::autoPosition($old_pos, $under, 1, $pos);
								}
								if($autoPos == true){
									if(!empty($url) && preg_match('/^www.(.*)/si', $url))
										$url = 'http://'.$url;
									$sql = $this->mysql->query("Update ".Prefix."_menu Set `title` = '$title', `icon` = '$icon', `under` = '$under', `sid` = '$file', `url` = '$url', `target` = '$target', `link_type` = '$type', `menu` = '$menu', `pos` = '$pos' Where id = '$id'");
									if($sql !== false){
										header('Location: ?p=menu&h=menu_edit_successfully');
										exit();
									} else {
										$error = 'Fehler beim bearbeiten des Men&uuml;punktes! Bitte versuche es sp&auml;ter erneut.';
										errormail('Fehler beim bearbeiten eines Men&uuml;punktes! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
									}
								} else {
									$error = 'Fehler beim anpassen der Positionen! Bitte versuche es sp&auml;ter erneut.';
								}
							} else {
								$error = 'Der Link kann nicht unter sich selbst eingeordnet werden!';
							}
						} else {
							$error = 'Dieser Eintrag existiert nicht!';
						}
					} else {
						$error = 'Die Datei existiert nicht!';
					}
				} else {
					$error = 'Bitte einen Link-Target w&auml;hlen! (Es darf entweder nur die URL oder die Datei ausgew&auml;hlt werden!)';
				}
			} else {
				$error = 'Bitte Name und/oder Icon und Position ausf&uuml;llen!';
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		
		return $error;
	}
	
	public function removeMenu(){
		$error = NULL;
		global $id;
		if(parent::auditRight('menu_remove')){
			if(parent::getAmount('menu', 'id', $id) == 1){
				if(parent::getAmount('menu', 'under', $id) == 0){
					$pos = parent::getValue('menu', 'id', $id, 'pos');
					$under = parent::getValue('menu', 'id', $id, 'under');
					if(self::autoPosition($pos, $under, 1)){
						$sql = $this->mysql->query("Delete From ".Prefix."_menu Where id = '$id'");
						if($sql !== false){
							header('Location: ?p=menu&h=menu_remove_successfully');
							exit();
						} else {
							$error = 'Fehler beim entfernen des Men&uuml;punktes! Bitte versuche es sp&auml;ter erneut.';
							errormail('Fehler beim entfernen eines Men&uuml;punktes! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
						}
					} else {
						$error = 'Fehler beim anpassen der Positionen! Bitte versuche es sp&auml;ter erneut.';
					}
				} else {
					$error = 'Dieser Men&uuml;punkt darf keine Unterpunkte mehr enthalten um diesen entfernen zu k&ouml;nnen!';
				}
			} else {
				$error = 'Dieser Eintrag existiert nicht!';
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		return $error;
	}
	
	public function resetOptions($val){
		$rtn = NULL;
		$pos = 0;
		$sql = $this->mysql->query("Select * From ".Prefix."_menu Group by `under` Order by `under` ASC, `title` ASC");
		while($row = $sql->fetch_assoc()){
			$sel = ($val == $row['under']) ? ' selected' : NULL;
			$icon = NULL;
			if($row['under'] != 0){
				$rowIcon = parent::getValue('menu', 'id', $row['under'], 'icon');
				$rowTitle = parent::getValue('menu', 'id', $row['under'], 'title');
				$pos = parent::getValue('menu', 'id', $row['under'], 'pos');
				if(!empty($rowIcon) && empty($rowTitle)){
					$icon = 'Nur ICON ('.$rowIcon.')';
				} elseif(!empty($rowIcon)) {
					$icon = ' '.$rowIcon;
				}
			} else {
				$rowTitle = 'Hauptmen&uuml;';
				$pos = -1;
			}
			
			$rtn[$pos] = '<option value="'.$row['under'].'"'.$sel.'>'.$rowTitle.$icon.'</option>';
		}
		ksort($rtn);
		return implode('', $rtn);
	}
	
	public function resetMenuPositions(){
		$error = NULL;
		$passwd  = length(isset($_POST['passwd'])  ? $_POST['passwd']  : NULL, 64);
		$resetOption  = length(isset($_POST['resetOption'])  ? $_POST['resetOption']  : NULL, 16);

		if(parent::auditRight('menu_reset_pos')){
			if(parent::pwverify($passwd)){
				$pos = 0;
				$under = 0;
				$range = NULL;
				if($resetOption != 'all'){
					$range = "Where under = '$resetOption'";
				}
				$sql = $this->mysql->query("Select * From `".Prefix."_menu` $range Order by `under` ASC, `title` ASC");
				while($row = $sql->fetch_assoc()){
					if($under != $row['under'])
						$pos = 0;
					
					$query = $this->mysql->query("Update `".Prefix."_menu` Set pos = '$pos' Where id = '".$row['id']."'");
					if($query === false){
						$error = 'Fehler beim neu anordnen der Positionen!';
							errormail('Fehler beim neu anordnen der Positionen! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
						break;
					}
					
					$under = $row['under'];
					$pos++;
				}
				if(empty($error)){
					header('Location: ?p=menu&h=reset_positions_successfully');
					exit();
				}
			} else {
				$error = 'Du hast ein falsches Passwort eingegeben! Bitter versuche es erneut.';
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		return $error;
	}
	
	public function fillGapsMenu(){
		$error = NULL;

		if(parent::auditRight('menu_fill_gaps')){
			$start = -1;
			$last = -1;
			$founds = array();
			$last_under = -1;
			$sql = $this->mysql->query("Select * From `".Prefix."_menu` Order by `under` ASC, `pos` ASC");
			while($row = $sql->fetch_assoc()){
				if($last_under != $row['under']){
					$start = $row['pos'];
					$last = $row['pos'] - 1;
				}
				
				$next_num = count($founds);
				if(($row['pos'] - $last) > 1){
					$founds[$next_num][0] = $last - $row['pos'] + 1;
					$founds[$next_num][1] = $row['id'];
					$founds[$next_num][2] = $row['under'];
					$founds[$next_num][3] = $row['pos'];
					$founds[$next_num][4] = false;
				} elseif(($row['pos'] - $last) == 0){
					$founds[$next_num][0] = 1;
					$founds[$next_num][1] = $row['id'];
					$founds[$next_num][2] = $row['under'];
					$founds[$next_num][3] = $row['pos'];
					$founds[$next_num][4] = true;
				} elseif($row['pos'] < 0){
					$founds[$next_num][0] = abs(0 - $row['pos']);
					$founds[$next_num][1] = $row['id'];
					$founds[$next_num][2] = $row['under'];
					$founds[$next_num][3] = $row['pos'];
					$founds[$next_num][4] = false;
				}
				
				$last = $row['pos'];
				$last_under = $row['under'];
			}

			if(count($founds) > 0){
				foreach($founds as $discovery){
					$start = $discovery[3];
					if($discovery[4] == true) // Wenn eine Zahl doppelt ist
						$start++;

					
					$direction = ($discovery[0] > 0) ? 0 : 1;
					
					for($i = 0; $i < abs($discovery[0]); $i++){
						self::autoPosition($start, $discovery[2], $direction);
					}
					
					if($discovery[4] == true) // Wenn eine Zahl doppelt ist
						$this->mysql->query("Update `".Prefix."_menu` Set pos = pos + 1 Where id = '".$discovery[1]."'");
				}
			}
			if(empty($error)){
				header('Location: ?p=menu&h=fill_gaps_successfully&a='.count($founds));
				exit();
			}
		} else {
			$error = 'Du hast keine Berechtigung diese Aktion auszuf&uuml;hren!';
		}
		return $error;
	}
}



















?>