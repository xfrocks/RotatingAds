<?php
class RotatingAds_Supporting_WidgetFramework_UseWrapper extends RotatingAds_Supporting_WidgetFramework_Abstract {
	protected function _getConfiguration() {
		$config = parent::_getConfiguration();
		$config['name'] .= ' (Use Wrapper)';
		return $config;
	}
}