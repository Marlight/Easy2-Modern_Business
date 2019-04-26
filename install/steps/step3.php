        <div id="page-content-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1>Installation - Step 3</h1>
                        <ol class="breadcrumb pull-right">
                            <li><a href="index.php">Einleitung</a></li>
                            <li><a href="index.php?p=terms_of_use">Nutzungsbedingungen</a></li>
                            <li><a href="index.php?p=step1">Step 1 - Voraussetzungen</a></li>
                            <li><a href="index.php?p=step2">Step 2 - MySQL-Daten</a></li>
                            <li class="active actual">Step 3 - Grundeinstellungen</li>
                            <li class="active">Step 4 - Accounts</li>
                         	<li class="active">Fertig</li>
              	        </ol>
                    </div>
				</div>
				<?php echo $error; ?>
                <div class="row">
                	<div class="col-sm-12">
                    	<div class="panel panel-primary">
                        	<div class="panel-heading">
                            	<i class="fa fa-cogs"></i> Grundeinstellungen
                            </div>
                            <div class="panel-body">
                                <form action="index.php?p=step3&c=mainsettings" method="post">
									<div class="form-group">
										<div class="row">
											<div class="col-sm-6">
												<label for="title">Seitentitel:</label>
												<div class="form-group input-group">
													<span class="input-group-addon"><i class="fa fa-fw fa-font fa-fw"></i></span>
													<input type="text" name="title" require class="form-control" value="<?php echo isset($_POST['title']) ? $_POST['title'] : getMainData('site_title'); ?>" id="title" maxlength="64" placeholder="Seitentitel">
												</div>
											</div>
											<div class="col-sm-6"><br>
												Gebe deiner Seite einen Namen. Dieser wird auf der Webseite, im Browser-Tab sowie in den Mails als Absender angezeigt.
											</div>
										</div>
									</div>
									<hr>
									<div class="form-group">
										<div class="row">
											<div class="col-sm-6">
												<label for="title_short">Seitentitel (kurz):</label>
												<div class="form-group input-group">
													<span class="input-group-addon"><i class="fa fa-fw fa-font fa-fw"></i></span>
													<input type="text" name="title_short" require class="form-control" value="<?php echo isset($_POST['title_short']) ? $_POST['title_short'] : getMainData('short_site_title'); ?>" id="title_short" maxlength="16" placeholder="Seitentitel (kurz)">
												</div>
											</div>
											<div class="col-sm-6">
												Dies erf&uuml;llt den gleichen Zweck wie der "Seitentitel (lang)", nur das falls dieser zu lang f&uuml;r manche Felder ist, kann diese Kurzform verwendet werden.
											</div>
										</div>
									</div>
									<hr>
									<div class="form-group">
										<div class="row">
											<div class="col-sm-6">
												<label for="email">Administrator E-Mail:</label>
												<div class="form-group input-group">
													<span class="input-group-addon"><i class="fa fa-fw fa-envelope fa-fw"></i></span>
													<input type="email" name="email" require class="form-control" value="<?php echo isset($_POST['email']) ? $_POST['email'] : getMainData('administrator_mail'); ?>" id="email" maxlength="128" placeholder="Administrator E-Mail">
												</div>
											</div>
											<div class="col-sm-6">
												An diese E-Mail werden <strong>Fehler-E-Mails</strong> gesendet. Diese teilen mit wenn auf der Webseite (meist MySQL-) Fehler auftreten, mit der Position und der Fehlermeldung.
											</div>
										</div>
									</div>
									<hr>
									<div class="form-group">
										<div class="row">
											<div class="col-sm-6">
												<label for="from">Absender E-Mail: (System E-Mails)</label>
												<div class="form-group input-group">
													<span class="input-group-addon"><i class="fa fa-fw fa-envelope fa-fw"></i></span>
													<input type="email" name="from" require class="form-control" value="<?php echo isset($_POST['from']) ? $_POST['from'] : getMainData('mail_sender'); ?>" id="from" maxlength="128" placeholder="Sender E-Mail">
												</div>
											</div>
											<div class="col-sm-6"><br>
												Diese E-Mail wird als Absender von System-E-Mails verwendet. Als Absender wird der Seitentitel angezeigt.
											</div>
										</div>
									</div>
									<hr>
									<div class="form-group">
										<div class="row">
											<div class="col-sm-6">
												<label for="to">Empf&auml;nger E-Mail: (Antworten auf System E-Mails)</label>
												<div class="form-group input-group">
													<span class="input-group-addon"><i class="fa fa-fw fa-envelope fa-fw"></i></span>
													<input type="email" name="to" require class="form-control" value="<?php echo isset($_POST['to']) ? $_POST['to'] : getMainData('mail_receiver'); ?>" id="to" maxlength="128" placeholder="Empf&auml;nger E-Mail">
												</div>
											</div>
											<div class="col-sm-6"><br>
												Diese E-Mail wird als "Reply-to" E-Mail gestzt, was bedeutet, dass auf diese E-Mail vom Benutzer geantwortet wird.
											</div>
										</div>
									</div>
									<hr>
									<h4>Login &amp; Registration</h4>
									<hr>
									<div class="form-group">
										<div class="row">
											<div class="col-sm-6">
												<label for="regist_ac">Registration:</label>
												<div class="form-group input-group">
													<span class="input-group-addon"><i class="fa fa-fw fa-edit"></i></span>
													<?php $regist_ac = isset($_POST['regist_ac']) ? $_POST['regist_ac'] : getMainData('regist_active'); ?>
													<select name="regist_ac" id="regist_ac" class="form-control">
														<option <?php echo checker($regist_ac, 1, 0); ?> value="1">aktiviert</option>
														<option <?php echo checker($regist_ac, 0, 0); ?> value="0">deaktiviert</option>
													</select>
												</div>
											</div>
											<div class="col-sm-6"><br>
												Hier kannst du ganz einfach die Registration de- oder aktivieren. Wenn diese deaktiviert ist, wird der Men&uuml;punkt nicht mehr angezeigt.
											</div>
										</div>
									</div>
									<hr>
									<div class="form-group">
										<div class="row">
											<div class="col-sm-6">
												<label for="reg_mode">Neu registrierte Benutzer:</label>
												<div class="form-group input-group">
													<span class="input-group-addon"><i class="fa fa-fw fa-check"></i></span>
													<?php $reg_mode = isset($_POST['reg_mode']) ? $_POST['reg_mode'] : getMainData('user_activation_mode'); ?>
													<select name="reg_mode" id="reg_mode" class="form-control">
														<option <?php echo checker($reg_mode, 2, 0); ?> value="2">m&uuml;ssen durch den den Admin freigeschaltet werden</option>
														<option <?php echo checker($reg_mode, 1, 0); ?> value="1">m&uuml;ssen sich mit einem Link freischalten</option>
														<option <?php echo checker($reg_mode, 0, 0); ?> value="0">sind sofort freigeschaltet</option>
													</select>
												</div>
											</div>
											<div class="col-sm-6"><br>
												Gebe an welchen Zustand (Aktiv: Ja/Nein) ein Konto nach der Registrierung erh&auml;lt.
											</div>
										</div>
									</div>
									<hr>
									<div class="form-group">
										<div class="row">
											<div class="col-sm-6">
												<label for="pwlength">Passwort min. L&auml;nge:</label>
												<div class="form-group input-group">
													<span class="input-group-addon"><i class="fa fa-fw fa-lock"></i></span>
													<input type="number" class="form-control" id="pwlength" name="pwlength" placeholder="PW min. length" min="3" max="32" maxlength="2" value="<?php echo isset($_POST['password_length']) ? $_POST['password_length'] : getMainData('password_length'); ?>">
												</div>
											</div>
											<div class="col-sm-6">
												Hier muss die L&auml;nge angegeben werden, wie lang ein Passwort ein muss. Die maximale L&auml;nge eines Passwortes darf 64 Zeichen nicht &uuml;berschreiten. 
												Alle Zeichen dar&uuml;ber werden abgeschnitten.
											</div>
										</div>
									</div>
									<hr>
									<div class="form-group">
										<div class="row">
											<div class="col-sm-6">
												<label for="pwv_ac">Passwort vergessen Formular:</label>
												<div class="form-group input-group">
													<span class="input-group-addon"><i class="fa fa-fw fa-edit"></i></span>
													<?php $pwv_ac = isset($_POST['pwv_ac']) ? $_POST['pwv_ac'] : getMainData('pwv_active'); ?>
													<select name="pwv_ac" id="pwv_ac" class="form-control">
														<option <?php echo checker($pwv_ac, 1, 0); ?> value="1">aktiviert</option>
														<option <?php echo checker($pwv_ac, 0, 0); ?> value="0">deaktiviert</option>
													</select>
												</div>
											</div>
											<div class="col-sm-6"><br>
												Hier kannst du ganz einfach das Passwort vergessen Formular de- oder aktivieren.
											</div>
										</div>
									</div>
									<hr>
									<div class="form-group">
										<div class="row">
											<div class="col-sm-6">
												<label for="useradministration_share">Benutzer informieren?</label>
												<div class="form-group input-group">
													<span class="input-group-addon"><i class="fa fa-fw fa-user fa-fw"></i></span>
													<select class="form-control" name="useradministration_share" id="useradministration_share">
														<?php $user_share = isset($_POST['useradministration_share']) ? $_POST['useradministration_share'] : getMainData('useradministration_share'); ?>
														<option <?php echo checker($user_share, 1, 0); ?> value="1">Ja</option>
														<option <?php echo checker($user_share, 0, 0); ?> value="0">Nein</option>
													</select>
												</div>
											</div>
											<div class="col-sm-6"><br>
												Mit dieser Option kann eingestellt werden, ob der Benutzer eine E-Mail erhalten soll, wenn sein Konto de- oder aktiviert wird.
											</div>
										</div>
									</div>
									<hr>
									<div class="form-group">
										<div class="row">
											<div class="col-sm-6">
												<label for="restore">Wiederherstellung von Benutzerdaten:</label>
												<div class="form-group input-group">
													<span class="input-group-addon"><i class="fa fa-fw fa-undo fa-fw"></i></span>
													<select class="form-control" name="restore" id="restore">
														<?php $restore = isset($_POST['restore']) ? $_POST['restore'] : getMainData('restore'); ?>
														<option <?php echo checker($restore, 0, 0); ?> value="0">Aus</option>
														<option <?php echo checker($restore, 1, 0); ?> value="1">Ein - Nur &Auml;nderungen vom Benutzer selbst</option>
														<option <?php echo checker($restore, 2, 0); ?> value="2">Ein - Alle &Auml;nderungen, auch von Admins</option>
													</select>
												</div>
											</div>
											<div class="col-sm-6">
												Soll die Wiederherstellung von Benutzerdaten m&ouml;glich sein? Diese Option kann ganz ausgeschaltet werden, 
												teilweise (Benutzer k&ouml;nnen Ihre eigenen &Auml;nderungen r&uuml;ckg&auml;ngig gemacht werden) eingeschaltet
												oder ganz eingeschaltet. Bei der letzten Option, 
												hat der Benutzer die M&ouml;glichkeit auch &Auml;nderungen von Admin's/Supportern o.&auml;. 
												r&uuml;ckg&auml;ngig zu machen (Au&szlig;nahme: Rang&auml;nderungen).
											</div>
										</div>
									</div>
									<hr>
									<div class="form-group">
										<div class="row">
											<div class="col-sm-6">
												<label for="cookie_lifetime">"Eingeloggt bleiben" Luftzeit (in Sekunden):</label>
												<div class="form-group input-group">
													<span class="input-group-addon"><i class="fa fa-fw fa-clock-o"></i></span>
													<input type="number" class="form-control" id="cookie_lifetime" name="cookie_lifetime" placeholder="Zeit in Sekunden" min="86400" max="9999999999999999" maxlength="16" value="<?php echo isset($_POST['cookie_lifetime']) ? $_POST['cookie_lifetime'] : getMainData('cookielifetime'); ?>">
												</div>
												<div id="cookietime">entspricht: <?php echo number_format(getMainData('cookielifetime') / 86400, 2, ',', '.');?> Tagen</div>
											</div>
											<div class="col-sm-6"><br>
												Bei dieser Option wird angegeben, wie lange die Benutzer mit hilfe der Funktion "Eingeloggt bleiben", eingeloggt bleiben k&ouml;nnen.
												Diese wird in Sekunden angegeben. Standard sind 90 Tage => 7776000 Sekunden.
											</div>
										</div>
									</div>
									<hr>
									<div class="form-group">
										<div class="row">
											<div class="col-sm-6">
												<input type="reset" class="btn btn-md btn-warning btn-block" value="Abbrechen">
											</div>
											<div class="col-sm-6">
												<input type="submit" class="btn btn-md btn-success btn-block" value="Speichern">
											</div>
										</div>
									</div>
                                </form>
                       		</div>
                        </div>
                    </div>
				</div>
            </div>
        </div>