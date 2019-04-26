CREATE TABLE `[prefix]_ml_menu` (
  `id` bigint(16) UNSIGNED NOT NULL,
  `sid` bigint(16) NOT NULL DEFAULT '0' COMMENT 'Site ID',
  `title` varchar(64) NOT NULL DEFAULT '' COMMENT 'Titel',
  `icon` varchar(32) NOT NULL DEFAULT '' COMMENT 'Icon',
  `pos` int(11) NOT NULL DEFAULT '0' COMMENT 'Position',
  `url` varchar(1024) NOT NULL DEFAULT '' COMMENT 'URL für externe Links',
  `under` int(11) NOT NULL DEFAULT '0' COMMENT 'Untergeordnet (ID vom Dropdownlink)',
  `menu` int(11) NOT NULL DEFAULT '1' COMMENT 'Welches Menu untergeordnet',
  `link_type` int(1) NOT NULL DEFAULT '0' COMMENT '0 = neutral / 1 = eingeloggt nicht sichtbar',
  `target` varchar(8) NOT NULL DEFAULT '_self'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `[prefix]_ml_menu` (`id`, `sid`, `title`, `icon`, `pos`, `url`, `under`, `menu`, `link_type`, `target`) VALUES
(1, 2, 'Home', '', 0, '', 0, 1, 0, '_self'),
(2, 5, 'Service', '', 1, '', 0, 1, 0, '_self'),
(3, 9, '&Uuml;ber uns', '', 2, '', 0, 1, 0, '_self'),
(17, 29, 'Login', '', 0, '', 19, 1, 1, '_self'),
(7, 0, '', 'fa-user', 7, '', 0, 1, 0, '_self'),
(8, 4, 'Mein Profil', 'fa-user', 0, '', 7, 1, 0, '_self'),
(9, 0, 'Sperren', 'fa-lock', 1, './?c=lock&csrf=[csrf]', 7, 1, 0, '_self'),
(10, 0, 'Abmelden', 'fa-power-off', 2, './?c=logout&csrf=[csrf]', 7, 1, 0, '_self'),
(11, 0, '', 'fa-cogs', 8, '', 0, 1, 0, '_self'),
(12, 7, 'Benutzer verwalten', 'fa-users', 0, '', 11, 1, 0, '_self'),
(13, 6, 'Einstellungen', 'fa-cog', 1, '', 11, 1, 0, '_self'),
(14, 1, 'R&auml;nge verwalten', 'fa-university', 3, '', 11, 1, 0, '_self'),
(15, 11, 'Regeln verwalten', 'fa-key', 4, '', 11, 1, 0, '_self'),
(16, 12, 'Seiten verwalten', 'fa-files-o', 5, '', 11, 1, 0, '_self'),
(18, 30, 'Men&uuml; verwalten', 'fa-align-left', 2, '', 11, 1, 0, '_self'),
(19, 0, 'Anmelden', '', 9, '', 0, 1, 1, '_self'),
(20, 14, 'Registrieren', '', 1, '', 19, 1, 1, '_self'),
(24, 8, 'Kontakt', '', 4, '', 0, 1, 0, '_self'),
(25, 0, 'Portfolio', '', 5, '', 0, 1, 0, '_self'),
(26, 15, '1 Column Portfolio', '', 0, '', 25, 1, 0, '_self'),
(27, 16, '2 Column Portfolio', '', 1, '', 25, 1, 0, '_self'),
(28, 17, '3 Column Portfolio', '', 2, '', 25, 1, 0, '_self'),
(29, 18, '4 Column Portfolio', '', 3, '', 25, 1, 0, '_self'),
(30, 19, 'Single Portfolio Item', '', 4, '', 25, 1, 0, '_self'),
(31, 0, 'Blog', '', 3, '', 0, 1, 0, '_self'),
(32, 0, 'Weiteres', '', 6, '', 0, 1, 0, '_self'),
(33, 21, 'Blog Home 1', '', 0, '', 31, 1, 0, '_self'),
(34, 22, 'Blog Home 2', '', 1, '', 31, 1, 0, '_self'),
(35, 23, 'Blog Post', '', 2, '', 31, 1, 0, '_self'),
(36, 24, 'Full Width Page', '', 2, '', 32, 1, 0, '_self'),
(37, 25, 'Sidebar Page', '', 4, '', 32, 1, 0, '_self'),
(38, 26, 'FAQ', '', 1, '', 32, 1, 0, '_self'),
(39, 28, '404', '', 0, '', 32, 1, 0, '_self'),
(40, 27, 'Pricing Table', '', 3, '', 32, 1, 0, '_self'),
(43, 3, 'Impressum', '', 5, '', 32, 1, 0, '_self'),
(44, 36, 'Datenschutz', 'fa-shield', 6, '', 32, 1, 0, '_self');

ALTER TABLE `[prefix]_ml_menu`
  ADD PRIMARY KEY (`id`);
  
ALTER TABLE `[prefix]_ml_menu`
  MODIFY `id` bigint(16) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;
COMMIT;