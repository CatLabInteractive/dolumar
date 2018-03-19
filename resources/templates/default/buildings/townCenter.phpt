<?php $this->setTextSection ('townCenter', 'buildings'); ?>

<div class="fancybox">

	<h3><?= $this->getText ('scoutforrunes'); ?></h3>
	<p><?= $this->getText ('aboutRunes1'); ?></p>

	<ul class="actions">
		<li>
			<a href="javascript:void(0);" onclick="windowAction(this, {'do':'scout'});"><?= $this->getText ('scout'); ?></a>
		</li>
	</ul><br />

</div>

<!--
<p><a href="javascript:void(0);" onclick="windowAction(this,'do=explore');">Explore</a></p>
-->

<h3><?=$changeName?></h3>

<?php if (isset ($changename_err)) { ?>
	<p class="false"><?=$changename_err?></p>
<?php } ?>

<form onsubmit="return submitForm(this);">
	<fieldset>
		<label><?=$villageName?>:</label>
		<input name="villageName" type="text" value="<?=$villageName_value?>" />
		
		<input type="submit" value="<?=$change?>" class="button" />
	</fieldset>
</form>

<p>
	<?=$overview[0]?><a href="javascript:void(0);" onclick="openWindow('VillageOverview',{'vid':<?=$vid?>});"><?=$overview[1]?></a><?=$overview[2]?><br />
	<?=$techniques[0]?><a href="javascript:void(0);" onclick="openWindow('technologies',{'vid':<?=$vid?>});"><?=$techniques[1]?></a><?=$techniques[2]?>
</p>
