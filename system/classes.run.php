<?php
/********************************************
* 
* System:   EASY 2.0 Loginsystem
* Author:   Marius Rasche (aka Marlight)
* Class:    database
* FVersion: 1.3 (this file)
* SVersion: 0.9.7 BETA (complete System)
* Date:     11.03.2018
*
* Created by www.marlight-systems.de
* Copyright by Marlight Systems (www.marlight-systems.de)
* All rights reserved.
* 
*********************************************/

// Autoloader for Class-Files
function __autoload($class_name){
	$parts = explode('\\', $class_name);
	$path = implode(DIRECTORY_SEPARATOR, $parts);
    include_once('system/classes/'.$path.'.php');
}


// Initialize the Loginsystem
$loginsystem = new loginsystem();

// Initialize Captcha
if(isset($_GET['captcha']) && $_GET['captcha'] == 'img'){
	$captcha = new captcha();
	$captcha->generateCaptcha();
}

// Initialize Rules
if(isset($_GET['p']) && $_GET['p'] == 'rules'){
	$rules = new rules();
}

// Initialize Sites
$sites = new sites();

// Initialize Menu
$menu = new menu();

// Initialize additional fields
$additional_fields = new additional_fields();



?>