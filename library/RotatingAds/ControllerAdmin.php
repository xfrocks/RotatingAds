<?php
class RotatingAds_ControllerAdmin extends XenForo_ControllerAdmin_Abstract
{
	public function actionIndex()
	{
		$model = $this->_getModel();
		$items = $model->getItems();

		// get position ads from items
		// since 1.2
		$positionAds = array();
		foreach ($items as $item)
		{
			if (!isset($positionAds[$item['position']]))
			{
				$positionAds[$item['position_original']] = $model->getPositionAds($item['position_original']);
			}
		}

		$viewParams = array(
			'items' => $items,
			'positionAds' => $positionAds,
		);

		return $this->responseView('RotatingAds_ViewAdmin_List', 'rotating_ads_list', $viewParams);
	}

	public function actionAdd()
	{
		$item = array(
			'position_relative' => RotatingAds_DataWriter::POSITION_RELATIVE_BELOW,
			'options' => array('user_criteria' => array( array(
						'rule' => 'not_user_groups',
						'data' => array('user_group_ids' => array(
								3,
								4,
							))
					)))
		);

		return $this->_actionAddEdit($item);
	}

	public function actionEdit()
	{
		$itemId = $this->_input->filterSingle('item_id', XenForo_Input::UINT);
		$item = $this->_getItemOrError($itemId);

		return $this->_actionAddEdit($item);
	}

