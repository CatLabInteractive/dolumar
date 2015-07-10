<h1><?=$title?></h1>

<?php if (isset ($fightResult)) { echo '<p>'.$fightResult.'</p>'; } ?>

<form onsubmit="submitBattleCalculater(this); return false;" class="battleCalc">

<h2><?=$fightBonus?></h2>
<table class="tlist unitList">
	<tr>
		<th style="width: 50%; text-align: center;"><?php echo $attackingVillage; ?></th>
		<th style="text-align: center;"><?php echo $defendingVillage; ?></th>

	</tr>
	<tr>
		<td style="text-align: center;">
			<input type="text" style="width: 25px;" value="<?=$bonus_def_value?>" id="bonus_def" /> %
		</td>

		<td style="text-align: center;">
			<input type="text" style="width: 25px;" value="<?=$bonus_att_value?>" id="bonus_att" /> %
		</td>
	</tr>
</table>

<?php foreach (array ('attacking', 'defending') as $v) { ?>

	<h2 style="margin-bottom: 0px;">

		<?php $unit_text = $v.'_units'; ?>
		<?=$$unit_text?>
		<a class="addUnit" href="javascript:void(0);" onClick="duplicateElement(document.getElementById('bcdiv_<?=$v?>'));">+</a>

	</h2>

	<div id="bc_<?=$v?>">

		<?php foreach ($$v as $unitK => $unit) { ?>

		<?php if ($unitK > 0) { ?>
			<div>
		<?php } else { ?>
			<div id="bcdiv_<?=$v?>">
		<?php } ?>
			<fieldset style="border: none; padding: 0px; margin: 0px;">
				<table class="tlist unitList" style="margin-top: 2px; width: 400px;">

					<tr>
						<td style="text-align: center;width: 25%;">

							<img src="<?=IMAGE_URL?>stats/attack.gif" title="<?=$unit_attack?>" />
							<input type="text" name="attAt" value="<?=$unit['attAt']?>" />/<input type="text" name="attDef" value="25" />

						</td>
							
						<td style="width: 25%; vertical-align: middle; text-align: center;">
						
							<img src="<?=IMAGE_URL?>stats/infDef.gif" title="<?=$unit_defIn?>" />
							<input type="text" name="defIn" value="<?=$unit['defIn']?>" />%

						</td>
							
						<td style="width: 25%; text-align: center;">

							<img src="<?=IMAGE_URL?>stats/ranDef.gif" title="<?=$unit_defAr?>" />
							<input type="text" name="defAr" value="<?=$unit['defAr']?>" />%

						</td>
						
						<td style="padding-left: 5px; text-align: center;">

							<img src="<?=IMAGE_URL?>stats/village.gif" title="<?=$unit_inVillage?>" />
							<input type="text" name="amount" value="<?=$unit['amount']?>" style="width: 40px;" />

						</td>
					</tr>

					<tr>
						<td style="text-align: center;">

							<img src="<?=IMAGE_URL?>stats/health.gif" title="<?=$unit_health?>" />
							<input type="text" name="health" value="<?=$unit['health']?>" style="width: 60px;" />
							
						</td>
						
						<td style="text-align: center;">

							<img src="<?=IMAGE_URL?>stats/cavDef.gif" title="<?=$unit_defCav?>" />
							<input type="text" name="defCav" value="<?=$unit['defCav']?>" />%

						</td>
						
						<td style="text-align: center;">

							<img src="<?=IMAGE_URL?>stats/magDef.gif" title="<?=$unit_defMag?>" />
							<input type="text" name="defMag" value="<?=$unit['defMag']?>" />%

						</td>
						
						<td style="padding-left: 5px; text-align: center;">

							<?php $o = array ('defIn', 'defAr', 'defCav', 'defMag'); ?>
							<select name="atType" style="width: 80px;">
							<?php foreach ($o as $v) { ?>
								<option
									value="<?=$v?>"
									<?php if ($unit['atType'] == $v) { echo 'selected="selected"'; } ?>
								><?=$$v?></option>
							<?php } ?>
							</select>
							
						</td>
					</tr>

					<?php if (isset ($unit['diedAmount'])) { ?>
					<tr>
						<td colspan="4" style="text-align: center;">
							<?=$unit['diedAmount']?>
						</td>
					</tr>
					<?php } ?>
				</table>
			</fieldset>
		</div>
		<?php } ?>
	</div>
<?php } ?>

<p>
	<button type="submit"><?=$submit?></button>
</p>
</form>
