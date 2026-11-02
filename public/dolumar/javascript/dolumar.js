Game.dolumar =
{
	/*
		Initialize the whole game.
	*/
	'init' : function ()
	{
		Game.dolumar.mobile.init ();
	},
	
	'initBattleSimulator' : function (oWindow)
	{
		var div = $(oWindow.div);
		
		var chooseUnitContainers = div.select('div.chooseUnitContainer');
		
		if (chooseUnitContainers.length > 0)
		{
			var clearbutton = div.select ('button.clear');
			var lng_clear = clearbutton[0].innerHTML;
		}
		
		for (var chooseUnitContainersI = 0; chooseUnitContainersI < chooseUnitContainers.length; chooseUnitContainersI ++)
		{
			var scrolldiv = chooseUnitContainers[chooseUnitContainersI];
			
			var fieldset = scrolldiv.select ('fieldset')[0];
		
			// Count the amount of divs:
			var divs = fieldset.select('div');
		
			// Make a list of image (and values)
			var imagelist = new Object ();

			var images = div.select('div.available-units div');
			for (var i = 0; i < images.length; i ++)
			{	
				var img = images[i].select ('img')[0];
				var id = images[i].className;
				
				img.id = oWindow.sDialogId + 'img' + id;
				img.alt = id;
			
				imagelist[id] = img;
				
				// Make dragable
				img.dragable = new Draggable 
				(
					img.id, 
					{
						'revert': true,
						'el' : img,
						'onStart' : function ()
						{

						},
						'onEnd' : function ()
						{
						
						},
						'reverteffect' : function(element, top_offset, left_offset)
						{
							element.style.left = (parseInt(element.style.left) - left_offset) + 'px';
							element.style.top = (parseInt(element.style.top) - top_offset) + 'px';
						},
						'scroll' : scrolldiv
					}
				);
			}
		
			var iWidth = 0;
			
			for (var i = 0; i < divs.length; i ++)
			{
				iWidth += divs[i].getWidth();
			}
			
			fieldset.style.width = (parseInt(iWidth) + 1) + 'px';
			
			// Scroll to middle of the troops
			var iscroll = ((fieldset.getDimensions().width - scrolldiv.getDimensions().width) / 2);
			scrolldiv.scrollLeft = iscroll;
			
			// Load the actual "dropables"
			var divs = fieldset.select('div.battlefield-slot');
		
			// Loop trough all divs
			for (var i = 0; i < divs.length; i ++)
			{
				divs[i].id = oWindow.sDialogId + 'div' + chooseUnitContainersI + '_' + i;
				var select = divs[i].select('select')[0];
				
				select.id = oWindow.sDialogId + 'sel' + chooseUnitContainersI + '_' + i;
				
				// On drop: set value
				Droppables.add
				(
					divs[i].id, 
					{
						'el' : select,
						'overlap' : 'horizontal',
						'onDrop' : function (element, dropel)
						{
							var slots = div.select ('.unit_amount')[0].value;
						
							if (typeof (this.el.clearmethod) != 'undefined')
							{
								this.el.clearmethod (false);
							}
							
							var amount = dropel.select ('input.selected_units')[0];							
							amount.value = parseInt(amount.value) + parseInt(slots);
						
							this.el.setValue (element.alt);
							
							//element.parentNode.removeChild (element);
							//dropel.appendChild (element);
							
							var img = document.createElement ('img');
							img.src = element.src;
							img.title = element.title;
							img.className = 'unit-selected';
							dropel.appendChild (img);
							
							// HIde the current element
							//element.style.display = 'none';

							var clear = document.createElement ('div');
							clear.className = 'clear-button';
							clear.innerHTML = '<span>'+lng_clear+'</span>';
							
							dropel.appendChild (clear);

							var ele = this.el;
							
							var clearfnct = function (resetamount)
							{
								if (typeof (resetamount) == 'undefined')
								{
									resetamount = true;
								}
							
								ele.clearmethod = function () {};
								img.parentNode.removeChild(img);	
								clear.parentNode.removeChild(clear);
								ele.setValue (0);
								
								if (resetamount)
								{
									var amount = ele.parentNode.select ('input.selected_units')[0];					
									amount.value = 0;
								}
							}
							
							clear.onclick = clearfnct;
							this.el.clearmethod = clearfnct;
							
							return true;
						},
						'scrollid' : scrolldiv
					}
				);
			}
		}
	},

	/*
		Battle window
		
		TODO: Clean all dragables on destruction
	*/
	'initBattleWindow' : function (oWindow)
	{
		var div = $(oWindow.div);
		
		var fieldset = div.select('fieldset.chooseUnits');
		var battlefield = div.select ('div.battlefield');
		
		if (fieldset.length == 1)
		{
			var scrolldiv = div.select ('div.chooseUnitContainer');
			var clearbutton = div.select ('button.clear');
			
			var lng_clear = clearbutton[0].innerHTML;
		
			fieldset = fieldset[0];
			scrolldiv = scrolldiv[0];
		
			// Count the amount of divs:
			var divs = fieldset.select('div');
		
			// Make a list of image (and values)
			var imagelist = new Object ();

			var images = div.select('div.available-units div');
			for (var i = 0; i < images.length; i ++)
			{	
				var img = images[i].select ('img')[0];
				var id = images[i].className;
				
				img.id = oWindow.sDialogId + 'img' + id;
				img.alt = id;
				
				img.curDropId = 0;
				img.lastDropId = 0;
			
				imagelist[id] = img;
				
				// Make dragable
				img.dragable = new Draggable 
				(
					img.id, 
					{
						'revert': true,
						'el' : img,
						'onStart' : function ()
						{
							this.el.curDropId = 0;
						},
						'onEnd' : function ()
						{
							// Unset the (previous) drop target
							if (this.el.curDropId != this.el.lastDropId && this.el.lastDropId != 0)
							{
								var obj = $(this.el.lastDropId);
								obj.setValue (0);
								obj.fire ('custom:change');
							}
							
							this.el.lastDropId = this.el.curDropId;
						},
						'reverteffect' : function(element, top_offset, left_offset)
						{
							element.style.left = (parseInt(element.style.left) - left_offset) + 'px';
							element.style.top = (parseInt(element.style.top) - top_offset) + 'px';
						},
						'scroll' : scrolldiv
					}
				);
			}
		
			var iWidth = 0;
			
			for (var i = 0; i < divs.length; i ++)
			{
				iWidth += divs[i].getWidth();
			}
			
			fieldset.style.width = (parseInt(iWidth) + 1) + 'px';
			
			// Scroll to middle of the troops
			var iscroll = ((fieldset.getDimensions().width - scrolldiv.getDimensions().width) / 2);
			scrolldiv.scrollLeft = iscroll;
			
			// Load the actual "dropables"
			var divs = fieldset.select('div.battlefield-slot');
		
			// Loop trough all divs
			for (var i = 0; i < divs.length; i ++)
			{			
				divs[i].id = oWindow.sDialogId + 'div' + i;
				var select = divs[i].select('select')[0];
				
				select.id = oWindow.sDialogId + 'sel' + i;
			
				select.observe
				(
					'custom:change', 
					function (event)
					{
						var el = event.target;
						var id = el.getValue();
						
						if (id == 0)
						{
							//el.style.backgroundImage = 'none';
							//el.parentNode.style.border = '1px solid red';
							el.parentNode.setOpacity (0.7);
						}
						else if (typeof(imagelist[id]) != 'undefined')
						{
							//el.style.backgroundImage = 'url('+imagelist[id].src+')';
							//el.parentNode.style.border = '1px solid green';
							el.parentNode.setOpacity (1);
						}
						else
						{
							el.parentNode.setOpacity (0.7);
						}
					}
				);
				
				// On drop: set value
				Droppables.add
				(
					divs[i].id, 
					{
						'el' : select,
						'overlap' : 'horizontal',
						'onDrop' : function (element, dropel)
						{
							if (this.el.getValue () != 0)
							{
								return false;
							}
						
							this.el.setValue (element.alt);
							Game.gui.removeDuplicates (this.el.parentNode.parentNode, this.el);
							element.curDropId = this.el.id;
							
							//element.parentNode.removeChild (element);
							//dropel.appendChild (element);
							
							var img = document.createElement ('img');
							img.src = element.src;
							img.title = element.title;
							img.className = 'unit-selected';
							dropel.appendChild (img);
							
							// HIde the current element
							element.style.display = 'none';

							var clear = document.createElement ('div');
							clear.className = 'clear-button';
							clear.innerHTML = '<span>'+lng_clear+'</span>';
							
							dropel.appendChild (clear);

							var ele = this.el;
							clear.onclick = function ()
							{
								element.style.display = 'block';
								img.parentNode.removeChild(img);
								
								clear.parentNode.removeChild(clear);
								
								ele.setValue (0);
								ele.fire ('custom:change');
							}
							
							return true;
						},
						'scrollid' : scrolldiv
					}
				);
				
				// Change opacity
				divs[i].setOpacity (0.7);
			}
		}
		
		else if (battlefield.length == 1)
		{
			var fieldset = div.select ('div.innerbattlefield');
			//var scrolldiv = div.select ('div.battlefield');
			var scrolldiv = div;
		
			fieldset = $(fieldset[0]);
			//scrolldiv = $(scrolldiv[0]);
		
			// Count the amount of divs:
			var divs = fieldset.childElements ();

			var iWidth = 0;
			
			for (var i = 0; i < divs.length; i ++)
			{
				iWidth += divs[i].getWidth();
			}
			
			fieldset.style.width = (parseInt(iWidth) + 1) + 'px';
			
			// Scroll to middle of the troops
			var iscroll = ((fieldset.getDimensions().width - scrolldiv.getDimensions().width) / 2);
			scrolldiv.scrollLeft = iscroll;
		}
		
		else
		{
			var fieldset = div.select ('fieldset.specialUnits');
			if (fieldset.length == 1)
			{
				fieldset = fieldset[0];
				
				var checkboxes = fieldset.select ('input.checkbox');
				for (var i = 0; i < checkboxes.length; i ++)
				{
					Event.observe (checkboxes[i], 'change', 
						function (event)
						{
							var sel = $(event.element().id.replace ('check', 'select'));
							if (sel)
							{
								if (event.element().checked)
								{
									sel.selectedIndex = 1;
								}
								else
								{
									sel.selectedIndex = 0;
								}
							}
						}
					);
				}
				
				var selects = fieldset.select ('select.specialAction')
				for (var i = 0; i < selects.length; i ++)
				{
					Event.observe (selects[i], 'change', 
						function (event)
						{
							var sel = $(event.element().id.replace ('select', 'check'));
							if (sel)
							{
								if (event.element().value != "0")
								{
									sel.checked = true;
								}
								else
								{
									sel.checked = false;
								}
							}
						}
					);
				}
			}
		}
	}
}

