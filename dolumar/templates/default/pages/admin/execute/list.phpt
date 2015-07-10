<h2>Execute moderator actions</h2>

<?php if (isset ($list_actions)) { ?>
	<table>
		<?php $diff = true; ?>
		<?php foreach ($list_actions as $v) { ?>
			<?php $diff = $diff ? false : true; ?>
		
			<tr class="<?=$diff ? 'diff' : null;?>">
				<td class="date"><?=$v['date']?></td>
				<td><?=$v['action']?></td>
				<td class="player"><?=$v['target']?></td>
				<td class="player"><?=$v['actor']?></td>
				
				<td class="action"><a href="<?=$v['accept_url']?>" class="accept"><span>accept</span></a></td>
				<td class="action"><a href="<?=$v['deny_url']?>" class="deny"><span>deny</span></a></td>
			</tr>
			
			<?php if (!empty ($v['reason'])) { ?>
				<tr class="<?=$diff ? 'diff' : null;?>">
					<td colspan="6" class="reason">
						<?=$v['reason']?>
					</td>
				</tr>
			<?php } ?>
		<?php } ?>
	</table>
<?php } else { ?>
	<p>No actions set yet.</p>
<?php } ?>
