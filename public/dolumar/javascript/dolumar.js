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
	'startupChecked' : false,

	'init' : function ()
	{
		var mobile = Game.dolumar.mobile;

		if (mobile.initialized || typeof (Game.layout) == 'undefined' || typeof (Game.sheets) == 'undefined')
		{
			return;
		}
		mobile.initialized = true;

		// Panels arrive (and are refreshed) after load: rebuild on every content change.
		// (Game.gui.trigger passes the event arguments as one array.)
		Game.gui.observe ('contentChange', function (args)
		{
			var element = args && args.length ? args[0] : args;

			mobile.hideStaleTooltip (element);
			mobile.refresh ();

			if (mobile.isMobile ())
			{
				mobile.fitContent (element);
			}

			// After the whole batch of start-up windows (contentChange fires while it is processed).
			if (!mobile.startupChecked)
			{
				setTimeout (mobile.closeStartupChat, 0);
			}
		});
		Game.layout.onChange (function () { mobile.refresh (); });

		// Fit every sheet again after each server response ('update' runs after
		// fancybox.js has boxed the new content, which makes room narrower) and after a
		// layout change (windows moved into sheets get no contentChange).
		Game.gui.observe ('update', function () { mobile.fitAll (); });
		Game.layout.onChange (function () { setTimeout (mobile.fitAll, 0); });

		// Tap tooltips: one delegated listener on the sheets container (not on document:
		// iOS Safari only delivers a tap as a click to an ancestor below body).
		mobile.bindTooltips ();

		mobile.refresh ();
	},

	/*
		Window content in a sheet (mobile mode only): a table wider than the room it has
		is wrapped in a div.table-scroll that scrolls sideways on its own, so the sheet
		itself never does. fitContent runs on each contentChange, fitAll on every open
		sheet after each server response. Tables already wrapped (or inside a wrapped
		table) are left alone, so running it again changes nothing. The rest (images,
		inline widths, tap targets) is CSS in css/mobile.css.
	*/
	'fitAll' : function ()
	{
		var mobile = Game.dolumar.mobile;

		mobile.hideStaleTooltip (null);

		if (mobile.isMobile ())
		{
			$$('#sheets .sheet-content.guiwindow').each (function (content) { mobile.fitContent (content); });
		}
	},

	'fitContent' : function (element)
	{
		element = (element && element.nodeType == 1) ? $(element) : null;
		var content = element ? (element.hasClassName ('sheet-content') ? element : element.up ('.sheet-content')) : null;

		if (!content)
		{
			return;
		}

		var style = window.getComputedStyle (content);
		var room = content.clientWidth - parseFloat (style.paddingLeft) - parseFloat (style.paddingRight);

		// A hidden sheet (lower in the stack) cannot be measured: wrap all its tables.
		var measured = content.offsetWidth > 0;

		var tables = element.tagName == 'TABLE' ? [ element ] : element.select ('table');
		for (var i = 0; i < tables.length; i ++)
		{
			var table = tables[i];
			var parent = table.parentNode;

			if (!parent || $(parent).hasClassName ('table-scroll') || table.up ('table') || table.up ('.table-scroll'))
			{
				continue;
			}

			if (measured && table.offsetWidth <= Math.min (room, parent.clientWidth || room))
			{
				continue;
			}

			var wrapper = new Element ('div', { 'class' : 'table-scroll' });
			parent.insertBefore (wrapper, table);
			wrapper.appendChild (table);
		}
	},

	/*
		A tap on an element with a title inside a sheet shows that title in a
		div.tap-tooltip next to it (touch screens have no hover). A tap anywhere
		else removes it. The tap itself is not stopped: links still do their thing.
	*/
	'onTooltipClick' : function (event)
	{
		var mobile = Game.dolumar.mobile;
		var target = event.target;

		if (target && target.nodeType != 1)
		{
			target = target.parentNode;
		}

		var titled = (mobile.isMobile () && target && target.closest) ? target.closest ('.sheet-content.guiwindow [title]') : null;
		var text = titled ? titled.getAttribute ('title') : '';

		if (!titled || !text || mobile.tooltipFor === titled)
		{
			// A second tap on the same element closes its tooltip.
			mobile.hideTooltip ();
			return;
		}

		mobile.showTooltip (titled, text);
	},

	'tooltip' : null,
	'tooltipFor' : null,

	'bindTooltips' : function ()
	{
		var mobile = Game.dolumar.mobile;
		var container = Game.sheets.container ();

		if (!container || container.mobileTooltipBound)
		{
			return;
		}
		container.mobileTooltipBound = true;

		container.addEventListener ('click', mobile.onTooltipClick, false);

		// Scroll does not bubble: capture it for every scrolling sheet content.
		container.addEventListener ('scroll', function () { mobile.hideTooltip (); }, true);
	},

	/*
		Remove the tooltip when what it points at is gone or hidden: its element left the
		page (content replaced), was inside the element whose content changed, or its
		sheet is no longer shown (another sheet on top). Other content changes, such as
		the Statusbar's regular refresh, leave it alone.
	*/
	'hideStaleTooltip' : function (changed)
	{
		var mobile = Game.dolumar.mobile;
		var target = mobile.tooltipFor;

		if (!target)
		{
			return;
		}

		if (!document.body.contains (target)
			|| (changed && changed.nodeType == 1 && changed.contains (target))
			|| target.offsetParent === null)
		{
			mobile.hideTooltip ();
		}
	},

	'showTooltip' : function (element, text)
	{
		var mobile = Game.dolumar.mobile;
		var sheet = element.closest ('.sheet');

		mobile.hideTooltip ();

		if (!sheet)
		{
			return;
		}

		var tip = document.createElement ('div');
		tip.className = 'tap-tooltip';
		tip.setAttribute ('role', 'tooltip');
		tip.textContent = text;
		sheet.appendChild (tip);

		// Below the element (above it when there is no room), inside the sheet.
		var box = sheet.getBoundingClientRect ();
		var rect = element.getBoundingClientRect ();
		var left = Math.max (4, Math.min (rect.left - box.left, box.width - tip.offsetWidth - 4));
		var top = rect.bottom - box.top + 4;

		if (top + tip.offsetHeight > box.height - 4)
		{
			top = Math.max (4, rect.top - box.top - tip.offsetHeight - 4);
		}

		tip.style.left = left + 'px';
		tip.style.top = top + 'px';

		mobile.tooltip = tip;
		mobile.tooltipFor = element;
	},

	'hideTooltip' : function ()
	{
		var mobile = Game.dolumar.mobile;

		if (mobile.tooltip && mobile.tooltip.parentNode)
		{
			mobile.tooltip.parentNode.removeChild (mobile.tooltip);
		}

		mobile.tooltip = null;
		mobile.tooltipFor = null;
	},

	/*
		Once per page load, when the start-up windows are in (the Menu is one of them):
		in mobile mode, close the General chat sheet the server opens at start, the same
		way the sheet's close button does. Later Chat opens are left alone.
	*/
	'closeStartupChat' : function ()
	{
		var mobile = Game.dolumar.mobile;

		if (mobile.startupChecked || Game.gui.getWindowsFromWindowId ('Menu').length == 0)
		{
			return;
		}
		mobile.startupChecked = true;

		if (!mobile.isMobile ())
		{
			return;
		}

		for (var i = Game.gui.aOpenWindows.length - 1; i >= 0; i --)
		{
			var entry = Game.gui.aOpenWindows[i];
			if (entry.sWindowId.toLowerCase () == 'chat' && entry.oSheet)
			{
				Game.sheets.dismiss (entry.oSheet);
			}
		}
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
		mobile.hideTooltip ();

		$$('.mobile-header-actions, .mobile-map-zoom').invoke ('remove');
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

	/*
		Inline icons for the header buttons (cream strokes, like the header text).
	*/
	'icons' : {
		'minimap' : '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M3 6l6-2 6 2 6-2v14l-6 2-6-2-6 2z"/><path d="M9 4v14M15 6v14"/></svg>',
		'chat' : '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/></svg>',
		'news' : '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M5 4h15v15a1.5 1.5 0 0 1-3 0V6"/><path d="M5 4v15a1.5 1.5 0 0 0 1.5 1.5H18"/><path d="M8 8h6M8 11h6M8 14h6"/></svg>'
	},

	'buildHeader' : function ()
	{
		var mobile = Game.dolumar.mobile;
		var body = $('bodyDiv');

		if (!body || body.down ('.mobile-header-actions'))
		{
			return;
		}

		var makeButton = function (cls, html, label, onclick)
		{
			var button = new Element ('button', {
				'type' : 'button',
				'class' : cls,
				'title' : label,
				'aria-label' : label
			});

			button.update (html);
			button.observe ('click', onclick);

			return button;
		};

		// Panel and window toggles, as icons, at the right of the header.
		var actions = new Element ('div', { 'class' : 'mobile-header-actions' });

		actions.appendChild (makeButton ('minimap', mobile.icons.minimap, mobile.label ('minimap', 'Map'), function () { mobile.togglePanel ('minimap', 'MiniMap'); }));
		actions.appendChild (makeButton ('chat', mobile.icons.chat, mobile.label ('chat', 'Chat'), function () { openWindow ('Chat'); }));
		actions.appendChild (makeButton ('news', mobile.icons.news, mobile.label ('news', 'News'), function () { mobile.togglePanel ('newsbar', 'Newsbar'); }));

		body.appendChild (actions);

		// Zoom as a floating pair on the map (pinch works too); sheets cover it.
		var zoom = new Element ('div', { 'class' : 'mobile-map-zoom' });

		zoom.appendChild (makeButton ('zoom-in', '+', mobile.label ('zoomin', 'Zoom in'), function () { Game.map.zoomIn (); }));
		zoom.appendChild (makeButton ('zoom-out', '&#8722;', mobile.label ('zoomout', 'Zoom out'), function () { Game.map.zoomOut (); }));

		body.appendChild (zoom);
	},

	/*
		MiniMap, Newsbar and Guide are panels (not sheets): show or hide them over the map.
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

		var buttonClass = { 'newsbar' : 'news', 'minimap' : 'minimap' }[className];
		var button = buttonClass ? $$('.mobile-header-actions button.' + buttonClass)[0] : null;
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
				// A long press opened More: no click on the tab underneath. Only that one
				// (synthetic) click: the flag is cleared shortly after, so a later mouse click works.
				event.preventDefault ();
				setTimeout (function () { mobile.suppressClick = false; }, 400);
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
		With a tab (long press): that tab's item, with its village list, is listed first.
		A "Guide" entry at the top shows or hides the Guide panel.
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

		if ($$('.panel.guide').length > 0)
		{
			var guide = new Element ('li', { 'class' : 'mobile-more-guide' });
			var guideLink = new Element ('a', { 'href' : 'javascript:void(0);', 'title' : mobile.label ('guide', 'Guide') });
			guideLink.appendChild (new Element ('span', { 'class' : 'guide-icon' }));
			guideLink.observe ('click', function () { mobile.togglePanel ('guide', 'Guide'); });
			guide.appendChild (guideLink);
			list.appendChild (guide);
		}

		if (tab)
		{
			list.appendChild (mobile.moreItem (tab));
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
