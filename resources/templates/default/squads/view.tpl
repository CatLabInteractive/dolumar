<?php $this->setTextSection ('squad', 'squads'); ?>

<!--<h1><?=$squads?></h1>-->

<?php 
	$current_tab = 'squad';
	include ('tabs.phpt'); 
?>

<?php if (isset ($error)) { echo '<p class="false">'.$error.'</p>'; } ?>

<form onsubmit="return submitForm(this);">

	<input type="hidden" class="hidden" name="page" value="squad" />
	<input type="hidden" class="hidden" name="id" value="<?=$squadId?>" />

	<h3><?=$name?></h3>

	<?php if (isset ($list_units)) { ?>
		<?php foreach ($list_units as $unit) { ?>
		
			<p class="sidenote"><a href="javascript:void(0);" onclick="confirmAction (this, {'page':'squad','id':<?=$squadId?>,'remove':<?=$unit[3]?>}, '<?=$confirm?>');"><?=$remove?></a></p>
			<h3><?=$unit[1]?> <?=$unit[0]?></h3>

			<fieldset>
				<div class="unitimage">
					<img src="<?=$unit[2]?>" alt="<?=$unit[0]?>" title="<?=$unit[0]?>" />
				</div>
			
				<div class="equipment">
					<?php foreach ($list_equipment as $v) { ?>
						<label><?=$v[0]?>:</label>
						<select onchange="submitForm(this.form);" name="<?php echo $unit[3] . '_' . $v[2]; ?>">
							<option value="0"><?=$noItem?></option>
				
							<?php foreach ($v[1] as $item) { ?>
								<?php if (isset ($unit[4][$v[2]]) && $unit[4][$v[2]] == $item->getId ()) { ?>
									<option value="<?=$item->getId ()?>" selected="selected"><?=$item->getName ()?></option>
								<?php } else { ?>
									<option value="<?=$item->getId ()?>"><?=$item->getName ()?></option>
								<?php } ?>
							<?php } ?>
						</select>
					<?php } ?>
				</div>
				
				<div class="stats"><?=$unit['stats']?></div>
			</fieldset>
			
		<?php } ?>
		
	<?php } else { ?>
		<p class="false"><?=$noUnits?></p>
	<?php } ?>
</form>

<!--
<p>
	<?=$toAdd[0]?><a href="javascript:void(0);" onclick="windowAction (this, {'page':'addUnits','id':<?=$squadId?>});"><?=$toAdd[1]?></a><?=$toAdd[2]?><br />
	<?=$toRemove[0]?><a href="javascript:void(0);" onclick="windowAction (this, {'page':'removeUnits','id':<?=$squadId?>});"><?=$toRemove[1]?></a><?=$toRemove[2]?><br />
	<?=$toReturn[0]?><a href="javascript:void(0);" onclick="windowAction (this, {'page':'overview'});"><?=$toReturn[1]?></a><?=$toReturn[2]?>
</p>
-->
