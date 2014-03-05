<?php
class RotatingAds_Listener
{
	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if ($templateName == 'thread_view')
		{
			$template->preloadTemplate('rotating_ads_second_post');
		}
		elseif ($templateName == 'PAGE_CONTAINER')
		{
			$template->preloadTemplate('rotating_ads_slider');
		}
	}

	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		RotatingAds_Engine::getInstance()->work($hookName, $contents, $hookParams, $template);

		if ($hookName == 'ad_message_below')
		{
			if (!defined(RotatingAds_Engine::MESSAGE_BELOW_FIRST_FLAG))
			{
				$contents .= '<!-- ' . RotatingAds_Engine::MESSAGE_BELOW_FIRST_FLAG . ' -->';
			}
		}
	}

	public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
	{
		if ($templateName == 'thread_view')
		{
			$params = $template->getParams();
			$firstPost = $params['firstPost'];
			if ($firstPost['position'] == 0)
			{
				// this is actually the first post of the thread
				// start working
				$pos = strpos($content, RotatingAds_Engine::MESSAGE_BELOW_FIRST_FLAG);
				if ($pos !== false)
				{
					$liPos = strpos($content, '</li>', $pos);
					if ($liPos !== false)
					{
						$ourParams = array('post' => array(
								'user_id' => 0,
								'username' => new XenForo_Phrase('rotating_ads_second_poster'),
								'gender' => '',
							), );
						$ourTemplate = $template->create('rotating_ads_second_post', $ourParams);
						$rendered = $ourTemplate->render();
						$content = substr_replace($content, $rendered, $liPos, 0);
					}
				}
			}
		}
		elseif ($templateName == 'PAGE_CONTAINER')
		{
			if (RotatingAds_Engine::$needsSliderScripts)
			{
				$scripts = <<<EOF
<script type="text/javascript" src="./js/RotatingAds/nivo-slider/jquery.nivo.slider.pack.js"></script>
<link rel="stylesheet" type="text/css" href="./js/RotatingAds/nivo-slider/nivo-slider.css" />
<link rel="stylesheet" type="text/css" href="./js/RotatingAds/nivo-slider/themes/default/default.css" />
EOF;
				$content = str_replace('<!--XenForo_Require:JS-->', '<!--XenForo_Require:JS-->' . $scripts, $content);
			}
		}
	}

	public static function widget_framework_ready(array &$renderers)
	{
		// support Widget Framework
		// since 1.2.1
		$renderers[] = 'RotatingAds_Supporting_WidgetFramework_UseWrapper';
		$renderers[] = 'RotatingAds_Supporting_WidgetFramework_NoWrapper';
	}

	public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
		// support file health check
		// since 1.6.1
		$ourHashes = RotatingAds_FileSums::getHashes();
		$hashes = array_merge($hashes, $ourHashes);
	}

}
