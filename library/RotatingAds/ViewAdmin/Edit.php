<?php
class RotatingAds_ViewAdmin_Edit extends XenForo_ViewAdmin_Base
{
	public function renderHtml()
	{
		$positions = array();
		foreach ($this->_params['availablePositions'] as $position)
		{
			$positions[] = array(
				'value' => $position,
				'label' => RotatingAds_ViewAdmin_Helper::positionTitle($position),
				'selected' => (!empty($this->_params['item']['position']) AND $this->_params['item']['position'] == $position),
			);
		}
		$this->_params['availablePositions'] = $positions;

		$positionRelatives = array();
		foreach ($this->_params['availablePositionRelatives'] as $positionRelative)
		{
			$positionRelatives[] = array(
				'value' => $positionRelative,
				'label' => RotatingAds_ViewAdmin_Helper::positionRelativeTitle($positionRelative),
				'selected' => (!empty($this->_params['item']['position_relative']) AND $this->_params['item']['position_relative'] == $positionRelative),
			);
		}
		$this->_params['availablePositionRelatives'] = $positionRelatives;
	}

}
