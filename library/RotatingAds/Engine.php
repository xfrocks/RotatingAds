<?php
class RotatingAds_Engine
{
	protected $_model;
	protected $_items;
	protected $_itemsGrouped;

	public static $needsSliderScripts = false;

	const MESSAGE_BELOW_FIRST_FLAG = 'ROTATING_ADS_MESSAGE_BELOW_FIRST';

	public function __construct()
	{
		$this->_model = XenForo_Model::create('RotatingAds_Model');
		$this->_items = $this->_model->getItemsFromCache();

		$this->_itemsGrouped = array();
		foreach (array_keys($this->_items) as $itemId)
		{
			$item = &$this->_items[$itemId];

			if ($item['expire_date'] > 0 AND $item['expire_date'] < XenForo_Application::$time)
			{
				// expired
				continue;
			}

			if (!empty($item['options']['user_groups']))
			{
				// checks for user groups if something is setup
				// since 1.6
				$visitor = XenForo_Visitor::getInstance();
				$targetThis = false;

				foreach ($item['options']['user_groups'] as $userGroupId)
				{
					if ($visitor->isMemberOf($userGroupId, true))
					{
						$targetThis = true;
					}
				}

				if (!$targetThis)
				{
					// this item doesn't target current user's user group
					continue;
				}
			}
			elseif (!empty($item['options']['user_criteria']))
			{
				// check for user criteria
				// since 2.0
				if (!XenForo_Helper_Criteria::userMatchesCriteria($item['options']['user_criteria']))
				{
					continue;
				}
			}

			$this->_itemsGrouped[$item['position']][$itemId] = true;
		}
	}

	public function work($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		if (!empty($this->_itemsGrouped[$hookName]))
		{
			$used = array();
			$show = min(count($this->_itemsGrouped[$hookName]), $this->_model->getPositionAds($hookName, true));
			for ($i = 0; $i < $show; $i++)
			{
				do
				{
					$itemId = array_rand($this->_itemsGrouped[$hookName], 1);
				}
				while (in_array($itemId, $used));
				$used[] = $itemId;

				$item = &$this->_items[$itemId];

				$href = $item['link'];
				$html = $item['html'];

				// slider mode
				// since 1.4
				if ($href == 'slider')
				{
					$slider = unserialize($html);
					$href = '';
					$sliderTemplate = $template->create('rotating_ads_slider', $template->getParams());
					$sliderTemplate->setParam('slider', $slider);
					$sliderTemplate->setParam('sliderId', 'slider_' . md5($html . $hookName));
					$html = $sliderTemplate->render();
					self::$needsSliderScripts = true;
				}

				if (!empty($item['position_relative']))
				{
					$positionRelative = $item['position_relative'];
				}
				else
				{
					$positionRelative = RotatingAds_DataWriter::POSITION_RELATIVE_BELOW;
				}

				if (!empty($href))
				{
					$html = '<a href="' . htmlspecialchars($href) . '" rel="nofollow" target="_blank">' . $html . '</a>';
				}

				switch ($positionRelative)
				{
					case RotatingAds_DataWriter::POSITION_RELATIVE_ABOVE:
						$contents = $html . $contents;
						break;
					default:
						$contents .= $html;
				}
			}
		}
	}

	public static function & getInstance()
	{
		static $instance = null;

		if ($instance === null)
		{
			$instance = new self();
		}

		return $instance;
	}

}
