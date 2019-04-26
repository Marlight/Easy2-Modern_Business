<?php
/********************************************
* 
* System:   EASY 2.0 Loginsystem
* Author:   Marius Rasche (aka Marlight)
* File:     config.inc.php
* FVersion: 1.0 (this file)
* SVersion: 0.9.7 (complete System)
* Date:     20.05.2018
*
* Created by www.marlight-systems.de
* Copyright by Marlight Systems (www.marlight-systems.de)
* All rights reserved.
* 
*********************************************/



/* MySQL Connections Data */

$mysql_data = array();

/*********************
 * EN: MySQL Database IP adress (host)
 * DE: MySQL Datenbank IP-Adresse (Host)
 *********************/
 
$mysql_data["host"]		= "[host]";

/*********************
 * EN: MySQL Database User (Username)
 * DE: MySQL Datenbank Benutzer (Benutzername)
 *********************/
 
$mysql_data["user"]		= "[user]";

/*********************
 * EN: MySQL Database Password (The user's password)
 * DE: MySQL Datenbank Passwort (Passwort des Benutzers)
 *********************/
 
$mysql_data["passwd"]	= "[pass]";

/*********************
 * EN: MySQL Database Name
 * DE: MySQL Datenbankname
 *********************/
 
$mysql_data["database"]	= "[dbna]";

/*********************
 * EN: Prefix of the MySQL-tables
 * DE: Praefix der MySQL-Tabellen
 *********************/
 
$mysql_data["prefix"]	= "[pref]";



define("Prefix", $mysql_data["prefix"]);
define("EASY_VERSION", "0.9.7 BETA");
$dberror = true;


/* MySQL Connection */

$mysql = @new mysqli($mysql_data["host"], $mysql_data["user"], $mysql_data["passwd"], $mysql_data["database"]);
if(mysqli_connect_errno()){ // If no connection to the database is possible
	$error = mysqli_connect_error()."(MySQL-Error: ".mysqli_connect_errno().")";
	$dberror = true;
} else {
	$dberror = false;
}

/* MySQL Connectionsscript end */

?>
