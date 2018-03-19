<h3><?=$silo?></h3>

<table class="tlist" style="margin-top: 5px;">

	<tr>
		<th style="width: 33%;"><?=$resource?></th>
		<th style="width: 34%; text-align: center;"><?=$capacity?></th>
		<th style="width: 33%; text-align: center;"><?=$filling?></th>	
	</tr>

	<?php foreach ($list_resources as $v) { ?>	
	<tr>
		<td style="width: 33%;"><?=$v[0]?></td>
		<td style="width: 34%; text-align: center;"><?=$v[4]?></td>
		<td style="width: 33%; text-align: center;"><?=$v[1]?>%</td>
	</tr>
	
	<tr>
		<td style="text-align: center; font-size: 6px;" colspan="3">
			<?php if ($v[2] > 0) { ?><img src="<?=IMAGE_URL?>bar1.gif" style="height: 5px; width: <?=$v[2]?>%;" /><?php } if ($v[3] > 0) { ?><img src="<?=IMAGE_URL?>bar2.gif" style="height: 5px; width: <?=$v[3]?>%;" /><?php } ?>
		</td>
	</tr>
	<?php } ?>
</table>
