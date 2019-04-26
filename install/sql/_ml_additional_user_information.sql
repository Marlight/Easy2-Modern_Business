CREATE TABLE `[prefix]_ml_additional_user_information` (
  `id` bigint(16) UNSIGNED NOT NULL,
  `field_id` bigint(16) NOT NULL DEFAULT '0',
  `value` text NOT NULL DEFAULT '',
  `user_id` bigint(16) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `[prefix]_ml_additional_user_information`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `[prefix]_ml_additional_user_information`
  MODIFY `id` bigint(16) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;