	protected function _actionAddEdit(array $item)
	{
		$userCriteria = array();
		if (!empty($item['options']['user_criteria']))
		{
			$userCriteria = $item['options']['user_criteria'];
		}
		if (!empty($item['options']['user_groups']))
		{
			$userCriteriaUserGroupsFound = false;
			foreach ($userCriteria as $rule)
			{
				if ($rule['rule'] == 'user_groups')
				{
					$userCriteriaUserGroupsFound = true;
				}
			}

			if (!$userCriteriaUserGroupsFound)
			{
				// user criteria array doesn't have user_groups rule yet
				// and we have an old user_groups option from old version
				// merge them together
				$userCriteria[] = array(
					'rule' => 'user_groups',
					'data' => array('user_group_ids' => $item['options']['user_groups']),
				);
			}
		}

		$viewParams = array(
			'item' => $item,
			'availablePositions' => $this->_getModel()->getAvailablePositions(),
			'availablePositionRelatives' => $this->_getModel()->getAvailablePositionRelatives(),
			'type' => 'link',

			'userCriteria' => XenForo_Helper_Criteria::prepareCriteriaForSelection($userCriteria),
			'userCriteriaData' => XenForo_Helper_Criteria::getDataForUserCriteriaSelection(),
		);

		if (!empty($item['link']) AND $item['link'] == 'slider')
		{
			// this is slider type
			$viewParams['type'] = 'slider';
			$viewParams['slider'] = unserialize($item['html']);
			$viewParams['item']['html'] = '';
			$viewParams['item']['link'] = '';
		}

		return $this->responseView('RotatingAds_ViewAdmin_Edit', 'rotating_ads_edit', $viewParams);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$itemId = $this->_input->filterSingle('item_id', XenForo_Input::UINT);

		$dwInput = $this->_input->filter(array(
			'name' => XenForo_Input::STRING,
			'position' => XenForo_Input::STRING,
			'link' => XenForo_Input::STRING,
			'html' => XenForo_Input::STRING,
			'expire_date' => XenForo_Input::DATE_TIME,
			'options' => XenForo_Input::ARRAY_SIMPLE,
		));

		$userCriteria = $this->_input->filterSingle('user_criteria', XenForo_Input::ARRAY_SIMPLE);
		$dwInput['options']['user_criteria'] = XenForo_Helper_Criteria::prepareCriteriaForSave($userCriteria);

		$extraInput = $this->_input->filter(array(
			'type' => XenForo_Input::STRING,
			'slider' => XenForo_Input::ARRAY_SIMPLE,
		));

		if (empty($dwInput['position']))
		{
			// process custom position
			// since 1.2
			$dwInput['position'] = $this->_input->filterSingle('position_custom', XenForo_Input::STRING);
			// support position relative
			// since 1.3
			if (!empty($dwInput['position']))
			{
				$positionRelative = $this->_input->filterSingle('position_relative', XeNForo_Input::STRING);
				if (!empty($positionRelative))
				{
					$dwInput['position'] .= RotatingAds_DataWriter::POSITION_SEPARATOR . $positionRelative;
				}
			}
		}

		// check for expire date
		// since 1.2
		if ($this->_input->filterSingle('has_expire_date', XenForo_Input::UINT))
		{
			// allow expire date
			// nothing to do here
		}
		else
		{
			// reset expire date in input if any
			$dwInput['expire_date'] = 0;
		}

		// check for slider
		// since 1.4
		if ($extraInput['type'] == 'slider' AND !empty($extraInput['slider']))
		{
			foreach (array_keys($extraInput['slider']['slides']) as $key)
			{
				if (empty($extraInput['slider']['slides'][$key]['link']) OR empty($extraInput['slider']['slides'][$key]['image']))
				{
					unset($extraInput['slider']['slides'][$key]);
				}
			}

			// sort the slider
			// since 1.5
			usort($extraInput['slider']['slides'], create_function('$a, $b', 'return $a["order"] > $b["order"] ? 1 : -1;'));

			$extraInput['slider']['slides'] = array_values($extraInput['slider']['slides']);
			$dwInput['html'] = serialize($extraInput['slider']);
			$dwInput['link'] = 'slider';
		}

		$dw = XenForo_DataWriter::create('RotatingAds_DataWriter');
		if ($itemId)
		{
			$dw->setExistingData($itemId);
		}
		$dw->bulkSet($dwInput);
		$dw->save();

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('rotating-ads'));
	}

	public function actionDelete()
	{
		$itemId = $this->_input->filterSingle('item_id', XenForo_Input::UINT);
		$item = $this->_getItemOrError($itemId);

		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('RotatingAds_DataWriter');
			$dw->setExistingData($itemId);
			$dw->delete();

			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('rotating-ads'));
		}
		else
		{
			$viewParams = array('item' => $item);

			return $this->responseView('RotatingAds_ViewAdmin_Delete', 'rotating_ads_delete', $viewParams);
		}
	}

	public function actionIncrease()
	{
		return $this->_actionIncreaseDecrease(1);
	}

	public function actionDecrease()
	{
		return $this->_actionIncreaseDecrease(-1);
	}

	protected function _actionIncreaseDecrease($delta)
	{
		$position = $this->_input->filterSingle('string', XenForo_Input::STRING);

		// support shift and ctrl keys
		// since 1.2
		$keys = $this->_input->filter(array(
			'shiftKey' => XenForo_Input::UINT,
			'ctrlKey' => XenForo_Input::UINT,
		));
		if ($keys['shiftKey'])
		{
			$delta *= 10;
		}
		elseif ($keys['ctrlKey'])
		{
			$delta *= 3;
		}

		$model = $this->_getModel();

		// no longer check for available position
		// since 1.2
		$model->setPositionAds($position, $model->getPositionAds($position) + $delta);

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CREATED, XenForo_Link::buildAdminLink('rotating-ads'), new XenForo_Phrase('rotating_ads_maximum_ads_saved'), array('updatedNumber' => XenForo_Template_Helper_Core::numberFormat($model->getPositionAds($position)), ));
	}

	public function actionEnable()
	{
		// can be requested over GET, so check for the token manually
		$this->_checkCsrfFromToken($this->_input->filterSingle('_xfToken', XenForo_Input::STRING));

		$itemId = $this->_input->filterSingle('item_id', XenForo_Input::UINT);
		return $this->_actionEnableDisable($itemId, 0);
	}

	public function actionDisable()
	{
		// can be requested over GET, so check for the token manually
		$this->_checkCsrfFromToken($this->_input->filterSingle('_xfToken', XenForo_Input::STRING));

		$itemId = $this->_input->filterSingle('item_id', XenForo_Input::UINT);
		return $this->_actionEnableDisable($itemId, 1);
	}

	protected function _actionEnableDisable($itemId, $newIsDisabled)
	{
		$dw = XenForo_DataWriter::create('RotatingAds_DataWriter');
		$dw->setExistingData($itemId);
		$dw->set('is_disabled', $newIsDisabled);
		$dw->save();

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('rotating-ads'));
	}

	public function actionDuplicate()
	{
		// can be requested over GET, so check for the token manually
		$this->_checkCsrfFromToken($this->_input->filterSingle('_xfToken', XenForo_Input::STRING));

		$itemId = $this->_input->filterSingle('item_id', XenForo_Input::UINT);
		$item = $this->_getItemOrError($itemId);

		$dw = XenForo_DataWriter::create('RotatingAds_DataWriter');
		$dw->set('name', $item['name'] . ' (' . (new XenForo_Phrase('rotating_ads_duplicated_lowercase')) . ')');
		$dw->set('position', $item['position_original']);
		$dw->set('link', $item['link']);
		$dw->set('html', $item['html']);
		$dw->set('expire_date', $item['expire_date']);
		// ignored hit
		$dw->set('is_disabled', $item['is_disabled']);
		$dw->set('options', $item['options']);
		$dw->save();

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('rotating-ads/edit', $dw->getMergedData()));
	}

	public function actionExport()
	{
		$model = $this->_getModel();
		$items = $model->getItems();

		$addOn = $this->getModelFromCache('XenForo_Model_AddOn')->getAddOnById('rotating_ads');

		$this->_routeMatch->setResponseType('xml');

		$viewParams = array(
			'system' => $addOn,
			'items' => $items,
		);

		return $this->responseView('RotatingAds_ViewAdmin_Export', '', $viewParams);
	}

	public function actionImport()
	{
		if ($this->isConfirmedPost())
		{
			$fileTransfer = new Zend_File_Transfer_Adapter_Http();
			if ($fileTransfer->isUploaded('upload_file'))
			{
				$fileInfo = $fileTransfer->getFileInfo('upload_file');
				$fileName = $fileInfo['upload_file']['tmp_name'];
			}
			else
			{
				$fileName = $this->_input->filterSingle('server_file', XenForo_Input::STRING);
			}

			$this->_getModel()->importFromFile($fileName);

			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('rotating-ads'));
		}
		else
		{
			return $this->responseView('RotatingAds_ViewAdmin_Import', 'rotating_ads_import');
		}
	}

	protected function _getItemOrError($itemId)
	{
		$info = $this->_getModel()->getItemById($itemId);
		if (empty($info))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('rotating_ads_item_not_found'), 404));
		}

		return $info;
	}

	protected function _getModel()
	{
		return $this->getModelFromCache('RotatingAds_Model');
	}

}
