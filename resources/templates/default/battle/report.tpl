<?php $this->setTextSection ('report', 'battle'); ?>

<!--<h2><?=$title?></h2>-->

<ul class="tabs">
	<li>
		<a href="javascript:void(0);" onclick="windowAction(this,'overview=true');"><?=$this->getText ('reports')?></a>
	</li>

	<?php if (isset ($show_summary)) { ?>
		<li <?= ($cur_tab == 'summary' ? 'class="active"' : null) ?>>
			<a href="javascript:void(0);" onclick="windowAction (this, {'report':<?=$reportid?>,'fightlog':0});"><?=$this->getText ('summary')?></a>
		</li>
	<?php } ?>
	
	<li <?= ($cur_tab == 'fightlog' ? 'class="active"' : null) ?>>
		<a href="javascript:void(0);" onclick="windowAction (this, {'report':<?=$reportid?>,'fightlog':1});"><?=$this->getText ('fightlog')?></a>
	</li>
</ul>

<table class="tlist belowTabs">

	<?php if (isset ($attacker_value)) { ?>
		<tr>
			<td style="width: 25%;"><?=$attacker?>:</td>
			<td><a href="javascript:void(0);" onclick="openWindow('villageProfile', {'village':<?php echo $attacker_id; ?>});"><?=$attacker_value?></a></td>
		</tr>
	<?php } ?>

	<?php if (isset ($defender_value)) { ?>
		<tr>
			<td><?=$defender?>:</td>
			<td><a href="javascript:void(0);" onclick="openWindow('villageProfile', {'village':<?php echo $defender_id; ?>});"><?=$defender_value?></a></td>
		</tr>
	<?php } ?>

	<tr>
		<td><?=$date?>:</td>
		<td><?=$date_value?></td>
	</tr>

	<?php if (isset ($victory_value)) { ?>
		<tr>
			<td><?=$victory?>:</td>
			<td><?=$victory_value?></td>
		</tr>
	<?php } ?>
</table>

<?php if (isset ($attacking)) { ?>
<h3><?=$attacking?></h3>
<div class="fancybox">
	<table>
		<tr>
			<th style="width: 44%;"><?=$unit?></th>
			<th style="width: 27%; text-align: center;"><?=$amount?></th>
			<th style="text-align: center;"><?=$died?></th>
		</tr>

		<?php if (isset ($list_attacking) && is_array ($list_attacking)) { ?>
			<?php foreach ($list_attacking as $v) { ?>
			<tr>
				<td><?=$v[0]?></td>
				<td style="text-align: center;"><?=$v[1]?></td>
				<td style="text-align: center;"><?=$v[2]?></td>
			</tr>
			<?php } ?>
		<?php } ?>
	</table>
</div>
<?php } ?>

<?php if (isset ($defending)) { ?>
	<h3><?=$defending?></h3>
	<div class="fancybox">
	<table>
		<tr>
			<th style="width: 44%;"><?=$unit?></th>
			<th style="width: 27%; text-align: center;"><?=$amount?></th>
			<th style="text-align: center;"><?=$died?></th>
		</tr>

		<?php if (isset ($list_defending)) { ?>
			<?php if ($list_defending == false) { ?>
				<td colspan="3"><?=$secretUnits?></td>
			<?php } else { ?>
				<?php foreach ($list_defending as $v) { ?>
					<tr>
						<td><?=$v[0]?></td>
						<td style="text-align: center;"><?=$v[1]?></td>
						<td style="text-align: center;"><?=$v[2]?></td>
					</tr>
				<?php } ?>
			<?php } ?>
		<?php } else { ?>
		<tr>
			<td colspan="3"><?=$noDefending?></td>
		</tr>
		<?php } ?>
	</table>
	</div>
<?php } ?>

<!-- Special units -->
<?php if (isset ($list_attacking_su)) { ?>
	<h3><?=$this->getText ('attSpecialUnits');?></h3>
	<div class="fancybox">
	<table class="tlist">
		<?php foreach ($list_attacking_su as $v) { ?>
			<tr>
				<td><?=$v['name']?></td>
				<td><?= $this->getText ($v['died'] ? 'died' : 'survived'); ?></td>
			</tr>
		<?php } ?>
	</table>
	</div>
<?php } ?>

<?php if (isset ($list_runes)) { ?>
	<h3><?=$runes?></h3>
	<p class="maybe">
		<?php foreach ($list_runes as $v) { ?>
			&raquo; <?=$v[1]?> <?=$v[0]?><br />
		<?php } ?>
	</p>
<?php } ?>

<?php if (isset ($stolen_value)) { ?>
	<h3><?=$stolen?></h3>
	<p class="maybe" style="text-align: center;"><?=$stolen_value?></p>
<?php } ?>

<?php if (isset ($report)) { ?>
	<!--<h3><?=$this->getText ('livereport'); ?></h3>-->
	<div class="report">
		<?=$report?>
	</div>
<?php } ?>

<!--
<p><?=$toReturn[0]?><a href="javascript:void(0);" onclick="windowAction(this,'overview=true');"><?=$toReturn[1]?></a><?=$toReturn[2]?></p>
-->
