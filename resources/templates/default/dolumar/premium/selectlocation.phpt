<?php $this->setTextSection ('movebuilding', 'premium'); ?>

<?php if (isset ($freetime)) { ?>
	<p class="premium">
		<span class="premium-icon"></span>
		<?=
		$this->putIntoText
		(
			$this->getText ('freemoveleft'),
			array
			(
				'duration' => '<strong>'.$freetime.'</strong>'
			)
		)
		?>
	</p>
<?php } ?>

<?php if (isset ($confirm_url)) { ?>
	<p class="information">
		<?=
		$this->putIntoText
		(
			$this->getText ('spendcredits'),
			array
			(
				'building' => $buildingname,
				'here' => '<a href="'.$confirm_url.'"  target="_BLANK" onclick="windowAction(this,{\'action\':\'overview\'}); return !Game.gui.openWindow(this, 450, 190);">'.$this->getText ('here').'</a>',
				'credits' => $credits,
				'location' => '['.$locx.','.$locy.']'
			)
		)
		?>
	</p>
<?php } ?>

<?php if (isset ($moved)) { ?>
	<p class="true"><?=$this->getText ('moved'); ?></p>
<?php } ?>

<?php if (isset ($error)) { ?>
	<p class="false"><?=$this->getText ($error, 'buildError', 'building'); ?></p>
<?php } ?>

<div class="fancybox">
	<p><?=$this->getText ('about'); ?></p>

	<form method="post" onsubmit="return submitForm(this);">
		<input type="hidden" class="hidden" name="action" value="movebuilding" />
		<input type="hidden" class="hidden" name="village" value="<?=$village?>" />
		<input type="hidden" class="hidden" name="building" value="<?=$building?>" />

		<fieldset class="centered">
			<legend><?=$this->getText ('newlocation');?></legend>

			<ol>					
				<li>
					<label for="location">
						<?=$this->getText ('location');?><br />
						<a href="javascript:void(0);" onclick="selectLocation (this, {'action':'movebuilding','village':<?=$village?>,'building':<?=$building?>});"><?=$this->getText ('selectlocation');?></a>
					</label>
					<input type="text" class="number small coordinate" id="locx" name="x" value="<?=$locx?>" />
					<input type="text" class="number small coordinate" id="locy" name="y" value="<?=$locy?>" />
				</li>					
			
				<li>
					<div class="buttons">
						<button type="submit"><span><?=$this->getText ('submitmove')?></span></button>
					</div>
				</li>
			</ol>
		</fieldset>
	</form>
</div>

<p>
	<a href="javascript:void(0);" onclick="windowAction(this,{'action':'overview'});"><?=$this->getText ('overview', 'shop'); ?></a><br />
	<a href="javascript:void(0);" onclick="windowAction(this,{'action':'movebuilding','village':<?=$village?>});"><?=$this->getText ('moveotherbuilding'); ?></a>
</p>
