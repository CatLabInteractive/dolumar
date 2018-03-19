<?php $this->setTextSection ('movebuilding', 'premium'); ?>

<div class="fancybox">
	<p><?=$this->getText ('about'); ?></p>

	<?php if (isset ($list_villages)) { ?>
		<form method="post" onsubmit="return submitForm(this);">
			<input type="hidden" class="hidden" name="action" value="movevillage" />
	
			<fieldset class="centered">
				<legend><?=$this->getText ('movevillage');?></legend>
	
				<ol>
					<li>
						<label for="village"><?=$this->getText ('village'); ?></label>
						<select name="village" id="village" class="village">
							<?php foreach ($list_villages as $v) { ?>
								<?php if ($v['id'] == $village) { ?>
									<option value="<?=$v['id']?>" selected="selected"><?=$v['name']?></option>
								<?php } else { ?>
									<option value="<?=$v['id']?>"><?=$v['name']?></option>
								<?php } ?>
							<?php } ?>
						</select>
					</li>
				
					<li>
						<div class="buttons">
							<button type="submit"><span><?=$this->getText ('searchlocation')?></span></button>
						</div>
					</li>
				</ol>
			</fieldset>
		</form>
	<?php } ?>
</div>

<p><a href="javascript:void(0);" onclick="windowAction(this,{'action':'overview'});"><?=$this->getText ('overview', 'shop'); ?></a></p>
