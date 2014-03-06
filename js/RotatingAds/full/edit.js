! function($, window, document, _undefined)
{
	XenForo.RotatingAds_PositionSelector = function($select)
	{
		this.__construct($select);
	};
	XenForo.RotatingAds_PositionSelector.prototype =
	{
		__construct: function($select)
		{
			this.$select = $select;
			this.$custom = $($select.data('customselector'));
			this.$select.change($.context(this, 'change'));

			this.change(null, true);
		},

		change: function(e, init)
		{
			var $custom = this.$custom;
			var speed = init ? 0 : XenForo.speed.fast

			if (this.$select.val() != '')
			{
				$custom.xfFadeUp(speed, null, XenForo.speed.fast, 'easeInBack');
				;
			}
			else
			{
				$custom.xfFadeDown(speed, init ? null : function(e)
				{
					$custom.focus().select();
				});
			}
		}
	};

	// *********************************************************************

	XenForo.RotatingAds_TypeSelector = function($select)
	{
		this.__construct($select);
	};
	XenForo.RotatingAds_TypeSelector.prototype =
	{
		__construct: function($select)
		{
			this.$select = $select;
			this.types = new Array();
			this.$select.change($.context(this, 'change'));
			this.prefix = $select.data('prefix');

			var _this = this;
			this.$select.find('option').each(function()
			{
				_this.types.push(this.value);
			});

			this.change(null, true);
		},

		change: function(e, init)
		{
			var speed = init ? 0 : XenForo.speed.fast;

			for (var i = 0; i < this.types.length; i++)
			{
				var $target = $('#' + this.prefix + this.types[i]);
				if (this.$select.val() != this.types[i])
				{
					$target.xfFadeUp(speed, null, XenForo.speed.fast, 'easeInBack');
					;
				}
				else
				{
					$target.xfFadeDown(speed, init ? null : function(e)
					{
						$target.find('input').focus().select();
					});
				}
			}
		}
	};

	// *********************************************************************

	XenForo.RotatingAds_SlidesListener = function($element)
	{
		this.__construct($element);
	};
	XenForo.RotatingAds_SlidesListener.prototype =
	{
		__construct: function($element)
		{
			$element.one('keypress', $.context(this, 'createChoice'));

			this.$element = $element;
			if (!this.$base)
			{
				this.$base = $element.clone();
			}
		},

		createChoice: function()
		{
			var $new = this.$base.clone(), nextCounter = this.$element.parent().children().length;

			$new.find('input[name]').each(function()
			{
				var $this = $(this);
				$this.attr('name', $this.attr('name').replace(/\[(\d+)\]/, '[' + nextCounter + ']'));
			});

			$new.find('.RotatingAds_SlideOrder').val(nextCounter * 10);

			$new.find('*[id]').each(function()
			{
				var $this = $(this);
				$this.removeAttr('id');
				XenForo.uniqueId($this);

				if (XenForo.formCtrl)
				{
					XenForo.formCtrl.clean($this);
				}
			});

			$new.xfInsert('insertAfter', this.$element);

			this.__construct($new);
		}
	};

	// *********************************************************************

	XenForo.register('select.RotatingAds_PositionSelector', 'XenForo.RotatingAds_PositionSelector');
	XenForo.register('select.RotatingAds_TypeSelector', 'XenForo.RotatingAds_TypeSelector');
	XenForo.register('.RotatingAds_SlidesListener', 'XenForo.RotatingAds_SlidesListener');

}(jQuery, this, document);
