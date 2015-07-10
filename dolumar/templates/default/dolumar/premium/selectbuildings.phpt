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

<div class="fancybox">
	<p><?=$this->getText ('about'); ?></p>

	<?php if (isset ($list_buildings)) { ?>
		<form method="post" onsubmit="return submitForm(this);">
			<input type="hidden" class="hidden" name="action" value="movebuilding" />
			<input type="hidden" class="hidden" name="village" value="<?=$village?>" />
	
			<fieldset class="centered">
				<legend><?=$this->getText ('selectbuilding');?></legend>
	
				<ol>
					<li>
						<label for="building"><?=$this->getText ('buildings')?></label>
						<select name="building" id="building" class="building">
							<?php foreach ($list_buildings as $v) { ?>
								<option value="<?=$v['id']?>"><?=$v['location']?> <?=$v['name']?></option>
							<?php } ?>
						</select>
					</li>
				
					<li>
						<div class="buttons">
							<button type="submit"><span><?=$this->getText ('submitmove')?></span></button>
						</div>
					</li>
				</ol>
			</fieldset>
		</form>
	<?php } ?>
</div>

<p><a href="javascript:void(0);" onclick="windowAction(this,{'action':'overview'});"><?=$this->getText ('overview', 'shop'); ?></a></p>
