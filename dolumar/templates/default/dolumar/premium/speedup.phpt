<?php $this->setTextSection ('speedup', 'statusbar'); ?>

<p class="premium">
	<span class="premium-icon">
		<?= $this->getText ('description_' . $type, null, null, $this->getText ('description')); ?>
	</span>
</p>

<form onsubmit="return submitForm (this);">
	<fieldset class="simpleform">
		<ol>
			<li>
				<label for="duration"><?=$this->getText ('selectduration')?></label>

				<select name="duration" id="duration" class="large">
					<?php
						$maxrounds = ceil ($timeleft / $unit);
						for ($i = 1; $i <= $maxrounds; $i ++ ) { ?>

						<option value="<?=$i?>" <?php if ($i == $maxrounds) { ?>selected="selected"<?php } ?> >

							<?php 
								$time = Neuron_Core_Tools::getDurationText ($i * $unit);
								$tprice = $i * $price
							?>

							<?=$this->putIntoText ($this->getText ('selectoption'), array ('time' => $time, 'price' => $tprice)); ?>
						</option>

					<?php }?>
				</select>
			</li>

			<li>
				<button type="submit"><span><?=$this->getText ('submit')?></span></button>
			</li>
		</ol>
	</fieldset>
</form>