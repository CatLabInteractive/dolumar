<?php $this->setTextSection ('buyrunes', 'premium'); ?>

<div class="fancybox">

	<?php if (isset ($error)) { ?>
		<p class="false">
			<?=
				Neuron_Core_Tools::putIntoText
				(
					$this->getText ($error),
					array
					(
						'left' => $buyable,
						'total' => $maximum
					)
				);
			?>
		</p>
	<?php } ?>

	<p><?=Neuron_Core_Tools::putIntoText
	(
		$this->getText ('about'),
		array
		(
			'left' => $buyable,
			'total' => $maximum
		)
	);?></p>

	<?php if (isset ($villages)) { ?>
		<form method="post" onsubmit="return submitForm(this);">
			<input type="hidden" class="hidden" name="action" value="buyrunes" />
	
			<fieldset class="centered">
				<legend><?=$this->getText ('buyrunes');?></legend>
	
				<ol>
					<li>
						<label for="village"><?=$this->getText ('village'); ?></label>
						<select name="village" id="village" class="village">
							<?php foreach ($villages as $v) { ?>
								<option value="<?=$v['id']?>"><?=$v['name']?></option>
							<?php } ?>
						</select>
					</li>
					
					<?php foreach ($runes as $v) { ?>
						<li>
							<label for="<?=$v?>"><?=ucfirst ($this->getText ($v, 'runeDouble', 'main'))?>:</label>
							<input type="text" id="<?=$v?>" name="<?=$v?>" value="0" class="number small" />
						</li>
					<?php } ?>
				
					<li>
						<div class="buttons">
							<button type="submit"><span><?=$this->getText ('buyrunes'); ?></span></button>
						</div>
					</li>
				</ol>
			</fieldset>
		</form>
	<?php } ?>
</div>

<p><a href="javascript:void(0);" onclick="windowAction(this,{'action':'overview'});"><?=$this->getText ('overview', 'shop'); ?></a></p>
