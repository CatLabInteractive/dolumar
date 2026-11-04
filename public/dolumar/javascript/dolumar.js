Game.dolumar =
{
	/*
		Initialize the whole game.
	*/
	'init' : function ()
	{
		Game.dolumar.mobile.init ();
	},

	/*
		A click on a counter line of a moving army (outside the links in it) centres the
		map on that army. x, y: a town centre on its way, used while the army is not
		loaded on the map yet. On a phone the ticker sheet closes so the map shows.
	*/
	'focusArmy' : function (event, id, x, y)
	{
		if (event && Event.findElement (event, 'a'))
		{
			return;
		}

		if (event)
		{
			Event.stop (event);
		}

		var mobile = Game.dolumar.mobile;
		if (mobile.tickerSheet && typeof (Game.sheets) != 'undefined')
		{
			Game.sheets.close (mobile.tickerSheet);
			mobile.tickerSheet = null;
		}

		if (typeof (Game.mapObjects) != 'undefined' && Game.mapObjects.focus)
		{
			Game.mapObjects.focus (id, x, y);
		}
		else
		{
			Game.map.mapIsoJump (x, y);
		}
	},
	
	/*
		Tap-to-place for the battle and simulator unit pickers. Script.aculo.us only listens to
		mouse events, so dragging a unit does nothing on a touch screen. Tapping a unit selects
		it, tapping a slot then runs the same drop handler (and draggable start/end callbacks)
		that a real drag-and-drop runs. Dragging keeps working unchanged.

		images: the unit <img> elements (each with a .dragable Draggable).
		slots:  [{ slot: slot div, options: the Droppables options passed for that slot }].
	*/
	'enableTapToPlace' : function (container, images, slots)
	{
		var selected = null;

		var setSelected = function (img)
		{
			if (selected)
			{
				selected.removeClassName ('tap-selected');
			}

			selected = img;

			if (selected)
			{
				selected.addClassName ('tap-selected');
				container.addClassName ('tap-placing');
			}
			else
			{
				container.removeClassName ('tap-placing');
			}
		};

		images.each (function (img)
		{
			var downAt = null;

			img.addClassName ('tap-unit');
			img.observe ('mousedown', function (event)
			{
				downAt = [ Event.pointerX (event), Event.pointerY (event) ];
			});
			img.observe ('click', function (event)
			{
				// A finished mouse drag can end in a click too; only a click that didn't move selects.
				if (downAt && (Math.abs (Event.pointerX (event) - downAt[0]) > 5 || Math.abs (Event.pointerY (event) - downAt[1]) > 5))
				{
					return;
				}

				setSelected (selected === img ? null : img);
			});
		});

		// Run what a real drag-and-drop runs: the draggable's start, the slot's drop, the draggable's end.
		var placeOn = function (img, entry, event)
		{
			var drag = img.dragable ? img.dragable.options : null;

			if (drag && drag.onStart)
			{
				drag.onStart.call (drag, img.dragable, event);
			}

			entry.options.onDrop.call (entry.options, img, entry.slot, event);

			if (drag && drag.onEnd)
			{
				drag.onEnd.call (drag, img.dragable, event);
			}

			setSelected (null);
		};

		slots.each (function (entry)
		{
			entry.slot.observe ('click', function (event)
			{
				if (!selected || event.findElement ('.clear-button'))
				{
					return;
				}

				placeOn (selected, entry, event);
			});
		});

		Game.dolumar.enableTouchDrag (images, slots, placeOn);
	},

	/*
		Finger dragging for the battle unit pickers (script.aculo.us only drags with a mouse).
		A unit follows the finger as a ghost image; letting go over a slot places it there through
		placeOn. Near the left or right edge of a slot row, the row scrolls. A touch that doesn't
		move stays a tap, so tap-to-place keeps working.
	*/
	'enableTouchDrag' : function (images, slots, placeOn)
	{
		var THRESHOLD = 8;
		var EDGE = 40;

		var slotAt = function (x, y)
		{
			var el = document.elementFromPoint (x, y);

			for (var i = 0; el && i < slots.length; i ++)
			{
				if (slots[i].slot === el || slots[i].slot.contains (el))
				{
					return slots[i];
				}
			}

			return null;
		};

		var scrollerAt = function (x, y)
		{
			for (var i = 0; i < slots.length; i ++)
			{
				var scroller = slots[i].options.scrollid;
				var box = scroller ? scroller.getBoundingClientRect () : null;

				if (box && y >= box.top && y <= box.bottom && x >= box.left && x <= box.right)
				{
					return { 'el' : scroller, 'box' : box };
				}
			}

			return null;
		};

		images.each (function (img)
		{
			var start = null;
			var ghost = null;
			var over = null;
			var last = null;
			var timer = null;

			var setOver = function (entry)
			{
				if (over === entry)
				{
					return;
				}

				if (over)
				{
					over.slot.removeClassName ('touch-over');
				}

				over = entry;

				if (over)
				{
					over.slot.addClassName ('touch-over');
				}
			};

			// Scroll a slot row while the finger rests near its edge.
			var autoScroll = function ()
			{
				timer = null;

				if (!ghost || !last)
				{
					return;
				}

				var row = scrollerAt (last[0], last[1]);
				var step = 0;

				if (row && last[0] < row.box.left + EDGE)
				{
					step = -12;
				}
				else if (row && last[0] > row.box.right - EDGE)
				{
					step = 12;
				}

				if (step != 0)
				{
					row.el.scrollLeft += step;
					setOver (slotAt (last[0], last[1]));
					timer = setTimeout (autoScroll, 30);
				}
			};

			var finish = function ()
			{
				if (ghost && ghost.parentNode)
				{
					ghost.parentNode.removeChild (ghost);
				}

				if (timer)
				{
					clearTimeout (timer);
				}

				ghost = null;
				start = null;
				timer = null;
				setOver (null);
			};

			img.addEventListener ('touchstart', function (event)
			{
				if (event.touches.length != 1)
				{
					finish ();
					return;
				}

				var t = event.touches[0];
				start = [ t.clientX, t.clientY ];
			}, { 'passive' : true });

			img.addEventListener ('touchmove', function (event)
			{
				if (!start || event.touches.length != 1)
				{
					return;
				}

				var t = event.touches[0];
				last = [ t.clientX, t.clientY ];

				if (!ghost)
				{
					if (Math.abs (last[0] - start[0]) < THRESHOLD && Math.abs (last[1] - start[1]) < THRESHOLD)
					{
						return;
					}

					var box = img.getBoundingClientRect ();

					ghost = img.cloneNode (false);
					ghost.removeAttribute ('id');
					ghost.className = 'touch-drag-ghost';
					ghost.style.width = box.width + 'px';
					ghost.style.height = box.height + 'px';
					ghost.offsetX = start[0] - box.left;
					ghost.offsetY = start[1] - box.top;
					document.body.appendChild (ghost);
				}

				// Dragging: the page must not scroll under the finger.
				event.preventDefault ();

				ghost.style.left = (last[0] - ghost.offsetX) + 'px';
				ghost.style.top = (last[1] - ghost.offsetY) + 'px';
				setOver (slotAt (last[0], last[1]));

				if (!timer)
				{
					autoScroll ();
				}
			}, { 'passive' : false });

			img.addEventListener ('touchend', function (event)
			{
				if (!ghost)
				{
					// A tap: leave it to the click handler (tap-to-place).
					finish ();
					return;
				}

				// No click (and no tap-select) after a drag.
				event.preventDefault ();

				var t = event.changedTouches[0];
				var entry = slotAt (t.clientX, t.clientY);

				finish ();

				if (entry)
				{
					placeOn (img, entry, event);
				}
			}, { 'passive' : false });

			img.addEventListener ('touchcancel', finish);
		});
	},

	'initBattleSimulator' : function (oWindow)
	{
		var div = $(oWindow.div);
		
		var chooseUnitContainers = div.select('div.chooseUnitContainer');

		// Touch screens can't drag: units are also placed by tapping a unit, then a slot.
		var tapImages = [];
		var tapSlots = [];
		
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

				// The unit list is shared by every container; register each unit once.
				if (tapImages.indexOf (img) < 0)
				{
					tapImages.push (img);
				}
				
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
				var dropOptions =
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
					};

				Droppables.add (divs[i].id, dropOptions);
				tapSlots.push ({ 'slot' : divs[i], 'options' : dropOptions });
			}
		}

		Game.dolumar.enableTapToPlace (div, tapImages, tapSlots);
	},

	/*
		Battle window
		
		TODO: Clean all dragables on destruction
	*/
	'initBattleWindow' : function (oWindow)
	{
		var div = $(oWindow.div);

		// Battle report replay (javascript/battlereport.js).
		if (typeof (DolumarBattleReplay) != 'undefined')
		{
			DolumarBattleReplay.init (oWindow.div);
		}
		
		var fieldset = div.select('fieldset.chooseUnits');
		var battlefield = div.select ('div.battlefield');
		
		if (fieldset.length == 1)
		{
			var scrolldiv = div.select ('div.chooseUnitContainer');
			var clearbutton = div.select ('button.clear');
			
			var lng_clear = clearbutton[0].innerHTML;
		
			fieldset = fieldset[0];
			scrolldiv = scrolldiv[0];

			// Touch screens can't drag: units are also placed by tapping a unit, then a slot.
			var tapImages = [];
			var tapSlots = [];
		
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
				tapImages.push (img);
				
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
				var dropOptions =
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
					};

				Droppables.add (divs[i].id, dropOptions);
				tapSlots.push ({ 'slot' : divs[i], 'options' : dropOptions });
				
				// Change opacity
				divs[i].setOpacity (0.7);
			}

			Game.dolumar.enableTapToPlace (div, tapImages, tapSlots);
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
	'ZOOM_IDLE_MS' : 3000,
	'MORE_DIALOG_ID' : 'mobile-more',

	'initialized' : false,
	'moreSheet' : null,
	'pressTimer' : null,
	'suppressClick' : false,
	'startupChecked' : false,
	'zoomTimer' : null,
	'zoomWake' : null,
	'tickerSheet' : null,
	'TICKER_DIALOG_ID' : 'mobile-ticker',
	// Tabs shown in the tab bar; the other menu items are in the More sheet (css: nth-child(n+6)).
	'TAB_COUNT' : 5,
	// A panel to show as soon as it arrives (the Map button opened its window).
	'pendingPanel' : null,
	// The Guide bubble last seen, to show the panel only for new advice.
	'guideHtml' : undefined,

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
		Game.gui.observe ('update', function () { mobile.fitAll (); mobile.syncHeader (); });
		Game.layout.onChange (function () { setTimeout (mobile.fitAll, 0); });

		// The tab bar marks the tab whose window is the top sheet (the window is registered
		// right after its sheet opens, hence the timeout).
		if (Game.sheets.observe)
		{
			Game.sheets.observe (function ()
			{
				mobile.syncModalState ();
				setTimeout (mobile.syncActiveTab, 0);
			});
		}

		// The header and tab bar sit above #sheets (and so above a modal's backdrop) and stay
		// tappable while a building is being placed: guard them (capture phase, before the
		// tabs' own handlers).
		document.addEventListener ('click', mobile.guardChrome, true);

		// Newsflashes scroll over the Newsbar panel, which is hidden on a phone: show them
		// over the resource strip instead.
		Game.gui.newsflashHost = function ()
		{
			return mobile.isMobile () ? ($$('.mobile-newsflash')[0] || null) : null;
		};

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

		if (!titled || !text || text === 'null' || mobile.tooltipFor === titled)
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

		// Below the element (above it when there is no room), inside the sheet. An absolutely
		// positioned child is placed from the padding edge, inside the sheet's frame (border).
		var box = sheet.getBoundingClientRect ();
		var originLeft = box.left + sheet.clientLeft;
		var originTop = box.top + sheet.clientTop;
		var rect = element.getBoundingClientRect ();
		var left = Math.max (4, Math.min (rect.left - originLeft, sheet.clientWidth - tip.offsetWidth - 4));
		var top = rect.bottom - originTop + 4;

		if (top + tip.offsetHeight > sheet.clientHeight - 4)
		{
			top = Math.max (4, rect.top - originTop - tip.offsetHeight - 4);
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
			mobile.syncHeader ();
			mobile.buildTabBar ();
			mobile.syncActiveTab ();
			mobile.syncGuide ();

			if (mobile.pendingPanel && $$('.panel.' + mobile.pendingPanel).length > 0)
			{
				var pending = mobile.pendingPanel;
				mobile.pendingPanel = null;
				mobile.setPanelVisible (pending, true);
			}
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

		// A long press in progress must not open More after the switch to desktop.
		if (mobile.pressTimer)
		{
			clearTimeout (mobile.pressTimer);
			mobile.pressTimer = null;
		}
		mobile.suppressClick = false;
		mobile.pendingPanel = null;
		mobile.guideHtml = undefined;

		if (mobile.tickerSheet)
		{
			Game.sheets.close (mobile.tickerSheet);
			mobile.tickerSheet = null;
		}

		$$('.mobile-header, .mobile-resources, .mobile-newsflash, .mobile-ticker, .mobile-map-zoom').invoke ('remove');
		$(document.body).removeClassName ('has-ticker');
		$(document.body).removeClassName ('has-modal-sheet');
		$$('.mobile-header-actions button.active').invoke ('removeClassName', 'active');
		$$('#menu li.tab-more').invoke ('remove');
		$$('#menu li.active-tab').invoke ('removeClassName', 'active-tab');
		$$('.panel.mobile-visible').invoke ('removeClassName', 'mobile-visible');

		if (mobile.zoomWake)
		{
			document.removeEventListener ('touchstart', mobile.zoomWake, { 'passive' : true, 'capture' : true });
			document.removeEventListener ('mousedown', mobile.zoomWake, true);
			mobile.zoomWake = null;
		}
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
		Inline icons (cream strokes, like the header text): header buttons and the More tab.
	*/
	'icons' : {
		'minimap' : '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M3 6l6-2 6 2 6-2v14l-6 2-6-2-6 2z"/><path d="M9 4v14M15 6v14"/></svg>',
		'chat' : '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/></svg>',
		'inbox' : '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18v13H3z"/><path d="M3 7l9 6 9-6"/></svg>',
		'more' : '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="8" cy="12" r="1.3" fill="currentColor"/><circle cx="12" cy="12" r="1.3" fill="currentColor"/><circle cx="16" cy="12" r="1.3" fill="currentColor"/></svg>'
	},

	/*
		The header: village switcher in the middle (name, previous and next village, driven by
		the Newsbar panel's own links), Map, Chat and Inbox at the right, the resource strip of
		the Newsbar underneath, and a floating zoom pair on the map that fades when idle.
	*/
	'buildHeader' : function ()
	{
		var mobile = Game.dolumar.mobile;
		var body = $('bodyDiv');

		if (!body || body.down ('.mobile-header'))
		{
			return;
		}

		// The label comes from the Menu panel, which usually arrives after the header is built:
		// relabel () sets it again from the data-label-key (see syncHeader).
		var makeButton = function (cls, html, key, fallback, onclick, showLabel)
		{
			var label = mobile.label (key, fallback);
			var button = new Element ('button', {
				'type' : 'button',
				'class' : cls,
				'title' : label,
				'aria-label' : label,
				'data-label-key' : key,
				'data-label-fallback' : fallback
			});

			button.update (html);
			if (showLabel)
			{
				var span = new Element ('span', { 'class' : 'label' });
				span.textContent = label;
				button.appendChild (span);
			}
			button.observe ('click', onclick);

			return button;
		};

		var header = new Element ('div', { 'class' : 'mobile-header' });

		var village = new Element ('div', { 'class' : 'mobile-village' });
		village.appendChild (makeButton ('prev', '&#8249;', 'prevvillage', 'Previous village', function () { mobile.newsbarClick ('div.newsbar-line .goLeft a'); }));
		village.appendChild (makeButton ('name', '', 'home', 'Home', function () { mobile.newsbarClick ('span.footer-navigation.home'); }));
		village.appendChild (makeButton ('next', '&#8250;', 'nextvillage', 'Next village', function () { mobile.newsbarClick ('div.newsbar-line .goRight a'); }));
		header.appendChild (village);

		var actions = new Element ('div', { 'class' : 'mobile-header-actions' });
		actions.appendChild (makeButton ('minimap', mobile.icons.minimap, 'minimap', 'Map', function () { mobile.togglePanel ('minimap', 'MiniMap'); }, true));
		actions.appendChild (makeButton ('chat', mobile.icons.chat, 'chat', 'Chat', function () { openWindow ('Chat'); }, true));
		actions.appendChild (makeButton ('inbox', mobile.icons.inbox, 'inbox', 'Inbox', function () { openWindow ('messages'); }, true));
		header.appendChild (actions);

		body.appendChild (header);
		body.appendChild (new Element ('div', { 'class' : 'mobile-resources' }));

		// Where newsflashes scroll (Game.gui.newsflashHost): over the resource strip.
		body.appendChild (new Element ('div', { 'class' : 'mobile-newsflash' }));

		// Ticker: the Statusbar's countdowns in one row, shown only while something is in
		// progress; a tap (not on a link inside) opens the full list as a sheet.
		var ticker = new Element ('div', { 'class' : 'mobile-ticker' });
		ticker.observe ('click', function (event)
		{
			if (!Event.findElement (event, 'a'))
			{
				mobile.openTicker ();
			}
		});
		body.appendChild (ticker);

		// Zoom as a floating pair on the map (pinch works too); sheets cover it.
		var zoom = new Element ('div', { 'class' : 'mobile-map-zoom' });

		zoom.appendChild (makeButton ('zoom-in', '+', 'zoomin', 'Zoom in', function () { Game.map.zoomIn (); }));
		zoom.appendChild (makeButton ('zoom-out', '&#8722;', 'zoomout', 'Zoom out', function () { Game.map.zoomOut (); }));

		body.appendChild (zoom);

		// Shown on any touch, hidden again after a few seconds without one.
		mobile.zoomWake = function ()
		{
			zoom.removeClassName ('idle');

			if (mobile.zoomTimer)
			{
				clearTimeout (mobile.zoomTimer);
			}

			mobile.zoomTimer = setTimeout (function ()
			{
				mobile.zoomTimer = null;
				zoom.addClassName ('idle');
			}, mobile.ZOOM_IDLE_MS);
		};

		// Capture phase: the map stops its touch events from bubbling.
		document.addEventListener ('touchstart', mobile.zoomWake, { 'passive' : true, 'capture' : true });
		document.addEventListener ('mousedown', mobile.zoomWake, true);
		mobile.zoomWake ();
	},

	/*
		Copy the current village, the resources and the inbox state from the Newsbar panel
		(hidden on a phone) into the header. Runs after every content change and server response.
	*/
	'syncHeader' : function ()
	{
		var mobile = Game.dolumar.mobile;
		var header = $$('.mobile-header')[0];
		var strip = $$('.mobile-resources')[0];

		if (!header || !strip)
		{
			return;
		}

		mobile.relabel ();

		var panel = $$('.panel.newsbar')[0];
		var village = header.down ('.mobile-village');

		// The village name link is a direct child (the arrows inside are links too).
		var holder = panel ? panel.down ('.newsbar-villagename') : null;
		var source = holder ? holder.childElements ().find (function (el) { return el.tagName == 'A'; }) : null;
		var name = source ? source.innerHTML : '';
		if (village.down ('.name').innerHTML !== name)
		{
			village.down ('.name').update (name);
		}

		// Previous and next only make sense with more than one village (the Build tab lists them).
		if ($$('#menu-main li[data-tab="build"] ul li').length > 1)
		{
			village.addClassName ('switchable');
		}
		else
		{
			village.removeClassName ('switchable');
		}

		var costs = panel ? panel.down ('.newsbar-content span.cost-resources') : null;
		var html = costs ? costs.outerHTML : '';
		if (strip.mobileHtml !== html)
		{
			strip.mobileHtml = html;
			strip.update ('');

			if (costs)
			{
				var copy = $(costs.cloneNode (true));
				strip.appendChild (copy);

				// The amounts count up client-side (Game.gui.updateIncreasing keeps its
				// state on the element): give each copy the state of its original.
				var originals = costs.select ('.increasing');
				copy.select ('.increasing').each (function (el, i)
				{
					if (originals[i] && typeof (originals[i].initial) != 'undefined')
					{
						el.initial = originals[i].initial;
						el.startdate = originals[i].startdate;
						el.income = originals[i].income;
						el.maximum = originals[i].maximum;
					}
				});
			}
		}

		var inbox = header.down ('button.inbox');
		if (panel && panel.down ('span.footer-navigation.messages.full'))
		{
			inbox.addClassName ('has-messages');
		}
		else
		{
			inbox.removeClassName ('has-messages');
		}

		mobile.syncTicker ();
	},

	/*
		Copy the Statusbar's counters (hidden on a phone) into the ticker row and, when open,
		the ticker sheet. The countdowns tick client-side (Game.gui.updateCounters keeps its
		state on the span): each copy gets the state of its original.
	*/
	'syncTicker' : function ()
	{
		var mobile = Game.dolumar.mobile;
		var ticker = $$('.mobile-ticker')[0];

		if (!ticker)
		{
			return;
		}

		var source = $$('.panel.status ul.statusbar')[0];
		var html = source && source.down ('li') ? source.outerHTML : '';

		if (ticker.mobileHtml !== html)
		{
			ticker.mobileHtml = html;
			ticker.update ('');

			if (html)
			{
				ticker.appendChild (mobile.copyCounters (source));
			}

			if (mobile.tickerSheet)
			{
				mobile.tickerSheet.content.update ('');
				if (html)
				{
					mobile.tickerSheet.content.appendChild (mobile.copyCounters (source));
				}
				else
				{
					Game.sheets.dismiss (mobile.tickerSheet);
				}
			}
		}

		if (html)
		{
			$(document.body).addClassName ('has-ticker');
		}
		else
		{
			$(document.body).removeClassName ('has-ticker');
		}
	},

	'copyCounters' : function (source)
	{
		var copy = $(source.cloneNode (true));
		var originals = source.select ('.counter');

		// The links in the list (cancel, withdraw) post to the window they are in
		// (getWindowFromElement looks for a .guiwindow with a dialogId): the Statusbar's.
		var owner = source.up ('.guiwindow');
		if (owner && owner.dialogId)
		{
			copy.addClassName ('guiwindow');
			copy.dialogId = owner.dialogId;
		}

		copy.select ('.counter').each (function (el, i)
		{
			if (originals[i] && typeof (originals[i].counter) != 'undefined')
			{
				el.counter = originals[i].counter;
				el.iStartDate = originals[i].iStartDate;
			}
		});

		// The copies tick from the next poll on.
		if (Game.gui.resetElementsCache)
		{
			Game.gui.resetElementsCache ();
		}

		return copy;
	},

	/*
		The full list of countdowns (with their speed-up and cancel links) as a sheet.
	*/
	'openTicker' : function ()
	{
		var mobile = Game.dolumar.mobile;
		var source = $$('.panel.status ul.statusbar')[0];

		if (mobile.tickerSheet)
		{
			Game.sheets.focus (mobile.tickerSheet);
			return;
		}

		if (!source || !source.down ('li'))
		{
			return;
		}

		var sheet = Game.sheets.open ({
			'dialogId' : mobile.TICKER_DIALOG_ID,
			'title' : mobile.label ('ticker', 'In progress'),
			'closable' : true,
			'modal' : false,
			'className' : 'mobile-ticker-sheet',
			'onClose' : function (closed)
			{
				if (mobile.tickerSheet === closed)
				{
					mobile.tickerSheet = null;
				}
			}
		});

		sheet.content.appendChild (mobile.copyCounters (source));
		mobile.tickerSheet = sheet;
	},

	/*
		Click a link of the Newsbar panel (its village switcher and home links work on the
		panel's own window), so the header needs no copy of their actions.
	*/
	'newsbarClick' : function (selector)
	{
		var panel = $$('.panel.newsbar')[0];
		var target = panel ? panel.down (selector) : null;

		if (target && target.tagName != 'A')
		{
			target = target.up ('a');
		}

		if (target)
		{
			target.click ();
		}
		else
		{
			openWindow ('Newsbar');
		}
	},

	/*
		MiniMap and Guide are panels (not sheets): show or hide them over the map.
		Opens the window if it is not on the page at all; it is shown when it arrives.
	*/
	'togglePanel' : function (className, windowId)
	{
		var mobile = Game.dolumar.mobile;
		var panel = $$('.panel.' + className)[0];

		if (!panel)
		{
			mobile.pendingPanel = className;
			openWindow (windowId);
			return;
		}

		// A visible panel under an open sheet cannot be seen: then the tap brings it forward.
		var covered = Game.sheets.top () !== null;
		mobile.setPanelVisible (className, covered || !panel.hasClassName ('mobile-visible'));
	},

	/*
		Show or hide a panel. The panels lie over the map, under the sheets: showing one
		closes the open sheets, so the map and the panel are what the player sees.
	*/
	'setPanelVisible' : function (className, visible)
	{
		var mobile = Game.dolumar.mobile;
		var panel = $$('.panel.' + className)[0];
		var button = $$('.mobile-header-actions button.' + className)[0];

		if (!panel)
		{
			return;
		}

		if (visible)
		{
			panel.addClassName ('mobile-visible');
			mobile.closeMore ();
			Game.sheets.dismissAll ();

			// The minimap was created inside the hidden panel (0 by 0): size it now.
			if (className == 'minimap' && Game.minimap && Game.minimap.refreshSize)
			{
				setTimeout (Game.minimap.refreshSize, 0);
			}
		}
		else
		{
			panel.removeClassName ('mobile-visible');
		}

		if (button)
		{
			if (visible)
			{
				button.addClassName ('active');
			}
			else
			{
				button.removeClassName ('active');
			}
		}
	},

	/*
		The Guide panel is hidden on a phone, but the server marks advice as read when it
		renders it. Show the panel when it renders new advice (a speech bubble). When the
		player hides the advice, keep the current task on screen as a one-line chip
		(div.objective, from Dolumar_Windows_Guide) while the guide has one, else hide it.
	*/
	'syncGuide' : function ()
	{
		var mobile = Game.dolumar.mobile;
		var panel = $$('.panel.guide')[0];

		if (!panel)
		{
			return;
		}

		var bubble = panel.down ('blockquote.bubble');
		var objective = panel.down ('div.objective');
		var html = bubble ? bubble.innerHTML : (objective ? 'objective:' + objective.innerHTML : null);

		if (html === mobile.guideHtml)
		{
			return;
		}
		mobile.guideHtml = html;

		if (bubble)
		{
			panel.removeClassName ('mobile-chip');
			panel.addClassName ('mobile-visible');
		}
		else if (objective)
		{
			panel.addClassName ('mobile-chip');
			panel.addClassName ('mobile-visible');
		}
		else
		{
			panel.removeClassName ('mobile-chip');
			panel.removeClassName ('mobile-visible');
		}
	},

	/*
		Header button labels from the Menu panel's translated data-label-* attributes.
	*/
	'relabel' : function ()
	{
		var mobile = Game.dolumar.mobile;

		$$('.mobile-header button[data-label-key], .mobile-map-zoom button[data-label-key]').each (function (button)
		{
			var label = mobile.label (button.getAttribute ('data-label-key'), button.getAttribute ('data-label-fallback'));

			if (button.getAttribute ('aria-label') === label)
			{
				return;
			}

			button.setAttribute ('title', label);
			button.setAttribute ('aria-label', label);

			var span = button.down ('span.label');
			if (span)
			{
				span.textContent = label;
			}
		});
	},

	'hasModalSheet' : function ()
	{
		var stack = Game.sheets.stack;

		for (var i = 0; i < stack.length; i ++)
		{
			if (stack[i].modal)
			{
				return true;
			}
		}

		return false;
	},

	'syncModalState' : function ()
	{
		var mobile = Game.dolumar.mobile;

		if (mobile.isMobile () && mobile.hasModalSheet ())
		{
			$(document.body).addClassName ('has-modal-sheet');
		}
		else
		{
			$(document.body).removeClassName ('has-modal-sheet');
		}
	},

	/*
		Stop choosing a building location (Place/Cancel on the map), as Cancel does.
	*/
	'cancelPlacement' : function ()
	{
		var viewer = (typeof (Game.map) != 'undefined') ? Game.map.oScrollMap : null;

		if (viewer && viewer.selecting)
		{
			viewer.cancelLocationClick ();
		}
	},

	/*
		Capture-phase click on the page, mobile mode. The header, resource strip, ticker and
		tab bar lie above #sheets, so they stay tappable when they should not be:
		- while a modal sheet (a confirm) is up, a tap on them does nothing: answer it first;
		- while a building is being placed, a tap on them cancels the placement first, so the
		  hidden window that waits for the location is not closed under it (a tab tap closes
		  the open sheets) and the build is never silently dropped.
	*/
	'guardChrome' : function (event)
	{
		var mobile = Game.dolumar.mobile;
		var target = event.target;

		if (!mobile.isMobile ())
		{
			return;
		}

		if (target && target.nodeType != 1)
		{
			target = target.parentNode;
		}

		if (!target || !target.closest || !target.closest ('.mobile-header, .mobile-resources, .mobile-ticker, .panel.topmenu'))
		{
			return;
		}

		if (mobile.hasModalSheet ())
		{
			event.preventDefault ();
			event.stopPropagation ();
			return;
		}

		mobile.cancelPlacement ();
	},

	/*
		Mark the tab whose window is the top sheet (or More), so the tab bar shows where you are.
	*/
	'syncActiveTab' : function ()
	{
		var mobile = Game.dolumar.mobile;
		var list = $('menu-main');

		if (!list || !mobile.isMobile () || !Game.sheets.top)
		{
			return;
		}

		var top = Game.sheets.top ();
		var current = null;

		if (top && top === mobile.moreSheet)
		{
			current = 'more';
		}
		else if (top && top.dialogId !== null)
		{
			var entry = Game.gui.getWindowFromDialogId (top.dialogId);
			current = entry ? String (entry.sWindowId).toLowerCase () : null;
		}

		list.childElements ().each (function (li)
		{
			var tab = li.hasClassName ('tab-more') ? 'more' : String (li.getAttribute ('data-tab') || '').toLowerCase ();

			if (tab && tab === current)
			{
				li.addClassName ('active-tab');
			}
			else
			{
				li.removeClassName ('active-tab');
			}
		});
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
			link.appendChild (new Element ('span', { 'class' : 'more-icon' }).update (mobile.icons.more));
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

			var tab = (mobile.isMobile () && !mobile.hasModalSheet ()) ? tabFromEvent (event) : null;
			if (!tab)
			{
				return;
			}

			mobile.pressTimer = setTimeout (function ()
			{
				mobile.pressTimer = null;
				mobile.suppressClick = true;
				mobile.cancelPlacement ();
				mobile.openMore (tab);
			}, mobile.LONG_PRESS_MS);
		}, { 'passive' : true });

		// The long press opens More; on Android it would also open the browser's own link
		// menu (the tabs are links), which can cancel the touch before More opens.
		menu.addEventListener ('contextmenu', function (event)
		{
			if (mobile.isMobile () && tabFromEvent (event))
			{
				event.preventDefault ();
			}
		}, false);

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

			// A tab starts a fresh stack: the sheets that were open (and More) are dismissed
			// first. Links inside a sheet still stack, so Back keeps working there.
			if (tabFromEvent (event))
			{
				mobile.closeMore ();
				if (Game.sheets.dismissAll)
				{
					Game.sheets.dismissAll ();
				}
			}
		}, true);
	},

	'toggleMore' : function ()
	{
		var mobile = Game.dolumar.mobile;

		if (mobile.moreSheet && Game.sheets.top () === mobile.moreSheet)
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
		for (var i = mobile.TAB_COUNT; i < items.length; i ++)
		{
			if (!items[i].hasClassName ('tab-more') && items[i] !== tab)
			{
				list.appendChild (mobile.moreItem (items[i]));
			}
		}

		// With more than one village, the tabs with a village list are listed too (with the
		// list), so it is reachable without the long press.
		for (var t = 0; t < mobile.TAB_COUNT && t < items.length; t ++)
		{
			if (items[t] !== tab && items[t].select ('div ul li').length > 1)
			{
				list.appendChild (mobile.moreItem (items[t]));
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

/*
	Regiment editor (squads/editor.tpl): the soldier stepper, the None/Half/All
	buttons, the unit tiles and the equipment stock hints. The form still posts
	through submitForm; this only keeps the fields in step with each other.
*/
Game.dolumar.squads =
{
	'sizeInput' : function (form)
	{
		return form ? form.down ('input[name=size]') : null;
	},

	'clamp' : function (input, value)
	{
		var min = parseInt (input.getAttribute ('min'), 10) || 0;
		var max = parseInt (input.getAttribute ('max'), 10) || 0;
		value = parseInt (value, 10);
		if (isNaN (value))
		{
			value = min;
		}
		return Math.max (min, Math.min (max, value));
	},

	'step' : function (button, delta)
	{
		var form = Game.gui.getFormFromElement (button);
		var input = Game.dolumar.squads.sizeInput (form);
		if (input)
		{
			input.value = Game.dolumar.squads.clamp (input, parseInt (input.value, 10) + delta);
			Game.dolumar.squads.refresh (form);
		}
	},

	'setSize' : function (button, mode)
	{
		var form = Game.gui.getFormFromElement (button);
		var input = Game.dolumar.squads.sizeInput (form);
		if (!input)
		{
			return;
		}

		var max = parseInt (input.getAttribute ('max'), 10) || 0;
		var value = mode == 'all' ? max : (mode == 'half' ? Math.ceil (max / 2) : 0);
		input.value = Game.dolumar.squads.clamp (input, value);
		Game.dolumar.squads.refresh (form);
	},

	'pickUnit' : function (radio)
	{
		var form = Game.gui.getFormFromElement (radio);
		var input = Game.dolumar.squads.sizeInput (form);
		var free = parseInt (radio.getAttribute ('data-free'), 10) || 0;

		$(form).select ('.squad-unit-tile').each (function (tile) { tile.removeClassName ('selected'); });
		$(radio.parentNode).addClassName ('selected');

		if (input)
		{
			input.setAttribute ('max', free);
			input.value = free;
			$(form).select ('.squad-max').each (function (el) { el.update (free); });
		}

		// Keep the suggested name in step with the unit, unless the player typed one.
		var name = form.down ('input[name=squadName]');
		if (name && name.getAttribute ('data-suggested') == '1')
		{
			name.value = radio.getAttribute ('data-suggestion');
		}

		Game.dolumar.squads.refresh (form);
	},

	// Grey out items there aren't enough of for this many soldiers.
	'refresh' : function (form)
	{
		var input = Game.dolumar.squads.sizeInput (form);
		if (!input)
		{
			return;
		}

		var size = parseInt (input.value, 10) || 0;
		var isShort = false;

		$(form).select ('select.squad-item').each (function (select)
		{
			$A (select.options).each (function (option)
			{
				var left = option.getAttribute ('data-left');
				if (left === null)
				{
					return;
				}

				var enough = parseInt (left, 10) >= size;
				option.disabled = !enough && !option.selected;

				if (!enough && option.selected)
				{
					isShort = true;
				}
			});
		});

		var warning = form.down ('.squad-short');
		if (warning)
		{
			warning.style.display = isShort ? '' : 'none';
		}
	}
};

Event.observe (window, 'load', Game.dolumar.init, false);
