<?php
class RotatingAds_ViewAdmin_Helper {
	public static function positionTitle($position) {
		static $processed = array(
		);
		
		if (!isset($processed[$position])) {
			$processed[$position] = ucwords(preg_replace('/[^a-z]/i', ' ', preg_replace('/^ad_/', '', $position)));
		}
		
		return $processed[$position]; 
	}
	
	public static function positionRelativeTitle($position) {
		return new XenForo_Phrase('rotating_ads_' . $position); 
	}
}