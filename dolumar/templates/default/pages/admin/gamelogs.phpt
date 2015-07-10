<h2>Game Logs</h2>

<h3>Logs for players:</h3>
<?php if (isset ($list_players)) { ?>
	<table>
		<?=$tdif = false; ?>
		<?php foreach ($list_players as $v) { ?>
			<tr class="type<?=$v['key']?> types">
				<td><a href="<?=$v['url']?>"><?=$v['name']?></a></td>
			</tr>
		<?php } ?>
	</table>
<?php } ?>

<h3>Player logs</h3>

<div class="page-navigation">
	<?php if (isset ($previouspage)) { ?>
		<p class="previous"><a href="<?=$previouspage?>">Previous page</a></p>
	<?php } ?>
	
	<?php if (isset ($nextpage)) { ?>
		<p class="next"><a href="<?=$nextpage?>">Next page</a></p>
	<?php } ?>
</div>

<?php if (isset ($list_logs)) { ?>
	<table>
		<tr>
			<th>Action</th>
			<th>Date</th>
			<th>Player</th>
		</tr>
		
		<?php if (isset ($list_logs)) { ?>
			<?php foreach ($list_logs as $v) { ?>
				<tr class="type<?=$v['key']?> types <?=$v['important']?>">
					<td class="log"><?=$v['action']?></td>
					<td class="date"><?=$v['date']?></td>
					<td class="player"><?=$v['player']?></td>
				</tr>
			<?php } ?>
		<?php } ?>
	</table>
<?php } ?>

<div class="page-navigation">
	<?php if (isset ($previouspage)) { ?>
		<p class="previous"><a href="<?=$previouspage?>">Previous page</a></p>
	<?php } ?>
	
	<?php if (isset ($nextpage)) { ?>
		<p class="next"><a href="<?=$nextpage?>">Next page</a></p>
	<?php } ?>
</div>
