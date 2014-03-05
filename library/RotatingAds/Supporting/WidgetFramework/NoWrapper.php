<?php
class RotatingAds_Supporting_WidgetFramework_NoWrapper extends RotatingAds_Supporting_WidgetFramework_Abstract
{
	protected function _getConfiguration()
	{
		$config = parent::_getConfiguration();
		$config['name'] .= ' (No Wrapper)';
		$config['useWrapper'] = false;
		return $config;
	}

}
