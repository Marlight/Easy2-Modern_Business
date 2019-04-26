CREATE TABLE `[prefix]_ml_fields` (
  `id` bigint(16) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT 'Name in HTML',
  `title` varchar(64) NOT NULL DEFAULT '' COMMENT 'Für Benutzer sichtbar',
  `type` varchar(64) NOT NULL DEFAULT '' COMMENT 'Feldtyp',
  `placeholder` varchar(128) NOT NULL DEFAULT '',
  `maxlength` int(11) NOT NULL DEFAULT '0',
  `required` int(1) NOT NULL DEFAULT '0' COMMENT 'Pflichtfeld?',
  `value` varchar(1024) NOT NULL DEFAULT '' COMMENT 'Standard Wert',
  `description` text NOT NULL DEFAULT '' COMMENT 'Beschreibung',
  `options` text NOT NULL DEFAULT '' COMMENT 'Optionen (Bei select, radio, checkbox)',
  `pos` int(11) NOT NULL DEFAULT '0' COMMENT 'position',
  `regist` int(1) NOT NULL DEFAULT '0' COMMENT 'bei der Registrierung anzeigen?',
  `regex` text NOT NULL DEFAULT '',
  `regex_options` varchar(8) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Zusätzliche Felder für das Profil';

ALTER TABLE `[prefix]_ml_fields`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `[prefix]_ml_fields`
  MODIFY `id` bigint(16) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;