/*
	Mobile layout: header action buttons and the bottom tab bar.

	The Statusbar and Menu panels are restyled by css/mobile.css (all under
	body.layout-mobile). This adds what CSS cannot: a sixth "More" tab with a
	sheet listing the remaining menu items, a long press on a tab that opens
	the More sheet at that item (with its village list), and the header
	buttons. Everything is rebuilt when panel content changes and removed when
	the layout switches back to desktop.
*/
Game.dolumar.mobile =
{
	'LONG_PRESS_MS' : 500,
	'MORE_DIALOG_ID' : 'mobile-more',

	'initialized' : false,
	'moreSheet' : null,
	'pressTimer' : null,
	'suppressClick' : false,

	'init' : function ()
	{
		var mobile = Game.dolumar.mobile;

		if (mobile.initialized || typeof (Game.layout) == 'undefined' || typeof (Game.sheets) == 'undefined')
		{
			return;
		}
		mobile.initialized = true;

		// Panels arrive (and are refreshed) after load: rebuild on every content change.
		Game.gui.observe ('contentChange', function () { mobile.refresh (); });
		Game.layout.onChange (function () { mobile.refresh (); });

		mobile.refresh ();
	},

	'isMobile' : function ()
	{
		return Game.layout.mode () === 'mobile';
	},

	/*
		Build (mobile) or remove (desktop) the additions. Safe to call any number of times.
	*/
	'refresh' : function ()
	{
		var mobile = Game.dolumar.mobile;

		if (mobile.isMobile ())
		{
			mobile.buildHeader ();
			mobile.buildTabBar ();
		}
		else
		{
			mobile.teardown ();
		}
	},

	'teardown' : function ()
	{
		var mobile = Game.dolumar.mobile;

		mobile.closeMore ();

		$$('.mobile-header-actions').invoke ('remove');
		$$('#menu li.tab-more').invoke ('remove');
		$$('.panel.mobile-visible').invoke ('removeClassName', 'mobile-visible');
	},

	/*
		Label from the Menu panel's data-label-* attributes (translated), or the fallback.
	*/
	'label' : function (name, fallback)
	{
		var menu = $('menu');
		var value = menu ? menu.getAttribute ('data-label-' + name) : null;

		return value ? value : fallback;
	},

	'buildHeader' : function ()
	{
		var mobile = Game.dolumar.mobile;
		var body = $('bodyDiv');

		if (!body || body.down ('.mobile-header-actions'))
		{
			return;
		}

		var actions = new Element ('div', { 'class' : 'mobile-header-actions' });

		var buttons = [
			[ 'zoom-out', '&#8722;', mobile.label ('zoomout', 'Zoom out'), function () { Game.map.zoomOut (); } ],
			[ 'zoom-in', '+', mobile.label ('zoomin', 'Zoom in'), function () { Game.map.zoomIn (); } ],
			[ 'minimap', null, mobile.label ('minimap', 'Map'), function () { mobile.togglePanel ('minimap', 'MiniMap'); } ],
			[ 'chat', null, mobile.label ('chat', 'Chat'), function () { openWindow ('Chat'); } ],
			[ 'news', null, mobile.label ('news', 'News'), function () { mobile.togglePanel ('newsbar', 'Newsbar'); } ]
		];

		for (var i = 0; i < buttons.length; i ++)
		{
			var button = new Element ('button', {
				'type' : 'button',
				'class' : buttons[i][0],
				'title' : buttons[i][2],
				'aria-label' : buttons[i][2]
			});

			if (buttons[i][1] !== null)
			{
				button.update (buttons[i][1]);
			}
			else
			{
				button.appendChild (document.createTextNode (buttons[i][2]));
			}

			button.observe ('click', buttons[i][3]);
			actions.appendChild (button);
		}

		body.appendChild (actions);
	},

	/*
		MiniMap and Newsbar are panels (not sheets): show or hide them over the map.
		Opens the window if it is not on the page at all.
	*/
	'togglePanel' : function (className, windowId)
	{
		var panel = $$('.panel.' + className)[0];

		if (!panel)
		{
			openWindow (windowId);
			return;
		}

		panel.toggleClassName ('mobile-visible');

		var button = $$('.mobile-header-actions button.' + (className == 'newsbar' ? 'news' : 'minimap'))[0];
		if (button)
		{
			if (panel.hasClassName ('mobile-visible'))
			{
				button.addClassName ('active');
			}
			else
			{
				button.removeClassName ('active');
			}
		}
	},

	'buildTabBar' : function ()
	{
		var mobile = Game.dolumar.mobile;
		var menu = $('menu');
		var list = $('menu-main');

		if (!menu || !list)
		{
			return;
		}

		if (!list.down ('li.tab-more'))
		{
			var more = new Element ('li', { 'class' : 'tab-more' });
			var link = new Element ('a', { 'href' : 'javascript:void(0);', 'title' : mobile.label ('more', 'More') });
			link.appendChild (new Element ('span', { 'class' : 'more-icon' }).update ('&#8943;'));
			link.observe ('click', function (event)
			{
				Event.stop (event);
				mobile.toggleMore ();
			});
			more.appendChild (link);
			list.appendChild (more);
		}

		// The Menu panel's content (and so #menu) is replaced on updates: bind once per element.
		if (!menu.mobileBound)
		{
			menu.mobileBound = true;
			mobile.bindTabs (menu);
		}
	},

	'bindTabs' : function (menu)
	{
		var mobile = Game.dolumar.mobile;

		var tabFromEvent = function (event)
		{
			var li = Event.findElement (event, 'li');

			while (li && li.parentNode && li.parentNode.id != 'menu-main')
			{
				li = $(li.parentNode).up ('li');
			}

			return (li && !li.hasClassName ('tab-more')) ? li : null;
		};

		var cancel = function ()
		{
			if (mobile.pressTimer)
			{
				clearTimeout (mobile.pressTimer);
				mobile.pressTimer = null;
			}
		};

		menu.addEventListener ('touchstart', function (event)
		{
			cancel ();
			mobile.suppressClick = false;

			var tab = mobile.isMobile () ? tabFromEvent (event) : null;
			if (!tab)
			{
				return;
			}

			mobile.pressTimer = setTimeout (function ()
			{
				mobile.pressTimer = null;
				mobile.suppressClick = true;
				mobile.openMore (tab);
			}, mobile.LONG_PRESS_MS);
		}, { 'passive' : true });

		menu.addEventListener ('touchmove', cancel, { 'passive' : true });
		menu.addEventListener ('touchcancel', cancel, false);
		menu.addEventListener ('touchend', function (event)
		{
			cancel ();
			if (mobile.suppressClick)
			{
				// A long press opened More: no click on the tab underneath.
				event.preventDefault ();
			}
		}, false);

		// Capture phase: runs before the tab's own onclick.
		menu.addEventListener ('click', function (event)
		{
			if (!mobile.isMobile ())
			{
				return;
			}

			if (mobile.suppressClick)
			{
				mobile.suppressClick = false;
				event.preventDefault ();
				event.stopPropagation ();
				return;
			}

			// A tab replaces the More sheet.
			if (tabFromEvent (event))
			{
				mobile.closeMore ();
			}
		}, true);
	},

	'toggleMore' : function ()
	{
		var mobile = Game.dolumar.mobile;
		var stack = Game.sheets.stack;

		if (mobile.moreSheet && stack.length > 0 && stack[stack.length - 1] === mobile.moreSheet)
		{
			mobile.closeMore ();
		}
		else
		{
			mobile.openMore (null);
		}
	},

	'closeMore' : function ()
	{
		var mobile = Game.dolumar.mobile;

		if (mobile.moreSheet)
		{
			var sheet = mobile.moreSheet;
			mobile.moreSheet = null;
			Game.sheets.close (sheet);
		}
	},

	/*
		Open the More sheet (rebuilt every time, so it matches the current menu).
		With a tab: that tab's item is listed first and scrolled to.
	*/
	'openMore' : function (tab)
	{
		var mobile = Game.dolumar.mobile;
		var main = $('menu-main');

		if (!main)
		{
			return;
		}

		mobile.closeMore ();

		var list = new Element ('ul', { 'class' : 'mobile-more-list' });
		var target = null;

		if (tab)
		{
			target = mobile.moreItem (tab);
			list.appendChild (target);
		}

		var items = main.childElements ();
		for (var i = 5; i < items.length; i ++)
		{
			if (!items[i].hasClassName ('tab-more') && items[i] !== tab)
			{
				list.appendChild (mobile.moreItem (items[i]));
			}
		}

		var extra = $('menu-extra');
		if (extra)
		{
			var others = extra.childElements ();
			for (var j = 0; j < others.length; j ++)
			{
				list.appendChild (mobile.moreItem (others[j]));
			}
		}

		var sheet = Game.sheets.open ({
			'dialogId' : mobile.MORE_DIALOG_ID,
			'title' : mobile.label ('more', 'More'),
			'closable' : true,
			'modal' : false,
			'className' : 'mobile-more',
			'onClose' : function (closed)
			{
				if (mobile.moreSheet === closed)
				{
					mobile.moreSheet = null;
				}
			}
		});

		sheet.content.appendChild (list);
		mobile.moreSheet = sheet;

		// Any link in the list: let its own onclick or href run, then close More.
		list.observe ('click', function (event)
		{
			if (Event.findElement (event, 'a'))
			{
				setTimeout (function ()
				{
					if (mobile.moreSheet === sheet)
					{
						mobile.closeMore ();
					}
				}, 0);
			}
		});

		if (target)
		{
			sheet.content.scrollTop = target.offsetTop - list.offsetTop;
		}
	},

	/*
		A More list entry: a copy of a menu item, its village (or clan, help) sub-list
		as a nested list. The copies keep their onclick and href attributes.
	*/
	'moreItem' : function (source)
	{
		var item = $(source.cloneNode (true));

		item.removeAttribute ('onmouseover');
		item.removeAttribute ('onmouseout');
		item.removeClassName ('over');

		var sub = item.down ('div');
		if (sub)
		{
			var nested = sub.down ('ul');
			if (nested)
			{
				item.appendChild (nested);
			}
			sub.remove ();
		}

		// Ids (such as a#build) must stay unique on the page.
		item.select ('[id]').each (function (el) { el.removeAttribute ('id'); });
		item.select ('[onmouseover], [onmouseout]').each (function (el)
		{
			el.removeAttribute ('onmouseover');
			el.removeAttribute ('onmouseout');
		});

		return item;
	}
};

Event.observe (window, 'load', Game.dolumar.init, false);
