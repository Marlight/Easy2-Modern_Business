CREATE TABLE `[prefix]_ml_changes` (
  `id` int(16) UNSIGNED NOT NULL,
  `code` varchar(32) NOT NULL DEFAULT '' COMMENT 'Wiederherstellungscode',
  `timestamp` bigint(16) NOT NULL DEFAULT '0' COMMENT 'Wann Änderung vorgenommen wurde',
  `coloum` varchar(32) NOT NULL DEFAULT '' COMMENT 'Welche Spalte geändert wurde',
  `value` varchar(1024) NOT NULL DEFAULT '' COMMENT 'Alter Wert',
  `author` int(16) NOT NULL DEFAULT '0' COMMENT 'Wer Änderung vorgenommen hat',
  `changed` bigint(16) NOT NULL DEFAULT '0' COMMENT 'Timetsamp andem es Rückgeändert wurde',
  `user` int(16) NOT NULL DEFAULT '0' COMMENT 'Bezug zum User'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
ALTER TABLE `[prefix]_ml_changes` ADD PRIMARY KEY (`id`);
ALTER TABLE `[prefix]_ml_changes` MODIFY `id` int(16) UNSIGNED NOT NULL AUTO_INCREMENT;