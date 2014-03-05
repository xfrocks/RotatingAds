<?php
class RotatingAds_Installer
{
	public static function install()
	{
		$db = XenForo_Application::get('db');

		$db->query("
			CREATE TABLE IF NOT EXISTS `xf_rotating_ads_item` (
				item_id INT(10) UNSIGNED AUTO_INCREMENT,
				name VARBINARY(255) NOT NULL,
				position VARBINARY(255) NOT NULL,
				link BLOB NOT NULL,
				html BLOB NOT NULL,
				expire_date INT(10) UNSIGNED DEFAULT 0,
				hit INT(10) UNSIGNED DEFAULT 0,
				PRIMARY KEY (item_id),
				KEY (position)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		// since 1.6
		$existed = $db->fetchOne("SHOW COLUMNS FROM `xf_rotating_ads_item` LIKE 'is_disabled'");
		if (empty($existed))
		{
			$db->query("
				ALTER TABLE `xf_rotating_ads_item`
				ADD COLUMN `is_disabled` TINYINT(4) UNSIGNED NOT NULL DEFAULT 0
			");
		}

		// since 1.6 (again)
		$existed = $db->fetchOne("SHOW COLUMNS FROM `xf_rotating_ads_item` LIKE 'options'");
		if (empty($existed))
		{
			$db->query("
				ALTER TABLE `xf_rotating_ads_item`
				ADD COLUMN `options` MEDIUMBLOB
			");
		}
	}

	public static function uninstall()
	{

	}

}
