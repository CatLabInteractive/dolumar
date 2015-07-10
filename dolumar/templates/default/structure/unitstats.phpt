<?php if (isset ($list_units)) { 
	foreach ($list_units as $v) { ?>
		<div class="unit fancybox">
			<table class="table-units">
				<tr class="header">
					<th colspan="2"> <?=$v['total']?> <?=$v['name']?></th>
					<td colspan="2" style="text-align: center;"><?=$v['type']?></td>
					<td style="text-align: right;"><?=$v['consumption']?></td>
				</tr>

				<tr class="stats">
					<td rowspan="2" style="width: 50px;" ><img src="<?=$v['image']?>" title="<?=$v['name']?>" style="width: 50px; margin-bottom: 0px;" /></td>
	
					<td style="width: 20%;"><img src="<?=IMAGE_URL?>stats/melee.gif" title="<?=$unit_melee?>" /> <?=$v['stats']['melee']?> (<span class="stat-frontage"><?=$v['stats']['frontage']?></span>)</td>
					<td style="width: 20%;"><img src="<?=IMAGE_URL?>stats/infDef.gif" title="<?=$unit_defIn?>" /> <?=$v['stats']['defIn']?>%</td>
					<td style="width: 20%;"><img src="<?=IMAGE_URL?>stats/cavDef.gif" title="<?=$unit_defCav?>" /> <?=$v['stats']['defCav']?>%</td>		
					<td><img src="<?=IMAGE_URL?>stats/health.gif" title="<?=$unit_health?>" /> <?=$v['stats']['hp']?></td>
				</tr>

				<tr class="stats">
					<td><img src="<?=IMAGE_URL?>stats/shooting.gif" title="<?=$unit_shooting?>" /> <?=$v['stats']['shooting']?></td>		
					<td><img src="<?=IMAGE_URL?>stats/ranDef.gif" title="<?=$unit_defAr?>" /> <?=$v['stats']['defAr']?>%</td>
					<td><img src="<?=IMAGE_URL?>stats/magDef.gif" title="<?=$unit_defMag?>" /> <?=$v['stats']['defMag']?>%</td>
					<td><img src="<?=IMAGE_URL?>stats/village.gif" title="<?=$unit_amount?>" /> <?=$v['available']?> / <?=$v['total']?></td>

				</tr>
				
				<?php if ($this->isTrue ('showSpeed')) { ?>
					<tr>
						<td colspan="2">
							<?=$this->getText ('speed', 'unitStats', 'main')?>
						</td>
				
						<td colspan="3">
							<?=$v['stats']['speed'];?>
						</td>
					</tr>
				<?php } ?>

				<?php if ($this->isTrue ('showCost')) { ?>
					<tr>
						<td colspan="2">
							<?=$this->getText ('cost', 'unitStats', 'main')?>
						</td>
				
						<td colspan="3">
							<?=$v['cost'];?>
						</td>
					</tr>
				<?php } ?>
		
				<?php if ($this->isTrue ('showConsumption')) { ?>
					<tr>
						<td colspan="2">
							<?=$this->getText ('consumption', 'unitStats', 'main')?>
						</td>
						
						<td colspan="3">
							<?=$v['consumption'];?>
						</td>
					</tr>
				<?php } ?>
			
			</table>
		</div>
<?php } } else { echo '<p>'.$this->getText ('noUnits', 'units', 'unit').'</p>'; } ?>
