CREATE TABLE `[prefix]_ml_rules` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(40) NOT NULL DEFAULT '',
  `tag` varchar(40) NOT NULL DEFAULT '',
  `description` varchar(256) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `[prefix]_ml_rules` (`id`, `name`, `tag`, `description`) VALUES
(1, 'Benutzer bearbeiten', 'user_edit', 'Darf einen Benutzer bearbeiten'),
(2, 'Benutzer l&ouml;schen', 'user_delete', 'Darf einen Benutzer l&ouml;schen'),
(3, 'Benutzer aktivieren', 'user_enable', 'Darf einen Benutzer aktivieren'),
(4, 'Benutzer deaktivieren', 'user_disable', 'Darf einen Benutzer deaktivieren'),
(5, 'Haupteinstellungen', 'mainsave', 'Darf die Haupteinstellungen &auml;ndern'),
(8, 'Benutzer Rang', 'user_rank', 'Darf den Rang der Benutzer &aumlndern'),
(160, 'Men&uuml; Positionen resetten', 'menu_reset_pos', 'Darf die Positionen der Men&uuml;punkte resetten'),
(29, 'Rang hinzuf&uuml;gen', 'rank_new', 'Darf einen Rang hinzuf&uuml;gen, mit maximal den gleichen Berechtigungen'),
(30, 'Rang verschieben', 'rank_move', 'Darf einen Rang verschieben'),
(31, 'Rang entfernen', 'rank_delete', 'Darf einen Rang entfernen'),
(32, 'Rang bearbeiten', 'rank_edit', 'Darf einen Rang bearbeiten'),
(33, 'Standard Rang setzten', 'rank_default', 'Darf den Standard Rang &auml;ndern'),
(37, 'Benutzer Passwort zur&uuml;cksetzen', 'user_pwreset', 'Darf Benutzerpassw&ouml;rter zur&uuml;cksetzen'),
(154, 'Seite hinzuf&uuml;gen', 'site_add', 'Darf eine Seite hinzuf&uuml;gen'),
(155, 'Seite bearbeiten', 'site_edit', 'Darf eine Seite bearbeiten'),
(156, 'Seite l&ouml;schen', 'site_remove', 'Darf eine Seite l&ouml;schen'),
(157, 'Men&uuml;punkt hinzuf&uuml;gen', 'menu_add', 'Darf Men&uuml;punkte hinzuf&uuml;gen'),
(158, 'Men&uuml;punkt bearbeiten', 'menu_edit', 'Darf Men&uuml;punkte bearbeiten'),
(159, 'Men&uuml;punkt l&ouml;schen', 'menu_remove', 'Darf Men&uuml;punkte l&ouml;schen'),
(83, 'Benutzer hinzuf&uuml;gen', 'user_add', 'Darf einen Benutzer hinzuf&uuml;gen'),
(138, 'Benutzer anzeigen', 'user_show', 'Darf Benutzer Informationen einsehen'),
(139, 'Benutzer Profilbild entfernen', 'user_rm_avatar', 'Darf die Benutzer Profilbilder entfernen'),
(164, 'Felder l&ouml;schen', 'fields_remove', 'Darf zus&auml;tzliche Felder zum Profil l&ouml;schen'),
(163, 'Felder bearbeiten', 'fields_edit', 'Darf zus&auml;tzliche Felder zum Profil berabiten'),
(162, 'Felder hinzuf&uuml;gen', 'fields_add', 'Darf zus&auml;tzliche Felder zum Profil hinzuf&uuml;gen'),
(161, 'Men&uuml; L&uuml;cken schlie&szlig;en', 'menu_fill_gaps', 'Draf im Men&uuml; die L&uuml;cken in den Positionsnummern schlie&szlig;en');

ALTER TABLE `[prefix]_ml_rules`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `[prefix]_ml_rules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;
COMMIT;