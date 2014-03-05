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

		$this->_params['preparedUserGroups'] = array();
		if (!empty($this->_params['allUserGroups']))
		{
			foreach ($this->_params['allUserGroups'] as $userGroupId => $title)
			{
				$tmp = array(
					'label' => $title,
					'value' => $userGroupId,
				);

				if (!empty($this->_params['item']['options']['user_groups']))
				{
					$tmp['selected'] = in_array($userGroupId, $this->_params['item']['options']['user_groups']);
				}

				$this->_params['preparedUserGroups'][] = $tmp;
			}
		}
	}

}
