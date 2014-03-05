<?php
class RotatingAds_ViewAdmin_List extends XenForo_ViewAdmin_Base
{
	public function renderHtml()
	{
		$this->_params['itemsGrouped'] = array();
		foreach ($this->_params['items'] as $itemId => &$item)
		{
			$this->_params['itemsGrouped'][$item['position_original']]['title'] = RotatingAds_ViewAdmin_Helper::positionTitle($item['position_original']);
			$this->_params['itemsGrouped'][$item['position_original']]['positionSafe'] = preg_replace('/[^0-9a-zA-Z_]/', '', $item['position_original']);
			$this->_params['itemsGrouped'][$item['position_original']]['items'][$itemId] = &$this->_params['items'][$itemId];
		}
	}

}
