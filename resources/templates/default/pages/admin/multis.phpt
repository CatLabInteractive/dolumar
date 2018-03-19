<h2>Users logging on using the same IP (last <?=round ($timeframe / (60*60*24))?> days)</h2>
<?php if (isset ($list_players)) { ?>
	<table id="multihunter">
		<?=$tdif = false; ?>
		<?php foreach ($list_players as $v) { ?>
			<tr class="first <?php echo $tdif ? 'diff' : null; $tdif = $tdif ? false : true; ?> <?= $v['cleared'] ? 'cleared' : ''; ?>">
				<td rowspan="<?=count($v['players'])?>" class="ip">
				
					<?=$v['ip']?><br />
					<a href="<?=$v['combined_logs_url']?>">watch logs</a><br />
					<a href="<?=$v['clearmultis']?>">clear multis</a>
				
				</td>
			<?php for ($i = 0; $i < count ($v['players']); $i ++) { ?>
				<?php if ($i > 0) { ?></tr><tr class="<?php echo !$tdif ? 'diff' : null;?> <?= $v['cleared'] ? 'cleared' : ''; ?>"><?php } ?>
				
					<?php $classname = $v['players'][$i]['cleared'] ? 'cleared' : ''; ?>
				
					<td class="<?=$classname?>">
						<?php if (!empty ($v['players'][$i]['name'])) { ?>
							<a href="<?=$v['players'][$i]['url']?>"><?=$v['players'][$i]['name']?></a>
						<?php } else { ?>&nbsp;<?php } ?>
					</td>
					
					<td class="actions <?=$classname?>">
						<a href="<?=$v['players'][$i]['logs_url']?>">watch logs</a>
					</td>
			<?php } ?>
			</tr>
		<?php } ?>
	</table>
<?php } else { ?>
	<p>No players have logged in using the same IP.</p>
<?php } ?>

<form method="get" id="timeframe">
	<fieldset>
		<legend>Change checking timeframe</legend>
		<ol>
			<li>
				<label for="timeframe">Choose timeframe</label>
				<select name="timeframe" id="timeframe" onchange="this.form.submit();">
					<?php for ($i = 1; $i < 11; $i ++) { ?>
						<option value="<?=$i * 60 * 60 * 24?>" <?php if ($i*60*60*24 == $timeframe) { ?>selected="selected"<?php } ?>><?=$i?> days</option>
					<?php } ?>
				</select>
			</li>
			
			<li class="buttons">
				<button type="submit">Change timeframe</button>
			</li>
		</ol>
	</fieldset>
</form>
