<?php $this->setTextSection ('clearmultis', 'admin'); ?>

<?php if (isset ($list_logs)) { ?>
	<h2><?=$this->getText ('suspected'); ?> (last <?=round ($timeframe / (60*60*24))?> days)</h2>

	<?php include (TEMPLATE_DIR . 'blocks/pagelist.tpl'); ?>
	
	<table>
		<tr>
			<th>Action</th>
			<th>Date</th>
			<th>Player</th>
		</tr>
		
		<?php if (isset ($list_logs)) { ?>
			<?php foreach ($list_logs as $v) { ?>
				<tr class="type<?=$v['key']?> types">
					<td class="log"><?=$v['action']?></td>
					<td class="date"><?=$v['date']?></td>
					<td class="player"><a href="<?=$v['url']?>"><?=$v['player']?></a></td>
				</tr>
			<?php } ?>
		<?php } ?>
	</table>
<?php } ?>
