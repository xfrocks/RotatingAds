<?php

class RotatingAds_ViewAdmin_Export extends XenForo_ViewAdmin_Base {
	public function renderXml() {
		$system =& $this->_params['system'];
		$items =& $this->_params['items'];
		
		$document = new DOMDocument('1.0', 'utf-8');
		$document->formatOutput = true;

		$rootNode = $document->createElement('rotating_ads');
		$rootNode->setAttribute('version', $system['version_string']);
		$document->appendChild($rootNode);
		
		foreach ($items as $item) {
			$itemNode = $document->createElement('item');
			$itemNode->setAttribute('name', $item['name']);
			$itemNode->setAttribute('position', $item['position_original']);
			$itemNode->setAttribute('link', $item['link']);
			
			$htmlNode = $document->createElement('html');
			$htmlNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, $item['html']));
			$itemNode->appendChild($htmlNode);
			
			$itemNode->setAttribute('expire_date', $item['expire_date']);
			// ignored hit
			$itemNode->setAttribute('is_disabled', $item['is_disabled']);
			
			$optionsNode = $document->createElement('options');
			$optionsNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, serialize($item['options'])));
			$itemNode->appendChild($optionsNode);

			$rootNode->appendChild($itemNode);
		}

		$this->setDownloadFileName('rotating_ads-items-' . XenForo_Template_Helper_Core::date(XenForo_Application::$time, 'YmdHi') . '.xml');
		return $document->saveXml();
	}
}