<?php
/********************************************
* 
* System:   EASY 2.0 Loginsystem
* Author:   Marius Rasche (aka Marlight)
* Class:    database
* File:     database.php
* FVersion: 0.2 (this file)
* SVersion: 0.9.7 BETA (complete System)
* Date:     10.01.2018
*
* Created by www.marlight-systems.de
* Copyright by Marlight Systems (www.marlight-systems.de)
* All rights reserved.
* 
*********************************************/


class database{
	protected $mysql;
	
	public function __construct(){
		global $mysql;
		$this->mysql = $mysql;	
	}
	
	public function getTableInfo($table, $value = NULL, $prefix = Prefix){
		global $mysql_data;
		if(!empty($prefix))
	 		$prefix .= '_';
			
		$sql = $this->mysql->query("SHOW TABLE STATUS FROM `".$mysql_data['database']."` LIKE '".$prefix.$table."'");
		$row = $sql->fetch_assoc();
		
		if(!empty($value))
			if(array_key_exists($value, $row))
				return $row[$value];
			else
				return 'Wert '.$value.' nicht gefunden!';
		return $row;
	}
	
	public function getAmount($table, $column = NULL, $value = NULL, $operator = "AND", $prefix = Prefix){
		/*****
		 * Gibt die Anzahl aller Datensaetze zurueck
		 * $table => Welche Tabelle soll ausgelesen werden?
		 * $column => Welche Spalte/n soll/en untersucht werden? (bei mehreren als Array)
		 * $value => Nach welchem Inhalt soll gesucht werden? (bei mehreren als Array)
		 * $operator => Nur wenn mehr als eine Spalte durchsucht werden soll angeben!!!!
		 *              Gibt an mit welchem Operator alle Spalten verbunden werden.
		 *              Optionen: "AND", "OR"
		 * $prefix => Wenn ein anderer Prefix genutzt werden soll
		 *****/
		 if(!empty($prefix))
		 	$prefix .= '_';
		 $query = NULL;
		 $sql = $this->mysql->query("Show Tables Like '".$prefix.$table."'");
		 if($sql->num_rows == 1){
			 if(!empty($column)){
				 if(is_array($column)){
					 $query = array();
					 if(is_array($value)){
						foreach($column as $key => $col){
							if(substr($col, 0, 1) == '!')
								$query[] = substr($col, 1)." != '".$value[$key]."'";
							else
								$query[] = $col." = '".$value[$key]."'";
						}
						$query = implode(' '.$operator.' ', $query);
					 } else {
						foreach($column as $col)
							if(substr($col, 0, 1) == '!')
								$query[] = substr($col, 1)." != '".$value."'";
							else
								$query[] = $col." = '".$value."'";
						$query = implode(' '.$operator.' ', $query);
					 }
				 } else {
					 $col_negative = (substr($column, 0, 1) == '!') ? " != '" : " = '";
					 if(substr($column, 0, 1) == '!')
					 	$column = substr($column, 1);
					if(is_array($value)){
						$query = array();
						foreach($value as $val)
							$query[] = $column.$col_negative.$val."'";
						$query = implode(' OR ', $query);
					} else
						$query = $column.$col_negative.$value."'"; 	
				 }
				 $query = " Where ".$query;
			 }
			 $sql = $this->mysql->query("Select * From ".$prefix.$table.$query);
			 $amount = $sql->num_rows;
			 $amount = empty($amount) ? 0 : $amount;
			 if(!empty($this->mysql->error))
			 	return $this->mysql->error;
			 return $amount;
		 } else {
			 return "Die Tabelle ".$prefix.$table." existiert nicht!";
		 }
	}
	
	public function getValue($table, $column = NULL, $value = NULL, $output = NULL, $array = false, $sort = NULL, $operator = "AND", $prefix = Prefix){
		/*****
		 * Gibt die gewuenschte Spalte zurueck
		 * $table => Welche Tabelle soll ausgelesen werden?
		 * $column => Welche Spalte/n soll/en untersucht werden? (bei mehreren als Array)
		 * $value => Nach welchem Inhalt soll gesucht werden? (bei mehreren als Array)
		 * $output => Von welcher Spalte soll der Inhalt zurueck gegeben werden?
		 *            Wenn NULL: alle Spalten des gefundenen Datensatzes wird zurueckgegeben
		 * $array => false: Nur der erste Datensatz wird zurueckgegeben
		 *            true: Alle uebereinstimmenden Datensaetze werden zurueckgegeben
		 * $sort => Wenn du $array auf "true" hast, dann kannst du hierueber noch eine Sortierung festlegen
		 * $operator => Nur wenn mehr als eine Spalte durchsucht werden soll angeben!!!!
		 *              Gibt an mit welchem Operator alle Spalten verbunden werden.
		 *              Optionen: "AND", "OR"
		 * $prefix => Wenn ein anderer Prefix genutzt werden soll
		 *****/
		 $query = NULL;
		 if(!empty($prefix))
		 	$prefix .= '_';
		 $sql = $this->mysql->query("Show Tables Like '".$prefix.$table."'");
		 if($sql->num_rows == 1){
			 if(!empty($column)){
				 if(is_array($column)){
					 $query = array();
					 if(is_array($value)){
						foreach($column as $key => $col)
							$query[] = $col." = '".$value[$key]."'";
						$query = implode(' '.$operator.' ', $query);
					 } else {
						foreach($column as $col)
							$query[] = $col." = '".$value."'";
						$query = implode(' '.$operator.' ', $query);
					 }
				 } else {
					if(is_array($value)){
						$query = array();
						foreach($value as $val)
							$query[] = $column." = '".$val."'";
						$query = implode(' OR ', $query);
					} else
						$query = $column." = '".$value."'"; 	
				 }
				 $query = " Where ".$query;
			 }
			 $query = ($array == false) ? $query." Limit 1" : $query." ".$sort;
			 
			 $sql = $this->mysql->query("Select * From ".$prefix.$table.$query);
			 $amount = $sql->num_rows;
			 $amount = empty($amount) ? 0 : $amount;
			 if($amount > 0){
				if($amount > 1 && $array == true && empty($output)){
					$row = array();
					while($out = $sql->fetch_assoc())
						$row[] = $out;
				}
				
				if($array == true && !empty($output)){
					$rtn = NULL;
					while($row = $sql->fetch_assoc()){
						$rtn[] = $row[$output];	
					}
					return $rtn;
				}
				 
				$row = $sql->fetch_assoc();
				if(empty($output)){
					return $row;
				}
				return $row[$output];
			 }
			 if(!empty($this->mysql->error))
				return $this->mysql->error;
			 return NULL;
		 } else {
			 return "Die Tabelle ".$prefix.$table." existiert nicht!";
		 }
	}
	
	public function getMainData($tag){
		/************************
		 * Gibt den gewuenschten Wert aus der Tabelle "main" zurueck
		 * $tag => Welche Spalte ausgegeben werden soll
		 ************************/
		
		$sql = $this->mysql->query("Select `value` From `".Prefix."_main` Where tag = '$tag' Limit 1");
		if($sql->num_rows > 0){
			$row = $sql->fetch_assoc();
			return $row['value'];
		}
		return "Einstellung nicht gefunden!";
	}
}




?>