<?php
class RotatingAds_DataWriter extends XenForo_DataWriter {
	const POSITION_SEPARATOR = ':';
	const POSITION_RELATIVE_ABOVE = 'above';
	const POSITION_RELATIVE_BELOW = 'below';
	
	protected function _getFields() {
		// PLEASE UPDATE DUPLICATE ACTION IN THE CONTROLLER IF NEW FIELD IS ADDED TO THIS DATAWRITER!
		// ALSO, DO NOT FORGET TO UPDATE THE EXPORT AND IMPORT PROCEDURE
		// since 1.6
		
		return array(
			'xf_rotating_ads_item' => array(
				'item_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'name' => array('type' => self::TYPE_STRING, 'required' => true),
				'position' => array('type' => self::TYPE_STRING, 'required' => true, 'verification' => array('$this', '_verifyPosition')),
				'link' => array('type' => self::TYPE_STRING, 'default' => ''),
				'html' => array('type' => self::TYPE_STRING, 'required' => true, 'verification' => array('$this', '_verifyHtml')),
				'expire_date' => array('type' => self::TYPE_UINT, 'default' => 0, 'verification' => array('$this', '_verifyExpireDate')),
				'hit' => array('type' => self::TYPE_UINT, 'default' => 0),
				// since 1.6
				'is_disabled' => array('type' => self::TYPE_UINT, 'default' => 0),
				'options' => array('type' => self::TYPE_SERIALIZED),
			)
		);
	}

	protected function _getExistingData($data) {
		if (!$itemId = $this->_getExistingPrimaryKey($data, 'item_id')) {
			return false;
		}

		return array('xf_rotating_ads_item' => $this->_getModel()->getItemById($itemId));
	}

	protected function _getUpdateCondition($tableName) {
		return 'item_id = ' . $this->_db->quote($this->getExisting('item_id'));
	}
	
	protected function _postSave() {
		$this->_getModel()->rebuildCache();
	}
	
	protected function _postDelete() {
		$this->_getModel()->rebuildCache();
	}
	
	protected function _verifyPosition($position) {
		if (!empty($position)) {
			$pos = strpos($position, ':');
			if ($pos !== false) {
				// found a separator
				$parts = explode(':', $position);
				if (count($parts) > 2) {
					throw new XenForo_Exception(new XenForo_Phrase('rotating_ads_invalid_position_x', array('position' => $position)), true);
					return false;
				}
				
				$relative = array_pop($parts);
				if ($relative != self::POSITION_RELATIVE_ABOVE && $relative != self::POSITION_RELATIVE_BELOW) {
					throw new XenForo_Exception(new XenForo_Phrase('rotating_ads_invalid_position_relative_x', array('relative' => $relative)), true);
					return false;
				}
			}
		}
		
		return true;
	}
	
	protected function _verifyHtml($html) {
		return true;
	}
	
	protected function _verifyExpireDate($expireDate) {
		if ($expireDate == 0) {
			// no expire date
		} else {
			// some expire date is set
			if ($expireDate < XenForo_Application::$time) {
				// expire date in the past?
				$this->error(new XenForo_Phrase('rotating_ads_expire_date_must_be_in_future'), 'expire_date');
				return false;
			}
		}
		
		return true;
	}
	
	protected function _getModel() {
		return $this->getModelFromCache('RotatingAds_Model');
	}
}