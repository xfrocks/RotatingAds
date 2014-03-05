<?php
class RotatingAds_Route_PrefixAdmin implements XenForo_Route_Interface {
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router) {
		if (in_array($routePath, array('add', 'save', 'export', 'import'))) {
			$action = $routePath;			
		} else {
			$action = $router->resolveActionWithIntegerOrStringParam($routePath, $request, 'item_id', 'string');
		}
		return $router->getRouteMatch('RotatingAds_ControllerAdmin', $action, 'appearance');
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams) {
		if (is_array($data)) {
			return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'item_id', 'name');
		} else {
			return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, array('string' => $data), 'string');
		}
	}
}