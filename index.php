<?php
/********************************************
* 
* System:   EASY 2.0 Loginsystem
* Author:   Marius Rasche (aka Marlight)
* File:     index.php
* FVersion: 0.1.2 (this file)
* SVersion: 0.9.7 BETA (complete System)
* Date:     20.05.2018
*
* Created by www.marlight-systems.de
* Copyright by Marlight Systems (www.marlight-systems.de)
* All rights reserved.
* 
*********************************************/

// Session starten | Start session
session_name("EASY_2");
session_start();

// Fehler-Ausgabe | Error output
error_reporting(E_ALL);
ini_set('display_errors', false);

// For install
if(!file_exists('./system/config.inc.php')){
	if(is_dir('./install/') && file_exists('./install/index.php'))
		header('Location: ./install/');
	exit('System nicht installiert!');
}

// Dateien einbinden | Including files
require_once('./system/config.inc.php');
require_once('./system/functions.inc.php');
require_once('./system/functions.user.php');
require_once('./system/classes.run.php');
require_once('./system/classes.run.user.php');
require_once('./system/run.inc.php');
require_once('./system/run.user.php');
require_once('./system/error_handling.php');

$sites->includeSite(true);

?>
<!DOCTYPE html>
<html lang="de">
  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title><?php echo $sites->getSiteName(); ?></title>
	<!-- Favicon -->
	<link rel="shortcut icon" href="favicon.ico">
	  
    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/modern-business.css" rel="stylesheet">
	<link href="font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
	<link href="css/mlsystems.css" rel="stylesheet">

  </head>
  <body <?php if($loginsystem->is_locked() == true) echo 'class="locked"'; ?>>
	<?php if($loginsystem->is_locked() == false){ ?>
    <!-- Navigation -->
    <nav class="navbar fixed-top navbar-expand-lg navbar-dark bg-dark fixed-top">
      <div class="container">
        <a class="navbar-brand" href="./"><?php echo $loginsystem->getMainData('site_title'); ?></a>
        <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
			  <?php echo $menu->getMenu(); ?>
          </ul>
        </div>
      </div>
    </nav>
	
	<?php include($sites->includeSite()); ?>
	
    <!-- Footer -->
    <footer class="py-5 bg-dark">
      <div class="container">
        <p class="m-0 text-center text-white">Copyright &copy; Your Website 2017</p>
      </div>
      <!-- /.container -->
    </footer>
	<?php } else { // Benutzer gesperrt
			require_once('./templates/login/locked.php');
		} ?>
    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  </body>

</html>
