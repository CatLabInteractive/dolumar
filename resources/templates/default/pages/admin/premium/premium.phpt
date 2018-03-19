
<?php if (isset ($list_players)) { ?>
	<table>
		<?php foreach ($list_players as $v) { ?>
			<tr>
				<td><?=$v['name']?></td>
				<td><?=$v['enddate']?></td>
				<td><?=$v['credits']?></td>
				<td><?=$v['refunded'] ? 'refunded' : ''; ?></td>
			</tr>
		<?php } ?>
	</table>
<?php } ?>
