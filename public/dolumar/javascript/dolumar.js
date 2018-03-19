Game.dolumar =
{
	/*
		Initialize the whole game.
	*/
	'init' : function ()
	{
		
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

Event.observe (window, 'load', Game.dolumar.init, false);
