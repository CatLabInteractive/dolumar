<h2><?=$this->getText ('title')?></h2>

<?php if (isset ($duration)) { ?>
	<p class="false"><?=$this->getText ('durationwarning'); ?></p>
<?php } ?>

<?php if (isset ($honour)) { ?>
	<p class="false"><?=$this->putIntoText
	(
		$this->getText ('honourwarning'),
		array
		(
			'honour' => $honour,
			'size' => $size
		)
	); ?></p>
<?php } ?>

<?php if (isset ($error)) { ?>
	<p class="false"><?=$this->getText ($error)?></p>
<?php } ?>
<table>
	<tr>
		<td><?=$this->getText ('target')?>:</td>
		<td><a href="javascript:void(0);" onclick="openWindow('villageProfile', {'village':<?=$targetId?>});"><?=$target?></a></td>
	</tr>
	
	<tr>
		<td><?=$this->getText ('distance');?>:</td>
		<td><?=$distance?></td>
	</tr>
</table>

<form onsubmit="return submitForm(this);">

	<?php foreach ($list_slots as $v) { ?>
		<input type="hidden" name="slot<?=$v['id']?>" value="<?=$v['unit']?>" class="hidden" />
	<?php } ?>
	
	<input type="hidden" name="confirm" value="yes" class="hidden" />
	
	<?php if (isset ($list_specialunits)) { ?>
	
		<h3><?=$this->getText ('special')?></h3>
		<p><?=$this->getText ('about');?></p>
	
		<fieldset class="specialUnits">
			<legend><?=$this->getText ('specialUnits');?></legend>
				<?php foreach ($list_specialunits as $unit) { ?>
					<div>
						<input type="checkbox" class="checkbox" name="special_<?=$unit['id']?>" value="send" id="<?=$templateID?>_<?=$unit['id']?>_check" />
						<label class="checkbox" for="<?=$templateID?>_<?=$unit['id']?>_check"><?=$unit['name']?></label>
						<select name="action_<?=$unit['id']?>" class="specialAction" id="<?=$templateID?>_<?=$unit['id']?>_select">
							<option value="0">&nbsp;</option>
							<?php foreach ($unit['actions'] as $action) { ?>
								<option value="<?=$action['id']?>"><?=$action['name']?> (<?=$this->getText ('cost')?>: <?=$action['cost']?>)</option>
							<?php } ?>
						</select>
					</div>
				<?php } ?>
		
		</fieldset>
	<?php } ?>
	
	<button type="submit"><?=Neuron_Core_Tools::putIntoText ($this->getText('confirm'), array ('village' => $target));?></button>
</form>
