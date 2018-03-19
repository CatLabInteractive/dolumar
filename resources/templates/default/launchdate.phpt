<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html>
	<head>
		<title>Dolumar <?=$name?></title>
		<link href="<?=ABSOLUTE_URL?>dolumar/css/launch.css" rel="stylesheet" type="text/css" > 
		
		<script type="text/javascript" src="<?=ABSOLUTE_URL?>gameserver/javascript/prototype/prototype.js"></script>
		<script type="text/javascript" src="<?=ABSOLUTE_URL?>gameserver/javascript/scriptaculous/scriptaculous.js"></script>
		
		<script type="text/javascript">
			function updateCounters ()
			{
				var cs = $$('.counter');
				var hasReachedZero = false;
		
				var now = Math.floor ((new Date()).getTime () / 1000);
		
				for (var i = 0; i < cs.length; i ++)
				{
					if (typeof(cs[i].counter) == 'undefined')
					{
						var sp = cs[i].innerHTML.split (':');
						cs[i].counter = parseInt(sp[0], 10) * 3600 + parseInt(sp[1], 10) * 60 + parseInt(sp[2], 10);
						cs[i].iStartDate = Math.floor ((new Date()).getTime() / 1000);
					}
			
					else if (cs[i].counter != false)
					{
						var counter = cs[i].counter - (now - cs[i].iStartDate);
		
						if (counter >= 0)
						{						
							var h = Math.floor (counter / 3600);
							var m = Math.floor ((counter - h * 3600) / 60);
							var s = counter - h * 3600 - m * 60;
			
							if (h < 10) h = '0' + h;
							if (m < 10) m = '0' + m;
							if (s < 10) s = '0' + s;
			
							cs[i].innerHTML = h + ':' + m + ':' + s;
						}
						else
						{
							// Put the counter to false.
							cs[i].counter = false;
							hasReachedZero = true;
						}
					}
					
					// A little fun
					if (counter % 10 == 0 && counter > 0)
					{							
						Effect.Pulsate('logo', {pulses: 1, duration: 1.5, from: 0.5});
					}
					
					else if (counter == 4)
					{
						$('countdown').fade
						(
							{ 
								duration: 5.0,
								afterFinish : function ()
								{
									window.location.reload();
								}
							}
						);
					}
				}
			}
			
			new PeriodicalExecuter(updateCounters, 1);
		</script>
	</head>
	
	<body>
		<div id="countdown">
			<h1 id="logo"><span>Dolumar</span></h1>
			<p>Game starts in <?=$launchdate?></p>
		</div>
	</body>
</html>
