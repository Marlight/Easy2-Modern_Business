CREATE TABLE `[prefix]_ml_menu_group` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `[prefix]_ml_menu_group` (`id`, `name`) VALUES
(1, 'Hauptmen&uuml;');

ALTER TABLE `[prefix]_ml_menu_group`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `[prefix]_ml_menu_group`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;