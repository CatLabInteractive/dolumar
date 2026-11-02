<?php if (isset ($list_players)) { ?>
	<table>
		<tr>
			<th>Player</th>
			<th>Premium until</th>
			<th>Credits left</th>
			<?php if (!empty ($didRefund)) { ?><th>Refunded</th><?php } ?>
		</tr>
		<?php foreach ($list_players as $v) { ?>
			<tr>
				<td><?=$v['name']?></td>
				<td><?=$v['enddate']?></td>
				<td><?=$v['credits']?></td>
				<?php if (!empty ($didRefund)) { ?>
					<td>
						<?=(int)$v['refunded']?>
						<?php if ($v['refund_pending'] > 0) { ?>(<?=(int)$v['refund_pending']?> pending, retried automatically)<?php } ?>
						<?php if ($v['refund_failed'] > 0) { ?>(<?=(int)$v['refund_failed']?> FAILED, see logs)<?php } ?>
					</td>
				<?php } ?>
			</tr>
		<?php } ?>
	</table>
<?php } ?>

<?php if (!empty ($canRefund)) { ?>
	<form method="post" action="<?=htmlspecialchars ($formAction)?>" onsubmit="return confirm('Refund the remaining premium of every player listed?');">
		<input type="hidden" name="refund" value="1" />
		<input type="hidden" name="token" value="<?=htmlspecialchars ($token)?>" />
		<p>
			Refunds, per player, the newest granted premium purchases covering the premium left
			(one purchase per started 15 days). Premium itself is not shortened, so run this once.
		</p>
		<button type="submit">Refund premium</button>
	</form>
<?php } ?>
