<?php $this->setTextSection ('movevillage', 'premium'); ?>

<?php if (isset ($error)) { ?>
	<p class="false">
		<?=$this->putIntoText ($this->getText ($error), array ('days' => $days)); ?>
	</p>
<?php } ?>

<?php if (isset ($proposal)) { ?>
	<div class="fancybox">
		<p><?=$this->getText ('proposal')?> (<?=$x?>,<?=$y?>)</p>
		
		<ul class="actions">
			<li>
				<a href="<?=$confirm_url?>" target="_BLANK" onclick="windowAction(this,{'action':'overview'}); return !Game.gui.openWindow(this, 450, 190);"><?=$this->getText ('approve'); ?></a>
			</li>
			
			<li>
				<a href="javascript:void(0);" onclick="windowAction(this,{'action':'movevillage','village':<?=$village?>,'x':<?=$desired_x?>,'y':<?=$desired_y?>,'offset':<?=$offset?>});"><?=$this->getText ('decline')?></a>
			</li>
		</ul>
		
		<p><?=$this->getText ('aboutlooking'); ?></p>
	</div>
<?php } ?>

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
						<label for="location">
							<?=$this->getText ('location');?><br />
							<a href="javascript:void(0);" onclick="selectLocation (this, {'action':'movevillage','do':'selectlocation'}, true);"><?=$this->getText ('selectlocation');?></a>
						</label>
						<input type="text" class="number small coordinate" id="locx" name="x" value="<?=$desired_x?>" />
						<input type="text" class="number small coordinate" id="locy" name="y" value="<?=$desired_y?>" />
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
