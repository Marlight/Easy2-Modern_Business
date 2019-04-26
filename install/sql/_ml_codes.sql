CREATE TABLE `[prefix]_ml_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `uik` varchar(32) NOT NULL DEFAULT '' COMMENT 'Benutzer',
  `code` varchar(32) NOT NULL DEFAULT '' COMMENT 'Code',
  `action` varchar(128) NOT NULL DEFAULT '' COMMENT 'Aktion',
  `expiry_date` bigint(16) NOT NULL DEFAULT '0' COMMENT 'Timestamp des verfalls | 0 = nie'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
ALTER TABLE `[prefix]_ml_codes` ADD PRIMARY KEY (`id`);
ALTER TABLE `[prefix]_ml_codes` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;