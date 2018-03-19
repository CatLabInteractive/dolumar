<?php $this->setTextSection ('logbook', 'account'); ?>

<?php include ('blocks/pagelist.tpl'); ?>

<?php if (isset ($list_logs)) { ?>
	<table style="margin-top: 5px;" class="logbook">
		<?php $alternate = true; ?>
		<?php foreach ($list_logs as $v) { ?>
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
		
			<tr class="<?=$rowclass?>">
				<td class="date"><?=$v['date']?></td>
				<td><?=$v['text']?></td>
				
				<?php if (isset ($v['link'])) { ?>
					<td class="actions">
						<?=$v['link']?>
					</td>
				<?php } ?>
			</tr>
		<?php } ?>
	</table>
<?php } else { ?>

	<p><?=$this->getText ('nologs'); ?></p>

<?php } ?>

<?php $pagelist_loc = 'bottom'; include ('blocks/pagelist.tpl'); ?>
