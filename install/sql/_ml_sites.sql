CREATE TABLE `[prefix]_ml_sites` (
  `id` bigint(16) NOT NULL,
  `filename` varchar(64) NOT NULL DEFAULT '' COMMENT 'Dateiname',
  `dir` varchar(128) NOT NULL DEFAULT '' COMMENT 'Verzeichnis',
  `title` varchar(32) NOT NULL DEFAULT '' COMMENT 'Seitentitle',
  `start_site` bigint(16) NOT NULL DEFAULT '0' COMMENT 'Startseite',
  `start_site_login` bigint(16) NOT NULL DEFAULT '0' COMMENT 'Startseite nach login',
  `errorsite` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Ist dies die Errorseite? 1 = yes',
  `type` varchar(16) NOT NULL DEFAULT 'php',
  `logout_site` int(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `[prefix]_ml_sites` (`id`, `filename`, `dir`, `title`, `start_site`, `start_site_login`, `errorsite`, `type`, `logout_site`) VALUES
(1, 'ranks', 'adm/', 'R&auml;nge verwalten', 0, 0, 0, 'php', 0),
(2, 'home', '', 'Startseite', 1, 1, 0, 'php', 0),
(3, 'impressum', '', 'Impressum', 0, 0, 0, 'php', 0),
(4, 'profil', 'login/', 'Profil', 0, 0, 0, 'php', 0),
(5, 'services', 'bootstrap/', 'Services', 0, 0, 0, 'php', 0),
(6, 'settings', 'adm/', 'Einstellungen', 0, 0, 0, 'php', 0),
(7, 'userlist', 'adm/', 'Benutzer verwalten', 0, 0, 0, 'php', 0),
(8, 'contact', 'bootstrap/', 'Kontakt', 0, 0, 0, 'php', 0),
(9, 'about', 'bootstrap/', 'About', 0, 0, 0, 'php', 0),
(10, 'locked', 'login/', 'Gesperrt', 0, 0, 0, 'php', 0),
(11, 'rules', 'adm/', 'Regeln verwalten', 0, 0, 0, 'php', 0),
(12, 'sites', 'adm/', 'Seiten verwalten', 0, 0, 0, 'php', 0),
(13, 'pwv', 'login/', 'Passwort vergessen', 0, 0, 0, 'php', 0),
(14, 'regist', 'login/', 'Registrieren', 0, 0, 0, 'php', 0),
(15, 'portfolio-1-col', 'bootstrap/', '1 Column Portfolio', 0, 0, 0, 'php', 0),
(16, 'portfolio-2-col', 'bootstrap/', '2 Column Portfolio', 0, 0, 0, 'php', 0),
(17, 'portfolio-3-col', 'bootstrap/', '3 Column Portfolio', 0, 0, 0, 'php', 0),
(18, 'portfolio-4-col', 'bootstrap/', '4 Column Portfolio', 0, 0, 0, 'php', 0),
(19, 'portfolio-item', 'bootstrap/', 'Single Portfolio Item', 0, 0, 0, 'php', 0),
(28, '404', '', '404', 0, 0, 1, 'php', 0),
(21, 'blog-home-1', 'bootstrap/', 'Blog Home 1', 0, 0, 0, 'php', 0),
(22, 'blog-home-2', 'bootstrap/', 'Blog Home 2', 0, 0, 0, 'php', 0),
(23, 'blog-post', 'bootstrap/', 'Blog Post', 0, 0, 0, 'php', 0),
(24, 'full-width', 'bootstrap/', 'Full Width Page', 0, 0, 0, 'php', 0),
(25, 'sidebar', 'bootstrap/', 'Sidebar Page', 0, 0, 0, 'php', 0),
(26, 'faq', 'bootstrap/', 'FAQ', 0, 0, 0, 'php', 0),
(27, 'pricing', 'bootstrap/', 'Pricing Table', 0, 0, 0, 'php', 0),
(29, 'login', 'login/', 'Anmeldung', 0, 0, 0, 'php', 1),
(30, 'menu', 'adm/', 'Men&uuml;verwaltung', 0, 0, 0, 'php', 0),
(34, 'additional_fields', 'adm/', 'Zusatzfelder', 0, 0, 0, 'php', 0),
(35, 'pw_reset', 'login/', 'Passwort zur&uuml;cksetzen', 0, 0, 0, 'php', 0),
(36, 'privacy_policy', '', 'Datenschutz', 0, 0, 0, 'php', 0);

ALTER TABLE `[prefix]_ml_sites`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `[prefix]_ml_sites`
  MODIFY `id` bigint(16) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;
COMMIT;