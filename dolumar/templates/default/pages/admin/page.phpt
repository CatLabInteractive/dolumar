<div id="container">

	<h1>Dolumar Control Panel</h1>

	<div id="navigation">			
		<div class="chatmoderators">
			<ul>
				<li>
					<a href="<?=$searchplayers?>">Search players</a>
				</li>
			
				<li>
					<a href="<?=$messages?>">Your messages</a>
				</li>
			</ul>
		</div>
		
		<?php if ($isModerator) { ?>
			<div class="moderators">
				<ul>
					<?php if (isset ($multiaccounts)) { ?>
						<li>
							<a href="<?=$multiaccounts?>">Check multi accounts</a>
						</li>
					<?php } ?>
				</ul>
			</div>
		<?php } ?>
		
		<?php if ($isAdmin) { ?>
			<div class="administrator">
				<ul>
					<?php if (isset ($execute)) { ?>
						<li>
							<a href="<?=$execute?>">Execute actions</a>
						</li>
					<?php } ?>
				</ul>
			</div>
		<?php } ?>
		
		<?php if ($isDeveloper) { ?>
			<div class="developers">
				<ul>
					<?php if (isset ($bonusbuilding)) { ?>
						<li>
							<a href="<?=$bonusbuilding?>">Bonus buildings</a>
						</li>
					<?php } ?>
				</ul>
			</div>
		<?php } ?>

		<div>
			<p>Please check the player support center regularly.</p>
		</div>
	
		<div>
			<ul>
				<li class="important">
					<a href="http://support.dolumar.com/scp/" target="_BLANK">Player Support Center</a>
				</li>
							
				<li>
					<a href="<?=$onlineurl?>" target="_BLANK" onclick="return !window.open('<?=$onlineurl?>', 'Online players', 'menubar=no,toolbar=no,width=400,height=200');">Online player counter</a>
				</li>
			</ul>
		</div>
	</div>

	<div id="content">
		<div>
			<?=$body?>
		</div>
	</div>
	
	<div style="clear: both;"></div>
</div>
