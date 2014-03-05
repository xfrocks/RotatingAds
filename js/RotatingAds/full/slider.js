! function($, window, document, _undefined)
{
	XenForo.bdRotatingAds_Slider = function($slider)
	{
		if ( typeof jQuery.fn.nivoSlider == 'function')
		{
			$slider.nivoSlider();
		}
	};

	// *********************************************************************

	XenForo.register('.bdRotatingAds_Slider', 'XenForo.bdRotatingAds_Slider');

}(jQuery, this, document);
