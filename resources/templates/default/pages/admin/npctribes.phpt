<h2>NPC tribes</h2>

<p>
	Aggression: <strong><?=$aggression?></strong>,
	players below <strong><?=$protectionRunes?></strong> runes are never attacked.
	Standing runs from -100 (hostile) to 100 (allied) and decays toward 0; players never see it.
</p>

<form method="get" action="<?=$formAction?>">
	<p>
		<label>Tribe village id <input type="text" name="vid" value="<?=$vid?>" size="8" /></label>
		<label>Player id <input type="text" name="plid" value="<?=$plid?>" size="8" /></label>
		<button type="submit">Filter</button>
	</p>
</form>

<?php if (isset ($filterText)) { ?>
	<p>Showing <?=$filterText?>. <a href="<?=$clearUrl?>">Show all tribes</a></p>
<?php } ?>

<?php if (isset ($tribe)) { ?>
	<h3>Tribe <?=$tribe['tribe']?></h3>
	<table>
		<tr><th>Village</th><td><?=$tribe['village']?></td></tr>
		<tr><th>Multipliers (threshold / decay / hoard)</th><td><?=$tribe['multipliers']?></td></tr>
		<tr><th>Current request</th><td><?=$tribe['request']?></td></tr>
		<tr><th>Last run</th><td><?=$tribe['lastRun']?></td></tr>
	</table>
<?php } ?>

<?php if (isset ($tribeCount)) { ?>
	<h3>Tribes (<?=$tribeCount?>)</h3>

	<?php if (isset ($list_tribes)) { ?>
		<table>
			<tr>
				<th>Tribe</th>
				<th>Village</th>
				<th>Multipliers</th>
				<th>Request</th>
				<th>Standings</th>
				<th>Created</th>
				<th>Last run</th>
			</tr>
			<?php foreach ($list_tribes as $v) { ?>
				<tr>
					<td><a href="<?=$v['url']?>"><?=$v['tribe']?></a></td>
					<td><?=$v['village']?></td>
					<td><?=$v['multipliers']?></td>
					<td><?=$v['request']?></td>
					<td><?=$v['standings']?></td>
					<td><?=$v['created']?></td>
					<td><?=$v['lastRun']?></td>
				</tr>
			<?php } ?>
		</table>
	<?php } else { ?>
		<p>No tribes yet. They are created by cron/ai.php.</p>
	<?php } ?>

	<div class="page-navigation">
		<?php if (isset ($tribesPrevious)) { ?>
			<p class="previous"><a href="<?=$tribesPrevious?>">Previous tribes</a></p>
		<?php } ?>
		<?php if (isset ($tribesNext)) { ?>
			<p class="next"><a href="<?=$tribesNext?>">Next tribes</a></p>
		<?php } ?>
	</div>
<?php } ?>

<h3>Standings</h3>
<?php if (isset ($list_standings)) { ?>
	<table>
		<tr>
			<th>Tribe</th>
			<th>Player</th>
			<th>Standing</th>
			<th>Tier</th>
			<th>Ladder</th>
			<th>Truce left</th>
			<th>Pact</th>
			<th>Support left</th>
			<th>Last raid by player</th>
		</tr>
		<?php foreach ($list_standings as $v) { ?>
			<tr class="tier-<?=$v['tier']?>">
				<td><a href="<?=$v['tribeUrl']?>"><?=$v['tribe']?></a></td>
				<td><a href="<?=$v['playerUrl']?>"><?=$v['player']?></a> (<a href="<?=$v['userUrl']?>">profile</a>)</td>
				<td><?=$v['value']?></td>
				<td><?=$v['tier']?></td>
				<td><?=$v['stage']?></td>
				<td><?=$v['truce']?></td>
				<td><?=$v['pact']?></td>
				<td><?=$v['support']?></td>
				<td><?=$v['lastAttack']?></td>
			</tr>
		<?php } ?>
	</table>
<?php } else { ?>
	<p>No standings.</p>
<?php } ?>

<h3>Decision log</h3>
<div class="page-navigation">
	<?php if (isset ($logsPrevious)) { ?>
		<p class="previous"><a href="<?=$logsPrevious?>">Newer</a></p>
	<?php } ?>
	<?php if (isset ($logsNext)) { ?>
		<p class="next"><a href="<?=$logsNext?>">Older</a></p>
	<?php } ?>
</div>

<?php if (isset ($list_logs)) { ?>
	<table>
		<tr>
			<th>Date</th>
			<th>Tribe</th>
			<th>Player</th>
			<th>Decision</th>
		</tr>
		<?php foreach ($list_logs as $v) { ?>
			<tr>
				<td class="date"><?=$v['date']?></td>
				<td><a href="<?=$v['tribeUrl']?>"><?=$v['tribe']?></a></td>
				<td><?php if ($v['playerUrl']) { ?><a href="<?=$v['playerUrl']?>"><?=$v['player']?></a><?php } ?></td>
				<td class="log"><?=$v['message']?></td>
			</tr>
		<?php } ?>
	</table>
<?php } else { ?>
	<p>Nothing logged.</p>
<?php } ?>

<div class="page-navigation">
	<?php if (isset ($logsPrevious)) { ?>
		<p class="previous"><a href="<?=$logsPrevious?>">Newer</a></p>
	<?php } ?>
	<?php if (isset ($logsNext)) { ?>
		<p class="next"><a href="<?=$logsNext?>">Older</a></p>
	<?php } ?>
</div>
