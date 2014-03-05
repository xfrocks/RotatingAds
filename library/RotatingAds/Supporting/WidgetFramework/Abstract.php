<?php
abstract class RotatingAds_Supporting_WidgetFramework_Abstract extends WidgetFramework_WidgetRenderer
{
	protected function _getConfiguration()
	{
		return array(
			'name' => 'Rotating Ads: Custom Ads Widget',
			'options' => array('position_code' => XenForo_Input::STRING, ),
		);
	}

	protected function _getOptionsTemplate()
	{
		return 'rotating_ads_widget_options';
	}

	protected function _getRenderTemplate(array $widget, $templateName, array $params)
	{
		return 'rotating_ads_widget';
		// this is not a real template
	}

	protected function _render(array $widget, $templateName, array $params, XenForo_Template_Abstract $renderTemplateObject)
	{
		$contents = '';
		RotatingAds_Engine::getInstance()->work($widget['options']['position_code'], $contents, array(), $renderTemplateObject);

		return $contents;
	}

}
