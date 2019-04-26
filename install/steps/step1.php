        <div id="page-content-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1>Installation - Step 1</h1>
                        <ol class="breadcrumb pull-right">
                            <li><a href="index.php">Einleitung</a></li>
                            <li><a href="index.php?p=terms_of_use">Nutzungsbedingungen</a></li>
                            <li class="active actual">Step 1 - Voraussetzungen</li>
                            <li class="active">Step 2 - MySQL-Daten</li>
                            <li class="active">Step 3 - Grundeinstellungen</li>
                            <li class="active">Step 4 - Accounts</li>
                           <li class="active">Fertig</li>
              	        </ol>
                    </div>
                    <div class="col-lg-12">
                        <p>Bitte vergewissere dich das folgende Bedingungen erf&uuml;llt sind:</p>
                        <table class="table table-striped">
                        	<thead>
                            	<tr>
                            		<th>Bedingung</th>
                                	<th>Soll</th>
                                	<th>Ist</th>
                            	</tr>
                            </thead>
                            <tbody>
                            	<tr>
                                	<td colspan="3"><strong>Schreibrechte auf:</strong></td>
                                </tr>
                            	<tr>
                                	<td>avatare/</td>
                                	<td>Ja</td>
                                	<td><?php echo $install->conditions(0); ?></td>
                                </tr>
                            	<tr>
                                	<td>system/</td>
                                	<td>Ja</td>
                                	<td><?php echo $install->conditions(1); ?></td>
                                </tr>
                             	<tr>
                                	<td colspan="3"><strong>Voraussetzungen:</strong></td>
                                </tr>                               
                             	<tr>
                                	<td>PHP 5.5 oder h&ouml;her</td>
                                	<td>&gt;= 5.5</td>
                                	<td><?php echo $install->conditions(3); ?></td>
                                </tr>
                             	<tr>
                                	<td>PHP Funktion "imagettftext" vorhanden</td>
                                	<td>true</td>
                                	<td><?php echo function_exists('imagettftext') ? '<span class="text-success">true</span>' : '<span class="text-danger">false</span>'; ?></td>
                                </tr>
                             	<tr>
                                	<td>PHP Erweiterung "GD" vorhanden</td>
                                	<td>true</td>
                                	<td><?php echo extension_loaded('gd') ? '<span class="text-success">true</span>' : '<span class="text-danger">false</span>'; ?></td>
                                </tr>
                             	<tr>
                                	<td>PHP Einstellung "short_open_tag" <span style="font-size: 10px">(Kein muss, aber kann ggf. Fehler vorbeugen)</span></td>
                                	<td>true</td>
                                	<td><?php echo ini_get('short_open_tag') ? '<span class="text-success">true</span>' : '<span class="text-danger">false</span>'; ?></td>
                                </tr>
                             	<tr>
                                	<td>MySQL Server 5.0 oder h&ouml;her</td>
                                	<td>&gt;= 5.0</td>
                                	<td>?</td>
                                </tr>
                           </tbody>
                        </table>
                    	<a class="btn btn-default" href="index.php?p=terms_of_use">zur&uuml;ck</a>
                    	<?php if($install->condition){ ?>
                        	<a class="btn btn-success" href="index.php?p=step2">Weiter</a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>