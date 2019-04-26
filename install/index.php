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
 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', false);
$dberror = true;
$mysql = NULL;
$version = "0.9.7 BETA";

if(file_exists('../system/config.inc.php'))
	require_once '../system/config.inc.php';
require_once './setup/install.php';

$error = NULL;
$p = length(isset($_GET['p']) ? $_GET['p'] : '', 16);
$c = length(isset($_GET['c']) ? $_GET['c'] : '', 16);

if($p == 'step3' && $dberror){
	sleep(1);
	header('Location: '.$_SERVER["SCRIPT_NAME"].'?p=step3');
}

if($p == 'step2.2' && $dberror){
	sleep(1);
	header('Location: '.$_SERVER["SCRIPT_NAME"].'?p=step2.2');
}

$install = @new install();
$tmp = @new template();
$install->conditions(NULL, false);

if($p == 'step2' && $c == 'mysql')
	$error = $install->mysqlConnection();

if($p == 'step2.2' && $c == 'mysql_ignore')
	$error = $install->createMysqlTableIgnoreExists();

if($p == 'step2.2' && $c == 'mysql_del')
	$error = $install->createMysqlTableDeleteExists();
	
if($p == 'step3' && $c == 'mainsettings')
	$error = $install->saveMainsettings();
	
if($p == 'step4' && $c == 'create_user')
	$error = $install->createUser();
	
if($p == 'step5' && $c == 'replace')
	$error = $install->replace_impressum();
	

if(!empty($error)){
	$error = '
        <div class="row">
          <div class="col-lg-12">
            <div class="alert alert-danger alert-dismissable">
            	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<i class="fa fa-warning"></i> '.$error.'
			</div>
          </div>
        </div><!-- /.row -->';
}
?>
<!DOCTYPE html><html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, shrink-to-fit=no, initial-scale=1">
    <meta name="author" content="Marlight Systems">
    <meta name="copyright" content="https://marlight-systems.de">
    <meta name="generator" content="https://marlight-systems.de">
    <meta name="robots" content="noindex, nofollow, noarchive">
    
    <title>Installation - EASY 2.0</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/simple-sidebar.css" rel="stylesheet">
    <link href="css/loginsystem.css" rel="stylesheet">
    <link rel="stylesheet" href="../font-awesome/css/font-awesome.min.css" type="text/css">
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <ul class="sidebar-nav">
                <li class="sidebar-brand"><a target="_blank" href="https://marlight-systems.de/?p=project_easy_2.0">EASY 2.0 v.<?php echo $version; ?></a></li>
                <li><a target="_blank" href="//marlight-systems.de/?p=project_easy_2"><i class="fa fa-external-link-square"></i> &Uuml;bersicht</a></li>
                <li><a target="_blank" href="//marlight-systems.de/?p=project_easy_2&s=manuals"><i class="fa fa-external-link-square"></i> Anleitungen &amp; FAQ</a></li>
                <li><a target="_blank" href="//marlight-systems.de/?p=project_easy_2&s=documentation"><i class="fa fa-external-link-square"></i> Dokumentation</a></li>
                <li><a target="_blank" href="//marlight-systems.de/?p=project_easy_2&s=terms_of_use"><i class="fa fa-external-link-square"></i> Nutzungsbedingungen</a></li>
            </ul>
            <span style="position:absolute; bottom:0; padding: 10px; color: #999">Copyright &copy; 2018 <a target="_blank" href="http://marlight-systems.de">marlight-systems.de</a></span>
        </div>
        <!-- /#sidebar-wrapper -->
        <!-- Page Content -->
		<?php include($tmp->includeFile()); ?>
        <!-- /#page-content-wrapper -->
    </div>
    <!-- /#wrapper -->

    <!-- jQuery -->
    <script src="js/jquery.js"></script>
    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>
</body>

</html>