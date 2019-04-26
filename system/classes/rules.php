<?php
/********************************************
* 
* System:   EASY 2.0 Loginsystem
* Author:   Marius Rasche (aka Marlight)
* Class:    rules
* File:     rules.php
* FVersion: 0.1 (this file)
* SVersion: 0.9.7 BETA (complete System)
* Date:     08.11.2017
*
* Created by www.marlight-systems.de
* Copyright by Marlight Systems (www.marlight-systems.de)
* All rights reserved.
* 
*********************************************/

class rules extends loginsystem{
	protected $mysql;
	
	public function __construct(){
		parent::__construct();	
	}

	public function listRules(){
		$output = NULL;
		$sql  	= $this->mysql->query("Select * From `".Prefix."_rules` Order by tag");
		while($out = $sql->fetch_assoc()){
			$output .= '
								<tr>
									<td class="">'.$out['name'].'</td>
									<td class="">'.$out['tag'].'</td>
									<td class="">'.$out['description'].'</td>
									<td class="text-center">
										<a href="?p=rules&f=edit&id='.$out['id'].'"><i class="fa fa-pencil"></i></a> | 
										<a href="?p=rules&f=delete&id='.$out['id'].'"><i class="fa fa-times"></i></a>
									</td>
								</tr>';

		}

		if($sql->num_rows == 0) 
		$output = '
							<tr>
								<td colspan="4" class="txtcenter"><em>Keine Regeln gefunden!</em></td>
							</tr>';
		return $output;
	}

	public function newRule(){
		$name	= length(isset($_POST['name']) ? $_POST['name'] : '', 40);
		$tag	= length(isset($_POST['tag']) ? $_POST['tag'] : '', 40);
		$desc	= length(isset($_POST['description']) ? $_POST['description'] : '', 256);
		if(parent::auditRight('rule_new')){
			if(!empty($name) && !empty($tag) && !empty($desc)){
				$sql = $this->mysql->query("Insert Into ".Prefix."_rules (`name`, `tag`, `description`) Values ('$name', '$tag', '$desc')");
				if($sql){
					header('Location: ?p=rules&h=rule_add_successfully');
					exit();
				} else {
					$error = 'Fehler beim erstellen der Regel! Bitte versuchen Sie es zu einem sp&auml;teren Zeitpunkt erneut.';
					errormail('MySQL-Fehler beim erstellen einer Regel! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
				}
			} else {
				$error = 'Es m&uuml;ssen alle Felder ausgef&uuml;llt sein!';
			}
		} else {
			$error = 'Sie haben keine Berechtigung um diese Aktion auszuf&uuml;hren!';
		}
		return sondertext($error);
	}

	public function editRule(){
		global $id;
		$name	= length(isset($_POST['name']) ? $_POST['name'] : '', 40);
		$tag	= length(isset($_POST['tag']) ? $_POST['tag'] : '', 40);
		$desc	= length(isset($_POST['description']) ? $_POST['description'] : '', 256);
		if(parent::auditRight('rule_edit')){
			if(!empty($name) && !empty($tag) && !empty($desc)){
				$sql = $this->mysql->query("Update ".Prefix."_rules Set `name` = '$name', `tag` = '$tag', `description` = '$desc' Where `id` = '$id'");
				if($sql){
					header('Location: ?p=rules&h=rule_edit_successfully');
					exit();
				} else {
					$error = 'Fehler beim bearbeiten der Regel! Bitte versuchen Sie es zu einem sp&auml;teren Zeitpunkt erneut.';
					errormail('MySQL-Fehler beim bearbeiten einer Regel! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
				}
			} else {
				$error = 'Es m&uuml;ssen alle Felder ausgef&uuml;llt sein!';
			}
		} else {
			$error = 'Sie haben keine Berechtigung um diese Aktion auszuf&uuml;hren!';
		}
		return sondertext($error);
	}

	public function removeRule(){
		global $id;
		if(parent::auditRight('rule_delete')){
			$sql = $this->mysql->query("Delete From ".Prefix."_rules Where id = '$id' Limit 1");
			if($sql){
				header('Location: ?p=rules&h=rule_remove_successfully');
				exit();
			} else {
				$error = 'Fehler beim l&ouml;schen der Regel! Bitte versuchen Sie es zu einem sp&auml;teren Zeitpunkt erneut.';
				errormail('MySQL-Fehler l&ouml;schen bearbeiten einer Regel! Fehler in class '.__CLASS__.' => function '.__FUNCTION__.'()! MySQL-Fehler '.$this->mysql->errno.': '.$this->mysql->error);
			}
		} else {
			$error = 'Sie haben keine Berechtigung um diese Aktion auszuf&uuml;hren!';
		}
		return sondertext($error);
	}

}

?>