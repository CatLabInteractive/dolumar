<?php $this->setTextSection ('market', 'buildings'); ?>

<h3><?=$this->getText ('transfers'); ?></h3>
<?php if (isset ($list_transfers)) { ?>
	<table>
	<?php $alternate = true; ?>
	<?php foreach ($list_transfers as $v) { ?>
		<?php
			if ($alternate)
			{
				$alternate = false;
				$rowclass = "odd";
			}
			else
			{
				$alternate = true;
				$rowclass = "even";
			}
		?>
	
		<tr class="<?=$rowclass?> remove-after-countdown">
			<td><?=$v['countdown']?></td>
			<td><?= $this->putIntoText ($this->getText ('transfer_' .$v['direction'].'_'. $v['type']), array ('from' => $v['from'], 'to' => $v['to'])); ?></td>
		</tr>
	<?php } ?>
	</table>
<?php } else { ?>
	<p><?=$this->getText ('noTransfers'); ?></p>
<?php } ?>

<p>
	<a href="javascript:void(0);" onclick="windowAction(this,{'action':'donate'});"><?=$this->getText ('sendToVillage')?></a>
</p>

<?=$overview?>


