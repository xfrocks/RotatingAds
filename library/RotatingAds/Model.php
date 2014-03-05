<?php
class RotatingAds_Model extends XenForo_Model {
	const SIMPLE_CACHE_ITEM_CACHE_KEY = 'rotating_ads_item_cache';
	const SIMPLE_CACHE_POSITION_ADS_KEY = 'rotating_ads_position_ads';
	
	public function importFromFile($fileName) {
		if (!file_exists($fileName) || !is_readable($fileName)) {
			throw new XenForo_Exception(new XenForo_Phrase('please_enter_valid_file_name_requested_file_not_read'), true);
		}

		try {
			$document = new SimpleXMLElement($fileName, 0, true);
		} catch (Exception $e) {
			throw new XenForo_Exception(
				new XenForo_Phrase('provided_file_was_not_valid_xml_file'), true
			);
		}

		if ($document->getName() != 'rotating_ads') {
			throw new XenForo_Exception(new XenForo_Phrase('rotating_ads_provided_file_is_not_an_items_xml_file'), true);
		}
		
		$items = XenForo_Helper_DevelopmentXml::fixPhpBug50670($document->item);
		
		XenForo_Db::beginTransaction();
		
		foreach ($items as $item) {
			$dw = XenForo_DataWriter::create('RotatingAds_DataWriter');
			$dw->set('name', $item['name']);
			$dw->set('position', $item['position']);
			$dw->set('link', $item['link']);
			$dw->set('expire_date', $item['expire_date']);
			$dw->set('is_disabled', $item['is_disabled']);
			
			$dw->set('html', XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($item->html));
			$dw->set('options', unserialize(XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($item->options)));
			
			$dw->save();
		}
		
		XenForo_Db::commit();
	}
	
	public function getItemById($itemId, array $fetchOptions = array()) {
		$items = $this->getItems(array('item_id' => $itemId), $fetchOptions);
		
		return reset($items);
	}
	
	public function getItems(array $conditions = array(), array $fetchOptions = array()) {
		$whereConditions = $this->prepareItemConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareItemOrderOptions($fetchOptions);
		$joinOptions = $this->prepareItemFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$items = $this->fetchAllKeyed($this->limitQueryResults("
				SELECT item.*
					$joinOptions[selectFields]
				FROM `xf_rotating_ads_item` AS item
					$joinOptions[joinTables]
				WHERE $whereConditions
					$orderClause
			", $limitOptions['limit'], $limitOptions['offset']
		), 'item_id');

		foreach ($items as &$item) {
			$this->prepare($item);
		}
		
		return $items;
	}
	
	public function prepareItemConditions(array $conditions, array &$fetchOptions) {
		$sqlConditions = array();
		$db = $this->_getDb();
		
		foreach (array('item_id', 'is_disabled') as $intField) {
			if (!isset($conditions[$intField])) continue;
			
			if (is_array($conditions[$intField])) {
				$sqlConditions[] = "item.$intField IN (" . $db->quote($conditions[$intField]) . ")";
			} else {
				$sqlConditions[] = "item.$intField = " . $db->quote($conditions[$intField]);
			}
		}
		
		if (!empty($conditions['expire_date']) && is_array($conditions['expire_date']))
		{
			list($operator, $cutOff) = $conditions['expire_date'];

			$this->assertValidCutOffOperator($operator);
			
			if ($operator == '>') {
				// accept no expire_date (equals 0) too
				$sqlConditions[] = "(item.expire_date = 0 OR item.expire_date $operator " . $db->quote($cutOff) . ")";
			} else {
				$sqlConditions[] = "item.expire_date $operator " . $db->quote($cutOff);
			}
		}
		
		return $this->getConditionsForClause($sqlConditions);
	}
	
	public function prepareItemFetchOptions(array $fetchOptions) {
		$selectFields = '';
		$joinTables = '';
		
		// to be inserted
		
		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}
	
	public function prepareItemOrderOptions(array &$fetchOptions, $defaultOrderSql = '') {
		$choices = array(
			// to be inserted
		);
		
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}
	
	protected function prepare(&$item) {
		if (is_array($item) AND empty($item['position_original'])) {
			$item['position_original'] = $item['position'];
			$item['position_relative'] = RotatingAds_DataWriter::POSITION_RELATIVE_ABOVE;
			$positionParts = explode(RotatingAds_DataWriter::POSITION_SEPARATOR, $item['position']);
			if (count($positionParts) > 1) {
				$item['position_relative'] = array_pop($positionParts);
				$item['position'] = implode(RotatingAds_DataWriter::POSITION_SEPARATOR, $positionParts);
			}
			
			// since 1.6
			$item['options'] = @unserialize($item['options']);
			if (empty($item['options'])) $item['options'] = array();
		}
	}
	
	public function rebuildCache() {
		$items = $this->getItems(array(
			'is_disabled' => 0, // only get non-disabled items
			'expire_date' => array('>', XenForo_Application::$time), // filter our expired items, this will be checked again in the Engine code
		));
		
		XenForo_Application::setSimpleCacheData(self::SIMPLE_CACHE_ITEM_CACHE_KEY, $items);
	}
	
	public function getItemsFromCache() {
		$items = XenForo_Application::getSimpleCacheData(self::SIMPLE_CACHE_ITEM_CACHE_KEY);
		if (!is_array($items)) {
			$items = $this->getItems();
		}
		
		return $items;
	}
	
	public function getAvailablePositions() {
		return array(
			// 'ad_header',
			'ad_above_top_breadcrumb',
			'ad_below_top_breadcrumb',
			'ad_sidebar_below_visitor_panel',
			'ad_sidebar_top',
			'ad_sidebar_bottom',
		
			'ad_above_content',
			'ad_below_content',

			'ad_forum_view_above_node_list',
			'ad_forum_view_above_thread_list',
			'ad_thread_list_below_stickies',
			'ad_thread_view_above_messages',
			'ad_thread_view_below_messages',
		
			'ad_message_below',
			'ad_message_body',
		
			'ad_special_second_post',
		);
	}
	
	public function getAvailablePositionRelatives() {
		return array(
			RotatingAds_DataWriter::POSITION_RELATIVE_ABOVE,
			RotatingAds_DataWriter::POSITION_RELATIVE_BELOW,
		);
	}
	
	public function getPositionAds($position = false, $getRelatives = false) {
		$positionAds = XenForo_Application::getSimpleCacheData(self::SIMPLE_CACHE_POSITION_ADS_KEY);
		if (!is_array($positionAds)) {
			$positionAds = array();
		}
		
		if ($position === false) {
			return $positionAds;
		} else {
			$ads = 0;
			
			if ($getRelatives) {
				foreach ($this->getAvailablePositionRelatives() as $relative) {
					if (isset($positionAds[$position . ':' . $relative])) {
						$ads += $positionAds[$position . ':' . $relative];
					}
				}
			} 
			
			if (isset($positionAds[$position])) {
				$ads += $positionAds[$position];
			}

			return max($ads, 1);
		}
	}
	
	public function setPositionAds($position, $ads) {
		$positionAds = $this->getPositionAds();
		$positionAds[$position] = $ads;
		
		XenForo_Application::setSimpleCacheData(self::SIMPLE_CACHE_POSITION_ADS_KEY, $positionAds);
	}
}