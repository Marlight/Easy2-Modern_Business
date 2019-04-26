CREATE TABLE `[prefix]_ml_user` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(30) NOT NULL DEFAULT '',
  `password` varchar(128) NOT NULL DEFAULT '',
  `email` varchar(60) NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `rank` int(10) NOT NULL DEFAULT '1',
  `regdate` varchar(40) NOT NULL DEFAULT '0',
  `first_name` varchar(64) NOT NULL DEFAULT '',
  `last_name` varchar(64) NOT NULL DEFAULT '',
  `uik` varchar(32) NOT NULL DEFAULT '',
  `avatar` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
INSERT INTO `[prefix]_ml_user` (`id`, `username`, `password`, `email`, `active`, `rank`, `regdate`, `first_name`, `last_name`, `uik`, `avatar`) VALUES 
(1, 'System', '', '', 0, 1, '', 'System', '', '3A2xdfRKw5k6IqptThiZSFXbT5J0oELO', ''), 
(2, 'Gast', '', '', 0, 1813201542, '', 'Gast', '', '3A2xdfRKw9l6IqptThiZSFXbT5J0oELO', '');
ALTER TABLE `[prefix]_ml_user` ADD PRIMARY KEY (`id`);
ALTER TABLE `[prefix]_ml_user` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;