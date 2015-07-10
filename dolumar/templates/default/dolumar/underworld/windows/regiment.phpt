<?php $this->setTextSection ('squad', 'underworld'); ?>

<h2><?=$this->getText ('actions'); ?></h2>

<?php if (isset ($nextpoint)) { ?>
	<p class="information">
		<span class="info-icon">
			Next move point in <?=$nextpoint?>
		</span>
	</p>
<?php } ?>

<ul class="actions">
	<li>
		<a href="javascript:void(0);" onclick="selectLocation (this, {'action':'move'});"><?=$this->getText ('move'); ?></a> <?=$this->putIntoText ($this->getText ('movepoints'), array ('<strong>'.$movepoints.'</strong>'))?>
	</li>

	<li>
		<?=Neuron_URLBuilder::getInstance ()->getUpdateUrl ('army', $this->getText ('split'), array ('action' => 'split')); ?>
	</li>

	<li>
		<?=Neuron_URLBuilder::getInstance ()->getUpdateUrl ('army', $this->getText ('dowithdraw'), array ('action' => 'withdraw')); ?>
	</li>
</ul>

<h2><?=$this->getText ('players'); ?></h2>
<?php if (isset ($list_players)) { ?>

	<ul>
		<?php foreach ($list_players as $v) { ?>
			<li>	
				<?=$v['name']?> - <?=$this->getText ($v['status'], 'status')?>
				<?php if ($v['canPromote']) { ?>
					- <?=Neuron_URLBuilder::getInstance ()->getUpdateUrl ('army', $this->getText ('promote'), array ('action' => 'player', 'do' => 'promote', 'player' => $v['id'])); ?>
				<?php } ?>
			
				<?php if ($v['canDemote']) { ?>
					- <?=Neuron_URLBuilder::getInstance ()->getUpdateUrl ('army', $this->getText ('demote'), array ('action' => 'player', 'do' => 'demote', 'player' => $v['id'])); ?>
				<?php } ?>
			</li>
		<?php } ?>
	</ul>

<?php } else { ?>
	<p>No players.</p>
<?php } ?>

<h2><?=$this->getText ('squads')?></h2>

<?php if (isset ($list_squads)) { ?>
	<?php foreach ($list_squads as $v) { ?>
		<h3><?=$v['name']?></h3>
		<div class="fancybox">
		
			<table>
				<?php foreach ($v['units'] as $unit) { ?>
					<tr>
						<td rowspan="3" style="width: 54px;">
							<img src="<?=$unit['image']?>" />
						</td>
					
						<td>
							<?=$v['owner']?>
						</td>
					</tr>
					
					<tr>
						<td>
							<?=$unit['numberedname']?>
						</td>
					</tr>
					
					<tr>
						<td>
							<?=$this->getText ('morale', 'overview', 'squads')?>: <?=$unit['morale']?>
						</td>
					</tr>
				
				<?php } ?>
			</table>
		</div>
	<?php } ?>
<?php } else { ?>
	<p>No regiments.</p>
<?php } ?>
