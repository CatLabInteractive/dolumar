<?php $this->setTextSection ('unitStats', 'main'); ?>

<table class="table-unit-stats">
	<tr>
		<td style="width: 30%;"><img src="<?=IMAGE_URL?>stats/melee.gif" title="<?=$unit_melee?>" /> <?=$unit['stats']['melee']?> (<span class="stat-frontage"><?=$unit['stats']['frontage']?></span>)</td>
		<td style="width: 25%;"><img src="<?=IMAGE_URL?>stats/infDef.gif" title="<?=$unit_defIn?>" /> <?=$unit['stats']['defIn']?>%</td>
		<td style="width: 25%;"><img src="<?=IMAGE_URL?>stats/cavDef.gif" title="<?=$unit_defCav?>" /> <?=$unit['stats']['defCav']?>%</td>		
		<td><img src="<?=IMAGE_URL?>stats/health.gif" title="<?=$unit_health?>" /> <?=$unit['stats']['hp']?></td>
	</tr>

	<tr>
		<td><img src="<?=IMAGE_URL?>stats/shooting.gif" title="<?=$unit_shooting?>" /> <?=$unit['stats']['shooting']?></td>		
		<td><img src="<?=IMAGE_URL?>stats/ranDef.gif" title="<?=$unit_defAr?>" /> <?=$unit['stats']['defAr']?>%</td>
		<td><img src="<?=IMAGE_URL?>stats/magDef.gif" title="<?=$unit_defMag?>" /> <?=$unit['stats']['defMag']?>%</td>
		<td>&nbsp;</td>
	</tr>
</table>
