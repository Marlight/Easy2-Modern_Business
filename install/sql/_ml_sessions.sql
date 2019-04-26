CREATE TABLE `[prefix]_ml_sessions` (
  `id` int(16) UNSIGNED NOT NULL,
  `uik` varchar(32) NOT NULL DEFAULT '' COMMENT 'User Identification Key',
  `sic` varchar(32) NOT NULL DEFAULT '' COMMENT 'Session Identification Code',
  `ult` bigint(16) NOT NULL DEFAULT '0' COMMENT 'User Login Time',
  `ulc` varchar(32) NOT NULL DEFAULT '' COMMENT 'User Login Code',
  `last_action` bigint(16) NOT NULL DEFAULT '0',
  `logout` bigint(16) NOT NULL DEFAULT '0',
  `locked` int(1) NOT NULL DEFAULT '0' COMMENT 'Gesperrt',
  `closed` int(1) NOT NULL DEFAULT '0' COMMENT 'Geschlossen',
  `locked_dir` varchar(512) NOT NULL  DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
ALTER TABLE `[prefix]_ml_sessions` ADD PRIMARY KEY (`id`);
ALTER TABLE `[prefix]_ml_sessions` MODIFY `id` int(16) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;