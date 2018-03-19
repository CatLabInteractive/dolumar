<?php if ($section == 'overview') { ?>

	<p><?=$about?></p>

	<h3><?=$capacity?></h3>
	<div class="fancybox">
		<table>
			<tr>
				<th><?=$buildingCapacity?></th>
				<th><?=$totalCapacity?></th>
				<th><?=$filling?></th>
			</tr>
	
			<tr>
				<td><?=$capacity_value?></td>
				<td><?=$totalCapacity_value?></td>
				<td><?=$capacity_left?>%</td>
			</tr>

			<tr>
				<td colspan="3">
					<?php if ($capacity_left > 0) { ?> <img src="<?=IMAGE_URL?>bar1.gif" style="height: 5px; width: <?=$capacity_left?>%;" /><?php } if ($capacity_right > 0) { ?><img src="<?=IMAGE_URL?>bar2.gif" style="height: 5px; width: <?=$capacity_right?>%;" /><?php } ?>
				</td>
			</tr>
		</table>
	</div>

	<h3><?=$train?></h3>

	<?php if (isset ($list_units)) { foreach ($list_units as $v) { ?>
	<table>
		<tr>
			<th colspan="3"><?=$v[0]?></th>
			<td colspan="2" style="text-align: right;"><a href="javascript:void(0);" onclick="windowAction(this,{'unit':'<?=$v[2]?>'});"><?=$trainUnits?></a></td>
		</tr>

		<tr>
			<td rowspan="2"><img src="<?=$v[1]['image']?>" title="<?=$v[0]?>" /></td>
			
			<td><img src="<?=IMAGE_URL?>stats/melee.gif" title="<?=$unit_melee?> (<?=$v[1]['atTypeTrans']?>)" /> <?=$v[1]['melee']?> (<span class="stat-frontage"><?=$v[1]['frontage']?></span>)</td>
			
			<td><img src="<?=IMAGE_URL?>stats/infDef.gif" title="<?=$unit_defIn?>" /> <?=$v[1]['defIn']?>%</td>
			<td><img src="<?=IMAGE_URL?>stats/ranDef.gif" title="<?=$unit_defAr?>" /> <?=$v[1]['defAr']?>%</td>
			
			<td><img src="<?=IMAGE_URL?>stats/health.gif" title="<?=$unit_health?>" /> <?=$v[1]['hp']?></td>
		</tr>

		<tr>
			<td><img src="<?=IMAGE_URL?>stats/shooting.gif" title="<?=$unit_shooting?>(<?=$v[1]['atTypeTrans']?>)" /> <?=$v[1]['shooting']?></td>
			
			<td><img src="<?=IMAGE_URL?>stats/cavDef.gif" title="<?=$unit_defCav?>" /> <?=$v[1]['defCav']?>%</td>
			<td><img src="<?=IMAGE_URL?>stats/magDef.gif" title="<?=$unit_defMag?>" /> <?=$v[1]['defMag']?>%</td>
			
			<td>&nbsp;</td>
		</tr>
	</table>
	<?php } } else { echo '<p>'.$noUnits.'</p>'; } ?>
	
<?php } elseif ($section == 'notFound') { ?>

	<h3><?=$train?></h3>
	<p><?=$error?></p>

<?php } elseif ($section == 'train') { ?>

	<h3><?=$train?></h3>

	<?php if (isset ($list_errors)) { foreach ($list_errors as $v) { ?>
		<p class="false"><?=$v[0]?></p>
	<?php } } ?>
	
	<table>
		<tr>
			<th colspan="5" class="alignLeft"><?=$unit?></th>
		</tr>

		<tr>
			<td rowspan="2"><img src="<?=$stats['image']?>" title="<?=$unit?>" /></td>
			
			<td><img src="<?=IMAGE_URL?>stats/melee.gif" title="<?=$unit_melee?> (<?=$stats['atTypeTrans']?>)" /> <?=$stats['melee']?>(<span class="stat-frontage"><?=$stats['frontage']?></span>)</td>
			
			<td><img src="<?=IMAGE_URL?>stats/infDef.gif" title="<?=$unit_defIn?>" /> <?=$stats['defIn']?>%</td>
			<td><img src="<?=IMAGE_URL?>stats/ranDef.gif" title="<?=$unit_defAr?>" /> <?=$stats['defAr']?>%</td>
			
			<td><img src="<?=IMAGE_URL?>stats/health.gif" title="<?=$unit_health?>" /> <?=$stats['hp']?></td>
		</tr>

		<tr>
			<td><img src="<?=IMAGE_URL?>stats/shooting.gif" title="<?=$unit_shooting?> (<?=$stats['atTypeTrans']?>)" /> <?=$stats['shooting']?></td>
			
			<td><img src="<?=IMAGE_URL?>stats/cavDef.gif" title="<?=$unit_defCav?>" /> <?=$stats['defCav']?>%</td>
			<td><img src="<?=IMAGE_URL?>stats/magDef.gif" title="<?=$unit_defMag?>" /> <?=$stats['defMag']?>%</td>
			
			<td>&nbsp;</td>
		</tr>
	</table>

	<h4><?=$trainingCost?>:</h4>
	<p class="resources-cost"><?=$trainingCost_value?></p>

	<h4><?=$consCost?>:</h4>
	<p class="resources-cost"><?=$consCost_value?></p>

	<?php if (isset ($maxtrainable_value)) { ?>
		<p class="information">
			<span class="info-icon">
				<?=$maxtrainable_value?>
			</span>
		</p>
	<?php } ?>

	<form onsubmit="return submitForm(this);">
		<fieldset>
			<legend><?=$trainUnits?></legend>
			
			<input type="hidden" name="unit" value="<?=$unit_value?>" class="hidden" />
			
			<ol>
				<li>
					<label><?=$amount?>:</label>
					<input type="text" value="0" name="amount" />
				</li>
				
				<li>
					<div class="buttons">
						<button type="submit"><span><?=$trainSubmit?></span></button>
					</div>
				</li>
			</ol>
		</fieldset>	
	</form>

	<p><?=$otherUnit[0]?> <a href="javascript:void(0);" onclick="windowAction(this, {'back':'back'});"><?=$otherUnit[1]?></a> <?=$otherUnit[2]?></p>

<?php } ?>
