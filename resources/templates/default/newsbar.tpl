<div class="newsbar-line">
	<div class="goLeft">
		<a href="javascript:void(0)" onclick="windowAction(this,{'page':<?=$previousPage?>});" style="text-decoration: none;"><span>«</span></a>
	</div>

	<div class="goRight">
		<a href="javascript:void(0)" onclick="windowAction(this,{'page':<?=$nextPage?>});" style="text-decoration: none;"><span>»</span></a>
	</div>

	<div style="float: right; margin-right: -28px; margin-top: 2px;">
		<a href="javascript:void(0);" onclick="mapIsoJump('<?=$homecors[0]?>','<?=$homecors[1]?>'); return false;">
			<span class="footer-navigation home"><span><?=$home?></span></span>
		</a>
		<a href="javascript:void(0);" onclick="toggleMinimap();" title="<?=$minimap?>">
			<span class="footer-navigation minimap"><span><?=$minimap?></span></span>
		</a>	
		<a href="javascript:void(0);" onclick="openWindow('messages');" title="<?=$inbox?>">
			<span class="footer-navigation messages <?=$hasMessages ? 'full blink' : 'empty'?>"><span><?=$inbox?></span></span>
		</a>
	</div>

	<div class="newsbar-content">
		<?=$content?>
	</div>
</div>

<div class="newsbar-villagename">
	<div class="goLeft">
		<a href="javascript:void(0)" onclick="windowAction(this,{'page':<?=$previousPage?>});" style="text-decoration: none;"><span>»</span></a>
	</div>

	<div class="goRight">
		<a href="javascript:void(0)" onclick="windowAction(this,{'page':<?=$nextPage?>});" style="text-decoration: none;"><span>«</span></a>
	</div>

	<?php if (isset ($current_village)) { ?>
		<a href="javascript:void(0);" onclick="return openWindow('villageProfile',{'village':<?=$current_village_id?>});"><?=$current_village?></a>
	<?php } ?>
</div>
