<?php $this->setTextSection ('producing', 'buildings'); ?>
<h3><?=$hourlyIncome?></h3>
<p class="maybe" style="text-align: center;">
	<?=$producing1?><br />
	<strong><?=$income?></strong><br />
	<?=$producing2?>
</p>

<?php if (isset ($list_breakdown) && count ($list_breakdown) > 0) { ?>
<?php $hasTiles = count (array_filter (array_column ($list_breakdown, 'image'))) > 0; ?>
<table class="ui-breakdown">
	<?php foreach (array_values ($list_breakdown) as $i => $v) { ?>
	<tr>
		<?php if ($hasTiles) { ?><td class="tile"><?php if (!empty ($v['image'])) { ?><img src="<?=$v['image']?>" alt="" /><?php } ?></td><?php } ?>
		<td><?=$v['label']?></td>
		<td class="number <?=$v['amount'] < 0 ? 'ui-bad' : ''?>"><?=($v['amount'] > 0 && $i > 0 ? '+' : '') . $v['amount']?></td>
	</tr>
	<?php } ?>
	<tr class="total">
		<?php if ($hasTiles) { ?><td class="tile"></td><?php } ?>
		<td><?=$this->getText ('totalIncome')?></td>
		<td class="number"><span class="resource <?=$resource?>">&nbsp;<span>&nbsp;</span></span> <?=$total?></td>
	</tr>
</table>
<?php } ?>

<?php if (!empty ($advice)) { ?>
<p><?=$advice?></p>
<?php } ?>

<?php if (!empty ($explanation)) { ?>
<p class="ui-muted"><?=$explanation?></p>
<?php } ?>
